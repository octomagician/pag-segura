<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TwoFactorController;
use App\Http\Middleware\EnsureTwoFactorIsConfigured;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware(['auth', 'verified', EnsureTwoFactorIsConfigured::class])->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->middleware(['auth', 'verified'])->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware('auth')->group(function () {
    Route::get('/verify-2fa-setup', [TwoFactorController::class, 'show'])->name('2fa.setup');
    Route::post('/verify-2fa-setup', [TwoFactorController::class, 'confirm'])->name('2fa.confirm');
});

require __DIR__.'/auth.php';
