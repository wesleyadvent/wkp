@php
    $isSale = $kind === 'sales';
    $money = fn ($value) => 'Rp '.number_format($value ?? 0, 2, ',', '.');
    $weight = fn ($value) => number_format($value ?? 0, 3, ',', '.').' gram';
@endphp
<article class="receipt">
    <p class="text-sm text-gray-500">Toko Emas Wijaya Kusuma � {{ $isSale ? 'Penjualan' : 'Pembelian' }}</p>
    <h1 class="mt-2 text-2xl font-bold">{{ $transaction->nomor_nota }}</h1>
    <h3>Informasi transaksi</h3>
    <dl>
        <div><dt>Tanggal</dt><dd>{{ $transaction->tanggal?->format('d/m/Y') }}</dd></div>
        <div><dt>Status</dt><dd>{{ ucfirst($transaction->status) }}</dd></div>
        <div><dt>Cabang</dt><dd>{{ $transaction->receipt('branch')?->nama_cabang ?? '�' }} ({{ $transaction->receipt('branch')?->kode_cabang }})</dd></div>
        <div><dt>Alamat / telepon cabang</dt><dd>{{ $transaction->receipt('branch')?->alamat ?? '�' }} / {{ $transaction->receipt('branch')?->telepon ?? '�' }}</dd></div>
        <div><dt>Customer</dt><dd>{{ $transaction->receipt('customer')?->nama ?? '�' }}</dd></div>
        <div><dt>Telepon customer</dt><dd>{{ $transaction->receipt('customer')?->telepon ?? '�' }}</dd></div>
        <div><dt>Alamat customer</dt><dd>{{ $transaction->receipt('customer')?->alamat ?? '�' }}</dd></div>
        <div><dt>Karyawan pembuat nota</dt><dd>{{ $transaction->employee_name ?? 'Belum tercatat (nota lama)' }}</dd></div>
        <div><dt>Akun pencatat</dt><dd>{{ $transaction->receipt('creator')?->name ?? '�' }}</dd></div>
        @unless($isSale)
        <div><dt>Dibuat</dt><dd>{{ $transaction->created_at?->format('d/m/Y H:i') ?? '�' }}</dd></div>
        <div><dt>Terakhir diperbarui</dt><dd>{{ $transaction->updated_at?->format('d/m/Y H:i') ?? '�' }}</dd></div>
        @endunless
        @unless($isSale)
            <div><dt>Sumber barang</dt><dd>{{ $transaction->sumber_barang === 'toko_sendiri' ? 'Toko sendiri' : 'Barang luar' }}</dd></div>
            <div><dt>Referensi nota penjualan</dt><dd>{{ $transaction->referenceSale?->nomor_nota ?? '�' }}</dd></div>
        @endunless
        @if($transaction->cancelled_at)
            <div><dt>Dibatalkan oleh</dt><dd>{{ $transaction->canceller?->name ?? '�' }}</dd></div>
            <div><dt>Alasan pembatalan</dt><dd>{{ $transaction->cancellation?->alasan ?? '�' }}</dd></div>
            <div><dt>Waktu pembatalan</dt><dd>{{ $transaction->cancelled_at->format('d/m/Y H:i') }}</dd></div>
        @endif
    </dl>
    <h3>Rincian barang</h3>
    <div class="overflow-x-auto"><div class="table-scroll" role="region" aria-label="Tabel data" tabindex="0"><table class="data-table">
        <thead><tr><th scope="col">Barang</th><th scope="col">Kategori / kadar</th><th scope="col">Berat</th><th scope="col">Harga / gram</th><th scope="col">{{ $isSale ? 'Ongkos' : 'Potongan / gram' }}</th><th scope="col">Subtotal</th></tr></thead>
        <tbody>@foreach($transaction->items as $item)
            <tr>
                <td>@if($isSale && $item->receipt('product')?->photo_path)<a href="{{ route('products.photo', $item->product) }}" target="_blank" rel="noopener"><img src="{{ route('products.photo', $item->product) }}" alt="Foto {{ $item->receipt('product')->nama_barang }}" style="width:100px;height:100px;object-fit:contain;border-radius:8px;margin-bottom:8px" /></a>@endif<b>{{ $isSale ? ($item->receipt('product')?->nama_barang ?? '�') : $item->nama_barang }}</b><br><span class="text-xs text-gray-500">{{ $item->receipt('product')?->kode_barang ?? 'Barang luar / tanpa referensi' }}</span>@if($isSale && $item->receipt('product')?->deskripsi)<p class="mt-1 text-xs">{{ $item->receipt('product')->deskripsi }}</p>@endif</td>
                <td>{{ $isSale ? $item->receipt('product')?->kategori : $item->kategori }} / {{ $isSale ? $item->receipt('product')?->kadar : $item->kadar }}</td>
                <td class="table-number">{{ $weight($item->berat) }}</td>
                <td class="table-number">{{ $money($isSale ? $item->harga_per_gram : $item->harga_per_gram_beli) }}</td>
                <td class="table-number">{{ $money($isSale ? $item->ongkos : $item->potongan_per_gram) }}</td>
                <td class="table-number">{{ $money($item->subtotal) }}</td>
            </tr>
        @endforeach</tbody>
    </table></div></div>
    @unless($isSale)
        <h3>Potongan tambahan</h3>
        <div class="table-scroll" role="region" aria-label="Tabel data" tabindex="0"><table class="data-table"><thead><tr><th scope="col">Jenis potongan</th><th scope="col">Keterangan</th><th scope="col">Nominal</th></tr></thead><tbody>
            @forelse($transaction->deductions as $deduction)<tr><td>{{ $deduction->receipt('deductionType')?->nama_potongan ?? 'Potongan lainnya' }}</td><td>{{ $deduction->keterangan ?? '�' }}</td><td class="table-number">{{ $money($deduction->nominal) }}</td></tr>
            @empty<tr><td colspan="3">Tidak ada potongan tambahan.</td></tr>@endforelse
        </tbody></table></div>
    @endunless
    <h3>Ringkasan pembayaran</h3>
    <dl>
        <div><dt>Total berat</dt><dd>{{ $weight($transaction->total_berat) }}</dd></div>
        <div><dt>{{ $isSale ? 'Total ongkos' : 'Total potongan (per gram + tambahan)' }}</dt><dd>{{ $money($isSale ? $transaction->total_ongkos : $transaction->total_potongan) }}</dd></div>
        <div><dt>Total {{ $isSale ? 'penjualan' : 'dibayarkan kepada customer' }}</dt><dd class="text-xl">{{ $money($transaction->total_harga) }}</dd></div>
    </dl>
</article>
