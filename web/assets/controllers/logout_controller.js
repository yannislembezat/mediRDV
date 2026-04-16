import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    handleSubmit(event) {
        const form = event.target;
        const button = form.querySelector('button[type="submit"]');
        const isDropdownItem = button && button.classList.contains('dropdown-item');

        if (isDropdownItem) {
            event.preventDefault();

            const formData = new FormData(form);

            fetch(form.action, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams(formData).toString(),
                redirect: 'follow'
            }).then(response => {
                if (response.ok || response.redirected) {
                    window.location.href = '/';
                } else {
                    console.error('Logout failed:', response.status);
                    window.location.reload();
                }
            }).catch(error => {
                console.error('Logout error:', error);
                window.location.reload();
            });
        }
    }
}
