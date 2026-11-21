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
        <h2 class="text-xl font-semibold leading-tight text-gray-800">Perbandingan Akurasi Prediksi</h2>
        <p class="mt-1 text-sm text-gray-500">SMA, WMA, ARIMA dan LightGBM dinilai terhadap penjualan aktual pada observasi uji yang sama.</p>
     <?php $__env->endSlot(); ?>

    <?php
        $metricLabels = ['mae' => 'MAE', 'rmse' => 'RMSE', 'mape' => 'MAPE'];
        $format = fn ($value, $places = 3) => $value === null ? '—' : number_format($value, $places, ',', '.');
        $metric = $filters['metric'];
    ?>

    <div class="py-8">
        <div class="mx-auto max-w-screen-2xl space-y-5 sm:px-6 lg:px-8">
            <form method="GET" action="<?php echo e(route('forecast-comparison.index')); ?>" class="grid items-end gap-4 rounded-xl bg-white p-5 shadow-sm md:grid-cols-3 xl:grid-cols-4">
                <div>
                    <?php if (isset($component)) { $__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => '92edfda80dc3faed2cda654b45151250::input-label','data' => ['for' => 'horizon','value' => 'Horizon prediksi']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('input-label'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['for' => 'horizon','value' => 'Horizon prediksi']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581)): ?>
<?php $attributes = $__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581; ?>
<?php unset($__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581); ?>
<?php endif; ?>
<?php if (isset($__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581)): ?>
<?php $component = $__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581; ?>
<?php unset($__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581); ?>
<?php endif; ?>
                    <select id="horizon" name="horizon" class="mt-1 block w-full rounded-md border-gray-300">
                        <?php $__currentLoopData = \App\Services\ForecastComparisonService::HORIZONS; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $days): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($days); ?>" <?php if($filters['horizon'] === $days): echo 'selected'; endif; ?>><?php echo e($days); ?> hari<?php echo e(in_array($days, \App\Services\ForecastComparisonService::LEAD_TIME_HORIZONS, true) ? ' · Lead time' : ' · Proyeksi'); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                </div>
                <div>
                    <?php if (isset($component)) { $__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => '92edfda80dc3faed2cda654b45151250::input-label','data' => ['for' => 'branch_id','value' => 'Cabang']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('input-label'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['for' => 'branch_id','value' => 'Cabang']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581)): ?>
<?php $attributes = $__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581; ?>
<?php unset($__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581); ?>
<?php endif; ?>
<?php if (isset($__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581)): ?>
<?php $component = $__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581; ?>
<?php unset($__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581); ?>
<?php endif; ?>
                    <?php if(Auth::user()->isAdmin()): ?>
                        <select id="branch_id" name="branch_id" class="mt-1 block w-full rounded-md border-gray-300">
                            <option value="">Seluruh cabang</option>
                            <?php $__currentLoopData = $branches; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $branch): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($branch->id); ?>" <?php if($selectedBranchId === $branch->id): echo 'selected'; endif; ?>><?php echo e($branch->nama_cabang); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    <?php else: ?>
                        <div class="mt-1 rounded-md border border-gray-200 bg-gray-50 px-3 py-2 text-sm"><?php echo e($branchName); ?></div>
                    <?php endif; ?>
                </div>
                <div>
                    <?php if (isset($component)) { $__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => '92edfda80dc3faed2cda654b45151250::input-label','data' => ['for' => 'metric','value' => 'Urutkan metode berdasarkan']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('input-label'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['for' => 'metric','value' => 'Urutkan metode berdasarkan']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581)): ?>
<?php $attributes = $__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581; ?>
<?php unset($__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581); ?>
<?php endif; ?>
<?php if (isset($__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581)): ?>
<?php $component = $__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581; ?>
<?php unset($__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581); ?>
<?php endif; ?>
                    <select id="metric" name="metric" class="mt-1 block w-full rounded-md border-gray-300">
                        <?php $__currentLoopData = $metricLabels; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($key); ?>" <?php if($metric === $key): echo 'selected'; endif; ?>><?php echo e($label); ?> terendah</option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                </div>
                <div>
                    <?php if (isset($component)) { $__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => '92edfda80dc3faed2cda654b45151250::input-label','data' => ['for' => 'category','value' => 'Kategori barang']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('input-label'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['for' => 'category','value' => 'Kategori barang']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581)): ?>
<?php $attributes = $__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581; ?>
<?php unset($__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581); ?>
<?php endif; ?>
<?php if (isset($__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581)): ?>
<?php $component = $__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581; ?>
<?php unset($__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581); ?>
<?php endif; ?>
                    <select id="category" name="category" class="mt-1 block w-full rounded-md border-gray-300">
                        <option value="">Seluruh kategori</option>
                        <?php $__currentLoopData = ['ANTING', 'CINCIN', 'GELANG', 'KALUNG', 'LIONTIN']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $category): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($category); ?>" <?php if($filters['category'] === $category): echo 'selected'; endif; ?>><?php echo e(ucfirst(strtolower($category))); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                </div>
                <div>
                    <?php if (isset($component)) { $__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => '92edfda80dc3faed2cda654b45151250::input-label','data' => ['for' => 'from','value' => 'Tanggal potong mulai']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('input-label'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['for' => 'from','value' => 'Tanggal potong mulai']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581)): ?>
<?php $attributes = $__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581; ?>
<?php unset($__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581); ?>
<?php endif; ?>
<?php if (isset($__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581)): ?>
<?php $component = $__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581; ?>
<?php unset($__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581); ?>
<?php endif; ?>
                    <?php if (isset($component)) { $__componentOriginal18c21970322f9e5c938bc954620c12bb = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal18c21970322f9e5c938bc954620c12bb = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => '92edfda80dc3faed2cda654b45151250::text-input','data' => ['type' => 'date','id' => 'from','name' => 'from','value' => $filters['from'],'class' => 'mt-1 block w-full']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('text-input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['type' => 'date','id' => 'from','name' => 'from','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($filters['from']),'class' => 'mt-1 block w-full']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal18c21970322f9e5c938bc954620c12bb)): ?>
<?php $attributes = $__attributesOriginal18c21970322f9e5c938bc954620c12bb; ?>
<?php unset($__attributesOriginal18c21970322f9e5c938bc954620c12bb); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal18c21970322f9e5c938bc954620c12bb)): ?>
<?php $component = $__componentOriginal18c21970322f9e5c938bc954620c12bb; ?>
<?php unset($__componentOriginal18c21970322f9e5c938bc954620c12bb); ?>
<?php endif; ?>
                </div>
                <div>
                    <?php if (isset($component)) { $__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => '92edfda80dc3faed2cda654b45151250::input-label','data' => ['for' => 'to','value' => 'Tanggal potong akhir']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('input-label'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['for' => 'to','value' => 'Tanggal potong akhir']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581)): ?>
<?php $attributes = $__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581; ?>
<?php unset($__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581); ?>
<?php endif; ?>
<?php if (isset($__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581)): ?>
<?php $component = $__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581; ?>
<?php unset($__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581); ?>
<?php endif; ?>
                    <?php if (isset($component)) { $__componentOriginal18c21970322f9e5c938bc954620c12bb = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal18c21970322f9e5c938bc954620c12bb = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => '92edfda80dc3faed2cda654b45151250::text-input','data' => ['type' => 'date','id' => 'to','name' => 'to','value' => $filters['to'],'class' => 'mt-1 block w-full']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('text-input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['type' => 'date','id' => 'to','name' => 'to','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($filters['to']),'class' => 'mt-1 block w-full']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal18c21970322f9e5c938bc954620c12bb)): ?>
<?php $attributes = $__attributesOriginal18c21970322f9e5c938bc954620c12bb; ?>
<?php unset($__attributesOriginal18c21970322f9e5c938bc954620c12bb); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal18c21970322f9e5c938bc954620c12bb)): ?>
<?php $component = $__componentOriginal18c21970322f9e5c938bc954620c12bb; ?>
<?php unset($__componentOriginal18c21970322f9e5c938bc954620c12bb); ?>
<?php endif; ?>
                </div>
                <div>
                    <?php if (isset($component)) { $__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => '92edfda80dc3faed2cda654b45151250::input-label','data' => ['for' => 'karat','value' => 'Kadar']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('input-label'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['for' => 'karat','value' => 'Kadar']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581)): ?>
<?php $attributes = $__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581; ?>
<?php unset($__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581); ?>
<?php endif; ?>
<?php if (isset($__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581)): ?>
<?php $component = $__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581; ?>
<?php unset($__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581); ?>
<?php endif; ?>
                    <select id="karat" name="karat" class="mt-1 block w-full rounded-md border-gray-300">
                        <option value="">Seluruh kadar</option>
                        <?php $__currentLoopData = range(8, 24); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $karat): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($karat); ?>" <?php if($filters['karat'] === $karat): echo 'selected'; endif; ?>><?php echo e($karat); ?>K</option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                </div>
                <div>
                    <?php if (isset($component)) { $__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => '92edfda80dc3faed2cda654b45151250::input-label','data' => ['for' => 'q','value' => 'Cari kelompok']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('input-label'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['for' => 'q','value' => 'Cari kelompok']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581)): ?>
<?php $attributes = $__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581; ?>
<?php unset($__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581); ?>
<?php endif; ?>
<?php if (isset($__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581)): ?>
<?php $component = $__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581; ?>
<?php unset($__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581); ?>
<?php endif; ?>
                    <?php if (isset($component)) { $__componentOriginal18c21970322f9e5c938bc954620c12bb = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal18c21970322f9e5c938bc954620c12bb = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => '92edfda80dc3faed2cda654b45151250::text-input','data' => ['id' => 'q','name' => 'q','value' => $filters['q'],'placeholder' => 'Cabang, kategori, atau kadar','class' => 'mt-1 block w-full']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('text-input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['id' => 'q','name' => 'q','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($filters['q']),'placeholder' => 'Cabang, kategori, atau kadar','class' => 'mt-1 block w-full']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal18c21970322f9e5c938bc954620c12bb)): ?>
<?php $attributes = $__attributesOriginal18c21970322f9e5c938bc954620c12bb; ?>
<?php unset($__attributesOriginal18c21970322f9e5c938bc954620c12bb); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal18c21970322f9e5c938bc954620c12bb)): ?>
<?php $component = $__componentOriginal18c21970322f9e5c938bc954620c12bb; ?>
<?php unset($__componentOriginal18c21970322f9e5c938bc954620c12bb); ?>
<?php endif; ?>
                </div>
                <div class="flex gap-2 md:col-span-3 xl:col-span-4">
                    <?php if (isset($component)) { $__componentOriginald411d1792bd6cc877d687758b753742c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald411d1792bd6cc877d687758b753742c = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => '92edfda80dc3faed2cda654b45151250::primary-button','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('primary-button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>Bandingkan <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginald411d1792bd6cc877d687758b753742c)): ?>
<?php $attributes = $__attributesOriginald411d1792bd6cc877d687758b753742c; ?>
<?php unset($__attributesOriginald411d1792bd6cc877d687758b753742c); ?>
<?php endif; ?>
<?php if (isset($__componentOriginald411d1792bd6cc877d687758b753742c)): ?>
<?php $component = $__componentOriginald411d1792bd6cc877d687758b753742c; ?>
<?php unset($__componentOriginald411d1792bd6cc877d687758b753742c); ?>
<?php endif; ?>
                    <a href="<?php echo e(route('forecast-comparison.index')); ?>" class="inline-flex items-center rounded-md border border-gray-300 px-3 py-2 text-sm text-gray-600">Reset</a>
                </div>
                <?php if($errors->any()): ?>
                    <div class="text-sm text-rose-700 md:col-span-3 xl:col-span-4"><?php echo e($errors->first()); ?></div>
                <?php endif; ?>
            </form>

            <?php if($error): ?>
                <p class="rounded-lg border border-amber-200 bg-amber-50 p-5 text-sm text-amber-900"><?php echo e($error); ?></p>
            <?php elseif($result['count'] === 0): ?>
                <div class="rounded-xl border border-gray-200 bg-white p-8 text-center">
                    <p class="font-semibold text-gray-800">Belum ada observasi uji yang cocok.</p>
                    <p class="mt-2 text-sm text-gray-500">Sesuaikan cabang, kelompok, atau tanggal potong. Hasil pengujian tersedia pada periode historis yang tercantum di bawah.</p>
                </div>
            <?php else: ?>
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-5">
                    <?php if($result['winners']): ?>
                        <p class="text-xs font-semibold uppercase tracking-wide text-emerald-700"><?php echo e(count($result['winners']) > 1 ? 'Peringkat pertama bersama' : 'Metode paling akurat'); ?> berdasarkan <?php echo e($metricLabels[$metric]); ?></p>
                        <p class="mt-2 text-2xl font-bold text-emerald-900"><?php echo e(implode(' & ', $result['winners'])); ?></p>
                        <p class="mt-1 text-sm text-emerald-800"><?php echo e($metricLabels[$metric]); ?> <?php echo e($format($result['best'][$metric])); ?><?php echo e($metric === 'mape' ? '%' : ' unit'); ?> pada <?php echo e(number_format($result['count'], 0, ',', '.')); ?> observasi uji dengan horizon <?php echo e($filters['horizon']); ?> hari.</p>
                    <?php else: ?>
                        <p class="font-semibold text-emerald-900">Peringkat MAPE belum dapat ditentukan.</p>
                        <p class="mt-1 text-sm text-emerald-800">Semua aktual pada pilihan ini bernilai nol. Pilih MAE atau RMSE untuk membandingkan metode.</p>
                    <?php endif; ?>
                    <p class="mt-3 text-xs text-emerald-800">Hasil berlaku untuk pilihan data ini. Semakin kecil MAE, RMSE, atau MAPE, semakin kecil galat prediksinya.</p>
                </div>

                <div class="grid gap-4 sm:grid-cols-3">
                    <?php $__currentLoopData = $metricLabels; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                            <p class="text-sm text-gray-500"><?php echo e($label); ?> terendah</p>
                            <p class="mt-2 text-2xl font-bold text-slate-900"><?php echo e($format($result['best'][$key])); ?><span class="ml-1 text-sm font-normal text-gray-500"><?php echo e($key === 'mape' ? '%' : 'unit'); ?></span></p>
                            <p class="mt-2 text-sm font-medium text-slate-600"><?php echo e(implode(' & ', $result['metric_winners'][$key]) ?: 'Tidak terdefinisi'); ?></p>
                        </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>

                <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                    <div class="border-b border-gray-100 px-5 py-4">
                        <h3 class="font-semibold text-gray-900">Peringkat metode</h3>
                        <p class="mt-1 text-xs text-gray-500"><?php echo e(number_format($result['group_count'], 0, ',', '.')); ?> kelompok · <?php echo e($result['date_count']); ?> tanggal potong · <?php echo e($result['first_cutoff']); ?> s.d. <?php echo e($result['last_cutoff']); ?> · Aktual sampai <?php echo e($result['target_end']); ?></p>
                    </div>
                    <div class="table-scroll">
                        <table class="data-table w-full text-sm">
                            <thead><tr><th>Peringkat</th><th>Metode &amp; konfigurasi uji</th><th class="text-right">MAE (unit)</th><th class="text-right">RMSE (unit)</th><th class="text-right">MAPE (%)</th><th class="text-right">Observasi</th></tr></thead>
                            <tbody>
                                <?php $__currentLoopData = $result['rankings']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <tr class="<?php echo e($row['rank'] === 1 ? 'bg-emerald-50' : ''); ?>">
                                        <td><span class="font-semibold text-gray-800"><?php echo e($row['rank'] ?? '—'); ?></span></td>
                                        <td><span class="font-semibold text-gray-900"><?php echo e($row['method']); ?></span><span class="table-secondary"><?php echo e($row['variant']); ?></span></td>
                                        <?php $__currentLoopData = ['mae', 'rmse', 'mape']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <td class="text-right tabular-nums <?php echo e(in_array($row['method'], $result['metric_winners'][$key], true) ? 'font-bold text-emerald-700' : ''); ?>"><?php echo e($format($row[$key])); ?></td>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                        <td class="text-right tabular-nums"><?php echo e(number_format($row['n'], 0, ',', '.')); ?></td>
                                    </tr>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </tbody>
                        </table>
                    </div>
                    <p class="border-t border-gray-100 px-5 py-4 text-xs text-gray-500">MAPE dihitung pada <?php echo e(number_format($result['mape_n'], 0, ',', '.')); ?> observasi dengan aktual &gt; 0. <?php echo e(number_format($result['zero_actual'], 0, ',', '.')); ?> observasi aktual nol tetap masuk MAE/RMSE dan dikecualikan dari MAPE. Metrik memakai angka asli sebelum pembulatan.</p>
                    <?php if($result['excluded']): ?>
                        <p class="px-5 pb-4 text-xs text-amber-700"><?php echo e($result['excluded']); ?> observasi tidak lengkap dikeluarkan untuk seluruh metode agar cakupannya sama.</p>
                    <?php endif; ?>
                </div>

                <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                    <h3 class="font-semibold text-gray-900">Galat <?php echo e($metricLabels[$metric]); ?> per metode</h3>
                    <p class="mt-1 text-xs text-gray-500">Batang lebih pendek menunjukkan galat lebih kecil.</p>
                    <?php $maximum = max(array_map(fn ($row) => $row[$metric] ?? 0, $result['rankings'])); ?>
                    <div class="mt-5 space-y-4">
                        <?php $__currentLoopData = $result['rankings']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <div class="grid grid-cols-4 items-center gap-3 text-sm">
                                <span class="font-medium text-slate-700"><?php echo e($row['method']); ?></span>
                                <div class="col-span-2 h-3 overflow-hidden rounded-full bg-slate-100"><div class="h-full rounded-full <?php echo e($row['rank'] === 1 ? 'bg-emerald-500' : 'bg-slate-400'); ?>" style="width: <?php echo e($maximum > 0 ? 100 * ($row[$metric] ?? 0) / $maximum : 0); ?>%"></div></div>
                                <span class="text-right tabular-nums text-slate-700"><?php echo e($format($row[$metric])); ?><?php echo e($metric === 'mape' && $row[$metric] !== null ? '%' : ''); ?></span>
                            </div>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </div>
                </div>

                <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                    <div class="border-b border-gray-100 px-5 py-4"><h3 class="font-semibold text-gray-900">Prediksi dan penjualan aktual</h3><p class="mt-1 text-xs text-gray-500">Satu observasi adalah satu kelompok dan tanggal potong. Aktual menjumlahkan unit terjual dari hari berikutnya sampai hari ke-<?php echo e($filters['horizon']); ?>.</p></div>
                    <div class="table-scroll">
                        <table class="data-table w-full text-sm">
                            <thead><tr><th>Kelompok barang</th><th>Tanggal potong &amp; target</th><th class="text-right">Aktual</th><?php $__currentLoopData = \App\Services\ForecastComparisonService::METHODS; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $method): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><th class="text-right"><?php echo e($method); ?></th><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></tr></thead>
                            <tbody>
                                <?php $__empty_1 = true; $__currentLoopData = $details; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                    <tr>
                                        <td><span class="font-semibold"><?php echo e(ucfirst(strtolower($row['category']))); ?> · <?php echo e($row['karat']); ?>K</span><span class="table-secondary"><?php echo e($row['branch']); ?></span></td>
                                        <td><?php echo e($row['cutoff']); ?><span class="table-secondary"><?php echo e($row['target_start']); ?> s.d. <?php echo e($row['target_end']); ?></span></td>
                                        <td class="text-right font-bold tabular-nums"><?php echo e($format($row['actual'], 0)); ?></td>
                                        <?php $__currentLoopData = $row['predictions']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $prediction): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <td class="text-right tabular-nums" title="Galat absolut <?php echo e($format(abs($prediction - $row['actual']))); ?> unit"><?php echo e($format($prediction, 0)); ?></td>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    </tr>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                    <tr><td colspan="7" class="py-6 text-center text-gray-500">Tidak ada observasi pada halaman ini.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="border-t border-gray-100 px-5 py-4"><?php echo e($details->links()); ?></div>
                </div>
            <?php endif; ?>

            <details class="rounded-xl border border-gray-200 bg-white p-5 text-sm text-gray-600" open>
                <summary class="cursor-pointer font-semibold text-gray-900">Dasar penilaian dan sumber data</summary>
                <div class="mt-4 space-y-3 leading-relaxed">
                    <p>Evaluasi historis mengikuti notebook Colab pada data transaksi <strong>sintetis</strong>. Parameter SMA/WMA, orde ARIMA dan varian LightGBM dipilih pada validasi, kemudian dinilai pada data uji Oktober 2025–Juli 2026. Jumlah kasus dan tanggal uji bergantung pada horizon agar target selesai dalam periode uji.</p>
                    <p>Semua metode memakai kelompok, tanggal potong dan aktual yang sama. MAE/RMSE hasil rekonstruksi diperiksa terhadap laporan Colab asli. Halaman membaca hasil evaluasi tersimpan, sehingga tetap dapat dibuka ketika Flask dimatikan.</p>
                    <p>Jendela SMA/WMA yang digunakan tercantum pada tabel. ARIMA mengikuti perhitungan notebook Colab; implementasi ARIMA operasional memakai batch statsmodels dengan aturan estimasi tersendiri. Peringkat ini berlaku untuk konfigurasi uji yang ditampilkan.</p>
                    <p><strong>MAE</strong> = rata-rata |prediksi − aktual|. <strong>RMSE</strong> = akar rata-rata (prediksi − aktual)². <strong>MAPE</strong> = rata-rata |prediksi − aktual| ÷ aktual × 100%, hanya untuk aktual &gt; 0. Lihat <a class="text-amber-700 underline" href="https://otexts.com/fpp3/accuracy.html" target="_blank" rel="noopener noreferrer">rujukan pengukuran akurasi</a>.</p>
                    <?php if($metadata): ?>
                        <p class="text-xs text-gray-500">Hasil dibangun <?php echo e($metadata['generated_at'] ?? '—'); ?>. <?php echo e(number_format($metadata['total_rows'] ?? 0, 0, ',', '.')); ?> observasi tersedia untuk seluruh horizon.</p>
                    <?php endif; ?>
                </div>
            </details>
        </div>
    </div>
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
<?php /**PATH D:\project\toko-emas-wijaya-kusuma\resources\views\analisis-persediaan\perbandingan.blade.php ENDPATH**/ ?>