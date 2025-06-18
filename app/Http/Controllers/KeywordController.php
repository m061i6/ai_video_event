<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use App\Models\Keyword;
use App\Events\NewKeywordSubmitted;

class KeywordController extends Controller
{
    public function submit(Request $request)
    {
        \Log::info('收到 submit-keyword 請求', $request->all());
        $keyword = $request->input('keyword');
        $sessionId = $request->session()->getId();
        $ip = $request->ip();

        // 1. 格式檢查（只允許繁中與英文，其他一律不給過）
        if (!preg_match('/^[\x{4e00}-\x{9fff}a-zA-Z]{2,8}$/u', $keyword)) {
            return response()->json(['message' => '格式錯誤，請輸入 2~8 個繁中或英文（不可含數字、標點、符號）'], 400);
        }
        $filtered = $keyword;

        // 2. 重複檢查
        $exists = Keyword::where('session_id', $sessionId)
            ->where('keyword', $filtered)
            ->exists();
        if ($exists) {
            return response()->json(['message' => '關鍵字重複 請輸入其他關鍵字'], 409);
        }

        // 3. 本地黑名單過濾
        $blacklist = config('keyword_blacklist');
        foreach ($blacklist as $badword) {
            if (mb_stripos($filtered, $badword) !== false) {
                return response()->json(['message' => '含有禁用詞，請重新輸入'], 400);
            }
        }

        // 4. AI 過濾（OpenAI）
        $openaiKey = env('OPENAI_API_KEY');
        if ($openaiKey) {
            $prompt = "請判斷下列詞語是否為不當、攻擊、色情、歧視、仇恨、髒話、敏感、違法或令人不適的詞彙，只需回答 yes 或 no：" . $filtered;
            $response = \Http::withHeaders([
                'Authorization' => 'Bearer ' . $openaiKey,
                'Content-Type' => 'application/json',
            ])->post('https://api.openai.com/v1/chat/completions', [
                'model' => 'gpt-4o-mini-2024-07-18',
                'messages' => [
                    ['role' => 'user', 'content' => $prompt],
                ],
                'max_tokens' => 3,
                'temperature' => 0,
            ]);
            $aiResult = strtolower($response->json('choices.0.message.content') ?? '');
            \Log::info('AI回傳內容', ['keyword' => $filtered, 'ai_result' => $aiResult, 'raw' => $response->json()]);
            if (str_contains($aiResult, 'yes')) {
                return response()->json(['message' => 'AI判斷此關鍵字不適合，請重新輸入'], 400);
            }
        }

        // 5. 全部通過才寫入
        Keyword::create([
            'keyword' => $filtered,
            'session_id' => $sessionId,
            'ip_address' => $ip,
        ]);
        \Log::info('關鍵字已寫入資料庫', ['keyword' => $filtered]);

        // 廣播事件
        event(new NewKeywordSubmitted($filtered));
        \Log::info('已廣播 NewKeywordSubmitted', ['keyword' => $filtered]);
        return response()->json(['message' => '提交完成']);
    }
}
