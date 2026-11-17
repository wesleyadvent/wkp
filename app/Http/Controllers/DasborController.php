<?php

namespace App\Http\Controllers;

use App\Models\Cabang;
use App\Models\Barang;
use App\Models\Pembelian;
use App\Models\Penjualan;
use App\Models\NotaLayanan;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DasborController extends Controller
{
    public function __invoke(Request $request): View
    {
        $this->validateListFilters($request);
        $branches = Cabang::orderBy('nama_cabang')->get();
        $selectedBranchId = $request->user()->isAdmin() ? $request->input('branch_id') : $request->user()->branch_id;
        $startDate = now()->subDays(6)->toDateString();
        $endDate = now()->toDateString();

        $sales = $this->branchFilter($this->scoped(Penjualan::query(), $request), $request)->where('penjualan.status', 'valid');
        $purchases = $this->branchFilter($this->scoped(Pembelian::query(), $request), $request)->where('status', 'valid');
        $services = $this->branchFilter($this->scoped(NotaLayanan::query(), $request), $request)->whereIn('status_service', ['selesai', 'sudah_diambil']);
        $products = $this->branchFilter($this->scoped(Barang::query(), $request), $request);
        $totalSales = (clone $sales)->sum('total_harga');
        $totalPurchases = (clone $purchases)->sum('total_harga');
        $totalServices = (clone $services)->sum('harga_service');
        $availableProducts = (clone $products)->where('status', 'tersedia')->count();
        $soldProducts = (clone $products)->where('status', 'terjual')->count();
        $serviceProducts = (clone $products)->where('status', 'service')->count();
        $archivedProducts = (clone $products)->where('status', 'arsip')->count();
        $grossProfit = $totalSales + $totalServices - $totalPurchases;
        $grossMargin = $totalSales > 0 ? ($grossProfit / $totalSales) * 100 : 0;

        $salesByDate = (clone $sales)
            ->whereBetween('tanggal', [$startDate, $endDate])
            ->get(['tanggal', 'total_harga'])
            ->groupBy(fn (Penjualan $sale) => $sale->tanggal->format('Y-m-d'))
            ->map(fn ($items) => (float) $items->sum('total_harga'));

        $purchasesByDate = (clone $purchases)
            ->whereBetween('tanggal', [$startDate, $endDate])
            ->get(['tanggal', 'total_harga'])
            ->groupBy(fn (Pembelian $purchase) => $purchase->tanggal->format('Y-m-d'))
            ->map(fn ($items) => (float) $items->sum('total_harga'));

        $servicesByDate = (clone $services)
            ->whereBetween('tanggal_masuk', [$startDate, $endDate])
            ->get(['tanggal_masuk', 'harga_service'])
            ->groupBy(fn (NotaLayanan $service) => $service->tanggal_masuk->format('Y-m-d'))
            ->map(fn ($items) => (float) $items->sum('harga_service'));

        $trend = collect(range(6, 0))->map(function (int $daysAgo) use ($salesByDate, $purchasesByDate, $servicesByDate) {
            $date = now()->subDays($daysAgo);
            $key = $date->format('Y-m-d');

            return [
                'label' => $date->format('d M'),
                'sales' => $salesByDate->get($key, 0),
                'purchases' => $purchasesByDate->get($key, 0),
                'services' => $servicesByDate->get($key, 0),
            ];
        });

        $maxTrendValue = max(1, (float) $trend->flatMap(fn ($item) => [
            $item['sales'],
            $item['purchases'],
            $item['services'],
        ])->max());

        $productStatus = collect([
            ['label' => 'Tersedia', 'value' => $availableProducts, 'color' => '#22c55e'],
            ['label' => 'Terjual', 'value' => $soldProducts, 'color' => '#f59e0b'],
            ['label' => 'Service', 'value' => $serviceProducts, 'color' => '#0ea5e9'],
            ['label' => 'Arsip', 'value' => $archivedProducts, 'color' => '#94a3b8'],
        ]);

        $maxStatusValue = max(1, (int) $productStatus->max('value'));

        $branchPerformance = (clone $sales)
            ->join('cabang', 'cabang.id', '=', 'penjualan.branch_id')
            ->selectRaw('penjualan.branch_id, cabang.nama_cabang AS label, SUM(penjualan.total_harga) AS value')
            ->groupBy('penjualan.branch_id', 'cabang.nama_cabang')
            ->orderByDesc('value')
            ->limit(5)
            ->get()
            ->map(fn ($branch) => [
                'label' => $branch->label,
                'value' => (float) $branch->value,
            ]);

        $maxBranchValue = max(1, (float) $branchPerformance->max('value'));

        return view('dashboard', [
            'totalSales' => $totalSales,
            'totalPurchases' => $totalPurchases,
            'totalServices' => $totalServices,
            'availableProducts' => $availableProducts,
            'soldProducts' => $soldProducts,
            'serviceProducts' => $serviceProducts,
            'archivedProducts' => $archivedProducts,
            'branchCount' => $request->user()->isAdmin() ? Cabang::count() : 1,
            'grossProfit' => $grossProfit,
            'grossMargin' => $grossMargin,
            'trend' => $trend,
            'maxTrendValue' => $maxTrendValue,
            'productStatus' => $productStatus,
            'maxStatusValue' => $maxStatusValue,
            'branchPerformance' => $branchPerformance,
            'maxBranchValue' => $maxBranchValue,
            'recentSales' => (clone $sales)->with('branch', 'customer')->latest()->paginate($this->perPage($request, 10))->withQueryString(),
            'branches' => $branches,
            'selectedBranchId' => $selectedBranchId,
        ]);
    }

    private function branchFilter(Builder $query, Request $request): Builder
    {
        if ($request->user()->isAdmin() && $request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        return $query;
    }
}
