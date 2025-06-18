<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\KeywordController;
use App\Http\Controllers\VoteController;
use App\Http\Controllers\ScreenController;
use App\Http\Controllers\ScriptController;
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
Route::post('/admin/deactivate-all-scripts', function () {
    \App\Models\Script::query()->update(['is_active' => 0]);
    return response()->json(['message' => '所有腳本已停用']);
});

Route::post('/api/submit-keyword', [KeywordController::class, 'submit'])
    ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);
Route::post('/api/vote', [VoteController::class, 'vote']);
Route::post('/api/generate-scripts', [ScriptController::class, 'generate'])
    ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);
Route::get('/scripts/result', [ScriptController::class, 'showResult']);
Route::get('/api/active-scripts', function() {
    $scripts = \App\Models\Script::where('is_active', 1)->orderBy('id')->get(['id', 'content']);
    return response()->json(['scripts' => $scripts]);
})->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);

Route::get('/api/my-keyword', function(\Illuminate\Http\Request $request) {
    $sessionId = $request->session()->getId();
    $keyword = \App\Models\Keyword::where('session_id', $sessionId)->latest()->first();
    return response()->json(['keyword' => $keyword ? $keyword->keyword : null]);
})->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);

Route::get('/api/my-vote', function(\Illuminate\Http\Request $request) {
    $sessionId = $request->session()->getId();
    $vote = \App\Models\Vote::where('session_id', $sessionId)->latest()->first();
    return response()->json(['script_id' => $vote ? $vote->script_id : null]);
})->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);

Route::get('/api/vote-counts', function(\Illuminate\Http\Request $request) {
    $ids = collect(explode(',', $request->query('ids')))->filter()->map('intval')->all();
    $counts = $ids ? \App\Models\Vote::whereIn('script_id', $ids)->selectRaw('script_id, count(*) as c')->groupBy('script_id')->pluck('c','script_id')->toArray() : [];
    return response()->json($counts);
})->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);
