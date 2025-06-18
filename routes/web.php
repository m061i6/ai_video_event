<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\KeywordController;
use App\Http\Controllers\VoteController;
use App\Http\Controllers\ScreenController;

Route::get('/', fn () => view('input'));
Route::get('/screen', [ScreenController::class, 'show']);
Route::get('/vote', [VoteController::class, 'show']);

Route::post('/api/submit-keyword', [KeywordController::class, 'submit'])
    ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);
Route::post('/api/vote', [VoteController::class, 'vote']);
