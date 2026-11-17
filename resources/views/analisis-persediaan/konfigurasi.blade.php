<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-xl font-semibold leading-tight text-gray-800">Konfigurasi Persediaan {{ $metode }}</h2>
                <p class="mt-1 text-sm text-gray-500">Lead time dan safety stock per cabang, kategori, dan kadar.</p>
            </div>
            <a href="{{ route($analysisRoute) }}" class="inline-flex items-center rounded-md border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Kembali ke analisis {{ $metode }}</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-5 sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="rounded border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('status') }}</div>
            @endif

            <form method="GET" action="{{ route($settingsPrefix.'.index') }}" class="flex flex-wrap items-end gap-3 rounded-xl bg-white p-5 shadow-sm">
                <div class="min-w-64">
                    <x-input-label for="branch_id" value="Filter cabang" />
                    <select id="branch_id" name="branch_id" class="mt-1 block w-full rounded-md border-gray-300">
                        <option value="">Seluruh cabang</option>
                        @foreach ($branches as $branch)
                            <option value="{{ $branch->id }}" @selected((int) request('branch_id') === $branch->id)>{{ $branch->nama_cabang }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('branch_id')" class="mt-2" />
                </div>
                <div class="min-w-64">
                    <x-input-label for="q" value="Cari kategori atau kadar" />
                    <x-text-input id="q" name="q" class="mt-1 block w-full" :value="request('q')" placeholder="Contoh: Cincin atau 24K" />
                    <x-input-error :messages="$errors->get('q')" class="mt-2" />
                </div>
                <div class="flex h-10 gap-2"><x-primary-button>Filter</x-primary-button><a href="{{ route($settingsPrefix.'.index') }}" class="inline-flex items-center rounded-md border border-gray-300 px-3 text-sm text-gray-600 hover:bg-gray-50">Reset</a></div>
            </form>

            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-gray-100 px-5 py-4"><h3 class="font-semibold text-gray-900">Konfigurasi per cabang, kategori, dan kadar</h3><p class="mt-1 text-xs text-gray-500">Kombinasi yang belum diedit memakai lead time 14 hari dan safety stock 2 unit.</p></div>
                <div class="table-scroll" role="region" aria-label="Tabel konfigurasi persediaan" tabindex="0">
                    <table class="data-table min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500"><tr><th scope="col" class="px-4 py-3">Cabang</th><th scope="col" class="px-4 py-3">Kategori</th><th scope="col" class="px-4 py-3">Kadar</th><th scope="col" class="px-4 py-3 text-right">Lead time</th><th scope="col" class="px-4 py-3 text-right">Safety stock</th><th scope="col" class="px-4 py-3 text-right">Aksi</th></tr></thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($configurations as $configuration)
                                <tr><td class="px-4 py-3 font-medium text-gray-900">{{ $configuration['cabang'] }}</td><td class="px-4 py-3">{{ $configuration['kategori'] }}</td><td class="px-4 py-3">{{ $configuration['kadar'] }}</td><td class="px-4 py-3 text-right tabular-nums">{{ $configuration['lead_time_hari'] }} hari</td><td class="px-4 py-3 text-right tabular-nums">{{ $configuration['safety_stock'] }} unit</td><td class="px-4 py-3 text-right"><a href="{{ route($settingsPrefix.'.edit', ['branch_id' => $configuration['branch_id'], 'kategori' => $configuration['kategori'], 'kadar' => $configuration['kadar']]) }}" class="action-button action-edit">Edit</a></td></tr>
                            @empty
                                <tr><td colspan="6" class="px-4 py-10 text-center text-gray-500">Belum ada kategori aktif atau kadar emas yang sesuai filter.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="border-t border-gray-100 px-5 py-4">{{ $configurations->links() }}</div>
            </div>
        </div>
    </div>
</x-app-layout>
