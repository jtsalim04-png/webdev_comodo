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

# Ensure frontend importmap vendor assets exist in the running container.
ensure_frontend_assets() {
    # Check if assets already exist in public directory
    if [ -d "public/assets" ] && [ "$(ls -A public/assets 2>/dev/null)" ]; then
        echo "Assets already exist in public/assets"
        return 0
    fi

    echo "=== Building frontend assets ==="
    
    # Run importmap install
    echo "Step 1: Installing importmap packages..."
    if ! console importmap:install --no-interaction; then
        echo "ERROR: importmap:install failed; frontend assets are required."
        return 1
    fi
    
    # CRITICAL: Compile assets to public directory
    echo "Step 2: Compiling assets for production..."
    if ! console asset-map:compile --no-interaction; then
        echo "ERROR: asset-map:compile failed; compiled assets are required."
        echo "Check your asset configuration in config/packages/asset_mapper.yaml"
        return 1
    fi
    
    # Verify assets were created
    if [ ! -d "public/assets" ] || [ -z "$(ls -A public/assets 2>/dev/null)" ]; then
        echo "ERROR: No assets were compiled to public/assets/"
        echo "Running debug:"
        console debug:asset-mapper || true
        return 1
    fi
    
    echo "✓ Assets compiled successfully:"
    echo "  - $(ls public/assets | wc -l) files in public/assets/"
    ls -la public/assets/ | head -10
    
    return 0
}

if ! ensure_frontend_assets; then
    exit 1
fi

bootstrap_background() {
    (
        if [ "${APP_ENV:-prod}" = "prod" ]; then
            echo "[background] Warming prod cache..."
            console cache:clear --no-warmup
            console cache:warmup
            chown -R www-data:www-data var/cache var/log 2>/dev/null || true
        fi

        echo "[background] Bootstrap finished."
    ) &
}

# DB, migrations, and JWT must finish before serving PHP traffic.
if [ -n "${DATABASE_URL:-}" ]; then
    echo "Waiting for database..."
    i=0
    while [ "$i" -lt 30 ]; do
        if console doctrine:query:sql "SELECT 1" >/dev/null 2>&1; then
            break
        fi
        i=$((i + 1))
        sleep 1
    done

    echo "Running migrations..."
    console doctrine:migrations:migrate --no-interaction
fi

if [ ! -f config/jwt/private.pem ] || [ ! -f config/jwt/public.pem ]; then
    echo "Generating JWT key pair..."
    console lexik:jwt:generate-keypair --skip-if-exists
fi

bootstrap_background

mkdir -p var/cache var/log
chown -R www-data:www-data var 2>/dev/null || true

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