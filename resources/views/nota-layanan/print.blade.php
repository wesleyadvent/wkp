<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Nota Service {{ $note->nomor_nota }}</title>
    <style>
        :root { --paper-width:80mm; --pad:4mm; } * { box-sizing:border-box; } body { margin:0; background:#eef2f7; color:#111; font:13px/1.42 Arial,Helvetica,sans-serif; } button,select { font:inherit; }
        .toolbar { display:flex; align-items:end; justify-content:center; gap:10px; padding:18px; color:#475569; font-size:12px; }.format { display:grid; gap:4px; font-weight:700; }.format select,.print { min-height:40px; border:1px solid #d9dee7; border-radius:8px; padding:0 12px; background:white; font-weight:700; }.print { border-color:#172033; background:#172033; color:#fff; cursor:pointer; }.print:hover { background:#2d405f; }.print:focus-visible,select:focus-visible { outline:3px solid #d4a54b; outline-offset:2px; }
        .receipt { width:var(--paper-width); margin:0 auto 30px; padding:var(--pad); background:#fff; box-shadow:0 8px 24px #17203320; overflow-wrap:anywhere; }.brand { padding-bottom:3mm; border-bottom:.3mm dashed #111; text-align:center; }.brand-name { font-size:16px; font-weight:900; letter-spacing:.35px; }.branch { margin-top:1mm; font-size:11px; }.type { margin-top:1.5mm; font-size:10px; font-weight:800; letter-spacing:1.6px; }
        .note-number { margin:3mm 0 1mm; text-align:center; font-size:15px; font-weight:900; letter-spacing:.5px; }.status { display:block; margin:auto; width:max-content; max-width:100%; border:.25mm solid #111; padding:.8mm 2.4mm; font-size:10px; font-weight:800; text-transform:uppercase; }.date { margin-top:2mm; text-align:center; font-size:10px; }
        .section { padding:3mm 0; border-bottom:.25mm dashed #111; }.section-title { margin-bottom:1.6mm; font-size:10px; font-weight:900; letter-spacing:1px; text-transform:uppercase; }.info { display:grid; grid-template-columns:29mm 1fr; gap:1mm 3mm; font-size:11px; }.info dt { color:#555; }.info dd { margin:0; text-align:right; font-weight:700; }.info dd.left { text-align:left; }
        .service-item { padding:2.5mm 0; border-top:.2mm dotted #9ca3af; }.service-item:first-of-type { border-top:0; padding-top:0; }.item-top { display:flex; justify-content:space-between; gap:7px; font-size:12px; font-weight:800; }.item-price { text-align:right; white-space:nowrap; }.item-description { margin-top:1mm; color:#4b5563; font-size:11px; }.empty { color:#555; font-size:11px; }.total { display:flex; justify-content:space-between; align-items:baseline; gap:10px; padding-top:2.5mm; font-size:13px; font-weight:900; }.total strong { font-size:16px; white-space:nowrap; }
        .notice { margin-top:3mm; padding:2.5mm; border:.25mm solid #111; font-size:10px; line-height:1.45; }.footer { padding-top:3mm; text-align:center; color:#444; font-size:10px; line-height:1.55; }.footer strong { color:#111; }
        .thermal-58 { --paper-width:58mm; --pad:3.5mm; }.thermal-58 .info { grid-template-columns:22mm 1fr; gap:1mm 2mm; font-size:10px; }.thermal-58 .branch { font-size:10px; }.thermal-58 .brand-name { font-size:14px; }.thermal-58 .item-top { font-size:11px; }.thermal-58 .item-description { font-size:10px; }
        @media print { body { width:var(--paper-width); background:#fff; }.toolbar { display:none!important; }.receipt { width:var(--paper-width); min-height:0; margin:0; box-shadow:none; } @page { margin:0; } }
    </style>
</head>
<body>
    <div class="toolbar">
        <label class="format">Lebar kertas thermal<select id="thermal-format" aria-label="Lebar kertas thermal"><option value="80" selected>80 mm (disarankan)</option><option value="58">58 mm</option></select></label>
        <button class="print" type="button" onclick="window.print()">Cetak nota service</button>
    </div>
    <main class="receipt" aria-label="Nota service {{ $note->nomor_nota }}">
        <header class="brand"><div class="brand-name">TOKO EMAS WIJAYA KUSUMA</div><div class="branch">{{ $note->receipt('branch')?->nama_cabang ?? '-' }}</div><div class="type">NOTA SERVICE</div></header>
        <div class="note-number">{{ $note->nomor_nota }}</div><span class="status">{{ str_replace('_', ' ', $note->status_service) }}</span><div class="date">Dibuat {{ $note->created_at?->format('d/m/Y H:i') ?? '-' }}</div>
        <section class="section"><div class="section-title">Data customer</div><dl class="info"><dt>Nama</dt><dd>{{ $note->receipt('customer')?->nama ?? '-' }}</dd><dt>Telepon</dt><dd>{{ $note->receipt('customer')?->telepon ?? '-' }}</dd><dt>Alamat</dt><dd>{{ $note->receipt('customer')?->alamat ?? '-' }}</dd></dl></section>
        <section class="section"><div class="section-title">Barang service</div><dl class="info"><dt>Nama barang</dt><dd>{{ $note->receipt('product')?->nama_barang ?? $note->nama_barang_manual ?? '-' }}</dd><dt>Kode barang</dt><dd>{{ $note->receipt('product')?->kode_barang ?? '-' }}</dd><dt>Berat</dt><dd>{{ $note->berat ? number_format($note->berat, 3, ',', '.') . ' gram' : '-' }}</dd><dt>Estimasi selesai</dt><dd>{{ $note->estimasi_selesai?->format('d/m/Y') ?? 'Belum ditentukan' }}</dd></dl></section>
        <section class="section"><div class="section-title">Rincian pengerjaan</div>@forelse($note->items as $index => $item)<article class="service-item"><div class="item-top"><span>{{ $index + 1 }}. {{ $item->receipt('serviceType')?->nama_service ?? '-' }}</span><span class="item-price">Rp {{ number_format($item->harga_service, 2, ',', '.') }}</span></div><div class="item-description">{{ $item->keterangan ?: '-' }}</div></article>@empty<div class="empty">Belum ada rincian service.</div>@endforelse<div class="total"><span>Total service</span><strong>Rp {{ number_format($note->harga_service, 2, ',', '.') }}</strong></div></section>
        <div class="notice"><strong>Catatan pengambilan:</strong> Bawa nota ini atau sebutkan nomor nota <strong>{{ $note->nomor_nota }}</strong> saat mengambil barang. Barang diserahkan setelah status dinyatakan selesai.</div>
        <footer class="footer">Dibuat oleh: <strong>{{ $note->employee_name ?? '-' }}</strong><br>Dokumen dicetak {{ now()->format('d/m/Y H:i') }}</footer>
    </main>
    <script>document.getElementById('thermal-format').addEventListener('change', (event) => document.body.classList.toggle('thermal-58', event.target.value === '58'));</script>
</body>
</html>
