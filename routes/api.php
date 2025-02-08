<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\FileSystemController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\GroupController;
use Illuminate\Support\Facades\Route;
use App\Http\Middleware\JwtMiddleware;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Broadcast;


// Group all authentication-related routes under the 'auth' prefix
Route::prefix('auth')->group(function () {
    Route::middleware([JwtMiddleware::class])->group(function () {
        Route::get('user', [AuthController::class, 'user']);
        Route::post('logout', [AuthController::class, 'logout']);
        Route::post('finish_registration', [AuthController::class , 'finishRegistration'])->withoutMiddleware([JwtMiddleware::class]);


     
        Route::post('login', [AuthController::class, 'login'])->withoutMiddleware([JwtMiddleware::class]);
        Route::post('register', [AuthController::class, 'register'])->withoutMiddleware([JwtMiddleware::class]);
    
    });
});

Broadcast::routes(['middleware' => [JwtMiddleware::class]]);

Route::prefix('file')->group(function (){
    Route::middleware([JwtMiddleware::class])->group(function(){
        Route::get('list', [FileSystemController::class , 'listFolders']);
        Route::get('list-archive', [FileSystemController::class , 'listArchive']);
        Route::get('list-trash', [FileSystemController::class , 'listTrash']);
        Route::post('create-folder', [FileSystemController::class , 'createFolder']);
        Route::post('delete-folder', [FileSystemController::class , 'deleteFolder']);
        Route::post('upload', [FileSystemController::class , 'uploadFile']);


        Route::post('upload_file', [FileSystemController::class , 'uploadFile2']);
        Route::post('check_chunk', [FileSystemController::class , 'checkChunks']);
    
        Route::get('run', [FileSystemController::class , 'runShellCommands']);
    });

});


Route::prefix('chat')->group(function (){
    Route::middleware([JwtMiddleware::class])->group(function(){
        Route::post('/send-message', [MessageController::class, 'sendMessage']);
        Route::post('/mark-as-read/{id}', [MessageController::class, 'markAsRead']);
        Route::get('/message-history/{userId}', [MessageController::class, 'getHistory']);

        Route::post('/create-group', [GroupController::class, 'createGroup']);
        Route::post('/add-user-to-group/{groupId}', [GroupController::class, 'addUserToGroup']);

    });
});

