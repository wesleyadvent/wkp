<?php

namespace App\Http\Controllers;

use App\Models\Cabang;
use App\Models\Pelanggan;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;

class PelangganController extends Controller
{
    public function index(Request $request): View
    {
        $this->validateListFilters($request);
        $query = $this->scoped(Pelanggan::with('branch'), $request)
            ->when($request->filled('q'), function ($query) use ($request): void {
                $keyword = '%'.trim($request->input('q')).'%';
                $query->where(function ($query) use ($keyword): void {
                    $query->where('nama', 'like', $keyword)
                        ->orWhere('telepon', 'like', $keyword)
                        ->orWhere('alamat', 'like', $keyword);
                });
            })
            ->when($request->filled('branch_id') && $request->user()->isAdmin(), fn ($query) => $query->where('branch_id', $request->branch_id));

        return view('sederhana.index', [
            'title' => 'Pelanggan',
            'route' => 'customers',
            'columns' => ['nama' => 'Nama', 'telepon' => 'Telepon', 'alamat' => 'Alamat', 'branch.nama_cabang' => 'Cabang'],
            'items' => $query->latest()->paginate($this->perPage($request))->withQueryString(),
            'branches' => Cabang::orderBy('nama_cabang')->get(),
        ]);
    }

    public function create(Request $request): View
    {
        return view('sederhana.customer-form', ['customer' => new Pelanggan(), 'branches' => Cabang::all()]);
    }

    public function store(Request $request, AuditLogService $auditLogService): RedirectResponse
    {
        $data = $request->validate(['nama' => ['required', 'string', 'max:255'], 'telepon' => ['nullable', 'string', 'max:50'], 'alamat' => ['nullable', 'string'], 'branch_id' => [$request->user()->isAdmin() ? 'required' : 'nullable', 'exists:cabang,id']]);
        $data['branch_id'] = $this->selectedBranchId($request);
        $data['created_by'] = $request->user()->id;
        DB::transaction(function () use ($data, $request, $auditLogService): void {
            $customer = Pelanggan::create($data);
            $auditLogService->log('create_customer', $customer, null, $customer->toArray(), $request);
        });

        return redirect()->route('customers.index')->with('status', 'Pelanggan tersimpan.');
    }

    public function edit(Request $request, Pelanggan $customer): View
    {
        abort_if($request->user()->isBranchUser() && $customer->branch_id !== $request->user()->branch_id, 403);

        return view('sederhana.customer-form', ['customer' => $customer, 'branches' => Cabang::all()]);
    }

    public function update(Request $request, Pelanggan $customer, AuditLogService $auditLogService): RedirectResponse
    {
        abort_if($request->user()->isBranchUser() && $customer->branch_id !== $request->user()->branch_id, 403);
        $data = $request->validate(['nama' => ['required', 'string', 'max:255'], 'telepon' => ['nullable', 'string', 'max:50'], 'alamat' => ['nullable', 'string'], 'branch_id' => [$request->user()->isAdmin() ? 'required' : 'nullable', 'exists:cabang,id']]);
        $data['branch_id'] = $this->selectedBranchId($request);
        DB::transaction(function () use ($customer, $data, $request, $auditLogService): void {
            $customer = Pelanggan::whereKey($customer->id)->lockForUpdate()->firstOrFail();
            abort_if($request->user()->isBranchUser() && $customer->branch_id !== $request->user()->branch_id, 403);
            $old = $customer->toArray();
            $customer->update($data);
            $auditLogService->log('update_customer', $customer, $old, $customer->toArray(), $request);
        });

        return redirect()->route('customers.index')->with('status', 'Pelanggan diperbarui.');
    }
}
