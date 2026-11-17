<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Barang</h2>
            @if(Auth::user()->isAdmin())<a href="{{ route('products.create') }}" class="rounded bg-gray-900 px-4 py-2 text-sm font-medium text-white">Tambah Barang</a>@endif
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">
            @if (session('status'))<div class="rounded border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('status') }}</div>@endif
            <form method="GET" class="grid gap-3 bg-white p-4 shadow-sm sm:rounded-lg md:grid-cols-2 lg:grid-cols-5 items-end">
                @if(Auth::user()->isAdmin())
                    <div>
                        <x-input-label for="filter_branch_id" value="Cabang" />
                        <select id="filter_branch_id" name="branch_id" class="mt-1 block w-full rounded-md border-gray-300">
                            <option value="">Pilih cabang terlebih dahulu</option>
                            @foreach($branches as $branch)
                                <option value="{{ $branch->id }}" @selected($selectedBranchId === $branch->id)>{{ $branch->nama_cabang }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('branch_id')" class="mt-2" />
                    </div>
                @endif
                <div><x-input-label for="filter_q" value="Cari Barang" /><x-text-input id="filter_q" name="q" class="mt-1 block w-full" placeholder="Kode, nama, atau kadar" :value="request('q')" /></div>
                <div><x-input-label for="filter_kategori" value="Kategori" /><select id="filter_kategori" name="kategori" class="mt-1 block w-full rounded-md border-gray-300">
                    <option value="">Semua kategori</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->nama_kategori }}" @selected(request('kategori') === $category->nama_kategori)>{{ $category->nama_kategori }}</option>
                    @endforeach
                </select></div>
                <div><x-input-label for="filter_status" value="Status" /><select id="filter_status" name="status" class="mt-1 block w-full rounded-md border-gray-300">
                    <option value="">Semua status</option>
                    @foreach (['tersedia','terjual','service','arsip'] as $status)
                        <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>
                    @endforeach
                </select></div>
                <div class="flex h-10 gap-2"><x-primary-button>Filter</x-primary-button><a href="{{ route('products.index') }}" class="inline-flex items-center rounded-md border border-gray-300 px-3 text-sm text-gray-600 hover:bg-gray-50">Reset</a></div>
            </form>
            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm"><div class="flex items-center justify-between border-b border-gray-100 px-5 py-4"><h3 class="font-semibold text-gray-900">Daftar barang</h3><span class="text-sm text-gray-500">{{ number_format($products->total(), 0, ',', '.') }} unit</span></div><div class="overflow-x-auto">
                <div class="table-scroll" role="region" aria-label="Tabel data" tabindex="0"><table class="data-table min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                        <tr>
                            <th scope="col" class="w-20 px-4 py-3">Foto</th>
                            <th scope="col" class="px-4 py-3">Barang</th>
                            <th scope="col" class="px-4 py-3">Cabang</th>
                            <th scope="col" class="px-4 py-3">Kadar</th>
                            <th scope="col" class="px-4 py-3 text-right table-number">Berat (g)</th>
                            <th scope="col" class="px-4 py-3 text-right table-number">Total Harga</th>
                            <th scope="col" class="px-4 py-3">Status</th>
                            <th scope="col" class="px-4 py-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($products as $product)
                            <tr class="transition-colors hover:bg-amber-50/30">
                                <td class="px-4 py-4 align-middle">
                                    @if($product->photo_path)
                                        <button type="button" data-product-photo="{{ route('products.photo', $product) }}" data-product-name="{{ $product->nama_barang }}" aria-label="Lihat foto {{ $product->nama_barang }}" aria-haspopup="dialog" class="block rounded border border-gray-200 p-1 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                            <img src="{{ route('products.photo', $product) }}" alt="{{ $product->nama_barang }}" class="h-12 w-12 rounded object-cover" loading="lazy">
                                        </button>
                                    @else
                                        <span class="flex h-12 w-12 items-center justify-center rounded-md bg-gray-100 text-center text-xs text-gray-400">Tanpa foto</span>
                                    @endif
                                </td>
                                <td class="px-4 py-4">
                                    <div class="min-w-[170px] max-w-xs font-semibold text-gray-900">{{ $product->nama_barang }}</div>
                                    <div class="mt-1 whitespace-nowrap font-mono text-xs text-gray-500">{{ $product->kode_barang }}</div>
                                    <div class="mt-1 text-xs text-gray-500">{{ $product->kategori }}</div>
                                </td>
                                <td class="px-4 py-4 text-gray-600">{{ $product->branch?->nama_cabang }}</td>
                                <td class="whitespace-nowrap px-4 py-4 font-medium">{{ $product->kadar }}</td>
                                <td class="whitespace-nowrap px-4 py-4 text-right tabular-nums">{{ number_format($product->berat, 3, ',', '.') }}</td>
                                <td class="whitespace-nowrap px-4 py-4 text-right font-semibold tabular-nums table-number">Rp {{ number_format($product->total_harga, 0, ',', '.') }}</td>
                                <td class="px-4 py-4">
                                    <x-status-badge :value="$product->status" />
                                </td>
                                <td class="px-4 py-4"><div class="flex items-center justify-end gap-2"><a class="action-button action-detail" target="_blank" rel="noopener" href="{{ route('products.labels', ['ids' => [$product->id]]) }}">Label</a>
                                    @if(Auth::user()->isAdmin() && $product->status !== 'terjual')
                                        <a href="{{ route('products.edit', $product) }}" class="action-button action-edit">Edit</a>
                                    @endif
                                </div></td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="px-4 py-8 text-center text-gray-500">{{ $requiresBranchFilter ? 'Pilih cabang lalu klik Filter untuk menampilkan barang.' : 'Tidak ada barang yang sesuai filter.' }}</td></tr>
                        @endforelse
                    </tbody>
                </table></div>
            </div></div>
            <div class="flex flex-wrap items-center justify-between gap-3"><x-per-page-selector :paginator="$products" />{{ $products->links() }}</div>
        </div>
    </div>
    <dialog id="product-photo-dialog" aria-labelledby="product-photo-title" class="w-full max-w-3xl rounded-lg bg-white p-4 shadow-xl backdrop:bg-black/70">
        <div class="mb-4 flex items-center justify-between gap-4">
            <h3 id="product-photo-title" class="font-semibold text-gray-900"></h3>
            <form method="dialog"><button autofocus class="rounded bg-gray-100 px-3 py-2 text-sm font-medium" aria-label="Tutup foto">Tutup</button></form>
        </div>
        <img id="product-photo-preview" alt="" class="mx-auto max-h-[75vh] max-w-full object-contain">
        <p id="product-photo-error" hidden class="py-8 text-center text-gray-500">Foto tidak dapat dimuat.</p>
    </dialog>
    <script>
        (() => {
            const dialog = document.getElementById('product-photo-dialog');
            const preview = document.getElementById('product-photo-preview');
            const title = document.getElementById('product-photo-title');
            const error = document.getElementById('product-photo-error');
            let opener;
            document.querySelectorAll('[data-product-photo]').forEach(button => {
                button.addEventListener('click', () => {
                    opener = button;
                    title.textContent = button.dataset.productName;
                    preview.alt = button.dataset.productName;
                    preview.hidden = false;
                    error.hidden = true;
                    preview.src = button.dataset.productPhoto;
                    dialog.showModal();
                });
            });
            preview.addEventListener('error', () => {
                preview.hidden = true;
                error.hidden = false;
            });
            dialog.addEventListener('click', event => {
                const bounds = dialog.getBoundingClientRect();
                if (event.target === dialog && (event.clientX < bounds.left || event.clientX > bounds.right || event.clientY < bounds.top || event.clientY > bounds.bottom)) {
                    dialog.close();
                }
            });
            dialog.addEventListener('close', () => {
                preview.removeAttribute('src');
                opener?.focus();
            });
        })();
    </script>
</x-app-layout>
