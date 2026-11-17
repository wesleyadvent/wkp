<?php

namespace Database\Seeders;

use App\Models\Cabang;
use App\Models\JenisPotongan;
use App\Models\Pengguna;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $branches = collect([
            ['kode_cabang' => 'PWG', 'slug' => 'purwonegoro', 'nama_cabang' => 'Cabang Purwonegoro', 'alamat' => 'Jl. Raya Purwonegoro No. 12, Banjarnegara', 'telepon' => '0286-591001'],
            ['kode_cabang' => 'MDR', 'slug' => 'mandiraja', 'nama_cabang' => 'Cabang Mandiraja', 'alamat' => 'Jl. Raya Mandiraja No. 8, Banjarnegara', 'telepon' => '0286-411001'],
            ['kode_cabang' => 'SPH', 'slug' => 'sumpiuh', 'nama_cabang' => 'Cabang Sumpiuh', 'alamat' => 'Jl. Nasional III No. 24, Sumpiuh, Banyumas', 'telepon' => '0282-497001'],
            ['kode_cabang' => 'DSA', 'slug' => 'desa', 'nama_cabang' => 'Cabang Desa', 'alamat' => 'Jl. Pasar Desa No. 5, Banjarnegara', 'telepon' => '0286-521001'],
        ])->mapWithKeys(function (array $branch) {
            $model = Cabang::updateOrCreate(
                ['kode_cabang' => $branch['kode_cabang']],
                collect($branch)->except('slug')->all() + [
                    'status' => 'aktif',
                    'is_login_enabled' => true,
                ]
            );

            $model->setAttribute('login_slug', $branch['slug']);

            return [$branch['kode_cabang'] => $model];
        });

        Pengguna::updateOrCreate(
            ['email' => 'admin@wijayakusuma.test'],
            [
                'name' => 'Admin Wijaya Kusuma',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'branch_id' => null,
                'is_active' => true,
            ]
        );

        foreach ($branches as $branch) {
            Pengguna::updateOrCreate(
                ['email' => $branch->getAttribute('login_slug').'@wijayakusuma.test'],
                [
                    'name' => 'Operator '.$branch->nama_cabang,
                    'password' => Hash::make('password'),
                    'role' => 'toko_cabang',
                    'branch_id' => $branch->id,
                    'is_active' => true,
                ]
            );
        }

        JenisPotongan::firstOrCreate(['nama_potongan' => 'Potongan Standar'], ['nominal' => 0, 'is_active' => true]);

        $this->call([
            DemoOperasionalSeeder::class,
        ]);
    }
}
