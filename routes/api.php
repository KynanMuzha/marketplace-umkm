<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\TransactionController;
use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Middleware\RoleMiddleware;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\SellerAuthController;
use App\Http\Controllers\Api\SellerOrderController;

/*==========================
=        PUBLIC ROUTES      =
===========================*/
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);

// AUTH SELLER (OTP)
Route::post('/seller/register', [SellerAuthController::class, 'register']);
Route::post('/seller/verify-otp', [SellerAuthController::class, 'verifyOtp']);

// PRODUCTS (VIEW)
Route::get('products', [ProductController::class, 'index']);
Route::get('products/{product}', [ProductController::class, 'show']);

// CATEGORY (PUBLIC)
Route::get('categories', [CategoryController::class, 'index']);
Route::get('/categories/{id}', [CategoryController::class, 'show']); 
Route::get('categories/{id}/products', [CategoryController::class, 'products']);

/*===============================
=        AUTHENTICATED ROUTES   =
===============================*/
Route::middleware('auth:sanctum')->group(function () {

    // AUTH
    Route::post('/logout', [AuthController::class, 'logout']);

    // PROFILE (SATU SUMBER DATA USER)
    Route::get('/profile', [ProfileController::class, 'show']);
    Route::put('/profile', [ProfileController::class, 'update']);
    Route::put('/profile/password', [ProfileController::class, 'updatePassword']);
    Route::post('/profile/avatar', [ProfileController::class, 'uploadAvatar']);
    Route::delete('/profile/avatar', [ProfileController::class, 'deleteAvatar']);

    // CART & TRANSACTION (PEMBELI)
    Route::post('cart/add', [TransactionController::class, 'addToCart']);
    Route::patch('cart/update', [TransactionController::class, 'updateCartQuantity']);
    Route::get('cart', [TransactionController::class, 'cart']);
    Route::delete('cart/{id}', [TransactionController::class, 'removeFromCart']);
    Route::post('checkout', [TransactionController::class, 'checkout']);
    Route::get('orders/history', [TransactionController::class, 'history']);
    Route::get('/pesanan', [TransactionController::class, 'history']); // tetap ada sesuai request
    Route::post('orders/{order}/upload-proof', [TransactionController::class, 'uploadProof']); // upload bukti pembayaran

    // ADMIN/PENJUAL ROUTES (update status & verify payment)
    Route::middleware([RoleMiddleware::class . ':admin,penjual'])->group(function() {
        Route::patch('orders/{order}/status', [TransactionController::class, 'updateStatus']);
        Route::patch('orders/{order}/verify-payment', [TransactionController::class, 'verifyPayment']);
    });
});

/*==========================
=        PENJUAL ROUTES     =
===========================*/
Route::middleware(['auth:sanctum', RoleMiddleware::class . ':penjual'])->group(function () {
    // Produk
    Route::post('products', [ProductController::class, 'store']);
    Route::put('products/{product}', [ProductController::class, 'update']);
    Route::delete('products/{product}', [ProductController::class, 'destroy']);
    Route::get('seller/products', [ProductController::class, 'sellerIndex']);
    Route::patch('products/{product}/toggle-status', [ProductController::class, 'toggleStatus']);

    // Pesanan penjual
    Route::get('seller/orders', [SellerOrderController::class, 'index']);
    Route::get('seller/orders/{id}', [SellerOrderController::class, 'show']);
    Route::patch('seller/orders/{id}/status', [SellerOrderController::class, 'updateStatus']);
    Route::get('/seller/orders/{id}/shipping-label', [SellerOrderController::class, 'downloadShippingLabel']);
});

/*==========================
=        ADMIN ROUTES       =
===========================*/
Route::middleware(['auth:sanctum', RoleMiddleware::class . ':admin'])->group(function () {

    // CATEGORY
    Route::post('admin/categories', [CategoryController::class, 'store']);
    Route::put('admin/categories/{category}', [CategoryController::class, 'update']);
    Route::delete('admin/categories/{category}', [CategoryController::class, 'destroy']);
    Route::get('admin/categories', [CategoryController::class, 'index']);
    Route::get('admin/categories/{category}', [CategoryController::class, 'show']);

    // USERS
    Route::get('admin/users', [AdminController::class, 'listUsers']);
    Route::get('admin/users/{user}', [AdminController::class, 'showUser']);
    Route::patch('admin/users/{user}', [AdminController::class, 'updateUser']);
    Route::delete('admin/users/{user}', [AdminController::class, 'deleteUser']);

    // ORDERS
    Route::get('admin/orders', [AdminController::class, 'allOrders']);
    Route::get('admin/orders/{order}', [AdminController::class, 'showOrder']);
    Route::patch('admin/orders/{order}/status', [AdminController::class, 'updateOrderStatus']);

    // REPORT
    Route::get('admin/reports/sales', [AdminController::class, 'salesReport']);
});
