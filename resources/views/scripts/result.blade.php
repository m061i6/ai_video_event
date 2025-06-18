@extends('layouts.app')
@section('content')
<div class="min-h-screen bg-gradient-to-b from-black via-gray-900 to-gray-800 flex flex-col items-center justify-center py-8">
    <h1 class="text-5xl font-extrabold text-blue-300 mb-10 tracking-wide drop-shadow-lg text-center">三個劇本產出結果</h1>
    <div id="countdown" class="text-4xl text-yellow-400 font-extrabold mb-8 tracking-widest text-center"></div>
    <div class="w-full max-w-5xl space-y-12" id="scripts-list">
        @foreach($scripts as $idx => $script)
        <div class="bg-white/20 backdrop-blur-md rounded-2xl shadow-2xl p-10 text-gray-100 text-3xl font-semibold border-l-8 border-blue-400 flex flex-col items-start animate-fade-in">
            <div class="mb-4 text-blue-400 font-extrabold text-2xl">劇本 {{ $idx+1 }}</div>
            <div class="whitespace-pre-line leading-relaxed mb-8 text-3xl text-blue-100">{{ $script->content }}</div>
            <div class="mt-auto text-5xl font-extrabold flex items-center gap-4">
                <span class="text-2xl font-bold text-gray-200">票數：</span>
                <span class="vote-count text-green-400 drop-shadow-lg" data-script-id="{{ $script->id }}">0</span>
            </div>
        </div>
        @endforeach
    </div>
    <button id="generateVideoBtn" class="mt-12 px-10 py-5 rounded-2xl bg-gradient-to-r from-blue-600 to-blue-400 text-white text-3xl font-extrabold shadow-xl hover:from-blue-800 hover:to-blue-600 transition hidden">產出影片</button>
</div>
<script>
let remain = 60;
let timer = setInterval(() => {
    remain--;
    document.getElementById('countdown').textContent = `投票倒數：${remain} 秒`;
    if(remain <= 0) {
        clearInterval(timer);
        document.getElementById('countdown').textContent = '投票已結束';
        document.getElementById('generateVideoBtn').classList.remove('hidden');
    }
}, 1000);

function updateVotes() {
    fetch('/api/vote-counts?ids={{ implode(",", $scripts->pluck('id')->toArray()) }}')
        .then(res => res.json())
        .then(data => {
            document.querySelectorAll('.vote-count').forEach(span => {
                const id = span.getAttribute('data-script-id');
                span.textContent = data[id] || 0;
            });
        });
}
updateVotes();
let voteInterval = setInterval(() => {
    if(remain > 0) updateVotes();
}, 1000);
</script>
@endsection
