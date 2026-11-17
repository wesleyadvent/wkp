<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

class BuildForecastComparison extends Command
{
    protected $signature = 'forecast:build-comparison {--dataset= : Panel harian CSV untuk evaluasi}';

    protected $description = 'Bangun perbandingan MAE, RMSE, MAPE seluruh data uji dan cocokkan dengan laporan Colab';

    public function handle(): int
    {
        $process = new Process([config('forecast.comparison.python'), base_path('scripts/forecast/build_comparison.py'),
            '--dataset', $this->option('dataset') ?: storage_path('app/private/dataset-forecast.csv'),
            '--model-dir', config('forecast.lightgbm.model_dir'), '--output', config('forecast.comparison.file')]);
        $process->setTimeout(1200);
        $exitCode = $process->run(fn ($type, $buffer) => $this->output->write($buffer));

        return $exitCode === 0 ? self::SUCCESS : self::FAILURE;
    }
}
