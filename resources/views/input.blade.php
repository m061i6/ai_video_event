<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="utf-8">
    <title>輸入關鍵字</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @vite(['resources/css/app.css'])
</head>
<body class="min-h-screen flex flex-col items-center justify-center bg-black text-[#00FF00]">
    <h1 class="text-3xl font-bold mb-6">請輸入一個關鍵字</h1>
    <form id="keywordForm" class="flex flex-row gap-2 items-center mb-4">
        <input type="text" name="keyword" id="keyword" required maxlength="8" class="px-4 py-2 rounded bg-gray-900 text-[#00FF00] border border-gray-700 focus:outline-none focus:ring-2 focus:ring-green-400 placeholder-gray-500" placeholder="請輸入...">
        <button type="submit" class="px-4 py-2 rounded bg-[#00FF00] text-black font-bold hover:bg-green-400 transition">提交</button>
    </form>
    <p id="statusMsg" class="h-6 text-lg"></p>
    <script>
    document.getElementById('keywordForm').onsubmit = function(e) {
        e.preventDefault();
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
        });
    };
    </script>
</body>
</html>
