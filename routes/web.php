<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\FileSystemController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/view-file', [FileSystemController::class, 'viewFile']);
