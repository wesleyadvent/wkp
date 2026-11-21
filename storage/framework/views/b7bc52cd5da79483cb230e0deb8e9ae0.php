<nav role="navigation" aria-label="Navigasi halaman" class="flex flex-wrap items-center justify-between gap-3 text-sm">
    <?php if (isset($component)) { $__componentOriginal720c5d99204acad589a79c73de989541 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal720c5d99204acad589a79c73de989541 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.per-page-selector','data' => ['paginator' => $paginator]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('per-page-selector'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['paginator' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($paginator)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal720c5d99204acad589a79c73de989541)): ?>
<?php $attributes = $__attributesOriginal720c5d99204acad589a79c73de989541; ?>
<?php unset($__attributesOriginal720c5d99204acad589a79c73de989541); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal720c5d99204acad589a79c73de989541)): ?>
<?php $component = $__componentOriginal720c5d99204acad589a79c73de989541; ?>
<?php unset($__componentOriginal720c5d99204acad589a79c73de989541); ?>
<?php endif; ?>

    <div class="flex flex-wrap items-center gap-3">
        <p class="text-gray-600">
            <?php if($paginator->firstItem()): ?>
                Menampilkan <span class="font-medium"><?php echo e($paginator->firstItem()); ?>–<?php echo e($paginator->lastItem()); ?></span> dari <span class="font-medium"><?php echo e($paginator->total()); ?></span> data
            <?php else: ?>
                Tidak ada data
            <?php endif; ?>
        </p>

        <?php if($paginator->hasPages()): ?>
            <span class="inline-flex rounded-md shadow-sm">
                <?php if($paginator->onFirstPage()): ?>
                    <span class="inline-flex items-center rounded-l-md border border-gray-300 bg-gray-50 px-3 py-2 text-gray-400">Sebelumnya</span>
                <?php else: ?>
                    <a href="<?php echo e($paginator->previousPageUrl()); ?>" rel="prev" class="inline-flex items-center rounded-l-md border border-gray-300 bg-white px-3 py-2 text-gray-700 hover:bg-gray-50">Sebelumnya</a>
                <?php endif; ?>

                <?php $__currentLoopData = $elements; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $element): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php if(is_string($element)): ?>
                        <span class="inline-flex items-center border-y border-gray-300 bg-white px-3 py-2 text-gray-500"><?php echo e($element); ?></span>
                    <?php endif; ?>
                    <?php if(is_array($element)): ?>
                        <?php $__currentLoopData = $element; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $page => $url): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <?php if($page === $paginator->currentPage()): ?>
                                <span aria-current="page" class="inline-flex items-center border-y border-gray-300 bg-amber-50 px-3 py-2 font-semibold text-amber-700"><?php echo e($page); ?></span>
                            <?php else: ?>
                                <a href="<?php echo e($url); ?>" class="inline-flex items-center border-y border-gray-300 bg-white px-3 py-2 text-gray-700 hover:bg-gray-50"><?php echo e($page); ?></a>
                            <?php endif; ?>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    <?php endif; ?>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

                <?php if($paginator->hasMorePages()): ?>
                    <a href="<?php echo e($paginator->nextPageUrl()); ?>" rel="next" class="inline-flex items-center rounded-r-md border border-gray-300 bg-white px-3 py-2 text-gray-700 hover:bg-gray-50">Berikutnya</a>
                <?php else: ?>
                    <span class="inline-flex items-center rounded-r-md border border-gray-300 bg-gray-50 px-3 py-2 text-gray-400">Berikutnya</span>
                <?php endif; ?>
            </span>
        <?php endif; ?>
    </div>
</nav>
<?php /**PATH D:\project\toko-emas-wijaya-kusuma\resources\views\pagination\default.blade.php ENDPATH**/ ?>