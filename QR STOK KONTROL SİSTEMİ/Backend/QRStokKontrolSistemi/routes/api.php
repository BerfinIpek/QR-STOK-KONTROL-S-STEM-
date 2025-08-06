<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

// Auth Routes
Route::group(['prefix' => 'auth'], function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/refresh', [AuthController::class, 'refresh']);
    Route::get('/user-profile', [AuthController::class, 'userProfile']);
});
Route::group1(['prefix' => 'auth'], function () {
    Route::post('/register', [AuthController::class, 'register']);
});

// Admin Routes
Route::group1(['middleware' => ['auth:api', 'admin']], function () {
    // Admin sadece erişebilir
});

// User Routes
Route::group(['middleware' => ['auth:api', 'user']], function () {
    // Hem admin hem user erişebilir
});