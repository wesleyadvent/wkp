<?php

namespace App\Http\Controllers;

use App\Models\JenisPotongan;
use App\Models\PotonganPembelian;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class JenisPotonganController extends Controller
{
    public function index(Request $request): View
    {
        $this->validateListFilters($request);
        return view('sederhana.index', [
            'title' => 'Potongan Harga',
            'route' => 'deduction-types',
            'columns' => ['nama_potongan' => 'Nama', 'nominal' => 'Nominal', 'is_active' => 'Aktif'],
            'items' => JenisPotongan::latest()->paginate($this->perPage($request))->withQueryString(),
        ]);
    }

    public function create(): View
    {
        return view('sederhana.deduction-form', ['deductionType' => new JenisPotongan]);
    }

    public function store(Request $request, AuditLogService $audit): RedirectResponse
    {
        $data = $this->data($request);
        DB::transaction(function () use ($data, $request, $audit): void {
            $item = JenisPotongan::create($data);
            $audit->log('create_deduction_type', $item, null, $item->toArray(), $request);
        });

        return redirect()->route('deduction-types.index')->with('status', 'Potongan tersimpan.');
    }

    public function edit(JenisPotongan $deductionType): View
    {
        return view('sederhana.deduction-form', compact('deductionType'));
    }

    public function update(Request $request, JenisPotongan $deductionType, AuditLogService $audit): RedirectResponse
    {
        $data = $this->data($request);
        DB::transaction(function () use ($deductionType, $data, $request, $audit): void {
            $deductionType = JenisPotongan::whereKey($deductionType->id)->lockForUpdate()->firstOrFail();
            $old = $deductionType->toArray();
            $deductionType->update($data);
            $audit->log('update_deduction_type', $deductionType, $old, $deductionType->toArray(), $request);
        });

        return redirect()->route('deduction-types.index')->with('status', 'Potongan diperbarui.');
    }

    public function destroy(Request $request, JenisPotongan $deductionType, AuditLogService $auditLogService): RedirectResponse
    {
        DB::transaction(function () use ($request, $deductionType, $auditLogService): void {
            $deductionType = JenisPotongan::whereKey($deductionType->id)->lockForUpdate()->firstOrFail();
            if (PotonganPembelian::where('deduction_type_id', $deductionType->id)->exists()) {
                throw ValidationException::withMessages(['delete' => 'Potongan masih digunakan oleh barang atau transaksi. Nonaktifkan melalui Edit jika tidak ingin digunakan lagi.']);
            }
            $old = $deductionType->toArray();
            $deductionType->delete();
            $auditLogService->log('delete_deduction_types', $deductionType, $old, null, $request);
        });

        return redirect()->route('deduction-types.index')->with('status', 'Potongan berhasil dihapus.');
    }

    private function data(Request $request): array
    {
        return $request->validate(['nama_potongan' => ['required', 'string', 'max:255'], 'nominal' => ['required', 'numeric', 'decimal:0,2', 'gte:0', 'max:9999999999999.99'], 'keterangan' => ['nullable', 'string'], 'is_active' => ['boolean']]) + ['is_active' => false];
    }
}
