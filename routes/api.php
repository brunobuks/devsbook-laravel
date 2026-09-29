<?php

use Illuminate\Http\Request;
use Illuminate\Routing\RouteGroup;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\FeedController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\SearchController;

Route::get('/ping', function() {
    return['pong'];
});

Route::get('/401', [AuthController::class, 'unauthorized'])->name('login');

Route::group(
    [
        'prefix' => 'auth'
    ],
    static function () {
        Route::post('/login', [AuthController::class, 'login']);

        Route::middleware('auth:api')->group(function () {
            Route::post('/logout', [AuthController::class, 'logout']);
            Route::post('/refresh', [AuthController::class, 'refresh']);
        });
    }
);

Route::group(
    [
        'prefix' => 'user'
    ],
    static function () {
        Route::post('', [AuthController::class, 'create']);

        Route::middleware('auth:api')->group(function () {
            Route::put('', [UserController::class, 'update']);
            Route::post('/avatar', [UserController::class, 'updateAvatar']);
            Route::post('/cover', [UserController::class, 'updateCover']);
            Route::get('/feed', [FeedController::class, 'userFeed']);
            Route::get('{id}/feed', [FeedController::class, 'userFeed']);
            Route::get('', [UserController::class, 'read']);
            Route::get('/{id}', [UserController::class, 'read']);
            Route::post('/{id}/follow', [UserController::class, 'follow']);
            Route::get('/{id}/followers', [UserController::class, 'followers']);
            Route::get('{id}/photos', [FeedController::class, 'userPhotos']);
        });
    }
);

Route::group(
    [
        'prefix' => 'feed'
    ],
    static function () {
        Route::middleware('auth:api')->group(function () {
            Route::get('', [FeedController::class, 'read']);
            Route::post('', [FeedController::class, 'create']);
        });
    }
);

Route::group(
    [
        'prefix' => 'post'
    ],
    static function () {
        Route::middleware('auth:api')->group(function () {
            Route::post('/{id}/like', [PostController::class, 'like']);
            Route::post('/{id}/comment', [PostController::class, 'comment']);
        });
    }
);

Route::get('/search', [SearchController::class, 'search'])->middleware('auth:api');
