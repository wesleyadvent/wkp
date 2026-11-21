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
     <?php $__env->slot('header', null, []); ?> <div class="flex justify-between"><h2 class="font-semibold text-xl text-gray-800">Nota Service</h2><a class="rounded bg-gray-900 px-4 py-2 text-sm text-white" href="<?php echo e(route('service-notes.create')); ?>">Buat Nota</a></div> <?php $__env->endSlot(); ?>
    <div class="py-8"><div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">
        <?php if(session('status')): ?><div class="rounded border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800"><?php echo e(session('status')); ?></div><?php endif; ?>
        <form method="GET" class="flex flex-wrap gap-3 bg-white p-4 shadow-sm sm:rounded-lg"><label class="flex-1 min-w-[220px]"><span class="sr-only">Cari nota, customer, atau barang</span><?php if (isset($component)) { $__componentOriginal18c21970322f9e5c938bc954620c12bb = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal18c21970322f9e5c938bc954620c12bb = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => '92edfda80dc3faed2cda654b45151250::text-input','data' => ['type' => 'search','name' => 'q','value' => request('q'),'placeholder' => 'Cari nota, customer, atau barang…','class' => 'w-full']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('text-input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['type' => 'search','name' => 'q','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(request('q')),'placeholder' => 'Cari nota, customer, atau barang…','class' => 'w-full']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal18c21970322f9e5c938bc954620c12bb)): ?>
<?php $attributes = $__attributesOriginal18c21970322f9e5c938bc954620c12bb; ?>
<?php unset($__attributesOriginal18c21970322f9e5c938bc954620c12bb); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal18c21970322f9e5c938bc954620c12bb)): ?>
<?php $component = $__componentOriginal18c21970322f9e5c938bc954620c12bb; ?>
<?php unset($__componentOriginal18c21970322f9e5c938bc954620c12bb); ?>
<?php endif; ?></label><?php if (isset($component)) { $__componentOriginal18c21970322f9e5c938bc954620c12bb = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal18c21970322f9e5c938bc954620c12bb = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => '92edfda80dc3faed2cda654b45151250::text-input','data' => ['ariaLabel' => 'Tanggal transaksi','type' => 'date','name' => 'date','value' => $selectedDate]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('text-input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['aria-label' => 'Tanggal transaksi','type' => 'date','name' => 'date','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($selectedDate)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal18c21970322f9e5c938bc954620c12bb)): ?>
<?php $attributes = $__attributesOriginal18c21970322f9e5c938bc954620c12bb; ?>
<?php unset($__attributesOriginal18c21970322f9e5c938bc954620c12bb); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal18c21970322f9e5c938bc954620c12bb)): ?>
<?php $component = $__componentOriginal18c21970322f9e5c938bc954620c12bb; ?>
<?php unset($__componentOriginal18c21970322f9e5c938bc954620c12bb); ?>
<?php endif; ?><?php if(Auth::user()->isAdmin()): ?><select name="branch_id" class="rounded-md border-gray-300"><option value="">Semua cabang</option><?php $__currentLoopData = $branches; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $branch): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($branch->id); ?>" <?php if((int)request('branch_id')===$branch->id): echo 'selected'; endif; ?>><?php echo e($branch->nama_cabang); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></select><?php endif; ?><?php if (isset($component)) { $__componentOriginald411d1792bd6cc877d687758b753742c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald411d1792bd6cc877d687758b753742c = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => '92edfda80dc3faed2cda654b45151250::primary-button','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('primary-button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>Cari / Filter <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginald411d1792bd6cc877d687758b753742c)): ?>
<?php $attributes = $__attributesOriginald411d1792bd6cc877d687758b753742c; ?>
<?php unset($__attributesOriginald411d1792bd6cc877d687758b753742c); ?>
<?php endif; ?>
<?php if (isset($__componentOriginald411d1792bd6cc877d687758b753742c)): ?>
<?php $component = $__componentOriginald411d1792bd6cc877d687758b753742c; ?>
<?php unset($__componentOriginald411d1792bd6cc877d687758b753742c); ?>
<?php endif; ?></form>
        <div class="overflow-x-auto bg-white shadow-sm sm:rounded-lg"><div class="table-scroll" role="region" aria-label="Tabel data" tabindex="0"><table class="data-table min-w-full divide-y divide-gray-200 text-sm"><thead class="bg-gray-50 text-left text-xs uppercase text-gray-500"><tr><th scope="col" class="px-4 py-3">Nota</th><th scope="col" class="px-4 py-3">Tanggal</th><th scope="col" class="px-4 py-3">Cabang</th><th scope="col" class="px-4 py-3">Customer</th><th scope="col" class="px-4 py-3">Service</th><th scope="col" class="px-4 py-3">Status</th><th scope="col" class="px-4 py-3">Pembuat nota</th><th scope="col" class="text-right">Aksi</th></tr></thead><tbody class="divide-y divide-gray-100"><?php $__empty_1 = true; $__currentLoopData = $notes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $note): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><tr><td class="px-4 py-3 font-medium"><?php echo e($note->nomor_nota); ?></td><td class="px-4 py-3"><?php echo e($note->tanggal_masuk?->format('d/m/Y')); ?></td><td class="px-4 py-3"><?php echo e($note->receipt('branch')?->nama_cabang); ?></td><td class="px-4 py-3"><?php echo e($note->receipt('customer')?->nama); ?></td><td class="px-4 py-3 table-number">Rp <?php echo e(number_format($note->harga_service, 2,',','.')); ?></td><td class="px-4 py-3"><?php if (isset($component)) { $__componentOriginal8c81617a70e11bcf247c4db924ab1b62 = $component; } ?>
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
<?php endif; ?></td><td class="px-4 py-3"><?php echo e($note->employee_name ?? 'Belum tercatat'); ?></td><td class="px-4 py-3 text-right"><a href="<?php echo e(route('service-notes.show',$note)); ?>" class="action-button action-detail">Detail</a></td></tr><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><tr><td colspan="8" class="px-4 py-8 text-center text-gray-500">Belum ada nota service.</td></tr><?php endif; ?></tbody></table></div></div><div class="flex flex-wrap items-center justify-between gap-3"><?php if (isset($component)) { $__componentOriginal720c5d99204acad589a79c73de989541 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal720c5d99204acad589a79c73de989541 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.per-page-selector','data' => ['paginator' => $notes]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('per-page-selector'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['paginator' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($notes)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal720c5d99204acad589a79c73de989541)): ?>
<?php $attributes = $__attributesOriginal720c5d99204acad589a79c73de989541; ?>
<?php unset($__attributesOriginal720c5d99204acad589a79c73de989541); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal720c5d99204acad589a79c73de989541)): ?>
<?php $component = $__componentOriginal720c5d99204acad589a79c73de989541; ?>
<?php unset($__componentOriginal720c5d99204acad589a79c73de989541); ?>
<?php endif; ?><?php echo e($notes->links()); ?></div>
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
<?php /**PATH D:\project\toko-emas-wijaya-kusuma\resources\views\nota-layanan\index.blade.php ENDPATH**/ ?>