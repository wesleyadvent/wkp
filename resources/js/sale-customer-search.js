document.querySelectorAll('[data-customer-search]').forEach((search) => {
    const input = document.getElementById('customer-search');
    const id = document.getElementById('customer_id');
    const branch = document.getElementById('branch_id');
    const list = document.getElementById('customer-results');
    const help = document.getElementById('customer-search-help');
    const fields = document.getElementById('note-fields');
    let timer, pending, version = 0, results = [], active = -1;
    const close = () => { list.hidden = true; input.setAttribute('aria-expanded', 'false'); input.removeAttribute('aria-activedescendant'); active = -1; };
    const invalidate = () => { version++; clearTimeout(timer); pending?.abort(); };
    const choose = customer => {
        invalidate(); id.value = customer.id; input.value = customer.nama; input.setCustomValidity('');
        help.textContent = [customer.telepon, customer.alamat].filter(Boolean).join(' • ') || 'Customer dipilih.';
        close(); input.focus();
    };
    const lookup = async () => {
        const branchId = branch?.type === 'hidden' ? '' : (branch?.value || search.dataset.branchId);
        if (fields.disabled) return;
        if (!input.value.trim()) { help.textContent = 'Ketik nama atau nomor telepon customer.'; return; }
        const current = version;
        pending = new AbortController();
        const url = new URL(search.dataset.url, location.origin);
        url.search = new URLSearchParams({q: input.value.trim(), ...(branchId ? {branch_id: branchId} : {})});
        help.textContent = 'Mencari customer...';
        try {
            const response = await fetch(url, {headers: {Accept: 'application/json'}, signal: pending.signal});
            const data = await response.json();
            if (current !== version || fields.disabled) return;
            if (!response.ok) { help.textContent = 'Customer tidak dapat dicari. Periksa cabang dan coba lagi.'; return; }
            results = data; list.replaceChildren();
            data.forEach((customer, index) => {
                const option = document.createElement('button');
                option.type = 'button'; option.id = `customer-option-${index}`; option.setAttribute('role', 'option'); option.tabIndex = -1;
                option.className = 'block w-full border-b border-gray-100 px-4 py-3 text-left text-sm hover:bg-amber-50';
                const name = document.createElement('span'); name.className = 'block font-semibold text-gray-900'; name.textContent = `${customer.nama} - ${customer.alamat || 'Alamat belum diisi'}`;
                const detail = document.createElement('span'); detail.className = 'mt-1 block text-xs text-gray-500'; detail.textContent = customer.telepon || 'Nomor telepon belum diisi';
                option.append(name, detail); option.addEventListener('click', () => choose(customer)); list.append(option);
            });
            list.hidden = data.length === 0; input.setAttribute('aria-expanded', String(data.length > 0)); active = -1;
            help.textContent = data.length === 0 ? 'Customer tidak ditemukan. Tambahkan melalui menu Customer bila belum terdaftar.' : data.length === 20 ? 'Menampilkan 20 hasil. Ketik lebih lengkap untuk mempersempit pencarian.' : 'Pilih customer yang sesuai.';
        } catch (error) {
            if (error.name !== 'AbortError' && current === version) help.textContent = 'Pencarian gagal. Periksa koneksi dan coba lagi.';
        }
    };
    input.addEventListener('input', () => {
        invalidate(); close(); id.value = ''; input.setCustomValidity('Pilih customer dari hasil pencarian.');
        timer = setTimeout(lookup, 250);
    });
    input.addEventListener('focus', () => { if (!id.value && input.value.trim()) { invalidate(); lookup(); } });
    input.addEventListener('keydown', event => {
        if (event.key === 'Escape') { invalidate(); close(); return; }
        if (event.key === 'Enter' && !id.value) {
            event.preventDefault();
            if (!list.hidden && active >= 0) choose(results[active]);
            else { invalidate(); lookup(); }
        }
        if (!list.hidden && ['ArrowDown', 'ArrowUp'].includes(event.key)) {
            event.preventDefault();
            active = active < 0 ? (event.key === 'ArrowDown' ? 0 : results.length - 1) : (active + (event.key === 'ArrowDown' ? 1 : -1) + results.length) % results.length;
            [...list.children].forEach((option, index) => {
                option.setAttribute('aria-selected', String(index === active));
                option.classList.toggle('bg-amber-50', index === active);
            });
            input.setAttribute('aria-activedescendant', list.children[active].id);
            list.children[active].scrollIntoView({block: 'nearest'});
        }
    });
    branch?.addEventListener('change', () => {
        invalidate(); close(); id.value = ''; input.value = ''; input.setCustomValidity(''); help.textContent = 'Ketik nama atau nomor telepon customer dari semua cabang.';
    });
    document.addEventListener('click', event => { if (!search.contains(event.target)) { invalidate(); close(); } });
    new MutationObserver(() => { if (fields.disabled) { invalidate(); close(); } }).observe(fields, {attributes: true, attributeFilter: ['disabled']});
});
