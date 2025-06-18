<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="utf-8">
    <title>輸入關鍵字</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @vite(['resources/css/app.css'])
</head>
<body class="min-h-screen flex flex-col items-center justify-center bg-black text-[#00FF00]">
    <div id="stage1" class="flex flex-col items-center w-full px-2">
        <h1 id="stage1Title" class="text-3xl font-bold text-white mb-2 text-center">階段一：關鍵字輸入</h1>
        <div class="bg-white/90 rounded-xl shadow-lg p-6 sm:p-8 text-gray-900 text-xl font-medium border-l-8 border-blue-400 mb-8 w-full max-w-xl flex flex-col items-center">
            <div id="inputTitle" class="text-2xl font-bold text-blue-600 mb-4 text-center">請輸入一個關鍵字</div>
            <form id="keywordForm" class="flex flex-col sm:flex-row gap-2 items-center mb-4 w-full justify-center">
                <input type="text" name="keyword" id="keyword" required maxlength="8" class="px-4 py-2 rounded bg-gray-900 text-white border border-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-400 placeholder-gray-500 w-full sm:w-auto" placeholder="請輸入...">
                <button type="submit" class="px-4 py-2 rounded bg-blue-500 text-white font-bold hover:bg-blue-700 transition w-full sm:w-auto">提交</button>
            </form>
            <p id="statusMsg" class="h-6 text-lg"></p>
        </div>
    </div>
    <div id="stage2" class="flex flex-col items-center w-full px-2" style="display:none"></div>
    <script>
    // 狀態初始化
    let myKeyword = null;
    let myVoteScriptId = null;

    // 查詢自己是否已輸入關鍵字
    fetch('/api/my-keyword', {
        credentials: 'same-origin'
    }).then(res => res.json()).then(data => {
        if (data.keyword) {
            myKeyword = data.keyword;
            document.getElementById('keyword').value = myKeyword;
            document.getElementById('keyword').disabled = true;
            document.querySelector('#keywordForm button[type="submit"]').disabled = true;
            document.getElementById('statusMsg').innerHTML = `已輸入關鍵字<span class="text-red-500 font-bold">「${myKeyword}」</span>成功，請等待其他人輸入`;
        }
        startPollingActiveScripts();
    });

    // 送出關鍵字
    document.getElementById('keywordForm').onsubmit = function(e) {
        e.preventDefault();
        if (myKeyword) return;
        const form = new FormData();
        form.append('keyword', document.getElementById('keyword').value);
        fetch('/api/submit-keyword', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: form,
            credentials: 'same-origin'
        }).then(res => res.json()).then(data => {
            document.getElementById('statusMsg').textContent = data.message;
            if (data.message && data.message.includes('完成')) {
                myKeyword = document.getElementById('keyword').value;
                document.getElementById('keyword').disabled = true;
                document.querySelector('#keywordForm button[type="submit"]').disabled = true;
            }
        });
    };

    // 輪詢查詢三筆 is_active 劇本
    function startPollingActiveScripts() {
        let polling = setInterval(() => {
            fetch('/api/active-scripts', {
                method: 'GET',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                credentials: 'same-origin'
            })
            .then(res => res.json())
            .then(data => {
                if (data.scripts && data.scripts.length === 3) {
                    clearInterval(polling);
                    document.getElementById('stage1').style.display = 'none';
                    showVoteUI(data.scripts);
                }
            });
        }, 3000);
    }

    // 投票階段
    function showVoteUI(scripts) {
        // 查詢自己是否已投票
        fetch('/api/my-vote', { credentials: 'same-origin' })
            .then(res => res.json())
            .then(data => {
                myVoteScriptId = data.script_id;
                renderVoteUI(scripts);
            });
    }

    function renderVoteUI(scripts) {
        const stage2 = document.getElementById('stage2');
        stage2.innerHTML = '';
        stage2.style.display = 'flex';
        const title = document.createElement('h1');
        title.className = 'text-3xl font-bold text-white mb-2 text-center';
        title.textContent = '階段二：劇本投票';
        stage2.appendChild(title);
        const subtitle = document.createElement('h2');
        subtitle.className = 'text-2xl font-bold text-white mb-6 text-center';
        subtitle.textContent = '請選擇你最喜歡的劇本並投票';
        stage2.appendChild(subtitle);
        scripts.forEach((script, idx) => {
            const box = document.createElement('div');
            box.className = 'bg-white/90 rounded-xl shadow-lg p-6 sm:p-8 text-gray-900 text-lg font-medium border-l-8 border-blue-400 mb-6 w-full max-w-xl';
            box.innerHTML = `<div class=\"mb-2 text-blue-600 font-bold\">劇本 ${idx+1}</div><div class=\"whitespace-pre-line mb-4\">${script.content}</div>`;
            const btn = document.createElement('button');
            btn.className = 'vote-btn px-4 py-2 rounded font-bold w-full sm:w-auto';
            btn.textContent = '投票';
            if (myVoteScriptId) {
                btn.disabled = true;
                if (myVoteScriptId == script.id) {
                    btn.className += ' bg-blue-500 text-white';
                    btn.textContent = '你已投票';
                } else {
                    btn.className += ' bg-gray-400 text-gray-200';
                }
            } else {
                btn.className += ' bg-blue-500 text-white hover:bg-blue-700 transition';
                btn.onclick = function() {
                    submitVote(script.id, btn, stage2);
                };
            }
            box.appendChild(btn);
            stage2.appendChild(box);
        });
    }

    function submitVote(scriptId, btn, container) {
        fetch('/api/vote', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Content-Type': 'application/json'
            },
            credentials: 'same-origin',
            body: JSON.stringify({ script_id: scriptId })
        })
        .then(res => res.json())
        .then(data => {
            myVoteScriptId = scriptId;
            renderVoteUI(Array.from(container.querySelectorAll('.vote-btn')).map((btn, i) => ({
                id: scripts[i].id,
                content: scripts[i].content
            })));
        })
        .catch(async err => {
            let msg = '投票失敗';
            if (err && err instanceof Response) {
                const data = await err.json();
                msg = data.message || msg;
            }
            btn.textContent = msg;
            container.querySelectorAll('.vote-btn').forEach(b => b.disabled = true);
        });
    }
    </script>
</body>
</html>
