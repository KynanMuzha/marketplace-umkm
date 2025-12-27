<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Middleware\RoleMiddleware;
use App\Http\Controllers\Api\TransactionController;
use App\Http\Controllers\Api\AdminController;

// Public routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Routes untuk semua user login
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/profile', [AuthController::class, 'profile']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // Produk: lihat daftar & detail (pembeli & penjual)
    Route::get('products', [ProductController::class, 'index']);
    Route::get('products/{product}', [ProductController::class, 'show']);

    // Transaction
    Route::post('cart/add', [TransactionController::class, 'addToCart']);
    Route::patch('cart/update', [TransactionController::class, 'updateCartQuantity']);
    Route::get('cart', [TransactionController::class, 'cart']);
    Route::post('checkout', [TransactionController::class, 'checkout']);
    Route::get('orders/history', [TransactionController::class, 'history']);
    Route::patch('orders/{order}/status', [TransactionController::class, 'updateStatus']); // admin/penjual
});

// Produk: CRUD hanya untuk penjual
Route::middleware(['auth:sanctum', RoleMiddleware::class . ':penjual'])->group(function () {
    Route::post('products', [ProductController::class, 'store']);
    Route::put('products/{product}', [ProductController::class, 'update']);
    Route::delete('products/{product}', [ProductController::class, 'destroy']);
});

Route::middleware(['auth:sanctum', RoleMiddleware::class . ':admin'])->group(function () {
    // Kelola user
    Route::get('admin/users', [AdminController::class, 'listUsers']);
    Route::get('admin/users/{user}', [AdminController::class, 'showUser']);
    Route::patch('admin/users/{user}', [AdminController::class, 'updateUser']);
    Route::delete('admin/users/{user}', [AdminController::class, 'deleteUser']);

    // Monitoring transaksi
    Route::get('admin/orders', [AdminController::class, 'allOrders']);
    Route::get('admin/orders/{order}', [AdminController::class, 'showOrder']);

    // Laporan penjualan
    Route::get('admin/reports/sales', [AdminController::class, 'salesReport']);
});