import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['day', 'input', 'label', 'panel'];

    connect() {
        this.selectedValue = this.hasInputTarget && this.inputTarget.value !== ''
            ? this.inputTarget.value
            : this.dayTargets[0]?.dataset.calendarValue ?? '';

        this.sync(false);
    }

    select(event) {
        this.selectedValue = event.currentTarget.dataset.calendarValue ?? '';
        this.sync(true);
    }

    sync(announce) {
        let activeButton = null;

        this.dayTargets.forEach((button) => {
            const isActive = button.dataset.calendarValue === this.selectedValue;
            button.classList.toggle('is-active', isActive);
            button.setAttribute('aria-pressed', isActive ? 'true' : 'false');

            if (isActive) {
                activeButton = button;
            }
        });

        this.panelTargets.forEach((panel) => {
            panel.classList.toggle('d-none', panel.dataset.calendarPanel !== this.selectedValue);
        });

        if (this.hasInputTarget) {
            this.inputTarget.value = this.selectedValue;
        }

        if (activeButton !== null) {
            const label = activeButton.dataset.calendarLabel ?? activeButton.textContent.trim();

            this.labelTargets.forEach((target) => {
                target.textContent = label;
            });
        }

        if (announce) {
            this.dispatch('changed', {
                detail: { value: this.selectedValue },
            });
        }
    }
}
