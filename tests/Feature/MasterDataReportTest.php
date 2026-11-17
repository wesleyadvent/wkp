<?php

namespace Tests\Feature;

use App\Models\Barang;
use App\Models\Cabang;
use App\Models\HargaEmas;
use App\Models\JenisLayanan;
use App\Models\JenisPotongan;
use App\Models\Karyawan;
use App\Models\KategoriBarang;
use App\Models\NotaLayanan;
use App\Models\Pelanggan;
use App\Models\Pembelian;
use App\Models\Pengguna;
use App\Models\Penjualan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MasterDataReportTest extends TestCase
{
    use RefreshDatabase;

    private function masters(): array
    {
        return [
            'employees' => Karyawan::create(['nama' => 'Siti']),
            'gold-prices' => HargaEmas::create(['tanggal' => today(), 'kadar' => '24K', 'harga_per_gram' => 1000000]),
            'product-categories' => KategoriBarang::create(['nama_kategori' => 'Cincin']),
            'deduction-types' => JenisPotongan::create(['nama_potongan' => 'Rusak', 'nominal' => 1000]),
            'service-types' => JenisLayanan::create(['nama_service' => 'Cuci', 'harga_default' => 1000]),
        ];
    }

    public function test_deduction_type_no_longer_has_a_type_field(): void
    {
        $this->actingAs(Pengguna::factory()->create(['role' => 'admin']));

        $this->assertFalse(Schema::hasColumn('jenis_potongan', 'tipe'));
        $this->get(route('deduction-types.create'))->assertOk()->assertDontSee('name="tipe"', false);
        $this->post(route('deduction-types.store'), [
            'nama_potongan' => 'Potongan Uji',
            'nominal' => 5000,
            'keterangan' => 'Tanpa tipe',
            'is_active' => 1,
        ])->assertSessionHasNoErrors()->assertRedirect(route('deduction-types.index'));
        $this->assertDatabaseHas('jenis_potongan', [
            'nama_potongan' => 'Potongan Uji',
            'nominal' => 5000,
            'keterangan' => 'Tanpa tipe',
        ]);
    }

    public function test_branch_can_only_read_all_five_masters(): void
    {
        $branch = Cabang::create(['kode_cabang' => 'A', 'nama_cabang' => 'Cabang A']);
        $this->actingAs(Pengguna::factory()->create(['role' => 'toko_cabang', 'branch_id' => $branch->id]));
        foreach ($this->masters() as $route => $master) {
            $this->get(route($route.'.index'))->assertOk()->assertDontSee('>Edit</a>', false)->assertDontSee('>Hapus</button>', false)->assertDontSee('>Tambah</a>', false);
            $this->get(route($route.'.create'))->assertForbidden();
            $this->get(route($route.'.edit', $master))->assertForbidden();
            $this->post(route($route.'.store'), [])->assertForbidden();
            $this->put(route($route.'.update', $master), [])->assertForbidden();
            $this->delete(route($route.'.destroy', $master))->assertForbidden();
            $this->assertModelExists($master);
        }
    }

    public function test_admin_can_delete_unused_masters(): void
    {
        $this->actingAs(Pengguna::factory()->create(['role' => 'admin']));
        foreach ($this->masters() as $route => $master) {
            $this->delete(route($route.'.destroy', $master))->assertSessionHasNoErrors()->assertRedirect(route($route.'.index'));
            $this->assertModelMissing($master);
        }
    }

    public function test_used_masters_remain_protected_after_cancellation_rename_and_source_removal(): void
    {
        $this->actingAs(Pengguna::factory()->create(['role' => 'admin']));
        $masters = $this->masters();
        $branch = Cabang::create(['kode_cabang' => 'A', 'nama_cabang' => 'Cabang A']);
        $customer = Pelanggan::create(['nama' => 'A', 'branch_id' => $branch->id]);
        $base = ['branch_id' => $branch->id, 'customer_id' => $customer->id, 'employee_id' => $masters['employees']->id, 'tanggal' => today(), 'status' => 'batal'];
        $product = Barang::create(['kode_barang' => 'X', 'branch_id' => $branch->id, 'nama_barang' => 'Cincin', 'kategori_barang_id' => $masters['product-categories']->id, 'kadar' => '24K', 'berat' => 1, 'harga_per_gram' => 1000000, 'total_harga' => 1000000]);
        $purchase = Pembelian::create($base + ['nomor_nota' => 'BUY']);
        $deduction = $purchase->deductions()->create(['deduction_type_id' => $masters['deduction-types']->id, 'nominal' => 1000]);
        $service = NotaLayanan::create(['nomor_nota' => 'SVC', 'branch_id' => $branch->id, 'customer_id' => $customer->id, 'tanggal_masuk' => today(), 'jenis_service_id' => $masters['service-types']->id]);
        HargaEmas::create(['tanggal' => today(), 'kadar' => '24K', 'harga_per_gram' => 2000000]);
        foreach ($masters as $route => $master) {
            $this->delete(route($route.'.destroy', $master))->assertSessionHasErrors('delete');
            $this->assertModelExists($master);
            $this->assertNotNull($master->fresh()->used_at);
        }
        $masters['product-categories']->update(['nama_kategori' => 'Kategori baru']);
        $masters['gold-prices']->update(['kadar' => '22K']);
        $product->forceDelete();
        $deduction->delete();
        $purchase->delete();
        $service->delete();
        foreach ($masters as $route => $master) {
            $this->delete(route($route.'.destroy', $master))->assertSessionHasErrors('delete');
        }
    }

    public function test_reports_print_all_filtered_rows_and_totals_exclude_cancelled_and_other_branches(): void
    {
        $branch = Cabang::create(['kode_cabang' => 'A', 'nama_cabang' => 'Cabang A']);
        $other = Cabang::create(['kode_cabang' => 'B', 'nama_cabang' => 'Cabang B']);
        $customer = Pelanggan::create(['nama' => 'Pelanggan Laporan', 'telepon' => '0812345', 'alamat' => 'Alamat lengkap', 'branch_id' => $branch->id]);
        $this->actingAs(Pengguna::factory()->create(['role' => 'admin']));
        foreach (['sales' => Penjualan::class, 'purchases' => Pembelian::class] as $route => $class) {
            $base = ['branch_id' => $branch->id, 'customer_id' => $customer->id, 'tanggal' => '2026-09-12', 'total_berat' => 1.125, 'total_harga' => 125000.25, 'status' => 'valid', 'employee_name' => 'Siti'];
            for ($i = 1; $i <= 21; $i++) {
                $class::create($base + ['nomor_nota' => $route.'-'.$i]);
            }
            $class::create(array_merge($base, ['nomor_nota' => 'CANCEL-'.$route, 'status' => 'batal']));
            $class::create(array_merge($base, ['nomor_nota' => 'OTHER-'.$route, 'branch_id' => $other->id]));
            $class::create(array_merge($base, ['nomor_nota' => 'OLD-'.$route, 'tanggal' => '2026-09-01']));
            $filters = ['branch_id' => $branch->id, 'start' => '2026-09-12', 'end' => '2026-09-12'];
            $this->get(route('reports.'.$route, $filters))->assertOk()->assertSee('Cetak')->assertSee('laporan')->assertViewHas('total', 2625005.25)->assertViewHas('totalWeight', 23.625)->assertViewHas('rows', fn ($rows) => $rows->count() === 20);
            $this->get(route('reports.'.$route, $filters + ['print' => 1, 'page' => 2]))->assertOk()->assertViewHas('rows', fn ($rows) => $rows->count() === 22)->assertSee('window.print()', false)->assertSee('Pelanggan Laporan')->assertSee('Alamat lengkap')->assertSee('Siti')->assertSee('2.625.005,25')->assertSee('23,625')->assertDontSee('OTHER-'.$route)->assertDontSee('OLD-'.$route);
            $this->get(route('reports.'.$route, $filters + ['status' => 'batal']))->assertOk()->assertViewHas('total', 0)->assertViewHas('totalWeight', 0);
            $this->actingAs(Pengguna::factory()->create(['role' => 'toko_cabang', 'branch_id' => $branch->id]));
            $this->get(route('reports.'.$route, ['branch_id' => $other->id, 'print' => 1]))->assertOk()->assertDontSee('OTHER-'.$route)->assertSee('Cabang A');
            $this->actingAs(Pengguna::factory()->create(['role' => 'admin']));
        }
    }
}
