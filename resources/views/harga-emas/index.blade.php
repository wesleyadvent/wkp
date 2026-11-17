<x-app-layout>
    <x-slot name="header"><div class="flex items-center justify-between"><h2 class="font-semibold text-xl text-gray-800">Harga Emas</h2>@if(Auth::user()->isAdmin())<a href="{{ route('gold-prices.create') }}" class="rounded bg-gray-900 px-4 py-2 text-sm text-white">Tambah kadar</a>@endif</div></x-slot>
    <div class="py-8"><div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        @if(session('status'))<div class="mb-4 rounded bg-green-50 p-4 text-green-800">{{ session('status') }}</div>@endif
        <x-input-error :messages="$errors->get('delete')" class="mb-4 rounded bg-red-50 p-4" />
        <p class="mb-4 text-sm text-gray-600">Harga terbaru untuk setiap kadar. Buka detail untuk melihat riwayat perubahan.</p>
        <div class="overflow-x-auto bg-white shadow-sm sm:rounded-lg"><table class="data-table min-w-full text-sm">
            <thead><tr><th class="px-4 py-3 text-left">Kadar</th><th class="px-4 py-3 text-left">Berlaku sejak</th><th class="px-4 py-3 text-right">Harga/gram</th><th class="px-4 py-3 text-left">Keterangan</th><th class="px-4 py-3 text-right">Aksi</th></tr></thead>
            <tbody>@forelse($items as $item)<tr>
                <td class="px-4 py-3"><a class="text-blue-700 underline" href="{{ route('gold-prices.show', $item) }}">{{ $item->kadar }}</a></td>
                <td class="px-4 py-3">{{ $item->tanggal->format('d/m/Y') }}</td><td class="px-4 py-3 text-right">Rp {{ number_format($item->harga_per_gram, 2, ',', '.') }}</td><td class="px-4 py-3">{{ $item->keterangan }}</td>
                <td class="px-4 py-3 text-right"><a class="action-button" href="{{ route('gold-prices.show', $item) }}">Detail</a> @if(Auth::user()->isAdmin())<a class="action-button action-edit" href="{{ route('gold-prices.edit', $item) }}">Edit</a>
                    @if(\App\Services\MasterDataUsage::isUsed($item) || \App\Models\HargaEmas::where('kadar', $item->kadar)->whereKeyNot($item->id)->exists())
                        <button type="button" disabled aria-disabled="true" class="action-button cursor-not-allowed opacity-60" title="Sudah digunakan atau memiliki riwayat.">Hapus</button><span class="block text-xs text-gray-500">Sudah digunakan atau memiliki riwayat</span>
                    @else
                        <button type="button" class="action-button action-delete" data-delete-url="{{ route('gold-prices.destroy', $item) }}" data-delete-label="{{ $item->kadar }}" aria-haspopup="dialog">Hapus</button>
                    @endif
                @endif</td>
            </tr>@empty<tr><td colspan="5" class="px-4 py-8 text-center">Belum ada data harga.</td></tr>@endforelse</tbody>
        </table></div><div class="mt-4 flex flex-wrap items-center justify-between gap-3"><x-per-page-selector :paginator="$items" />{{ $items->links() }}</div>
    </div></div>
    @if(Auth::user()->isAdmin())<x-delete-confirmation />@endif
</x-app-layout>
