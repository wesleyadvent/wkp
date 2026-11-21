const panel = document.querySelector('[data-arima-prepare]');

if (panel) {
    const progress = panel.querySelector('[data-arima-progress]');
    const prepare = async () => {
        try {
            const response = await fetch(panel.dataset.arimaPrepare, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify({ cutoff: panel.dataset.cutoff, branch_id: panel.dataset.branch || null }),
            });
            const result = await response.json();
            if (!response.ok) {
                throw new Error(result.message || 'Perhitungan ARIMA terhenti. Muat ulang halaman untuk mencoba lagi.');
            }
            if (result.complete) {
                window.location.reload();
                return;
            }
            progress.textContent = result.busy
                ? 'Perhitungan sedang berjalan. Halaman akan memeriksa kembali secara otomatis.'
                : `${result.remaining} kelompok tersisa. Biarkan halaman ini terbuka; 
                hasil akan diperbarui setelah selesai.`;
            window.setTimeout(prepare, result.busy ? 2000 : 100);
        } catch (error) {
            panel.setAttribute('role', 'alert');
            panel.querySelector('.font-semibold').textContent = 'Perhitungan ARIMA belum selesai.';
            progress.textContent = error.message;
        }
    };

    prepare();
}
