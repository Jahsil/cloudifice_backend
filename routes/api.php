<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;
use App\Http\Middleware\JwtMiddleware;

// Group all authentication-related routes under the 'auth' prefix
Route::prefix('auth')->group(function () {
    Route::middleware([JwtMiddleware::class])->group(function () {
        Route::get('user', [AuthController::class, 'user']);
        Route::post('logout', [AuthController::class, 'logout']);

     
        Route::post('login', [AuthController::class, 'login'])->withoutMiddleware([JwtMiddleware::class]);
        Route::post('register', [AuthController::class, 'register'])->withoutMiddleware([JwtMiddleware::class]);
    
    });
});






