<?php

namespace App\Services;

use App\Models\JenisPotongan;
use App\Models\Karyawan;
use App\Models\HargaEmas;
use App\Models\Barang;
use App\Models\KategoriBarang;
use App\Models\Pembelian;
use App\Models\PotonganPembelian;
use App\Models\ItemPembelian;
use App\Models\Penjualan;
use App\Models\NotaLayanan;
use App\Models\ItemNotaLayanan;
use App\Models\JenisLayanan;
use App\Models\KonfigurasiPersediaan;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class MasterDataUsage
{
    public const MASTERS = [Karyawan::class, HargaEmas::class, KategoriBarang::class, JenisPotongan::class, JenisLayanan::class];

    public static function isUsed(Model $model): bool
    {
        if ($model->used_at) {
            return true;
        }

        return match ($model::class) {
            Karyawan::class => Penjualan::where('employee_id', $model->id)->exists() || Pembelian::where('employee_id', $model->id)->exists() || NotaLayanan::where('employee_id', $model->id)->exists(),
            // Legacy purchase records still store the purity/category as text.
            HargaEmas::class => Barang::withTrashed()->where('kadar', $model->kadar)->exists() || ItemPembelian::where('kadar', $model->kadar)->exists() || KonfigurasiPersediaan::where('kadar', $model->kadar)->exists(),
            KategoriBarang::class => Barang::withTrashed()->where('kategori_barang_id', $model->id)->exists() || ItemPembelian::where('kategori', $model->nama_kategori)->exists() || KonfigurasiPersediaan::where('kategori', $model->nama_kategori)->exists(),
            JenisPotongan::class => PotonganPembelian::where('deduction_type_id', $model->id)->exists(),
            JenisLayanan::class => NotaLayanan::where('jenis_service_id', $model->id)->exists() || ItemNotaLayanan::where('service_type_id', $model->id)->exists(),
            default => false,
        };
    }

    public static function assertUnused(Model $model): void
    {
        if (self::isUsed($model)) {
            throw ValidationException::withMessages(['delete' => 'Data sudah pernah digunakan dan tidak boleh dihapus, termasuk pada transaksi batal. Nonaktifkan data melalui Edit jika tersedia.']);
        }
    }

    public static function track(Model $source): void
    {
        if ($source instanceof KonfigurasiPersediaan) {
            HargaEmas::where('kadar', $source->kadar)->whereNull('used_at')->update(['used_at' => now()]);
            KategoriBarang::where('nama_kategori', $source->kategori)->whereNull('used_at')->update(['used_at' => now()]);
        }
        if ($source instanceof Barang || $source instanceof ItemPembelian) {
            if ($source->kadar) {
                HargaEmas::where('kadar', $source->kadar)->whereNull('used_at')->update(['used_at' => now()]);
            }
            if ($source instanceof Barang && $source->kategori_barang_id) {
                KategoriBarang::whereKey($source->kategori_barang_id)->whereNull('used_at')->update(['used_at' => now()]);
            } elseif ($source instanceof ItemPembelian && $source->kategori) {
                KategoriBarang::where('nama_kategori', $source->kategori)->whereNull('used_at')->update(['used_at' => now()]);
            }
        }
        if ($source instanceof Penjualan || $source instanceof Pembelian || $source instanceof NotaLayanan) {
            Karyawan::whereKey($source->employee_id)->whereNull('used_at')->update(['used_at' => now()]);
        }
        if ($source instanceof PotonganPembelian) {
            JenisPotongan::whereKey($source->deduction_type_id)->whereNull('used_at')->update(['used_at' => now()]);
        }
        if ($source instanceof NotaLayanan || $source instanceof ItemNotaLayanan) {
            JenisLayanan::whereKey($source instanceof NotaLayanan ? $source->jenis_service_id : $source->service_type_id)->whereNull('used_at')->update(['used_at' => now()]);
        }
    }
}
