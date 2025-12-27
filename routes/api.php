<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Middleware\RoleMiddleware;
use App\Http\Controllers\Api\TransactionController;

// Public routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);

// Routes untuk semua user login
Route::middleware('auth:sanctum')->group(function () {

    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/profile', [AuthController::class, 'profile']);

    // Produk: lihat daftar & detail (pembeli & penjual)
    Route::get('products', [ProductController::class, 'index']);
    Route::get('products/{product}', [ProductController::class, 'show']);

    // Transaction
    Route::post('cart/add', [TransactionController::class, 'addToCart']);
    Route::patch('cart/update', [TransactionController::class, 'updateCartQuantity']);
    Route::get('cart', [TransactionController::class, 'cart']);
    Route::post('checkout', [TransactionController::class, 'checkout']);
    Route::get('orders/history', [TransactionController::class, 'history']);
    Route::patch(
        'orders/{order}/status',
        [TransactionController::class, 'updateStatus']
    ); // admin/penjual

});

// Produk: CRUD hanya untuk penjual
Route::middleware(['auth:sanctum', RoleMiddleware::class . ':penjual'])->group(function () {
    Route::post('products', [ProductController::class, 'store']);
    Route::put('products/{product}', [ProductController::class, 'update']);
    Route::delete('products/{product}', [ProductController::class, 'destroy']);
});
