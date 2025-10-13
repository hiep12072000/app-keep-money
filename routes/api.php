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
    Route::get('find-myself', 'API\AuthController@findMyself');
    Route::get('find-except-me', 'API\AuthController@findExceptMe');
});

// User profile route (cần JWT token)
Route::patch('user', 'API\AuthController@updateProfile')->middleware('auth:api');

// File upload routes (cần JWT token)
Route::prefix('files')->middleware('auth:api')->group(function () {
    Route::post('upload', 'API\FileController@upload');
    Route::delete('delete', 'API\FileController@delete');
});

// Group routes (cần JWT token)
Route::prefix('group')->middleware('auth:api')->group(function () {
    Route::get('/', 'API\GroupController@getList');
    Route::get('detail/{id}', 'API\GroupController@getDetail');
    Route::get('{id}', 'API\GroupController@getById');
    Route::post('/', 'API\GroupController@create');
    Route::put('{id}', 'API\GroupController@update');
    Route::patch('update-group/{groupId}', 'API\GroupController@updateGroup');
    Route::delete('{id}', 'API\GroupController@delete');
});

Route::resource('users', 'API\UserController');