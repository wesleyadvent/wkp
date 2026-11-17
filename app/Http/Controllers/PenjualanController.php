<?php

namespace App\Http\Controllers;

use App\Models\Cabang;
use App\Models\Pelanggan;
use App\Models\Barang;
use App\Models\Penjualan;
use App\Services\AuditLogService;
use App\Support\NotaNumber;
use App\Support\TransactionAmounts;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PenjualanController extends Controller
{
    public function index(Request $request): View
    {
        $this->validateListFilters($request);
        $selectedDate = $request->input('date', now()->toDateString());
        $query = $this->scoped(Penjualan::with('branch', 'customer', 'items.product', 'creator', 'canceller', 'cancellation'), $request)
            ->whereDate('tanggal', $selectedDate)
            ->when($request->filled('q'), function ($query) use ($request): void {
                $keyword = '%'.trim($request->input('q')).'%';
                $query->where(function ($query) use ($keyword): void {
                    $query->where('nomor_nota', 'like', $keyword)
                        ->orWhere('employee_name', 'like', $keyword)
                        ->orWhere('status', 'like', $keyword)
                        ->orWhere('receipt_snapshot->customer->nama', 'like', $keyword)
                        ->orWhere('receipt_snapshot->customer->telepon', 'like', $keyword)
                        ->orWhereHas('items', fn ($item) => $item->where('receipt_snapshot->product->nama_barang', 'like', $keyword)->orWhere('receipt_snapshot->product->kode_barang', 'like', $keyword));
                });
            })
            ->when($request->filled('branch_id') && $request->user()->isAdmin(), fn ($query) => $query->where('branch_id', $request->branch_id));

        return view('penjualan.index', [
            'sales' => $query->latest()->paginate($this->perPage($request))->withQueryString(),
            'total' => (clone $query)->where('status', 'valid')->sum('total_harga'),
            'totalWeight' => (clone $query)->where('status', 'valid')->sum('total_berat'),
            'totalCount' => (clone $query)->where('status', 'valid')->count(),
            'branches' => Cabang::orderBy('nama_cabang')->get(),
            'selectedDate' => $selectedDate,
        ]);
    }

    public function create(Request $request): View
    {
        return view('penjualan.form', [
            'branches' => Cabang::all(),
            'selectedCustomer' => Pelanggan::find(old('customer_id')),
            'products' => $this->scoped(Barang::with('branch', 'kategoriBarang')->where('status', 'tersedia'), $request)->whereKey(old('product_id'))->get(),
        ]);
    }

    public function store(Request $request, AuditLogService $auditLogService): RedirectResponse
    {
        $employee = $this->noteEmployee($request);
        $data = $request->validate([
            'branch_id' => [$request->user()->isAdmin() ? 'required' : 'nullable', 'exists:cabang,id'],
            'customer_id' => ['required', 'exists:pelanggan,id'],
            'product_id' => ['required', 'exists:barang,id'],
            'barcode' => ['nullable', 'string', 'max:255', 'regex:/^[\x20-\x7E]+$/'],
        ]);
        $data['branch_id'] = $this->selectedBranchId($request);
        $data = array_merge($data, $employee);

        $sale = DB::transaction(function () use ($data, $request, $auditLogService): Penjualan {
            $product = Barang::lockForUpdate()->findOrFail($data['product_id']);
            $this->ensureSaleProduct($product, (int) $data['branch_id']);
            if (! empty($data['barcode']) && trim($data['barcode']) !== $product->kode_barang) {
                throw ValidationException::withMessages(['barcode' => 'Barcode tidak sesuai dengan barang pada nota. Pindai ulang barcode barang.']);
            }
            abort_unless(Pelanggan::whereKey($data['customer_id'])->exists(), 422, 'Pelanggan tidak ditemukan.');

            $data['harga_per_gram'] = $product->harga_per_gram;
            $data['ongkos'] = $product->ongkos;

            $branch = Cabang::findOrFail($data['branch_id']);
            $subtotal = TransactionAmounts::weighted($product->berat, $data['harga_per_gram'])->plus(TransactionAmounts::money($data['ongkos']));
            TransactionAmounts::assertFits($subtotal, 'product_id');
            $sale = Penjualan::create([
                'nomor_nota' => NotaNumber::make('PJL', $branch, Penjualan::class),
                'branch_id' => $branch->id,
                'customer_id' => $data['customer_id'],
                'tanggal' => now()->toDateString(),
                'total_berat' => $product->berat,
                'total_ongkos' => $data['ongkos'],
                'total_harga' => (string) $subtotal,
                'status' => 'valid',
                'employee_id' => $data['employee_id'],
                'employee_name' => $data['employee_name'],
                'created_by' => $request->user()->id,
            ]);
            $sale->items()->create([
                'product_id' => $product->id,
                'berat' => $product->berat,
                'harga_per_gram' => $data['harga_per_gram'],
                'ongkos' => $data['ongkos'],
                'subtotal' => (string) $subtotal,
            ]);
            $product->update(['status' => 'terjual']);
            $auditLogService->log('create_sale', $sale, null, $sale->load('items')->toArray(), $request);

            return $sale;
        }, 3);

        return redirect()->route('sales.show', $sale)->with('status', 'Nota penjualan tersimpan dan stok berubah terjual.');
    }

    public function show(Request $request, Penjualan $sale): View
    {
        abort_if($request->user()->isBranchUser() && $sale->branch_id !== $request->user()->branch_id, 403);

        return view('penjualan.show', ['sale' => $sale->load('branch', 'customer', 'items.product.kategoriBarang', 'creator', 'canceller', 'cancellation')]);
    }

    public function print(Request $request, Penjualan $sale): View
    {
        abort_if($request->user()->isBranchUser() && $sale->branch_id !== $request->user()->branch_id, 403);

        return view('penjualan.print', ['sale' => $sale->load('branch', 'customer', 'items.product.kategoriBarang', 'creator', 'canceller', 'cancellation')]);
    }

    public function customerLookup(Request $request): JsonResponse
    {
        $request->validate([
            'q' => ['required', 'string', 'max:100'],
            'branch_id' => ['nullable', 'integer', 'exists:cabang,id'],
        ]);
        $term = trim($request->input('q'));
        $customers = Pelanggan::query()
            ->where(function ($query) use ($term) {
                $query->where('nama', 'like', '%'.$term.'%')->orWhere('telepon', 'like', '%'.$term.'%');
            })->orderBy('nama')->orderBy('id')->limit(20)->get(['id', 'nama', 'telepon', 'alamat']);

        return response()->json($customers);
    }

    public function barcodeLookup(Request $request): JsonResponse
    {
        $data = $request->validate([
            'barcode' => ['required', 'string', 'max:255', 'regex:/^[\\x20-\\x7E]+$/'],
            'branch_id' => ['nullable', 'integer', 'exists:cabang,id'],
            'selected_product_id' => ['nullable', 'integer'],
        ], ['barcode.regex' => 'Barcode tidak valid atau rusak. Pindai ulang label.', 'barcode.required' => 'Pindai atau masukkan barcode barang.', 'barcode.max' => 'Barcode tidak valid atau rusak.']);
        $product = Barang::with('branch', 'kategoriBarang')->where('kode_barang', trim($data['barcode']))->first();
        if (! $product) {
            throw ValidationException::withMessages(['barcode' => 'Barcode tidak ditemukan. Periksa label atau ketik ulang kode barcode.']);
        }
        $this->ensureSaleProduct($product, (int) $this->selectedBranchId($request));
        if ($request->integer('selected_product_id') === $product->id) {
            throw ValidationException::withMessages(['barcode' => 'Barang sudah dimasukkan ke nota yang sama.']);
        }

        return response()->json(array_merge($product->toArray(), [
            'branch_name' => $product->branch?->nama_cabang,
            'photo_url' => $product->photo_path ? route('products.photo', $product) : null,
        ]));
    }

    private function ensureSaleProduct(Barang $product, int $branchId): void
    {
        $message = match (true) {
            $product->branch_id !== $branchId => 'Barang tidak tersedia di cabang kasir. Pilih barang dari cabang yang sesuai.',
            $product->status === 'terjual' => 'Barang sudah terjual. Barcode tidak dapat digunakan kembali.',
            $product->status === 'service' => 'Barang sedang dalam proses servis dan belum dapat dijual.',
            $product->status !== 'tersedia' => 'Barang tidak tersedia untuk penjualan.',
            default => null,
        };
        if ($message) {
            throw ValidationException::withMessages(['barcode' => $message]);
        }
    }

    public function searchProduct(Request $request, string $kode): JsonResponse
    {
        $request->merge(['barcode' => $kode]);
        if ($request->user()->isAdmin() && ! $request->filled('branch_id')) {
            $request->merge(['branch_id' => Barang::where('kode_barang', $kode)->value('branch_id')]);
        }

        return $this->barcodeLookup($request);
    }
}
