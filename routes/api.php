<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::group(
    ['namespace' => 'Api', 'as' => 'api.', 'prefix' => 'v1'],
    function () {
        Route::post('/login', 'CustomerAuthController@login')->name('login');
        Route::post('/register', 'CustomerAuthController@register')->name('register');

        //give two times more atteempt for api call... another throttle for sms is already in place
        $otpThrottle = (config("staticdata.throttle.otp.attempt") * 2) . "," . config("staticdata.throttle.otp.decay") . ",otp";
        Route::post('/sendOTP', 'CustomerAuthController@sendOTP')->name('sendOTP')->middleware("throttle_customer:$otpThrottle");
        Route::post('/verifyOTP', 'CustomerAuthController@verifyOTP')->name('verifyOTP');
        Route::post('/reset', 'CustomerAuthController@reset')->name('reset');
    }
);
