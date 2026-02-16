<?php

use App\Http\Controllers\Api\ActivityController;
use App\Http\Controllers\Api\ArticleController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\PostController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();

})->middleware('auth:sanctum');

Route::get('/ping', function () {
    return response()->json(['message' => 'pong'], 200);
});

Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']); 
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/google', [AuthController::class, 'loginWithGoogle']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::post ('/auth/logout', [AuthController::class, 'logout']);
   Route::prefix('posts')->group(function () {
       Route::get('/', [PostController::class, 'index']);
       Route::post('/', [PostController::class, 'store']);
       Route::get('/{slug}', [PostController::class, 'show']);
       Route::post('/{content}/answers', [PostController::class, 'addAnswer']);
       Route::get('/{content}/answers', [PostController::class, 'showAllAnswers']);
       Route::post('/{content}/reactions', [PostController::class, 'addReaction']);
   });

   Route::prefix('articles')->group(function () {
       Route::get('/', [ArticleController::class, 'index']);
       Route::post('/', [ArticleController::class, 'store']);
       Route::get('/{slug}', [ArticleController::class, 'show']);
   });


   Route::prefix('notifications')->group(function () {
       Route::get('/', [NotificationController::class, 'index']);

   });

   Route::prefix('activities')->group(function () {
       Route::get('/', [ActivityController::class, 'index']);

   });
});
