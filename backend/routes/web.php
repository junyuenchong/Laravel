<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ItemController;

Route::get('/', function () {
    return view('welcome');
});

// SPA auth helpers (session + CSRF) + cookie-based JWT auth.
Route::prefix('api')->group(function () {
    Route::get('/csrf-cookie', [AuthController::class, 'csrfCookie']);

    // Tight throttling for auth endpoints (basic brute-force protection)
    Route::middleware('throttle:10,1')->post('/auth/login', [AuthController::class, 'login']);
    Route::middleware('throttle:5,1')->post('/auth/register', [AuthController::class, 'register']);

    Route::middleware('jwt')->group(function () {
        Route::get('/auth/me', [AuthController::class, 'me']);
        Route::put('/auth/me', [AuthController::class, 'updateMe']);
        Route::post('/auth/logout', [AuthController::class, 'logout']);

        Route::middleware('throttle:120,1')->group(function () {
            Route::get('/items', [ItemController::class, 'index']);
            Route::post('/items', [ItemController::class, 'store']);
            Route::get('/items/{item}', [ItemController::class, 'show']);
            Route::put('/items/{item}', [ItemController::class, 'update']);
            Route::delete('/items/{item}', [ItemController::class, 'destroy']);
        });
    });
});
