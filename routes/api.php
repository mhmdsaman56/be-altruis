<?php

use App\Http\Controllers\Api\ArticleController;
use App\Http\Controllers\Api\AuthController;
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
Route::get('/google/redirect',[AuthController::class, 'redirectToGoogle'])->name('google.redirect');
Route::get('/google/callback',[AuthController::class, 'handleGoogleCallback'])->name('google.callback');
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/auth/logout', [AuthController::class, 'logout']);
   Route::prefix('posts')->group(function () {
       Route::get('/', [PostController::class, 'index']);
       Route::post('/', [PostController::class, 'store']);
       Route::post('/{content}/answers', [PostController::class, 'addAnswer']);
       Route::get('/{content}/answers', [PostController::class, 'showAllAnswers']);
       Route::post('/{content}/reactions', [PostController::class, 'addReaction']);
   });

   Route::prefix('articles')->group(function () {
       Route::get('/', [ArticleController::class, 'index']);
       Route::post('/', [ArticleController::class, 'store']);
       Route::get('/{slug}', [ArticleController::class, 'show']);
   });

});
