<?php

namespace App\Http\Controllers;

use App\Models\Cabang;
use App\Models\Pelanggan;
use App\Models\Barang;
use App\Models\Pembelian;
use App\Models\Penjualan;
use App\Models\NotaLayanan;
use App\Models\JenisLayanan;
use App\Services\AuditLogService;
use App\Services\TransactionReference;
use App\Support\TransactionAmounts;
use App\Support\NotaNumber;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class NotaLayananController extends Controller
{
    public function index(Request $request): View
    {
        $this->validateListFilters($request);
        $selectedDate = $request->input('date', now()->toDateString());
        $query = $this->scoped(NotaLayanan::with('branch', 'customer', 'serviceType', 'items.serviceType'), $request)
            ->whereDate('tanggal_masuk', $selectedDate)
            ->when($request->filled('q'), function ($query) use ($request): void {
                $keyword = '%'.trim($request->input('q')).'%';
                $query->where(function ($query) use ($keyword): void {
                    $query->where('nomor_nota', 'like', $keyword)
                        ->orWhere('employee_name', 'like', $keyword)
                        ->orWhere('status_service', 'like', $keyword)
                        ->orWhere('nama_barang_manual', 'like', $keyword)
                        ->orWhere('receipt_snapshot->product->nama_barang', 'like', $keyword)
                        ->orWhere('receipt_snapshot->product->kode_barang', 'like', $keyword)
                        ->orWhere('receipt_snapshot->customer->nama', 'like', $keyword)
                        ->orWhere('receipt_snapshot->customer->telepon', 'like', $keyword);
                });
            })
            ->when($request->filled('branch_id') && $request->user()->isAdmin(), fn ($query) => $query->where('branch_id', $request->branch_id));

        return view('nota-layanan.index', [
            'notes' => $query->latest()->paginate($this->perPage($request))->withQueryString(),
            'branches' => Cabang::orderBy('nama_cabang')->get(),
            'selectedDate' => $selectedDate,
        ]);
    }

    public function create(Request $request): View
    {
        return view('nota-layanan.form', [
            'branches' => Cabang::all(),
            'customers' => collect(),
            'selectedCustomer' => Pelanggan::find(old('customer_id')),
            'serviceTypes' => JenisLayanan::where('is_active', true)->get(),
        ]);
    }

    public function store(Request $request, AuditLogService $auditLogService): RedirectResponse
    {
        $employee = $this->noteEmployee($request);
        $this->fillReferenceItem($request, 'asal_barang', true);
        $data = $request->validate([
            'branch_id' => ['nullable', 'exists:cabang,id'],
            'customer_id' => ['nullable', 'required_if:asal_barang,barang_luar', 'exists:pelanggan,id'],
            'product_id_ref' => ['nullable', 'exists:barang,id'],
            'sale_id_ref' => ['nullable', 'exists:penjualan,id'],
            'asal_barang' => ['required', 'in:toko_sendiri,barang_luar'],
            'nama_barang_manual' => ['required', 'string', 'max:255'],
            'berat' => ['nullable', 'numeric', 'decimal:0,3', 'gt:0', 'max:9999999.999'],
            'service_type_ids' => ['required', 'array', 'list', 'min:1', 'max:100'],
            'service_type_ids.*' => ['required', Rule::exists('jenis_layanan', 'id')->where('is_active', true)],
            'service_prices' => ['required', 'array', 'list', 'min:1', 'max:100'],
            'service_prices.*' => ['required', 'numeric', 'decimal:0,2', 'gte:0', 'max:9999999999999.99'],
            'service_notes' => ['nullable', 'array', 'list', 'max:100'],
            'service_notes.*' => ['nullable', 'string', 'max:255'],
            'estimasi_selesai' => ['nullable', 'date'],
        ]);
        $data['branch_id'] = $request->user()->branch_id
            ?: ($data['sale_id_ref'] ? Penjualan::whereKey($data['sale_id_ref'])->value('branch_id') : null)
            ?: ($data['product_id_ref'] ? Barang::whereKey($data['product_id_ref'])->value('branch_id') : null)
            ?: $request->input('branch_id');
        if (! $data['branch_id']) {
            throw ValidationException::withMessages(['asal_barang' => 'Cabang nota otomatis mengikuti cabang sumber. Gunakan nota penjualan sebagai sumber barang service.']);
        }
        $data['status_service'] = 'diproses';
        $data = array_merge($data, $employee);

        if (! empty($data['sale_id_ref']) && $this->saleHasBeenBoughtBack((int) $data['sale_id_ref'])) {
            throw ValidationException::withMessages([
                'sale_id_ref' => 'Nota penjualan ini sudah masuk ke nota pembelian kembali, sehingga tidak bisa dipakai untuk nota service.',
            ]);
        }

        abort_unless(Pelanggan::whereKey($data['customer_id'])->exists(), 422, 'Pelanggan tidak ditemukan.');

        $serviceTypeIds = $data['service_type_ids'];
        $servicePrices = $data['service_prices'];
        $serviceNotes = $data['service_notes'] ?? [];
        if (count($serviceTypeIds) !== count($servicePrices)) {
            throw ValidationException::withMessages(['service_prices' => 'Setiap jenis service harus memiliki satu biaya yang sesuai.']);
        }
        $totalService = TransactionAmounts::sum($servicePrices);
        TransactionAmounts::assertFits($totalService, 'service_prices');

        $note = DB::transaction(function () use ($data, $request, $auditLogService, $serviceTypeIds, $servicePrices, $serviceNotes, $totalService): NotaLayanan {
            if ($data['asal_barang'] === 'toko_sendiri') {
                TransactionReference::lockSaleItem((int) $data['sale_id_ref'], (int) $data['product_id_ref']);
                TransactionReference::assertNoOpenService((int) $data['sale_id_ref'], (int) $data['product_id_ref']);
            }
            $types = JenisLayanan::whereIn('id', $serviceTypeIds)->sharedLock()->get();
            if ($types->where('is_active', true)->count() !== count(array_unique($serviceTypeIds))) {
                throw ValidationException::withMessages(['service_type_ids' => 'Jenis service sudah berubah atau dinonaktifkan. Muat ulang form.']);
            }
            $branch = Cabang::findOrFail($data['branch_id']);
            $data['tanggal_masuk'] = now()->toDateString();
            $data['tanggal_ambil'] = null;
            $note = NotaLayanan::create($data + [
                'nomor_nota' => NotaNumber::make('SRV', $branch, NotaLayanan::class),
                'created_by' => $request->user()->id,
                'jenis_service_id' => $serviceTypeIds[0],
                'harga_service' => (string) $totalService,
            ]);
            foreach ($serviceTypeIds as $index => $serviceTypeId) {
                $note->items()->create([
                    'service_type_id' => $serviceTypeId,
                    'harga_service' => $servicePrices[$index] ?? 0,
                    'keterangan' => $serviceNotes[$index] ?? null,
                ]);
            }
            $auditLogService->log('create_service_note', $note, null, $note->load('items')->toArray(), $request);

            return $note;
        }, 3);

        return redirect()->route('service-notes.show', $note)->with('status', 'Nota service tersimpan.');
    }

    public function show(Request $request, NotaLayanan $serviceNote): View
    {
        abort_if($request->user()->isBranchUser() && $serviceNote->branch_id !== $request->user()->branch_id, 403);

        return view('nota-layanan.show', ['note' => $serviceNote->load('branch', 'customer', 'serviceType', 'product', 'items.serviceType')]);
    }

    public function print(Request $request, NotaLayanan $serviceNote): View
    {
        abort_if($request->user()->isBranchUser() && $serviceNote->branch_id !== $request->user()->branch_id, 403);

        return view('nota-layanan.print', ['note' => $serviceNote->load('branch', 'customer', 'serviceType', 'product', 'items.serviceType')]);
    }

    public function markPickedUp(Request $request, NotaLayanan $serviceNote, AuditLogService $auditLogService): RedirectResponse
    {
        $this->transitionStatus($request, $serviceNote, 'sudah_diambil', $auditLogService);

        return back()->with('status', 'Barang service berhasil ditandai sudah diambil customer.');
    }

    public function markCompleted(Request $request, NotaLayanan $serviceNote, AuditLogService $auditLogService): RedirectResponse
    {
        $this->transitionStatus($request, $serviceNote, 'selesai', $auditLogService);

        return back()->with('status', 'Nota service berhasil ditandai selesai.');
    }

    private function transitionStatus(Request $request, NotaLayanan $note, string $target, AuditLogService $audit): void
    {
        DB::transaction(function () use ($request, $note, $target, $audit): void {
            $note = NotaLayanan::whereKey($note->id)->lockForUpdate()->firstOrFail();
            abort_if($request->user()->isBranchUser() && $note->branch_id !== $request->user()->branch_id, 403);
            if ($note->status_service === $target) {
                return;
            }
            $allowed = $target === 'selesai' ? ['diterima', 'diproses'] : ['selesai'];
            if (! in_array($note->status_service, $allowed, true)) {
                throw ValidationException::withMessages(['status_service' => 'Status service sudah berubah atau urutan proses tidak sesuai. Muat ulang nota.']);
            }
            $old = $note->only(['status_service', 'tanggal_ambil']);
            $note->update(['status_service' => $target, 'tanggal_ambil' => $target === 'sudah_diambil' ? today()->toDateString() : null]);
            $audit->log($target === 'selesai' ? 'mark_service_completed' : 'mark_service_picked_up', $note, $old, $note->toArray(), $request);
        }, 3);
    }

    public function findSaleQuery(Request $request): JsonResponse
    {
        $data = $request->validate(['nomor_nota' => ['required', 'string']]);

        $sale = $this->referenceSale($request, $data['nomor_nota'], true);

        if ($this->saleHasBeenBoughtBack($sale->id)) {
            abort(422, 'Nota penjualan ini sudah masuk ke nota pembelian kembali, sehingga tidak bisa dipakai untuk nota service.');
        }

        return response()->json($this->referenceSaleData($sale));
    }

    private function saleHasBeenBoughtBack(int $saleId): bool
    {
        return Pembelian::query()
            ->where('sale_id_ref', $saleId)
            ->where('status', 'valid')
            ->exists();
    }
}
