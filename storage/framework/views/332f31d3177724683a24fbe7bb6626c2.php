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
            <div>
                <h2 class="text-xl font-semibold leading-tight text-gray-800">Analisis Persediaan <?php echo e($metode); ?></h2>
                <p class="mt-1 text-sm text-gray-500">Tren penjualan dan rekomendasi persediaan berdasarkan kategori dan kadar.</p>
            </div>
            <?php if(Auth::user()->isAdmin()): ?>
                <a href="<?php echo e(route($settingsPrefix.'.index')); ?>" class="inline-flex items-center rounded-md border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Konfigurasi <?php echo e($metode); ?></a>
            <?php endif; ?>
        </div>
     <?php $__env->endSlot(); ?>

    <?php
        $trendBadges = [
            'data_belum_lengkap' => 'bg-slate-100 text-slate-700',
            'baru' => 'bg-sky-100 text-sky-800',
            'meningkat' => 'bg-emerald-100 text-emerald-800',
            'stabil' => 'bg-slate-100 text-slate-700',
            'menurun' => 'bg-rose-100 text-rose-800',
        ];
        $stockBadges = [
            'data_belum_lengkap' => 'bg-slate-100 text-slate-700',
            'ramalan_belum_tersedia' => 'bg-slate-100 text-slate-700',
            'aman' => 'bg-emerald-100 text-emerald-800',
            'perlu_perhatian' => 'bg-amber-100 text-amber-800',
            'kritis' => 'bg-rose-100 text-rose-800',
        ];
    ?>

    <div class="py-8">
        <div class="mx-auto max-w-screen-2xl space-y-5 sm:px-6 lg:px-8">
            <form method="GET" action="<?php echo e(route($analysisRoute)); ?>" class="grid items-end gap-3 rounded-lg bg-white p-4 shadow-sm md:grid-cols-4">
                <div><?php if (isset($component)) { $__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => '92edfda80dc3faed2cda654b45151250::input-label','data' => ['for' => 'cutoff','value' => 'Tanggal potong']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('input-label'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['for' => 'cutoff','value' => 'Tanggal potong']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581)): ?>
<?php $attributes = $__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581; ?>
<?php unset($__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581); ?>
<?php endif; ?>
<?php if (isset($__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581)): ?>
<?php $component = $__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581; ?>
<?php unset($__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581); ?>
<?php endif; ?><?php if (isset($component)) { $__componentOriginal18c21970322f9e5c938bc954620c12bb = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal18c21970322f9e5c938bc954620c12bb = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => '92edfda80dc3faed2cda654b45151250::text-input','data' => ['type' => 'date','id' => 'cutoff','name' => 'cutoff','value' => $cutoff,'max' => now('Asia/Jakarta')->subDay()->toDateString(),'class' => 'mt-1 block w-full']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('text-input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['type' => 'date','id' => 'cutoff','name' => 'cutoff','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($cutoff),'max' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(now('Asia/Jakarta')->subDay()->toDateString()),'class' => 'mt-1 block w-full']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal18c21970322f9e5c938bc954620c12bb)): ?>
<?php $attributes = $__attributesOriginal18c21970322f9e5c938bc954620c12bb; ?>
<?php unset($__attributesOriginal18c21970322f9e5c938bc954620c12bb); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal18c21970322f9e5c938bc954620c12bb)): ?>
<?php $component = $__componentOriginal18c21970322f9e5c938bc954620c12bb; ?>
<?php unset($__componentOriginal18c21970322f9e5c938bc954620c12bb); ?>
<?php endif; ?></div>
                <?php if(Auth::user()->isAdmin()): ?>
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
                        <select id="branch_id" name="branch_id" class="mt-1 block w-full rounded-md border-gray-300">
                            <option value="">Seluruh cabang</option>
                            <?php $__currentLoopData = $branches; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $branch): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($branch->id); ?>" <?php if($selectedBranchId === $branch->id): echo 'selected'; endif; ?>><?php echo e($branch->nama_cabang); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                        <?php if (isset($component)) { $__componentOriginalf94ed9c5393ef72725d159fe01139746 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalf94ed9c5393ef72725d159fe01139746 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => '92edfda80dc3faed2cda654b45151250::input-error','data' => ['messages' => $errors->get('branch_id'),'class' => 'mt-2']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('input-error'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['messages' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($errors->get('branch_id')),'class' => 'mt-2']); ?>
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
                    </div>
                <?php else: ?>
                    <div>
                        <?php if (isset($component)) { $__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => '92edfda80dc3faed2cda654b45151250::input-label','data' => ['value' => 'Cabang']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('input-label'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['value' => 'Cabang']); ?>
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
                        <div class="mt-1 rounded-md border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-700">Data cabang Anda</div>
                    </div>
                <?php endif; ?>
                <div>
                    <?php if (isset($component)) { $__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => '92edfda80dc3faed2cda654b45151250::input-label','data' => ['for' => 'periode','value' => 'Jendela analisis &amp; ramalan']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('input-label'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['for' => 'periode','value' => 'Jendela analisis &amp; ramalan']); ?>
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
                    <select id="periode" name="periode" class="mt-1 block w-full rounded-md border-gray-300">
                        <?php $__currentLoopData = \App\Services\InventoryAnalysisService::PERIODS; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $option): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($option); ?>" <?php if($periode === $option): echo 'selected'; endif; ?>><?php echo e($option); ?> hari</option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                    <?php if (isset($component)) { $__componentOriginalf94ed9c5393ef72725d159fe01139746 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalf94ed9c5393ef72725d159fe01139746 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => '92edfda80dc3faed2cda654b45151250::input-error','data' => ['messages' => $errors->get('periode'),'class' => 'mt-2']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('input-error'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['messages' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($errors->get('periode')),'class' => 'mt-2']); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => '92edfda80dc3faed2cda654b45151250::text-input','data' => ['id' => 'q','name' => 'q','class' => 'mt-1 block w-full','value' => request('q'),'placeholder' => 'Cabang, kategori, atau kadar']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('text-input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['id' => 'q','name' => 'q','class' => 'mt-1 block w-full','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(request('q')),'placeholder' => 'Cabang, kategori, atau kadar']); ?>
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
                    <?php if (isset($component)) { $__componentOriginalf94ed9c5393ef72725d159fe01139746 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalf94ed9c5393ef72725d159fe01139746 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => '92edfda80dc3faed2cda654b45151250::input-error','data' => ['messages' => $errors->get('q'),'class' => 'mt-2']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('input-error'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['messages' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($errors->get('q')),'class' => 'mt-2']); ?>
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
                </div>
                <div class="flex h-10 gap-2">
                    <?php if (isset($component)) { $__componentOriginald411d1792bd6cc877d687758b753742c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald411d1792bd6cc877d687758b753742c = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => '92edfda80dc3faed2cda654b45151250::primary-button','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('primary-button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>Analisis <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginald411d1792bd6cc877d687758b753742c)): ?>
<?php $attributes = $__attributesOriginald411d1792bd6cc877d687758b753742c; ?>
<?php unset($__attributesOriginald411d1792bd6cc877d687758b753742c); ?>
<?php endif; ?>
<?php if (isset($__componentOriginald411d1792bd6cc877d687758b753742c)): ?>
<?php $component = $__componentOriginald411d1792bd6cc877d687758b753742c; ?>
<?php unset($__componentOriginald411d1792bd6cc877d687758b753742c); ?>
<?php endif; ?>
                    <a href="<?php echo e(route($analysisRoute)); ?>" class="inline-flex items-center rounded-md border border-gray-300 px-3 text-sm text-gray-600 hover:bg-gray-50">Reset</a>
                </div>
            </form>

            <?php if($metode === 'LightGBM'): ?>
                <?php if($lightgbmStatus && $lightgbmStatus['state'] !== 'ready'): ?>
                    <div role="alert" class="space-y-2 rounded border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
                        <p class="font-semibold"><?php echo e($lightgbmStatus['message']); ?></p>
                        <?php if($lightgbmStatus['state'] === 'unreachable'): ?>
                            <p>Jalankan perintah berikut dari folder proyek dan biarkan terminal tetap terbuka:</p>
                            <code class="block overflow-x-auto rounded bg-white p-2">.\.venv-lightgbm\Scripts\python.exe services\lightgbm\serve.py</code>
                        <?php endif; ?>
                        <p>Ramalan, ROP, dan jumlah yang perlu disiapkan belum dapat dihitung sampai layanan siap.</p>
                    </div>
                <?php endif; ?>
                <p class="rounded border border-sky-200 bg-sky-50 p-4 text-sm text-sky-900">Model LightGBM ini dilatih dengan transaksi sintetis. Model lead time tersedia untuk 3, 7, 14, dan 21 hari; proyeksi memakai model tersendiri untuk 30, 60, atau 90 hari. Jika model atau data belum mendukung, ramalan terkait ditandai belum tersedia beserta alasannya.</p>
            <?php endif; ?>

            <?php if($summary['belum_lengkap']): ?><p class="rounded border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900"><?php echo e($summary['belum_lengkap']); ?> kelompok belum memiliki pencatatan lengkap pada tanggal potong ini. Ramalan dan ROP ditunda; pilih tanggal dengan pencatatan lengkap atau konfirmasikan pencatatannya terlebih dahulu.</p><?php endif; ?>
            <?php if($summary['ramalan_belum_tersedia'] && ! ($lightgbmStatus && $lightgbmStatus['state'] !== 'ready')): ?><p class="rounded border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900"><?php echo e($summary['ramalan_belum_tersedia']); ?> kelompok memiliki ramalan <?php echo e($metode); ?> yang belum tersedia. Periksa keterangan pada tabel. ROP dan jumlah yang perlu disiapkan hanya dihitung jika ramalan lead time <?php echo e($metode); ?> tersedia.</p><?php endif; ?>
            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <div class="rounded-xl border border-emerald-100 bg-white p-5 shadow-sm"><p class="text-sm font-medium text-slate-500">Kelompok aman</p><p class="mt-2 text-3xl font-bold text-emerald-700"><?php echo e($summary['aman']); ?></p></div>
                <div class="rounded-xl border border-amber-100 bg-white p-5 shadow-sm"><p class="text-sm font-medium text-slate-500">Perlu perhatian</p><p class="mt-2 text-3xl font-bold text-amber-700"><?php echo e($summary['perlu_perhatian']); ?></p></div>
                <div class="rounded-xl border border-rose-100 bg-white p-5 shadow-sm"><p class="text-sm font-medium text-slate-500">Kritis</p><p class="mt-2 text-3xl font-bold text-rose-700"><?php echo e($summary['kritis']); ?></p></div>
                <div class="rounded-xl border border-sky-100 bg-white p-5 shadow-sm">
                    <p class="text-sm font-medium text-slate-500">Tren penjualan tertinggi</p>
                    <?php if($summary['tren_tertinggi']): ?>
                        <p class="mt-2 truncate text-base font-bold text-slate-900"><?php echo e($summary['tren_tertinggi']['kategori']); ?> – <?php echo e($summary['tren_tertinggi']['kadar']); ?></p>
                        <p class="mt-1 text-sm text-slate-600"><?php echo e($summary['tren_tertinggi']['cabang']); ?> · <?php echo e(ucfirst($summary['tren_tertinggi']['status_tren'])); ?></p>
                    <?php else: ?>
                        <p class="mt-2 text-sm text-slate-500">Belum ada penjualan valid.</p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                <div class="flex flex-wrap items-center justify-between gap-2 border-b border-gray-100 px-5 py-4">
                    <div>
                        <h3 class="font-semibold text-gray-900">Hasil analisis <?php echo e($periode); ?> hari</h3>
                        <p class="mt-1 text-xs text-gray-500">Tanggal potong <?php echo e($cutoff); ?>. Ramalan lead time memperkirakan penjualan selama menunggu barang datang; ramalan jendela untuk <?php echo e($periode); ?> hari ke depan. Ramalan ditampilkan dalam unit bulat terdekat. ROP = pembulatan ke atas (ramalan lead time + safety stock).</p>
                        <p class="mt-1 text-xs text-gray-500">Tren memakai <?php echo e($periode); ?> hari terakhir. <?php echo e($metode === 'LightGBM' ? 'LightGBM memakai riwayat 90 hari, jadwal buka dan Idulfitri.' : ($metode === 'ARIMA' ? 'ARIMA memakai hasil batch dengan tanggal potong, horizon dan riwayat yang sesuai.' : $metode.' memakai riwayat sesuai jendela yang dipilih.')); ?></p>
                    </div>
                    <span class="text-sm text-gray-500"><?php echo e(number_format($totalRows, 0, ',', '.')); ?> kelompok</span>
                </div>
                <div class="table-scroll inventory-analysis-scroll" role="region" aria-label="Tabel analisis persediaan" tabindex="0">
                    <table class="data-table inventory-analysis-table divide-y divide-gray-200 text-sm">
                        <colgroup>
                            <col class="inventory-col-group">
                            <col class="inventory-col-stock">
                            <col class="inventory-col-sales">
                            <col class="inventory-col-trend">
                            <col class="inventory-col-plan">
                            <col class="inventory-col-number">
                            <col class="inventory-col-number">
                            <col class="inventory-col-status">
                        </colgroup>
                        <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                            <tr>
                                <th scope="col">Kelompok barang</th>
                                <th scope="col" class="text-right">Stok</th>
                                <th scope="col">Penjualan</th>
                                <th scope="col">Perubahan &amp; tren</th>
                                <th scope="col">Perencanaan</th>
                                <th scope="col" class="text-right">ROP</th>
                                <th scope="col" class="text-right">Siapkan</th>
                                <th scope="col">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <?php $__empty_1 = true; $__currentLoopData = $rows; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                <tr class="hover:bg-amber-50/30">
                                    <td>
                                        <span class="inventory-primary"><?php echo e($row['kategori']); ?> · <?php echo e($row['kadar']); ?></span>
                                        <span class="inventory-secondary"><?php echo e($row['cabang']); ?></span>
                                    </td>
                                    <td class="text-right inventory-emphasis"><?php echo e(number_format($row['stok_tersedia'], 0, ',', '.')); ?></td>
                                    <td>
                                        <dl class="inventory-metrics">
                                            <div><dt>Berjalan</dt><dd><?php echo e(number_format($row['penjualan_periode_berjalan'], 0, ',', '.')); ?></dd></div>
                                            <div><dt>Sebelumnya</dt><dd><?php echo e(number_format($row['penjualan_periode_sebelumnya'], 0, ',', '.')); ?></dd></div>
                                            <?php if(in_array($metode, ['SMA', 'WMA'], true)): ?>
                                                <div><dt><?php echo e($metode); ?>/hari</dt><dd><?php echo e($row['estimasi_harian_metode'] === null ? 'Data belum lengkap' : number_format($row['estimasi_harian_metode'], 0, ',', '.')); ?></dd></div>
                                            <?php else: ?>
                                                <div><dt>Rata-rata/hari</dt><dd><?php echo e($row['rata_rata_penjualan_harian'] === null ? 'Data belum lengkap' : number_format($row['rata_rata_penjualan_harian'], 0, ',', '.')); ?></dd></div>
                                            <?php endif; ?>
                                        </dl>
                                    </td>
                                    <td>
                                        <span class="inventory-primary tabular-nums"><?php echo e($row['persentase_perubahan'] === null ? '—' : number_format($row['persentase_perubahan'], 1, ',', '.').'%'); ?></span>
                                        <span class="inventory-badge <?php echo e($trendBadges[$row['status_tren']]); ?>"><?php echo e(ucfirst($row['status_tren'])); ?></span>
                                    </td>
                                    <td>
                                        <dl class="inventory-metrics">
                                            <div><dt>Ramalan lead time (<?php echo e($row['lead_time_hari']); ?> hari)</dt><dd><?php echo e($row['ramalan_unit'] === null ? 'Belum tersedia' : number_format($row['ramalan_unit'], 0, ',', '.').' unit'); ?></dd></div>
                                            <div><dt>Metode</dt><dd title="<?php echo e($row['versi_model'] ?? ''); ?>"><?php echo e($metode); ?></dd></div>
                                        </dl>
                                        <span class="inventory-secondary">Riwayat <?php echo e($row['riwayat_mulai']); ?> s.d. <?php echo e($row['riwayat_akhir']); ?></span>
                                        <?php if($row['alasan_fallback']): ?><span class="inventory-secondary">Diminta <?php echo e($row['metode_diminta']); ?>: <?php echo e($row['alasan_fallback']); ?></span><?php endif; ?>
                                        <div class="mt-3">
                                            <dl class="inventory-metrics">
                                                <div><dt>Ramalan jendela (<?php echo e($periode); ?> hari)</dt><dd><?php echo e($row['ramalan_unit_periode'] === null ? 'Belum tersedia' : number_format($row['ramalan_unit_periode'], 0, ',', '.').' unit'); ?></dd></div>
                                                <div><dt>Metode</dt><dd title="<?php echo e($row['versi_model_periode'] ?? ''); ?>"><?php echo e($metode); ?></dd></div>
                                            </dl>
                                            <?php if($row['riwayat_mulai_periode'] !== $row['riwayat_mulai']): ?><span class="inventory-secondary">Riwayat <?php echo e($row['riwayat_mulai_periode']); ?> s.d. <?php echo e($row['riwayat_akhir']); ?></span><?php endif; ?>
                                            <?php if($row['alasan_fallback_periode']): ?><span class="inventory-secondary">Diminta <?php echo e($row['metode_diminta']); ?>: <?php echo e($row['alasan_fallback_periode']); ?></span><?php endif; ?>
                                        </div>
                                        <div class="mt-3"><dl class="inventory-metrics"><div><dt>Safety stock</dt><dd><?php echo e($row['safety_stock']); ?></dd></div></dl></div>
                                    </td>
                                    <td class="text-right inventory-emphasis"><?php echo e($row['rop'] ?? '—'); ?></td>
                                    <td class="text-right inventory-emphasis"><?php echo e($row['jumlah_perlu_disiapkan'] ?? '—'); ?></td>
                                    <td><span class="inventory-badge <?php echo e($stockBadges[$row['status_persediaan']]); ?>"><?php echo e(ucfirst(str_replace('_', ' ', $row['status_persediaan']))); ?></span></td>
                                </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                <tr><td colspan="8" class="px-4 py-10 text-center text-gray-500">Belum ada stok tersedia atau penjualan valid pada periode analisis ini.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <div class="border-t border-gray-100 px-5 py-4"><?php echo e($rows->links()); ?></div>
            </div>
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
<?php /**PATH D:\project\toko-emas-wijaya-kusuma\resources\views/analisis-persediaan/index.blade.php ENDPATH**/ ?>