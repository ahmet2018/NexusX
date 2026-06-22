<?php
session_start();
require_once 'config/database.php';

// Eğer oturum açılmamışsa login.php'ye gönder
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Kullanıcı adını oturumdan alıyoruz
$username = $_SESSION['username'] ?? 'Kullanıcı';
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Merkezi Kontrol Paneli | ULTRA</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800;900&display=swap" rel="stylesheet">
    <style>
        body {
            background-color: #05080f;
            color: white;
            font-family: 'Inter', sans-serif;
            overflow-x: hidden;
        }

        /* Kar Tanesi Efekti */
        .snowflake {
            position: fixed;
            top: -10px;
            color: white;
            font-size: 1em;
            user-select: none;
            z-index: 9999;
            pointer-events: none;
            animation-name: fall;
            animation-timing-function: linear;
        }
        @keyframes fall { to { transform: translateY(105vh); } }

        /* Kart Tasarımı */
        .glass-card {
            background: rgba(13, 17, 23, 0.8);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 24px;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            text-decoration: none;
            height: 100%;
        }

        .glass-card:hover {
            border-color: #3b82f6;
            background: rgba(20, 27, 38, 1);
            transform: translateY(-5px);
            box-shadow: 0 20px 40px -15px rgba(59, 130, 246, 0.4);
        }

        .nav-container {
            background: rgba(13, 17, 23, 0.6);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.08);
        }

        .icon-box {
            width: 70px;
            height: 70px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 18px;
            background: rgba(59, 130, 246, 0.1);
            margin-bottom: 1rem;
            transition: 0.4s;
        }

        .glass-card:hover .icon-box {
            background: rgba(59, 130, 246, 0.2);
            transform: scale(1.1);
        }

        .category-title {
            border-left: 4px solid #3b82f6;
            padding-left: 15px;
            margin-bottom: 25px;
            font-weight: 800;
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        /* Arama Kutusu Stili */
        .search-input {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: all 0.3s;
        }
        .search-input:focus {
            border-color: #3b82f6;
            background: rgba(59, 130, 246, 0.05);
            outline: none;
            box-shadow: 0 0 15px rgba(59, 130, 246, 0.1);
        }
    </style>
</head>
<body class="min-h-screen flex flex-col items-center p-6">

    <div class="w-full max-w-6xl nav-container rounded-2xl p-4 mb-12 flex justify-between items-center px-8 shadow-2xl">
        <div class="flex items-center gap-2 font-bold text-blue-500 italic shrink-0">
            <i class="fas fa-shield-alt text-xl animate-pulse"></i> ULTRA<span class="text-white">PANEL</span>
        </div>

        <div class="relative w-full max-w-xs mx-8">
            <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-gray-500 text-sm"></i>
            <input type="text" id="panelSearch" onkeyup="filterCards()" placeholder="Checker ara..." 
                   class="search-input w-full pl-11 pr-4 py-2 rounded-xl text-sm text-white placeholder-gray-500">
        </div>

        <div class="flex items-center gap-6 text-sm shrink-0">
            <div class="flex items-center gap-2 text-gray-300">
                <i class="fas fa-circle text-[8px] text-green-500 animate-ping"></i>
                <span class="font-semibold"><?php echo htmlspecialchars($username); ?></span>
            </div>
            <a href="login.php" class="bg-red-900/20 text-red-500 border border-red-900/40 px-4 py-1.5 rounded-lg flex items-center gap-2 hover:bg-red-500 hover:text-white transition-all">
                <i class="fas fa-sign-out-alt"></i> Çıkış Yap
            </a>
        </div>
    </div>

    <div class="text-center mb-16">
        <h1 class="text-6xl font-black mb-4 tracking-tight bg-gradient-to-b from-white to-gray-500 bg-clip-text text-transparent">
            Merkezi Kontrol Paneli
        </h1>
        <p class="text-gray-400 text-lg font-medium">Kategorize edilmiş modülleri yönetin</p>
    </div>



    
    <div id="cardContainer" class="w-full max-w-6xl px-4 pb-20 space-y-16">
         
        <section class="category-group">
            <h2 class="category-title text-2xl text-blue-400">Oyun Sistemleri</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <a href="OSM.php" class="glass-card p-8 group search-target">
                    <div class="icon-box">
                        <i class="fas fa-futbol text-3xl text-blue-500 group-hover:rotate-90 transition-transform duration-500"></i>
                    </div>
                    <h2 class="text-xl font-bold mb-2">OSM Checker</h2>
                    <p class="text-gray-500 text-xs">Online Soccer Manager hesap analiz sistemi.</p>
                </a>

                <a href="steam.php" class="glass-card p-8 group search-target">
                    <div class="icon-box">
                        <i class="fas fa-futbol text-3xl text-blue-500 group-hover:rotate-90 transition-transform duration-500"></i>
                    </div>
                    <h2 class="text-xl font-bold mb-2">steam Checker</h2>
                    <p class="text-gray-500 text-xs">steam hesap analiz sistemi.</p>
                </a>



                <a href="hotmail.php" class="glass-card p-8 group search-target">
                    <div class="icon-box">
                        <i class="fas fa-futbol text-3xl text-blue-500 group-hover:rotate-90 transition-transform duration-500"></i>
                    </div>
                    <h2 class="text-xl font-bold mb-2">hotmail Checker</h2>
                    <p class="text-gray-500 text-xs">hotmail.php hesap analiz sistemi.</p>
                </a>
         

                <a href="CarParking.php" class="glass-card p-8 group search-target">
                    <div class="icon-box">
                        <i class="fas fa-car-side text-3xl text-blue-500 group-hover:scale-110"></i>
                    </div>
                    <h2 class="text-xl font-bold mb-2">CarParking</h2>
                    <p class="text-gray-500 text-xs">CPM hesap envanter ve araç kontrol modülü.</p>
                </a>
            </div>
        </section>

        <section class="category-group">
            <h2 class="category-title text-2xl text-purple-400">Hesap Satış & Doğrulama</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <a href="hesappin.php" class="glass-card p-8 group search-target">
                    <div class="icon-box">
                        <i class="fas fa-shopping-cart text-3xl text-blue-500 group-hover:scale-110"></i>
                    </div>
                    <h2 class="text-xl font-bold mb-2">Hesappin Checker</h2>
                    <p class="text-gray-500 text-xs">Hesappin platformu hızlı hesap doğrulama.</p>
                </a>

                <a href="oyundinar.php" class="glass-card p-8 group search-target">
                    <div class="icon-box">
                        <i class="fas fa-coins text-3xl text-blue-500 group-hover:scale-110"></i>
                    </div>
                    <h2 class="text-xl font-bold mb-2">oyundinar Checker</h2>
                    <p class="text-gray-500 text-xs">Oyundinar platformu otomatik kontrol.</p>
                </a>

                <a href="s2g.php" class="glass-card p-8 group search-target">
                    <div class="icon-box">
                        <i class="fas fa-exchange-alt text-3xl text-blue-500 group-hover:scale-110"></i>
                    </div>
                    <h2 class="text-xl font-bold mb-2">s2g checker</h2>
                    <p class="text-gray-500 text-xs">s2g platformu hızlı hesap doğrulama.</p>
                </a>
            </div>
        </section>

        <section class="category-group">
            <h2 class="category-title text-2xl text-red-400">Hile & Araç Yazılımları</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <a href="EzGloba.php" class="glass-card p-8 group search-target">
                    <div class="icon-box">
                        <i class="fas fa-crosshairs text-3xl text-blue-500 group-hover:scale-110"></i>
                    </div>
                    <h2 class="text-xl font-bold mb-2">EzGloba Checker</h2>
                    <p class="text-gray-500 text-xs">EzGloba hile platformu hesap kontrolü.</p>
                </a>

                <a href="tradeproxy.php" class="glass-card p-8 group search-target">
                    <div class="icon-box">
                        <i class="fas fa-network-wired text-3xl text-blue-500 group-hover:scale-110"></i>
                    </div>
                    <h2 class="text-xl font-bold mb-2">tradeproxy checker</h2>
                    <p class="text-gray-500 text-xs">TradeProxy platformu proxy kontrol sistemi.</p>
                </a>

                <a href="mullvad.php" class="glass-card p-8 group search-target">
                    <div class="icon-box">
                        <i class="fas fa-network-wired text-3xl text-blue-500 group-hover:scale-110"></i>
                    </div>
                    <h2 class="text-xl font-bold mb-2">mullvad checker</h2>
                    <p class="text-gray-500 text-xs">Mullvad VPN hesap doğrulama sistemi.</p>
                </a>

                <a href="wolf.php" class="glass-card p-8 group search-target">
                    <div class="icon-box">
                        <i class="fas fa-paw text-3xl text-blue-500 group-hover:scale-110"></i>
                    </div>
                    <h2 class="text-xl font-bold mb-2">wolf checker</h2>
                    <p class="text-gray-500 text-xs">Wolf hile platformu hesap kontrolü.</p>
                </a>

                <a href="fenix.php" class="glass-card p-8 group search-target">
                    <div class="icon-box">
                        <i class="fas fa-fire text-3xl text-blue-500 group-hover:scale-110"></i>
                    </div>
                    <h2 class="text-xl font-bold mb-2">fenix checker</h2>
                    <p class="text-gray-500 text-xs">Fenix hile platformu hesap kontrolü.</p>
                </a>
            </div>
        </section>

        <section class="category-group">
            <h2 class="category-title text-2xl text-green-400">Medya & Diğer</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <a href="capcut.php" class="glass-card p-8 group search-target">
                    <div class="icon-box">
                        <i class="fas fa-video text-3xl text-blue-500 group-hover:scale-110"></i>
                    </div>
                    <h2 class="text-xl font-bold mb-2">CAPCUT CHECKER</h2>
                    <p class="text-gray-500 text-xs">Video düzenleme platformu hesap kontrolü.</p>
                </a>

                <a href="tabi.php" class="glass-card p-8 group search-target">
                    <div class="icon-box">
                        <i class="fas fa-tv text-3xl text-blue-500 group-hover:scale-110"></i>
                    </div>
                    <h2 class="text-xl font-bold mb-2">Tabi Checker</h2>
                    <p class="text-gray-500 text-xs">Tabi platformu hızlı hesap doğrulama.</p>
                </a>

                <a href="anizium.php" class="glass-card p-8 group search-target">
                    <div class="icon-box">
                        <i class="fas fa-clapperboard text-3xl text-blue-500 group-hover:scale-110"></i>
                    </div>
                    <h2 class="text-xl font-bold mb-2">anizium checker</h2>
                    <p class="text-gray-500 text-xs">Anizium anime platformu hesap kontrolü.</p>
                </a>

                <a href="domi.php" class="glass-card p-8 group search-target">
                    <div class="icon-box">
                        <i class="fas fa-cube text-3xl text-blue-500 group-hover:scale-110"></i>
                    </div>
                    <h2 class="text-xl font-bold mb-2">domi checker</h2>
                    <p class="text-gray-500 text-xs">Domi platformu hızlı hesap doğrulama.</p>
                </a>
            </div>
        </section>

    </div>

    <script>
        function filterCards() {
            const input = document.getElementById('panelSearch').value.toLowerCase();
            const cards = document.getElementsByClassName('search-target');
            const sections = document.getElementsByClassName('category-group');

            // Kartları filtrele
            for (let i = 0; i < cards.length; i++) {
                const title = cards[i].getElementsByTagName('h2')[0].innerText.toLowerCase();
                const desc = cards[i].getElementsByTagName('p')[0].innerText.toLowerCase();
                
                if (title.includes(input) || desc.includes(input)) {
                    cards[i].parentElement.parentElement.style.display = "block"; // Grubu göster
                    cards[i].style.display = "flex";
                } else {
                    cards[i].style.display = "none";
                }
            }

            // Eğer bir kategoride hiç kart kalmadıysa o başlığı gizle
            for (let s = 0; s < sections.length; s++) {
                const visibleCards = sections[s].querySelectorAll('.search-target[style="display: flex;"]');
                if (visibleCards.length === 0 && input !== "") {
                    sections[s].style.display = "none";
                } else {
                    sections[s].style.display = "block";
                }
            }
        }

        // Kar Tanesi Efekti
        function createSnowflake() {
            const snowflake = document.createElement('div');
            snowflake.classList.add('snowflake');
            snowflake.innerHTML = '❄';
            snowflake.style.left = Math.random() * 100 + 'vw';
            snowflake.style.opacity = Math.random() * 0.7;
            snowflake.style.fontSize = Math.random() * 8 + 8 + 'px';
            snowflake.style.animationDuration = Math.random() * 5 + 5 + 's';
            document.body.appendChild(snowflake);
            setTimeout(() => { snowflake.remove(); }, 10000);
        }
        setInterval(createSnowflake, 400);
    </script>

</body>
</html>