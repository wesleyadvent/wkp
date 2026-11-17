<?php

namespace App\Http\Controllers;

use App\Models\Cabang;
use App\Models\Pelanggan;
use App\Models\JenisPotongan;
use App\Models\Pembelian;
use App\Models\Penjualan;
use App\Services\AuditLogService;
use App\Services\TransactionReference;
use App\Support\TransactionAmounts;
use App\Support\NotaNumber;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PembelianController extends Controller
{
    public function index(Request $request): View
    {
        $this->validateListFilters($request);
        $selectedDate = $request->input('date', now()->toDateString());
        $query = $this->scoped(Pembelian::with('branch', 'customer', 'items.product.kategoriBarang', 'creator', 'canceller', 'cancellation', 'deductions.deductionType', 'referenceSale'), $request)
            ->whereDate('tanggal', $selectedDate)
            ->when($request->filled('q'), function ($query) use ($request): void {
                $keyword = '%'.trim($request->input('q')).'%';
                $query->where(function ($query) use ($keyword): void {
                    $query->where('nomor_nota', 'like', $keyword)
                        ->orWhere('employee_name', 'like', $keyword)
                        ->orWhere('status', 'like', $keyword)
                        ->orWhere('sumber_barang', 'like', $keyword)
                        ->orWhere('receipt_snapshot->customer->nama', 'like', $keyword)
                        ->orWhere('receipt_snapshot->customer->telepon', 'like', $keyword)
                        ->orWhereHas('items', fn ($item) => $item->where('nama_barang', 'like', $keyword));
                });
            })
            ->when($request->filled('branch_id') && $request->user()->isAdmin(), fn ($query) => $query->where('branch_id', $request->branch_id));

        return view('pembelian.index', [
            'purchases' => $query->latest()->paginate($this->perPage($request))->withQueryString(),
            'total' => (clone $query)->where('status', 'valid')->sum('total_harga'),
            'totalWeight' => (clone $query)->where('status', 'valid')->sum('total_berat'),
            'totalCount' => (clone $query)->where('status', 'valid')->count(),
            'branches' => Cabang::orderBy('nama_cabang')->get(),
            'selectedDate' => $selectedDate,
        ]);
    }

    public function create(Request $request): View
    {
        return view('pembelian.form', [
            'branches' => Cabang::all(),
            'customers' => collect(),
            'selectedCustomer' => Pelanggan::find(old('customer_id')),
            'deductions' => JenisPotongan::where('is_active', true)->get(),
        ]);
    }

    public function store(Request $request, AuditLogService $auditLogService): RedirectResponse
    {
        $employee = $this->noteEmployee($request);
        $this->fillReferenceItem($request, 'sumber_barang', true);
        $data = $request->validate([
            'branch_id' => [$request->user()->isAdmin() ? 'required' : 'nullable', 'exists:cabang,id'],
            'customer_id' => ['nullable', 'required_if:sumber_barang,barang_luar', 'exists:pelanggan,id'],
            'sumber_barang' => ['required', 'in:toko_sendiri,barang_luar'],
            'sale_id_ref' => ['nullable', 'exists:penjualan,id'],
            'product_id_ref' => ['nullable', 'exists:barang,id'],
            'nama_barang' => ['required', 'string', 'max:255'],
            'kategori' => ['nullable', 'string', 'max:255'],
            'kadar' => ['nullable', 'string', 'max:50'],
            'berat' => ['required', 'numeric', 'decimal:0,3', 'gt:0', 'max:9999999.999'],
            'harga_per_gram_beli' => ['required', 'numeric', 'decimal:0,2', 'gte:0', 'max:9999999999999.99'],
            'potongan_per_gram' => ['nullable', 'numeric', 'decimal:0,2', 'gte:0', 'lte:harga_per_gram_beli'],
            'deduction_type_ids' => ['nullable', 'array', 'list', 'max:100'],
            'deduction_type_ids.*' => ['required', Rule::exists('jenis_potongan', 'id')->where('is_active', true)],
            'deduction_nominals' => ['nullable', 'array', 'list', 'max:100'],
            'deduction_nominals.*' => ['required', 'numeric', 'decimal:0,2', 'gte:0', 'max:9999999999999.99'],
            'deduction_notes' => ['nullable', 'array', 'list', 'max:100'],
            'deduction_notes.*' => ['nullable', 'string', 'max:255'],
        ]);
        $data['branch_id'] = $this->selectedBranchId($request);
        $data = array_merge($data, $employee);

        if (count($data['deduction_type_ids'] ?? []) !== count($data['deduction_nominals'] ?? [])) {
            throw ValidationException::withMessages(['deduction_type_ids' => 'Lengkapi jenis dan nominal setiap potongan.']);
        }
        abort_unless(Pelanggan::whereKey($data['customer_id'])->exists(), 422, 'Pelanggan tidak ditemukan.');

        $purchase = DB::transaction(function () use ($data, $request, $auditLogService): Pembelian {
            if ($data['sumber_barang'] === 'toko_sendiri') {
                TransactionReference::lockSaleItem((int) $data['sale_id_ref'], (int) $data['product_id_ref']);
                TransactionReference::assertNoOpenService((int) $data['sale_id_ref'], (int) $data['product_id_ref']);
            }
            $types = JenisPotongan::whereIn('id', $data['deduction_type_ids'] ?? [])->sharedLock()->get();
            if ($types->where('is_active', true)->count() !== count(array_unique($data['deduction_type_ids'] ?? []))) {
                throw ValidationException::withMessages(['deduction_type_ids' => 'Jenis potongan sudah berubah atau dinonaktifkan. Muat ulang form.']);
            }
            $branch = Cabang::findOrFail($data['branch_id']);
            $potonganPerGram = $data['potongan_per_gram'] ?? 0;
            $extra = TransactionAmounts::sum($data['deduction_nominals'] ?? []);
            $gross = TransactionAmounts::weighted($data['berat'], $data['harga_per_gram_beli']);
            $weightDeduction = TransactionAmounts::weighted($data['berat'], $potonganPerGram);
            $subtotal = $gross->minus($weightDeduction);
            $total = $subtotal->minus($extra);
            if ($total->isNegative()) {
                throw ValidationException::withMessages(['deduction_nominals' => 'Total potongan tidak boleh melebihi nilai barang.']);
            }
            TransactionAmounts::assertFits($gross, 'harga_per_gram_beli');
            $purchase = Pembelian::create([
                'nomor_nota' => NotaNumber::make('PBL', $branch, Pembelian::class),
                'branch_id' => $branch->id,
                'customer_id' => $data['customer_id'],
                'sumber_barang' => $data['sumber_barang'],
                'sale_id_ref' => $data['sale_id_ref'] ?? null,
                'tanggal' => now()->toDateString(),
                'total_berat' => $data['berat'],
                'total_potongan' => (string) $weightDeduction->plus($extra),
                'total_harga' => (string) $total,
                'status' => 'valid',
                'employee_id' => $data['employee_id'],
                'employee_name' => $data['employee_name'],
                'created_by' => $request->user()->id,
            ]);
            $purchase->items()->create([
                'product_id_ref' => $data['product_id_ref'] ?? null,
                'nama_barang' => $data['nama_barang'],
                'kategori' => $data['kategori'] ?? null,
                'kadar' => $data['kadar'] ?? null,
                'berat' => $data['berat'],
                'harga_per_gram_beli' => $data['harga_per_gram_beli'],
                'potongan_per_gram' => $potonganPerGram,
                'subtotal' => (string) $subtotal,
            ]);
            foreach (($data['deduction_nominals'] ?? []) as $index => $nominal) {
                if ((float) $nominal <= 0 && empty($data['deduction_type_ids'][$index])) {
                    continue;
                }
                $purchase->deductions()->create([
                    'deduction_type_id' => $data['deduction_type_ids'][$index] ?? null,
                    'nominal' => $nominal ?? 0,
                    'keterangan' => $data['deduction_notes'][$index] ?? 'Potongan tambahan pembelian',
                ]);
            }
            $auditLogService->log('create_purchase', $purchase, null, $purchase->load('items', 'deductions')->toArray(), $request);

            return $purchase;
        }, 3);

        return redirect()->route('purchases.show', $purchase)->with('status', 'Nota pembelian tersimpan.');
    }

    public function show(Request $request, Pembelian $purchase): View
    {
        abort_if($request->user()->isBranchUser() && $purchase->branch_id !== $request->user()->branch_id, 403);

        return view('pembelian.show', ['purchase' => $purchase->load('branch', 'customer', 'items.product.kategoriBarang', 'deductions.deductionType', 'referenceSale', 'creator', 'canceller', 'cancellation')]);
    }

    public function print(Request $request, Pembelian $purchase): View
    {
        abort_if($request->user()->isBranchUser() && $purchase->branch_id !== $request->user()->branch_id, 403);

        return view('pembelian.print', ['purchase' => $purchase->load('branch', 'customer', 'items.product.kategoriBarang', 'deductions.deductionType', 'referenceSale', 'creator', 'canceller', 'cancellation')]);
    }

    public function findSale(Request $request, string $nomorNota): JsonResponse
    {
        $sale = $this->findSaleRecord($request, $nomorNota);

        return response()->json($this->referenceSaleData($sale));
    }

    public function findSaleQuery(Request $request): JsonResponse
    {
        $data = $request->validate(['nomor_nota' => ['required', 'string']]);

        return response()->json($this->referenceSaleData($this->findSaleRecord($request, $data['nomor_nota'])));
    }

    private function findSaleRecord(Request $request, string $notaOrId): Penjualan
    {
        return $this->referenceSale($request, $notaOrId, true);
    }
}
