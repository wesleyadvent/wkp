<div class="rounded-lg border border-indigo-200 bg-indigo-50 p-4">
    <x-input-label for="employee_name" value="Nama karyawan pembuat nota *" />
    <x-text-input id="employee_name" name="employee_name" :value="old('employee_name')" class="mt-2 block w-full" placeholder="Ketik nama lengkap Anda" autocomplete="off" required maxlength="255" aria-describedby="employee-verification-status" />
    <p id="employee-verification-status" role="status" aria-live="polite" class="mt-2 text-sm text-gray-600">Isi nama karyawan aktif dengan benar untuk membuka form.</p>
    <x-input-error :messages="$errors->get('employee_name')" class="mt-2" />
</div>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const input = document.getElementById('employee_name');
    const fields = document.getElementById('note-fields');
    const status = document.getElementById('employee-verification-status');
    const form = input.closest('form');
    let timer, generation = 0, verifiedName = null, pending;
    const lock = () => {
        fields.disabled = true;
        verifiedName = null;
        generation++;
        pending?.abort();
        clearTimeout(timer);
    };
    const verify = async () => {
        const name = input.value;
        if (!name.trim()) return;
        const version = generation;
        pending = new AbortController();
        status.textContent = 'Memeriksa nama karyawan…';
        try {
            const response = await fetch(@json(route('employees.verify')), {
                method: 'POST', signal: pending.signal,
                headers: {'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content},
                body: JSON.stringify({employee_name: name}),
            });
            const data = await response.json();
            if (version !== generation || input.value !== name) return;
            if (!response.ok || data.valid !== true) {
                status.textContent = data.errors?.employee_name?.[0] || 'Nama belum dapat diverifikasi. Periksa nama atau coba kembali.';
                input.setAttribute('aria-invalid', 'true');
                return;
            }
            verifiedName = name;
            fields.disabled = false;
            input.setAttribute('aria-invalid', 'false');
            status.textContent = 'Nama karyawan terverifikasi. Silakan isi nota.';
        } catch (error) {
            if (error.name !== 'AbortError' && version === generation) status.textContent = 'Verifikasi gagal. Periksa koneksi, lalu ketik ulang nama untuk mencoba kembali.';
        }
    };
    input.addEventListener('input', () => {
        lock();
        input.removeAttribute('aria-invalid');
        status.textContent = 'Isi nama karyawan aktif dengan benar untuk membuka form.';
        if (input.value.trim()) timer = setTimeout(verify, 500);
    });
    input.addEventListener('keydown', event => {
        if (event.key === 'Enter' && fields.disabled) {event.preventDefault();clearTimeout(timer);verify();}
    });
    form.addEventListener('submit', event => {
        if (fields.disabled || verifiedName !== input.value) {event.preventDefault();lock();input.focus();verify();}
    });
    lock();
    if (input.value.trim()) verify();
});
</script>
