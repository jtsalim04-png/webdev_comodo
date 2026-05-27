#!/bin/sh
set -e

cd /app

# Fail fast in production when required env vars are missing.
if [ "${APP_ENV:-prod}" = "prod" ]; then
    if [ -z "${APP_SECRET:-}" ]; then
        echo "ERROR: APP_SECRET is not set (required for prod)."
        exit 1
    fi
    if [ -z "${DATABASE_URL:-}" ]; then
        echo "ERROR: DATABASE_URL is not set (required for prod)."
        exit 1
    fi
    if [ -z "${BREVO_API_KEY:-}" ]; then
        echo "WARN: BREVO_API_KEY is not set — outbound email will fail in prod."
    fi
fi

# Railway public URL
if [ -z "${APP_URL:-}" ] && [ -n "${RAILWAY_PUBLIC_DOMAIN:-}" ]; then
    export APP_URL="https://${RAILWAY_PUBLIC_DOMAIN}"
    echo "APP_URL set from RAILWAY_PUBLIC_DOMAIN: ${APP_URL}"
fi

if [ -z "${DEFAULT_URI:-}" ] && [ -n "${APP_URL:-}" ]; then
    export DEFAULT_URI="${APP_URL}"
fi

PORT="${PORT:-8080}"
echo "Nginx binding 0.0.0.0:${PORT} — set Railway Public Networking to port ${PORT}"

NGINX_CONF="/etc/nginx/conf.d/default.conf"
if [ -f "$NGINX_CONF" ] && [ ! -w "$NGINX_CONF" ]; then
    echo "nginx.conf is read-only (mounted); using baked listen port"
elif [ -f "$NGINX_CONF" ]; then
    sed -i "s/listen 8080/listen ${PORT}/g" "$NGINX_CONF" 2>/dev/null || true
    sed -i "s/listen \[::\]:8080/listen [::]:${PORT}/g" "$NGINX_CONF" 2>/dev/null || true
fi

console() {
    if [ -f vendor/autoload_runtime.php ]; then
        php bin/console "$@"
    else
        echo "WARN: vendor/autoload_runtime.php missing; skipping: $*"
        return 1
    fi
}

bootstrap_background() {
    (
        if [ -n "${DATABASE_URL:-}" ]; then
            echo "[background] Waiting for database..."
            i=0
            while [ "$i" -lt 30 ]; do
                if console doctrine:query:sql "SELECT 1" >/dev/null 2>&1; then
                    break
                fi
                i=$((i + 1))
                sleep 1
            done

            echo "[background] Running migrations..."
            console doctrine:migrations:migrate --no-interaction
        fi

        if [ ! -f config/jwt/private.pem ] || [ ! -f config/jwt/public.pem ]; then
            echo "[background] Generating JWT key pair..."
            console lexik:jwt:generate-keypair --skip-if-exists
        fi

        if [ "${APP_ENV:-prod}" = "prod" ]; then
            echo "[background] Warming prod cache..."
            console cache:clear --no-warmup
            console cache:warmup
            chown -R www-data:www-data var/cache var/log 2>/dev/null || true
        fi

        echo "[background] Bootstrap finished."
    ) &
}

bootstrap_background

php-fpm -D

nginx -t
echo "Starting nginx on 0.0.0.0:${PORT}..."
nginx -g 'daemon off;' &
NGINX_PID=$!

trap 'kill $NGINX_PID 2>/dev/null; exit 0' TERM INT

i=0
while [ "$i" -lt 30 ]; do
    if curl -sf "http://127.0.0.1:${PORT}/health.html" >/dev/null 2>&1; then
        echo "[probe] health.html OK on 127.0.0.1:${PORT} (after ${i}s)"
        break
    fi
    if ! kill -0 "$NGINX_PID" 2>/dev/null; then
        echo "[probe] nginx process exited unexpectedly"
        exit 1
    fi
    i=$((i + 1))
    sleep 1
done

if [ "$i" -ge 30 ]; then
    echo "[probe] health.html FAILED on 127.0.0.1:${PORT} after 30s"
    nginx -T 2>&1 | tail -20 || true
    exit 1
fi

wait "$NGINX_PID"
