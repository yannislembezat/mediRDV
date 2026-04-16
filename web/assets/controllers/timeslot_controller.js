import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['slot', 'input', 'summary'];

    connect() {
        this.selectedValue = this.hasInputTarget ? this.inputTarget.value : '';
        this.sync();
    }

    select(event) {
        const button = event.currentTarget;

        if (button.disabled) {
            return;
        }

        this.selectedValue = button.dataset.timeslotValue ?? '';
        this.sync();
    }

    clear() {
        this.selectedValue = '';
        this.sync();
    }

    sync() {
        let activeLabel = '';

        this.slotTargets.forEach((button) => {
            const isActive = this.selectedValue !== '' && button.dataset.timeslotValue === this.selectedValue;

            button.classList.toggle('is-selected', isActive);
            button.setAttribute('aria-pressed', isActive ? 'true' : 'false');

            if (isActive) {
                activeLabel = button.dataset.timeslotLabel ?? button.textContent.trim();
            }
        });

        if (this.hasInputTarget) {
            this.inputTarget.value = this.selectedValue;
        }

        if (this.hasSummaryTarget) {
            this.summaryTarget.textContent = activeLabel || 'Choisir un horaire';
        }
    }
}
