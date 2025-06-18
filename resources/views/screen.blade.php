<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <title>AI 影片活動 - 關鍵字雲</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @vite(['resources/css/app.css', 'resources/js/screen.js'])
    <script src="https://cdn.jsdelivr.net/npm/wordcloud@1.2.2/src/wordcloud2.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/laravel-echo/1.15.0/echo.iife.js"></script>
    <script src="https://js.pusher.com/7.2/pusher.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
</head>
<body class="w-screen h-screen bg-black text-[#00FF00] overflow-hidden relative">
    <div id="mainStage" class="w-full h-full relative">
        <div id="qrWrapper" class="fixed right-8 bottom-8 z-50 bg-white p-6 rounded-2xl shadow-2xl flex flex-col items-center">
            <canvas id="qrCanvas"></canvas>
        </div>
        <div id="countdown" class="absolute left-1/2 top-[calc(50%+180px)] -translate-x-1/2 text-4xl font-bold text-[#00FF00] z-20 drop-shadow-lg select-none">
            60
        </div>
        <div id="wordCloud" class="absolute left-0 top-0 w-full h-full pointer-events-none z-5 pr-56 pb-56"></div>
    </div>
    <script>
        window.APP_QR_URL = "{{ env('APP_QR_URL', config('app.url')) }}";
        window.Pusher = Pusher;
        window.Echo = new Echo({
            broadcaster: 'pusher',
            key: "{{ env('PUSHER_APP_KEY') }}",
            cluster: "{{ env('PUSHER_APP_CLUSTER') }}",
            forceTLS: true
        });
        document.addEventListener('DOMContentLoaded', function() {
            var qrUrl = window.APP_QR_URL || (window.location.origin + '/');
            var qrWrapper = document.getElementById('qrWrapper');
            // 移除舊 canvas
            var oldCanvas = document.getElementById('qrCanvas');
            if (oldCanvas) oldCanvas.remove();
            // 新增 div 給 qrcodejs
            var qrDiv = document.createElement('div');
            qrDiv.id = 'qrCanvas';
            qrWrapper.appendChild(qrDiv);
            new QRCode(qrDiv, {
                text: qrUrl,
                width: 200,
                height: 200,
                colorDark: "#000000",
                colorLight: "#ffffff",
                correctLevel: QRCode.CorrectLevel.H
            });
        });
    </script>
</body>
</html>
