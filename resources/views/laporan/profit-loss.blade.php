<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800">Selisih Nilai Transaksi</h2>
    </x-slot>
    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">
            <form method="GET" class="grid gap-3 bg-white p-4 shadow-sm sm:rounded-lg md:grid-cols-4"><x-text-input
                    type="date" name="start" :value="request('start')" /><x-text-input type="date" name="end"
                    :value="request('end')" />@if(Auth::user()->isAdmin())<select name="branch_id"
                        class="rounded-md border-gray-300">
                        <option value="">Semua cabang</option>@foreach($branches as $branch)
                            <option value="{{ $branch->id }}" @selected((int) request('branch_id') === $branch->id)>
                        {{ $branch->nama_cabang }}</option>@endforeach
                    </select>@endif<x-primary-button>Filter</x-primary-button></form>
            <div class="grid gap-4 md:grid-cols-4">
                <div class="bg-white p-4 shadow-sm sm:rounded-lg">Penjualan<br><b>Rp
                        {{ number_format($sales, 0, ',', '.') }}</b></div>
                <div class="bg-white p-4 shadow-sm sm:rounded-lg">Pembelian<br><b>Rp
                        {{ number_format($purchases, 0, ',', '.') }}</b></div>
                <div class="bg-white p-4 shadow-sm sm:rounded-lg">Service<br><b>Rp
                        {{ number_format($services, 0, ',', '.') }}</b></div>
                <div class="bg-white p-4 shadow-sm sm:rounded-lg">Selisih Nilai Transaksi<br><b>Rp
                        {{ number_format($sales + $services - $purchases, 0, ',', '.') }}</b></div>
            </div>
        </div>
    </div>
</x-app-layout>