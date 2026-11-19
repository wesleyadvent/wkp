<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Nota Penjualan {{ $sale->nomor_nota }}</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; padding: 24px; background: #f1f5f9; color: #172033; font-family: Arial, sans-serif; font-size: 10px; line-height: 1.35; }
        .toolbar { max-width: 210mm; margin: 0 auto 18px; display: flex; align-items: center; gap: 16px; font-size: 13px; }
        button { padding: 12px 20px; border: 0; border-radius: 8px; background: #172033; color: #fff; font-weight: bold; cursor: pointer; }
        button:focus-visible { outline: 3px solid #6366f1; outline-offset: 3px; }
        .receipt { width: 210mm; min-height: 148mm; margin: auto; padding: 8mm; background: #fff; box-shadow: 0 8px 24px #17203320; overflow-wrap: anywhere; }
        .receipt-header { display: flex; align-items: center; gap: 4mm; padding-bottom: 2mm; border-bottom: 1px solid #8b6914; break-inside: avoid; }
        .store-logo { width: 20mm; height: 20mm; object-fit: contain; flex-shrink: 0; }
        .store-heading { min-width: 0; }
        .store-heading strong { display: block; font-size: 17px; }
        .store-heading span { display: block; margin-top: 1mm; font-size: 10px; letter-spacing: 1px; }
        h1 { margin: 1mm 0 0; font-size: 15px; }
        h3 { margin: 2.5mm 0 1.5mm; font-size: 11px; break-after: avoid; }
        dl { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 1.5mm 4mm; margin: 0; }
        dl > div { min-width: 0; break-inside: avoid; }
        dt { color: #555; font-size: 8px; text-transform: uppercase; }
        dd { margin: .5mm 0 0; font-size: 10px; }
        table { width: 100%; border-collapse: collapse; table-layout: fixed; font-size: 9px; }
        th, td { padding: 1.5mm; text-align: left; vertical-align: top; border-bottom: 1px solid #ccc; }
        th { background: #f3f4f6; font-size: 8px; }
        th:first-child { width: 28%; }
        th:nth-child(2) { width: 14%; }
        th:nth-child(3) { width: 12%; }
        th:nth-child(4), th:nth-child(5) { width: 15%; }
        th:last-child { width: 16%; }
        .table-number { text-align: right; font-variant-numeric: tabular-nums; }
        tr { break-inside: avoid; }
        thead { display: table-header-group; }
        td img { float: left; width: 16mm !important; height: 16mm !important; margin: 0 2mm 1mm 0 !important; border-radius: 2px !important; }
        td p { margin: 1mm 0 0; }
        td span { color: #555; font-size: 8px; }
        .receipt > dl:last-child { padding-top: 2mm; border-top: 1px solid #8b6914; break-inside: avoid; }
        .receipt > dl:last-child > div:last-child dd { font-size: 14px; font-weight: bold; }
        @page { size: A5 landscape; margin: 8mm; }
        @media print {
            body { padding: 0; background: #fff; color: #000; }
            .toolbar { display: none; }
            .receipt { width: 100%; min-height: 0; margin: 0; padding: 0; box-shadow: none; }
            .overflow-x-auto, .table-scroll { overflow: visible; }
        }
        @media screen and (max-width: 850px) {
            body { padding: 12px; }
            .toolbar { flex-wrap: wrap; }
            .receipt { width: 100%; min-height: 0; padding: 5mm; }
            .table-scroll { overflow-x: auto; }
            table { min-width: 180mm; }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <button type="button" onclick="window.print()">Cetak / Simpan PDF</button>
        <span>A5 landscape · 210 × 148 mm (setengah A4). Gunakan skala 100% dan nonaktifkan header/footer browser.</span>
    </div>
    @include('transaksi.receipt', ['transaction' => $sale, 'kind' => 'sales', 'printLayout' => true])
    <script>window.addEventListener('load', () => window.print());</script>
</body>
</html>
