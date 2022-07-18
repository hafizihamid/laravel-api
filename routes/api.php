<?php

use Illuminate\Support\Facades\Route;

Route::group(
    ['namespace' => 'Api', 'as' => 'api.', 'prefix' => 'v1'],
    function () {
        // Admin
        Route::group(
            ['prefix' => 'admin', 'as' => 'admin.'],
            function () {
                Route::post('/login', 'AuthController@login')->name('login');
                Route::group(
                    ['prefix' => 'password', 'as' => 'password.'],
                    function () {
                        Route::post('/forgot', 'AuthController@forgot')->name('forgot');
                        Route::post('/reset', 'AuthController@reset')->name('reset');
                    }
                );
            }
        );
    }
);
