<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800">Buat Nota Penjualan</h2></x-slot>
    <div class="py-8"><div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
        <form method="POST" action="{{ route('sales.store') }}" class="space-y-5 rounded-xl bg-white p-6 shadow-sm">@csrf
        <x-employee-select />
<fieldset id="note-fields" disabled class="space-y-6 disabled:opacity-50" aria-describedby="employee-verification-status">
<p class="text-sm text-gray-500">Tanggal nota otomatis mengikuti tanggal saat disimpan (WIB).</p>
            <x-input-error :messages="$errors->all()" />
            <div class="grid gap-5 sm:grid-cols-2">
                @if(Auth::user()->isAdmin())
                    <div><x-input-label for="branch_id" value="Cabang" /><select name="branch_id" id="branch_id" class="mt-1 block w-full rounded-md border-gray-300" required><option value="">Pilih cabang</option>@foreach($branches as $branch)<option value="{{ $branch->id }}" @selected((string)old('branch_id') === (string)$branch->id)>{{ $branch->nama_cabang }}</option>@endforeach</select></div>
                @endif
                <div id="sale-customer-search" data-customer-search class="relative" data-url="{{ route('sales.customer-query') }}" data-branch-id="{{ Auth::user()->isBranchUser() ? Auth::user()->branch_id : '' }}">
                    <x-input-label for="customer-search" value="Customer" />
                    <x-text-input id="customer-search" type="search" class="mt-1 block w-full" :value="$selectedCustomer?->nama" required autocomplete="off" maxlength="100" placeholder="Ketik nama atau nomor telepon" role="combobox" aria-autocomplete="list" aria-expanded="false" aria-controls="customer-results" aria-describedby="customer-search-help" />
                    <input type="hidden" id="customer_id" name="customer_id" value="{{ $selectedCustomer?->id }}">
                    <div id="customer-results" role="listbox" hidden class="absolute z-20 mt-1 max-h-64 w-full overflow-y-auto rounded-lg border border-gray-200 bg-white shadow-lg"></div>
                    <p id="customer-search-help" role="status" class="mt-2 text-xs text-gray-500">Ketik untuk mencari, lalu pilih customer dari hasil pencarian.</p>
                </div>
                <input type="hidden" name="product_id" id="product_id" value="{{ old('product_id') }}">
            </div>
            <section id="sale-barcode" data-lookup-url="{{ route('sales.barcode-query') }}" data-branch-id="{{ Auth::user()->isBranchUser() ? Auth::user()->branch_id : '' }}" class="rounded-lg border border-indigo-200 bg-indigo-50 p-4">
                <x-input-label for="barcode" value="Pindai barcode barang" />
                <div class="mt-2 flex flex-wrap gap-2"><x-text-input id="barcode" name="barcode" autocomplete="off" maxlength="255" class="flex-1" placeholder="Pindai dengan scanner lalu Enter" /><button id="lookup-barcode" type="button" class="rounded bg-indigo-700 px-4 py-2 text-white">Cari barcode</button><button id="start-barcode-camera" type="button" class="rounded border border-indigo-300 bg-white px-4 py-2">Pindai kamera</button><button id="stop-barcode-camera" type="button" hidden class="rounded border border-gray-300 bg-white px-4 py-2">Tutup kamera</button></div>
                <p id="barcode-message" role="status" aria-live="polite" class="mt-2 text-sm">Pindai dengan scanner atau kamera, atau ketik kode barcode lalu klik Cari barcode.</p>
                <video id="barcode-video" hidden muted playsinline class="mt-3 w-full rounded" style="max-height:300px"></video>
            </section>
            <p id="product-help" class="text-sm text-gray-500">Pindai barcode untuk menampilkan rincian barang.</p>
            <section id="selected-product" hidden class="rounded-xl border border-gray-200 bg-gray-50 p-5" aria-live="polite">
                <h3 class="mb-4 font-semibold text-gray-900">Rincian barang dari database</h3>
                <div class="grid gap-6 md:grid-cols-3">
                    <div><a id="product-photo-link" target="_blank" rel="noopener" aria-label="Buka foto barang ukuran penuh"><img id="product-photo" alt="" hidden class="w-full rounded-lg border border-gray-200 bg-white object-contain" style="max-height:260px"></a><p id="photo-message" class="rounded-lg bg-white p-6 text-center text-sm text-gray-500">Belum ada foto barang.</p></div>
                    <dl class="grid gap-4 text-sm sm:grid-cols-2 md:col-span-2">
                        @foreach(['kode_barang' => 'Kode barang', 'nama_barang' => 'Nama barang', 'branch_name' => 'Cabang', 'kategori' => 'Kategori', 'kadar' => 'Kadar', 'berat' => 'Berat', 'status' => 'Status', 'harga_per_gram' => 'Harga per gram', 'ongkos' => 'Ongkos', 'total_harga' => 'Harga total tersimpan', 'deskripsi' => 'Deskripsi'] as $field => $label)
                            <div><dt class="text-gray-500">{{ $label }}</dt><dd data-product-field="{{ $field }}" class="mt-1 break-words font-semibold text-gray-900"></dd></div>
                        @endforeach
                    </dl>
                </div>
                <div class="mt-5 border-t border-gray-200 pt-4"><p class="text-sm text-gray-500">Total penjualan � berat � harga per gram + ongkos</p><p id="sale-total" class="mt-1 text-2xl font-bold text-gray-900"></p></div>
                <p class="mt-3 text-xs text-gray-500">Harga dan ongkos mengikuti data barang. Saat disimpan, sistem memeriksa kembali data dan ketersediaan barang.</p>
            </section>
            <div class="flex flex-wrap gap-3"><x-primary-button id="save-sale" disabled>Simpan Nota Penjualan</x-primary-button><a href="{{ route('sales.index') }}" class="action-button action-detail">Kembali</a></div>
        </fieldset>
</form>
    </div></div>
    @php
        $productData = $products->map(fn ($product) => array_merge($product->toArray(), ['branch_name' => $product->branch?->nama_cabang, 'photo_url' => $product->photo_path ? route('products.photo', $product) : null]));
    @endphp
    <script>
    (() => {
        const products = @json($productData);
        const branch = document.getElementById('branch_id');
        const select = document.getElementById('product_id');
        const customer = document.getElementById('customer_id');
        const panel = document.getElementById('selected-product');
        const photo = document.getElementById('product-photo');
        const photoLink = document.getElementById('product-photo-link');
        const photoMessage = document.getElementById('photo-message');
        const money = value => new Intl.NumberFormat('id-ID', {style: 'currency', currency: 'IDR', maximumFractionDigits: 0}).format(Number(value));
        const render = () => {
            const product = products.find(item => String(item.id) === select.value);
            panel.hidden = !product;
            document.getElementById('save-sale').disabled = !product;
            if (!product) return;
            document.querySelectorAll('[data-product-field]').forEach(element => {
                const field = element.dataset.productField;
                let value = product[field];
                if (['harga_per_gram', 'ongkos', 'total_harga'].includes(field)) value = money(value);
                if (field === 'berat') value = Number(value).toLocaleString('id-ID', {minimumFractionDigits: 3}) + ' gram';
                if (['created_at', 'updated_at'].includes(field) && value) value = new Date(value).toLocaleString('id-ID');
                element.textContent = value || '�';
            });
            photo.hidden = true;
            photoMessage.hidden = false;
            photoMessage.textContent = product.photo_url ? 'Memuat foto�' : 'Belum ada foto barang.';
            photoLink.removeAttribute('href');
            if (product.photo_url) {
                photo.alt = product.nama_barang;
                photoLink.href = product.photo_url;
                photo.src = product.photo_url;
            } else photo.removeAttribute('src');
            document.getElementById('sale-total').textContent = money(Number(product.berat) * Number(product.harga_per_gram) + Number(product.ongkos));
        };
        photo.addEventListener('load', () => { photo.hidden = false; photoMessage.hidden = true; });
        photo.addEventListener('error', () => { photo.hidden = true; photoMessage.hidden = false; photoMessage.textContent = 'File foto tidak ditemukan atau gagal dimuat.'; });
        const filter = () => {
            const branchId = branch ? branch.value : @json((string)Auth::user()->branch_id);
            const product = products.find(item => String(item.id) === select.value);
            if (product && String(product.branch_id) !== branchId) select.value = '';
            document.getElementById('product-help').textContent = !branchId ? 'Pilih cabang terlebih dahulu.' : 'Pindai barcode barang dari cabang ini.';
            render();
        };
        branch?.addEventListener('change', filter);
        select.addEventListener('change', render);
        document.getElementById('sale-barcode').addEventListener('barcode-product', event => {
            const product = event.detail;
            const index = products.findIndex(item => item.id === product.id);
            if (index >= 0) products[index] = product; else products.push(product);
            select.value = String(product.id);
            render();
        });
        filter();
    })();
    </script>
</x-app-layout>
