<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <h2 class="font-semibold text-xl text-gray-800">Laporan Service</h2><a
                href="{{ route('reports.services', array_merge(request()->query(), ['print' => 1])) }}" target="_blank"
                rel="noopener" class="rounded bg-gray-900 px-4 py-2 text-sm font-semibold text-white">Cetak Laporan</a>
        </div>
    </x-slot>
    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">
            <form method="GET" class="grid gap-3 bg-white p-4 shadow-sm sm:rounded-lg md:grid-cols-5"><x-text-input
                    type="date" name="start" :value="request('start')" /><x-text-input type="date" name="end"
                    :value="request('end')" />@if(Auth::user()->isAdmin())<select name="branch_id"
                        class="rounded-md border-gray-300">
                        <option value="">Semua cabang</option>@foreach($branches as $branch)
                            <option value="{{ $branch->id }}" @selected((int) request('branch_id') === $branch->id)>
                        {{ $branch->nama_cabang }}</option>@endforeach
                    </select>@endif<select name="status" class="rounded-md border-gray-300">
                    <option value="">Semua status</option>
                    @foreach(['diterima', 'diproses', 'selesai', 'sudah_diambil', 'batal'] as $status)
                        <option value="{{ $status }}" @selected(request('status') === $status)>{{ $status }}</option>
                    @endforeach
                </select><x-primary-button>Filter</x-primary-button></form>
            <div class="rounded bg-white p-4 shadow-sm font-semibold">Total service selesai: Rp
                {{ number_format($total, 0, ',', '.') }}</div>
            <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                <div class="table-scroll" role="region" aria-label="Tabel data" tabindex="0">
                    <table class="data-table min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50 text-left text-xs uppercase text-gray-500">
                            <tr>
                                <th scope="col" class="px-4 py-3">Nota</th>
                                <th scope="col" class="px-4 py-3">Tanggal</th>
                                <th scope="col" class="px-4 py-3">Cabang</th>
                                <th scope="col" class="px-4 py-3">Customer</th>
                                <th scope="col" class="px-4 py-3">Status</th>
                                <th scope="col" class="px-4 py-3 table-number">Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">@forelse($rows as $row)
                            <tr>
                                <td class="px-4 py-3">{{ $row->nomor_nota }}</td>
                                <td class="px-4 py-3">{{ $row->tanggal_masuk?->format('d/m/Y') }}</td>
                                <td class="px-4 py-3">{{ $row->receipt('branch')?->nama_cabang }}</td>
                                <td class="px-4 py-3">{{ $row->receipt('customer')?->nama }}</td>
                                <td class="px-4 py-3"><x-status-badge :value="$row->status_service" /></td>
                                <td class="px-4 py-3 table-number">Rp {{ number_format($row->harga_service, 0, ',', '.') }}
                                </td>
                        </tr>@empty<tr>
                                <td colspan="6">Belum ada data yang dapat ditampilkan.</td>
                            </tr>@endforelse
                        </tbody>
                    </table>
                </div>
            </div><div class="flex flex-wrap items-center justify-between gap-3"><x-per-page-selector :paginator="$rows" />{{ $rows->links() }}</div>
        </div>
    </div>
</x-app-layout>
