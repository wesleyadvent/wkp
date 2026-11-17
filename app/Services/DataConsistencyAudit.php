<?php

namespace App\Services;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/** Read-only diagnostics. Never guesses or rewrites historical financial records. */
class DataConsistencyAudit
{
    public function run(): array
    {
        $checks = [];
        $saleItems = DB::table('item_penjualan')->selectRaw('sale_id, COUNT(*) AS n, SUM(subtotal) AS amount, SUM(berat) AS weight, SUM(ongkos) AS fee')->groupBy('sale_id');
        $this->add($checks, 'sale_header_totals', DB::table('penjualan as n')->leftJoinSub($saleItems, 'i', 'i.sale_id', '=', 'n.id')
            ->whereRaw('COALESCE(i.n, 0) = 0 OR ABS(n.total_harga - COALESCE(i.amount, 0)) > 0.01 OR ABS(n.total_berat - COALESCE(i.weight, 0)) > 0.0001 OR ABS(n.total_ongkos - COALESCE(i.fee, 0)) > 0.01')->selectRaw('n.id AS record_id'));
        $this->add($checks, 'sale_item_calculations', DB::table('item_penjualan')->whereRaw('ABS(subtotal - (ROUND(berat * harga_per_gram, 2) + ongkos)) > 0.01')->selectRaw('id AS record_id'));

        $purchaseItems = DB::table('item_pembelian')->selectRaw('purchase_id, COUNT(*) AS n, SUM(subtotal) AS amount, SUM(berat) AS weight, SUM(ROUND(berat * potongan_per_gram, 2)) AS deduction')->groupBy('purchase_id');
        $deductions = DB::table('potongan_pembelian')->selectRaw('purchase_id, SUM(nominal) AS amount')->groupBy('purchase_id');
        $this->add($checks, 'purchase_header_totals', DB::table('pembelian as n')->leftJoinSub($purchaseItems, 'i', 'i.purchase_id', '=', 'n.id')->leftJoinSub($deductions, 'd', 'd.purchase_id', '=', 'n.id')
            ->whereRaw('COALESCE(i.n, 0) = 0 OR ABS(n.total_harga - (COALESCE(i.amount, 0) - COALESCE(d.amount, 0))) > 0.01 OR ABS(n.total_berat - COALESCE(i.weight, 0)) > 0.0001 OR ABS(n.total_potongan - (COALESCE(i.deduction, 0) + COALESCE(d.amount, 0))) > 0.01')->selectRaw('n.id AS record_id'));
        $this->add($checks, 'purchase_item_calculations', DB::table('item_pembelian')->whereRaw('potongan_per_gram > harga_per_gram_beli OR subtotal < 0 OR ABS(subtotal - (ROUND(berat * harga_per_gram_beli, 2) - ROUND(berat * potongan_per_gram, 2))) > 0.01')->selectRaw('id AS record_id'));

        $serviceItems = DB::table('item_nota_layanan')->selectRaw('service_note_id, COUNT(*) AS n, SUM(harga_service) AS amount')->groupBy('service_note_id');
        $this->add($checks, 'service_header_totals', DB::table('nota_layanan as n')->leftJoinSub($serviceItems, 'i', 'i.service_note_id', '=', 'n.id')
            ->whereRaw('COALESCE(i.n, 0) = 0 OR ABS(n.harga_service - COALESCE(i.amount, 0)) > 0.01')->selectRaw('n.id AS record_id'));
        $this->add($checks, 'service_status_dates', DB::table('nota_layanan')->whereRaw("(status_service = 'sudah_diambil' AND tanggal_ambil IS NULL) OR (status_service NOT IN ('sudah_diambil', 'batal') AND tanggal_ambil IS NOT NULL)")->selectRaw('id AS record_id'));

        $validSales = DB::table('item_penjualan as i')->join('penjualan as s', 's.id', '=', 'i.sale_id')->where('s.status', 'valid')->selectRaw('i.product_id AS record_id')->groupBy('i.product_id')->havingRaw('COUNT(*) > 1');
        $this->add($checks, 'multiple_valid_sales_for_product', $validSales);
        $this->add($checks, 'sold_stock_status', DB::table('item_penjualan as i')->join('penjualan as s', 's.id', '=', 'i.sale_id')->join('barang as p', 'p.id', '=', 'i.product_id')
            ->where('s.status', 'valid')->where(fn ($q) => $q->where('p.status', '!=', 'terjual')->orWhereNotNull('p.deleted_at'))->selectRaw('p.id AS record_id')->distinct());
        $this->add($checks, 'product_totals', DB::table('barang')->whereRaw('ABS(total_harga - (ROUND(berat * harga_per_gram, 2) + ongkos)) > 0.01')->selectRaw('id AS record_id'));

        $this->add($checks, 'duplicate_valid_buybacks', DB::table('pembelian as n')->join('item_pembelian as i', 'i.purchase_id', '=', 'n.id')->where('n.status', 'valid')->whereNotNull('n.sale_id_ref')
            ->selectRaw('MIN(n.id) AS record_id')->groupBy('n.sale_id_ref', 'i.product_id_ref')->havingRaw('COUNT(*) > 1'));
        foreach (['pembelian' => 'purchase', 'nota_layanan' => 'service'] as $table => $key) {
            $query = DB::table($table.' as n')->leftJoin('penjualan as s', 's.id', '=', 'n.sale_id_ref')->whereNotNull('n.sale_id_ref');
            $query->where('n.'.($key === 'purchase' ? 'status' : 'status_service'), $key === 'purchase' ? '=' : '!=', $key === 'purchase' ? 'valid' : 'batal');
            $this->add($checks, $key.'_invalid_sale_reference', $query->where(fn ($q) => $q->whereNull('s.id')->orWhere('s.status', '!=', 'valid')->orWhereColumn('s.customer_id', '!=', 'n.customer_id'))->selectRaw('n.id AS record_id'));
        }
        $this->add($checks, 'invalid_user_assignment', DB::table('pengguna')->where('is_active', true)->where(fn ($q) => $q->whereNotIn('role', ['admin', 'toko_cabang'])->orWhere(fn ($q) => $q->where('role', 'toko_cabang')->whereNull('branch_id')))->selectRaw('id AS record_id'));
        foreach (ReceiptSnapshot::MODELS as $class) {
            $table = (new $class)->getTable();
            $this->add($checks, 'missing_snapshot_'.$table, DB::table($table)->whereNull('receipt_snapshot')->selectRaw('id AS record_id'));
        }

        return $checks;
    }

    private function add(array &$checks, string $name, Builder $query): void
    {
        $checks[$name] = [
            'count' => DB::query()->fromSub(clone $query, 'issues')->count(),
            'sample_ids' => $query->limit(10)->pluck('record_id')->all(),
        ];
    }
}
