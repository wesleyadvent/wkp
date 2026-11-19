<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Laporan Service - <?php echo e(config('app.name')); ?></title>
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
            max-width: 1280px;
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
            min-width: 900px;
            border-collapse: collapse;
            margin-top: 22px
        }

        .report-table th,
        .report-table td {
            padding: 11px 10px;
            border-bottom: 1px solid var(--line);
            text-align: left;
            vertical-align: top
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

        .report-table .number {
            text-align: right;
            white-space: nowrap;
            font-variant-numeric: tabular-nums
        }

        .summary {
            display: flex;
            justify-content: space-between;
            gap: 16px;
            margin-top: 22px;
            padding: 16px 0 0;
            border-top: 2px solid var(--ink)
        }

        .summary span {
            display: block;
            color: var(--muted);
            font-size: 11px
        }

        .summary strong {
            font-size: 16px
        }

        .status {
            display: inline-flex;
            padding: 3px 8px;
            border-radius: 999px;
            background: #edf2f7;
            color: #475569;
            font-size: 10px;
            font-weight: 700
        }

        .status.selesai,
        .status.sudah_diambil {
            background: #e8f7ed;
            color: #187344
        }

        .status.diproses {
            background: #fff6dc;
            color: #94600b
        }

        .status.batal {
            background: #edf2f7;
            color: #64748b
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

            .summary {
                margin-top: 12px;
                padding-top: 9px
            }

            .summary strong {
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

            .summary {
                align-items: flex-start;
                flex-direction: column
            }
        }

        .summary {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px
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

        @media screen and (max-width:700px) {
            .summary {
                grid-template-columns: 1fr
            }
        }
    </style>
</head>

<body>
    <div class="page">
        <div class="toolbar"><span>Pratinjau laporan service dengan filter yang sedang dipilih.</span><button
                type="button" onclick="window.print()">Cetak / Simpan PDF</button></div>
        <section class="sheet">
            <header class="report-head">
                <div>
                    <p class="eyebrow">Toko Emas Wijaya Kusuma</p>
                    <h1>Laporan Service</h1>
                    <p>Daftar nota service terperinci</p>
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
            <div class="report-body">
                <table class="report-table">
                    <thead>
                        <tr>
                            <th>No.</th>
                            <th>Nomor Nota</th>
                            <th>Tanggal</th>
                            <th>Cabang</th>
                            <th>Customer</th>
                            <th>Jenis Service</th>
                            <th>Status</th>
                            <th class="number">Total</th>
                        </tr>
                    </thead>
                    <tbody><?php $__empty_1 = true; $__currentLoopData = $rows; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <tr>
                            <td><?php echo e($index + 1); ?></td>
                            <td><strong><?php echo e($row->nomor_nota); ?></strong></td>
                            <td><?php echo e($row->tanggal_masuk?->format('d/m/Y')); ?></td>
                            <td><?php echo e($row->receipt('branch')?->nama_cabang ?? '-'); ?></td>
                            <td><?php echo e($row->receipt('customer')?->nama ?? '-'); ?></td>
                            <td><?php echo e($row->receipt('serviceType')?->nama_service ?? '-'); ?></td>
                            <td><span
                                    class="status <?php echo e($row->status_service); ?>"><?php echo e(str_replace('_', ' ', ucfirst($row->status_service))); ?></span>
                            </td>
                            <td class="number">Rp <?php echo e(number_format($row->harga_service, 0, ',', '.')); ?></td>
                    </tr><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><tr>
                            <td colspan="8">Belum ada data service.</td>
                        </tr><?php endif; ?>
                    </tbody>
                </table>
                <div class="summary">
                    <div class="summary-card"><span>Total nota</span><strong><?php echo e($rows->count()); ?></strong></div>
                    <div class="summary-card"><span>Total service valid selesai / diambil</span><strong>Rp
                            <?php echo e(number_format($total, 0, ',', '.')); ?></strong></div>
                </div>
            </div>
        </section>
    </div>
</body>

</html>
<?php /**PATH D:\project\toko-emas-wijaya-kusuma\resources\views/laporan/services-print.blade.php ENDPATH**/ ?>