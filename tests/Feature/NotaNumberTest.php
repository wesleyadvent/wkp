<?php

namespace Tests\Feature;

use App\Models\Cabang;
use App\Models\Pelanggan;
use App\Models\Penjualan;
use App\Support\NotaNumber;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class NotaNumberTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_continues_after_the_highest_existing_number_instead_of_using_the_row_count(): void
    {
        $this->travelTo(Carbon::parse('2026-10-07 20:51:31', 'Asia/Jakarta'));
        $branch = Cabang::create(['kode_cabang' => 'PWG', 'nama_cabang' => 'Purworejo']);
        $customer = Pelanggan::create(['branch_id' => $branch->id, 'nama' => 'Pelanggan Uji']);

        foreach (['PJL/PWG/2026/0001', 'PJL/PWG/2026/14353'] as $noteNumber) {
            Penjualan::create([
                'nomor_nota' => $noteNumber,
                'branch_id' => $branch->id,
                'customer_id' => $customer->id,
                'tanggal' => today(),
            ]);
        }

        $this->assertSame('PJL/PWG/2026/14354', NotaNumber::make('PJL', $branch, Penjualan::class));
        $this->assertDatabaseHas('urutan_nomor_nota', [
            'branch_id' => $branch->id,
            'prefix' => 'PJL',
            'year' => 2026,
            'last_number' => 14354,
        ]);
    }

    public function test_it_does_not_reuse_a_reserved_number_after_the_sale_is_deleted(): void
    {
        $this->travelTo(Carbon::parse('2026-10-07 20:51:31', 'Asia/Jakarta'));
        $branch = Cabang::create(['kode_cabang' => 'PWG', 'nama_cabang' => 'Purworejo']);
        $customer = Pelanggan::create(['branch_id' => $branch->id, 'nama' => 'Pelanggan Uji']);

        $firstNumber = NotaNumber::make('PJL', $branch, Penjualan::class);
        $sale = Penjualan::create([
            'nomor_nota' => $firstNumber,
            'branch_id' => $branch->id,
            'customer_id' => $customer->id,
            'tanggal' => today(),
        ]);
        $sale->delete();

        $this->assertSame('PJL/PWG/2026/0002', NotaNumber::make('PJL', $branch, Penjualan::class));
        $this->assertSame(2, (int) DB::table('urutan_nomor_nota')->value('last_number'));
    }
}
