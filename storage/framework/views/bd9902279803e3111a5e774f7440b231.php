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
     <?php $__env->slot('header', null, []); ?> <div class="flex items-center justify-between"><h2 class="font-semibold text-xl text-gray-800">Harga Emas</h2><?php if(Auth::user()->isAdmin()): ?><a href="<?php echo e(route('gold-prices.create')); ?>" class="rounded bg-gray-900 px-4 py-2 text-sm text-white">Tambah kadar</a><?php endif; ?></div> <?php $__env->endSlot(); ?>
    <div class="py-8"><div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <?php if(session('status')): ?><div class="mb-4 rounded bg-green-50 p-4 text-green-800"><?php echo e(session('status')); ?></div><?php endif; ?>
        <?php if (isset($component)) { $__componentOriginalf94ed9c5393ef72725d159fe01139746 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalf94ed9c5393ef72725d159fe01139746 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => '92edfda80dc3faed2cda654b45151250::input-error','data' => ['messages' => $errors->get('delete'),'class' => 'mb-4 rounded bg-red-50 p-4']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('input-error'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['messages' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($errors->get('delete')),'class' => 'mb-4 rounded bg-red-50 p-4']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalf94ed9c5393ef72725d159fe01139746)): ?>
<?php $attributes = $__attributesOriginalf94ed9c5393ef72725d159fe01139746; ?>
<?php unset($__attributesOriginalf94ed9c5393ef72725d159fe01139746); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalf94ed9c5393ef72725d159fe01139746)): ?>
<?php $component = $__componentOriginalf94ed9c5393ef72725d159fe01139746; ?>
<?php unset($__componentOriginalf94ed9c5393ef72725d159fe01139746); ?>
<?php endif; ?>
        <p class="mb-4 text-sm text-gray-600">Harga terbaru untuk setiap kadar. Buka detail untuk melihat riwayat perubahan.</p>
        <div class="overflow-x-auto bg-white shadow-sm sm:rounded-lg"><table class="data-table min-w-full text-sm">
            <thead><tr><th class="px-4 py-3 text-left">Kadar</th><th class="px-4 py-3 text-left">Berlaku sejak</th><th class="px-4 py-3 text-right">Harga/gram</th><th class="px-4 py-3 text-left">Keterangan</th><th class="px-4 py-3 text-right">Aksi</th></tr></thead>
            <tbody><?php $__empty_1 = true; $__currentLoopData = $items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><tr>
                <td class="px-4 py-3"><a class="text-blue-700 underline" href="<?php echo e(route('gold-prices.show', $item)); ?>"><?php echo e($item->kadar); ?></a></td>
                <td class="px-4 py-3"><?php echo e($item->tanggal->format('d/m/Y')); ?></td><td class="px-4 py-3 text-right">Rp <?php echo e(number_format($item->harga_per_gram, 2, ',', '.')); ?></td><td class="px-4 py-3"><?php echo e($item->keterangan); ?></td>
                <td class="px-4 py-3 text-right"><a class="action-button" href="<?php echo e(route('gold-prices.show', $item)); ?>">Detail</a> <?php if(Auth::user()->isAdmin()): ?><a class="action-button action-edit" href="<?php echo e(route('gold-prices.edit', $item)); ?>">Edit</a>
                    <?php if(\App\Services\MasterDataUsage::isUsed($item) || \App\Models\HargaEmas::where('kadar', $item->kadar)->whereKeyNot($item->id)->exists()): ?>
                        <button type="button" disabled aria-disabled="true" class="action-button cursor-not-allowed opacity-60" title="Sudah digunakan atau memiliki riwayat.">Hapus</button><span class="block text-xs text-gray-500">Sudah digunakan atau memiliki riwayat</span>
                    <?php else: ?>
                        <button type="button" class="action-button action-delete" data-delete-url="<?php echo e(route('gold-prices.destroy', $item)); ?>" data-delete-label="<?php echo e($item->kadar); ?>" aria-haspopup="dialog">Hapus</button>
                    <?php endif; ?>
                <?php endif; ?></td>
            </tr><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><tr><td colspan="5" class="px-4 py-8 text-center">Belum ada data harga.</td></tr><?php endif; ?></tbody>
        </table></div><div class="mt-4 flex flex-wrap items-center justify-between gap-3"><?php if (isset($component)) { $__componentOriginal720c5d99204acad589a79c73de989541 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal720c5d99204acad589a79c73de989541 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.per-page-selector','data' => ['paginator' => $items]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('per-page-selector'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['paginator' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($items)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal720c5d99204acad589a79c73de989541)): ?>
<?php $attributes = $__attributesOriginal720c5d99204acad589a79c73de989541; ?>
<?php unset($__attributesOriginal720c5d99204acad589a79c73de989541); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal720c5d99204acad589a79c73de989541)): ?>
<?php $component = $__componentOriginal720c5d99204acad589a79c73de989541; ?>
<?php unset($__componentOriginal720c5d99204acad589a79c73de989541); ?>
<?php endif; ?><?php echo e($items->links()); ?></div>
    </div></div>
    <?php if(Auth::user()->isAdmin()): ?><?php if (isset($component)) { $__componentOriginal9502acf056f720678e71cee27afcafd8 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9502acf056f720678e71cee27afcafd8 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => '92edfda80dc3faed2cda654b45151250::delete-confirmation','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('delete-confirmation'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal9502acf056f720678e71cee27afcafd8)): ?>
<?php $attributes = $__attributesOriginal9502acf056f720678e71cee27afcafd8; ?>
<?php unset($__attributesOriginal9502acf056f720678e71cee27afcafd8); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal9502acf056f720678e71cee27afcafd8)): ?>
<?php $component = $__componentOriginal9502acf056f720678e71cee27afcafd8; ?>
<?php unset($__componentOriginal9502acf056f720678e71cee27afcafd8); ?>
<?php endif; ?><?php endif; ?>
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
<?php /**PATH D:\project\toko-emas-wijaya-kusuma\resources\views/harga-emas/index.blade.php ENDPATH**/ ?>