<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[.18em] text-amber-600">Operasional toko</p>
                <h2 class="mt-1 text-xl font-semibold text-slate-900">Buat Nota Service</h2>
            </div>
            <span class="rounded-full border border-blue-200 bg-blue-50 px-3 py-1.5 text-xs font-semibold text-blue-700">Status awal: Diproses</span>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('service-notes.store') }}" class="space-y-6">
                @csrf
                <x-input-error :messages="$errors->all()" />
                <x-employee-select />
                <fieldset id="note-fields" disabled class="space-y-6 disabled:opacity-60" aria-describedby="employee-verification-status">
                    <section class="relative overflow-visible rounded-2xl border border-slate-200 bg-white shadow-sm">
                        <div class="rounded-t-2xl border-b border-slate-100 bg-gradient-to-r from-amber-50 to-white px-6 py-5">
                            <p class="text-xs font-semibold uppercase tracking-[.16em] text-amber-700">Langkah 1</p>
                            <h3 class="mt-1 text-lg font-semibold text-slate-900">Identitas nota</h3>
                            <p class="mt-1 text-sm text-slate-500">Pilih customer dengan mengetik nama atau nomor telepon. Suggestion menampilkan nama dan alamat.</p>
                        </div>
                        <div class="grid gap-5 p-6 sm:grid-cols-2">
                            <input type="hidden" id="branch_id" name="branch_id" value="{{ old('branch_id', Auth::user()->branch_id) }}">
                            <div>
                                <x-input-label for="asal_barang" value="Asal barang" />
                                <select id="asal_barang" name="asal_barang" class="mt-1 block w-full rounded-lg border-slate-300">
                                    <option value="toko_sendiri" @selected(old('asal_barang', 'toko_sendiri') === 'toko_sendiri')>Barang dari toko sendiri</option>
                                    <option value="barang_luar" @selected(old('asal_barang') === 'barang_luar')>Barang dari luar</option>
                                </select>
                            </div>
                            <div id="service-customer-search" data-customer-search data-url="{{ route('sales.customer-query') }}" class="relative sm:col-span-2">
                                <x-input-label for="customer-search" value="Customer" />
                                <x-text-input id="customer-search" type="search" class="mt-1 block w-full" value="{{ $selectedCustomer?->nama }}" required autocomplete="off" maxlength="100" placeholder="Ketik nama atau nomor telepon" role="combobox" aria-autocomplete="list" aria-expanded="false" aria-controls="customer-results" aria-describedby="customer-search-help" />
                                <input type="hidden" id="customer_id" name="customer_id" value="{{ old('customer_id') }}">
                                <div id="customer-results" role="listbox" hidden class="absolute z-30 mt-1 max-h-64 w-full overflow-y-auto rounded-xl border border-slate-200 bg-white shadow-xl"></div>
                                <p id="customer-search-help" role="status" class="mt-2 text-xs text-slate-500">Ketik nama customer untuk melihat suggestion.</p>
                            </div>
                        </div>
                    </section>

                    <x-sale-reference :endpoint="route('service-notes.find-sale-query')" source="asal_barang" name-field="nama_barang_manual" />

                    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                        <div class="border-b border-slate-100 bg-gradient-to-r from-slate-50 to-white px-6 py-5">
                            <p class="text-xs font-semibold uppercase tracking-[.16em] text-slate-500">Langkah 2</p>
                            <h3 class="mt-1 text-lg font-semibold text-slate-900">Data barang</h3>
                            <p class="mt-1 text-sm text-slate-500">Pastikan identitas dan berat barang sesuai dengan barang yang diterima.</p>
                        </div>
                        <div class="grid gap-5 p-6 sm:grid-cols-2">
                            <div>
                                <x-input-label for="nama_barang_manual" value="Nama barang" />
                                <x-text-input id="nama_barang_manual" name="nama_barang_manual" type="text" :value="old('nama_barang_manual', '')" class="mt-1 block w-full" required />
                            </div>
                            <div>
                                <x-input-label for="berat" value="Berat (gram)" />
                                <x-text-input id="berat" name="berat" type="number" :value="old('berat', '')" class="mt-1 block w-full" step="0.001" min="0.001" required />
                            </div>
                        </div>
                    </section>

                    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                        <div class="flex flex-wrap items-start justify-between gap-4 border-b border-slate-100 bg-gradient-to-r from-blue-50 to-white px-6 py-5">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-[.16em] text-blue-600">Langkah 3</p>
                                <h3 class="mt-1 text-lg font-semibold text-slate-900">Pengerjaan service</h3>
                                <p class="mt-1 text-sm text-slate-500">Tambahkan satu atau beberapa jenis service dalam satu nota.</p>
                            </div>
                            <button type="button" id="add-service" class="action-button action-detail">+ Tambah jenis service</button>
                        </div>
                        <div class="space-y-4 p-6">
                            <div id="service-items" class="space-y-4"></div>
                            <div class="flex items-center justify-between rounded-xl bg-slate-50 px-4 py-4">
                                <span class="text-sm font-medium text-slate-600">Total biaya service</span>
                                <strong id="service-total" class="text-xl text-slate-900">Rp 0</strong>
                            </div>
                            <div class="grid gap-5 border-t border-slate-100 pt-5 sm:grid-cols-2">
                                <div>
                                    <x-input-label for="estimasi_selesai" value="Estimasi selesai" />
                                    <x-text-input id="estimasi_selesai" name="estimasi_selesai" type="date" :value="old('estimasi_selesai', '')" class="mt-1 block w-full" />
                                </div>
                                <div class="rounded-xl border border-blue-100 bg-blue-50 p-4 text-sm text-blue-800">
                                    <span class="font-semibold">Status otomatis: Diproses</span>
                                    <p class="mt-1 text-blue-700">Setelah barang selesai dikerjakan, ubah status melalui tombol detail nota.</p>
                                </div>
                            </div>
                        </div>
                    </section>

                    <div class="flex flex-wrap gap-3 border-t border-slate-200 pt-2">
                        <x-primary-button id="save-note">Simpan Nota Service</x-primary-button>
                        <a href="{{ route('service-notes.index') }}" class="action-button action-detail">Kembali</a>
                    </div>
                </fieldset>
            </form>
        </div>
    </div>

    <template id="service-row-template">
        <div class="service-row rounded-xl border border-slate-200 bg-slate-50 p-4">
            <div class="grid gap-4 md:grid-cols-[minmax(0,1fr)_180px_auto] md:items-end">
                <div>
                    <label class="block text-sm font-medium text-slate-700">Jenis service</label>
                    <select name="service_type_ids[]" class="service-type mt-1 block w-full rounded-lg border-slate-300" required>
                        <option value="">Pilih jenis service</option>
                        @foreach($serviceTypes as $type)<option value="{{ $type->id }}" data-price="{{ $type->harga_default }}">{{ $type->nama_service }}</option>@endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Harga (Rp)</label>
                    <input name="service_prices[]" type="number" step="0.01" min="0" class="service-price mt-1 block w-full rounded-lg border-slate-300" required>
                </div>
                <button type="button" class="remove-service action-button action-delete">Hapus</button>
            </div>
            <div class="mt-4">
                <label class="block text-sm font-medium text-slate-700">Keterangan pengerjaan <span class="font-normal text-slate-400">(opsional)</span></label>
                <textarea name="service_notes[]" rows="2" maxlength="255" class="mt-1 block w-full rounded-lg border-slate-300" placeholder="Contoh: poles ulang dan cek pengunci"></textarea>
            </div>
        </div>
    </template>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const container = document.getElementById('service-items');
            const template = document.getElementById('service-row-template');
            const total = document.getElementById('service-total');
            const oldTypes = @json(old('service_type_ids', []));
            const oldPrices = @json(old('service_prices', []));
            const oldNotes = @json(old('service_notes', []));
            const money = value => new Intl.NumberFormat('id-ID', {style: 'currency', currency: 'IDR', minimumFractionDigits: 2, maximumFractionDigits: 2}).format(Number(value || 0));
            const updateTotal = () => { total.textContent = money([...container.querySelectorAll('.service-price')].reduce((sum, input) => sum + Number(input.value || 0), 0)); };
            const addRow = (type = '', price = '', note = '') => {
                const row = template.content.firstElementChild.cloneNode(true);
                const select = row.querySelector('.service-type');
                const priceInput = row.querySelector('.service-price');
                select.value = type; priceInput.value = price;
                row.querySelector('textarea').value = note;
                select.addEventListener('change', () => { if (!priceInput.value || Number(priceInput.value) === 0) priceInput.value = select.selectedOptions[0]?.dataset.price || ''; updateTotal(); });
                priceInput.addEventListener('input', updateTotal);
                row.querySelector('.remove-service').addEventListener('click', () => { if (container.children.length > 1) { row.remove(); updateTotal(); } });
                container.append(row); updateTotal();
            };
            document.getElementById('add-service').addEventListener('click', () => addRow());
            if (oldTypes.length) oldTypes.forEach((type, index) => addRow(type, oldPrices[index] || '', oldNotes[index] || '')); else addRow();
        });
    </script>
</x-app-layout>
