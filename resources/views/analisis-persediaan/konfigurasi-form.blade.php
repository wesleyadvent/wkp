<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-xl font-semibold leading-tight text-gray-800">Edit Konfigurasi Persediaan {{ $metode }}</h2>
                <p class="mt-1 text-sm text-gray-500">Ubah hanya nilai untuk kombinasi yang dipilih.</p>
            </div>
            <a href="{{ route($settingsPrefix.'.index', ['branch_id' => $branch->id]) }}" class="inline-flex items-center rounded-md border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Kembali</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-2xl sm:px-6 lg:px-8">
            <form method="POST" action="{{ route($settingsPrefix.'.store') }}" class="space-y-5 rounded-xl bg-white p-6 shadow-sm">
                @csrf
                <input type="hidden" name="branch_id" value="{{ $branch->id }}">
                <input type="hidden" name="kategori" value="{{ $configuration->kategori }}">
                <input type="hidden" name="kadar" value="{{ $configuration->kadar }}">

                <dl class="grid gap-4 rounded-lg bg-slate-50 p-4 sm:grid-cols-3"><div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Cabang</dt><dd class="mt-1 font-semibold text-slate-900">{{ $branch->nama_cabang }}</dd></div><div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Kategori</dt><dd class="mt-1 font-semibold text-slate-900">{{ $configuration->kategori }}</dd></div><div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Kadar</dt><dd class="mt-1 font-semibold text-slate-900">{{ $configuration->kadar }}</dd></div></dl>

                <div>
                    <x-input-label for="lead_time_hari" value="Lead time (hari)" />
                    <x-text-input id="lead_time_hari" name="lead_time_hari" type="number" min="1" max="365" class="mt-1 block w-full" :value="old('lead_time_hari', $configuration->lead_time_hari)" required />
                    <x-input-error :messages="$errors->get('lead_time_hari')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="safety_stock" value="Safety stock (unit)" />
                    <x-text-input id="safety_stock" name="safety_stock" type="number" min="0" max="100000" class="mt-1 block w-full" :value="old('safety_stock', $configuration->safety_stock)" required />
                    <x-input-error :messages="$errors->get('safety_stock')" class="mt-2" />
                </div>
                <div class="flex justify-end gap-2"><a href="{{ route($settingsPrefix.'.index', ['branch_id' => $branch->id]) }}" class="inline-flex items-center rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Batal</a><x-primary-button>Simpan perubahan</x-primary-button></div>
            </form>
        </div>
    </div>
</x-app-layout>
