<?php

namespace App\Http\Controllers;

use App\Models\Cabang;
use App\Models\Pengguna;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PenggunaController extends Controller
{
    public function index(Request $request): View
    {
        $this->validateListFilters($request);
        return view('pengguna.index', ['users' => Pengguna::with('branch')->latest()->paginate($this->perPage($request))->withQueryString()]);
    }

    public function create(): View
    {
        return view('pengguna.form', ['userModel' => new Pengguna(), 'branches' => Cabang::all()]);
    }

    public function store(Request $request, AuditLogService $audit): RedirectResponse
    {
        $data = $this->data($request);
        $data['password'] = Hash::make($data['password']);
        DB::transaction(function () use ($data, $request, $audit): void {
            $user = Pengguna::create($data);
            $audit->log('create_user', $user, null, $user->toArray(), $request);
        });

        return redirect()->route('users.index')->with('status', 'Pengguna tersimpan.');
    }

    public function edit(Pengguna $user): View
    {
        return view('pengguna.form', ['userModel' => $user, 'branches' => Cabang::all()]);
    }

    public function update(Request $request, Pengguna $user, AuditLogService $audit): RedirectResponse
    {
        $data = $this->data($request, $user);
        if (empty($data['password'])) {
            unset($data['password']);
        } else {
            $data['password'] = Hash::make($data['password']);
        }
        DB::transaction(function () use ($user, $data, $request, $audit): void {
            // Serialize edits to administrators so the final active admin cannot disappear.
            $admins = Pengguna::where('role', 'admin')->where('is_active', true)->orderBy('id')->lockForUpdate()->get();
            $user = Pengguna::whereKey($user->id)->lockForUpdate()->firstOrFail();
            if ($user->isAdmin() && $user->is_active && ($data['role'] !== 'admin' || ! $data['is_active']) && $admins->count() <= 1) {
                throw ValidationException::withMessages(['role' => 'Minimal satu admin aktif harus tetap tersedia.']);
            }
            $old = $user->toArray();
            $user->update($data);
            $audit->log('update_user', $user, $old, $user->toArray(), $request);
        });

        return redirect()->route('users.index')->with('status', 'Pengguna diperbarui.');
    }

    private function data(Request $request, ?Pengguna $user = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:pengguna,email,'.($user?->id ?? 'NULL').',id'],
            'password' => [$user ? 'nullable' : 'required', Password::defaults()],
            'role' => ['required', 'in:admin,toko_cabang'],
            'branch_id' => ['required_if:role,toko_cabang', 'nullable', 'exists:cabang,id'],
            'is_active' => ['boolean'],
        ]) + ['is_active' => false];
        $data['branch_id'] = $data['role'] === 'admin' ? null : $data['branch_id'];

        return $data;
    }
}
