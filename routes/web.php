<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\KeywordController;
use App\Http\Controllers\VoteController;
use App\Http\Controllers\ScreenController;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

Route::get('/', fn () => view('input'));
Route::get('/screen', [ScreenController::class, 'show']);
Route::get('/vote', [VoteController::class, 'show']);

// --- Admin 驗證區塊 ---
// 只保留 /admin 與 /admin/clear-keywords，無 session 驗證
Route::get('/admin', function () {
    return view('admin');
});
Route::post('/admin/clear-keywords', function () {
    DB::table('keywords')->delete(); // 改用 delete() 以避免外鍵問題
    return response()->json(['message' => '關鍵字已全部清空']);
});

Route::post('/api/submit-keyword', [KeywordController::class, 'submit'])
    ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);
Route::post('/api/vote', [VoteController::class, 'vote']);
