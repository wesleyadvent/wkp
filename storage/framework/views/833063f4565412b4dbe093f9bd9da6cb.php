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
                <p class="text-xs font-semibold uppercase tracking-[.18em] text-amber-600">Operasional toko</p>
                <h2 class="mt-1 text-xl font-semibold text-slate-900">Buat Nota Service</h2>
            </div>
            <span class="rounded-full border border-blue-200 bg-blue-50 px-3 py-1.5 text-xs font-semibold text-blue-700">Status awal: Diproses</span>
        </div>
     <?php $__env->endSlot(); ?>

    <div class="py-8">
        <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
            <form method="POST" action="<?php echo e(route('service-notes.store')); ?>" class="space-y-6">
                <?php echo csrf_field(); ?>
                <?php if (isset($component)) { $__componentOriginalf94ed9c5393ef72725d159fe01139746 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalf94ed9c5393ef72725d159fe01139746 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => '92edfda80dc3faed2cda654b45151250::input-error','data' => ['messages' => $errors->all()]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('input-error'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['messages' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($errors->all())]); ?>
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
                <?php if (isset($component)) { $__componentOriginale8201895c7684dc6cbac45067ef41981 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale8201895c7684dc6cbac45067ef41981 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => '92edfda80dc3faed2cda654b45151250::employee-select','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('employee-select'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginale8201895c7684dc6cbac45067ef41981)): ?>
<?php $attributes = $__attributesOriginale8201895c7684dc6cbac45067ef41981; ?>
<?php unset($__attributesOriginale8201895c7684dc6cbac45067ef41981); ?>
<?php endif; ?>
<?php if (isset($__componentOriginale8201895c7684dc6cbac45067ef41981)): ?>
<?php $component = $__componentOriginale8201895c7684dc6cbac45067ef41981; ?>
<?php unset($__componentOriginale8201895c7684dc6cbac45067ef41981); ?>
<?php endif; ?>
                <fieldset id="note-fields" disabled class="space-y-6 disabled:opacity-60" aria-describedby="employee-verification-status">
                    <section class="relative overflow-visible rounded-2xl border border-slate-200 bg-white shadow-sm">
                        <div class="rounded-t-2xl border-b border-slate-100 bg-gradient-to-r from-amber-50 to-white px-6 py-5">
                            <p class="text-xs font-semibold uppercase tracking-[.16em] text-amber-700">Langkah 1</p>
                            <h3 class="mt-1 text-lg font-semibold text-slate-900">Identitas nota</h3>
                            <p class="mt-1 text-sm text-slate-500">Pilih customer dengan mengetik nama atau nomor telepon. Suggestion menampilkan nama dan alamat.</p>
                        </div>
                        <div class="grid gap-5 p-6 sm:grid-cols-2">
                            <input type="hidden" id="branch_id" name="branch_id" value="<?php echo e(old('branch_id', Auth::user()->branch_id)); ?>">
                            <div>
                                <?php if (isset($component)) { $__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => '92edfda80dc3faed2cda654b45151250::input-label','data' => ['for' => 'asal_barang','value' => 'Asal barang']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('input-label'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['for' => 'asal_barang','value' => 'Asal barang']); ?>
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
                                <select id="asal_barang" name="asal_barang" class="mt-1 block w-full rounded-lg border-slate-300">
                                    <option value="toko_sendiri" <?php if(old('asal_barang', 'toko_sendiri') === 'toko_sendiri'): echo 'selected'; endif; ?>>Barang dari toko sendiri</option>
                                    <option value="barang_luar" <?php if(old('asal_barang') === 'barang_luar'): echo 'selected'; endif; ?>>Barang dari luar</option>
                                </select>
                            </div>
                            <div id="service-customer-search" data-customer-search data-url="<?php echo e(route('sales.customer-query')); ?>" class="relative sm:col-span-2">
                                <?php if (isset($component)) { $__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => '92edfda80dc3faed2cda654b45151250::input-label','data' => ['for' => 'customer-search','value' => 'Customer']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('input-label'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['for' => 'customer-search','value' => 'Customer']); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => '92edfda80dc3faed2cda654b45151250::text-input','data' => ['id' => 'customer-search','type' => 'search','class' => 'mt-1 block w-full','value' => ''.e($selectedCustomer?->nama).'','required' => true,'autocomplete' => 'off','maxlength' => '100','placeholder' => 'Ketik nama atau nomor telepon','role' => 'combobox','ariaAutocomplete' => 'list','ariaExpanded' => 'false','ariaControls' => 'customer-results','ariaDescribedby' => 'customer-search-help']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('text-input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['id' => 'customer-search','type' => 'search','class' => 'mt-1 block w-full','value' => ''.e($selectedCustomer?->nama).'','required' => true,'autocomplete' => 'off','maxlength' => '100','placeholder' => 'Ketik nama atau nomor telepon','role' => 'combobox','aria-autocomplete' => 'list','aria-expanded' => 'false','aria-controls' => 'customer-results','aria-describedby' => 'customer-search-help']); ?>
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
                                <input type="hidden" id="customer_id" name="customer_id" value="<?php echo e(old('customer_id')); ?>">
                                <div id="customer-results" role="listbox" hidden class="absolute z-30 mt-1 max-h-64 w-full overflow-y-auto rounded-xl border border-slate-200 bg-white shadow-xl"></div>
                                <p id="customer-search-help" role="status" class="mt-2 text-xs text-slate-500">Ketik nama customer untuk melihat suggestion.</p>
                            </div>
                        </div>
                    </section>

                    <?php if (isset($component)) { $__componentOriginal716852d4390575b8dc682aff08e8cdb7 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal716852d4390575b8dc682aff08e8cdb7 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => '92edfda80dc3faed2cda654b45151250::sale-reference','data' => ['endpoint' => route('service-notes.find-sale-query'),'source' => 'asal_barang','nameField' => 'nama_barang_manual']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('sale-reference'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['endpoint' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(route('service-notes.find-sale-query')),'source' => 'asal_barang','name-field' => 'nama_barang_manual']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal716852d4390575b8dc682aff08e8cdb7)): ?>
<?php $attributes = $__attributesOriginal716852d4390575b8dc682aff08e8cdb7; ?>
<?php unset($__attributesOriginal716852d4390575b8dc682aff08e8cdb7); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal716852d4390575b8dc682aff08e8cdb7)): ?>
<?php $component = $__componentOriginal716852d4390575b8dc682aff08e8cdb7; ?>
<?php unset($__componentOriginal716852d4390575b8dc682aff08e8cdb7); ?>
<?php endif; ?>

                    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                        <div class="border-b border-slate-100 bg-gradient-to-r from-slate-50 to-white px-6 py-5">
                            <p class="text-xs font-semibold uppercase tracking-[.16em] text-slate-500">Langkah 2</p>
                            <h3 class="mt-1 text-lg font-semibold text-slate-900">Data barang</h3>
                            <p class="mt-1 text-sm text-slate-500">Pastikan identitas dan berat barang sesuai dengan barang yang diterima.</p>
                        </div>
                        <div class="grid gap-5 p-6 sm:grid-cols-2">
                            <div>
                                <?php if (isset($component)) { $__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => '92edfda80dc3faed2cda654b45151250::input-label','data' => ['for' => 'nama_barang_manual','value' => 'Nama barang']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('input-label'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['for' => 'nama_barang_manual','value' => 'Nama barang']); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => '92edfda80dc3faed2cda654b45151250::text-input','data' => ['id' => 'nama_barang_manual','name' => 'nama_barang_manual','type' => 'text','value' => old('nama_barang_manual', ''),'class' => 'mt-1 block w-full','required' => true]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('text-input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['id' => 'nama_barang_manual','name' => 'nama_barang_manual','type' => 'text','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(old('nama_barang_manual', '')),'class' => 'mt-1 block w-full','required' => true]); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => '92edfda80dc3faed2cda654b45151250::input-label','data' => ['for' => 'berat','value' => 'Berat (gram)']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('input-label'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['for' => 'berat','value' => 'Berat (gram)']); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => '92edfda80dc3faed2cda654b45151250::text-input','data' => ['id' => 'berat','name' => 'berat','type' => 'number','value' => old('berat', ''),'class' => 'mt-1 block w-full','step' => '0.001','min' => '0.001','required' => true]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('text-input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['id' => 'berat','name' => 'berat','type' => 'number','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(old('berat', '')),'class' => 'mt-1 block w-full','step' => '0.001','min' => '0.001','required' => true]); ?>
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
                        </div>
                    </section>

                    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                        <div class="flex flex-wrap items-start justify-between gap-4 border-b border-slate-100 bg-gradient-to-r from-blue-50 to-white px-6 py-5">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-[.16em] text-blue-600">Langkah 3</p>
                                <h3 class="mt-1 text-lg font-semibold text-slate-900">Pengerjaan service</h3>
                                <p class="mt-1 text-sm text-slate-500">Tambahkan satu atau beberapa jenis service dalam satu nota.</p>
                            </div>
                            <button type="button" id="add-service" class="action-button action-detail">+ Tambah jenis service</button>
                        </div>
                        <div class="space-y-4 p-6">
                            <div id="service-items" class="space-y-4"></div>
                            <div class="flex items-center justify-between rounded-xl bg-slate-50 px-4 py-4">
                                <span class="text-sm font-medium text-slate-600">Total biaya service</span>
                                <strong id="service-total" class="text-xl text-slate-900">Rp 0</strong>
                            </div>
                            <div class="grid gap-5 border-t border-slate-100 pt-5 sm:grid-cols-2">
                                <div>
                                    <?php if (isset($component)) { $__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => '92edfda80dc3faed2cda654b45151250::input-label','data' => ['for' => 'estimasi_selesai','value' => 'Estimasi selesai']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('input-label'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['for' => 'estimasi_selesai','value' => 'Estimasi selesai']); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => '92edfda80dc3faed2cda654b45151250::text-input','data' => ['id' => 'estimasi_selesai','name' => 'estimasi_selesai','type' => 'date','value' => old('estimasi_selesai', ''),'class' => 'mt-1 block w-full']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('text-input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['id' => 'estimasi_selesai','name' => 'estimasi_selesai','type' => 'date','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(old('estimasi_selesai', '')),'class' => 'mt-1 block w-full']); ?>
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
                                <div class="rounded-xl border border-blue-100 bg-blue-50 p-4 text-sm text-blue-800">
                                    <span class="font-semibold">Status otomatis: Diproses</span>
                                    <p class="mt-1 text-blue-700">Setelah barang selesai dikerjakan, ubah status melalui tombol detail nota.</p>
                                </div>
                            </div>
                        </div>
                    </section>

                    <div class="flex flex-wrap gap-3 border-t border-slate-200 pt-2">
                        <?php if (isset($component)) { $__componentOriginald411d1792bd6cc877d687758b753742c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald411d1792bd6cc877d687758b753742c = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => '92edfda80dc3faed2cda654b45151250::primary-button','data' => ['id' => 'save-note']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('primary-button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['id' => 'save-note']); ?>Simpan Nota Service <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginald411d1792bd6cc877d687758b753742c)): ?>
<?php $attributes = $__attributesOriginald411d1792bd6cc877d687758b753742c; ?>
<?php unset($__attributesOriginald411d1792bd6cc877d687758b753742c); ?>
<?php endif; ?>
<?php if (isset($__componentOriginald411d1792bd6cc877d687758b753742c)): ?>
<?php $component = $__componentOriginald411d1792bd6cc877d687758b753742c; ?>
<?php unset($__componentOriginald411d1792bd6cc877d687758b753742c); ?>
<?php endif; ?>
                        <a href="<?php echo e(route('service-notes.index')); ?>" class="action-button action-detail">Kembali</a>
                    </div>
                </fieldset>
            </form>
        </div>
    </div>

    <template id="service-row-template">
        <div class="service-row rounded-xl border border-slate-200 bg-slate-50 p-4">
            <div class="grid gap-4 md:grid-cols-[minmax(0,1fr)_180px_auto] md:items-end">
                <div>
                    <label class="block text-sm font-medium text-slate-700">Jenis service</label>
                    <select name="service_type_ids[]" class="service-type mt-1 block w-full rounded-lg border-slate-300" required>
                        <option value="">Pilih jenis service</option>
                        <?php $__currentLoopData = $serviceTypes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $type): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($type->id); ?>" data-price="<?php echo e($type->harga_default); ?>"><?php echo e($type->nama_service); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Harga (Rp)</label>
                    <input name="service_prices[]" type="number" step="0.01" min="0" class="service-price mt-1 block w-full rounded-lg border-slate-300" required>
                </div>
                <button type="button" class="remove-service action-button action-delete">Hapus</button>
            </div>
            <div class="mt-4">
                <label class="block text-sm font-medium text-slate-700">Keterangan pengerjaan <span class="font-normal text-slate-400">(opsional)</span></label>
                <textarea name="service_notes[]" rows="2" maxlength="255" class="mt-1 block w-full rounded-lg border-slate-300" placeholder="Contoh: poles ulang dan cek pengunci"></textarea>
            </div>
        </div>
    </template>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const container = document.getElementById('service-items');
            const template = document.getElementById('service-row-template');
            const total = document.getElementById('service-total');
            const oldTypes = <?php echo json_encode(old('service_type_ids', []), 512) ?>;
            const oldPrices = <?php echo json_encode(old('service_prices', []), 512) ?>;
            const oldNotes = <?php echo json_encode(old('service_notes', []), 512) ?>;
            const money = value => new Intl.NumberFormat('id-ID', {style: 'currency', currency: 'IDR', minimumFractionDigits: 2, maximumFractionDigits: 2}).format(Number(value || 0));
            const updateTotal = () => { total.textContent = money([...container.querySelectorAll('.service-price')].reduce((sum, input) => sum + Number(input.value || 0), 0)); };
            const addRow = (type = '', price = '', note = '') => {
                const row = template.content.firstElementChild.cloneNode(true);
                const select = row.querySelector('.service-type');
                const priceInput = row.querySelector('.service-price');
                select.value = type; priceInput.value = price;
                row.querySelector('textarea').value = note;
                select.addEventListener('change', () => { if (!priceInput.value || Number(priceInput.value) === 0) priceInput.value = select.selectedOptions[0]?.dataset.price || ''; updateTotal(); });
                priceInput.addEventListener('input', updateTotal);
                row.querySelector('.remove-service').addEventListener('click', () => { if (container.children.length > 1) { row.remove(); updateTotal(); } });
                container.append(row); updateTotal();
            };
            document.getElementById('add-service').addEventListener('click', () => addRow());
            if (oldTypes.length) oldTypes.forEach((type, index) => addRow(type, oldPrices[index] || '', oldNotes[index] || '')); else addRow();
        });
    </script>
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
<?php /**PATH D:\project\toko-emas-wijaya-kusuma\resources\views\nota-layanan\form.blade.php ENDPATH**/ ?>