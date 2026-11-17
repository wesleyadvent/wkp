<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[.18em] text-amber-600">Operasional toko</p>
                <h2 class="mt-1 text-xl font-semibold text-slate-900">Buat Nota Pembelian</h2>
            </div>
            <span
                class="rounded-full border border-blue-200 bg-blue-50 px-3 py-1.5 text-xs font-semibold text-blue-700">Tanggal
                nota mengikuti WIB</span>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('purchases.store') }}" class="space-y-6">
                @csrf
                <x-input-error :messages="$errors->all()" />
                <x-employee-select />
                <fieldset id="note-fields" disabled class="space-y-6 disabled:opacity-60"
                    aria-describedby="employee-verification-status">
                    <section class="relative overflow-visible rounded-2xl border border-slate-200 bg-white shadow-sm">
                        <div
                            class="rounded-t-2xl border-b border-slate-100 bg-gradient-to-r from-amber-50 to-white px-6 py-5">
                            <p class="text-xs font-semibold uppercase tracking-[.16em] text-amber-700">Langkah 1</p>
                            <h3 class="mt-1 text-lg font-semibold text-slate-900">Identitas nota</h3>
                            <p class="mt-1 text-sm text-slate-500">Tentukan asal barang dan pilih customer untuk
                                transaksi ini.</p>
                        </div>
                        <div class="grid gap-5 p-6 sm:grid-cols-2">
                            @if(Auth::user()->isAdmin())
                                <div>
                                    <x-input-label for="branch_id" value="Cabang" />
                                    <select id="branch_id" name="branch_id"
                                        class="mt-1 block w-full rounded-lg border-slate-300" required>
                                        @foreach($branches as $branch)
                                            <option value="{{ $branch->id }}"
                                                @selected((string) old('branch_id') === (string) $branch->id)>
                                                {{ $branch->nama_cabang }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif
                            <div>
                                <x-input-label for="sumber_barang" value="Asal barang" />
                                <select id="sumber_barang" name="sumber_barang"
                                    class="mt-1 block w-full rounded-lg border-slate-300">
                                    <option value="toko_sendiri" @selected(old('sumber_barang', 'toko_sendiri') === 'toko_sendiri')>Barang dari toko sendiri</option>
                                    <option value="barang_luar" @selected(old('sumber_barang') === 'barang_luar')>Barang
                                        dari luar</option>
                                </select>
                            </div>
                            <div id="purchase-customer-search" data-customer-search
                                data-url="{{ route('sales.customer-query') }}" class="relative z-20 sm:col-span-2"
                                data-branch-id="{{ Auth::user()->isBranchUser() ? Auth::user()->branch_id : '' }}">
                                <x-input-label for="customer-search" value="Customer" />
                                <x-text-input id="customer-search" type="search" class="mt-1 block w-full"
                                    value="{{ $selectedCustomer?->nama }}" autocomplete="off" maxlength="100"
                                    placeholder="Ketik nama atau nomor telepon" role="combobox" aria-autocomplete="list"
                                    aria-expanded="false" aria-controls="customer-results"
                                    aria-describedby="customer-search-help" />
                                <input type="hidden" id="customer_id" name="customer_id"
                                    value="{{ old('customer_id') }}">
                                <div id="customer-results" role="listbox" hidden
                                    class="absolute z-30 mt-1 max-h-64 w-full overflow-y-auto rounded-xl border border-slate-200 bg-white shadow-xl">
                                </div>
                                <p id="customer-search-help" role="status" class="mt-2 text-xs text-slate-500">Ketik
                                    nama atau nomor telepon, lalu pilih customer dari hasil suggestion.</p>
                            </div>
                        </div>
                    </section>

                    <x-sale-reference :endpoint="route('purchases.find-sale-query')" source="sumber_barang"
                        name-field="nama_barang" />

                    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                        <div
                            class="rounded-t-2xl border-b border-slate-100 bg-gradient-to-r from-slate-50 to-white px-6 py-5">
                            <p class="text-xs font-semibold uppercase tracking-[.16em] text-slate-500">Langkah 2</p>
                            <h3 class="mt-1 text-lg font-semibold text-slate-900">Data barang</h3>
                            <p class="mt-1 text-sm text-slate-500">Lengkapi identitas dan berat barang yang dibeli.</p>
                        </div>
                        <div class="grid gap-5 p-6 sm:grid-cols-2">
                            <div>
                                <x-input-label for="nama_barang" value="Nama barang" />
                                <x-text-input id="nama_barang" name="nama_barang" type="text" :value="old('nama_barang', '')" class="mt-1 block w-full" required />
                            </div>
                            <div>
                                <x-input-label for="kategori" value="Kategori" />
                                <x-text-input id="kategori" name="kategori" type="text" :value="old('kategori', '')"
                                    class="mt-1 block w-full" />
                            </div>
                            <div>
                                <x-input-label for="kadar" value="Kadar" />
                                <x-text-input id="kadar" name="kadar" type="text" :value="old('kadar', '')"
                                    class="mt-1 block w-full" />
                            </div>
                            <div>
                                <x-input-label for="berat" value="Berat (gram)" />
                                <x-text-input id="berat" name="berat" type="number" :value="old('berat', '')"
                                    class="mt-1 block w-full" step="0.001" min="0.001" required />
                            </div>
                        </div>
                    </section>

                    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                        <div
                            class="rounded-t-2xl border-b border-slate-100 bg-gradient-to-r from-blue-50 to-white px-6 py-5">
                            <p class="text-xs font-semibold uppercase tracking-[.16em] text-blue-600">Langkah 3</p>
                            <h3 class="mt-1 text-lg font-semibold text-slate-900">Harga pembelian</h3>
                            <p class="mt-1 text-sm text-slate-500">Harga awal barang toko sendiri mengikuti nota
                                penjualan dan dapat disesuaikan.</p>
                        </div>
                        <div class="grid gap-5 p-6 sm:grid-cols-2">
                            <div>
                                <x-input-label for="harga_per_gram_beli" value="Harga beli / gram (Rp)" />
                                <x-text-input id="harga_per_gram_beli" name="harga_per_gram_beli" type="number"
                                    :value="old('harga_per_gram_beli', '')" class="mt-1 block w-full" step="0.01"
                                    min="0" required />
                            </div>
                            <div>
                                <x-input-label for="potongan_per_gram" value="Potongan / gram (Rp)" />
                                <x-text-input id="potongan_per_gram" name="potongan_per_gram" type="number"
                                    :value="old('potongan_per_gram', 0)" class="mt-1 block w-full" step="0.01"
                                    min="0" />
                            </div>
                        </div>
                    </section>

                    @include('pembelian.deductions')

                    <div class="flex flex-wrap gap-3 border-t border-slate-200 pt-2">
                        <x-primary-button id="save-note">Simpan Nota Pembelian</x-primary-button>
                        <a href="{{ route('purchases.index') }}" class="action-button action-detail">Kembali</a>
                    </div>
                </fieldset>
            </form>
        </div>
    </div>
</x-app-layout>