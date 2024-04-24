<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\FileController;

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

//API route for register new user
Route::post('/register', [App\Http\Controllers\API\AuthController::class, 'register']);
//API route for login user
Route::post('/login', [App\Http\Controllers\API\AuthController::class, 'login']);
//API route for login user
Route::get('/logout', [App\Http\Controllers\API\AuthController::class, 'logout']);

Route::controller(FileController::class)->group(function() {
    Route::get('get-file-raw/{file_id}/{file_token}', 'getFileRaw');
    Route::get('get-file-thumbnail/{file_id}/{file_token}', 'getFileThumbnail');
});

//Protecting Routes
Route::group(['middleware' => ['auth:sanctum']], function () {
    Route::controller(UserController::class)->group(function(){
        Route::get('get-all-user', 'getAllUser');
        Route::get('get-user-setting',  'getUserSetting');
        Route::get('get-user/{user_id}', 'getUser');
        Route::post('add-user', 'addUser');
        Route::put('update-user', 'updateUser');
        Route::delete('delete-user/{user_id}', 'deleteUser');
    });

    Route::controller(RoomController::class)->group(function(){
        Route::get('get-all-room', 'getAllRoom');
        Route::get('get-room/{room_id}', 'getRoom');
        Route::post('add-room', 'addRoom');
        Route::put('update-room', 'updateRoom');
        Route::delete('delete-room/{room_id}', 'deleteRoom');
    });

    Route::controller(MessageController::class)->group(function(){
        Route::get('get-messages/{room_id}/{position?}/{type?}', 'getMessages');
        Route::get('get-message/{room_id}/{message_id}', 'getMessage');
        Route::post('add-message', 'addMessage');
        Route::put('update-message', 'updateMessage');
        Route::delete('delete-message/{room_id}/{message_id}', 'deleteMessage');
    });

    Route::controller(MemberController::class)->group(function(){
        Route::get('get-members-of-room/{room_id}', 'getMembers');
        Route::put('set-unread', 'setUnread');
    });

    Route::controller(FileController::class)->group(function(){
        Route::post('upload-files', 'uploadFiles');
        Route::get('get-my-file', 'getMyFile');
        Route::get('get-room-file/{room_id}', 'getRoomFile');

    });
});
