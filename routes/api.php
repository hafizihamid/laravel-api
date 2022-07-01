<?php

use Illuminate\Support\Facades\Route;

// Admin
Route::group(
    ['prefix' => 'admin', 'as' => 'admin.'],
    function () {
        Route::post('/login', "AuthController@login")->name('login');
    }
);
