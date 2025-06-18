<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Keyword;
use App\Models\Script;
use Illuminate\Support\Facades\Http;
use App\Events\ScriptsGenerated;

class ScriptController extends Controller
{
    public function generate(Request $request)
    {
        // 測試推播用：如果有 test=1 參數，直接推播目前 is_active=1 的三個 id
        if ($request->query('test') == 1) {
            $ids = Script::where('is_active', 1)->orderBy('id')->limit(3)->pluck('id')->toArray();
            event(new \App\Events\ScriptsGenerated($ids));
            $scripts = Script::whereIn('id', $ids)->pluck('content', 'id')->toArray();
            return response()->json(['ids' => $ids, 'scripts' => $scripts]);
        }
        // 1. 撈取所有關鍵字
        $keywords = Keyword::pluck('keyword')->toArray();
        if (empty($keywords)) {
            return response()->json(['error' => 'No keywords found'], 400);
        }
        $keywordStr = implode('、', $keywords);
        // 2. 呼叫 OpenAI API 產生三段腳本
        $prompts = [];
        for ($i = 0; $i < 3; $i++) {
            $prompts[] = "請用以下關鍵字：{$keywordStr}，產生一段適合6秒影片的腳本描述，字數不用多，描述要能用於TextToVideo生成影片。";
        }
        $scripts = [];
        foreach ($prompts as $prompt) {
            $response = Http::withToken(env('OPENAI_API_KEY'))
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => 'gpt-4o-mini-2024-07-18',
                    'messages' => [
                        ['role' => 'system', 'content' => '你是一個短影音腳本產生器，請用繁體中文回答。'],
                        ['role' => 'user', 'content' => $prompt],
                    ],
                    'max_tokens' => 120,
                    'temperature' => 1.0,
                ]);
            $text = $response->json('choices.0.message.content') ?? '';
            $scripts[] = trim($text);
        }
        // 3. 先將所有舊的 is_active=0
        Script::query()->update(['is_active' => 0]);
        // 4. 寫入新劇本 is_active=1
        $ids = [];
        foreach ($scripts as $content) {
            $script = Script::create(['content' => $content, 'is_active' => 1]);
            $ids[] = $script->id;
        }
        // 5. 再從資料庫撈出這三筆劇本內容
        $scriptContents = Script::whereIn('id', $ids)->pluck('content', 'id')->toArray();
        // 推播事件
        event(new ScriptsGenerated($ids));
        // 6. 回傳三個劇本id與內容
        return response()->json(['ids' => $ids, 'scripts' => $scriptContents]);
    }

    public function showResult(Request $request)
    {
        $ids = explode(',', $request->query('ids', ''));
        $scripts = Script::whereIn('id', $ids)->get();
        return view('scripts.result', ['scripts' => $scripts]);
    }
}
