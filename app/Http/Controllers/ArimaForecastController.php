<?php

namespace App\Http\Controllers;

use App\Services\ArimaForecastBuilder;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ArimaForecastController extends Controller
{
    public function __invoke(Request $request, ArimaForecastBuilder $builder): JsonResponse
    {
        $data = $request->validate([
            'cutoff' => ['required', 'date_format:Y-m-d', 'before:today'],
            'branch_id' => ['nullable', 'integer', 'exists:cabang,id'],
        ]);
        $branch = $request->user()->isAdmin()
            ? ($request->filled('branch_id') ? (int) $data['branch_id'] : null)
            : (int) $request->user()->branch_id;
        abort_if($request->user()->isBranchUser() && ! $branch, 403);

        try {
            return response()->json($builder->prepare($branch, CarbonImmutable::parse($data['cutoff'], 'Asia/Jakarta')));
        } catch (\Throwable $error) {
            report($error);

            return response()->json(['message' => 'Perhitungan ARIMA terhenti. Muat ulang halaman untuk mencoba lagi. Jika tetap gagal, hubungi administrator.'], 503);
        }
    }
}
