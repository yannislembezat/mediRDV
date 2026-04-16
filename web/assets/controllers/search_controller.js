import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['input', 'filter', 'item', 'empty', 'count'];

    connect() {
        this.refresh();
    }

    refresh() {
        const query = this.hasInputTarget ? this.inputTarget.value.trim().toLowerCase() : '';
        const filter = this.hasFilterTarget ? this.filterTarget.value.trim().toLowerCase() : '';
        let visibleCount = 0;

        this.itemTargets.forEach((item) => {
            const itemText = (item.dataset.searchText ?? item.textContent).toLowerCase();
            const itemCategory = (item.dataset.searchCategory ?? '').toLowerCase();
            const matchesQuery = query === '' || itemText.includes(query);
            const matchesFilter = filter === '' || itemCategory === filter;
            const isVisible = matchesQuery && matchesFilter;

            item.classList.toggle('d-none', !isVisible);
            visibleCount += isVisible ? 1 : 0;
        });

        if (this.hasCountTarget) {
            const suffix = visibleCount > 1 ? 's' : '';
            this.countTarget.textContent = `${visibleCount} resultat${suffix}`;
        }

        this.emptyTargets.forEach((element) => {
            element.classList.toggle('d-none', visibleCount !== 0);
        });
    }
}
