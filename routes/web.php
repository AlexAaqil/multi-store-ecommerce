<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\GuestPagesController;

Route::controller(GuestPagesController::class)
    ->middleware([])
    ->group(function () {
        Route::get('/', 'homePage')->name('home');
        Route::get('deals', 'dealsAndOffersPage')->name('deals-page');
    });

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';
