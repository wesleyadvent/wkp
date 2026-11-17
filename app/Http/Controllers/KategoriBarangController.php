<?php

namespace App\Http\Controllers;

use App\Models\Barang;
use App\Models\KategoriBarang;
use App\Models\ItemPembelian;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class KategoriBarangController extends Controller
{
    public function index(Request $request): View
    {
        $this->validateListFilters($request);
        return view('sederhana.index', [
            'title' => 'Kategori Barang',
            'route' => 'product-categories',
            'columns' => ['nama_kategori' => 'Nama Kategori', 'is_active' => 'Aktif', 'keterangan' => 'Keterangan'],
            'items' => KategoriBarang::latest()->paginate($this->perPage($request))->withQueryString(),
        ]);
    }

    public function create(): View
    {
        return view('sederhana.product-category-form', ['productCategory' => new KategoriBarang]);
    }

    public function store(Request $request, AuditLogService $audit): RedirectResponse
    {
        $data = $this->data($request);
        DB::transaction(function () use ($data, $request, $audit): void {
            $item = KategoriBarang::create($data);
            $audit->log('create_product_category', $item, null, $item->toArray(), $request);
        });

        return redirect()->route('product-categories.index')->with('status', 'Kategori barang tersimpan.');
    }

    public function edit(KategoriBarang $productCategory): View
    {
        return view('sederhana.product-category-form', compact('productCategory'));
    }

    public function update(Request $request, KategoriBarang $productCategory, AuditLogService $audit): RedirectResponse
    {
        $data = $this->data($request, $productCategory->id);
        DB::transaction(function () use ($productCategory, $data, $request, $audit): void {
            $productCategory = KategoriBarang::whereKey($productCategory->id)->lockForUpdate()->firstOrFail();
            $oldName = $productCategory->nama_kategori;
            $old = $productCategory->toArray();
            $productCategory->update($data);
            if ($oldName !== $data['nama_kategori']) {
                \App\Models\KonfigurasiPersediaan::where('kategori', $oldName)->update(['kategori' => $data['nama_kategori']]);
            }
            $audit->log('update_product_category', $productCategory, $old, $productCategory->toArray(), $request);
        });

        return redirect()->route('product-categories.index')->with('status', 'Kategori barang diperbarui.');
    }

    public function destroy(Request $request, KategoriBarang $productCategory, AuditLogService $auditLogService): RedirectResponse
    {
        DB::transaction(function () use ($request, $productCategory, $auditLogService): void {
            $productCategory = KategoriBarang::whereKey($productCategory->id)->lockForUpdate()->firstOrFail();
            if (Barang::withTrashed()->where('kategori_barang_id', $productCategory->id)->exists() || ItemPembelian::where('kategori', $productCategory->nama_kategori)->exists()) {
                throw ValidationException::withMessages(['delete' => 'Kategori barang masih digunakan oleh barang atau transaksi. Nonaktifkan melalui Edit jika tidak ingin digunakan lagi.']);
            }
            $old = $productCategory->toArray();
            $productCategory->delete();
            $auditLogService->log('delete_product_categories', $productCategory, $old, null, $request);
        });

        return redirect()->route('product-categories.index')->with('status', 'Kategori barang berhasil dihapus.');
    }

    private function data(Request $request, ?int $id = null): array
    {
        return $request->validate([
            'nama_kategori' => ['required', 'string', 'max:255', 'unique:kategori_barang,nama_kategori,'.($id ?? 'NULL').',id'],
            'keterangan' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ]) + ['is_active' => false];
    }
}
