<?php

namespace App\Support;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use InvalidArgumentException;

class BranchCalendar
{
    // 2027 planning date: SKB published 15 September 2026, Ministry of Manpower.
    public const EID_DATES = ['2023-04-22', '2024-04-10', '2025-03-31', '2026-03-21', '2027-03-10'];

    public const EID_AVAILABLE_FROM = [2027 => '2026-09-15'];

    public static function isOpen(string $branchCode, CarbonInterface $date): bool
    {
        $day = CarbonImmutable::instance($date)->startOfDay();
        if (! in_array($branchCode, ['PWG', 'MDR', 'SPH', 'DSA'], true)) {
            throw new InvalidArgumentException('Kode cabang tidak dikenal: '.$branchCode);
        }
        foreach (self::EID_DATES as $eid) {
            $distance = (int) $day->diffInDays(CarbonImmutable::parse($eid, 'Asia/Jakarta'), false);
            if ($distance === 0) {
                return false;
            }
            if (abs($distance) <= 21) {
                return true;
            }
        }

        return match ($branchCode) {
            'PWG' => $day->dayOfWeek !== CarbonInterface::SATURDAY,
            'MDR', 'SPH' => $day->dayOfWeek !== CarbonInterface::SUNDAY,
            'DSA' => true,
        };
    }
}
