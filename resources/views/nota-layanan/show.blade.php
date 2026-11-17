<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div><p class="text-xs font-semibold uppercase tracking-[.18em] text-amber-600">Nota service</p><h2 class="mt-1 text-xl font-semibold text-slate-900">{{ $note->nomor_nota }}</h2></div>
            <x-status-badge :value="$note->status_service" />
        </div>
    </x-slot>
    <div class="py-8"><div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
        <div class="space-y-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
            <div class="flex flex-wrap items-start justify-between gap-4 border-b border-slate-100 pb-6">
                <div><p class="text-sm text-slate-500">Simpan nomor nota ini untuk pengambilan barang.</p><p class="mt-2 text-2xl font-bold tracking-wide text-slate-900">{{ $note->nomor_nota }}</p><p class="mt-1 text-sm text-slate-500">Dibuat {{ $note->created_at?->format('d/m/Y H:i') }}</p></div>
                <a href="{{ route('service-notes.print',$note) }}" class="action-button action-print">Cetak nota</a>
            </div>
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <div class="rounded-xl bg-slate-50 p-4"><p class="text-xs uppercase tracking-wide text-slate-500">Customer</p><p class="mt-1 font-semibold text-slate-900">{{ $note->receipt('customer')?->nama ?? '-' }}</p><p class="mt-1 text-sm text-slate-500">{{ $note->receipt('customer')?->telepon ?? '-' }}</p><p class="text-sm text-slate-500">{{ $note->receipt('customer')?->alamat ?? '-' }}</p></div>
                <div class="rounded-xl bg-slate-50 p-4"><p class="text-xs uppercase tracking-wide text-slate-500">Barang</p><p class="mt-1 font-semibold text-slate-900">{{ $note->receipt('product')?->nama_barang ?? $note->nama_barang_manual ?? '-' }}</p><p class="mt-1 text-sm text-slate-500">Berat: {{ $note->berat ? number_format($note->berat, 3, ',', '.') . ' gram' : '-' }}</p></div>
                <div class="rounded-xl bg-amber-50 p-4"><p class="text-xs uppercase tracking-wide text-amber-700">Estimasi selesai</p><p class="mt-1 font-semibold text-slate-900">{{ $note->estimasi_selesai?->format('d/m/Y') ?? 'Belum ditentukan' }}</p><p class="mt-1 text-sm text-slate-600">Cabang: {{ $note->receipt('branch')?->nama_cabang ?? '-' }}</p></div>
            </div>
            <div><div class="mb-3 flex items-center justify-between"><h3 class="text-lg font-semibold text-slate-900">Rincian pengerjaan</h3><span class="text-sm text-slate-500">{{ $note->items->count() }} jenis service</span></div><div class="overflow-hidden rounded-xl border border-slate-200"><table class="w-full text-left text-sm"><thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500"><tr><th class="px-4 py-3">Jenis service</th><th class="px-4 py-3">Keterangan</th><th class="px-4 py-3 text-right">Biaya</th></tr></thead><tbody class="divide-y divide-slate-100">@forelse($note->items as $item)<tr><td class="px-4 py-4 font-medium text-slate-900">{{ $item->receipt('serviceType')?->nama_service ?? '-' }}</td><td class="px-4 py-4 text-slate-500">{{ $item->keterangan ?: '-' }}</td><td class="px-4 py-4 text-right font-semibold text-slate-900">Rp {{ number_format($item->harga_service, 2, ',', '.') }}</td></tr>@empty<tr><td colspan="3" class="px-4 py-6 text-center text-slate-500">Belum ada rincian service.</td></tr>@endforelse</tbody><tfoot class="bg-slate-50"><tr><th colspan="2" class="px-4 py-4 text-right text-slate-600">Total service</th><th class="px-4 py-4 text-right text-lg text-slate-900">Rp {{ number_format($note->harga_service, 2, ',', '.') }}</th></tr></tfoot></table></div></div>
            <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 pt-5"><p class="text-sm text-slate-500">Dibuat oleh: {{ $note->employee_name ?? 'Belum tercatat' }}</p><div class="flex flex-wrap gap-2">@if(!in_array($note->status_service, ['selesai','sudah_diambil','batal'], true))<form method="POST" action="{{ route('service-notes.completed',$note) }}">@csrf @method('PATCH')<button class="action-button border-emerald-200 bg-emerald-50 text-emerald-700 hover:bg-emerald-100">Tandai selesai</button></form>@endif @if($note->status_service === 'selesai')<form method="POST" action="{{ route('service-notes.picked-up',$note) }}">@csrf @method('PATCH')<button class="action-button action-detail">Tandai sudah diambil</button></form>@endif</div></div>
        </div>
    </div></div>
</x-app-layout>
