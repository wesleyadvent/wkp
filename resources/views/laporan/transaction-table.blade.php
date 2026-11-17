<div class="overflow-x-auto rounded bg-white p-4 shadow-sm report-data">
    <div class="table-scroll" role="region" aria-label="Tabel data" tabindex="0"><table class="data-table min-w-full text-sm report-table">
        <thead><tr class="bg-gray-50 text-left"><th scope="col">Nota / tanggal / cabang</th><th scope="col">Customer / pembuat nota</th><th scope="col">Rincian barang</th><th scope="col">{{ $type === 'sales' ? 'Ongkos' : 'Potongan' }}</th><th scope="col" class="table-number">Berat total</th><th scope="col" class="table-number">Total harga</th><th scope="col">Status</th></tr></thead>
        <tbody>
        @forelse($rows as $row)
            <tr class="border-b align-top">
                <td><strong>@if(!$printing)<a class="table-primary text-indigo-700 underline" href="{{ route($type.'.show', $row) }}">{{ $row->nomor_nota }}</a>@else{{ $row->nomor_nota }}@endif</strong><br>{{ $row->tanggal?->format('d/m/Y') }}<br>{{ $row->receipt('branch')?->nama_cabang ?? '—' }}
                    @if($type === 'purchases')<br>Asal: {{ str_replace('_', ' ', $row->sumber_barang) }}<br>Nota referensi: {{ $row->referenceSale?->nomor_nota ?? '—' }}@endif
                </td>
                <td><span class="table-primary">{{ $row->receipt('customer')?->nama ?? '—' }}</span><br>Telp: {{ $row->receipt('customer')?->telepon ?? '—' }}<br>{{ $row->receipt('customer')?->alamat ?? '—' }}<br>Karyawan: {{ $row->employee_name ?? 'Belum tercatat' }}<br>Akun: {{ $row->receipt('creator')?->name ?? '—' }}</td>
                <td>
                    @foreach($row->items as $item)
                        <div class="mb-3 table-item">
                            <strong>{{ $type === 'sales' ? ($item->receipt('product')?->nama_barang ?? '—') : $item->nama_barang }}</strong><br>
                            Kode: {{ $item->receipt('product')?->kode_barang ?? '—' }} · Kategori: {{ $type === 'sales' ? ($item->receipt('product')?->kategori ?? '—') : ($item->kategori ?? '—') }} · Kadar: {{ $type === 'sales' ? ($item->receipt('product')?->kadar ?? '—') : ($item->kadar ?? '—') }}<br>
                            {{ number_format($item->berat, 3, ',', '.') }} g × Rp {{ number_format($type === 'sales' ? $item->harga_per_gram : $item->harga_per_gram_beli, 2, ',', '.') }}/g<br>
                            @if($type === 'sales')Ongkos: Rp {{ number_format($item->ongkos, 2, ',', '.') }}@else Potongan/gram: Rp {{ number_format($item->potongan_per_gram, 2, ',', '.') }}@endif<br>
                            Subtotal: Rp {{ number_format($item->subtotal, 2, ',', '.') }}
                        </div>
                    @endforeach
                </td>
                <td>
                    @if($type === 'purchases')
                        @foreach($row->deductions as $deduction)<div>{{ $deduction->receipt('deductionType')?->nama_potongan ?? 'Potongan' }}: Rp {{ number_format($deduction->nominal, 2, ',', '.') }}@if($deduction->keterangan)<br>{{ $deduction->keterangan }}@endif</div>@endforeach
                    @endif
                    <strong>Total: Rp {{ number_format($type === 'sales' ? $row->total_ongkos : $row->total_potongan, 2, ',', '.') }}</strong>
                </td>
                <td class="table-number">{{ number_format($row->total_berat, 3, ',', '.') }} g</td>
                <td class="table-number">Rp {{ number_format($row->total_harga, 2, ',', '.') }}</td>
                <td><x-status-badge :value="$row->status" :label="in_array($row->status, ['batal', 'cancelled']) ? 'Batal' : ucfirst($row->status)" />@if(in_array($row->status, ['batal', 'cancelled']))<br>{{ $row->cancelled_at?->format('d/m/Y H:i') }}<br>{{ $row->canceller?->name }}<br>{{ $row->cancellation?->alasan }}@endif</td>
            </tr>
        @empty
            <tr><td colspan="7" class="py-8 text-center">Tidak ada transaksi sesuai filter.</td></tr>
        @endforelse
        </tbody>
    </table></div>
    <div class="mt-4 border-t pt-4 report-summary">
        <div class="summary-card"><span>Transaksi valid</span><strong>{{ number_format($totalCount, 0, ',', '.') }}</strong><small>Transaksi batal tetap ditampilkan di tabel.</small></div>
        <div class="summary-card"><span>Total berat valid</span><strong>{{ number_format($totalWeight, 3, ',', '.') }} gram</strong><small>Tidak termasuk transaksi batal.</small></div>
        <div class="summary-card"><span>Total harga valid</span><strong>Rp {{ number_format($total, 2, ',', '.') }}</strong><small>Tidak termasuk transaksi batal.</small></div>
        <p class="report-note">Ringkasan hanya menghitung transaksi berstatus valid, sedangkan transaksi batal tetap ditampilkan agar riwayat laporan lengkap.</p>
    </div>
</div>
