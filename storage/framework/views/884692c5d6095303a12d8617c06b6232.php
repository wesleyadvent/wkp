<?php
    $isSale = $kind === 'sales';
    $money = fn ($value) => 'Rp '.number_format($value ?? 0, 2, ',', '.');
    $weight = fn ($value) => number_format($value ?? 0, 3, ',', '.').' gram';
?>
<article class="receipt">
    <?php if($isSale && ($printLayout ?? false)): ?>
    <header class="receipt-header">
        <img class="store-logo" src="<?php echo e(asset('images/wkp-gradasi.png')); ?>" alt="Logo Toko Emas Wijaya Kusuma" width="76" height="76">
        <div class="store-heading"><strong>TOKO EMAS WIJAYA KUSUMA</strong><span>NOTA PENJUALAN</span><h1><?php echo e($transaction->nomor_nota); ?></h1></div>
    </header>
    <?php else: ?>
    <p class="text-sm text-gray-500">Toko Emas Wijaya Kusuma � <?php echo e($isSale ? 'Penjualan' : 'Pembelian'); ?></p>
    <h1 class="mt-2 text-2xl font-bold"><?php echo e($transaction->nomor_nota); ?></h1>
    <?php endif; ?>
    <h3>Informasi transaksi</h3>
    <dl>
        <div><dt>Tanggal</dt><dd><?php echo e($transaction->tanggal?->format('d/m/Y')); ?></dd></div>
        <div><dt>Status</dt><dd><?php echo e(ucfirst($transaction->status)); ?></dd></div>
        <div><dt>Cabang</dt><dd><?php echo e($transaction->receipt('branch')?->nama_cabang ?? '�'); ?> (<?php echo e($transaction->receipt('branch')?->kode_cabang); ?>)</dd></div>
        <div><dt>Alamat / telepon cabang</dt><dd><?php echo e($transaction->receipt('branch')?->alamat ?? '�'); ?> / <?php echo e($transaction->receipt('branch')?->telepon ?? '�'); ?></dd></div>
        <div><dt>Customer</dt><dd><?php echo e($transaction->receipt('customer')?->nama ?? '�'); ?></dd></div>
        <div><dt>Telepon customer</dt><dd><?php echo e($transaction->receipt('customer')?->telepon ?? '�'); ?></dd></div>
        <div><dt>Alamat customer</dt><dd><?php echo e($transaction->receipt('customer')?->alamat ?? '�'); ?></dd></div>
        <div><dt>Karyawan pembuat nota</dt><dd><?php echo e($transaction->employee_name ?? 'Belum tercatat (nota lama)'); ?></dd></div>
        <div><dt>Akun pencatat</dt><dd><?php echo e($transaction->receipt('creator')?->name ?? '�'); ?></dd></div>
        <?php if (! ($isSale)): ?>
        <div><dt>Dibuat</dt><dd><?php echo e($transaction->created_at?->format('d/m/Y H:i') ?? '�'); ?></dd></div>
        <div><dt>Terakhir diperbarui</dt><dd><?php echo e($transaction->updated_at?->format('d/m/Y H:i') ?? '�'); ?></dd></div>
        <?php endif; ?>
        <?php if (! ($isSale)): ?>
            <div><dt>Sumber barang</dt><dd><?php echo e($transaction->sumber_barang === 'toko_sendiri' ? 'Toko sendiri' : 'Barang luar'); ?></dd></div>
            <div><dt>Referensi nota penjualan</dt><dd><?php echo e($transaction->referenceSale?->nomor_nota ?? '�'); ?></dd></div>
        <?php endif; ?>
        <?php if($transaction->cancelled_at): ?>
            <div><dt>Dibatalkan oleh</dt><dd><?php echo e($transaction->canceller?->name ?? '�'); ?></dd></div>
            <div><dt>Alasan pembatalan</dt><dd><?php echo e($transaction->cancellation?->alasan ?? '�'); ?></dd></div>
            <div><dt>Waktu pembatalan</dt><dd><?php echo e($transaction->cancelled_at->format('d/m/Y H:i')); ?></dd></div>
        <?php endif; ?>
    </dl>
    <h3>Rincian barang</h3>
    <div class="overflow-x-auto"><div class="table-scroll" role="region" aria-label="Tabel data" tabindex="0"><table class="data-table">
        <thead><tr><th scope="col">Barang</th><th scope="col">Kategori / kadar</th><th scope="col">Berat</th><th scope="col">Harga / gram</th><th scope="col"><?php echo e($isSale ? 'Ongkos' : 'Potongan / gram'); ?></th><th scope="col">Subtotal</th></tr></thead>
        <tbody><?php $__currentLoopData = $transaction->items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <tr>
                <td><?php if($isSale && $item->receipt('product')?->photo_path): ?><a href="<?php echo e(route('products.photo', $item->product)); ?>" target="_blank" rel="noopener"><img src="<?php echo e(route('products.photo', $item->product)); ?>" alt="Foto <?php echo e($item->receipt('product')->nama_barang); ?>" style="width:100px;height:100px;object-fit:contain;border-radius:8px;margin-bottom:8px" /></a><?php endif; ?><b><?php echo e($isSale ? ($item->receipt('product')?->nama_barang ?? '�') : $item->nama_barang); ?></b><br><span class="text-xs text-gray-500"><?php echo e($item->receipt('product')?->kode_barang ?? 'Barang luar / tanpa referensi'); ?></span><?php if($isSale && $item->receipt('product')?->deskripsi): ?><p class="mt-1 text-xs"><?php echo e($item->receipt('product')->deskripsi); ?></p><?php endif; ?></td>
                <td><?php echo e($isSale ? $item->receipt('product')?->kategori : $item->kategori); ?> / <?php echo e($isSale ? $item->receipt('product')?->kadar : $item->kadar); ?></td>
                <td class="table-number"><?php echo e($weight($item->berat)); ?></td>
                <td class="table-number"><?php echo e($money($isSale ? $item->harga_per_gram : $item->harga_per_gram_beli)); ?></td>
                <td class="table-number"><?php echo e($money($isSale ? $item->ongkos : $item->potongan_per_gram)); ?></td>
                <td class="table-number"><?php echo e($money($item->subtotal)); ?></td>
            </tr>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></tbody>
    </table></div></div>
    <?php if (! ($isSale)): ?>
        <h3>Potongan tambahan</h3>
        <div class="table-scroll" role="region" aria-label="Tabel data" tabindex="0"><table class="data-table"><thead><tr><th scope="col">Jenis potongan</th><th scope="col">Keterangan</th><th scope="col">Nominal</th></tr></thead><tbody>
            <?php $__empty_1 = true; $__currentLoopData = $transaction->deductions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $deduction): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><tr><td><?php echo e($deduction->receipt('deductionType')?->nama_potongan ?? 'Potongan lainnya'); ?></td><td><?php echo e($deduction->keterangan ?? '�'); ?></td><td class="table-number"><?php echo e($money($deduction->nominal)); ?></td></tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><tr><td colspan="3">Tidak ada potongan tambahan.</td></tr><?php endif; ?>
        </tbody></table></div>
    <?php endif; ?>
    <h3>Ringkasan pembayaran</h3>
    <dl>
        <div><dt>Total berat</dt><dd><?php echo e($weight($transaction->total_berat)); ?></dd></div>
        <div><dt><?php echo e($isSale ? 'Total ongkos' : 'Total potongan (per gram + tambahan)'); ?></dt><dd><?php echo e($money($isSale ? $transaction->total_ongkos : $transaction->total_potongan)); ?></dd></div>
        <div><dt>Total <?php echo e($isSale ? 'penjualan' : 'dibayarkan kepada customer'); ?></dt><dd class="text-xl"><?php echo e($money($transaction->total_harga)); ?></dd></div>
    </dl>
</article>
<?php /**PATH D:\project\toko-emas-wijaya-kusuma\resources\views/transaksi/receipt.blade.php ENDPATH**/ ?>