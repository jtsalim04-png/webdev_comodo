import { Controller } from '@hotwired/stimulus';

/**
 * Single poll for the page — refreshes every [data-realtime-topic] target when data changes.
 */
export default class extends Controller {
    static values = {
        since: { type: Number, default: 0 },
        interval: { type: Number, default: 5000 },
        pollUrl: { type: String, default: '/realtime/poll' },
    };

    connect() {
        this.timer = window.setInterval(() => this.tick(), this.intervalValue);
        document.addEventListener('visibilitychange', this.onVisibilityChange);
    }

    disconnect() {
        window.clearInterval(this.timer);
        document.removeEventListener('visibilitychange', this.onVisibilityChange);
    }

    onVisibilityChange = () => {
        if (!document.hidden) {
            this.tick();
        }
    };

    async tick() {
        if (document.hidden) {
            return;
        }

        try {
            const pollResponse = await fetch(
                `${this.pollUrlValue}?since=${encodeURIComponent(this.sinceValue)}`,
                {
                    headers: { Accept: 'application/json' },
                    credentials: 'same-origin',
                },
            );

            if (!pollResponse.ok) {
                return;
            }

            const pollData = await pollResponse.json();
            if (!pollData.changed) {
                return;
            }

            this.sinceValue = pollData.version;
            await this.refreshAllTargets();
        } catch (error) {
            // retry on next interval
        }
    }

    async refreshAllTargets() {
        const targets = document.querySelectorAll('[data-realtime-topic]');
        await Promise.all(Array.from(targets).map((element) => this.refreshTarget(element)));
        document.dispatchEvent(new CustomEvent('realtime:updated'));
    }

    async refreshTarget(element) {
        const topic = element.dataset.realtimeTopic;
        if (!topic) {
            return;
        }

        const query = element.dataset.realtimeQuery || window.location.search || '';
        const response = await fetch(`/realtime/fragment/${topic}${query}`, {
            headers: { Accept: 'text/html' },
            credentials: 'same-origin',
        });

        if (!response.ok) {
            return;
        }

        const html = await response.text();
        this.applyUpdate(element, html);
    }

    applyUpdate(element, html) {
        this.destroyDataTables(element);

        const mode = element.dataset.realtimeMode || 'inner';
        if (mode === 'inner') {
            element.innerHTML = html;
            return;
        }

        if (mode === 'tbody') {
            const table = element.tagName === 'TABLE' ? element : element.querySelector('table');
            if (table) {
                table.querySelector('tbody')?.remove();
                table.insertAdjacentHTML('beforeend', html);
            }
            return;
        }

        element.outerHTML = html;
    }

    destroyDataTables(scope) {
        if (!window.jQuery?.fn?.DataTable) {
            return;
        }

        const tables = scope.tagName === 'TABLE'
            ? [scope]
            : scope.querySelectorAll('table.datatable');

        tables.forEach((table) => {
            if (window.jQuery.fn.DataTable.isDataTable(table)) {
                window.jQuery(table).DataTable().destroy(false);
            }
        });
    }
}
