<?php

namespace App\Http\Controllers;

use App\Models\Cabang;
use App\Models\Pembelian;
use App\Models\Penjualan;
use App\Models\NotaLayanan;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PenghapusanTransaksiController extends Controller
{
    public function sales(Request $request): View
    {
        $this->validateListFilters($request);
        abort_unless($request->user()->isAdmin(), 403);

        $hasFilter = $request->filled('branch_id') || $request->filled('tanggal');
        $sales = collect();

        if ($hasFilter) {
            $sales = Penjualan::with('branch', 'customer', 'items.product')
                ->where('status', 'valid')
                ->when($request->filled('branch_id'), fn ($query) => $query->where('branch_id', $request->branch_id))
                ->when($request->filled('tanggal'), fn ($query) => $query->whereDate('tanggal', $request->tanggal))
                ->latest()
                ->paginate($this->perPage($request))
                ->withQueryString();
        }

        return view('transaksi.destroy-sales', [
            'branches' => Cabang::orderBy('nama_cabang')->get(),
            'sales' => $sales,
            'hasFilter' => $hasFilter,
        ]);
    }

    public function purchases(Request $request): View
    {
        $this->validateListFilters($request);
        abort_unless($request->user()->isAdmin(), 403);

        $hasFilter = $request->filled('branch_id') || $request->filled('tanggal');
        $purchases = collect();

        if ($hasFilter) {
            $purchases = Pembelian::with('branch', 'customer', 'items', 'deductions.deductionType')
                ->where('status', 'valid')
                ->when($request->filled('branch_id'), fn ($query) => $query->where('branch_id', $request->branch_id))
                ->when($request->filled('tanggal'), fn ($query) => $query->whereDate('tanggal', $request->tanggal))
                ->latest()
                ->paginate($this->perPage($request))
                ->withQueryString();
        }

        return view('transaksi.destroy-purchases', [
            'branches' => Cabang::orderBy('nama_cabang')->get(),
            'purchases' => $purchases,
            'hasFilter' => $hasFilter,
        ]);
    }

    public function services(Request $request): View
    {
        $this->validateListFilters($request);
        abort_unless($request->user()->isAdmin(), 403);

        $hasFilter = $request->filled('branch_id') || $request->filled('tanggal');
        $services = collect();

        if ($hasFilter) {
            $services = NotaLayanan::with('branch', 'customer', 'product')
                ->where('status_service', '!=', 'batal')
                ->when($request->filled('branch_id'), fn ($query) => $query->where('branch_id', $request->branch_id))
                ->when($request->filled('tanggal'), fn ($query) => $query->whereDate('tanggal_masuk', $request->tanggal))
                ->latest()
                ->paginate($this->perPage($request))
                ->withQueryString();
        }

        return view('transaksi.destroy-services', [
            'branches' => Cabang::orderBy('nama_cabang')->get(),
            'services' => $services,
            'hasFilter' => $hasFilter,
        ]);
    }
}
