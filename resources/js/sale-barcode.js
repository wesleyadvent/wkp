const section = document.getElementById('sale-barcode');
if (section) {
    const input = document.getElementById('barcode');
    const select = document.getElementById('product_id');
    const branch = document.getElementById('branch_id');
    const fields = document.getElementById('note-fields');
    const message = document.getElementById('barcode-message');
    const video = document.getElementById('barcode-video');
    const camera = document.getElementById('start-barcode-camera');
    const close = document.getElementById('stop-barcode-camera');
    let pending, generation = 0, controls, cameraGeneration = 0;
    const stopCamera = () => {
        cameraGeneration++;
        controls?.stop(); controls = null;
        const stream = video.srcObject;
        stream?.getTracks().forEach(track => track.stop());
        video.srcObject = null;
        video.hidden = true; close.hidden = true; camera.disabled = false;
    };
    const invalidate = () => { generation++; pending?.abort(); };
    const lookup = async () => {
        if (fields.disabled) return;
        invalidate();
        const version = generation;
        const branchId = branch?.value || section.dataset.branchId;
        if (!branchId) { message.textContent = 'Pilih cabang terlebih dahulu.'; return; }
        if (!input.value.trim() || !/^[\x20-\x7E]{1,255}$/.test(input.value.trim())) {
            message.textContent = 'Barcode tidak valid atau rusak. Pindai ulang label.'; return;
        }
        pending = new AbortController();
        const url = new URL(section.dataset.lookupUrl, location.origin);
        url.search = new URLSearchParams({barcode: input.value.trim(), branch_id: branchId, selected_product_id: select.value});
        message.textContent = 'Mencari barang…';
        try {
            const response = await fetch(url, {headers: {Accept: 'application/json'}, signal: pending.signal});
            const data = await response.json();
            if (version !== generation || fields.disabled) return;
            if (!response.ok) {
                message.textContent = Object.values(data.errors || {}).flat()[0] || data.message || 'Barcode tidak dapat dibaca. Pindai ulang.';
                return;
            }
            section.dispatchEvent(new CustomEvent('barcode-product', {detail: data}));
            input.value = data.kode_barang;
            message.textContent = `Barang ${data.kode_barang} ditemukan. Rincian dan harga telah diisi dari database.`;
            input.focus(); input.select();
        } catch (error) {
            if (error.name !== 'AbortError' && version === generation) message.textContent = 'Pencarian gagal. Periksa koneksi lalu coba lagi.';
        }
    };
    input.addEventListener('input', invalidate);
    input.addEventListener('keydown', event => {
        if (event.key === 'Enter') { event.preventDefault(); lookup(); }
    });
    document.getElementById('lookup-barcode').addEventListener('click', lookup);
    select.addEventListener('change', () => {
        invalidate(); input.value = ''; message.textContent = 'Pindai barcode untuk memilih barang.';
    });
    branch?.addEventListener('change', () => {
        invalidate(); stopCamera(); input.value = ''; select.value = ''; select.dispatchEvent(new Event('change')); message.textContent = 'Pindai barang dari cabang yang dipilih.';
    });
    camera.addEventListener('click', async () => {
        if (fields.disabled) return;
        if (!navigator.mediaDevices?.getUserMedia) {
            message.textContent = 'Kamera memerlukan HTTPS atau localhost dan browser yang mendukung kamera. Gunakan scanner atau ketik kode barcode.'; return;
        }
        stopCamera();
        const version = cameraGeneration;
        camera.disabled = true; close.hidden = false;
        message.textContent = 'Izinkan akses kamera, lalu arahkan ke barcode.';
        try {
            const {BrowserMultiFormatOneDReader} = await import('@zxing/browser');
            if (version !== cameraGeneration || fields.disabled) return;
            video.hidden = false;
            const reader = new BrowserMultiFormatOneDReader();
            const started = await reader.decodeFromConstraints({video: {facingMode: {ideal: 'environment'}}, audio: false}, video, (result, error, activeControls) => {
                if (version !== cameraGeneration || fields.disabled) { activeControls.stop(); return; }
                if (result) {
                    activeControls.stop(); stopCamera();
                    input.value = result.getText(); lookup();
                }
            });
            if (version !== cameraGeneration || fields.disabled) started.stop(); else controls = started;
        } catch (error) {
            if (version !== cameraGeneration) return;
            stopCamera();
            message.textContent = error.name === 'NotAllowedError' ? 'Izin kamera ditolak. Izinkan kamera atau gunakan scanner atau ketik kode barcode.' : 'Kamera tidak dapat dibuka. Periksa perangkat atau gunakan scanner atau ketik kode barcode.';
        }
    });
    close.addEventListener('click', stopCamera);
    new MutationObserver(() => { if (fields.disabled) { invalidate(); stopCamera(); } }).observe(fields, {attributes: true, attributeFilter: ['disabled']});
    window.addEventListener('pagehide', stopCamera);
    document.addEventListener('visibilitychange', () => { if (document.hidden) stopCamera(); });
}
