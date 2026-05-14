import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = [
        'viewModal', 'editModal', 'deleteOverlay', 'createModal',
        'editId', 'editTitle', 'editDescription'
    ];

    open(e) {
        e.preventDefault();
        e.stopPropagation();

        const btn = e.target.closest('[data-modal-type]');
        if (!btn) return;

        const type = btn.dataset.modalType;
        if (!type) return;

        if (type === 'edit') {
            const idField = document.querySelector('[data-modal-target="editId"]');
            const titleField = document.querySelector('[data-modal-target="editTitle"]');
            const descField = document.querySelector('[data-modal-target="editDescription"]');

            if (idField) idField.value = btn.dataset.id || '';
            if (titleField) titleField.value = btn.dataset.title || '';
            if (descField) descField.value = btn.dataset.description || '';
        }

        const modalElement = document.querySelector(`[data-modal-target="${type}Modal"]`);

        if (modalElement) {
            modalElement.classList.remove('hidden');
            modalElement.classList.add('flex');
            document.body.classList.add('overflow-hidden');

            if (type === 'edit') {
                const descField = document.querySelector('[data-modal-target="editDescription"]');
                if (descField) {
                    setTimeout(() => {
                        descField.style.height = "";
                        descField.style.height = descField.scrollHeight + "px";
                    }, 0);
                }
            }
        } else {
            console.error(`HTML block data-modal-target="${type}Modal" not found`);
        }
    }


    async submitEdit(e) {
        e.preventDefault();
        e.stopPropagation();

        const id = this.editIdTarget.value;
        const url = `/api/tasks/${id}`;

        try {
            const response = await fetch(url, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    title: this.editTitleTarget.value,
                    description: this.editDescriptionTarget.value
                }),
                credentials: 'include'
            });

            if (response.ok) {
                window.location.reload();
            } else {
                const errorData = await response.json().catch(() => ({}));
                console.error("ERROR EDIT:", response.status, errorData);
                alert(`Failed to update task, error code: ${response.status}`);
            }
        } catch (error) {
            console.error("Critical JS or NETWORK ERROR (EDIT):", error);
            alert("Request ERROR");
        }
    }

    openDeleteConfirm(e) {
        e.preventDefault();
        e.stopPropagation();

        const overlay = this.deleteOverlayTarget;

        if (overlay) {
            overlay.classList.remove('hidden');
            overlay.classList.add('flex');
            this.element.classList.add('is-delete-intent');
        } else {
            console.error("deleteOverlay not found.");
        }
    }

    async confirmDelete(e) {
        e.preventDefault();
        e.stopPropagation();

        const button = e.currentTarget.closest('[data-modal-url-value]') || e.target.closest('[data-modal-url-value]');
        const url = button ? button.getAttribute('data-modal-url-value') : null;

        console.log("DEBUG: Delete button clicked. Attempting to delete at URL:", url);

        if (!url || url === '/' || url === window.location.origin + '/') {
            console.error("ERROR: URL error");
            alert("ERROR: Failed to define task address");
            return;
        }

        try {
            const response = await fetch(url, {
                method: 'DELETE',
                redirect: 'manual',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
                credentials: 'include'
            });

            if (response.ok) {
                const card = this.element.closest('.contents') || this.element.closest('.task-item') || this.element;

                card.style.transition = 'all 0.4s cubic-bezier(0.4, 0, 0.2, 1)';
                card.style.opacity = '0';
                card.style.transform = 'scale(0.8)';

                setTimeout(() => card.remove(), 400);
            } else {
                const errorData = await response.json().catch(() => ({}));
                console.error("ERROR:", response.status, errorData);
                alert(`Failed to delete task error code: ${response.status}`);
            }
        } catch (error) {
            console.error("Critical JS or network error:", error);
            alert("An error occurred while sending the request. Check your internet connection.");
        }
    }

    close(e) {
        if (e) {
            e.preventDefault();
            e.stopPropagation();
        }

        const allModals = [
            ...this.viewModalTargets,
            ...this.editModalTargets,
            ...this.deleteOverlayTargets,
            ...this.createModalTargets
        ];

        allModals.forEach(modal => {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        });

        document.body.classList.remove('overflow-hidden');
    }

    closeDeleteConfirm(e) {
        e.preventDefault();
        e.stopPropagation();

        const overlay = e.currentTarget.closest('[data-modal-target="deleteOverlay"]');
        if (overlay) {
            overlay.classList.add('hidden');
            overlay.classList.remove('flex');
            this.element.classList.remove('is-delete-intent');
        }
    }

    closeBackground(e) {
        if (e.target === e.currentTarget) {
            this.close();
        }
    }

    disconnect() {
        document.body.classList.remove('overflow-hidden');
    }
}
