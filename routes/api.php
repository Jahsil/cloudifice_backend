<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\FileSystemController;
use Illuminate\Support\Facades\Route;
use App\Http\Middleware\JwtMiddleware;
use Illuminate\Support\Facades\Validator;

// Group all authentication-related routes under the 'auth' prefix
Route::prefix('auth')->group(function () {
    Route::middleware([JwtMiddleware::class])->group(function () {
        Route::get('user', [AuthController::class, 'user']);
        Route::post('logout', [AuthController::class, 'logout']);

     
        Route::post('login', [AuthController::class, 'login'])->withoutMiddleware([JwtMiddleware::class]);
        Route::post('register', [AuthController::class, 'register'])->withoutMiddleware([JwtMiddleware::class]);
    
    });
});


Route::prefix('file')->group(function (){
    // Route::middleware([JwtMiddleware::class])->group(function(){
    // });
    Route::get('list', [FileSystemController::class , 'listFolders']);
    Route::post('create-folder', [FileSystemController::class , 'createFolder']);
    Route::post('delete-folder', [FileSystemController::class , 'deleteFolder']);
    Route::post('upload', [FileSystemController::class , 'uploadFile']);

    Route::get('run', [FileSystemController::class , 'runShellCommands']);
});




