<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\GuestPagesController;
use App\Http\Controllers\GuestPages\GuestSalesController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Users\UserController;
use App\Http\Controllers\Shops\ShopCategoryController;
use App\Http\Controllers\Shops\ShopController;
use App\Http\Controllers\Products\ProductCategoryController;
use App\Http\Controllers\Products\ProductController;
use App\Http\Controllers\Products\ProductImageController;
use App\Http\Controllers\Products\DiscountController;
use App\Http\Controllers\Sales\CartController;

Route::middleware([])->group(function () {
    Route::get('/', [GuestPagesController::class, 'homePage'])->name('home');
    Route::get('discover-shops', [GuestPagesController::class, 'discoverShops'])->name('discover-shops');
    Route::get('shop-details/{shop:slug}', [GuestPagesController::class, 'shopDetails'])->name('shop-details-page');
    Route::get('product-details/{product:slug}', [GuestPagesController::class, 'productDetails'])->name('product-details-page');
    Route::get('deals', [GuestPagesController::class, 'dealsAndOffersPage'])->name('deals-page');

    // Cart routes
    Route::get('cart', [CartController::class, 'index'])->name('cart.index');
    Route::get('cart/summary', [CartController::class, 'summary'])->name('cart.summary');
    Route::post('cart/add', [CartController::class, 'add'])->name('cart.add');
    Route::put('cart/item/{cartItem}', [CartController::class, 'update'])->name('cart.update');
    Route::delete('cart/item/{cartItem}', [CartController::class, 'remove'])->name('cart.remove');
    Route::delete('cart/clear', [CartController::class, 'clear'])->name('cart.clear');

    Route::get('checkout', [GuestSalesController::class, 'checkoutPage'])->name('checkout.index');
    Route::post('checkout', [GuestSalesController::class, 'processCheckout'])->name('checkout.store');
});

Route::get('cart', [CartController::class, 'index'])->name('cart.index');
Route::get('cart/summary', [CartController::class, 'summary'])->name('cart.summary');
Route::post('cart/add', [CartController::class, 'add'])->name('cart.add');
Route::put('cart/item/{cartItem}', [CartController::class, 'update'])->name('cart.update');
Route::delete('cart/item/{cartItem}', [CartController::class, 'remove'])->name('cart.remove');
Route::delete('cart/clear', [CartController::class, 'clear'])->name('cart.clear');

Route::middleware(['auth', 'verified', 'role:seller'])
    ->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::resource('shops', ShopController::class);
    Route::resource('products', ProductController::class)->except('show');
    Route::delete('product-images/{image}', [ProductImageController::class, 'destroy'])->name('product-images.destroy');
    Route::resource('discounts', DiscountController::class)->except('show');
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
