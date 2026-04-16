import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['item', 'counter', 'filterButton', 'empty', 'dropdown', 'badge', 'notificationItem', 'notificationTitle', 'notificationMessage', 'notificationDate'];
    static classes = ['read'];

    connect() {
        this.activeFilter = 'all';
        this.render();
    }

    filter(event) {
        this.activeFilter = event.currentTarget.dataset.notificationFilterParam ?? 'all';
        this.render();
    }

    async markRead(event) {
        const item = event.currentTarget.closest('.notification-item');
        if (!item) return;

        const notificationId = item.dataset.notificationId;
        if (!notificationId) {
            item.classList.add(this.readClass);
            event.currentTarget.remove();
            this.render();
            return;
        }

        try {
            const response = await fetch(`/api/notifications/${notificationId}/read`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                },
                credentials: 'same-origin',
            });

            if (response.ok) {
                item.classList.add(this.readClass);
                event.currentTarget.remove();
                this.render();
            }
        } catch (error) {
            console.error('Error marking notification as read:', error);
        }
    }

    dismiss(event) {
        const item = event.currentTarget.closest('.notification-item');
        if (!item) return;

        item.remove();
        this.render();
    }

    render() {
        let unreadCount = 0;
        let visibleCount = 0;

        if (this.hasItemTarget) {
            this.itemTargets.forEach((item) => {
                const state = item.dataset.notificationState ?? 'unread';
                const isVisible = this.activeFilter === 'all' || state === this.activeFilter;

                item.classList.toggle('d-none', !isVisible);
                visibleCount += isVisible ? 1 : 0;
                unreadCount += state === 'unread' ? 1 : 0;
            });
        } else {
            const items = this.element.querySelectorAll('.notification-item');
            items.forEach((item) => {
                if (!item.classList.contains('is-read')) {
                    unreadCount += 1;
                }
            });
        }

        if (this.hasCounterTarget) {
            this.counterTargets.forEach((target) => {
                target.textContent = `${unreadCount}`;
            });
        }

        if (this.hasBadgeTarget) {
            this.badgeTargets.forEach((badge) => {
                if (unreadCount > 0) {
                    badge.textContent = unreadCount;
                    badge.classList.remove('d-none');
                } else {
                    badge.classList.add('d-none');
                }
            });
        }

        if (this.hasFilterButtonTarget) {
            this.filterButtonTargets.forEach((button) => {
                button.classList.toggle('is-active', (button.dataset.notificationFilterParam ?? 'all') === this.activeFilter);
            });
        }

        if (this.hasEmptyTarget) {
            this.emptyTargets.forEach((target) => {
                target.classList.toggle('d-none', visibleCount !== 0);
            });
        }
    }
}
