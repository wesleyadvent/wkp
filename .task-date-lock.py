from pathlib import Path
for name,date in [('Sale','tanggal'),('Purchase','tanggal'),('ServiceNote','tanggal_masuk')]:
 p=Path(f'app/Http/Controllers/{name}Controller.php');s=p.read_text().replace("            '"+date+"' => ['required', 'date'],\n",'')
 if name=='ServiceNote':
  s=s.replace("            'tanggal_ambil' => ['nullable', 'date'],\n",'')
  s=s.replace("            $note = ServiceNote::create($data + [", "            $data['tanggal_masuk'] = now()->toDateString();\n            $data['tanggal_ambil'] = $data['status_service'] === 'sudah_diambil' ? now()->toDateString() : null;\n            $note = ServiceNote::create($data + [")
 else:s=s.replace("'tanggal' => $data['tanggal'],", "'tanggal' => now()->toDateString(),")
 p.write_text(s)
p=Path('config/app.php');s=p.read_text().replace("'timezone' => 'UTC'", "'timezone' => 'Asia/Jakarta'");p.write_text(s)
import re
for folder in ['sales','purchases','service-notes']:
 p=Path(f'resources/views/{folder}/form.blade.php');s=p.read_text();s=re.sub(r'<div><x-input-label for="tanggal(?:_masuk)?"[^\n]*?</div>', '',s,count=1)
 s=s.replace('<x-employee-select />','<x-employee-select />\n<fieldset id="note-fields" disabled class="space-y-6 disabled:opacity-50" aria-describedby="employee-verification-status">\n<p class="text-sm text-gray-500">Tanggal nota otomatis mengikuti tanggal saat disimpan (WIB).</p>',1)
 s=s.replace('</form>','</fieldset>\n</form>',1);p.write_text(s)
p=Path('app/Http/Controllers/EmployeeController.php');s=p.read_text().replace('    public function index(Request $request)', '''    public function verify(Request $request): \\Illuminate\\Http\\JsonResponse
    {
        $this->noteEmployee($request);
        return response()->json(['valid' => true]);
    }

    public function index(Request $request)''');p.write_text(s)
p=Path('routes/web.php');s=p.read_text().replace("    Route::get('employees',", "    Route::post('employees/verify', [\\App\\Http\\Controllers\\EmployeeController::class, 'verify'])->middleware('throttle:60,1')->name('employees.verify');\n    Route::get('employees',",1);p.write_text(s)
p=Path('resources/views/components/employee-select.blade.php');p.write_text('''<div class="rounded-lg border border-indigo-200 bg-indigo-50 p-4">
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
''')
