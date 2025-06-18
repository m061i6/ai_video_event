import QRCode from 'qrcode';

window.wordList = [];
window.wordFreq = {};
window.wordMeta = {}; // 新增：記錄每個關鍵字的位置與角度
let countdown = 60;
let timer = null;
let wsListener = null;
let stageEnded = false;

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
            endStage();
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
    } else {
        window.wordFreq[word]++;
    }
    updateWordCloud();
}

// 重新渲染所有關鍵字
function updateWordCloud() {
    wordCloudDiv.innerHTML = '';
    window.wordList.forEach(word => {
        const meta = window.wordMeta[word];
        const displayText = word.slice(0, 8);
        let sizeClass = 'text-6xl';
        if (displayText.length >= 7) sizeClass = 'text-3xl';
        else if (displayText.length >= 5) sizeClass = 'text-4xl';
        else if (displayText.length >= 3) sizeClass = 'text-5xl';
        const span = document.createElement('span');
        span.textContent = displayText;
        span.className = `absolute pointer-events-none text-[#00FF00] font-mono cloudword ${sizeClass}`;
        span.style.left = meta.left + 'px';
        span.style.top = meta.top + 'px';
        span.style.transform = `rotate(0deg)`;
        span.style.width = 'auto';
        span.style.whiteSpace = 'nowrap';
        wordCloudDiv.appendChild(span);
        setTimeout(() => {
            span.style.animation = 'breath 1.6s infinite alternate';
        }, 100);
    });
}

// 呼吸動畫 keyframes
const style = document.createElement('style');
style.innerHTML = `
@keyframes breath {
    0% { transform: scale(1);}
    100% { transform: scale(1.25);}
}`;
document.head.appendChild(style);

// 結束動畫
function endStage() {
    stageEnded = true;
    qrWrapper.style.display = 'none';
    countdownDiv.style.display = 'none';
    if (wsListener) window.Echo.leave('keywords');
    const words = document.querySelectorAll('.cloudword');
    words.forEach((el, i) => {
        el.style.transition = 'all 1.2s cubic-bezier(0.87,0,0.13,1)';
        el.style.transform = 'scale(1.2) rotate(720deg)';
    });
    setTimeout(() => {
        words.forEach(el => {
            el.style.transition = 'all 1.2s cubic-bezier(0.87,0,0.13,1)';
            el.style.transform = 'translateY(-100vh) scale(0.3)';
            el.style.opacity = '0';
        });
        setTimeout(() => {
            wordCloudDiv.innerHTML = '';
            window.dispatchEvent(new Event('stage1Complete'));
        }, 1200);
    }, 1300);
}

window.addEventListener('DOMContentLoaded', () => {
    startCountdown();
    listenWS();
    // 初始化時顯示所有已存在的關鍵字
    updateWordCloud();
});
