<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Keyword;
use App\Models\Script;
use Illuminate\Support\Facades\Http;

class ScriptController extends Controller
{
    public function generate(Request $request)
    {
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
                    'model' => 'gpt-3.5-turbo',
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
        // 3. 寫入scripts table
        $ids = [];
        foreach ($scripts as $content) {
            $script = Script::create(['content' => $content]);
            $ids[] = $script->id;
        }
        // 4. 回傳三個劇本id
        return response()->json(['ids' => $ids]);
    }

    public function showResult(Request $request)
    {
        $ids = explode(',', $request->query('ids', ''));
        $scripts = Script::whereIn('id', $ids)->pluck('content');
        return view('scripts.result', ['scripts' => $scripts]);
    }
}
