<?php

namespace App\Http\Controllers;

use App\Models\HargaEmas;
use App\Models\Barang;
use App\Services\AuditLogService;
use App\Services\MasterDataUsage;
use App\Support\TransactionAmounts;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;

class HargaEmasController extends Controller
{
    public function index(Request $request): View
    {
        $this->validateListFilters($request);
        $ids = HargaEmas::query()->select('kadar')->distinct()->pluck('kadar')->map(
            fn ($kadar) => HargaEmas::where('kadar', $kadar)->latest('tanggal')->latest('id')->value('id')
        );
        return view('harga-emas.index', [
            'items' => HargaEmas::whereIn('id', $ids)->orderBy('kadar')->paginate($this->perPage($request))->withQueryString(),
        ]);
    }

    public function show(Request $request, HargaEmas $goldPrice): View
    {
        $this->validateListFilters($request);
        $current = HargaEmas::where('kadar', $goldPrice->kadar)->latest('tanggal')->latest('id')->firstOrFail();
        return view('harga-emas.show', [
            'goldPrice' => $current,
            'history' => HargaEmas::where('kadar', $current->kadar)->latest('tanggal')->latest('id')->paginate($this->perPage($request))->withQueryString(),
        ]);
    }

    public function create(): View
    {
        return view('sederhana.gold-price-form', ['goldPrice' => new HargaEmas]);
    }

    public function store(Request $request, AuditLogService $auditLogService): RedirectResponse
    {
        $data = $this->validateData($request);
        $request->validate(['kadar' => [Rule::unique('harga_emas', 'kadar')]]);
        $data['created_by'] = $request->user()->id;
        $updatedProducts = 0;
        $item = DB::transaction(function () use ($data, $request, $auditLogService, &$updatedProducts): HargaEmas {
            $item = HargaEmas::create($data);
            $updatedProducts = $this->syncProductPrices($data['kadar']);
            $auditLogService->log('create_gold_price', $item, null, $item->toArray() + ['synced_products' => $updatedProducts], $request);

            return $item;
        });

        return redirect()->route('gold-prices.index')->with('status', "Harga emas tersimpan. {$updatedProducts} barang tersedia ikut diperbarui.");
    }

    public function edit(HargaEmas $goldPrice): View
    {
        $goldPrice = HargaEmas::where('kadar', $goldPrice->kadar)->latest('tanggal')->latest('id')->firstOrFail();
        return view('sederhana.gold-price-form', compact('goldPrice'));
    }

    public function update(Request $request, HargaEmas $goldPrice, AuditLogService $auditLogService): RedirectResponse
    {
        $data = $this->validateData($request);
        if ($goldPrice->kadar !== $data['kadar']) {
            throw ValidationException::withMessages(['kadar' => 'Kadar tidak dapat diubah. Tambahkan kadar baru.']);
        }
        $updatedProducts = 0;
        DB::transaction(function () use ($goldPrice, $data, $request, $auditLogService, &$updatedProducts): void {
            $locked = HargaEmas::whereKey($goldPrice->id)->lockForUpdate()->firstOrFail();
            $current = HargaEmas::where('kadar', $locked->kadar)->latest('tanggal')->latest('id')->firstOrFail();
            if ($current->id !== $locked->id) {
                throw ValidationException::withMessages(['tanggal' => 'Harga sudah berubah. Muat ulang halaman sebelum menyimpan.']);
            }
            if ($data['tanggal'] < $current->tanggal->toDateString()) {
                throw ValidationException::withMessages(['tanggal' => 'Tanggal harga baru tidak boleh lebih awal daripada harga terkini.']);
            }
            $old = $current->toArray();
            $data['created_by'] = $request->user()->id;
            $new = HargaEmas::create($data);
            $updatedProducts = $this->syncProductPrices($data['kadar']);
            $auditLogService->log('update_gold_price', $new, $old, $new->toArray() + ['synced_products' => $updatedProducts], $request);
        });

        return redirect()->route('gold-prices.index')->with('status', "Harga emas diperbarui. {$updatedProducts} barang tersedia ikut diperbarui.");
    }

    public function destroy(Request $request, HargaEmas $goldPrice, AuditLogService $auditLogService): RedirectResponse
    {
        DB::transaction(function () use ($request, $goldPrice, $auditLogService): void {
            $goldPrice = HargaEmas::whereKey($goldPrice->id)->lockForUpdate()->firstOrFail();
            MasterDataUsage::assertUnused($goldPrice);
            if (HargaEmas::where('kadar', $goldPrice->kadar)->whereKeyNot($goldPrice->id)->exists()) {
                throw ValidationException::withMessages(['delete' => 'Kadar memiliki riwayat harga dan tidak dapat dihapus.']);
            }
            $old = $goldPrice->toArray();
            $goldPrice->delete();
            $auditLogService->log('delete_gold_price', $goldPrice, $old, null, $request);
        });

        return redirect()->route('gold-prices.index')->with('status', 'Harga emas berhasil dihapus.');
    }

    private function validateData(Request $request): array
    {
        if (is_string($request->input('kadar'))) {
            $request->merge(['kadar' => mb_strtoupper(trim($request->input('kadar')))]);
        }
        return $request->validate(['tanggal' => ['required', 'date', 'date_format:Y-m-d', 'date_equals:today'], 'kadar' => ['required', 'string', 'max:50'], 'harga_per_gram' => ['required', 'numeric', 'decimal:0,2', 'gte:0', 'max:9999999999999.99'], 'keterangan' => ['nullable', 'string']]);
    }

    private function syncProductPrices(string $kadar): int
    {
        $latest = HargaEmas::where('kadar', $kadar)->latest('tanggal')->latest('id')->first();
        if (! $latest) {
            return 0;
        }
        $hargaPerGram = $latest->harga_per_gram;
        $products = Barang::query()
            ->where('kadar', $kadar)
            ->where('status', 'tersedia')
            ->orderBy('id')->lockForUpdate()->get();

        foreach ($products as $product) {
            $total = TransactionAmounts::weighted($product->berat, $hargaPerGram)->plus(TransactionAmounts::money($product->ongkos));
            TransactionAmounts::assertFits($total, 'harga_per_gram');
            $product->forceFill([
                'harga_per_gram' => $hargaPerGram,
                'total_harga' => (string) $total,
            ])->save();
        }

        return $products->count();
    }
}
