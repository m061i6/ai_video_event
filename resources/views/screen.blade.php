<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <title>即時關鍵字雲</title>
    <script src="https://cdn.jsdelivr.net/npm/wordcloud@1.2.2/src/wordcloud2.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/laravel-echo/1.15.0/echo.iife.js"></script>
    <script src="https://js.pusher.com/7.2/pusher.min.js"></script>
    <style>
        #wordCloud {
            width: 100vw;
            height: 100vh;
        }
    </style>
</head>
<body>
    <canvas id="wordCloud"></canvas>

    <script>
        let words = [];
        const wordMap = {};

        function updateWordCloud() {
            const list = Object.entries(wordMap).map(([k, v]) => [k, v]);
            WordCloud(document.getElementById('wordCloud'), {
                list: list,
                weightFactor: 10,
                gridSize: 10,
                rotateRatio: 0.5,
                backgroundColor: '#000',
                color: '#0f0'
            });
        }

        // Laravel Echo + Pusher 設定
        window.Pusher = Pusher;
        window.Echo = new Echo({
            broadcaster: 'pusher',
            key: "{{ env('PUSHER_APP_KEY') }}",
            cluster: "{{ env('PUSHER_APP_CLUSTER') }}",
            forceTLS: true
        });

        window.Echo.channel('keywords')
            .listen('NewKeywordSubmitted', (e) => {
                const keyword = e.keyword;
                if (wordMap[keyword]) {
                    wordMap[keyword]++;
                } else {
                    wordMap[keyword] = 1;
                }
                updateWordCloud();
            });

        // 初始可選：載入現有關鍵字
        // fetch('/api/keywords').then(r => r.json()).then(data => { ... });
    </script>
</body>
</html>
