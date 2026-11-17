const form = document.getElementById('product-wizard');
if (form) {
    const setup = document.getElementById('wizard-setup');
    const editor = document.getElementById('wizard-editor');
    const quantity = document.getElementById('jumlah');
    const fields = [...form.querySelectorAll('[data-unit-field]')];
    const next = document.getElementById('wizard-next');
    const errorBox = document.getElementById('wizard-error');
    const photo = document.getElementById('unit-photo');
    const photoName = document.getElementById('unit-photo-name');
    const removePhoto = document.getElementById('unit-photo-remove');
    const preview = document.getElementById('unit-photo-preview');
    let previewUrl;
    const prices = JSON.parse(form.dataset.prices);
    const money = value => new Intl.NumberFormat('id-ID', {style: 'currency', currency: 'IDR'}).format(value);
    const drafts = [];
    let current = 0, count = 1, saving = false;
    const draft = () => drafts[current] ??= {ongkos: '0'};
    const syncTotal = () => {
        const price = prices[document.getElementById('unit-purity').value];
        document.getElementById('unit-price').value = price === undefined ? '' : money(Number(price));
        document.getElementById('unit-total').textContent = money(Number(document.getElementById('unit-weight').value || 0) * Number(price || 0) + Number(document.getElementById('unit-fee').value || 0));
    };
    const syncPhoto = () => {
        photoName.textContent = draft().photo ? draft().photo.name : 'Maksimal 2 MB per foto.';
        removePhoto.hidden = !draft().photo;
        if (previewUrl) URL.revokeObjectURL(previewUrl);
        previewUrl = draft().photo ? URL.createObjectURL(draft().photo) : null;
        preview.hidden = !previewUrl;
        if (previewUrl) preview.src = previewUrl; else preview.removeAttribute('src');
    };
    window.addEventListener('pagehide', () => { if (previewUrl) URL.revokeObjectURL(previewUrl); });
    const snapshot = () => fields.forEach(field => { draft()[field.dataset.unitField] = field.value; });
    const show = () => {
        fields.forEach(field => { field.value = draft()[field.dataset.unitField] ?? ''; });
        photo.value = '';
        document.getElementById('wizard-title').textContent = `Barang ${current + 1} dari ${count}`;
        document.getElementById('wizard-progress').max = count;
        document.getElementById('wizard-progress').value = current + 1;
        next.textContent = current === count - 1 ? `Simpan ${count} barang & lihat barcode` : 'Selanjutnya';
        syncTotal(); syncPhoto();
        document.getElementById('wizard-title').focus();
    };
    const valid = scope => [...scope.querySelectorAll('input, select, textarea')].every(field => field.reportValidity());
    const error = text => { errorBox.textContent = text; errorBox.hidden = false; errorBox.scrollIntoView({block: 'center'}); };
    document.getElementById('wizard-start').addEventListener('click', () => {
        if (!valid(setup)) return;
        count = Number(quantity.value); current = 0;
        setup.hidden = true; editor.hidden = false; editor.disabled = false;
        errorBox.hidden = true; show();
    });
    document.getElementById('wizard-prev').addEventListener('click', () => {
        snapshot(); errorBox.hidden = true;
        if (current > 0) { current--; show(); }
        else { setup.hidden = false; editor.hidden = true; editor.disabled = true; quantity.focus(); }
    });
    fields.forEach(field => field.addEventListener('input', syncTotal));
    photo.addEventListener('change', () => {
        const file = photo.files[0];
        if (file && file.size > 2 * 1024 * 1024) { photo.value = ''; error('Ukuran foto maksimal 2 MB.'); return; }
        if (file) draft().photo = file;
        syncPhoto();
    });
    removePhoto.addEventListener('click', () => { delete draft().photo; photo.value = ''; syncPhoto(); });
    const advance = async () => {
        if (saving || editor.hidden || !valid(editor)) return;
        snapshot(); errorBox.hidden = true;
        if (current < count - 1) { current++; show(); return; }
        const data = new FormData(form);
        // JSON avoids PHP's max_input_vars truncating larger batches.
        data.set('items_json', JSON.stringify(drafts.slice(0, count).map(({photo, ...item}) => item)));
        drafts.slice(0, count).forEach((item, index) => { if (item.photo) data.append(`photos[${index}]`, item.photo); });
        saving = true; editor.disabled = true; next.textContent = 'Menyimpan semua barang...';
        try {
            const response = await fetch(form.action, {method: 'POST', body: data, headers: {Accept: 'application/json'}});
            if (!response.ok) {
                const result = await response.json().catch(() => ({}));
                const entries = Object.entries(result.errors || {});
                const index = entries.map(([key]) => key.match(/^(?:items|photos)\.(\d+)/)?.[1]).find(value => value !== undefined);
                if (index !== undefined) current = Number(index);
                editor.disabled = false; show();
                error(entries.map(([key, messages]) => {
                    const match = key.match(/^(?:items|photos)\.(\d+)/);
                    return (match ? `Barang ${Number(match[1]) + 1}: ` : '') + messages.join(' ');
                }).join('\n') || (response.status === 413 ? 'Ukuran seluruh foto terlalu besar. Kurangi ukuran foto lalu coba kembali.' : result.message || 'Penyimpanan gagal. Periksa data dan coba lagi.'));
                return;
            }
            window.location.assign(response.url);
        } catch {
            editor.disabled = false; show(); error('Koneksi terputus. Periksa daftar barang sebelum mencoba menyimpan kembali.');
        } finally { saving = false; editor.disabled = false; }
    };
    next.addEventListener('click', advance);
    form.addEventListener('submit', event => { event.preventDefault(); if (!setup.hidden) document.getElementById('wizard-start').click(); else advance(); });
}
