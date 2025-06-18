<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class VoteController extends Controller
{
    public function show()
    {
        return view('vote');
    }

    public function vote(Request $request)
    {
        // 接收投票邏輯
        return response()->json(['message' => '投票成功']);
    }
}
