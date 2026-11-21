<section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
    <div
        class="flex flex-wrap items-start justify-between gap-4 rounded-t-2xl border-b border-slate-100 bg-gradient-to-r from-amber-50 to-white px-6 py-5">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[.16em] text-amber-700">Langkah 4</p>
            <h3 class="mt-1 text-lg font-semibold text-slate-900">Potongan & ringkasan</h3>
            <p class="mt-1 text-sm text-slate-500">Tambahkan potongan bila diperlukan, lalu periksa total pembayaran.
            </p>
        </div><button id="add-deduction" type="button" class="action-button action-detail">+ Tambah Potongan</button>
    </div>
    <div class="space-y-5 p-6">
        <div id="deduction-rows" class="space-y-3"></div>
        <p id="no-deductions" class="text-sm text-slate-500">Belum ada potongan tambahan.</p>
        <template id="deduction-template">
            <div class="deduction-row grid gap-3 rounded-lg border border-gray-200 bg-gray-50 p-4 sm:grid-cols-2">
                <label class="text-sm font-medium">Jenis potongan<select name="deduction_type_ids[]"
                        class="mt-1 block w-full rounded-md border-gray-300" required>
                        <option value="">Pilih jenis potongan</option><?php $__currentLoopData = $deductions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $deduction): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($deduction->id); ?>" data-nominal="<?php echo e($deduction->nominal); ?>">
                        <?php echo e($deduction->nama_potongan); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select></label>
                <label class="text-sm font-medium">Nominal (Rp)<input name="deduction_nominals[]" type="number" min="0"
                        step="0.01" value="0" class="mt-1 block w-full rounded-md border-gray-300" required></label>
                <label class="text-sm font-medium">Keterangan<input name="deduction_notes[]" maxlength="255"
                        class="mt-1 block w-full rounded-md border-gray-300" placeholder="Opsional"></label>
                <div class="flex items-end justify-end"><button type="button"
                        class="remove-deduction action-button action-delete">Hapus potongan</button></div>
            </div>
        </template>
        <div class="rounded-xl border border-slate-200 bg-slate-50 p-5" aria-live="polite">
            <h4 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-500">Ringkasan pembelian</h4>
            <dl class="grid gap-4 sm:grid-cols-3">
                <div>
                    <dt class="text-sm text-slate-500">Harga kotor</dt>
                    <dd id="gross-total" class="font-semibold text-slate-900">Rp 0</dd>
                </div>
                <div>
                    <dt class="text-sm text-slate-500">Total potongan</dt>
                    <dd id="deduction-total" class="font-semibold text-slate-900">Rp 0</dd>
                </div>
                <div>
                    <dt class="text-sm text-slate-500">Dibayar ke customer</dt>
                    <dd id="purchase-total" class="text-xl font-bold text-slate-900">Rp 0</dd>
                </div>
            </dl>
        </div>
        <p id="deduction-error" role="alert" class="text-sm text-red-600" hidden>Total potongan tidak boleh melebihi nilai barang.</p>
    </div>
</section>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const rows = document.getElementById('deduction-rows');
        const number = id => Number(document.getElementById(id).value) || 0;
        const money = value => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(value);
        const cents = value => Math.round((value + Number.EPSILON) * 100);
        const recalculate = () => {
            const gross = cents(number('berat') * number('harga_per_gram_beli'));
            const deduction = cents(number('berat') * number('potongan_per_gram')) + Array.from(rows.querySelectorAll('[name="deduction_nominals[]"]')).reduce((sum, input) => sum + cents(Number(input.value) || 0), 0);
            const excessive = deduction > gross;
            document.getElementById('harga_per_gram_beli').setCustomValidity(excessive ? 'Total potongan melebihi nilai barang.' : '');
            document.getElementById('potongan_per_gram').setCustomValidity(number('potongan_per_gram') > number('harga_per_gram_beli') ? 'Potongan per gram melebihi harga beli per gram.' : '');
            document.getElementById('deduction-error').hidden = !excessive;
            document.getElementById('gross-total').textContent = money(gross / 100); document.getElementById('deduction-total').textContent = money(deduction / 100); document.getElementById('purchase-total').textContent = money((gross - deduction) / 100); document.getElementById('no-deductions').hidden = rows.children.length > 0;
        };
        const add = (id = '', nominal = 0, note = '') => {
            const row = document.getElementById('deduction-template').content.firstElementChild.cloneNode(true);
            const select = row.querySelector('select'), amount = row.querySelector('[name="deduction_nominals[]"]');
            select.value = id; amount.value = nominal; row.querySelector('[name="deduction_notes[]"]').value = note;
            select.addEventListener('change', () => { amount.value = select.selectedOptions[0]?.dataset.nominal || 0; recalculate(); });
            row.querySelector('.remove-deduction').addEventListener('click', () => { row.remove(); recalculate(); });
            rows.append(row); recalculate();
        };
        document.getElementById('add-deduction').addEventListener('click', () => { add(); rows.lastElementChild.querySelector('select').focus(); });
        document.querySelector('form[action="<?php echo e(route('purchases.store')); ?>"]').addEventListener('input', recalculate);
        const ids = <?php echo json_encode(old('deduction_type_ids', []), 512) ?>, amounts = <?php echo json_encode(old('deduction_nominals', []), 512) ?>, notes = <?php echo json_encode(old('deduction_notes', []), 512) ?>;
        ids.forEach((id, index) => add(id, amounts[index] || 0, notes[index] || '')); recalculate();
    });
</script>
<?php /**PATH D:\project\toko-emas-wijaya-kusuma\resources\views\pembelian\deductions.blade.php ENDPATH**/ ?>