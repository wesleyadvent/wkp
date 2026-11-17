<?php

namespace App\Support;

use App\Models\Barang;
use App\Models\Cabang;
use App\Models\KategoriBarang;
use Illuminate\Support\Facades\DB;
use Picqer\Barcode\Renderers\SvgRenderer;
use Picqer\Barcode\Types\TypeCode128;

class ProductCode
{
    public static function make(int $branchId, int $categoryId): string
    {
        return DB::transaction(function () use ($branchId, $categoryId): string {
            DB::table('urutan_kode_barang')->insertOrIgnore([
                'branch_id' => $branchId,
                'kategori_barang_id' => $categoryId,
                'last_number' => 0,
            ]);
            $sequence = DB::table('urutan_kode_barang')
                ->where('branch_id', $branchId)
                ->where('kategori_barang_id', $categoryId)
                ->lockForUpdate()
                ->first();
            $branchCode = self::abbreviation(Cabang::query()->findOrFail($branchId)->kode_cabang);
            $categoryCode = self::abbreviation(KategoriBarang::query()->findOrFail($categoryId)->nama_kategori);
            $number = (int) $sequence->last_number;
            do {
                $number++;
                $code = sprintf('%s-%s-%03d', $branchCode, $categoryCode, $number);
            } while (Barang::withTrashed()->where('kode_barang', $code)->exists());
            DB::table('urutan_kode_barang')->where('id', $sequence->id)->update(['last_number' => $number]);

            return $code;
        });
    }

    public static function abbreviation(string $value): string
    {
        $parts = preg_split('/[^A-Za-z0-9]+/', strtoupper(trim($value)), -1, PREG_SPLIT_NO_EMPTY);
        $source = $parts ? end($parts) : 'BRG';

        return substr($source, 0, 3);
    }

    public static function svg(string $code): string
    {
        $barcode = (new TypeCode128)->getBarcode($code);
        $bars = (new SvgRenderer)->setSvgType(SvgRenderer::TYPE_SVG_INLINE)->render($barcode, $barcode->getWidth() * 2, 70);
        // Add quiet zones to both sides, also in downloaded SVG files.
        $width = $barcode->getWidth() * 2 + 40;
        $text = htmlspecialchars($code, ENT_QUOTES | ENT_XML1, 'UTF-8');

        return '<svg xmlns="http://www.w3.org/2000/svg" width="'.$width.'" height="100" viewBox="0 0 '.$width.' 100"><rect width="100%" height="100%" fill="white"/><g transform="translate(20,5)">'.$bars.'</g><text x="'.($width / 2).'" y="94" text-anchor="middle" font-family="monospace" font-size="14" fill="black">'.$text.'</text></svg>';
    }
}
