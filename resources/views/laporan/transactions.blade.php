<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <h2 class="font-semibold text-xl text-gray-800">{{ $title }}</h2><a
                class="rounded bg-gray-900 px-4 py-2 text-sm text-white" target="_blank" rel="noopener"
                href="{{ route('reports.' . $type, array_merge(request()->only(['start', 'end', 'branch_id', 'status']), ['print' => 1])) }}">Cetak
                laporan</a>
        </div>
    </x-slot>
    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-4 sm:px-6 lg:px-8">
            <x-input-error :messages="$errors->all()" />
            <form method="GET" class="flex flex-wrap items-end gap-3 rounded bg-white p-4 shadow-sm">
                <label>Dari tanggal<x-text-input class="block" type="date" name="start"
                        :value="request('start')" /></label>
                <label>Sampai tanggal<x-text-input class="block" type="date" name="end"
                        :value="request('end')" /></label>
                @if(Auth::user()->isAdmin())<label>Cabang<select name="branch_id"
                    class="block rounded-md border-gray-300">
                    <option value="">Semua cabang</option>@foreach($branches as $branch)
                        <option value="{{ $branch->id }}" @selected((int) request('branch_id') === $branch->id)>
                    {{ $branch->nama_cabang }}</option>@endforeach
                </select></label>@endif
                <label>Status<select name="status" class="block rounded-md border-gray-300">
                        <option value="">Semua status</option>
                        <option value="valid" @selected(request('status') === 'valid')>Valid</option>
                        <option value="batal" @selected(request('status') === 'batal')>Batal</option>
                    </select></label>
                <x-primary-button>Filter</x-primary-button>
            </form>
            @include('laporan.transaction-table')
            <div class="flex flex-wrap items-center justify-between gap-3"><x-per-page-selector :paginator="$rows" />{{ $rows->links() }}</div>
        </div>
    </div>
</x-app-layout>
