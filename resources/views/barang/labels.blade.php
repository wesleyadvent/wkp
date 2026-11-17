<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Tag Barcode Lipat | Wijaya Kusuma</title>
    <style id="thermal-page-style">
        @page {
            size: 58mm 40mm;
            margin: 0;
        }
    </style>
    <style>
        :root {
            --ink: #172033;
            --muted: #64748b;
            --line: #dce1e8;
            --thermal-width: 58mm;
            --thermal-height: 40mm;
            --tag-body-width: 24mm;
            --hook-width: 34mm;
            --barcode-width: 20mm;
            --tag-padding: 2mm;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: #f3f5f8;
            color: var(--ink);
            font: 14px/1.5 Arial, sans-serif;
        }

        button,
        select {
            font: inherit;
        }

        button {
            cursor: pointer;
        }

        a {
            color: inherit;
            text-decoration: none;
        }

        .page {
            max-width: 1040px;
            margin: 0 auto;
            padding: 32px 24px 56px;
        }

        .back {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: var(--muted);
            font-size: 13px;
        }

        .back:hover {
            color: var(--ink);
        }

        .hero {
            display: flex;
            align-items: end;
            justify-content: space-between;
            gap: 24px;
            margin: 22px 0;
        }

        .eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            border: 1px solid #ccebd6;
            border-radius: 99px;
            padding: 5px 11px;
            background: #eaf8ef;
            color: #166534;
            font-size: 12px;
            font-weight: 700;
        }

        h1 {
            margin: 11px 0 6px;
            font-size: 28px;
            line-height: 1.15;
            letter-spacing: -.6px;
        }

        .description {
            max-width: 620px;
            margin: 0;
            color: var(--muted);
        }

        .actions {
            display: flex;
            align-items: end;
            gap: 10px;
            flex-wrap: wrap;
        }

        .format-control {
            display: grid;
            gap: 5px;
            color: var(--muted);
            font-size: 12px;
            font-weight: 600;
        }

        select,
        .button {
            min-height: 42px;
            border: 1px solid var(--line);
            border-radius: 9px;
            padding: 0 13px;
            background: white;
            color: var(--ink);
            font-weight: 600;
            box-shadow: 0 1px 2px #00000008;
        }

        .button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .button:hover,
        select:hover {
            background: #fafbfc;
        }

        .button-primary {
            border-color: var(--ink);
            background: var(--ink);
            color: #fff;
        }

        .button-primary:hover {
            background: #2b3b55;
        }

        .button:focus-visible,
        select:focus-visible,
        .back:focus-visible {
            outline: 3px solid #d4a54b;
            outline-offset: 3px;
        }

        .icon {
            width: 18px;
            height: 18px;
        }

        .summary {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 14px;
            margin-bottom: 24px;
        }

        .stat {
            border: 1px solid #e3e7ed;
            border-radius: 12px;
            padding: 15px 18px;
            background: white;
        }

        .stat span {
            display: block;
            color: var(--muted);
            font-size: 12px;
        }

        .stat strong {
            display: block;
            margin-top: 4px;
            font-size: 18px;
        }

        .preview {
            overflow: hidden;
            border: 1px solid #e1e5eb;
            border-radius: 15px;
            background: white;
            box-shadow: 0 3px 12px #15203608;
        }

        .preview-heading {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            padding: 18px 22px;
            border-bottom: 1px solid #edf0f4;
        }

        .preview-heading h2 {
            margin: 0;
            font-size: 16px;
        }

        .preview-heading p {
            margin: 3px 0 0;
            color: var(--muted);
            font-size: 12px;
        }

        .paper-badge {
            border-radius: 6px;
            padding: 5px 9px;
            background: #f2f4f7;
            color: var(--muted);
            font-size: 12px;
            white-space: nowrap;
        }

        .thermal-stage {
            overflow: auto;
            padding: 30px;
            background: linear-gradient(135deg, #eef1f5, #f8fafc);
        }

        .thermal-paper {
            display: grid;
            width: var(--thermal-width);
            margin: 0 auto;
            background: white;
            box-shadow: 0 7px 20px #17203322;
        }

        .label {
            position: relative;
            display: grid;
            grid-template-rows: 1fr 4mm 1fr;
            width: var(--thermal-width);
            height: var(--thermal-height);
            background: #fff;
            color: #000;
            break-inside: avoid;
            page-break-inside: avoid;
        }

        .label+.label {
            border-top: 1px solid #cbd5e1;
        }

        .tag-side {
            display: flex;
            flex-direction: column;
            align-items: center;
            width: var(--tag-body-width);
            min-height: 0;
            margin-left: auto;
            padding: 2mm var(--tag-padding);
            border-right: .25mm solid #111;
            border-left: .25mm solid #111;
            text-align: center;
        }

        .weight-side {
            border-top: .25mm solid #111;
        }

        .barcode-side {
            border-bottom: .25mm solid #111;
        }

        .tag-name {
            display: -webkit-box;
            overflow: hidden;
            -webkit-box-orient: vertical;
            -webkit-line-clamp: 2;
            max-width: 100%;
            font-size: 7px;
            font-weight: 800;
            line-height: 1.15;
            overflow-wrap: anywhere;
        }

        .side-caption {
            margin-top: auto;
            color: #3f3f46;
            font-size: 4.5px;
            font-weight: 800;
            letter-spacing: .8px;
        }

        .weight {
            margin: .8mm 0 auto;
            font-size: 11px;
            font-weight: 900;
            letter-spacing: -.5px;
            line-height: 1;
        }

        .weight small {
            font-size: 6px;
            letter-spacing: 0;
        }

        .weight-meta {
            max-width: 100%;
            color: #333;
            font-size: 5px;
            line-height: 1.2;
            overflow-wrap: anywhere;
        }

        .fold-line {
            display: flex;
            align-items: center;
            justify-content: center;
            width: var(--tag-body-width);
            margin-left: auto;
            border-top: .25mm dashed #475569;
            border-bottom: .25mm dashed #475569;
            color: #475569;
            font-size: 6px;
            font-weight: 800;
            letter-spacing: 1px;
        }

        .hook-tab {
            position: absolute;
            z-index: 2;
            top: calc(50% - 3mm);
            left: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            width: calc(var(--hook-width) + .3mm);
            height: 6mm;
            border: .25mm solid #111;
            border-right: 0;
            background: #fff;
            font-size: 5px;
            font-weight: 800;
            letter-spacing: .35px;
        }

        .barcode-side {
            justify-content: flex-start;
        }

        .barcode-side .tag-name {
            -webkit-line-clamp: 1;
            margin-bottom: .5mm;
        }

        .barcode {
            width: var(--barcode-width);
            margin: 0 auto;
        }

        .barcode svg {
            display: block;
            width: 100%;
            height: auto;
        }

        .barcode svg text {
            display: none;
        }

        .barcode-code {
            margin-top: .3mm;
            font-family: ui-monospace, Consolas, monospace;
            font-size: 5px;
            letter-spacing: 0;
        }

        .branch {
            margin-top: auto;
            color: #333;
            font-size: 5px;
        }

        .hint {
            margin: 16px 0 0;
            color: var(--muted);
            font-size: 12px;
        }

        .hint strong {
            color: #334155;
        }

        .thermal-standard {
            --thermal-width: 64mm;
            --thermal-height: 44mm;
            --tag-body-width: 28mm;
            --hook-width: 36mm;
            --barcode-width: 22mm;
            --tag-padding: 3mm;
        }

        .thermal-standard .weight {
            font-size: 12px;
        }

        .thermal-standard .tag-name {
            font-size: 7px;
        }

        .thermal-standard .weight-meta,
        .thermal-standard .barcode-code {
            font-size: 5px;
        }

        @media(max-width:700px) {
            .page {
                padding: 20px 14px 36px;
            }

            .hero {
                align-items: flex-start;
                flex-direction: column;
            }

            .actions {
                width: 100%;
            }

            .format-control {
                flex: 1;
            }

            .format-control select {
                width: 100%;
            }

            .button {
                flex: 1;
            }

            h1 {
                font-size: 24px;
            }

            .summary {
                gap: 8px;
            }

            .stat {
                padding: 12px;
            }

            .stat strong {
                font-size: 15px;
            }

            .thermal-stage {
                padding: 20px 8px;
            }

            .preview-heading {
                padding: 15px;
            }

            .paper-badge {
                display: none;
            }
        }

        @media print {
            body {
                width: var(--thermal-width);
                background: #fff;
                color: #000;
            }

            .controls {
                display: none !important;
            }

            .page {
                width: var(--thermal-width);
                max-width: none;
                margin: 0;
                padding: 0;
            }

            .preview {
                overflow: visible;
                border: 0;
                border-radius: 0;
                box-shadow: none;
            }

            .thermal-stage {
                overflow: visible;
                padding: 0;
                background: #fff;
            }

            .thermal-paper {
                width: var(--thermal-width);
                margin: 0;
                box-shadow: none;
            }

            .label {
                break-after: page;
                page-break-after: always;
            }

            .label+.label {
                border-top: 0;
            }

            .label:last-child {
                break-after: auto;
                page-break-after: auto;
            }
        }
    </style>
</head>

<body class="thermal-58">
    <main class="page">
        <div class="controls">
            <a class="back" href="{{ route('products.index', ['branch_id' => $products->first()?->branch_id]) }}"><span
                    aria-hidden="true">&larr;</span> Kembali ke daftar barang</a>
            <header class="hero">
                <div><span class="eyebrow"><span aria-hidden="true">&#10003;</span> Tag siap dicetak</span>
                    <h1>Tag barcode dengan pengait</h1>
                    <p class="description">Model tag perhiasan dengan lidah pengait panjang di sisi kiri. Bagian kanan
                        dilipat: satu sisi berat dan satu sisi barcode ringkas.</p>
                </div>
                <div class="actions"><label class="format-control" for="thermal-format">Ukuran tag<select
                            id="thermal-format" aria-label="Ukuran tag">
                            <option value="compact" selected>Kompak 58 mm x 40 mm (pengait panjang)</option>
                            <option value="standard">Standar 64 mm x 44 mm</option>
                        </select></label><button class="button button-primary" type="button"
                        onclick="window.print()"><svg class="icon" aria-hidden="true" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="1.8">
                            <path d="M6 9V3h12v6M6 17H3V9h18v8h-3M6 14h12v7H6zM17 11h1" />
                        </svg>Cetak tag barcode</button></div>
            </header>
            <div class="summary">
                <div class="stat"><span>Jumlah tag</span><strong>{{ $products->count() }} unit</strong></div>
                <div class="stat">
                    <span>Cabang</span><strong>{{ $products->pluck('branch.nama_cabang')->unique()->join(', ') }}</strong>
                </div>
                <div class="stat"><span>Total
                        berat</span><strong>{{ number_format($products->sum('berat'), 3, ',', '.') }} g</strong></div>
            </div>
        </div>
        <section class="preview" aria-label="Pratinjau tag barcode lipat">
            <div class="preview-heading controls">
                <div>
                    <h2>Pratinjau tag</h2>
                    <p>Lidah panjang di kiri menjadi pengait; bagian utama dilipat di garis putus-putus.</p>
                </div><span class="paper-badge" id="paper-badge">58 mm x 40 mm</span>
            </div>
            <div class="thermal-stage">
                <div class="thermal-paper">
                    @foreach($products as $product)
                        <article class="label">
                            <section class="tag-side weight-side">
                                <div class="tag-name">{{ $product->nama_barang }}</div>
                                <div class="side-caption">BERAT BARANG</div>
                                <div class="weight">{{ number_format($product->berat, 3, ',', '.') }} <small>g</small></div>
                                <div class="weight-meta">{{ $product->kadar }} &bull; {{ $product->branch?->nama_cabang }}
                                </div>
                            </section>
                            <div class="fold-line">LIPAT</div>
                            <section class="tag-side barcode-side">
                                <div class="tag-name">{{ $product->nama_barang }}</div>
                                <div class="barcode">{!! \App\Support\ProductCode::svg($product->kode_barang) !!}</div>
                                <div class="barcode-code">{{ $product->kode_barang }}</div>
                            </section><span class="hook-tab" aria-label="Pengait tag">PENGAIT</span>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>
        <p class="hint controls"><strong>Petunjuk:</strong> pilih ukuran yang sama di dialog printer, skala 100%, dan
            nonaktifkan header/footer. Gunakan kertas tag khusus yang sudah die-cut sesuai bentuk pengait, lalu lipat
            bagian utama di garis putus-putus.</p>
    </main>
    <script>
        (() => {
            const format = document.getElementById('thermal-format');
            const pageStyle = document.getElementById('thermal-page-style');
            const badge = document.getElementById('paper-badge');
            const sizes = { compact: { width: 58, height: 40 }, standard: { width: 64, height: 44 } };
            const setFormat = () => { const size = sizes[format.value]; document.body.classList.toggle('thermal-standard', format.value === 'standard'); pageStyle.textContent = `@page { size: ${size.width}mm ${size.height}mm; margin: 0; }`; badge.textContent = `${size.width} mm x ${size.height} mm`; };
            format.addEventListener('change', setFormat); setFormat();
        })();
    </script>
</body>

</html>
