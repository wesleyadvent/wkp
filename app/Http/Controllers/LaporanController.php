<?php

namespace App\Http\Controllers;

use App\Models\Cabang;
use App\Models\Pembelian;
use App\Models\Penjualan;
use App\Models\NotaLayanan;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LaporanController extends Controller
{
    public function sales(Request $request): View
    {
        return $this->transactions($request, Penjualan::class, 'sales');
    }

    public function purchases(Request $request): View
    {
        return $this->transactions($request, Pembelian::class, 'purchases');
    }

    private function transactions(Request $request, string $model, string $type): View
    {
        $request->validate([
            'start' => ['nullable', 'date'],
            'end' => array_merge(['nullable', 'date'], $request->filled('start') ? ['after_or_equal:start'] : []),
            'status' => ['nullable', 'in:valid,batal,cancelled'],
            'branch_id' => ['nullable', 'integer', 'exists:cabang,id'],
            'print' => ['nullable', 'boolean'],
        ]);
        $relations = ['branch', 'customer', 'creator', 'canceller', 'cancellation', 'items.product'];
        if ($type === 'purchases') {
            $relations = array_merge($relations, ['referenceSale', 'deductions.deductionType']);
        }
        $query = $this->filter($this->scoped($model::with($relations), $request), $request);
        $totals = (clone $query)->where('status', 'valid')
            ->selectRaw('COUNT(*) as count, COALESCE(SUM(total_harga), 0) as price, COALESCE(SUM(total_berat), 0) as weight')->first();
        $printing = $request->boolean('print');

        return view($printing ? 'laporan.print' : 'laporan.transactions', [
            'rows' => $printing ? $query->orderBy('tanggal')->orderBy('id')->get() : $query->orderByDesc('tanggal')->orderByDesc('id')->paginate($this->perPage($request, 20))->withQueryString(),
            'branches' => Cabang::orderBy('nama_cabang')->get(),
            'total' => $totals->price,
            'totalWeight' => $totals->weight,
            'totalCount' => $totals->count,
            'type' => $type,
            'title' => $type === 'sales' ? 'Laporan Penjualan' : 'Laporan Pembelian',
            'printing' => $printing,
        ]);
    }

    public function services(Request $request): View
    {
        $request->validate([
            'start' => ['nullable', 'date'],
            'end' => array_merge(['nullable', 'date'], $request->filled('start') ? ['after_or_equal:start'] : []),
            'status' => ['nullable', 'in:diterima,diproses,selesai,sudah_diambil,batal'],
            'branch_id' => ['nullable', 'integer', 'exists:cabang,id'],
            'print' => ['nullable', 'boolean'],
        ]);

        $query = $this->filter($this->scoped(NotaLayanan::with('branch', 'customer', 'serviceType'), $request), $request, 'tanggal_masuk');
        $printing = $request->boolean('print');

        return view($printing ? 'laporan.services-print' : 'laporan.services', [
            'rows' => $printing ? $query->orderBy('tanggal_masuk')->orderBy('id')->get() : $query->latest()->paginate($this->perPage($request, 20))->withQueryString(),
            'branches' => Cabang::orderBy('nama_cabang')->get(),
            'total' => (clone $query)->whereIn('status_service', ['selesai', 'sudah_diambil'])->sum('harga_service'),
            'printing' => $printing,
        ]);
    }

    public function profitLoss(Request $request): View
    {
        $sales = $this->filter($this->scoped(Penjualan::query(), $request), $request)->where('status', 'valid')->sum('total_harga');
        $purchases = $this->filter($this->scoped(Pembelian::query(), $request), $request)->where('status', 'valid')->sum('total_harga');
        $services = $this->filter($this->scoped(NotaLayanan::query(), $request), $request, 'tanggal_masuk')->whereIn('status_service', ['selesai', 'sudah_diambil'])->sum('harga_service');
        $branches = Cabang::orderBy('nama_cabang')->get();
        $branchRows = collect();

        if ($request->user()->isAdmin() && ! $request->filled('branch_id')) {
            $branchRows = $branches->map(function (Cabang $branch) use ($request): array {
                $branchRequest = $request->duplicate(array_merge($request->query(), ['branch_id' => $branch->id]));
                $branchSales = $this->filter(Penjualan::query(), $branchRequest)->where('status', 'valid')->sum('total_harga');
                $branchPurchases = $this->filter(Pembelian::query(), $branchRequest)->where('status', 'valid')->sum('total_harga');
                $branchServices = $this->filter(NotaLayanan::query(), $branchRequest, 'tanggal_masuk')->whereIn('status_service', ['selesai', 'sudah_diambil'])->sum('harga_service');

                return [
                    'branch' => $branch,
                    'sales' => $branchSales,
                    'purchases' => $branchPurchases,
                    'services' => $branchServices,
                    'profit' => $branchSales + $branchServices - $branchPurchases,
                ];
            });
        }

        return view('laporan.profit-loss', compact('sales', 'purchases', 'services', 'branches', 'branchRows'));
    }

    private function filter(Builder $query, Request $request, string $dateColumn = 'tanggal'): Builder
    {
        $this->validateListFilters($request);
        $request->validate([
            'start' => ['nullable', 'date'],
            'end' => array_merge(['nullable', 'date'], $request->filled('start') ? ['after_or_equal:start'] : []),
        ]);
        return $query
            ->when($request->filled('branch_id') && $request->user()->isAdmin(), fn (Builder $q) => $q->where('branch_id', $request->branch_id))
            ->when($request->filled('start'), fn (Builder $q) => $q->whereDate($dateColumn, '>=', $request->start))
            ->when($request->filled('end'), fn (Builder $q) => $q->whereDate($dateColumn, '<=', $request->end))
            ->when($request->filled('status'), function (Builder $q) use ($request): void {
                $column = $q->getModel() instanceof NotaLayanan ? 'status_service' : 'status';
                if ($column === 'status' && in_array($request->status, ['batal', 'cancelled'])) {
                    $q->whereIn($column, ['batal', 'cancelled']);
                } else {
                    $q->where($column, $request->status);
                }
            });
    }
}
