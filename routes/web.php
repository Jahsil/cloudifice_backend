<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\FileSystemController;
use App\Http\Controllers\AuthController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/view-file', [FileSystemController::class, 'viewFile']);


Route::prefix('auth')->group(function () {
    // sanctum 
    Route::post('/register', [AuthController::class, 'registerSanctum']);
    Route::post('/login', [AuthController::class, 'loginSanctum']);

        Route::get('/user', [AuthController::class, 'userSanctum']);
        Route::get('/users', [AuthController::class, 'users']);
        Route::post('/logout', [AuthController::class, 'logoutSanctum']);
});



