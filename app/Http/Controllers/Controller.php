<?php

namespace App\Http\Controllers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

abstract class Controller
{
    /** @var array<int, int> */
    protected const PAGE_SIZE_OPTIONS = [10, 15, 20, 25, 50, 100];

    protected function validateListFilters(Request $request): void
    {
        $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'date' => ['nullable', 'date'],
            'tanggal' => ['nullable', 'date'],
            'branch_id' => ['nullable', 'integer', 'exists:cabang,id'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', \Illuminate\Validation\Rule::in(self::PAGE_SIZE_OPTIONS)],
        ]);
    }

    protected function perPage(Request $request, int $default = 15): int
    {
        $perPage = $request->integer('per_page', $default);

        return in_array($perPage, self::PAGE_SIZE_OPTIONS, true) ? $perPage : $default;
    }

    /** @param Collection<int, mixed> $items */
    protected function paginateCollection(Collection $items, Request $request, int $default = 15): LengthAwarePaginator
    {
        $perPage = $this->perPage($request, $default);
        $page = LengthAwarePaginator::resolveCurrentPage();

        return new LengthAwarePaginator(
            $items->forPage($page, $perPage)->values(),
            $items->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()],
        );
    }

    protected function noteEmployee(Request $request): array
    {
        $request->validate(['employee_name' => ['required', 'string', 'max:255']]);
        $name = mb_strtolower(preg_replace('/\s+/u', ' ', trim($request->input('employee_name'))));
        $matches = \App\Models\Karyawan::where('is_active', true)->get()->filter(
            fn ($employee) => mb_strtolower(preg_replace('/\s+/u', ' ', trim($employee->nama))) === $name
        );
        if ($matches->count() !== 1) {
            throw \Illuminate\Validation\ValidationException::withMessages(['employee_name' => 'Nama harus sesuai dengan satu karyawan aktif yang terdaftar. Hubungi admin jika nama belum terdaftar atau sama dengan karyawan lain.']);
        }
        $employee = $matches->first();
        return ['employee_id' => $employee->id, 'employee_name' => $employee->nama];
    }

    protected function referenceSale(Request $request, string $code, bool $allBranches = false): \App\Models\Penjualan
    {
        $query = \App\Models\Penjualan::with('customer', 'items.product.kategoriBarang');
        if (! $allBranches) {
            $query = $this->scoped($query, $request);
        }
        $sale = $query->where('nomor_nota', trim($code))->where('status', 'valid')->first();
        if (! $sale || (! $allBranches && $request->filled('branch_id') && $sale->branch_id !== $this->selectedBranchId($request))) {
            throw \Illuminate\Validation\ValidationException::withMessages(['nomor_nota' => 'Nota penjualan valid tidak ditemukan pada cabang ini.']);
        }
        foreach ($sale->items as $item) {
            if ($item->product && $item->product->photo_path) {
                $item->product->setAttribute('photo_url', route('products.photo', $item->product));
            }
        }
        return $sale;
    }

    protected function fillReferenceItem(Request $request, string $sourceField, bool $allBranches = false): void
    {
        if ($request->input($sourceField) !== 'toko_sendiri') {
            $request->merge(['sale_id_ref' => null, 'product_id_ref' => null]);
            return;
        }
        $request->validate(['nomor_nota' => ['required', 'string', 'max:255']]);
        $sale = $this->referenceSale($request, $request->input('nomor_nota'), $allBranches);
        $item = $request->filled('product_id_ref')
            ? $sale->items->firstWhere('product_id', (int) $request->input('product_id_ref'))
            : ($sale->items->count() === 1 ? $sale->items->first() : null);
        if (! $item || ! $item->product) {
            throw \Illuminate\Validation\ValidationException::withMessages(['nomor_nota' => 'Pilih barang yang tercantum pada nota penjualan ini.']);
        }
        $request->merge([
            'sale_id_ref' => $sale->id, 'product_id_ref' => $item->product_id,
            'customer_id' => $sale->customer_id, 'nama_barang' => $item->receipt('product')->nama_barang,
            'nama_barang_manual' => $item->receipt('product')->nama_barang, 'kategori' => $item->receipt('product')->kategori,
            'kadar' => $item->receipt('product')->kadar, 'berat' => $item->berat,
        ]);
    }

    protected function referenceSaleData(\App\Models\Penjualan $sale): array
    {
        $data = $sale->toArray();
        $data['customer'] = $sale->receipt('customer');
        foreach ($sale->items as $index => $item) {
            $product = $item->receipt('product');
            $data['items'][$index]['product'] = $product ? (array) $product : null;
            if ($product?->photo_path) {
                $data['items'][$index]['product']['photo_url'] = route('products.photo', $product->id);
            }
        }

        return $data;
    }

    protected function scoped(Builder $query, Request $request, string $column = 'branch_id'): Builder
    {
        $user = $request->user();

        if ($user && $user->isBranchUser()) {
            $query->where($column, $user->branch_id);
        }

        return $query;
    }

    protected function selectedBranchId(Request $request): int
    {
        $user = $request->user();

        return $user->isAdmin() ? (int) $request->input('branch_id') : (int) $user->branch_id;
    }
}
