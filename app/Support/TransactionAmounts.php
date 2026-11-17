<?php

namespace App\Support;

use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Illuminate\Validation\ValidationException;

class TransactionAmounts
{
    public static function money(mixed $value): BigDecimal
    {
        return BigDecimal::of((string) ($value ?? 0))->toScale(2, RoundingMode::HalfUp);
    }

    public static function weighted(mixed $weight, mixed $price): BigDecimal
    {
        return BigDecimal::of((string) $weight)->multipliedBy((string) $price)->toScale(2, RoundingMode::HalfUp);
    }

    public static function sum(array $amounts): BigDecimal
    {
        return array_reduce($amounts, fn (BigDecimal $sum, $amount) => $sum->plus(self::money($amount)), self::money(0));
    }

    public static function assertFits(BigDecimal $amount, string $field): void
    {
        if ($amount->isNegative() || $amount->isGreaterThan('9999999999999.99')) {
            throw ValidationException::withMessages([$field => 'Nilai transaksi harus berada antara 0 dan 9.999.999.999.999,99.']);
        }
    }
}
