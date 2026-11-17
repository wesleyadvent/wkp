<?php

namespace App\Http\Controllers;

use App\Models\Karyawan;
use App\Services\AuditLogService;
use App\Services\MasterDataUsage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class KaryawanController extends Controller
{
    public function verify(Request $request): JsonResponse
    {
        $this->noteEmployee($request);

        return response()->json(['valid' => true]);
    }

    public function index(Request $request)
    {
        $this->validateListFilters($request);
        return view('sederhana.index', [
            'title' => 'Daftar Karyawan', 'route' => 'employees',
            'columns' => ['nama' => 'Nama karyawan', 'is_active' => 'Aktif'],
            'items' => Karyawan::orderBy('nama')->paginate($this->perPage($request))->withQueryString(),
        ]);
    }

    public function create()
    {
        return view('sederhana.employee-form', ['employee' => new Karyawan]);
    }

    public function store(Request $request, AuditLogService $audit)
    {
        $data = $request->validate(['nama' => ['required', 'string', 'max:255'], 'is_active' => ['required', 'boolean']]);
        $employee = Karyawan::create($data);
        $audit->log('create_employee', $employee, null, $employee->toArray(), $request);

        return redirect()->route('employees.index')->with('status', 'Karyawan ditambahkan.');
    }

    public function edit(Karyawan $employee)
    {
        return view('sederhana.employee-form', compact('employee'));
    }

    public function destroy(Request $request, Karyawan $employee, AuditLogService $audit)
    {
        DB::transaction(function () use ($request, $employee, $audit): void {
            $employee = Karyawan::whereKey($employee->id)->lockForUpdate()->firstOrFail();
            MasterDataUsage::assertUnused($employee);
            $old = $employee->toArray();
            $employee->delete();
            $audit->log('delete_employee', $employee, $old, null, $request);
        });

        return redirect()->route('employees.index')->with('status', 'Karyawan berhasil dihapus.');
    }

    public function update(Request $request, Karyawan $employee, AuditLogService $audit)
    {
        $data = $request->validate(['nama' => ['required', 'string', 'max:255'], 'is_active' => ['required', 'boolean']]);
        $old = $employee->toArray();
        $employee->update($data);
        $audit->log('update_employee', $employee, $old, $employee->toArray(), $request);

        return redirect()->route('employees.index')->with('status', 'Karyawan diperbarui.');
    }
}
