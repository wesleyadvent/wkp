<?php

namespace App\Services;

use App\Models\Pembelian;
use App\Models\Penjualan;
use App\Models\NotaLayanan;
use App\Models\PembatalanTransaksi;
use App\Models\Pengguna;
use App\Models\Barang;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class TransactionCancellationService
{
    public function __construct(private readonly AuditLogService $auditLogService)
    {
    }

    public function cancel(string $type, int $id, string $adminEmail, string $adminPassword, string $reason): void
    {
        $admin = Pengguna::query()
            ->where('email', $adminEmail)
            ->where('role', 'admin')
            ->where('is_active', true)
            ->first();

        if (! $admin || ! Hash::check($adminPassword, $admin->password)) {
            throw ValidationException::withMessages(['admin_email' => 'Verifikasi admin tidak valid.']);
        }

        if (trim($reason) === '') {
            throw ValidationException::withMessages(['alasan' => 'Alasan pembatalan wajib diisi.']);
        }

        DB::transaction(function () use ($type, $id, $admin, $reason): void {
            // All writes depending on a sale acquire the sale lock first.
            if (in_array($type, ['purchase', 'service'], true)) {
                $dependent = ($type === 'purchase' ? Pembelian::class : NotaLayanan::class)::findOrFail($id);
                if ($dependent->sale_id_ref) {
                    Penjualan::whereKey($dependent->sale_id_ref)->lockForUpdate()->firstOrFail();
                }
            }
            $model = match ($type) {
                'sale' => Penjualan::with('items.product')->lockForUpdate()->findOrFail($id),
                'purchase' => Pembelian::with('items')->lockForUpdate()->findOrFail($id),
                'service' => NotaLayanan::lockForUpdate()->findOrFail($id),
                default => throw ValidationException::withMessages(['transaction_type' => 'Jenis transaksi tidak valid.']),
            };

            if (in_array($model->status ?? $model->status_service, ['batal', 'cancelled'], true)) {
                throw ValidationException::withMessages(['transaction_id' => 'Transaksi sudah dibatalkan.']);
            }

            if ($model->created_at && $model->created_at->lt(now()->subHours(24))) {
                throw ValidationException::withMessages(['transaction_id' => 'Pembatalan hanya dapat dilakukan maksimal 24 jam setelah nota dibuat.']);
            }

            $old = $model->toArray();

            if ($type === 'sale') {
                if (Pembelian::where('sale_id_ref', $model->id)->where('status', 'valid')->exists()
                    || NotaLayanan::where('sale_id_ref', $model->id)->where('status_service', '!=', 'batal')->exists()) {
                    throw ValidationException::withMessages(['transaction_id' => 'Nota penjualan masih digunakan oleh pembelian atau service. Batalkan nota terkait terlebih dahulu.']);
                }
                foreach ($model->items as $item) {
                    $product = Barang::withTrashed()->whereKey($item->product_id)->lockForUpdate()->firstOrFail();
                    if ($product->trashed() || $product->status !== 'terjual'
                        || \App\Models\ItemPenjualan::where('product_id', $product->id)->where('sale_id', '!=', $model->id)
                            ->whereIn('sale_id', Penjualan::where('status', 'valid')->select('id'))->exists()) {
                        throw ValidationException::withMessages(['transaction_id' => 'Status barang tidak sesuai dengan penjualan ini. Periksa riwayat barang sebelum membatalkan.']);
                    }
                    $product->update(['status' => 'tersedia']);
                }
                $model->update(['status' => 'cancelled', 'cancelled_by' => $admin->id, 'cancelled_at' => now()]);
            } elseif ($type === 'purchase') {
                $model->update(['status' => 'cancelled', 'cancelled_by' => $admin->id, 'cancelled_at' => now()]);
            } else {
                $model->update(['status_service' => 'batal', 'cancelled_by' => $admin->id, 'cancelled_at' => now()]);
            }

            PembatalanTransaksi::create([
                'transaction_type' => $type,
                'transaction_id' => $id,
                'alasan' => $reason,
                'verified_admin_id' => $admin->id,
                'cancelled_at' => now(),
            ]);

            $this->auditLogService->log('cancel_'.$type, $model, $old, $model->fresh()->toArray());
        }, 3);
    }
}
