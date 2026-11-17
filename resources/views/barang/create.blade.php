<x-app-layout>
    <x-slot name="header"><div><h2 class="text-xl font-semibold text-gray-800">Tambah Barang</h2><p class="mt-1 text-sm text-gray-500">Isi rincian setiap barang, lalu cetak seluruh barcode.</p></div></x-slot>
    <div class="py-8"><div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
        <form id="product-wizard" action="{{ route('products.store') }}" method="POST" enctype="multipart/form-data" data-prices="{{ $goldPrices->pluck('harga_per_gram', 'kadar')->toJson() }}" class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
            @csrf
            <div id="wizard-error" role="alert" hidden class="m-6 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700"></div>
            <fieldset id="wizard-setup" class="space-y-6 p-6 sm:p-8">
                <div><span class="text-xs font-semibold uppercase tracking-wider text-amber-700">Langkah awal</span><h3 class="mt-2 text-lg font-semibold text-gray-900">Berapa barang yang ingin ditambahkan?</h3><p class="mt-1 text-sm text-gray-500">Contoh: 10 unit berarti 10 form rincian yang diisi satu per satu.</p></div>
                <div class="grid gap-5 sm:grid-cols-2">
                    <div><x-input-label for="jumlah" value="Jumlah barang" /><x-text-input id="jumlah" name="jumlah" type="number" min="1" max="1000" step="1" value="1" required class="mt-2 block w-full" /><p class="mt-2 text-xs text-gray-500">1 sampai 1.000 barang per pengisian.</p></div>
                    @if(Auth::user()->isAdmin())<div><x-input-label for="branch_id" value="Cabang" /><select id="branch_id" name="branch_id" required class="mt-2 block w-full rounded-md border-gray-300"><option value="">Pilih cabang</option>@foreach($branches as $branch)<option value="{{ $branch->id }}">{{ $branch->nama_cabang }} ({{ $branch->id }})</option>@endforeach</select></div>@else<div class="rounded-lg bg-gray-50 p-4 text-sm"><p class="text-gray-500">Cabang</p><p class="mt-1 font-semibold">{{ Auth::user()->branch?->nama_cabang }}</p></div>@endif
                </div>
                <div class="flex items-center gap-4"><button id="wizard-start" type="button" class="rounded-lg bg-gray-900 px-5 py-3 text-sm font-semibold text-white">Mulai isi barang</button><a href="{{ route('products.index') }}" class="text-sm text-gray-600">Kembali ke daftar</a></div>
            </fieldset>
            <fieldset id="wizard-editor" hidden disabled>
                <div class="border-b border-gray-100 bg-gray-50 px-6 py-5 sm:px-8"><div class="flex items-center justify-between gap-4"><h3 id="wizard-title" tabindex="-1" class="text-lg font-semibold text-gray-900">Barang 1 dari 1</h3><span class="rounded-full bg-green-100 px-3 py-1 text-xs font-semibold text-green-700">Status awal: Tersedia</span></div><p class="mt-1 text-sm text-gray-500">Setiap barang memiliki rincian dan barcode sendiri.</p><progress id="wizard-progress" max="1" value="1" aria-label="Progres pengisian barang" class="mt-4 block h-2 w-full" style="accent-color:#b45309"></progress></div>
                <div class="grid gap-5 p-6 sm:grid-cols-2 sm:p-8">
                    <div><x-input-label for="unit-name" value="Nama barang" /><x-text-input id="unit-name" data-unit-field="nama_barang" required maxlength="255" class="mt-2 block w-full" placeholder="Contoh: Cincin motif bunga" /></div>
                    <div><x-input-label for="unit-category" value="Kategori" /><select id="unit-category" data-unit-field="kategori_barang_id" required class="mt-2 block w-full rounded-md border-gray-300"><option value="">Pilih kategori</option>@foreach($categories as $category)<option value="{{ $category->id }}">{{ $category->nama_kategori }}</option>@endforeach</select></div>
                    <div><x-input-label for="unit-purity" value="Kadar emas" /><select id="unit-purity" data-unit-field="kadar" required class="mt-2 block w-full rounded-md border-gray-300"><option value="">Pilih kadar</option>@foreach($goldPrices as $price)<option value="{{ $price->kadar }}">{{ $price->kadar }}</option>@endforeach</select></div>
                    <div><x-input-label for="unit-weight" value="Berat barang (gram)" /><x-text-input id="unit-weight" data-unit-field="berat" type="number" min="0.001" step="0.001" required class="mt-2 block w-full" /></div>
                    <div><x-input-label for="unit-price" value="Harga per gram" /><x-text-input id="unit-price" readonly class="mt-2 block w-full bg-gray-50" /><p class="mt-2 text-xs text-gray-500">Mengikuti harga emas terbaru untuk kadar yang dipilih.</p></div>
                    <div><x-input-label for="unit-fee" value="Ongkos (Rp)" /><x-text-input id="unit-fee" data-unit-field="ongkos" type="number" min="0" step="0.01" value="0" class="mt-2 block w-full" /></div>
                    <div class="sm:col-span-2 rounded-lg border border-amber-100 bg-amber-50 p-4"><p class="text-sm text-amber-800">Total harga barang ini</p><p id="unit-total" class="mt-1 text-2xl font-semibold text-gray-900">Rp 0</p><p class="mt-1 text-xs text-gray-500">Berat × harga per gram + ongkos.</p></div>
                    <div class="sm:col-span-2">
                        <x-input-label for="unit-photo" value="Foto barang (opsional)" />
                        <div class="mt-2 rounded-xl border border-dashed border-gray-300 bg-gray-50 p-5">
                            <input id="unit-photo" type="file" accept="image/*" class="block w-full text-sm text-gray-500 file:mr-4 file:rounded-lg file:border-0 file:bg-white file:px-4 file:py-2.5 file:text-sm file:font-semibold file:text-gray-700 file:shadow-sm hover:file:bg-gray-100" />
                            <div class="mt-4 flex flex-wrap items-center gap-4">
                                <img id="unit-photo-preview" hidden alt="Pratinjau foto barang" class="h-20 w-20 rounded-lg border border-gray-200 bg-white object-cover" />
                                <p id="unit-photo-name" class="min-w-0 flex-1 break-all text-sm text-gray-500" aria-live="polite">Maksimal 2 MB per foto.</p>
                                <button id="unit-photo-remove" type="button" hidden class="rounded-lg border border-red-200 bg-white px-4 py-2.5 text-sm font-semibold text-red-600 shadow-sm transition hover:border-red-300 hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-red-300">
                                    <span class="flex items-center gap-2"><svg aria-hidden="true" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 6h18M9 6V4h6v2M5 6l1 14h12l1-14M10 10v6m4-6v6" /></svg>Hapus foto</span>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="sm:col-span-2"><x-input-label for="unit-description" value="Deskripsi (opsional)" /><textarea id="unit-description" data-unit-field="deskripsi" rows="3" class="mt-2 block w-full rounded-md border-gray-300"></textarea></div>
                </div>
                <div class="flex flex-wrap items-center justify-between gap-3 border-t border-gray-100 px-6 py-5 sm:px-8"><button id="wizard-prev" type="button" class="rounded-lg border border-gray-300 px-5 py-3 text-sm font-medium text-gray-700">Sebelumnya</button><button id="wizard-next" type="button" class="rounded-lg bg-gray-900 px-5 py-3 text-sm font-semibold text-white">Selanjutnya</button></div>
            </fieldset>
            <noscript><p class="p-6 text-red-700">Aktifkan JavaScript untuk mengisi form barang bertahap.</p></noscript>
        </form>
    </div></div>
</x-app-layout>
