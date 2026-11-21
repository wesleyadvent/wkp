<!DOCTYPE html>
<html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Nota <?php echo e($transaction->nomor_nota); ?></title>
<style>
body{font-family:Arial,sans-serif;color:#172033;background:#f1f5f9;margin:0;padding:24px;line-height:1.5}.receipt,.toolbar{max-width:1000px;margin:0 auto}.receipt{background:white;padding:32px;border:1px solid #ddd;border-radius:12px}h1{font-size:24px}h3{margin:24px 0 12px}dl{display:grid;grid-template-columns:1fr 1fr;gap:14px}dt{font-size:11px;text-transform:uppercase;color:#555}dd{margin:3px 0;font-size:13px;overflow-wrap:anywhere}table{width:100%;border-collapse:collapse;font-size:12px}th,td{text-align:left;padding:10px;border-bottom:1px solid #ddd}th{background:#f3f4f6}tr{break-inside:avoid}.toolbar{display:flex;align-items:center;gap:16px;margin-bottom:18px}button{border:0;border-radius:8px;background:#172033;color:white;padding:12px 20px;font-weight:bold;cursor:pointer}button:focus-visible{outline:3px solid #6366f1;outline-offset:3px}.overflow-x-auto{overflow:auto}
@page{size:A4;margin:12mm}@media print{body{background:white;padding:0}.toolbar{display:none}.receipt{border:0;padding:0;border-radius:0;max-width:none}thead{display:table-header-group}.overflow-x-auto{overflow:visible}h3{break-after:avoid}}@media(max-width:600px){body{padding:12px}.receipt{padding:16px}dl{grid-template-columns:1fr}}
</style></head><body>
<div class="toolbar"><button type="button" onclick="window.print()">Cetak / Simpan PDF</button><span>Gunakan dialog cetak untuk memilih printer atau menyimpan PDF.</span></div>
<?php echo $__env->make('transaksi.receipt', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<script>window.addEventListener('load', () => window.print());</script>
</body></html>
<?php /**PATH D:\project\toko-emas-wijaya-kusuma\resources\views\transaksi\print.blade.php ENDPATH**/ ?>