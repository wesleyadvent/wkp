<?php

namespace App\Http\Controllers;

use App\Models\Cabang;
use App\Services\ForecastComparisonService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use RuntimeException;

class PerbandinganPrediksiController extends Controller
{
    public function __invoke(Request $request, ForecastComparisonService $comparison): View
    {
        $this->validateListFilters($request);
        $data = $request->validate([
            'horizon' => ['nullable', 'integer', Rule::in(ForecastComparisonService::HORIZONS)],
            'metric' => ['nullable', Rule::in(['mae', 'rmse', 'mape'])],
            'category' => ['nullable', 'string', 'max:100'], 'karat' => ['nullable', 'integer', 'between:8,24'],
            'from' => ['nullable', 'date_format:Y-m-d'], 'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
        ]);
        $selectedBranchId = $request->user()->isAdmin() ? ($request->filled('branch_id') ? $request->integer('branch_id') : null) : (int) $request->user()->branch_id;
        abort_if($request->user()->isBranchUser() && ! $selectedBranchId, 403);
        $branches = Cabang::orderBy('nama_cabang')->get();
        $selectedBranch = $selectedBranchId ? $branches->firstWhere('id', $selectedBranchId) : null;
        abort_if($selectedBranchId && ! $selectedBranch, 403);
        $horizon = (int) ($data['horizon'] ?? 14);
        $error = null;
        $meta = null;
        $result = null;
        try {
            $meta = $comparison->metadata();
        } catch (RuntimeException $e) {
            $error = $e->getMessage();
        }
        $bounds = $meta['horizons'][(string) $horizon] ?? [];
        $filters = [
            'horizon' => $horizon, 'metric' => $data['metric'] ?? 'mae', 'branch_code' => $selectedBranch?->kode_cabang,
            'category' => mb_strtoupper(trim($data['category'] ?? '')), 'karat' => isset($data['karat']) ? (int) $data['karat'] : null,
            'from' => $data['from'] ?? $bounds['cutoff_start'] ?? '2025-10-01', 'to' => $data['to'] ?? $bounds['cutoff_end'] ?? '2026-07-17',
            'q' => trim($request->input('q', '')),
        ];
        $perPage = $this->perPage($request);
        $page = $request->integer('page', 1);
        if (! $error) {
            try {
                $result = $comparison->analyze($filters, $branches->pluck('nama_cabang', 'kode_cabang')->all(), $page, $perPage);
            } catch (RuntimeException $e) {
                $error = $e->getMessage();
            }
        }

        return view('analisis-persediaan.perbandingan', [
            'filters' => $filters, 'result' => $result, 'error' => $error, 'metadata' => $meta,
            'selectedBranchId' => $selectedBranchId, 'branches' => $request->user()->isAdmin() ? $branches : collect(),
            'branchName' => $selectedBranch?->nama_cabang ?? 'Seluruh cabang',
            'details' => new LengthAwarePaginator($result['details'] ?? [], $result['count'] ?? 0, $perPage, $page, ['path' => $request->url(), 'query' => $request->query()]),
        ]);
    }
}
