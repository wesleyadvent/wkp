<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames((['endpoint', 'source', 'nameField']));

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

foreach (array_filter((['endpoint', 'source', 'nameField']), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>
<section id="sale-reference" hidden class="rounded-xl border border-indigo-200 bg-indigo-50 p-5">
    <?php if (isset($component)) { $__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => '92edfda80dc3faed2cda654b45151250::input-label','data' => ['for' => 'nomor_nota','value' => 'Kode nota penjualan']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('input-label'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['for' => 'nomor_nota','value' => 'Kode nota penjualan']); ?>
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
    <div class="mt-2 flex flex-wrap gap-3"><?php if (isset($component)) { $__componentOriginal18c21970322f9e5c938bc954620c12bb = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal18c21970322f9e5c938bc954620c12bb = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => '92edfda80dc3faed2cda654b45151250::text-input','data' => ['id' => 'nomor_nota','name' => 'nomor_nota','value' => old('nomor_nota'),'placeholder' => 'PJL/WKP-DSA/2026/0033','class' => 'min-w-0 flex-1']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('text-input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['id' => 'nomor_nota','name' => 'nomor_nota','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(old('nomor_nota')),'placeholder' => 'PJL/WKP-DSA/2026/0033','class' => 'min-w-0 flex-1']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal18c21970322f9e5c938bc954620c12bb)): ?>
<?php $attributes = $__attributesOriginal18c21970322f9e5c938bc954620c12bb; ?>
<?php unset($__attributesOriginal18c21970322f9e5c938bc954620c12bb); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal18c21970322f9e5c938bc954620c12bb)): ?>
<?php $component = $__componentOriginal18c21970322f9e5c938bc954620c12bb; ?>
<?php unset($__componentOriginal18c21970322f9e5c938bc954620c12bb); ?>
<?php endif; ?><button id="find-sale" type="button" class="action-button action-detail">Ambil Data</button></div>
    <p id="reference-message" role="status" class="mt-3 text-sm text-gray-600">Masukkan kode lengkap, lalu klik Ambil Data.</p>
    <input type="hidden" id="sale_id_ref" name="sale_id_ref"><input type="hidden" id="product_id_ref" name="product_id_ref">
    <div id="reference-result" hidden class="mt-4 space-y-4">
        <label class="block text-sm font-medium">Barang pada nota<select id="reference-item" class="mt-1 block w-full rounded-md border-gray-300"></select></label>
        <div class="flex flex-wrap gap-5"><img id="reference-photo" hidden alt="Foto barang" style="width:140px;height:140px;object-fit:contain;border-radius:8px"><dl id="reference-data" class="grid flex-1 gap-3 text-sm sm:grid-cols-2"></dl></div>
    </div>
</section>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const source = document.getElementById(<?php echo json_encode($source, 15, 512) ?>);
    const nameField = document.getElementById(<?php echo json_encode($nameField, 15, 512) ?>);
    const code = document.getElementById('nomor_nota');
    const branch = document.getElementById('branch_id');
    const customer = document.getElementById('customer_id');
    const customerSearch = document.getElementById('customer-search');
    const result = document.getElementById('reference-result');
    const message = document.getElementById('reference-message');
    const picker = document.getElementById('reference-item');
    const button = document.getElementById('find-sale');
    const photo = document.getElementById('reference-photo');
    const submit = document.getElementById('save-note');
    const fields = [nameField, ...['kategori','kadar','berat'].map(id => document.getElementById(id)).filter(Boolean)];
    let sale = null, generation = 0;
    const money = value => new Intl.NumberFormat('id-ID', {style:'currency',currency:'IDR',maximumFractionDigits:0}).format(Number(value || 0));
    const invalidate = () => {
        generation++; sale = null; result.hidden = true;
        document.getElementById('sale_id_ref').value = '';
        document.getElementById('product_id_ref').value = '';
        submit.disabled = source.value === 'toko_sendiri';
    };
    const applyItem = () => {
        const item = sale?.items.find(item => String(item.product_id) === picker.value);
        if (!item?.product) return;
        const product = item.product;
        nameField.value = product.nama_barang;
        ['kategori','kadar'].forEach(id => {const field = document.getElementById(id); if (field) field.value = product[id] || '';});
        document.getElementById('berat').value = item.berat;
        const price = document.getElementById('harga_per_gram_beli');
        if (price) price.value = item.harga_per_gram;
        document.getElementById('sale_id_ref').value = sale.id;
        document.getElementById('product_id_ref').value = item.product_id;
        customer.value = String(sale.customer_id);
        if (customerSearch) customerSearch.value = sale.customer?.nama || '';
        if (branch?.type === 'hidden' && sale.branch_id) branch.value = String(sale.branch_id);
        const details = {'Kode barang':product.kode_barang,'Nama barang':product.nama_barang,'Kategori':product.kategori,'Kadar':product.kadar,'Berat':item.berat+' gram','Harga jual / gram':money(item.harga_per_gram),'Ongkos penjualan':money(item.ongkos),'Subtotal penjualan':money(item.subtotal),'Customer':sale.customer?.nama,'Telepon':sale.customer?.telepon,'Alamat':sale.customer?.alamat,'Deskripsi':product.deskripsi};
        const list = document.getElementById('reference-data'); list.replaceChildren();
        Object.entries(details).forEach(([label,value]) => {const div=document.createElement('div'),dt=document.createElement('dt'),dd=document.createElement('dd');dt.className='text-gray-500';dt.textContent=label;dd.className='font-semibold';dd.textContent=value || '—';div.append(dt,dd);list.append(div);});
        photo.hidden = true; photo.onload = () => photo.hidden = false; photo.onerror = () => photo.hidden = true;
        if (product.photo_url) photo.src = product.photo_url; else photo.removeAttribute('src');
        submit.disabled = false;
        document.getElementById('berat').dispatchEvent(new Event('input', {bubbles:true}));
    };
    const lookup = async () => {
        invalidate(); const token = generation;
        if (!code.value.trim()) {message.textContent='Masukkan kode nota penjualan.';return;}
        button.disabled=true;message.textContent='Mengambil data nota…';
        const url = new URL(<?php echo json_encode($endpoint, 15, 512) ?>, window.location.origin);
        url.searchParams.set('nomor_nota',code.value.trim());
        if (branch) url.searchParams.set('branch_id',branch.value);
        try {
            const response = await fetch(url,{headers:{Accept:'application/json'}});
            const data = await response.json();
            if (token !== generation) return;
            if (!response.ok) throw new Error(Object.values(data.errors || {}).flat()[0] || data.message || 'Nota tidak ditemukan.');
            sale=data;picker.replaceChildren();
            sale.items.filter(item=>item.product).forEach(item=>picker.add(new Option(item.product.kode_barang+' — '+item.product.nama_barang,item.product_id)));
            if (!picker.options.length) throw new Error('Nota ini tidak memiliki data barang.');
            const oldProduct = <?php echo json_encode((string)old('product_id_ref'), 15, 512) ?>;
            if (Array.from(picker.options).some(option=>option.value===oldProduct)) picker.value=oldProduct;
            result.hidden=false;message.textContent='Data berhasil diambil dari '+sale.nomor_nota+'.';applyItem();
        } catch(error) {if(token===generation){invalidate();message.textContent=error.message || 'Gagal mengambil data. Coba lagi.';}}
        finally {button.disabled=false;}
    };
    const toggle = () => {
        invalidate();const own=source.value==='toko_sendiri';
        document.getElementById('sale-reference').hidden=!own;code.required=own;
        fields.forEach(field=>field.readOnly=own);customer.style.pointerEvents=own?'none':'';
        if (customerSearch) { customerSearch.disabled = own; customerSearch.required = !own; customerSearch.setAttribute('aria-disabled', String(own)); }
        customer.tabIndex=own?-1:0;
        customer.setAttribute('aria-disabled',String(own));
        message.textContent='Masukkan kode lengkap, lalu klik Ambil Data.';
    };
    source.addEventListener('change',()=>{toggle();fields.forEach(field=>field.value='');customer.value='';if (customerSearch) customerSearch.value='';document.getElementById('berat').dispatchEvent(new Event('input',{bubbles:true}));});
    branch?.addEventListener('change',()=>{invalidate();message.textContent='Cabang berubah. Ambil ulang data nota.';});
    code.addEventListener('input',()=>{invalidate();message.textContent='Kode berubah. Klik Ambil Data.';});
    code.addEventListener('keydown',event=>{if(event.key==='Enter'){event.preventDefault();lookup();}});
    button.addEventListener('click',lookup);picker.addEventListener('change',applyItem);
    toggle();if(source.value==='toko_sendiri' && code.value) lookup();
});
</script>
<?php /**PATH D:\project\toko-emas-wijaya-kusuma\resources\views/komponen/sale-reference.blade.php ENDPATH**/ ?>