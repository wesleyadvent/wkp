<x-app-layout>
    <x-slot name="header"><div class="flex items-center justify-between gap-4"><h2 class="font-semibold text-xl text-gray-800">Riwayat Harga {{ $goldPrice->kadar }}</h2><div><a href="{{ route('gold-prices.index') }}" class="text-sm text-blue-700 underline">Kembali</a>@if(Auth::user()->isAdmin()) <a href="{{ route('gold-prices.edit', $goldPrice) }}" class="action-button action-edit">Edit harga</a>@endif</div></div></x-slot>
    <div class="py-8"><div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <p class="mb-4 text-sm text-gray-600">Harga terkini: Rp {{ number_format($goldPrice->harga_per_gram, 2, ',', '.') }} per gram, sejak {{ $goldPrice->tanggal->format('d/m/Y') }}.</p>
        <div class="overflow-x-auto bg-white shadow-sm sm:rounded-lg"><table class="data-table min-w-full text-sm"><thead><tr><th class="px-4 py-3 text-left">Tanggal berlaku</th><th class="px-4 py-3 text-right">Harga/gram</th><th class="px-4 py-3 text-left">Keterangan</th></tr></thead><tbody>
            @foreach($history as $item)<tr><td class="px-4 py-3">{{ $item->tanggal->format('d/m/Y') }} @if($item->id === $goldPrice->id)<span class="text-green-700">(terkini)</span>@endif</td><td class="px-4 py-3 text-right">Rp {{ number_format($item->harga_per_gram, 2, ',', '.') }}</td><td class="px-4 py-3">{{ $item->keterangan }}</td></tr>@endforeach
        </tbody></table></div><div class="mt-4 flex flex-wrap items-center justify-between gap-3"><x-per-page-selector :paginator="$history" />{{ $history->links() }}</div>
    </div></div>
</x-app-layout>
