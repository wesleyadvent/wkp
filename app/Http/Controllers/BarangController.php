<?php

namespace App\Http\Controllers;

use App\Models\Cabang;
use App\Models\HargaEmas;
use App\Models\Barang;
use App\Models\KategoriBarang;
use App\Models\Pengguna;
use App\Services\AuditLogService;
use App\Support\ProductCode;
use App\Support\TransactionAmounts;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class BarangController extends Controller
{
    public function index(Request $request): View
    {
        $this->validateListFilters($request);
        $branches = Cabang::orderBy('nama_cabang')->get();
        if ($request->user()->isAdmin()) {
            $request->validate(['branch_id' => ['nullable', 'integer', 'exists:cabang,id']]);
        }
        $defaultBranchId = $request->user()->isAdmin()
            ? ($request->filled('branch_id') ? $request->integer('branch_id') : null)
            : null;

        $query = $this->scoped(Barang::with('branch', 'kategoriBarang'), $request)
            ->when($request->filled('q'), function ($query) use ($request): void {
                $keyword = $request->string('q');
                $query->where(function ($query) use ($keyword): void {
                    $query->where('kode_barang', 'like', "%{$keyword}%")
                        ->orWhere('nama_barang', 'like', "%{$keyword}%")
                        ->orWhereHas('kategoriBarang', fn ($category) => $category->where('nama_kategori', 'like', "%{$keyword}%"))
                        ->orWhere('kadar', 'like', "%{$keyword}%");
                });
            })
            ->when($request->filled('kategori'), fn ($query) => $query->whereHas('kategoriBarang', fn ($category) => $category->where('nama_kategori', $request->kategori)))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->status))
            ->when($request->user()->isAdmin() && ! $defaultBranchId, fn ($query) => $query->whereRaw('1 = 0'))
            ->when($defaultBranchId && $request->user()->isAdmin(), fn ($query) => $query->where('branch_id', $defaultBranchId));

        return view('barang.index', [
            'products' => $query->latest()->paginate($this->perPage($request))->withQueryString(),
            'categories' => KategoriBarang::where('is_active', true)->orderBy('nama_kategori')->get(),
            'branches' => $branches,
            'selectedBranchId' => $defaultBranchId,
            'requiresBranchFilter' => $request->user()->isAdmin() && ! $defaultBranchId,
        ]);
    }

    public function create(Request $request): View
    {
        return view('barang.create', $this->formData(new Barang));
    }

    public function store(Request $request, AuditLogService $auditLogService): RedirectResponse
    {
        if ($request->has('items_json')) {
            $request->validate(['items_json' => ['required', 'json']]);
            $request->merge(['items' => json_decode($request->input('items_json'), true)]);
        }
        // Keep the existing single-item submission compatible.
        if (! $request->has('items') && (string) $request->input('jumlah', 1) === '1') {
            $request->validate(['status' => ['nullable', 'in:tersedia']]);
            $request->merge(['items' => [$request->only(['nama_barang', 'kategori_barang_id', 'kadar', 'berat', 'ongkos', 'deskripsi', 'status'])], 'jumlah' => 1]);
            if ($request->hasFile('photo')) {
                $request->files->set('photos', [0 => $request->file('photo')]);
            }
        }
        $data = $request->validate([
            'jumlah' => ['required', 'integer', 'min:1', 'max:1000'],
            'branch_id' => [$request->user()->isAdmin() ? 'required' : 'nullable', 'exists:cabang,id'],
            'items' => ['required', 'array', 'min:1', 'max:1000'],
            'items.*' => ['required', 'array'],
            'items.*.nama_barang' => ['required', 'string', 'max:255'],
            'items.*.kategori_barang_id' => ['required', 'integer', Rule::exists('kategori_barang', 'id')->where('is_active', true)],
            'items.*.kadar' => ['required', 'string', 'max:50', 'exists:harga_emas,kadar'],
            'items.*.berat' => ['required', 'numeric', 'decimal:0,3', 'gt:0', 'max:9999999.999'],
            'items.*.ongkos' => ['nullable', 'numeric', 'decimal:0,2', 'gte:0', 'max:9999999999999.99'],
            'items.*.deskripsi' => ['nullable', 'string'],
            'items.*.status' => ['nullable', 'in:tersedia'],
            'photos' => ['nullable', 'array', 'max:1000'],
            'photos.*' => ['nullable', 'image', 'max:2048'],
        ], ['items.required' => 'Isi rincian setiap barang terlebih dahulu.']);
        if (count($data['items']) !== (int) $data['jumlah'] || ! array_is_list($data['items'])) {
            throw ValidationException::withMessages(['jumlah' => 'Jumlah form barang harus sesuai dengan jumlah unit yang dipilih.']);
        }
        $branchId = $this->selectedBranchId($request);
        $batch = (string) Str::ulid();
        $photos = [];
        try {
            DB::transaction(function () use ($data, $branchId, $batch, $request, $auditLogService, &$photos): void {
                foreach ($data['items'] as $index => $item) {
                    $price = HargaEmas::where('kadar', $item['kadar'])->latest('tanggal')->latest('id')->firstOrFail()->harga_per_gram;
                    $total = TransactionAmounts::weighted($item['berat'], $price)->plus(TransactionAmounts::money($item['ongkos'] ?? 0));
                    TransactionAmounts::assertFits($total, "items.{$index}.berat");
                    $unit = [
                        'branch_id' => $branchId, 'creation_batch' => $batch, 'status' => 'tersedia',
                        'nama_barang' => $item['nama_barang'], 'kategori_barang_id' => $item['kategori_barang_id'], 'kadar' => $item['kadar'],
                        'berat' => $item['berat'], 'ongkos' => $item['ongkos'] ?? 0, 'deskripsi' => $item['deskripsi'] ?? null,
                        'harga_per_gram' => $price, 'total_harga' => (string) $total,
                    ];
                    if ($request->hasFile("photos.{$index}")) {
                        $unit['photo_path'] = $request->file("photos.{$index}")->store('product-photos', 'public');
                        $photos[] = $unit['photo_path'];
                    }
                    $product = Barang::create($unit);
                    $auditLogService->log('create_product', $product, null, $product->toArray(), $request);
                }
            });
        } catch (\Throwable $exception) {
            Storage::disk('public')->delete($photos);
            throw $exception;
        }

        return redirect()->route('products.labels', ['batch' => $batch])
            ->with('status', $data['jumlah'].' barang berhasil disimpan. Seluruh barcode siap dicetak.');
    }

    public function edit(Request $request, Barang $product): View
    {
        $this->authorizeBranch($request, $product);
        $this->ensureEditable($product);

        return view('barang.form', $this->formData($product));
    }

    public function update(Request $request, Barang $product, AuditLogService $auditLogService): RedirectResponse
    {
        $this->authorizeBranch($request, $product);
        $data = $this->validated($request, $product->id);
        $data['branch_id'] = $this->selectedBranchId($request);
        unset($data['kode_barang'], $data['jumlah']);
        if ($request->hasFile('photo')) {
            $data['photo_path'] = $request->file('photo')->store('product-photos', 'public');
        }
        unset($data['photo']);
        try {
            DB::transaction(function () use ($request, $product, $data, $auditLogService): void {
                $product = Barang::whereKey($product->id)->lockForUpdate()->firstOrFail();
                $this->authorizeBranch($request, $product);
                $this->ensureEditable($product);
                $old = $product->toArray();
                $data['harga_per_gram'] = HargaEmas::where('kadar', $data['kadar'])->latest('tanggal')->latest('id')->firstOrFail()->harga_per_gram;
                $total = TransactionAmounts::weighted($data['berat'], $data['harga_per_gram'])->plus(TransactionAmounts::money($data['ongkos'] ?? 0));
                TransactionAmounts::assertFits($total, 'berat');
                $data['total_harga'] = (string) $total;
                $product->update($data);
                $auditLogService->log('update_product', $product, $old, $product->toArray(), $request);
            });
        } catch (\Throwable $exception) {
            if (isset($data['photo_path'])) {
                Storage::disk('public')->delete($data['photo_path']);
            }
            throw $exception;
        }

        return redirect()->route('products.index')->with('status', 'Barang berhasil diperbarui.');
    }

    public function secureDelete(Request $request, Barang $product, AuditLogService $auditLogService): RedirectResponse
    {
        abort_unless($request->user()->isAdmin(), 403);
        $this->ensureEditable($product);

        $data = $request->validate([
            'admin_email' => ['required', 'email'],
            'admin_password' => ['required', 'string'],
            'alasan' => ['required', 'string', 'max:1000'],
        ]);

        $admin = Pengguna::where('email', $data['admin_email'])->where('role', 'admin')->where('is_active', true)->first();
        if (! $admin || ! Hash::check($data['admin_password'], $admin->password)) {
            throw ValidationException::withMessages(['admin_email' => 'Verifikasi admin tidak valid.']);
        }

        DB::transaction(function () use ($product, $data, $request, $auditLogService): void {
            $product = Barang::whereKey($product->id)->lockForUpdate()->firstOrFail();
            $this->ensureEditable($product);
            if (\App\Models\ItemPenjualan::where('product_id', $product->id)->exists()
                || \App\Models\ItemPembelian::where('product_id_ref', $product->id)->exists()
                || \App\Models\NotaLayanan::where('product_id_ref', $product->id)->exists()) {
                throw ValidationException::withMessages(['delete' => 'Barang sudah pernah digunakan pada nota dan tidak boleh dihapus.']);
            }
            $old = $product->toArray();
            $product->delete();
            $auditLogService->log('soft_delete_product', $product, $old, ['alasan' => $data['alasan']], $request);
        });

        return back()->with('status', 'Barang diarsipkan dengan soft delete.');
    }

    private function validated(Request $request, ?int $id = null): array
    {
        $data = $request->validate([
            'jumlah' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'branch_id' => [$request->user()->isAdmin() ? 'required' : 'nullable', 'exists:cabang,id'],
            'nama_barang' => ['required', 'string', 'max:255'],
            'kategori_barang_id' => ['required', 'integer', Rule::exists('kategori_barang', 'id')->where('is_active', true)],
            'kadar' => ['required', 'string', 'max:50', 'exists:harga_emas,kadar'],
            'berat' => ['required', 'numeric', 'decimal:0,3', 'gt:0', 'max:9999999.999'],
            'harga_per_gram' => ['nullable', 'numeric', 'gte:0'],
            'ongkos' => ['nullable', 'numeric', 'decimal:0,2', 'gte:0', 'max:9999999999999.99'],
            'deskripsi' => ['nullable', 'string'],
            'photo' => ['nullable', 'image', 'max:2048'],
            'status' => [$id ? 'required' : 'nullable', $id ? 'in:tersedia,service,arsip' : 'in:tersedia'],
        ]);

        $data['harga_per_gram'] = HargaEmas::where('kadar', $data['kadar'])
            ->latest('tanggal')->latest('id')->firstOrFail()->harga_per_gram;

        return $data;
    }

    private function authorizeBranch(Request $request, Barang $product): void
    {
        abort_if($request->user()->isBranchUser() && $product->branch_id !== $request->user()->branch_id, 403);
    }

    public function photo(Request $request, Barang $product)
    {
        $this->authorizeBranch($request, $product);
        $disk = Storage::disk('public');
        abort_unless($product->photo_path && $disk->exists($product->photo_path), 404, 'File foto tidak ditemukan.');

        return response()->file($disk->path($product->photo_path), ['Cache-Control' => 'private, max-age=3600']);
    }

    private function ensureEditable(Barang $product): void
    {
        abort_if($product->status === 'terjual', 403, 'Barang yang sudah terjual tidak boleh diedit atau dihapus.');
    }

    public function barcode(Request $request, Barang $product)
    {
        $this->authorizeBranch($request, $product);
        $svg = ProductCode::svg($product->kode_barang);

        return response($svg, 200, [
            'Content-Type' => 'image/svg+xml',
            'Content-Disposition' => 'inline; filename="barcode-'.$product->id.'.svg"',
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }

    public function labels(Request $request): View
    {
        $data = $request->validate([
            'batch' => ['nullable', 'string', 'size:26'],
            'ids' => ['required_without:batch', 'array', 'min:1', 'max:1000'],
            'ids.*' => ['integer', 'distinct', 'exists:barang,id'],
        ]);
        $query = $this->scoped(Barang::with('branch', 'kategoriBarang'), $request);
        if (! empty($data['batch'])) {
            $query->where('creation_batch', $data['batch']);
        } else {
            $query->whereIn('id', $data['ids']);
        }
        $products = $query->orderBy('id')->get();
        abort_if($products->isEmpty(), 404, 'Label barang tidak ditemukan pada cabang Anda.');
        if (empty($data['batch'])) {
            abort_unless($products->count() === count($data['ids']), 403, 'Barang tidak tersedia di cabang Anda.');
        }

        return view('barang.labels', compact('products'));
    }

    private function formData(Barang $product): array
    {
        return [
            'product' => $product,
            'branches' => Cabang::orderBy('nama_cabang')->get(),
            'categories' => KategoriBarang::where('is_active', true)->orderBy('nama_kategori')->get(),
            'goldPrices' => HargaEmas::query()
                ->latest('tanggal')
                ->latest('id')
                ->get()
                ->unique('kadar')
                ->values(),
        ];
    }
}
