import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
    static targets = ["menu", "label"];

    connect() {
        this.closeOnMouseLeave = this.closeOnMouseLeave.bind(this);
    }

    toggle(e) {
        e.preventDefault();
        e.stopPropagation();

        const isHidden = this.menuTarget.classList.contains('hidden');

        if (isHidden) {
            this.open();
        } else {
            this.close();
        }
    }

    open() {
        this.menuTarget.classList.remove('hidden');
        this.labelTarget.classList.add('invisible');

        this.element.style.zIndex = "100";
        this.element.style.position = "relative";

        this.menuTarget.addEventListener('mouseleave', this.closeOnMouseLeave);

        this.clickOutHandler = (e) => {
            if (!this.element.contains(e.target)) this.close();
        };
        setTimeout(() => document.addEventListener('click', this.clickOutHandler, { once: true }), 0);
    }

    closeOnMouseLeave() {
        this.close();
    }

    close() {
        this.menuTarget.classList.add('hidden');
        this.labelTarget.classList.remove('invisible');

        this.menuTarget.removeEventListener('mouseleave', this.closeOnMouseLeave);

        this.element.style.zIndex = "";
    }

    async change(e) {
        e.preventDefault();
        const newStatus = e.currentTarget.dataset.statusValue;
        const url = e.currentTarget.dataset.url;

        try {
            const response = await fetch(url, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                body: JSON.stringify({ status: newStatus }),
                credentials: 'include'
            });
            if (response.ok) window.location.reload();
        } catch (error) { console.error(error); }
    }
}
