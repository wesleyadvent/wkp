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
     <?php $__env->slot('header', null, []); ?> <div class="flex items-center justify-between gap-4"><h2 class="font-semibold text-xl text-gray-800">Riwayat Harga <?php echo e($goldPrice->kadar); ?></h2><div><a href="<?php echo e(route('gold-prices.index')); ?>" class="text-sm text-blue-700 underline">Kembali</a><?php if(Auth::user()->isAdmin()): ?> <a href="<?php echo e(route('gold-prices.edit', $goldPrice)); ?>" class="action-button action-edit">Edit harga</a><?php endif; ?></div></div> <?php $__env->endSlot(); ?>
    <div class="py-8"><div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <p class="mb-4 text-sm text-gray-600">Harga terkini: Rp <?php echo e(number_format($goldPrice->harga_per_gram, 2, ',', '.')); ?> per gram, sejak <?php echo e($goldPrice->tanggal->format('d/m/Y')); ?>.</p>
        <div class="overflow-x-auto bg-white shadow-sm sm:rounded-lg"><table class="data-table min-w-full text-sm"><thead><tr><th class="px-4 py-3 text-left">Tanggal berlaku</th><th class="px-4 py-3 text-right">Harga/gram</th><th class="px-4 py-3 text-left">Keterangan</th></tr></thead><tbody>
            <?php $__currentLoopData = $history; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><tr><td class="px-4 py-3"><?php echo e($item->tanggal->format('d/m/Y')); ?> <?php if($item->id === $goldPrice->id): ?><span class="text-green-700">(terkini)</span><?php endif; ?></td><td class="px-4 py-3 text-right">Rp <?php echo e(number_format($item->harga_per_gram, 2, ',', '.')); ?></td><td class="px-4 py-3"><?php echo e($item->keterangan); ?></td></tr><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </tbody></table></div><div class="mt-4 flex flex-wrap items-center justify-between gap-3"><?php if (isset($component)) { $__componentOriginal720c5d99204acad589a79c73de989541 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal720c5d99204acad589a79c73de989541 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.per-page-selector','data' => ['paginator' => $history]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('per-page-selector'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['paginator' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($history)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal720c5d99204acad589a79c73de989541)): ?>
<?php $attributes = $__attributesOriginal720c5d99204acad589a79c73de989541; ?>
<?php unset($__attributesOriginal720c5d99204acad589a79c73de989541); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal720c5d99204acad589a79c73de989541)): ?>
<?php $component = $__componentOriginal720c5d99204acad589a79c73de989541; ?>
<?php unset($__componentOriginal720c5d99204acad589a79c73de989541); ?>
<?php endif; ?><?php echo e($history->links()); ?></div>
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
<?php /**PATH D:\project\toko-emas-wijaya-kusuma\resources\views\harga-emas\show.blade.php ENDPATH**/ ?>