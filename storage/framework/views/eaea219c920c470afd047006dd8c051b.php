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
     <?php $__env->slot('header', null, []); ?> 
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div><p class="text-xs font-semibold uppercase tracking-[.18em] text-amber-600">Nota service</p><h2 class="mt-1 text-xl font-semibold text-slate-900"><?php echo e($note->nomor_nota); ?></h2></div>
            <?php if (isset($component)) { $__componentOriginal8c81617a70e11bcf247c4db924ab1b62 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal8c81617a70e11bcf247c4db924ab1b62 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => '92edfda80dc3faed2cda654b45151250::status-badge','data' => ['value' => $note->status_service]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('status-badge'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($note->status_service)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal8c81617a70e11bcf247c4db924ab1b62)): ?>
<?php $attributes = $__attributesOriginal8c81617a70e11bcf247c4db924ab1b62; ?>
<?php unset($__attributesOriginal8c81617a70e11bcf247c4db924ab1b62); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal8c81617a70e11bcf247c4db924ab1b62)): ?>
<?php $component = $__componentOriginal8c81617a70e11bcf247c4db924ab1b62; ?>
<?php unset($__componentOriginal8c81617a70e11bcf247c4db924ab1b62); ?>
<?php endif; ?>
        </div>
     <?php $__env->endSlot(); ?>
    <div class="py-8"><div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
        <div class="space-y-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
            <div class="flex flex-wrap items-start justify-between gap-4 border-b border-slate-100 pb-6">
                <div><p class="text-sm text-slate-500">Simpan nomor nota ini untuk pengambilan barang.</p><p class="mt-2 text-2xl font-bold tracking-wide text-slate-900"><?php echo e($note->nomor_nota); ?></p><p class="mt-1 text-sm text-slate-500">Dibuat <?php echo e($note->created_at?->format('d/m/Y H:i')); ?></p></div>
                <a href="<?php echo e(route('service-notes.print',$note)); ?>" class="action-button action-print">Cetak nota</a>
            </div>
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <div class="rounded-xl bg-slate-50 p-4"><p class="text-xs uppercase tracking-wide text-slate-500">Customer</p><p class="mt-1 font-semibold text-slate-900"><?php echo e($note->receipt('customer')?->nama ?? '-'); ?></p><p class="mt-1 text-sm text-slate-500"><?php echo e($note->receipt('customer')?->telepon ?? '-'); ?></p><p class="text-sm text-slate-500"><?php echo e($note->receipt('customer')?->alamat ?? '-'); ?></p></div>
                <div class="rounded-xl bg-slate-50 p-4"><p class="text-xs uppercase tracking-wide text-slate-500">Barang</p><p class="mt-1 font-semibold text-slate-900"><?php echo e($note->receipt('product')?->nama_barang ?? $note->nama_barang_manual ?? '-'); ?></p><p class="mt-1 text-sm text-slate-500">Berat: <?php echo e($note->berat ? number_format($note->berat, 3, ',', '.') . ' gram' : '-'); ?></p></div>
                <div class="rounded-xl bg-amber-50 p-4"><p class="text-xs uppercase tracking-wide text-amber-700">Estimasi selesai</p><p class="mt-1 font-semibold text-slate-900"><?php echo e($note->estimasi_selesai?->format('d/m/Y') ?? 'Belum ditentukan'); ?></p><p class="mt-1 text-sm text-slate-600">Cabang: <?php echo e($note->receipt('branch')?->nama_cabang ?? '-'); ?></p></div>
            </div>
            <div><div class="mb-3 flex items-center justify-between"><h3 class="text-lg font-semibold text-slate-900">Rincian pengerjaan</h3><span class="text-sm text-slate-500"><?php echo e($note->items->count()); ?> jenis service</span></div><div class="overflow-hidden rounded-xl border border-slate-200"><table class="w-full text-left text-sm"><thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500"><tr><th class="px-4 py-3">Jenis service</th><th class="px-4 py-3">Keterangan</th><th class="px-4 py-3 text-right">Biaya</th></tr></thead><tbody class="divide-y divide-slate-100"><?php $__empty_1 = true; $__currentLoopData = $note->items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><tr><td class="px-4 py-4 font-medium text-slate-900"><?php echo e($item->receipt('serviceType')?->nama_service ?? '-'); ?></td><td class="px-4 py-4 text-slate-500"><?php echo e($item->keterangan ?: '-'); ?></td><td class="px-4 py-4 text-right font-semibold text-slate-900">Rp <?php echo e(number_format($item->harga_service, 2, ',', '.')); ?></td></tr><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><tr><td colspan="3" class="px-4 py-6 text-center text-slate-500">Belum ada rincian service.</td></tr><?php endif; ?></tbody><tfoot class="bg-slate-50"><tr><th colspan="2" class="px-4 py-4 text-right text-slate-600">Total service</th><th class="px-4 py-4 text-right text-lg text-slate-900">Rp <?php echo e(number_format($note->harga_service, 2, ',', '.')); ?></th></tr></tfoot></table></div></div>
            <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 pt-5"><p class="text-sm text-slate-500">Dibuat oleh: <?php echo e($note->employee_name ?? 'Belum tercatat'); ?></p><div class="flex flex-wrap gap-2"><?php if(!in_array($note->status_service, ['selesai','sudah_diambil','batal'], true)): ?><form method="POST" action="<?php echo e(route('service-notes.completed',$note)); ?>"><?php echo csrf_field(); ?> <?php echo method_field('PATCH'); ?><button class="action-button border-emerald-200 bg-emerald-50 text-emerald-700 hover:bg-emerald-100">Tandai selesai</button></form><?php endif; ?> <?php if($note->status_service === 'selesai'): ?><form method="POST" action="<?php echo e(route('service-notes.picked-up',$note)); ?>"><?php echo csrf_field(); ?> <?php echo method_field('PATCH'); ?><button class="action-button action-detail">Tandai sudah diambil</button></form><?php endif; ?></div></div>
        </div>
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
<?php /**PATH D:\project\toko-emas-wijaya-kusuma\resources\views/nota-layanan/show.blade.php ENDPATH**/ ?>