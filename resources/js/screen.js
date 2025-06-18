import QRCode from 'qrcode';

window.wordList = [];
window.wordFreq = {};
window.wordMeta = {}; // 新增：記錄每個關鍵字的位置與角度
window.wordColor = {}; // 關鍵字顏色記憶：每個關鍵字第一次分配顏色後就固定
let countdown = 60;
let timer = null;
let wsListener = null;
let stageEnded = false;
let pendingOverlay = null;

const qrUrl = window.location.origin + '/';
const qrWrapper = document.getElementById('qrWrapper');
const qrcodeDiv = document.getElementById('qrcode');
const countdownDiv = document.getElementById('countdown');
const wordCloudDiv = document.getElementById('wordCloud');

// 產生 QR Code（置中）
QRCode.toCanvas(qrcodeDiv, qrUrl, {
    width: 220,
    margin: 1,
    color: { dark: '#000', light: '#fff' }
}, err => { if (err) console.error(err) });
qrWrapper.className = 'fixed left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2 z-50 bg-white p-6 rounded-2xl shadow-2xl flex flex-col items-center';
countdownDiv.className = 'fixed left-1/2 bottom-16 -translate-x-1/2 text-4xl font-bold text-[#00FF00] z-40 drop-shadow-lg select-none';
wordCloudDiv.className = 'fixed left-0 top-0 w-full h-full pointer-events-none z-30';

// 定義 3x3 區塊（8格）座標範圍
const GRID_ROWS = 3;
const GRID_COLS = 3;
const gridRects = [];
function calcGridRects() {
    const W = window.innerWidth;
    const H = window.innerHeight;
    const cellW = W / GRID_COLS;
    const cellH = H / GRID_ROWS;
    gridRects.length = 0;
    for (let r = 0; r < GRID_ROWS; r++) {
        for (let c = 0; c < GRID_COLS; c++) {
            // 跳過中間格（1,1）
            if (r === 1 && c === 1) {
                gridRects.push(null);
                continue;
            }
            gridRects.push({
                x: c * cellW,
                y: r * cellH,
                w: cellW,
                h: cellH
            });
        }
    }
}
window.addEventListener('resize', calcGridRects);
calcGridRects();

// 關鍵字分配到 8 格，每格至少一個，剩下隨機分配
function assignKeywordsToGrids(wordList) {
    const gridWords = Array(8).fill(0).map(() => []);
    let idx = 0;
    // 先每格一個
    for (; idx < Math.min(wordList.length, 8); idx++) {
        gridWords[idx].push(wordList[idx]);
    }
    // 剩下的隨機分配
    for (; idx < wordList.length; idx++) {
        const randGrid = Math.floor(Math.random() * 8);
        gridWords[randGrid].push(wordList[idx]);
    }
    return gridWords;
}

// 倒數計時
function startCountdown() {
    countdownDiv.textContent = countdown;
    timer = setInterval(() => {
        countdown--;
        countdownDiv.textContent = countdown;
        if (countdown <= 0) {
            clearInterval(timer);
            showPendingOverlay('劇本產出中');
            generateScriptsAndRedirect();
        }
    }, 1000);
}

// 監聽 WebSocket 關鍵字
function listenWS() {
    wsListener = window.Echo.channel('keywords')
        .listen('NewKeywordSubmitted', (e) => {
            addKeyword(e.keyword);
        });
}

// 新增關鍵字
function addKeyword(word) {
    if (!window.wordFreq[word]) {
        window.wordList.push(word);
        window.wordFreq[word] = 1;
        // 統計目前每格已分配幾個字
        const gridCount = Array(8).fill(0);
        Object.values(window.wordMeta).forEach(meta => {
            if (meta.gridIdx !== undefined) gridCount[meta.gridIdx]++;
        });
        // 找出目前最少字的格子（如有多個隨機選一個）
        let min = Math.min(...gridCount);
        let candidates = [];
        gridCount.forEach((cnt, idx) => { if (cnt === min) candidates.push(idx); });
        const gridIdx = candidates[Math.floor(Math.random() * candidates.length)];
        const rect = gridRects[gridIdx < 4 ? gridIdx : gridIdx + 1];
        const padding = 30;
        const left = rect.x + padding + Math.random() * (rect.w - 200 - padding * 2);
        const top = rect.y + padding + Math.random() * (rect.h - 60 - padding * 2);
        const rotate = 0; // 固定為0度
        window.wordMeta[word] = { left, top, rotate, gridIdx };
        updateWordCloud(word);
    } else {
        window.wordFreq[word]++;
    }
}

// 好看的配色組
const colorPalette = [
    '#00FF00', // 螢光綠
    '#00BFFF', // 深天藍
    '#FF69B4', // 粉紅
    '#FFD700', // 金黃
    '#FF4500', // 橘紅
    '#7CFC00', // 草綠
    '#1E90FF', // 道奇藍
    '#FF6347', // 番茄紅
    '#8A2BE2', // 藍紫
    '#FF1493', // 深粉紅
    '#40E0D0', // 青綠
    '#FFA500', // 橙色
    '#ADFF2F', // 黃綠
    '#00CED1', // 深青
    '#C71585'  // 紫紅
];

// 重新渲染所有關鍵字
function updateWordCloud(newWord = null) {
    if (newWord) {
        // 只新增新關鍵字
        const meta = window.wordMeta[newWord];
        const displayText = newWord.slice(0, 8);
        let sizeClass = 'text-6xl';
        if (displayText.length >= 7) sizeClass = 'text-3xl';
        else if (displayText.length >= 5) sizeClass = 'text-4xl';
        else if (displayText.length >= 3) sizeClass = 'text-5xl';
        const span = document.createElement('span');
        span.textContent = displayText;
        if (!window.wordColor[newWord]) {
            window.wordColor[newWord] = colorPalette[Math.floor(Math.random() * colorPalette.length)];
        }
        const color = window.wordColor[newWord];
        span.className = `absolute pointer-events-none font-mono cloudword ${sizeClass}`;
        span.style.color = color;
        span.style.left = meta.left + 'px';
        span.style.top = meta.top + 'px';
        span.style.transform = `rotate(0deg)`;
        span.style.width = 'auto';
        span.style.whiteSpace = 'nowrap';
        wordCloudDiv.appendChild(span);
        setTimeout(() => {
            span.style.animation = 'breath 1.6s infinite alternate';
        }, 100);
    } else {
        // 初始化時才渲染全部
        wordCloudDiv.innerHTML = '';
        window.wordList.forEach(word => {
            if (document.querySelector(`.cloudword[data-word='${word}']`)) return;
            updateWordCloud(word);
        });
    }
}

// 呼吸動畫 keyframes
const style = document.createElement('style');
style.innerHTML = `
@keyframes breath {
    0% { transform: scale(1);}
    100% { transform: scale(1.25);}
}`;
document.head.appendChild(style);

// 顯示 pending 畫面
function showPending(status = 'pending') {
    let pendingDiv = document.getElementById('pendingScriptDiv');
    if (!pendingDiv) {
        pendingDiv = document.createElement('div');
        pendingDiv.id = 'pendingScriptDiv';
        pendingDiv.style.position = 'fixed';
        pendingDiv.style.left = '0';
        pendingDiv.style.top = '0';
        pendingDiv.style.width = '100vw';
        pendingDiv.style.height = '100vh';
        pendingDiv.style.background = '#000';
        pendingDiv.style.zIndex = '9999';
        pendingDiv.style.display = 'flex';
        pendingDiv.style.flexDirection = 'column';
        pendingDiv.style.justifyContent = 'center';
        pendingDiv.style.alignItems = 'center';
        pendingDiv.style.transition = 'opacity 0.5s';
        document.body.appendChild(pendingDiv);
    }
    pendingDiv.innerHTML = `<div style="color:#fff;font-size:3rem;font-weight:bold;letter-spacing:0.2em;">${status === 'pending' ? '劇本產出中' : '劇本產出完成'}</div>`;
}

function showPendingOverlay(text = '劇本產出中') {
    if (!pendingOverlay) {
        pendingOverlay = document.createElement('div');
        pendingOverlay.id = 'pendingOverlay';
        pendingOverlay.className = 'fixed inset-0 bg-black bg-opacity-100 flex flex-col items-center justify-center z-[1000]';
        pendingOverlay.innerHTML = `<div class="text-white text-5xl font-bold mb-8 animate-pulse">${text}</div>`;
        document.body.appendChild(pendingOverlay);
    } else {
        pendingOverlay.innerHTML = `<div class="text-white text-5xl font-bold mb-8 animate-pulse">${text}</div>`;
        pendingOverlay.style.display = 'flex';
    }
}

function hidePendingOverlay() {
    if (pendingOverlay) pendingOverlay.style.display = 'none';
}

// 產生劇本並導向新頁面
async function generateScriptsAndRedirect() {
    showPendingOverlay('劇本產出中');
    try {
        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        const res = await fetch('/api/generate-scripts?test=1', {
            method: 'POST',
            headers: token ? { 'X-CSRF-TOKEN': token } : {},
            credentials: 'same-origin'
        });
        if (!res.ok) throw new Error('API error');
        const data = await res.json();
        showPendingOverlay('劇本產出完成');
        setTimeout(() => {
            window.location.href = `/scripts/result?ids=${data.ids.join(',')}`;
        }, 1200);
    } catch (e) {
        showPendingOverlay('產生失敗，請重試');
        setTimeout(() => { hidePendingOverlay(); }, 2000);
    }
}

// 結束動畫
function endStage() {
    stageEnded = true;
    // QRCode、倒數、關鍵字全部淡出
    qrWrapper.style.transition = 'opacity 1.2s';
    qrWrapper.style.opacity = '0';
    countdownDiv.style.transition = 'opacity 1.2s';
    countdownDiv.style.opacity = '0';
    const words = document.querySelectorAll('.cloudword');
    words.forEach((el) => {
        el.style.transition = 'opacity 1.2s';
        el.style.opacity = '0';
    });
    setTimeout(() => {
        wordCloudDiv.innerHTML = '';
        // 顯示 pending 畫面並呼叫後端產生劇本
        showPending('pending');
        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        fetch('/api/generate-scripts', {
            method: 'POST',
            headers: token ? { 'X-CSRF-TOKEN': token } : {},
            credentials: 'same-origin'
        })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'done') {
                    showPending('done');
                    setTimeout(() => {
                        window.location.href = '/scripts';
                    }, 1200);
                } else {
                    showPending('error');
                }
            })
            .catch(() => showPending('error'));
    }, 1300);
}

window.addEventListener('DOMContentLoaded', () => {
    startCountdown();
    listenWS();
    // 初始化時顯示所有已存在的關鍵字
    updateWordCloud();
});
