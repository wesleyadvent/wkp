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
     <?php $__env->slot('header', null, []); ?> <div class="flex justify-between"><h2 class="font-semibold text-xl text-gray-800">Cabang</h2><a class="rounded bg-gray-900 px-4 py-2 text-sm text-white" href="<?php echo e(route('branches.create')); ?>">Tambah Cabang</a></div> <?php $__env->endSlot(); ?>
    <div class="py-8"><div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">
        <?php if(session('status')): ?><div class="rounded border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800"><?php echo e(session('status')); ?></div><?php endif; ?>
        <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg"><div class="table-scroll" role="region" aria-label="Tabel data" tabindex="0"><table class="data-table min-w-full divide-y divide-gray-200 text-sm"><thead class="bg-gray-50 text-left text-xs uppercase text-gray-500"><tr><th scope="col" class="px-4 py-3">Kode</th><th scope="col" class="px-4 py-3">Cabang</th><th scope="col" class="px-4 py-3">Telepon</th><th scope="col" class="px-4 py-3">Login</th><th scope="col" class="text-right">Aksi</th></tr></thead><tbody class="divide-y divide-gray-100"><?php $__empty_1 = true; $__currentLoopData = $branches; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $branch): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><tr><td class="px-4 py-3 font-medium"><?php echo e($branch->kode_cabang); ?></td><td class="px-4 py-3"><?php echo e($branch->nama_cabang); ?></td><td class="px-4 py-3"><?php echo e($branch->telepon); ?></td><td class="px-4 py-3"><?php if (isset($component)) { $__componentOriginal8c81617a70e11bcf247c4db924ab1b62 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal8c81617a70e11bcf247c4db924ab1b62 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => '92edfda80dc3faed2cda654b45151250::status-badge','data' => ['value' => $branch->is_login_enabled]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('status-badge'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($branch->is_login_enabled)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal8c81617a70e11bcf247c4db924ab1b62)): ?>
<?php $attributes = $__attributesOriginal8c81617a70e11bcf247c4db924ab1b62; ?>
<?php unset($__attributesOriginal8c81617a70e11bcf247c4db924ab1b62); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal8c81617a70e11bcf247c4db924ab1b62)): ?>
<?php $component = $__componentOriginal8c81617a70e11bcf247c4db924ab1b62; ?>
<?php unset($__componentOriginal8c81617a70e11bcf247c4db924ab1b62); ?>
<?php endif; ?></td><td class="px-4 py-3 text-right"><a href="<?php echo e(route('branches.edit',$branch)); ?>" class="action-button action-edit">Edit &amp; Atur Login</a></td></tr><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><tr><td colspan="5">Belum ada data yang dapat ditampilkan.</td></tr><?php endif; ?></tbody></table></div></div><div class="flex flex-wrap items-center justify-between gap-3"><?php if (isset($component)) { $__componentOriginal720c5d99204acad589a79c73de989541 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal720c5d99204acad589a79c73de989541 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.per-page-selector','data' => ['paginator' => $branches]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('per-page-selector'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['paginator' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($branches)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal720c5d99204acad589a79c73de989541)): ?>
<?php $attributes = $__attributesOriginal720c5d99204acad589a79c73de989541; ?>
<?php unset($__attributesOriginal720c5d99204acad589a79c73de989541); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal720c5d99204acad589a79c73de989541)): ?>
<?php $component = $__componentOriginal720c5d99204acad589a79c73de989541; ?>
<?php unset($__componentOriginal720c5d99204acad589a79c73de989541); ?>
<?php endif; ?><?php echo e($branches->links()); ?></div>
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
<?php /**PATH D:\project\toko-emas-wijaya-kusuma\resources\views\cabang\index.blade.php ENDPATH**/ ?>