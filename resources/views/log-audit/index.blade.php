<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800">Audit Log</h2>
    </x-slot>
    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                <div class="table-scroll" role="region" aria-label="Tabel data" tabindex="0">
                    <table class="data-table min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50 text-left text-xs uppercase text-gray-500">
                            <tr>
                                <th scope="col" class="px-4 py-3">Waktu</th>
                                <th scope="col" class="px-4 py-3">User</th>
                                <th scope="col" class="px-4 py-3">Aksi</th>
                                <th scope="col" class="px-4 py-3">Tabel</th>
                                <th scope="col" class="px-4 py-3">Record</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">@forelse($logs as $log)
                            <tr>
                                <td class="px-4 py-3">{{ $log->created_at }}</td>
                                <td class="px-4 py-3">{{ $log->user?->name }}</td>
                                <td class="px-4 py-3">{{ $log->action }}</td>
                                <td class="px-4 py-3">{{ $log->table_name }}</td>
                                <td class="px-4 py-3">{{ $log->record_id }}</td>
                        </tr>@empty<tr>
                                <td colspan="5">Belum ada data yang dapat ditampilkan.</td>
                            </tr>@endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="mt-4">{{ $logs->links() }}</div>
        </div>
    </div>
</x-app-layout>