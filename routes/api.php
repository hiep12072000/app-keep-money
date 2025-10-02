<?php

use Illuminate\Http\Request;

// Auth routes
Route::prefix('auth')->group(function () {
    Route::post('register', 'API\AuthController@register');
    Route::post('login', 'API\AuthController@login');
    
    // Forgot password routes (không cần token)
    Route::prefix('forgot-password')->group(function () {
        Route::post('send-email', 'API\AuthController@sendEmail');
        Route::post('send-otp', 'API\AuthController@verifyOtp');
        Route::patch('reset-password', 'API\AuthController@resetPassword');
    });
    
    // Protected routes (cần JWT token)
    Route::middleware('auth:api')->group(function () {
        Route::post('logout', 'API\AuthController@logout');
        Route::get('me', 'API\AuthController@me');
        Route::post('refresh', 'API\AuthController@refresh');
    });
});

// User routes (cần JWT token)
Route::prefix('user')->middleware('auth:api')->group(function () {
    Route::patch('change-password', 'API\AuthController@changePassword');
});

// File upload routes (cần JWT token)
Route::prefix('files')->middleware('auth:api')->group(function () {
    Route::post('upload', 'API\FileController@upload');
    Route::delete('delete', 'API\FileController@delete');
});

Route::resource('users', 'API\UserController');