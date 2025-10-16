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

    // Activity routes
    Route::get('activity/detail/{activityId}', 'API\GroupController@getActivityDetail');
    Route::post('activity', 'API\GroupController@createActivity');
    Route::patch('update-activity/{activityId}', 'API\GroupController@updateActivity');

    // Member routes
    Route::post('add-member/{groupId}', 'API\GroupController@addMember');

    // Group management routes
    Route::get('get-group-report/{groupId}', 'API\GroupController@getGroupReport');
    Route::patch('finish-group/{groupId}', 'API\GroupController@finishGroup');
});

Route::resource('users', 'API\UserController');

// Group chat routes (cần JWT token)
Route::prefix('group-chat')->middleware('auth:api')->group(function () {
    Route::get('detail/{type}/{id}', 'API\GroupChatController@getChatDetail');
    Route::get('search', 'API\GroupChatController@searchGroupChat');
    Route::get('list-all', 'API\GroupChatController@getListGroupChat');
    Route::get('join-group/{groupId}/{code}', 'API\GroupChatController@joinGroup');
    Route::post('create-link', 'API\GroupChatController@createJoinLink');
    Route::post('/', 'API\GroupChatController@createGroupChat');
    Route::patch('update-group/{groupChatId}', 'API\GroupChatController@updateGroupChat');
    Route::patch('seen-message/{conversationId}', 'API\GroupChatController@updateSeenStatus');
    Route::post('invite-member/{groupChatId}/{userId}', 'API\GroupChatController@inviteMember');
    Route::delete('remove-member/{groupChatId}/{userId}', 'API\GroupChatController@removeMember');
});
