(() => {
    const dialog = document.getElementById('app-confirm-dialog');
    if (!dialog) return;

    const message = document.getElementById('app-confirm-message');
    const cancelButton = document.getElementById('app-confirm-cancel');
    const acceptButton = document.getElementById('app-confirm-accept');
    let pendingForm = null;
    let approvedForm = null;

    document.addEventListener('submit', (event) => {
        const form = event.target;
        if (!(form instanceof HTMLFormElement) || !form.hasAttribute('data-confirm')) return;

        if (approvedForm === form) {
            approvedForm = null;
            return;
        }

        event.preventDefault();
        pendingForm = form;
        message.textContent = form.dataset.confirm || 'Lanjutkan tindakan ini?';
        const isDanger = form.dataset.confirmVariant === 'danger';
        acceptButton.textContent = isDanger ? 'Ya, hapus' : 'Ya, lanjutkan';
        acceptButton.classList.toggle('button-danger', isDanger);
        dialog.showModal();
    });

    cancelButton.addEventListener('click', () => {
        pendingForm = null;
        dialog.close();
    });

    acceptButton.addEventListener('click', () => {
        const form = pendingForm;
        pendingForm = null;
        dialog.close();
        if (!form || !form.reportValidity()) return;
        approvedForm = form;
        form.requestSubmit();
    });

    dialog.addEventListener('cancel', () => {
        pendingForm = null;
    });

    dialog.addEventListener('click', (event) => {
        if (event.target === dialog) {
            pendingForm = null;
            dialog.close();
        }
    });
})();
