<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Middleware\RoleMiddleware;
use App\Http\Controllers\Api\TransactionController;

// Route public (tidak perlu login)
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Route untuk semua user yang login
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/profile', [AuthController::class, 'profile']);
    Route::post('/logout', [AuthController::class, 'logout']);
});

// Route products hanya untuk penjual
Route::middleware(['auth:sanctum', RoleMiddleware::class . ':penjual'])->group(function () {
    Route::apiResource('products', ProductController::class);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::post('cart/add', [TransactionController::class, 'addToCart']);
    Route::get('cart', [TransactionController::class, 'cart']);
    Route::post('checkout', [TransactionController::class, 'checkout']);
    Route::get('orders/history', [TransactionController::class, 'history']);
    Route::patch('orders/{order}/status', [TransactionController::class, 'updateStatus']); // admin
});
