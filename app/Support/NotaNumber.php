<?php

namespace App\Support;

use App\Models\Cabang;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class NotaNumber
{
    public static function make(string $prefix, Cabang $branch, string $modelClass): string
    {
        return DB::transaction(function () use ($prefix, $branch, $modelClass): string {
            /** @var class-string<Model> $modelClass */
            $year = (int) now()->format('Y');
            $base = sprintf('%s/%s/%d', $prefix, $branch->kode_cabang, $year);

            DB::table('urutan_nomor_nota')->insertOrIgnore([
                'branch_id' => $branch->id,
                'prefix' => $prefix,
                'year' => $year,
                'last_number' => 0,
            ]);

            $sequence = DB::table('urutan_nomor_nota')
                ->where('branch_id', $branch->id)
                ->where('prefix', $prefix)
                ->where('year', $year)
                ->lockForUpdate()
                ->first();

            $number = (int) $sequence->last_number;
            if ($number === 0) {
                $number = self::highestExistingNumber($modelClass, $branch->id, $base);
            }

            do {
                $number++;
                $noteNumber = sprintf('%s/%04d', $base, $number);
            } while ($modelClass::query()->where('nomor_nota', $noteNumber)->exists());

            DB::table('urutan_nomor_nota')
                ->where('id', $sequence->id)
                ->update(['last_number' => $number]);

            return $noteNumber;
        });
    }

    /** @param class-string<Model> $modelClass */
    private static function highestExistingNumber(string $modelClass, int $branchId, string $base): int
    {
        $pattern = '/^'.preg_quote($base, '/').'\/(\d+)$/';

        return $modelClass::query()
            ->where('branch_id', $branchId)
            ->where('nomor_nota', 'like', $base.'/%')
            ->pluck('nomor_nota')
            ->reduce(function (int $highest, string $noteNumber) use ($pattern): int {
                return preg_match($pattern, $noteNumber, $matches)
                    ? max($highest, (int) $matches[1])
                    : $highest;
            }, 0);
    }
}
