<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800">Detail Penjualan</h2></x-slot>
    <div class="py-8"><div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        @if(session('status'))<p class="mb-4 rounded-lg bg-green-50 p-4 text-green-800">{{ session('status') }}</p>@endif
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <a href="{{ route('sales.index') }}" class="action-button action-detail">Kembali ke Penjualan</a>
            <a href="{{ route('sales.print', $sale) }}" target="_blank" rel="noopener" class="action-button action-print"><svg aria-hidden="true" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M6 9V3h12v6M6 18H3V9h18v9h-3M6 14h12v7H6z"/></svg>Cetak Nota</a>
        </div>
        @include('transaksi.receipt', ['transaction' => $sale, 'kind' => 'sales'])
    </div></div>
</x-app-layout>
