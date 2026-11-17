<?php

namespace App\Http\Controllers;

use App\Models\Cabang;
use App\Models\LogLoginCabang;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CabangController extends Controller
{
    public function index(Request $request): View
    {
        $this->validateListFilters($request);
        return view('cabang.index', ['branches' => Cabang::latest()->paginate($this->perPage($request))->withQueryString()]);
    }

    public function create(): View
    {
        return view('cabang.form', ['branch' => new Cabang()]);
    }

    public function store(Request $request, AuditLogService $auditLogService): RedirectResponse
    {
        $data = $this->validated($request);
        DB::transaction(function () use ($data, $request, $auditLogService): void {
            $branch = Cabang::create($data);
            $auditLogService->log('create_branch', $branch, null, $branch->toArray(), $request);
        });

        return redirect()->route('branches.index')->with('status', 'Cabang berhasil ditambahkan.');
    }

    public function edit(Cabang $branch): View
    {
        return view('cabang.form', compact('branch'));
    }

    public function update(Request $request, Cabang $branch, AuditLogService $auditLogService): RedirectResponse
    {
        $data = $this->validated($request, $branch->id);
        DB::transaction(function () use ($branch, $data, $request, $auditLogService): void {
            $branch = Cabang::whereKey($branch->id)->lockForUpdate()->firstOrFail();
            $old = $branch->toArray();
            $branch->update($data);
            $auditLogService->log('update_branch', $branch, $old, $branch->toArray(), $request);
        });

        return redirect()->route('branches.index')->with('status', 'Cabang berhasil diperbarui.');
    }

    public function toggleLogin(Request $request, Cabang $branch, AuditLogService $auditLogService): RedirectResponse
    {
        $data = $request->validate(['alasan' => ['required', 'string', 'max:1000']]);

        DB::transaction(function () use ($branch, $data, $request, $auditLogService): void {
            $branch = Cabang::whereKey($branch->id)->lockForUpdate()->firstOrFail();
            $old = $branch->is_login_enabled;
            $new = ! $old;
            $oldValues = $branch->toArray();

            $branch->forceFill([
                'is_login_enabled' => $new,
                'login_disabled_at' => $new ? null : now(),
                'login_disabled_by' => $new ? null : $request->user()->id,
                'login_disabled_reason' => $new ? null : $data['alasan'],
            ])->save();

            LogLoginCabang::create([
                'branch_id' => $branch->id,
                'admin_id' => $request->user()->id,
                'old_status' => $old,
                'new_status' => $new,
                'alasan' => $data['alasan'],
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'created_at' => now(),
            ]);

            $auditLogService->log('toggle_branch_login', $branch, $oldValues, $branch->fresh()->toArray(), $request);
        });

        return back()->with('status', 'Status login cabang berhasil diubah.');
    }

    public function logs(Request $request): View
    {
        return view('cabang.login-logs', [
            'logs' => LogLoginCabang::with('branch')->latest('created_at')->paginate($this->perPage($request, 20))->withQueryString(),
        ]);
    }

    private function validated(Request $request, ?int $id = null): array
    {
        return $request->validate([
            'kode_cabang' => ['required', 'string', 'max:30', 'unique:cabang,kode_cabang,'.($id ?? 'NULL').',id'],
            'nama_cabang' => ['required', 'string', 'max:255'],
            'alamat' => ['nullable', 'string'],
            'telepon' => ['nullable', 'string', 'max:50'],
        ]);
    }
}
