<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\GuestPagesController;

Route::controller(GuestPagesController::class)
    ->middleware([])
    ->group(function () {
        Route::get('/', 'home')->name('home');
        Route::get('about', 'about')->name('about-page');
    });

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';
