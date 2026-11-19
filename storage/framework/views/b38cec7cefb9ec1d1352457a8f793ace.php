<?php if (isset($component)) { $__componentOriginal9ac128a9029c0e4701924bd2d73d7f54 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9ac128a9029c0e4701924bd2d73d7f54 = $attributes; } ?>
<?php $component = App\View\Components\AppLayout::resolve([] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('app-layout'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\App\View\Components\AppLayout::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
     <?php $__env->slot('header', null, []); ?> <h2 class="font-semibold text-xl text-gray-800">Detail Penjualan</h2> <?php $__env->endSlot(); ?>
    <div class="py-8"><div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        <?php if(session('status')): ?><p class="mb-4 rounded-lg bg-green-50 p-4 text-green-800"><?php echo e(session('status')); ?></p><?php endif; ?>
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <a href="<?php echo e(route('sales.index')); ?>" class="action-button action-detail">Kembali ke Penjualan</a>
            <a href="<?php echo e(route('sales.print', $sale)); ?>" target="_blank" rel="noopener" class="action-button action-print"><svg aria-hidden="true" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M6 9V3h12v6M6 18H3V9h18v9h-3M6 14h12v7H6z"/></svg>Cetak Nota</a>
        </div>
        <?php echo $__env->make('transaksi.receipt', ['transaction' => $sale, 'kind' => 'sales'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    </div></div>
 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal9ac128a9029c0e4701924bd2d73d7f54)): ?>
<?php $attributes = $__attributesOriginal9ac128a9029c0e4701924bd2d73d7f54; ?>
<?php unset($__attributesOriginal9ac128a9029c0e4701924bd2d73d7f54); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal9ac128a9029c0e4701924bd2d73d7f54)): ?>
<?php $component = $__componentOriginal9ac128a9029c0e4701924bd2d73d7f54; ?>
<?php unset($__componentOriginal9ac128a9029c0e4701924bd2d73d7f54); ?>
<?php endif; ?>
<?php /**PATH D:\project\toko-emas-wijaya-kusuma\resources\views/penjualan/show.blade.php ENDPATH**/ ?>