<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>輸入關鍵字</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>
<body>
    <h1>請輸入一個關鍵字</h1>
    <form id="keywordForm">
        <input type="text" name="keyword" id="keyword" required maxlength="8">
        <button type="submit">提交</button>
    </form>
    <p id="statusMsg"></p>
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
