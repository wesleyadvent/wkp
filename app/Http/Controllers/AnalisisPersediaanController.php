<?php

namespace App\Http\Controllers;

use App\Models\Cabang;
use App\Models\HargaEmas;
use App\Models\KategoriBarang;
use App\Models\KonfigurasiPersediaan;
use App\Services\AuditLogService;
use App\Services\InventoryAnalysisService;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AnalisisPersediaanController extends Controller
{
    public function index(Request $request, InventoryAnalysisService $analysisService): View
    {
        $this->validateListFilters($request);
        $data = $request->validate([
            'branch_id' => ['nullable', 'integer', 'exists:cabang,id'],
            'periode' => ['nullable', 'integer', Rule::in(InventoryAnalysisService::PERIODS)],
            'q' => ['nullable', 'string', 'max:100'],
            'metode' => ['nullable', Rule::in(['SMA', 'WMA', 'ARIMA', 'LightGBM'])],
            'cutoff' => ['nullable', 'date_format:Y-m-d', 'before:today'],
        ]);

        $selectedBranchId = $request->user()->isAdmin()
            ? ($request->filled('branch_id') ? (int) $data['branch_id'] : null)
            : (int) $request->user()->branch_id;
        abort_if($request->user()->isBranchUser() && ! $selectedBranchId, 403);

        $periode = (int) ($data['periode'] ?? 30);
        $metode = $this->forecastMethod($request);
        $cutoff = CarbonImmutable::parse($data['cutoff'] ?? now('Asia/Jakarta')->subDay()->toDateString(), 'Asia/Jakarta');
        $allRows = $analysisService->analyze($selectedBranchId, $periode, $cutoff, $metode)
            ->when(filled($data['q'] ?? null), function ($rows) use ($data) {
                $keyword = mb_strtolower(trim($data['q']));

                return $rows->filter(fn (array $row) => str_contains(
                    mb_strtolower(implode(' ', [$row['cabang'], $row['kategori'], $row['kadar']])),
                    $keyword,
                ));
            })
            ->values();
        $summary = [
            'belum_lengkap' => $allRows->where('status_persediaan', 'data_belum_lengkap')->count(),
            'ramalan_belum_tersedia' => in_array($metode, ['ARIMA', 'LightGBM'], true) ? $allRows->filter(fn ($r) => $r['ramalan_unit'] === null || $r['ramalan_unit_periode'] === null)->count() : 0,
            'aman' => $allRows->where('status_persediaan', 'aman')->count(),
            'perlu_perhatian' => $allRows->where('status_persediaan', 'perlu_perhatian')->count(),
            'kritis' => $allRows->where('status_persediaan', 'kritis')->count(),
            'tren_tertinggi' => $this->highestTrend($allRows),
        ];

        return view('analisis-persediaan.index', [
            'rows' => $this->paginateCollection($allRows, $request),
            'totalRows' => $allRows->count(),
            'summary' => $summary,
            'periode' => $periode,
            'metode' => $metode,
            'lightgbmStatus' => $analysisService->lightgbmStatus(),
            'analysisRoute' => 'inventory-analysis.'.strtolower($metode),
            'settingsPrefix' => 'inventory-settings.'.strtolower($metode),
            'cutoff' => $cutoff->toDateString(),
            'selectedBranchId' => $selectedBranchId,
            'branches' => $request->user()->isAdmin() ? Cabang::orderBy('nama_cabang')->get() : collect(),
        ]);
    }

    public function configurations(Request $request): View
    {
        $metode = $this->forecastMethod($request);
        $this->validateListFilters($request);
        $data = $request->validate([
            'branch_id' => ['nullable', 'integer', 'exists:cabang,id'],
            'q' => ['nullable', 'string', 'max:100'],
        ]);
        $branches = Cabang::orderBy('nama_cabang')->get();
        $selectedBranches = $request->filled('branch_id')
            ? $branches->where('id', (int) $data['branch_id'])
            : $branches;
        $categories = KategoriBarang::where('is_active', true)->orderBy('nama_kategori')->get();
        $karats = HargaEmas::query()->orderBy('kadar')->get()->pluck('kadar')->unique()->values();
        $configurations = KonfigurasiPersediaan::query()
            ->where('method', $metode)
            ->when($request->filled('branch_id'), fn ($query) => $query->where('branch_id', $data['branch_id']))
            ->get()
            ->keyBy(fn (KonfigurasiPersediaan $configuration) => $this->configurationKey(
                $configuration->branch_id,
                $configuration->kategori,
                $configuration->kadar,
            ));

        $rows = $selectedBranches->flatMap(function (Cabang $branch) use ($categories, $karats, $configurations) {
            return $categories->flatMap(function (KategoriBarang $category) use ($branch, $karats, $configurations) {
                return $karats->map(function (string $karat) use ($branch, $category, $configurations): array {
                    $configuration = $configurations->get($this->configurationKey($branch->id, $category->nama_kategori, $karat));

                    return [
                        'branch_id' => $branch->id,
                        'cabang' => $branch->nama_cabang,
                        'kategori' => $category->nama_kategori,
                        'kadar' => $karat,
                        'lead_time_hari' => $configuration?->lead_time_hari ?? InventoryAnalysisService::DEFAULT_LEAD_TIME_HARI,
                        'safety_stock' => $configuration?->safety_stock ?? InventoryAnalysisService::DEFAULT_SAFETY_STOCK,
                        'menggunakan_default' => $configuration === null,
                    ];
                });
            });
        })->sort(fn (array $left, array $right) => [$left['cabang'], $left['kategori'], $left['kadar']]
            <=> [$right['cabang'], $right['kategori'], $right['kadar']])
            ->when(filled($data['q'] ?? null), function ($rows) use ($data) {
                $keyword = mb_strtolower(trim($data['q']));

                return $rows->filter(fn (array $row) => str_contains(
                    mb_strtolower($row['kategori'].' '.$row['kadar']),
                    $keyword,
                ));
            })
            ->values();

        return view('analisis-persediaan.konfigurasi', [
            'metode' => $metode,
            'analysisRoute' => 'inventory-analysis.'.strtolower($metode),
            'settingsPrefix' => 'inventory-settings.'.strtolower($metode),
            'branches' => $branches,
            'configurations' => $this->paginateCollection($rows, $request),
        ]);
    }

    public function editConfiguration(Request $request): View
    {
        $data = $this->configurationKeys($request);
        $configuration = KonfigurasiPersediaan::firstOrNew($data);
        $configuration->lead_time_hari ??= InventoryAnalysisService::DEFAULT_LEAD_TIME_HARI;
        $configuration->safety_stock ??= InventoryAnalysisService::DEFAULT_SAFETY_STOCK;

        return view('analisis-persediaan.konfigurasi-form', [
            'metode' => $this->forecastMethod($request),
            'settingsPrefix' => 'inventory-settings.'.strtolower($this->forecastMethod($request)),
            'configuration' => $configuration,
            'branch' => Cabang::findOrFail($data['branch_id']),
        ]);
    }

    public function storeConfiguration(Request $request, AuditLogService $auditLogService): RedirectResponse
    {
        $data = array_merge($this->configurationKeys($request), $request->validate([
            'lead_time_hari' => ['required', 'integer', 'min:1', 'max:365'],
            'safety_stock' => ['required', 'integer', 'min:0', 'max:100000'],
        ]));

        $configuration = KonfigurasiPersediaan::firstOrNew([
            'method' => $this->forecastMethod($request),
            'branch_id' => $data['branch_id'],
            'kategori' => $data['kategori'],
            'kadar' => $data['kadar'],
        ]);
        $isNew = ! $configuration->exists;
        $oldValues = $isNew ? null : $configuration->toArray();
        $configuration->fill($data)->save();
        $auditLogService->log(
            $isNew ? 'create_inventory_configuration' : 'update_inventory_configuration',
            $configuration,
            $oldValues,
            $configuration->toArray(),
            $request,
        );

        $prefix = $request->route('forecast_method') ? 'inventory-settings.'.strtolower($this->forecastMethod($request)) : 'inventory-settings';

        return redirect()->route($prefix.'.index', ['branch_id' => $data['branch_id']])
            ->with('status', $isNew ? 'Konfigurasi persediaan berhasil ditambahkan.' : 'Konfigurasi persediaan berhasil diperbarui.');
    }

    /** @return array{branch_id:int,kategori:string,kadar:string} */
    private function configurationKeys(Request $request): array
    {
        /** @var array{branch_id:int,kategori:string,kadar:string} $data */
        $data = $request->validate([
            'branch_id' => ['required', 'integer', 'exists:cabang,id'],
            'kategori' => ['required', 'string', 'max:100', 'exists:kategori_barang,nama_kategori'],
            'kadar' => ['required', 'string', 'max:50', 'exists:harga_emas,kadar'],
        ]);

        return $data + ['method' => $this->forecastMethod($request)];
    }

    private function forecastMethod(Request $request): string
    {
        return $request->route('forecast_method') ?? 'SMA';
    }

    private function configurationKey(int $branchId, string $category, string $karat): string
    {
        return implode("\x1F", [$branchId, $category, $karat]);
    }

    private function highestTrend($rows): ?array
    {
        return $rows->filter(fn (array $row) => $row['penjualan_periode_berjalan'] > 0)
            ->sortByDesc(function (array $row): float {
                return $row['status_tren'] === 'baru'
                    ? INF
                    : ($row['persentase_perubahan'] ?? 0);
            })
            ->first();
    }
}
