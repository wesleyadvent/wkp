<!DOCTYPE html>
<html lang="<?php echo e(str_replace('_', '-', app()->getLocale())); ?>">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">

        <title><?php echo e(config('app.name', 'Toko Emas Wijaya Kusuma')); ?></title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

        <?php echo app('Illuminate\Foundation\Vite')(['resources/css/app.css', 'resources/js/app.js']); ?>
    </head>
    <body class="app-ui font-sans antialiased text-gray-800">
        <?php
            $navGroups = [
                'Operasional' => [
                    ['label' => 'Dashboard', 'route' => 'dashboard', 'active' => 'dashboard', 'icon' => 'dashboard'],
                    ['label' => 'Barang', 'route' => 'products.index', 'active' => 'products.*', 'icon' => 'box'],
                    ['label' => 'Penjualan', 'route' => 'sales.index', 'active' => 'sales.*', 'icon' => 'receipt'],
                    ['label' => 'Pembelian', 'route' => 'purchases.index', 'active' => 'purchases.*', 'icon' => 'cart'],
                    ['label' => 'Service', 'route' => 'service-notes.index', 'active' => 'service-notes.*', 'icon' => 'tool'],
                    ['label' => 'Customer', 'route' => 'customers.index', 'active' => 'customers.*', 'icon' => 'users'],
                ],
                'Master Data' => [
                    ['label' => 'Daftar Karyawan', 'route' => 'employees.index', 'active' => 'employees.*', 'icon' => 'users'],
                    ['label' => 'Harga Emas', 'route' => 'gold-prices.index', 'active' => 'gold-prices.*', 'icon' => 'trend'],
                    ['label' => 'Kategori Barang', 'route' => 'product-categories.index', 'active' => 'product-categories.*', 'icon' => 'tag'],
                    ['label' => 'Potongan', 'route' => 'deduction-types.index', 'active' => 'deduction-types.*', 'icon' => 'percent'],
                    ['label' => 'Jenis Service', 'route' => 'service-types.index', 'active' => 'service-types.*', 'icon' => 'spark'],
                ],
                'Laporan' => [
                    ['label' => 'Selisih Nilai Transaksi', 'route' => 'reports.profit-loss', 'active' => 'reports.profit-loss', 'icon' => 'chart'],
                    ['label' => 'Laporan Penjualan', 'route' => 'reports.sales', 'active' => 'reports.sales', 'icon' => 'file'],
                    ['label' => 'Laporan Pembelian', 'route' => 'reports.purchases', 'active' => 'reports.purchases', 'icon' => 'file'],
                    ['label' => 'Laporan Service', 'route' => 'reports.services', 'active' => 'reports.services', 'icon' => 'file'],
                    ['label' => 'Analisis SMA', 'route' => 'inventory-analysis.sma', 'active' => 'inventory-analysis.sma', 'icon' => 'trend'],
                    ['label' => 'Analisis WMA', 'route' => 'inventory-analysis.wma', 'active' => 'inventory-analysis.wma', 'icon' => 'trend'],
                    ['label' => 'Analisis ARIMA', 'route' => 'inventory-analysis.arima', 'active' => 'inventory-analysis.arima', 'icon' => 'trend'],
                    ['label' => 'Analisis LightGBM', 'route' => 'inventory-analysis.lightgbm', 'active' => 'inventory-analysis.lightgbm', 'icon' => 'trend'],
                    ['label' => 'Perbandingan Prediksi', 'route' => 'forecast-comparison.index', 'active' => 'forecast-comparison.*', 'icon' => 'chart'],
                ],
            ];

            if (Auth::user()?->isAdmin()) {
                $navGroups['Admin'] = [
                    ['label' => 'Cabang', 'route' => 'branches.index', 'active' => 'branches.index', 'icon' => 'building'],
                    ['label' => 'User', 'route' => 'users.index', 'active' => 'users.*', 'icon' => 'user'],
                    ['label' => 'Audit Log', 'route' => 'audit-logs.index', 'active' => 'audit-logs.*', 'icon' => 'shield'],
                    ['label' => 'Konfigurasi SMA', 'route' => 'inventory-settings.sma.index', 'active' => 'inventory-settings.sma.*', 'icon' => 'box'],
                    ['label' => 'Konfigurasi WMA', 'route' => 'inventory-settings.wma.index', 'active' => 'inventory-settings.wma.*', 'icon' => 'box'],
                    ['label' => 'Konfigurasi ARIMA', 'route' => 'inventory-settings.arima.index', 'active' => 'inventory-settings.arima.*', 'icon' => 'box'],
                    ['label' => 'Konfigurasi LightGBM', 'route' => 'inventory-settings.lightgbm.index', 'active' => 'inventory-settings.lightgbm.*', 'icon' => 'box'],
                    ['label' => 'Login Cabang', 'route' => 'branches.login-logs', 'active' => 'branches.login-logs', 'icon' => 'lock'],
                    ['label' => 'Batal Penjualan', 'route' => 'transactions.destroy-sales', 'active' => 'transactions.destroy-sales', 'icon' => 'ban'],
                    ['label' => 'Batal Pembelian', 'route' => 'transactions.destroy-purchases', 'active' => 'transactions.destroy-purchases', 'icon' => 'ban'],
                    ['label' => 'Batal Service', 'route' => 'transactions.destroy-services', 'active' => 'transactions.destroy-services', 'icon' => 'ban'],
                ];
            }
        ?>

        <div x-data="{ sidebarOpen: false }" class="min-h-screen bg-slate-50">
            <div x-cloak x-show="sidebarOpen" x-transition.opacity class="fixed inset-0 z-30 bg-slate-900/40 lg:hidden" @click="sidebarOpen = false"></div>

            <aside class="fixed inset-y-0 left-0 z-40 flex w-72 -translate-x-full flex-col border-r border-slate-200 bg-white transition-transform duration-200 lg:translate-x-0" :class="{ 'translate-x-0': sidebarOpen }">
                <div class="flex h-20 items-center gap-3 border-b border-slate-100 px-6">
                    <div class="grid h-11 w-11 place-items-center rounded-lg bg-amber-500 text-lg font-bold text-white shadow-sm">WK</div>
                    <div>
                        <div class="text-sm font-bold uppercase tracking-wide text-slate-900">Wijaya Kusuma</div>
                        <div class="text-xs font-medium text-slate-500">Admin Toko Emas</div>
                    </div>
                </div>

                <nav class="flex-1 space-y-7 overflow-y-auto px-4 py-6">
                    <?php $__currentLoopData = $navGroups; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $group => $items): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <div>
                            <div class="mb-3 px-3 text-[11px] font-bold uppercase tracking-wider text-slate-400"><?php echo e($group); ?></div>
                            <div class="space-y-1">
                                <?php $__currentLoopData = $items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <?php $active = request()->routeIs($item['active']); ?>
                                    <a href="<?php echo e(route($item['route'])); ?>" class="group flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-semibold transition <?php echo e($active ? 'bg-amber-50 text-amber-700 ring-1 ring-amber-100' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-950'); ?>">
                                        <span class="grid h-8 w-8 place-items-center rounded-md <?php echo e($active ? 'bg-amber-500 text-white' : 'bg-slate-100 text-slate-500 group-hover:bg-white group-hover:text-slate-800'); ?>">
                                            <?php switch($item['icon']):
                                                case ('dashboard'): ?><svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h7v7H4z"/><path d="M13 4h7v7h-7z"/><path d="M4 13h7v7H4z"/><path d="M13 13h7v7h-7z"/></svg><?php break; ?>
                                                <?php case ('box'): ?><svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m21 8-9-5-9 5 9 5 9-5Z"/><path d="M3 8v8l9 5 9-5V8"/><path d="M12 13v8"/></svg><?php break; ?>
                                                <?php case ('receipt'): ?><svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2h12v20l-3-2-3 2-3-2-3 2z"/><path d="M8 7h8M8 11h8M8 15h5"/></svg><?php break; ?>
                                                <?php case ('cart'): ?><svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 6h15l-2 8H8L6 3H3"/><circle cx="9" cy="20" r="1"/><circle cx="18" cy="20" r="1"/></svg><?php break; ?>
                                                <?php case ('tool'): ?><svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14.7 6.3a4 4 0 0 0 5 5L11 20l-5-5 8.7-8.7Z"/></svg><?php break; ?>
                                                <?php case ('users'): ?><svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg><?php break; ?>
                                                <?php case ('trend'): ?><svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m3 17 6-6 4 4 7-7"/><path d="M14 8h6v6"/></svg><?php break; ?>
                                                <?php case ('tag'): ?><svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 10 14 4H5v9l6 6a2 2 0 0 0 3 0l6-6a2 2 0 0 0 0-3Z"/><path d="M8 8h.01"/></svg><?php break; ?>
                                                <?php case ('percent'): ?><svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m19 5-14 14"/><circle cx="7" cy="7" r="2"/><circle cx="17" cy="17" r="2"/></svg><?php break; ?>
                                                <?php case ('spark'): ?><svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3v18M3 12h18M5 5l14 14M19 5 5 19"/></svg><?php break; ?>
                                                <?php case ('chart'): ?><svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 3v18h18"/><path d="M7 16v-5M12 16V7M17 16v-8"/></svg><?php break; ?>
                                                <?php case ('building'): ?><svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 21V5a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v16"/><path d="M9 21v-5h6v5M8 7h.01M16 7h.01M8 11h.01M16 11h.01"/></svg><?php break; ?>
                                                <?php case ('user'): ?><svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/></svg><?php break; ?>
                                                <?php case ('shield'): ?><svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10Z"/></svg><?php break; ?>
                                                <?php case ('lock'): ?><svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/></svg><?php break; ?>
                                                <?php case ('ban'): ?><svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="m5.6 5.6 12.8 12.8"/></svg><?php break; ?>
                                                <?php default: ?><svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/></svg>
                                            <?php endswitch; ?>
                                        </span>
                                        <span class="truncate"><?php echo e($item['label']); ?></span>
                                    </a>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </div>
                        </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </nav>

                <div class="border-t border-slate-100 p-4">
                    <a href="<?php echo e(route('profile.edit')); ?>" class="flex items-center gap-3 rounded-lg bg-slate-50 p-3 hover:bg-slate-100">
                        <div class="grid h-10 w-10 place-items-center rounded-full bg-slate-900 text-sm font-bold text-white"><?php echo e(\Illuminate\Support\Str::of(Auth::user()->name)->substr(0, 1)->upper()); ?></div>
                        <div class="min-w-0 flex-1">
                            <div class="truncate text-sm font-semibold text-slate-900"><?php echo e(Auth::user()->name); ?></div>
                            <div class="truncate text-xs text-slate-500"><?php echo e(Auth::user()->email); ?></div>
                        </div>
                    </a>
                    <form method="POST" action="<?php echo e(route('logout')); ?>" class="mt-3">
                        <?php echo csrf_field(); ?>
                        <button type="submit" class="flex w-full items-center justify-center gap-2 rounded-lg border border-slate-200 px-3 py-2 text-sm font-semibold text-slate-600 hover:border-red-200 hover:bg-red-50 hover:text-red-600">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="m16 17 5-5-5-5"/><path d="M21 12H9"/></svg>
                            Keluar
                        </button>
                    </form>
                </div>
            </aside>

            <div class="min-h-screen lg:pl-72">
                <header class="sticky top-0 z-20 border-b border-slate-200 bg-white/95 backdrop-blur">
                    <div class="flex min-h-16 items-center gap-4 px-4 sm:px-6 lg:px-8">
                        <button type="button" class="grid h-10 w-10 place-items-center rounded-lg border border-slate-200 text-slate-600 lg:hidden" @click="sidebarOpen = true">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
                        </button>
                        <div class="min-w-0 flex-1 py-4">
                            <?php if(isset($header)): ?>
                                <?php echo e($header); ?>

                            <?php else: ?>
                                <h1 class="text-lg font-bold text-slate-900">Toko Emas Wijaya Kusuma</h1>
                            <?php endif; ?>
                        </div>
                    </div>
                </header>

                <main>
                    <?php echo e($slot); ?>

                </main>
            </div>
        </div>
    </body>
</html>
<?php /**PATH D:\project\toko-emas-wijaya-kusuma\resources\views\tata-letak\app.blade.php ENDPATH**/ ?>