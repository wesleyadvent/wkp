<div class="overflow-x-auto rounded bg-white p-4 shadow-sm report-data">
    <div class="table-scroll" role="region" aria-label="Tabel data" tabindex="0"><table class="data-table min-w-full text-sm report-table">
        <thead><tr class="bg-gray-50 text-left"><th scope="col">Nota / tanggal / cabang</th><th scope="col">Customer / pembuat nota</th><th scope="col">Rincian barang</th><th scope="col"><?php echo e($type === 'sales' ? 'Ongkos' : 'Potongan'); ?></th><th scope="col" class="table-number">Berat total</th><th scope="col" class="table-number">Total harga</th><th scope="col">Status</th></tr></thead>
        <tbody>
        <?php $__empty_1 = true; $__currentLoopData = $rows; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <tr class="border-b align-top">
                <td><strong><?php if(!$printing): ?><a class="table-primary text-indigo-700 underline" href="<?php echo e(route($type.'.show', $row)); ?>"><?php echo e($row->nomor_nota); ?></a><?php else: ?><?php echo e($row->nomor_nota); ?><?php endif; ?></strong><br><?php echo e($row->tanggal?->format('d/m/Y')); ?><br><?php echo e($row->receipt('branch')?->nama_cabang ?? '—'); ?>

                    <?php if($type === 'purchases'): ?><br>Asal: <?php echo e(str_replace('_', ' ', $row->sumber_barang)); ?><br>Nota referensi: <?php echo e($row->referenceSale?->nomor_nota ?? '—'); ?><?php endif; ?>
                </td>
                <td><span class="table-primary"><?php echo e($row->receipt('customer')?->nama ?? '—'); ?></span><br>Telp: <?php echo e($row->receipt('customer')?->telepon ?? '—'); ?><br><?php echo e($row->receipt('customer')?->alamat ?? '—'); ?><br>Karyawan: <?php echo e($row->employee_name ?? 'Belum tercatat'); ?><br>Akun: <?php echo e($row->receipt('creator')?->name ?? '—'); ?></td>
                <td>
                    <?php $__currentLoopData = $row->items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <div class="mb-3 table-item">
                            <strong><?php echo e($type === 'sales' ? ($item->receipt('product')?->nama_barang ?? '—') : $item->nama_barang); ?></strong><br>
                            Kode: <?php echo e($item->receipt('product')?->kode_barang ?? '—'); ?> · Kategori: <?php echo e($type === 'sales' ? ($item->receipt('product')?->kategori ?? '—') : ($item->kategori ?? '—')); ?> · Kadar: <?php echo e($type === 'sales' ? ($item->receipt('product')?->kadar ?? '—') : ($item->kadar ?? '—')); ?><br>
                            <?php echo e(number_format($item->berat, 3, ',', '.')); ?> g × Rp <?php echo e(number_format($type === 'sales' ? $item->harga_per_gram : $item->harga_per_gram_beli, 2, ',', '.')); ?>/g<br>
                            <?php if($type === 'sales'): ?>Ongkos: Rp <?php echo e(number_format($item->ongkos, 2, ',', '.')); ?><?php else: ?> Potongan/gram: Rp <?php echo e(number_format($item->potongan_per_gram, 2, ',', '.')); ?><?php endif; ?><br>
                            Subtotal: Rp <?php echo e(number_format($item->subtotal, 2, ',', '.')); ?>

                        </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </td>
                <td>
                    <?php if($type === 'purchases'): ?>
                        <?php $__currentLoopData = $row->deductions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $deduction): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><div><?php echo e($deduction->receipt('deductionType')?->nama_potongan ?? 'Potongan'); ?>: Rp <?php echo e(number_format($deduction->nominal, 2, ',', '.')); ?><?php if($deduction->keterangan): ?><br><?php echo e($deduction->keterangan); ?><?php endif; ?></div><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    <?php endif; ?>
                    <strong>Total: Rp <?php echo e(number_format($type === 'sales' ? $row->total_ongkos : $row->total_potongan, 2, ',', '.')); ?></strong>
                </td>
                <td class="table-number"><?php echo e(number_format($row->total_berat, 3, ',', '.')); ?> g</td>
                <td class="table-number">Rp <?php echo e(number_format($row->total_harga, 2, ',', '.')); ?></td>
                <td><?php if (isset($component)) { $__componentOriginal8c81617a70e11bcf247c4db924ab1b62 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal8c81617a70e11bcf247c4db924ab1b62 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => '92edfda80dc3faed2cda654b45151250::status-badge','data' => ['value' => $row->status,'label' => in_array($row->status, ['batal', 'cancelled']) ? 'Batal' : ucfirst($row->status)]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('status-badge'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($row->status),'label' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(in_array($row->status, ['batal', 'cancelled']) ? 'Batal' : ucfirst($row->status))]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal8c81617a70e11bcf247c4db924ab1b62)): ?>
<?php $attributes = $__attributesOriginal8c81617a70e11bcf247c4db924ab1b62; ?>
<?php unset($__attributesOriginal8c81617a70e11bcf247c4db924ab1b62); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal8c81617a70e11bcf247c4db924ab1b62)): ?>
<?php $component = $__componentOriginal8c81617a70e11bcf247c4db924ab1b62; ?>
<?php unset($__componentOriginal8c81617a70e11bcf247c4db924ab1b62); ?>
<?php endif; ?><?php if(in_array($row->status, ['batal', 'cancelled'])): ?><br><?php echo e($row->cancelled_at?->format('d/m/Y H:i')); ?><br><?php echo e($row->canceller?->name); ?><br><?php echo e($row->cancellation?->alasan); ?><?php endif; ?></td>
            </tr>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <tr><td colspan="7" class="py-8 text-center">Tidak ada transaksi sesuai filter.</td></tr>
        <?php endif; ?>
        </tbody>
    </table></div>
    <div class="mt-4 border-t pt-4 report-summary">
        <div class="summary-card"><span>Transaksi valid</span><strong><?php echo e(number_format($totalCount, 0, ',', '.')); ?></strong><small>Transaksi batal tetap ditampilkan di tabel.</small></div>
        <div class="summary-card"><span>Total berat valid</span><strong><?php echo e(number_format($totalWeight, 3, ',', '.')); ?> gram</strong><small>Tidak termasuk transaksi batal.</small></div>
        <div class="summary-card"><span>Total harga valid</span><strong>Rp <?php echo e(number_format($total, 2, ',', '.')); ?></strong><small>Tidak termasuk transaksi batal.</small></div>
        <p class="report-note">Ringkasan hanya menghitung transaksi berstatus valid, sedangkan transaksi batal tetap ditampilkan agar riwayat laporan lengkap.</p>
    </div>
</div>
<?php /**PATH D:\project\toko-emas-wijaya-kusuma\resources\views/laporan/transaction-table.blade.php ENDPATH**/ ?>