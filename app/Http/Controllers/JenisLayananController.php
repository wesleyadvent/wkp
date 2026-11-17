<?php

namespace App\Http\Controllers;

use App\Models\NotaLayanan;
use App\Models\ItemNotaLayanan;
use App\Models\JenisLayanan;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class JenisLayananController extends Controller
{
    public function index(Request $request): View
    {
        $this->validateListFilters($request);
        return view('sederhana.index', [
            'title' => 'Jenis Service',
            'route' => 'service-types',
            'columns' => ['nama_service' => 'Nama', 'harga_default' => 'Harga Default', 'is_active' => 'Aktif'],
            'items' => JenisLayanan::latest()->paginate($this->perPage($request))->withQueryString(),
        ]);
    }

    public function create(): View
    {
        return view('sederhana.service-type-form', ['serviceType' => new JenisLayanan]);
    }

    public function store(Request $request, AuditLogService $audit): RedirectResponse
    {
        $data = $this->data($request);
        DB::transaction(function () use ($data, $request, $audit): void {
            $item = JenisLayanan::create($data);
            $audit->log('create_service_type', $item, null, $item->toArray(), $request);
        });

        return redirect()->route('service-types.index')->with('status', 'Jenis service tersimpan.');
    }

    public function edit(JenisLayanan $serviceType): View
    {
        return view('sederhana.service-type-form', compact('serviceType'));
    }

    public function update(Request $request, JenisLayanan $serviceType, AuditLogService $audit): RedirectResponse
    {
        $data = $this->data($request);
        DB::transaction(function () use ($serviceType, $data, $request, $audit): void {
            $serviceType = JenisLayanan::whereKey($serviceType->id)->lockForUpdate()->firstOrFail();
            $old = $serviceType->toArray();
            $serviceType->update($data);
            $audit->log('update_service_type', $serviceType, $old, $serviceType->toArray(), $request);
        });

        return redirect()->route('service-types.index')->with('status', 'Jenis service diperbarui.');
    }

    public function destroy(Request $request, JenisLayanan $serviceType, AuditLogService $auditLogService): RedirectResponse
    {
        DB::transaction(function () use ($request, $serviceType, $auditLogService): void {
            $serviceType = JenisLayanan::whereKey($serviceType->id)->lockForUpdate()->firstOrFail();
            if (NotaLayanan::where('jenis_service_id', $serviceType->id)->exists() || ItemNotaLayanan::where('service_type_id', $serviceType->id)->exists()) {
                throw ValidationException::withMessages(['delete' => 'Jenis service masih digunakan oleh barang atau transaksi. Nonaktifkan melalui Edit jika tidak ingin digunakan lagi.']);
            }
            $old = $serviceType->toArray();
            $serviceType->delete();
            $auditLogService->log('delete_service_types', $serviceType, $old, null, $request);
        });

        return redirect()->route('service-types.index')->with('status', 'Jenis service berhasil dihapus.');
    }

    private function data(Request $request): array
    {
        return $request->validate(['nama_service' => ['required', 'string', 'max:255'], 'harga_default' => ['required', 'numeric', 'decimal:0,2', 'gte:0', 'max:9999999999999.99'], 'keterangan' => ['nullable', 'string'], 'is_active' => ['boolean']]) + ['is_active' => false];
    }
}
