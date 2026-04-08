<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\GuestPagesController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Shops\ShopController;
use App\Http\Controllers\Users\UserController;

Route::controller(GuestPagesController::class)
    ->middleware([])
    ->group(function () {
        Route::get('/', 'homePage')->name('home');
        Route::get('deals', 'dealsAndOffersPage')->name('deals-page');
    });

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::resource('shops', ShopController::class)->except('show');
});

Route::middleware(['auth', 'verified', 'role:super_admin'])->group(function () {
    Route::resource('users', UserController::class)->except('show');
});

require __DIR__.'/settings.php';
