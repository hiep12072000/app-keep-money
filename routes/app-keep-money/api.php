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
    Route::get('/', 'APP_KEEP_MONEY\GroupController@getList');
    Route::get('detail/{id}', 'APP_KEEP_MONEY\GroupController@getDetail');
    Route::get('{id}', 'APP_KEEP_MONEY\GroupController@getById');
    Route::post('/', 'APP_KEEP_MONEY\GroupController@create');
    Route::put('{id}', 'APP_KEEP_MONEY\GroupController@update');
    Route::patch('update-group/{groupId}', 'APP_KEEP_MONEY\GroupController@updateGroup');
    Route::delete('{id}', 'APP_KEEP_MONEY\GroupController@delete');

    // Activity routes
    Route::get('activity/detail/{activityId}', 'APP_KEEP_MONEY\GroupController@getActivityDetail');
    Route::post('activity', 'APP_KEEP_MONEY\GroupController@createActivity');
    Route::patch('update-activity/{activityId}', 'APP_KEEP_MONEY\GroupController@updateActivity');

    // Member routes
    Route::post('add-member/{groupId}', 'APP_KEEP_MONEY\GroupController@addMember');

    // Group management routes
    Route::get('get-group-report/{groupId}', 'APP_KEEP_MONEY\GroupController@getGroupReport');
    Route::patch('finish-group/{groupId}', 'APP_KEEP_MONEY\GroupController@finishGroup');
});

Route::resource('users', 'APP_KEEP_MONEY\UserController');

// Group chat routes (cần JWT token)
Route::prefix('group-chat')->middleware('auth:api')->group(function () {
    Route::get('detail/{type}/{id}', 'APP_KEEP_MONEY\GroupChatController@getChatDetail');
    Route::get('search', 'APP_KEEP_MONEY\GroupChatController@searchGroupChat');
    Route::get('list-all', 'APP_KEEP_MONEY\GroupChatController@getListGroupChat');
    Route::get('join-group/{groupId}/{code}', 'APP_KEEP_MONEY\GroupChatController@joinGroup');
    Route::post('create-link', 'APP_KEEP_MONEY\GroupChatController@createJoinLink');
    Route::post('/', 'APP_KEEP_MONEY\GroupChatController@createGroupChat');
    Route::patch('update-group/{groupChatId}', 'APP_KEEP_MONEY\GroupChatController@updateGroupChat');
    Route::patch('seen-message/{conversationId}', 'APP_KEEP_MONEY\GroupChatController@updateSeenStatus');
    Route::post('invite-member/{groupChatId}/{userId}', 'APP_KEEP_MONEY\GroupChatController@inviteMember');
    Route::delete('remove-member/{groupChatId}/{userId}', 'APP_KEEP_MONEY\GroupChatController@removeMember');
});
