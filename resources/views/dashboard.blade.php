<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-1">
            <h1 class="text-xl font-bold text-slate-950">Dashboard Toko Emas Wijaya Kusuma</h1>
            <p class="text-sm text-slate-500">Ringkasan penjualan, pembelian, service, stok, dan performa cabang.</p>
        </div>
    </x-slot>

    @php
        $formatCurrency = fn ($value) => 'Rp ' . number_format((float) $value, 0, ',', '.');
        $formatShort = function ($value) {
            $value = (float) $value;
            if ($value >= 1000000000) {
                return 'Rp ' . number_format($value / 1000000000, 1, ',', '.') . 'M';
            }
            if ($value >= 1000000) {
                return 'Rp ' . number_format($value / 1000000, 1, ',', '.') . 'Jt';
            }
            return 'Rp ' . number_format($value, 0, ',', '.');
        };
        $totalProducts = max(1, ($availableProducts ?? 0) + ($soldProducts ?? 0) + ($serviceProducts ?? 0) + ($archivedProducts ?? 0));
        $stockPercent = round((($availableProducts ?? 0) / $totalProducts) * 100);
        $marginColor = ($grossProfit ?? 0) >= 0 ? 'text-emerald-600' : 'text-red-600';
        $kpis = [
            ['label' => 'Penjualan Valid', 'value' => $formatCurrency($totalSales ?? 0), 'meta' => 'Total omzet transaksi aktif', 'tone' => 'bg-emerald-50 text-emerald-600', 'icon' => 'M3 13h4v8H3v-8Zm7-6h4v14h-4V7Zm7-4h4v18h-4V3Z'],
            ['label' => 'Pembelian Valid', 'value' => $formatCurrency($totalPurchases ?? 0), 'meta' => 'Modal pembelian tercatat', 'tone' => 'bg-amber-50 text-amber-600', 'icon' => 'M6 6h15l-2 8H8L6 3H3m6 17h.01M18 20h.01'],
            ['label' => 'Service Selesai', 'value' => $formatCurrency($totalServices ?? 0), 'meta' => 'Pendapatan service selesai', 'tone' => 'bg-sky-50 text-sky-600', 'icon' => 'M14.7 6.3a4 4 0 0 0 5 5L11 20l-5-5 8.7-8.7Z'],
            ['label' => 'Selisih Nilai Transaksi', 'value' => $formatCurrency($grossProfit ?? 0), 'meta' => number_format($grossMargin ?? 0, 1, ',', '.') . '% margin dari omzet', 'tone' => 'bg-violet-50 text-violet-600', 'icon' => 'm3 17 6-6 4 4 7-7m-6 0h6v6'],
        ];
    @endphp

    <div class="px-4 py-6 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-screen-2xl space-y-6">
            @if (Auth::user()->isAdmin())
                <form method="GET" class="flex flex-wrap items-end gap-3 rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
                    <div class="min-w-64">
                        <label for="branch_id" class="text-sm font-semibold text-slate-700">Filter Cabang</label>
                        <select id="branch_id" name="branch_id" class="mt-1 w-full rounded-lg border-slate-200 text-sm focus:border-amber-500 focus:ring-amber-500">
                            <option value="">Semua cabang</option>
                            @foreach ($branches as $branch)
                                <option value="{{ $branch->id }}" @selected((int) $selectedBranchId === $branch->id)>{{ $branch->nama_cabang }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button class="rounded-lg bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800">Terapkan</button>
                </form>
            @endif

            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                @foreach ($kpis as $kpi)
                    <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p class="text-sm font-medium text-slate-500">{{ $kpi['label'] }}</p>
                                <p class="mt-2 text-2xl font-bold tracking-tight text-slate-950">{{ $kpi['value'] }}</p>
                            </div>
                            <div class="grid h-11 w-11 place-items-center rounded-lg {{ $kpi['tone'] }}">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="{{ $kpi['icon'] }}"/></svg>
                            </div>
                        </div>
                        <p class="mt-4 text-sm text-slate-500">{{ $kpi['meta'] }}</p>
                    </div>
                @endforeach
            </div>

            <div class="grid gap-6 xl:grid-cols-12">
                <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm xl:col-span-8">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <h2 class="text-lg font-bold text-slate-950">Tren 7 Hari</h2>
                            <p class="text-sm text-slate-500">Perbandingan penjualan, pembelian, dan service.</p>
                        </div>
                        <div class="flex flex-wrap gap-3 text-xs font-semibold text-slate-500">
                            <span class="flex items-center gap-2"><span class="h-2.5 w-2.5 rounded-full bg-emerald-500"></span>Penjualan</span>
                            <span class="flex items-center gap-2"><span class="h-2.5 w-2.5 rounded-full bg-amber-500"></span>Pembelian</span>
                            <span class="flex items-center gap-2"><span class="h-2.5 w-2.5 rounded-full bg-sky-500"></span>Service</span>
                        </div>
                    </div>

                    <div class="mt-6 h-80">
                        <div class="flex h-64 items-end gap-3 border-b border-l border-slate-200 px-3">
                            @foreach ($trend as $item)
                                <div class="flex min-w-0 flex-1 flex-col items-center gap-2">
                                    <div class="flex h-56 w-full items-end justify-center gap-1.5">
                                        <div title="Penjualan {{ $formatCurrency($item['sales']) }}" class="w-3 rounded-t bg-emerald-500 sm:w-4" style="height: {{ max(5, ($item['sales'] / $maxTrendValue) * 100) }}%"></div>
                                        <div title="Pembelian {{ $formatCurrency($item['purchases']) }}" class="w-3 rounded-t bg-amber-500 sm:w-4" style="height: {{ max(5, ($item['purchases'] / $maxTrendValue) * 100) }}%"></div>
                                        <div title="Service {{ $formatCurrency($item['services']) }}" class="w-3 rounded-t bg-sky-500 sm:w-4" style="height: {{ max(5, ($item['services'] / $maxTrendValue) * 100) }}%"></div>
                                    </div>
                                    <span class="truncate text-xs font-medium text-slate-500">{{ $item['label'] }}</span>
                                </div>
                            @endforeach
                        </div>
                        <div class="mt-4 grid grid-cols-3 gap-3 text-sm">
                            <div class="rounded-lg bg-emerald-50 p-3"><span class="block text-slate-500">Penjualan</span><b class="text-emerald-700">{{ $formatShort($totalSales ?? 0) }}</b></div>
                            <div class="rounded-lg bg-amber-50 p-3"><span class="block text-slate-500">Pembelian</span><b class="text-amber-700">{{ $formatShort($totalPurchases ?? 0) }}</b></div>
                            <div class="rounded-lg bg-sky-50 p-3"><span class="block text-slate-500">Service</span><b class="text-sky-700">{{ $formatShort($totalServices ?? 0) }}</b></div>
                        </div>
                    </div>
                </section>

                <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm xl:col-span-4">
                    <h2 class="text-lg font-bold text-slate-950">Status Stok</h2>
                    <p class="text-sm text-slate-500">Distribusi barang aktif dan arsip.</p>

                    <div class="mt-6 grid place-items-center">
                        <div class="grid h-44 w-44 place-items-center rounded-full" style="background: conic-gradient(#22c55e 0 {{ (($availableProducts ?? 0) / $totalProducts) * 100 }}%, #f59e0b 0 {{ ((($availableProducts ?? 0) + ($soldProducts ?? 0)) / $totalProducts) * 100 }}%, #0ea5e9 0 {{ ((($availableProducts ?? 0) + ($soldProducts ?? 0) + ($serviceProducts ?? 0)) / $totalProducts) * 100 }}%, #94a3b8 0 100%);">
                            <div class="grid h-28 w-28 place-items-center rounded-full bg-white text-center shadow-inner">
                                <div>
                                    <div class="text-2xl font-bold text-slate-950">{{ $stockPercent }}%</div>
                                    <div class="text-xs font-medium text-slate-500">Tersedia</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 space-y-3">
                        @foreach ($productStatus as $status)
                            <div>
                                <div class="mb-1 flex items-center justify-between text-sm">
                                    <span class="font-semibold text-slate-700">{{ $status['label'] }}</span>
                                    <span class="text-slate-500">{{ $status['value'] }} item</span>
                                </div>
                                <div class="h-2 rounded-full bg-slate-100">
                                    <div class="h-2 rounded-full" style="width: {{ max(3, ($status['value'] / $maxStatusValue) * 100) }}%; background-color: {{ $status['color'] }}"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </section>
            </div>

            <div class="grid gap-6 xl:grid-cols-12">
                <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm xl:col-span-5">
                    <h2 class="text-lg font-bold text-slate-950">Performa Cabang</h2>
                    <p class="text-sm text-slate-500">Top cabang berdasarkan penjualan valid.</p>
                    <div class="mt-6 space-y-4">
                        @forelse ($branchPerformance as $branch)
                            <div>
                                <div class="mb-1 flex items-center justify-between gap-3 text-sm">
                                    <span class="truncate font-semibold text-slate-700">{{ $branch['label'] }}</span>
                                    <span class="shrink-0 text-slate-500">{{ $formatShort($branch['value']) }}</span>
                                </div>
                                <div class="h-3 rounded-full bg-slate-100">
                                    <div class="h-3 rounded-full bg-slate-900" style="width: {{ max(4, ($branch['value'] / $maxBranchValue) * 100) }}%"></div>
                                </div>
                            </div>
                        @empty
                            <div class="rounded-lg border border-dashed border-slate-200 p-6 text-center text-sm text-slate-500">Belum ada transaksi cabang.</div>
                        @endforelse
                    </div>
                </section>

                <section class="rounded-lg border border-slate-200 bg-white shadow-sm xl:col-span-7">
                    <div class="flex items-center justify-between gap-3 border-b border-slate-100 p-5">
                        <div>
                            <h2 class="text-lg font-bold text-slate-950">Penjualan Terbaru</h2>
                            <p class="text-sm text-slate-500">Nota valid terakhir yang masuk dashboard.</p>
                        </div>
                        <a href="{{ route('sales.index') }}" class="rounded-lg border border-slate-200 px-3 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-50">Lihat semua</a>
                    </div>
                    <div class="overflow-x-auto">
                        <div class="table-scroll" role="region" aria-label="Tabel data" tabindex="0"><table class="data-table min-w-full divide-y divide-slate-100 text-sm">
                            <thead class="bg-slate-50 text-left text-xs font-bold uppercase tracking-wide text-slate-500">
                                <tr>
                                    <th scope="col" class="px-5 py-3">Nota</th>
                                    <th scope="col" class="px-5 py-3">Customer</th>
                                    <th scope="col" class="px-5 py-3">Cabang</th>
                                    <th scope="col" class="px-5 py-3 text-right table-number">Total</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @forelse ($recentSales as $sale)
                                    <tr class="hover:bg-slate-50">
                                        <td class="px-5 py-4">
                                            <a href="{{ route('sales.show', $sale) }}" class="font-semibold text-amber-700 hover:text-amber-800">{{ $sale->nomor_nota }}</a>
                                            <div class="text-xs text-slate-500">{{ $sale->tanggal?->format('d M Y') }}</div>
                                        </td>
                                        <td class="px-5 py-4 text-slate-700">{{ $sale->receipt('customer')?->nama ?? '-' }}</td>
                                        <td class="px-5 py-4 text-slate-500">{{ $sale->receipt('branch')?->nama_cabang ?? '-' }}</td>
                                        <td class="px-5 py-4 text-right font-bold text-slate-950">{{ $formatCurrency($sale->total_harga) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="px-5 py-10 text-center text-slate-500">Belum ada penjualan valid.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table></div>
                    </div>
                    <div class="border-t border-slate-100 px-5 py-4">{{ $recentSales->links() }}</div>
                </section>
            </div>
        </div>
    </div>
</x-app-layout>
