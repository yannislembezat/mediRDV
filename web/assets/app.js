import './stimulus_bootstrap.js';
import './styles/app.css';

document.documentElement.classList.add('js');

document.addEventListener('DOMContentLoaded', () => {
    const topbar = document.querySelector('[data-app-topbar]');

    if (!topbar) {
        return;
    }

    const syncTopbarState = () => {
        topbar.classList.toggle('is-scrolled', window.scrollY > 16);
    };

    syncTopbarState();
    window.addEventListener('scroll', syncTopbarState, { passive: true });
});
