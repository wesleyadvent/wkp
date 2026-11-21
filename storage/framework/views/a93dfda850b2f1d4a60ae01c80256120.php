<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo e($title); ?> - <?php echo e(config('app.name')); ?></title>
    <style>
        :root {
            --ink: #172033;
            --muted: #64748b;
            --line: #dbe3ec;
            --soft: #f5f7fa;
            --accent: #b7791f
        }

        * {
            box-sizing: border-box
        }

        body {
            margin: 0;
            background: #eef2f6;
            color: var(--ink);
            font: 13px/1.55 Arial, sans-serif
        }

        .page {
            max-width: 1440px;
            margin: 0 auto;
            padding: 28px
        }

        .toolbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            margin-bottom: 18px
        }

        .toolbar button {
            border: 0;
            border-radius: 8px;
            background: var(--ink);
            color: #fff;
            padding: 11px 18px;
            font-weight: 700;
            cursor: pointer
        }

        .toolbar span {
            color: var(--muted);
            font-size: 12px
        }

        .sheet {
            background: #fff;
            border: 1px solid var(--line);
            border-radius: 14px;
            box-shadow: 0 8px 26px #17203312;
            overflow: hidden
        }

        .report-head {
            display: flex;
            justify-content: space-between;
            gap: 24px;
            padding: 28px 30px 22px;
            border-bottom: 1px solid var(--line)
        }

        .eyebrow {
            margin: 0 0 5px;
            color: var(--accent);
            font-size: 11px;
            font-weight: 700;
            letter-spacing: .14em;
            text-transform: uppercase
        }

        .report-head h1 {
            margin: 0;
            font-size: 24px;
            line-height: 1.2
        }

        .report-head p {
            margin: 6px 0 0;
            color: var(--muted)
        }

        .brand {
            text-align: right;
            font-weight: 700
        }

        .brand small {
            display: block;
            color: var(--muted);
            font-weight: 400;
            margin-top: 3px
        }

        .meta {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
            padding: 18px 30px;
            background: var(--soft);
            border-bottom: 1px solid var(--line)
        }

        .meta-card {
            padding: 11px 13px;
            border: 1px solid var(--line);
            border-radius: 9px;
            background: #fff
        }

        .meta-card span {
            display: block;
            color: var(--muted);
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: .08em
        }

        .meta-card strong {
            display: block;
            margin-top: 3px;
            font-size: 13px
        }

        .report-body {
            padding: 0 30px 28px;
            overflow-x: auto
        }

        .report-table {
            width: 100%;
            min-width: 1050px;
            border-collapse: collapse;
            margin-top: 22px
        }

        .report-table th,
        .report-table td {
            padding: 11px 10px;
            border-bottom: 1px solid var(--line);
            text-align: left;
            vertical-align: top;
            overflow-wrap: anywhere
        }

        .report-table th {
            background: var(--ink);
            color: #fff;
            font-size: 10px;
            letter-spacing: .06em;
            text-transform: uppercase
        }

        .report-table tbody tr:nth-child(even) {
            background: #fbfcfd
        }

        .report-table tbody tr {
            break-inside: avoid
        }

        .report-table td:nth-child(3) {
            min-width: 260px
        }

        .report-table .table-number {
            text-align: right;
            white-space: nowrap;
            font-variant-numeric: tabular-nums
        }

        .table-item {
            margin-bottom: 9px;
            padding: 9px;
            border: 1px solid var(--line);
            border-radius: 7px;
            background: #fff;
            font-size: 11px
        }

        .table-item:last-child {
            margin-bottom: 0
        }

        .status-badge {
            display: inline-flex;
            padding: 3px 8px;
            border-radius: 999px;
            font-size: 10px;
            font-weight: 700;
            background: #edf2f7;
            color: #475569
        }

        .status-badge-success {
            background: #e8f7ed;
            color: #187344
        }

        .status-badge-warning {
            background: #fff6dc;
            color: #94600b
        }

        .status-badge-muted {
            background: #edf2f7;
            color: #64748b
        }

        .status-badge-info {
            background: #eaf1ff;
            color: #345b9d
        }

        .report-summary {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
            margin: 22px 0 0;
            padding: 16px 0 0;
            border-top: 2px solid var(--ink)
        }

        .summary-item span {
            display: block;
            color: var(--muted);
            font-size: 11px
        }

        .summary-item strong {
            font-size: 16px
        }

        .report-note {
            grid-column: 1/-1;
            color: var(--muted);
            font-size: 11px
        }

        @page {
            size: A4 landscape;
            margin: 10mm
        }

        @media print {
            body {
                background: #fff;
                font-size: 9px
            }

            .page {
                max-width: none;
                padding: 0
            }

            .toolbar {
                display: none
            }

            .sheet {
                border: 0;
                border-radius: 0;
                box-shadow: none
            }

            .report-head {
                padding: 0 0 12px
            }

            .report-head h1 {
                font-size: 18px
            }

            .meta {
                padding: 10px 0;
                gap: 8px;
                background: #fff
            }

            .meta-card {
                padding: 7px 9px
            }

            .report-body {
                padding: 0;
                overflow: visible
            }

            .report-table {
                min-width: 0;
                margin-top: 12px
            }

            .report-table th,
            .report-table td {
                padding: 6px 5px;
                font-size: 8px
            }

            .report-table th {
                font-size: 8px
            }

            .report-table td:nth-child(3) {
                min-width: 0
            }

            .table-item {
                padding: 5px;
                font-size: 8px
            }

            .report-summary {
                margin-top: 12px;
                gap: 8px;
                padding-top: 9px
            }

            .summary-item strong {
                font-size: 11px
            }

            .report-note {
                font-size: 8px
            }

            .brand {
                font-size: 11px
            }
        }

        @media screen and (max-width:700px) {
            .page {
                padding: 12px
            }

            .toolbar {
                align-items: flex-start;
                flex-direction: column
            }

            .report-head {
                padding: 20px;
                flex-direction: column
            }

            .brand {
                text-align: left
            }

            .meta {
                grid-template-columns: 1fr;
                padding: 14px 20px
            }

            .report-body {
                padding: 0 20px 20px
            }

            .report-summary {
                grid-template-columns: 1fr
            }

            .report-note {
                grid-column: auto
            }
        }

        .summary-card {
            padding: 10px 12px;
            border: 1px solid var(--line);
            border-radius: 8px;
            background: var(--soft)
        }

        .summary-card span,
        .summary-card small {
            display: block;
            color: var(--muted);
            font-size: 10px
        }

        .summary-card strong {
            display: block;
            margin: 3px 0;
            font-size: 16px
        }

        @media print {
            .summary-card {
                padding: 6px 8px
            }

            .summary-card strong {
                font-size: 11px
            }
        }
    </style>
</head>

<body>
    <div class="page">
        <div class="toolbar"><span>Pratinjau laporan dengan filter yang sedang dipilih.</span><button type="button"
                onclick="window.print()">Cetak / Simpan PDF</button></div>
        <section class="sheet">
            <header class="report-head">
                <div>
                    <p class="eyebrow">Toko Emas Wijaya Kusuma</p>
                    <h1><?php echo e($title); ?></h1>
                    <p>Laporan transaksi terperinci</p>
                </div>
                <div class="brand">LAPORAN RESMI<small><?php echo e(now()->format('d/m/Y H:i')); ?></small></div>
            </header>
            <div class="meta">
                <div class="meta-card"><span>Periode</span><strong><?php echo e(request('start') ?: 'Awal'); ?> s.d.
                        <?php echo e(request('end') ?: 'Sekarang'); ?></strong></div>
                <div class="meta-card">
                    <span>Cabang</span><strong><?php echo e(Auth::user()->isAdmin() ? ($branches->firstWhere('id', (int) request('branch_id'))?->nama_cabang ?? 'Semua cabang') : Auth::user()->branch?->nama_cabang); ?></strong>
                </div>
                <div class="meta-card"><span>Status</span><strong><?php echo e(request('status') ?: 'Semua status'); ?></strong>
                </div>
            </div>
            <div class="report-body"><?php echo $__env->make('laporan.transaction-table', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?></div>
        </section>
    </div>
</body>

</html><?php /**PATH D:\project\toko-emas-wijaya-kusuma\resources\views\laporan\print.blade.php ENDPATH**/ ?>