<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\FileSystemController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\FileController;
use Illuminate\Support\Facades\Route;
use App\Http\Middleware\JwtMiddleware;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;


Route::post('/broadcasting/auth', function (Request $request) {
    Log::info('Broadcasting auth request:', [
        'socket_id' => $request->socket_id,
        'channel_name' => $request->channel_name,
        'user' => $request->user(), // Log the authenticated user
    ]);

    return Broadcast::auth($request);
})->middleware('auth:sanctum');

Route::post('/login', [AuthController::class, 'loginSanctum']);

// Group all authentication-related routes under the 'auth' prefix
Route::prefix('auth')->group(function () {
    // sanctum 
    Route::post('/register', [AuthController::class, 'registerSanctum']);
    Route::post('/login', [AuthController::class, 'loginSanctum']);
    Route::post('/finish_registration', [AuthController::class , 'finishRegistration']);
    Route::post('/access_delegation/{userId}', [AuthController::class , 'allowNginxToAccessHomeDirectories']);
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/user', [AuthController::class, 'userSanctum']);
        Route::get('/users', [AuthController::class, 'users']);
        Route::post('/logout', [AuthController::class, 'logoutSanctum']);
    });

    // Route::post('/login', [AuthController::class, 'loginSanctum']);
    // Route::post('/logout', [AuthController::class, 'logoutSanctum'])->middleware('auth:sanctum');
    // Route::get('/user', [AuthController::class, 'userSanctum'])->middleware('auth:sanctum');


    // Route::get('user', [AuthController::class, 'user']);
    // Route::post('logout', [AuthController::class, 'logout']);
    // Route::post('finish_registration', [AuthController::class , 'finishRegistration'])->withoutMiddleware([JwtMiddleware::class]);

    // Route::post('login', [AuthController::class, 'login'])->withoutMiddleware([JwtMiddleware::class]);
    // Route::post('register', [AuthController::class, 'register'])->withoutMiddleware([JwtMiddleware::class]);
    
});


Route::prefix('file')->group(function (){
    Route::middleware('auth:sanctum')->group(function(){
        Route::get('list', [FileSystemController::class , 'listFolders']);
        Route::get('list-archive', [FileSystemController::class , 'listArchive']);
        Route::get('list-trash', [FileSystemController::class , 'listTrash']);
        Route::post('create-folder', [FileSystemController::class , 'createFolder']);
        Route::post('delete-folder', [FileSystemController::class , 'deleteFolder']);
        Route::post('delete-file', [FileSystemController::class , 'deleteFile']);
        Route::post('upload', [FileSystemController::class , 'uploadFile']);
    
        Route::post('upload-profile', [FileSystemController::class , 'uploadProfileImage']);
    
        Route::post('upload_file', [FileSystemController::class , 'uploadFile2']);
        Route::post('check_chunk', [FileSystemController::class , 'checkChunks']);
    
        Route::get('run', [FileSystemController::class , 'runShellCommands']);
    });

});


Route::prefix('chat')->group(function (){
    Route::middleware('auth:sanctum')->group(function () {
        
        Route::post('/send-message', [MessageController::class, 'sendMessage']);
        Route::post('/mark-as-read/{id}', [MessageController::class, 'markAsRead']);
        Route::get('/message-history/{userId}', [MessageController::class, 'getHistory']);
        Route::post('/last-active/{userId}', [MessageController::class, 'setLastActiveTime']);
    
        Route::post('/create-group', [GroupController::class, 'createGroup']);
        Route::post('/add-user-to-group/{groupId}', [GroupController::class, 'addUserToGroup']);
        Route::get('/new-messages', [MessageController::class, 'getUnreadMessagesCount']);
    });
});

Route::prefix('dashboard')->group(function (){
    Route::middleware('auth:sanctum')->group(function(){
        Route::get('/total-stats', [DashboardController::class , 'getTotalStats']);
    });
    

});

Route::prefix('file')->group(function (){
    Route::middleware('auth:sanctum')->group(function(){
        Route::get('/total-files', [FileController::class , 'getAllFiles']);
        Route::get('/recent-files', [FileController::class , 'getRecentFiles']);
    });
    

});


Route::get('/debug-session', function (Request $request) {
    return response()->json([
        'cookies' => request()->cookies->all(),
        'headers' => request()->headers->all(),
        'session' => session()->all(),
        'user' => auth()->user(),
    ]);
})->middleware(['auth:sanctum']);

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('');
