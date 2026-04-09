<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\GuestPagesController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Users\UserController;
use App\Http\Controllers\Shops\ShopCategoryController;
use App\Http\Controllers\Shops\ShopController;
use App\Http\Controllers\Products\ProductCategoryController;
use App\Http\Controllers\Products\ProductController;
use App\Http\Controllers\Products\ProductImageController;

Route::controller(GuestPagesController::class)
    ->middleware([])
    ->group(function () {
        Route::get('/', 'homePage')->name('home');
        Route::get('deals', 'dealsAndOffersPage')->name('deals-page');
    });

Route::middleware(['auth', 'verified'])
    ->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::resource('shops', ShopController::class)->except('show');
    Route::resource('products', ProductController::class)->except('show');
    Route::delete('product-images/{image}', [ProductImageController::class, 'destroy'])->name('product-images.destroy');
});

// Admins
Route::middleware(['auth', 'verified', 'role:admin'])
    ->group(function () {
    Route::get('shops/all', [ShopController::class, 'getAllShops'])->name('shops.all');
    Route::resource('shop-categories', ShopCategoryController::class)->except('show');
    Route::resource('product-categories', ProductCategoryController::class)->except('show');
});

// Super Admins
Route::middleware(['auth', 'verified', 'role:super_admin'])
    ->group(function () {
    Route::resource('users', UserController::class)->except('show');
});

require __DIR__.'/settings.php';
