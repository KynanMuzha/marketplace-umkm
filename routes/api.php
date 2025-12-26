<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Middleware\RoleMiddleware;

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
