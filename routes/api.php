<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;

// Admin
Route::group(
    ['prefix' => 'admin', 'as' => 'admin.'],
    function () {
        Route::post('/login', [AuthController::class, 'login']);
    }
);
