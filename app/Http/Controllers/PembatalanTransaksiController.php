<?php

namespace App\Http\Controllers;

use App\Services\TransactionCancellationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PembatalanTransaksiController extends Controller
{
    public function store(Request $request, TransactionCancellationService $service): RedirectResponse
    {
        abort_unless($request->user()->isAdmin(), 403);

        $data = $request->validate([
            'transaction_type' => ['required', 'in:sale,purchase,service'],
            'transaction_id' => ['required', 'integer'],
            'admin_email' => ['required', 'email'],
            'admin_password' => ['required', 'string'],
            'alasan' => ['required', 'string', 'max:1000'],
        ]);

        $service->cancel($data['transaction_type'], (int) $data['transaction_id'], $data['admin_email'], $data['admin_password'], $data['alasan']);

        return back()->with('status', 'Transaksi berhasil dibatalkan dan audit log tercatat.');
    }
}
