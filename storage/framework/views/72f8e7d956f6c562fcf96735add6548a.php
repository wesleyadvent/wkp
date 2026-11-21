<div class="rounded-lg border border-indigo-200 bg-indigo-50 p-4">
    <?php if (isset($component)) { $__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => '92edfda80dc3faed2cda654b45151250::input-label','data' => ['for' => 'employee_name','value' => 'Nama karyawan pembuat nota *']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('input-label'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['for' => 'employee_name','value' => 'Nama karyawan pembuat nota *']); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => '92edfda80dc3faed2cda654b45151250::text-input','data' => ['id' => 'employee_name','name' => 'employee_name','value' => old('employee_name'),'class' => 'mt-2 block w-full','placeholder' => 'Ketik nama lengkap Anda','autocomplete' => 'off','required' => true,'maxlength' => '255','ariaDescribedby' => 'employee-verification-status']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('text-input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['id' => 'employee_name','name' => 'employee_name','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(old('employee_name')),'class' => 'mt-2 block w-full','placeholder' => 'Ketik nama lengkap Anda','autocomplete' => 'off','required' => true,'maxlength' => '255','aria-describedby' => 'employee-verification-status']); ?>
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
    <p id="employee-verification-status" role="status" aria-live="polite" class="mt-2 text-sm text-gray-600">Isi nama karyawan aktif dengan benar untuk membuka form.</p>
    <?php if (isset($component)) { $__componentOriginalf94ed9c5393ef72725d159fe01139746 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalf94ed9c5393ef72725d159fe01139746 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => '92edfda80dc3faed2cda654b45151250::input-error','data' => ['messages' => $errors->get('employee_name'),'class' => 'mt-2']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('input-error'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['messages' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($errors->get('employee_name')),'class' => 'mt-2']); ?>
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
<script>
document.addEventListener('DOMContentLoaded', () => {
    const input = document.getElementById('employee_name');
    const fields = document.getElementById('note-fields');
    const status = document.getElementById('employee-verification-status');
    const form = input.closest('form');
    let timer, generation = 0, verifiedName = null, pending;
    const lock = () => {
        fields.disabled = true;
        verifiedName = null;
        generation++;
        pending?.abort();
        clearTimeout(timer);
    };
    const verify = async () => {
        const name = input.value;
        if (!name.trim()) return;
        const version = generation;
        pending = new AbortController();
        status.textContent = 'Memeriksa nama karyawan…';
        try {
            const response = await fetch(<?php echo json_encode(route('employees.verify'), 15, 512) ?>, {
                method: 'POST', signal: pending.signal,
                headers: {'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content},
                body: JSON.stringify({employee_name: name}),
            });
            const data = await response.json();
            if (version !== generation || input.value !== name) return;
            if (!response.ok || data.valid !== true) {
                status.textContent = data.errors?.employee_name?.[0] || 'Nama belum dapat diverifikasi. Periksa nama atau coba kembali.';
                input.setAttribute('aria-invalid', 'true');
                return;
            }
            verifiedName = name;
            fields.disabled = false;
            input.setAttribute('aria-invalid', 'false');
            status.textContent = 'Nama karyawan terverifikasi. Silakan isi nota.';
        } catch (error) {
            if (error.name !== 'AbortError' && version === generation) status.textContent = 'Verifikasi gagal. Periksa koneksi, lalu ketik ulang nama untuk mencoba kembali.';
        }
    };
    input.addEventListener('input', () => {
        lock();
        input.removeAttribute('aria-invalid');
        status.textContent = 'Isi nama karyawan aktif dengan benar untuk membuka form.';
        if (input.value.trim()) timer = setTimeout(verify, 500);
    });
    input.addEventListener('keydown', event => {
        if (event.key === 'Enter' && fields.disabled) {event.preventDefault();clearTimeout(timer);verify();}
    });
    form.addEventListener('submit', event => {
        if (fields.disabled || verifiedName !== input.value) {event.preventDefault();lock();input.focus();verify();}
    });
    lock();
    if (input.value.trim()) verify();
});
</script>
<?php /**PATH D:\project\toko-emas-wijaya-kusuma\resources\views\komponen\employee-select.blade.php ENDPATH**/ ?>