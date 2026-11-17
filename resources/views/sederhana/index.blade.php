<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $title }}</h2>
            @if (Route::has($route . '.create') && (!in_array($route, ['employees', 'gold-prices', 'product-categories', 'deduction-types', 'service-types']) || Auth::user()->isAdmin()))
                <a href="{{ route($route . '.create') }}"
                    class="rounded bg-gray-900 px-4 py-2 text-sm font-medium text-white">Tambah</a>
            @endif
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="mb-4 rounded border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                    {{ session('status') }}</div>
            @endif

            <x-input-error :messages="$errors->get('delete')" class="mb-4 rounded bg-red-50 p-4" />
            @if($route === 'customers' || (isset($branches) && Auth::user()->isAdmin()))
                <form method="GET" class="mb-4 flex flex-wrap gap-3 bg-white p-4 shadow-sm sm:rounded-lg">
                    @if($route === 'customers')<x-text-input type="search" name="q" :value="request('q')"
                    aria-label="Cari customer" placeholder="Cari nama, telepon, atau alamat�" class="flex-1" />@endif
                    @if(isset($branches) && Auth::user()->isAdmin())
                        <select name="branch_id" aria-label="Cabang" class="rounded-md border-gray-300">
                            <option value="">Semua cabang</option>@foreach($branches as $branch)
                                <option value="{{ $branch->id }}" @selected((int) request('branch_id') === $branch->id)>
                            {{ $branch->nama_cabang }}</option>@endforeach
                        </select>
                    @endif
                    <x-primary-button>Cari / Filter</x-primary-button>
                </form>
            @endif

            <div class="overflow-x-auto bg-white shadow-sm sm:rounded-lg">
                <div class="table-scroll" role="region" aria-label="Tabel data" tabindex="0">
                    <table class="data-table min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                            <tr>
                                @foreach ($columns as $field => $label)
                                    <th scope="col" @class(['px-4 py-3', 'table-number' => in_array($field, ['harga_per_gram', 'nominal', 'harga_default'])])>{{ $label }}</th>
                                @endforeach
                                <th scope="col" class="px-4 py-3 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            @forelse ($items as $item)
                            <tr>
                                @foreach ($columns as $field => $label)
                                <td @class(['px-4 py-3 text-gray-700', 'table-number' => in_array($field, ['harga_per_gram', 'nominal', 'harga_default'])])>
                                    @php($value = data_get($item, $field))
                                    @if (is_bool($value))
                                        <x-status-badge :value="$value" />
                                    @elseif (in_array($field, ['harga_per_gram', 'nominal', 'harga_default']))
                                        Rp {{ number_format($value ?? 0, 2, ',', '.') }}
                                    @elseif ($value instanceof \Carbon\CarbonInterface)
                                        {{ $value->format('d/m/Y') }}
                                    @else
                                        {{ $value }}
                                    @endif
                                </td>
                                @endforeach
                                <td class="px-4 py-3 text-right">
                                    @if (Route::has($route . '.edit') && (!in_array($route, ['employees', 'gold-prices', 'product-categories', 'deduction-types', 'service-types']) || Auth::user()->isAdmin()))
                                        <a href="{{ route($route . '.edit', $item) }}"
                                            class="action-button action-edit">Edit</a>
                                    @endif
                                    @if(Auth::user()->isAdmin() && in_array($route, ['employees', 'gold-prices', 'product-categories', 'deduction-types', 'service-types']))
                                        @if(\App\Services\MasterDataUsage::isUsed($item))
                                        <button type="button" disabled aria-disabled="true"
                                            title="Sudah pernah digunakan; data tidak dapat dihapus."
                                            class="action-button cursor-not-allowed border-gray-200 bg-gray-100 text-gray-400 opacity-60">Hapus</button>
                                        <span class="block text-xs text-gray-500">Sudah digunakan</span>
                                        @else
                                        <button type="button" data-delete-url="{{ route($route . '.destroy', $item) }}"
                                            data-delete-label="{{ $route === 'gold-prices' ? $item->kadar . ' - ' . $item->tanggal->format('d/m/Y') . ' - Rp ' . number_format($item->harga_per_gram, 0, ',', '.') : data_get($item, array_key_first($columns)) }}"
                                            aria-haspopup="dialog" class="action-button action-delete">Hapus</button>
                                        @endif
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="{{ count($columns) + 1 }}" class="px-4 py-8 text-center text-gray-500">
                                    Belum ada data.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="mt-4 flex flex-wrap items-center justify-between gap-3"><x-per-page-selector :paginator="$items" />{{ $items->links() }}</div>
        </div>
    </div>
    @if(Auth::user()->isAdmin() && in_array($route, ['employees', 'gold-prices', 'product-categories', 'deduction-types', 'service-types']))
        <x-delete-confirmation />
    @endif
</x-app-layout>
