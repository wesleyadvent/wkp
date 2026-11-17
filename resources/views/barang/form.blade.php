<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $product->exists ? 'Edit Barang' : 'Tambah Barang' }}</h2></x-slot>
    <div class="py-8"><div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
        <form method="POST" enctype="multipart/form-data" action="{{ $product->exists ? route('products.update', $product) : route('products.store') }}" class="grid gap-5 bg-white p-6 shadow-sm sm:rounded-lg md:grid-cols-2">
            <x-input-error :messages="$errors->all()" class="md:col-span-2" />
            @csrf @if($product->exists) @method('PUT') @endif
            @if(Auth::user()->isAdmin())
                <div><x-input-label for="branch_id" value="Cabang" /><select id="branch_id" name="branch_id" class="mt-1 block w-full rounded-md border-gray-300" required>@foreach($branches as $branch)<option value="{{ $branch->id }}" @selected((int) old('branch_id', $product->branch_id) === $branch->id)>{{ $branch->nama_cabang }}</option>@endforeach</select></div>
            @endif
            <div><x-input-label for="nama_barang" value="Nama Barang" /><x-text-input id="nama_barang" name="nama_barang" class="mt-1 block w-full" :value="old('nama_barang', $product->nama_barang)" required /></div>
            <div><x-input-label for="kategori_barang_id" value="Kategori" /><select id="kategori_barang_id" name="kategori_barang_id" class="mt-1 block w-full rounded-md border-gray-300" required><option value="">Pilih kategori</option>@foreach($categories as $category)<option value="{{ $category->id }}" @selected((int) old('kategori_barang_id', $product->kategori_barang_id) === $category->id)>{{ $category->nama_kategori }}</option>@endforeach</select></div>
            <div>
                <x-input-label for="kadar" value="Kadar" />
                <select id="kadar" name="kadar" class="mt-1 block w-full rounded-md border-gray-300" required>
                    <option value="">Pilih kadar</option>
                    @foreach($goldPrices as $goldPrice)
                        <option value="{{ $goldPrice->kadar }}" data-price="{{ $goldPrice->harga_per_gram }}" @selected(old('kadar', $product->kadar) === $goldPrice->kadar)>{{ $goldPrice->kadar }}</option>
                    @endforeach
                </select>
                <p class="mt-1 text-sm text-gray-500">Harga mengikuti data terbaru di menu Harga Emas.</p>
                @if($goldPrices->isEmpty())<p class="mt-1 text-sm text-red-600">Tambahkan kadar dan harga di menu Harga Emas terlebih dahulu.</p>@endif
                <x-input-error :messages="$errors->get('kadar')" class="mt-2" />
            </div>
            <div><x-input-label for="berat" value="Berat per unit (gram)" /><x-text-input id="berat" name="berat" type="number" step="0.001" class="mt-1 block w-full" :value="old('berat', $product->berat)" required /></div>
            <div><x-input-label for="harga_per_gram" value="Harga per Gram" /><x-text-input id="harga_per_gram" name="harga_per_gram" type="number" step="0.01" class="mt-1 block w-full bg-gray-100" readonly required /></div>
            <div><x-input-label for="ongkos" value="Ongkos" /><x-text-input id="ongkos" name="ongkos" type="number" step="0.01" class="mt-1 block w-full" :value="old('ongkos', $product->ongkos ?? 0)" /></div>
            <div>
                <x-input-label for="total_harga" value="Harga Total per Unit" />
                <x-text-input id="total_harga" class="mt-1 block w-full bg-gray-100" readonly aria-describedby="total_harga_help" />
                <p id="total_harga_help" class="mt-1 text-sm text-gray-500">Berat × harga per gram + ongkos.</p>
            </div>
            <div><x-input-label for="status" value="Status" /><select id="status" name="status" class="mt-1 block w-full rounded-md border-gray-300">@foreach(($product->exists ? ['tersedia','service','arsip'] : ['tersedia']) as $status)<option value="{{ $status }}" @selected(old('status', $product->status ?? 'tersedia') === $status)>{{ ucfirst($status) }}</option>@endforeach</select></div>
            <div><x-input-label for="photo" value="Foto" /><input id="photo" name="photo" type="file" accept="image/*" class="mt-1 block w-full text-sm" /></div>
            <div class="md:col-span-2"><x-input-label for="deskripsi" value="Deskripsi" /><textarea id="deskripsi" name="deskripsi" class="mt-1 block w-full rounded-md border-gray-300">{{ old('deskripsi', $product->deskripsi) }}</textarea></div>
            <div class="md:col-span-2"><x-primary-button>Simpan</x-primary-button></div>
        </form>
    </div></div>
    <script>
        (() => {
            const kadar = document.getElementById('kadar');
            const harga = document.getElementById('harga_per_gram');
            const berat = document.getElementById('berat');
            const ongkos = document.getElementById('ongkos');
            const total = document.getElementById('total_harga');
            const currency = new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 2 });
            const syncTotal = () => {
                const weight = Number(berat.value);
                const price = Number(harga.value);
                const fee = Number(ongkos.value || 0);
                total.value = harga.value !== '' && berat.value !== '' && weight > 0 && price >= 0 && fee >= 0 && Number.isFinite(weight * price + fee)
                    ? currency.format(weight * price + fee)
                    : '';
            };
            const syncPrice = () => {
                harga.value = kadar.selectedOptions[0]?.dataset.price ?? '';
                syncTotal();
            };
            berat.addEventListener('input', syncTotal);
            ongkos.addEventListener('input', syncTotal);
            kadar.addEventListener('change', syncPrice);
            syncPrice();
        })();
    </script>
</x-app-layout>
