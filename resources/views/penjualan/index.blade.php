<x-app-layout>
    <x-slot name="header"><div class="flex justify-between"><h2 class="font-semibold text-xl text-gray-800">Penjualan</h2><a class="rounded bg-gray-900 px-4 py-2 text-sm text-white" href="{{ route('sales.create') }}">Buat Nota</a></div></x-slot>
    <div class="py-8"><div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">
        @if(session('status'))<div class="rounded border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('status') }}</div>@endif
        <form method="GET" class="flex flex-wrap gap-3 bg-white p-4 shadow-sm sm:rounded-lg"><label class="flex-1 min-w-[220px]"><span class="sr-only">Cari nota, customer, atau barang</span><x-text-input type="search" name="q" :value="request('q')" placeholder="Cari nota, customer, atau barang…" class="w-full" /></label><x-text-input aria-label="Tanggal transaksi" type="date" name="date" :value="$selectedDate" />@if(Auth::user()->isAdmin())<select name="branch_id" class="rounded-md border-gray-300"><option value="">Semua cabang</option>@foreach($branches as $branch)<option value="{{ $branch->id }}" @selected((int)request('branch_id')===$branch->id)>{{ $branch->nama_cabang }}</option>@endforeach</select>@endif<x-primary-button>Cari / Filter</x-primary-button></form>
        @include('laporan.transaction-table', ['rows' => $sales, 'type' => 'sales', 'printing' => false])
        <div class="flex flex-wrap items-center justify-between gap-3"><x-per-page-selector :paginator="$sales" />{{ $sales->links() }}</div>
    </div></div>
</x-app-layout>
