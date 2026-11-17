<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames((['paginator', 'label' => 'Tampilkan']));

foreach ($attributes->all() as $__key => $__value) {
    if (in_array($__key, $__propNames)) {
        $$__key = $$__key ?? $__value;
    } else {
        $__newAttributes[$__key] = $__value;
    }
}

$attributes = new \Illuminate\View\ComponentAttributeBag($__newAttributes);

unset($__propNames);
unset($__newAttributes);

foreach (array_filter((['paginator', 'label' => 'Tampilkan']), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<?php if (! $__env->hasRenderedOnce('87f957ac-328e-4b16-b5cd-deecbf3e9196')): $__env->markAsRenderedOnce('87f957ac-328e-4b16-b5cd-deecbf3e9196'); ?>
    <form method="GET" class="inline-flex items-center gap-2 text-sm text-gray-600">
        <?php $__currentLoopData = request()->except(['page', 'per_page']); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $name => $value): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <?php if(is_array($value)): ?>
                <?php $__currentLoopData = $value; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $arrayValue): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <input type="hidden" name="<?php echo e($name); ?>[]" value="<?php echo e($arrayValue); ?>">
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            <?php else: ?>
                <input type="hidden" name="<?php echo e($name); ?>" value="<?php echo e($value); ?>">
            <?php endif; ?>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        <label for="per-page" class="whitespace-nowrap"><?php echo e($label); ?></label>
        <select id="per-page" name="per_page" class="rounded-md border-gray-300 py-1.5 text-sm" onchange="this.form.submit()">
            <?php $__currentLoopData = [10, 15, 20, 25, 50, 100]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $option): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <option value="<?php echo e($option); ?>" <?php if((int) request('per_page', $paginator->perPage()) === $option): echo 'selected'; endif; ?>><?php echo e($option); ?></option>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </select>
        <span>data</span>
    </form>
<?php endif; ?>
<?php /**PATH D:\project\toko-emas-wijaya-kusuma\resources\views/components/per-page-selector.blade.php ENDPATH**/ ?>