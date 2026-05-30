import { Controller } from '@hotwired/stimulus';

/**
 * Polls /realtime/poll and swaps HTML fragments — web equivalent of app useFocusEffect + pull-to-refresh.
 */
export default class extends Controller {
    static values = {
        topic: String,
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
            await this.refreshFragment();
        } catch (error) {
            // Silent fail — next poll will retry
        }
    }

    async refreshFragment() {
        const query = window.location.search || '';
        const fragmentUrl = `/realtime/fragment/${this.topicValue}${query}`;
        const response = await fetch(fragmentUrl, {
            headers: { Accept: 'text/html' },
            credentials: 'same-origin',
        });

        if (!response.ok) {
            return;
        }

        const html = await response.text();
        this.applyUpdate(html);
        document.dispatchEvent(new CustomEvent('realtime:updated', { detail: { topic: this.topicValue } }));
    }

    applyUpdate(html) {
        const mode = this.element.dataset.realtimeMode || 'replace';

        if (mode === 'tbody') {
            const table = this.element.closest('table');
            if (table) {
                table.querySelector('tbody')?.remove();
                table.insertAdjacentHTML('beforeend', html);
            }
            return;
        }

        if (mode === 'inner') {
            this.element.innerHTML = html;
            return;
        }

        this.element.outerHTML = html;
    }
}
