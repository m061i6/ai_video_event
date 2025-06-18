<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <title>中控台 - 關鍵字清空</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @vite(['resources/css/app.css'])
</head>
<body class="min-h-screen flex flex-col items-center justify-center bg-black text-[#00FF00]">
    <h1 class="text-2xl font-bold mb-8">中控台 - 關鍵字管理</h1>
    <div id="adminPanel" class="flex flex-col items-center">
        <button id="clearBtn" class="px-6 py-3 rounded bg-red-600 text-white font-bold text-lg hover:bg-red-400 transition mb-4">清空所有關鍵字</button>
        <p id="statusMsg" class="h-6 text-lg"></p>
    </div>
    <div id="unauthMsg" class="hidden text-red-500 text-xl font-bold mt-8">無權限</div>
    <script>
    function showPanel(show) {
        document.getElementById('adminPanel').style.display = show ? '' : 'none';
        document.getElementById('unauthMsg').style.display = show ? 'none' : '';
    }
    function checkAuth() {
        let authed = sessionStorage.getItem('admin_authed');
        if (authed === '1') return true;
        let user = window.prompt('請輸入帳號 (admin)：');
        let pass = window.prompt('請輸入密碼 (test)：');
        if (user === 'admin' && pass === 'test') {
            sessionStorage.setItem('admin_authed', '1');
            return true;
        }
        showPanel(false);
        return false;
    }
    if (!checkAuth()) {
        showPanel(false);
    }
    document.getElementById('clearBtn').onclick = function() {
        if (!confirm('確定要清空所有關鍵字？')) return;
        fetch('/admin/clear-keywords', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            credentials: 'same-origin'
        }).then(res => res.json()).then(data => {
            document.getElementById('statusMsg').textContent = data.message;
        });
    };
    </script>
</body>
</html>
