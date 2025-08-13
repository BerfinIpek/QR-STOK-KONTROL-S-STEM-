<?php

use App\Models\User;
use App\Models\Cihaz;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CihazController;

// Auth Routes
Route::group(['prefix' => 'auth'], function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/refresh', [AuthController::class, 'refresh']);
    Route::get('/user-profile', [AuthController::class, 'userProfile']);
    Route::post('/login-debug', [AuthController::class, 'loginDebug']);
});

// Cihaz Routes (Auth gerekli) /* */
Route::middleware(['auth:api'])->group(function () {
    Route::get('/cihazlar', [CihazController::class, 'index']);
    Route::get('/cihazlar/{id}', [CihazController::class, 'show']);
    Route::post('/cihazlar', [CihazController::class, 'store']);
    Route::put('/cihazlar/{id}', [CihazController::class, 'update']);
    Route::delete('/cihazlar/{id}', [CihazController::class, 'destroy']);
    Route::get('/cihazlar/{id}/qr-download', [CihazController::class, 'downloadQr']);
    Route::post('/qr-scan', [CihazController::class, 'scanQr']);
});



// Admin Routes                         
Route::group(['middleware' => ['auth:api', 'admin']], function () {
    // Admin sadece erişebilir
});

// User Routes
Route::group(['middleware' => ['auth:api', 'user']], function () {
    // Hem admin hem user erişebilir
});