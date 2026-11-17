<?php

namespace App\Models\Concerns;

use App\Services\ReceiptSnapshot;

trait HasReceiptSnapshot
{
    public function initializeHasReceiptSnapshot(): void
    {
        $this->mergeCasts(['receipt_snapshot' => 'array']);
    }

    public static function bootHasReceiptSnapshot(): void
    {
        static::creating(function ($model): void {
            $model->receipt_snapshot = ReceiptSnapshot::capture($model);
        });
        static::updating(function ($model): void {
            // A status transition must never recapture today's master data.
            if ($model->isDirty('receipt_snapshot')) {
                $model->receipt_snapshot = $model->getOriginal('receipt_snapshot');
            }
            $mutable = ['status', 'status_service', 'tanggal_ambil', 'cancelled_by', 'cancelled_at', 'updated_at'];
            if (array_diff(array_keys($model->getDirty()), $mutable)) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'transaction' => 'Isi nota yang sudah diterbitkan tidak dapat diubah. Gunakan pembatalan dan buat nota baru.',
                ]);
            }
        });
    }

    public function receipt(string $relation): ?object
    {
        $snapshot = $this->receipt_snapshot;

        // No live fallback after capture, even if a relation was absent.
        if ($snapshot !== null) {
            return isset($snapshot[$relation]) ? (object) $snapshot[$relation] : null;
        }

        $data = ReceiptSnapshot::capture($this);

        return isset($data[$relation]) ? (object) $data[$relation] : null;
    }
}
