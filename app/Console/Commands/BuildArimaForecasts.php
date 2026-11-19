<?php

namespace App\Console\Commands;

use App\Services\ArimaForecastBuilder;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

class BuildArimaForecasts extends Command
{
    protected $signature = 'forecast:arima {--cutoff=} {--branch=}';

    protected $description = 'Bangun batch ARIMA berdasarkan data lengkap sampai tanggal potong';

    public function handle(): int
    {
        $args = Validator::make(['cutoff' => $this->option('cutoff') ?: now('Asia/Jakarta')->subDay()->toDateString(), 'branch' => $this->option('branch')], ['cutoff' => 'required|date_format:Y-m-d|before:today', 'branch' => 'nullable|integer|exists:cabang,id'])->validate();
        $builder = app(ArimaForecastBuilder::class);
        $groups = $builder->groups($args['branch'] ? (int) $args['branch'] : null, CarbonImmutable::parse($args['cutoff'], 'Asia/Jakarta'));
        try {
            $forecasts = $builder->build($groups);
        } catch (\Throwable $e) {
            $this->error('Batch gagal; hasil lama tidak ditimpa: '.$e->getMessage());

            return self::FAILURE;
        }
        $this->info('Batch tersimpan: '.count($groups).' kelompok, '.count($forecasts).' ramalan; berhasil '.collect($forecasts)->where('result.status', 'ok')->count().'.');

        return self::SUCCESS;
    }
}
