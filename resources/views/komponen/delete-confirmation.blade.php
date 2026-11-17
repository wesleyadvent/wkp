<dialog id="delete-confirmation" aria-labelledby="delete-title" aria-describedby="delete-description" class="w-full max-w-md rounded-xl bg-white p-6 shadow-xl backdrop:bg-black/50">
    <h3 id="delete-title" class="text-lg font-semibold text-gray-900">Hapus data ini?</h3>
    <p id="delete-description" class="mt-2 text-sm text-gray-600">Data <strong id="delete-label" class="font-semibold text-gray-900"></strong> akan dihapus. Tindakan ini tidak dapat dibatalkan.</p>
    <form id="delete-form" method="POST" class="mt-6 flex justify-end gap-3">
        @csrf
        @method('DELETE')
        <button type="button" id="delete-cancel" autofocus class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Batal</button>
        <button type="submit" id="delete-submit" class="rounded-md bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700 disabled:opacity-50">Ya, Hapus</button>
    </form>
</dialog>
<script>
    (() => {
        const dialog = document.getElementById('delete-confirmation');
        const form = document.getElementById('delete-form');
        const submit = document.getElementById('delete-submit');
        let opener;
        document.querySelectorAll('[data-delete-url]').forEach(button => {
            button.addEventListener('click', () => {
                opener = button;
                form.action = button.dataset.deleteUrl;
                document.getElementById('delete-label').textContent = button.dataset.deleteLabel;
                submit.disabled = false;
                dialog.showModal();
            });
        });
        document.getElementById('delete-cancel').addEventListener('click', () => dialog.close());
        dialog.addEventListener('click', event => {
            const bounds = dialog.getBoundingClientRect();
            if (event.target === dialog && (event.clientX < bounds.left || event.clientX > bounds.right || event.clientY < bounds.top || event.clientY > bounds.bottom)) dialog.close();
        });
        dialog.addEventListener('close', () => opener?.focus());
        form.addEventListener('submit', event => {
            if (!dialog.open || !opener) {
                event.preventDefault();
                return;
            }
            submit.disabled = true;
        });
    })();
</script>
