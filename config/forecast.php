<?php

return [
    'python' => env('FORECAST_PYTHON', base_path('.venv-forecast/Scripts/python.exe')),
    'version' => 'arima-aicc-v1',
    'history_days' => 180,
    'group_timeout' => 12,
    'batch_timeout' => 3600,
    'comparison' => [
        'python' => env('LIGHTGBM_PYTHON', base_path('.venv-lightgbm/Scripts/python.exe')),
        'file' => storage_path('app/private/forecast-comparison.csv'),
        'metadata' => storage_path('app/private/forecast-comparison.json'),
    ],
    'lightgbm' => [
        'url' => env('LIGHTGBM_URL', 'http://127.0.0.1:5001'),
        'model_dir' => env('LIGHTGBM_MODEL_DIR', base_path('services/lightgbm/models')),
        'connect_timeout' => (float) env('LIGHTGBM_CONNECT_TIMEOUT', 1),
        'timeout' => (float) env('LIGHTGBM_TIMEOUT', 5),
        'cache_seconds' => (int) env('LIGHTGBM_CACHE_SECONDS', 3600),
    ],
];
