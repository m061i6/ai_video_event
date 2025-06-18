<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Vote;
use Illuminate\Support\Facades\Session;

class VoteController extends Controller
{
    public function show()
    {
        return view('vote');
    }

    public function vote(Request $request)
    {
        $request->validate([
            'script_id' => 'required|integer|exists:scripts,id',
        ]);
        $sessionId = $request->session()->getId();
        $ip = $request->ip();
        $scriptId = $request->input('script_id');

        // 檢查是否已投票
        $alreadyVoted = Vote::where('session_id', $sessionId)->exists();
        if ($alreadyVoted) {
            return response()->json(['message' => '您已經投過票，無法再次投票'], 403);
        }

        // 寫入投票紀錄
        Vote::create([
            'script_id' => $scriptId,
            'session_id' => $sessionId,
            'ip_address' => $ip,
        ]);

        return response()->json(['message' => '投票成功！']);
    }
}
