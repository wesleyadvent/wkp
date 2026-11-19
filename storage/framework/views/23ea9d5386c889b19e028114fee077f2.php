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
     <?php $__env->slot('header', null, []); ?> <h2 class="font-semibold text-xl text-gray-800">Buat Nota Penjualan</h2> <?php $__env->endSlot(); ?>
    <div class="py-8"><div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
        <form method="POST" action="<?php echo e(route('sales.store')); ?>" class="space-y-5 rounded-xl bg-white p-6 shadow-sm"><?php echo csrf_field(); ?>
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
<fieldset id="note-fields" disabled class="space-y-6 disabled:opacity-50" aria-describedby="employee-verification-status">
<p class="text-sm text-gray-500">Tanggal nota otomatis mengikuti tanggal saat disimpan (WIB).</p>
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
            <div class="grid gap-5 sm:grid-cols-2">
                <?php if(Auth::user()->isAdmin()): ?>
                    <div><?php if (isset($component)) { $__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $component; } ?>
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
<?php endif; ?><select name="branch_id" id="branch_id" class="mt-1 block w-full rounded-md border-gray-300" required><option value="">Pilih cabang</option><?php $__currentLoopData = $branches; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $branch): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($branch->id); ?>" <?php if((string)old('branch_id') === (string)$branch->id): echo 'selected'; endif; ?>><?php echo e($branch->nama_cabang); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></select></div>
                <?php endif; ?>
                <div id="sale-customer-search" data-customer-search class="relative" data-url="<?php echo e(route('sales.customer-query')); ?>" data-branch-id="<?php echo e(Auth::user()->isBranchUser() ? Auth::user()->branch_id : ''); ?>">
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => '92edfda80dc3faed2cda654b45151250::text-input','data' => ['id' => 'customer-search','type' => 'search','class' => 'mt-1 block w-full','value' => $selectedCustomer?->nama,'required' => true,'autocomplete' => 'off','maxlength' => '100','placeholder' => 'Ketik nama atau nomor telepon','role' => 'combobox','ariaAutocomplete' => 'list','ariaExpanded' => 'false','ariaControls' => 'customer-results','ariaDescribedby' => 'customer-search-help']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('text-input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['id' => 'customer-search','type' => 'search','class' => 'mt-1 block w-full','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($selectedCustomer?->nama),'required' => true,'autocomplete' => 'off','maxlength' => '100','placeholder' => 'Ketik nama atau nomor telepon','role' => 'combobox','aria-autocomplete' => 'list','aria-expanded' => 'false','aria-controls' => 'customer-results','aria-describedby' => 'customer-search-help']); ?>
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
                    <input type="hidden" id="customer_id" name="customer_id" value="<?php echo e($selectedCustomer?->id); ?>">
                    <div id="customer-results" role="listbox" hidden class="absolute z-20 mt-1 max-h-64 w-full overflow-y-auto rounded-lg border border-gray-200 bg-white shadow-lg"></div>
                    <p id="customer-search-help" role="status" class="mt-2 text-xs text-gray-500">Ketik untuk mencari, lalu pilih customer dari hasil pencarian.</p>
                </div>
                <input type="hidden" name="product_id" id="product_id" value="<?php echo e(old('product_id')); ?>">
            </div>
            <section id="sale-barcode" data-lookup-url="<?php echo e(route('sales.barcode-query')); ?>" data-branch-id="<?php echo e(Auth::user()->isBranchUser() ? Auth::user()->branch_id : ''); ?>" class="rounded-lg border border-indigo-200 bg-indigo-50 p-4">
                <?php if (isset($component)) { $__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => '92edfda80dc3faed2cda654b45151250::input-label','data' => ['for' => 'barcode','value' => 'Pindai barcode barang']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('input-label'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['for' => 'barcode','value' => 'Pindai barcode barang']); ?>
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
                <div class="mt-2 flex flex-wrap gap-2"><?php if (isset($component)) { $__componentOriginal18c21970322f9e5c938bc954620c12bb = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal18c21970322f9e5c938bc954620c12bb = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => '92edfda80dc3faed2cda654b45151250::text-input','data' => ['id' => 'barcode','name' => 'barcode','autocomplete' => 'off','maxlength' => '255','class' => 'flex-1','placeholder' => 'Pindai dengan scanner lalu Enter']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('text-input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['id' => 'barcode','name' => 'barcode','autocomplete' => 'off','maxlength' => '255','class' => 'flex-1','placeholder' => 'Pindai dengan scanner lalu Enter']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal18c21970322f9e5c938bc954620c12bb)): ?>
<?php $attributes = $__attributesOriginal18c21970322f9e5c938bc954620c12bb; ?>
<?php unset($__attributesOriginal18c21970322f9e5c938bc954620c12bb); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal18c21970322f9e5c938bc954620c12bb)): ?>
<?php $component = $__componentOriginal18c21970322f9e5c938bc954620c12bb; ?>
<?php unset($__componentOriginal18c21970322f9e5c938bc954620c12bb); ?>
<?php endif; ?><button id="lookup-barcode" type="button" class="rounded bg-indigo-700 px-4 py-2 text-white">Cari barcode</button><button id="start-barcode-camera" type="button" class="rounded border border-indigo-300 bg-white px-4 py-2">Pindai kamera</button><button id="stop-barcode-camera" type="button" hidden class="rounded border border-gray-300 bg-white px-4 py-2">Tutup kamera</button></div>
                <p id="barcode-message" role="status" aria-live="polite" class="mt-2 text-sm">Pindai dengan scanner atau kamera, atau ketik kode barcode lalu klik Cari barcode.</p>
                <video id="barcode-video" hidden muted playsinline class="mt-3 w-full rounded" style="max-height:300px"></video>
            </section>
            <p id="product-help" class="text-sm text-gray-500">Pindai barcode untuk menampilkan rincian barang.</p>
            <section id="selected-product" hidden class="rounded-xl border border-gray-200 bg-gray-50 p-5" aria-live="polite">
                <h3 class="mb-4 font-semibold text-gray-900">Rincian barang dari database</h3>
                <div class="grid gap-6 md:grid-cols-3">
                    <div><a id="product-photo-link" target="_blank" rel="noopener" aria-label="Buka foto barang ukuran penuh"><img id="product-photo" alt="" hidden class="w-full rounded-lg border border-gray-200 bg-white object-contain" style="max-height:260px"></a><p id="photo-message" class="rounded-lg bg-white p-6 text-center text-sm text-gray-500">Belum ada foto barang.</p></div>
                    <dl class="grid gap-4 text-sm sm:grid-cols-2 md:col-span-2">
                        <?php $__currentLoopData = ['kode_barang' => 'Kode barang', 'nama_barang' => 'Nama barang', 'branch_name' => 'Cabang', 'kategori' => 'Kategori', 'kadar' => 'Kadar', 'berat' => 'Berat', 'status' => 'Status', 'harga_per_gram' => 'Harga per gram', 'ongkos' => 'Ongkos', 'total_harga' => 'Harga total tersimpan', 'deskripsi' => 'Deskripsi']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $field => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <div><dt class="text-gray-500"><?php echo e($label); ?></dt><dd data-product-field="<?php echo e($field); ?>" class="mt-1 break-words font-semibold text-gray-900"></dd></div>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </dl>
                </div>
                <div class="mt-5 border-t border-gray-200 pt-4"><p class="text-sm text-gray-500">Total penjualan � berat � harga per gram + ongkos</p><p id="sale-total" class="mt-1 text-2xl font-bold text-gray-900"></p></div>
                <p class="mt-3 text-xs text-gray-500">Harga dan ongkos mengikuti data barang. Saat disimpan, sistem memeriksa kembali data dan ketersediaan barang.</p>
            </section>
            <div class="flex flex-wrap gap-3"><?php if (isset($component)) { $__componentOriginald411d1792bd6cc877d687758b753742c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald411d1792bd6cc877d687758b753742c = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => '92edfda80dc3faed2cda654b45151250::primary-button','data' => ['id' => 'save-sale','disabled' => true]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('primary-button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['id' => 'save-sale','disabled' => true]); ?>Simpan Nota Penjualan <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginald411d1792bd6cc877d687758b753742c)): ?>
<?php $attributes = $__attributesOriginald411d1792bd6cc877d687758b753742c; ?>
<?php unset($__attributesOriginald411d1792bd6cc877d687758b753742c); ?>
<?php endif; ?>
<?php if (isset($__componentOriginald411d1792bd6cc877d687758b753742c)): ?>
<?php $component = $__componentOriginald411d1792bd6cc877d687758b753742c; ?>
<?php unset($__componentOriginald411d1792bd6cc877d687758b753742c); ?>
<?php endif; ?><a href="<?php echo e(route('sales.index')); ?>" class="action-button action-detail">Kembali</a></div>
        </fieldset>
</form>
    </div></div>
    <?php
        $productData = $products->map(fn ($product) => array_merge($product->toArray(), ['branch_name' => $product->branch?->nama_cabang, 'photo_url' => $product->photo_path ? route('products.photo', $product) : null]));
    ?>
    <script>
    (() => {
        const products = <?php echo json_encode($productData, 15, 512) ?>;
        const branch = document.getElementById('branch_id');
        const select = document.getElementById('product_id');
        const customer = document.getElementById('customer_id');
        const panel = document.getElementById('selected-product');
        const photo = document.getElementById('product-photo');
        const photoLink = document.getElementById('product-photo-link');
        const photoMessage = document.getElementById('photo-message');
        const money = value => new Intl.NumberFormat('id-ID', {style: 'currency', currency: 'IDR', maximumFractionDigits: 0}).format(Number(value));
        const render = () => {
            const product = products.find(item => String(item.id) === select.value);
            panel.hidden = !product;
            document.getElementById('save-sale').disabled = !product;
            if (!product) return;
            document.querySelectorAll('[data-product-field]').forEach(element => {
                const field = element.dataset.productField;
                let value = product[field];
                if (['harga_per_gram', 'ongkos', 'total_harga'].includes(field)) value = money(value);
                if (field === 'berat') value = Number(value).toLocaleString('id-ID', {minimumFractionDigits: 3}) + ' gram';
                if (['created_at', 'updated_at'].includes(field) && value) value = new Date(value).toLocaleString('id-ID');
                element.textContent = value || '�';
            });
            photo.hidden = true;
            photoMessage.hidden = false;
            photoMessage.textContent = product.photo_url ? 'Memuat foto�' : 'Belum ada foto barang.';
            photoLink.removeAttribute('href');
            if (product.photo_url) {
                photo.alt = product.nama_barang;
                photoLink.href = product.photo_url;
                photo.src = product.photo_url;
            } else photo.removeAttribute('src');
            document.getElementById('sale-total').textContent = money(Number(product.berat) * Number(product.harga_per_gram) + Number(product.ongkos));
        };
        photo.addEventListener('load', () => { photo.hidden = false; photoMessage.hidden = true; });
        photo.addEventListener('error', () => { photo.hidden = true; photoMessage.hidden = false; photoMessage.textContent = 'File foto tidak ditemukan atau gagal dimuat.'; });
        const filter = () => {
            const branchId = branch ? branch.value : <?php echo json_encode((string)Auth::user()->branch_id, 15, 512) ?>;
            const product = products.find(item => String(item.id) === select.value);
            if (product && String(product.branch_id) !== branchId) select.value = '';
            document.getElementById('product-help').textContent = !branchId ? 'Pilih cabang terlebih dahulu.' : 'Pindai barcode barang dari cabang ini.';
            render();
        };
        branch?.addEventListener('change', filter);
        select.addEventListener('change', render);
        document.getElementById('sale-barcode').addEventListener('barcode-product', event => {
            const product = event.detail;
            const index = products.findIndex(item => item.id === product.id);
            if (index >= 0) products[index] = product; else products.push(product);
            select.value = String(product.id);
            render();
        });
        filter();
    })();
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
<?php /**PATH D:\project\toko-emas-wijaya-kusuma\resources\views/penjualan/form.blade.php ENDPATH**/ ?>