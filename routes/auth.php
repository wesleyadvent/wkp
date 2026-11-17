<?php

use App\Http\Controllers\Auth\KataSandiBaruController;
use App\Http\Controllers\Auth\KataSandiController;
use App\Http\Controllers\Auth\KonfirmasiKataSandiController;
use App\Http\Controllers\Auth\NotifikasiVerifikasiEmailController;
use App\Http\Controllers\Auth\PenggunaTerdaftarController;
use App\Http\Controllers\Auth\PermintaanVerifikasiEmailController;
use App\Http\Controllers\Auth\SesiAutentikasiController;
use App\Http\Controllers\Auth\TautanAturUlangKataSandiController;
use App\Http\Controllers\Auth\VerifikasiEmailController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    // Operational accounts are created by an admin with a valid role/branch.
    Route::get('register', fn () => redirect()->route('login'))->name('register');
    Route::post('register', fn () => abort(403, 'Pendaftaran akun hanya dapat dilakukan oleh admin.'));

    Route::get('login', [SesiAutentikasiController::class, 'create'])
        ->name('login');

    Route::post('login', [SesiAutentikasiController::class, 'store']);

    Route::get('forgot-password', [TautanAturUlangKataSandiController::class, 'create'])
        ->name('password.request');

    Route::post('forgot-password', [TautanAturUlangKataSandiController::class, 'store'])
        ->name('password.email');

    Route::get('reset-password/{token}', [KataSandiBaruController::class, 'create'])
        ->name('password.reset');

    Route::post('reset-password', [KataSandiBaruController::class, 'store'])
        ->name('password.store');
});

Route::middleware('auth')->group(function () {
    Route::get('verify-email', PermintaanVerifikasiEmailController::class)
        ->name('verification.notice');

    Route::get('verify-email/{id}/{hash}', VerifikasiEmailController::class)
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');

    Route::post('email/verification-notification', [NotifikasiVerifikasiEmailController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('verification.send');

    Route::get('confirm-password', [KonfirmasiKataSandiController::class, 'show'])
        ->name('password.confirm');

    Route::post('confirm-password', [KonfirmasiKataSandiController::class, 'store']);

    Route::put('password', [KataSandiController::class, 'update'])->name('password.update');

    Route::post('logout', [SesiAutentikasiController::class, 'destroy'])
        ->name('logout');
});
