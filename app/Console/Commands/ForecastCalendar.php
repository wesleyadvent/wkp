<?php

namespace App\Console\Commands;

use App\Support\BranchCalendar;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ForecastCalendar extends Command
{
    protected $signature = 'forecast:calendar {--from=} {--to=} {--branch=} {--complete : Konfirmasi pencatatan lengkap untuk periode yang sudah diperiksa}';

    protected $description = 'Simpan kalender operasional; kelengkapan pencatatan hanya atas konfirmasi eksplisit';

    public function handle(): int
    {
        $data = Validator::make(['from' => $this->option('from'), 'to' => $this->option('to'), 'branch' => $this->option('branch')], ['from' => 'required|date_format:Y-m-d', 'to' => 'required|date_format:Y-m-d|after_or_equal:from', 'branch' => 'nullable|integer|exists:cabang,id'])->validate();
        if ($this->option('complete') && $data['to'] >= now('Asia/Jakarta')->toDateString()) {
            $this->error('Hari berjalan/future belum dapat dikonfirmasi lengkap.');

            return self::FAILURE;
        }
        DB::transaction(function () use ($data) {
            foreach (DB::table('cabang')->when($data['branch'], fn ($q) => $q->where('id', $data['branch']))->get() as $b) {
                for ($date = CarbonImmutable::parse($data['from'], 'Asia/Jakarta'); $date->toDateString() <= $data['to']; $date = $date->addDay()) {
                    $values = ['buka' => BranchCalendar::isOpen($b->kode_cabang, $date)];
                    if ($this->option('complete')) {
                        $values['pencatatan_lengkap'] = true;
                    }
                    DB::table('kalender_cabang')->updateOrInsert(['branch_id' => $b->id, 'tanggal' => $date->toDateString()], $values);
                }
            }
        });
        $this->info('Kalender tersimpan.');

        return self::SUCCESS;
    }
}
