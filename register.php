<?php
session_start();
require_once 'config/database.php';

$error = '';
$success = '';


$now = time();
$rate_limited = false;

if (isset($_SESSION['last_reg_attempt'])) {
    $time_passed = $now - $_SESSION['last_reg_attempt'];
    $attempt_count = $_SESSION['reg_attempt_count'] ?? 0;
    $wait_time = ($attempt_count >= 2) ? 60 : 10; 

    if ($time_passed < $wait_time) {
        $remaining = $wait_time - $time_passed;
        $error = "Çok hızlı işlem yapıyorsunuz. Lütfen " . $remaining . " saniye bekleyin.";
        $rate_limited = true;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$rate_limited) {
    
    $username = trim($_POST['reg_username'] ?? '');
    $email    = trim($_POST['reg_email'] ?? '');
    $password = $_POST['reg_password'] ?? '';
    $password_confirm = $_POST['reg_password_confirm'] ?? '';
    
    
    $is_valid_gmail = preg_match('/^[a-zA-Z0-9]+@gmail\.com$/', $email);

    if (empty($username) || empty($password)) {
        $error = 'Kullanıcı adı ve şifre zorunludur';
    } elseif (!$is_valid_gmail) {
        $error = 'Sadece standart @gmail.com adresleri kabul edilir (Nokta veya + içeremez)';
    } elseif ($password !== $password_confirm) {
        $error = 'Şifreler eşleşmiyor';
    } elseif (strlen($password) < 6) {
        $error = 'Şifre en az 6 karakter olmalıdır';
    } else {

        $_SESSION['last_reg_attempt'] = $now;
        $_SESSION['reg_attempt_count'] = ($_SESSION['reg_attempt_count'] ?? 0) + 1;

        try {
            $stmt = $db->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $email]);
            
            if ($stmt->fetch()) {
                $error = 'Bu kullanıcı adı veya e-posta zaten kullanımda';
            } else {
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $db->prepare("INSERT INTO users (username, email, password, is_admin) VALUES (?, ?, ?, 0)");
                $stmt->execute([$username, $email, $hashed]);
                
                // Başarılı kayıtta her şeyi sıfırla
                unset($_SESSION['reg_attempt_count']);
                unset($_SESSION['last_reg_attempt']);

                $_SESSION['user_id']  = $db->lastInsertId();
                $_SESSION['username'] = $username;
                $_SESSION['is_admin'] = false;
                
                header('Location: index.php');
                exit;
            }
        } catch (PDOException $e) {
            $error = 'Sistem hatası: Kayıt şu an gerçekleştirilemiyor.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LogFinder Pro - Account Creation</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;700&family=Plus+Jakarta+Sans:wght@400;600;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #000000;
            --card-bg: rgba(5, 5, 8, 0.9);
            --accent: #00f2ff;
            --accent-glow: rgba(0, 242, 255, 0.4);
            --text: #e2e8f0;
            --border: rgba(0, 242, 255, 0.25);
            --error: #ff4747;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Plus Jakarta Sans', sans-serif; }
        body { background-color: var(--bg); color: var(--text); height: 100vh; display: flex; align-items: center; justify-content: center; overflow: hidden; position: relative; }
        #cyber-canvas { position: absolute; top: 0; left: 0; width: 100%; height: 100%; z-index: 1; }
        .auth-card { width: 90%; max-width: 440px; background: var(--card-bg); border: 1px solid var(--border); border-radius: 24px; padding: 35px; z-index: 10; backdrop-filter: blur(15px); box-shadow: 0 0 50px rgba(0, 242, 255, 0.1); animation: slideUp 0.6s ease-out; }
        @keyframes slideUp { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }
        .header { text-align: center; margin-bottom: 25px; }
        .logo-icon { font-size: 45px; color: var(--accent); filter: drop-shadow(0 0 15px var(--accent)); margin-bottom: 10px; }
        h2 { font-size: 24px; font-weight: 800; letter-spacing: -0.5px; }
        .mono { font-family: 'JetBrains Mono', monospace; font-size: 11px; color: var(--accent); opacity: 0.7; letter-spacing: 2px; margin-top: 5px;}
        .alert { background: rgba(255, 71, 71, 0.1); border: 1px solid var(--error); color: var(--error); padding: 12px; border-radius: 12px; font-size: 13px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }
        .form-group { margin-bottom: 18px; }
        label { display: block; font-size: 10px; font-weight: 700; color: var(--accent); text-transform: uppercase; letter-spacing: 1.5px; margin-bottom: 7px; margin-left: 5px; }
        .input-group { position: relative; }
        .input-group i { position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: rgba(255, 255, 255, 0.3); font-size: 14px; }
        input { width: 100%; background: rgba(255, 255, 255, 0.03); border: 1px solid var(--border); padding: 14px 14px 14px 42px; border-radius: 12px; color: #fff; font-size: 14px; transition: all 0.3s ease; }
        input:focus { outline: none; border-color: var(--accent); background: rgba(0, 242, 255, 0.05); box-shadow: 0 0 15px var(--accent-glow); }
        .btn-register { width: 100%; padding: 16px; background: var(--accent); color: #000; border: none; border-radius: 12px; font-weight: 800; font-size: 15px; cursor: pointer; transition: 0.3s; display: flex; align-items: center; justify-content: center; gap: 10px; box-shadow: 0 0 20px rgba(0, 242, 255, 0.2); }
        .btn-register:hover { transform: translateY(-2px); box-shadow: 0 0 30px var(--accent); background: #fff; }
        .footer { margin-top: 25px; text-align: center; font-size: 13px; color: #64748b; }
        .footer a { color: var(--accent); text-decoration: none; font-weight: 700; }
        .footer a:hover { text-shadow: 0 0 10px var(--accent); }
    </style>
</head>
<body>

    <canvas id="cyber-canvas"></canvas>

    <div class="auth-card">
        <div class="header">
            <div class="logo-icon"><i class="fas fa-user-shield"></i></div>
            <h2>Yeni Üyelik</h2>
            <div class="mono">NEW_ENTITY_REGISTRATION</div>
        </div>

        <?php if ($error): ?>
            <div class="alert">
                <i class="fas fa-triangle-exclamation"></i>
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label>Kullanıcı Adı</label>
                <div class="input-group">
                    <i class="fas fa-user"></i>
                    <input type="text" name="reg_username" placeholder="Sistem Kimliği" required value="<?= htmlspecialchars($username ?? '') ?>">
                </div>
            </div>

            <div class="form-group">
                <label>Email (Sadece Gmail)</label>
                <div class="input-group">
                    <i class="fas fa-envelope"></i>
                    <input type="email" name="reg_email" placeholder="ornek@gmail.com" required value="<?= htmlspecialchars($email ?? '') ?>">
                </div>
            </div>

            <div class="form-group">
                <label>Şifre</label>
                <div class="input-group">
                    <i class="fas fa-key"></i>
                    <input type="password" name="reg_password" placeholder="••••••••" required>
                </div>
            </div>

            <div class="form-group">
                <label>Şifre Doğrulama</label>
                <div class="input-group">
                    <i class="fas fa-check-double"></i>
                    <input type="password" name="reg_password_confirm" placeholder="••••••••" required>
                </div>
            </div>

            <button type="submit" class="btn-register" <?= $rate_limited ? 'disabled style="opacity:0.5; cursor:not-allowed;"' : '' ?>>
                <?= $rate_limited ? 'BEKLEYİN...' : 'KAYDI TAMAMLA' ?> <i class="fas fa-arrow-right"></i>
            </button>
        </form>

        <div class="footer">
            Zaten yetkiniz var mı? <a href="login.php">Giriş Yap</a>
        </div>
    </div>

    <script>
        const canvas = document.getElementById('cyber-canvas');
        const ctx = canvas.getContext('2d');
        let particles = [];
        
        function init() {
            canvas.width = window.innerWidth;
            canvas.height = window.innerHeight;
            particles = [];
            for (let i = 0; i < 120; i++) {
                particles.push({
                    x: Math.random() * canvas.width,
                    y: Math.random() * canvas.height,
                    vx: (Math.random() - 0.5) * 0.4,
                    vy: (Math.random() - 0.5) * 0.4,
                    size: Math.random() * 1.5
                });
            }
        }

        function draw() {
            ctx.fillStyle = "#000000";
            ctx.fillRect(0, 0, canvas.width, canvas.height);
            ctx.strokeStyle = "rgba(0, 242, 255, 0.03)";
            for(let i=0; i<canvas.width; i+=50) {
                ctx.beginPath(); ctx.moveTo(i, 0); ctx.lineTo(i, canvas.height); ctx.stroke();
            }
            for(let i=0; i<canvas.height; i+=50) {
                ctx.beginPath(); ctx.moveTo(0, i); ctx.lineTo(canvas.width, i); ctx.stroke();
            }
            particles.forEach(p => {
                p.x += p.vx; p.y += p.vy;
                if (p.x < 0 || p.x > canvas.width) p.vx *= -1;
                if (p.y < 0 || p.y > canvas.height) p.vy *= -1;
                ctx.fillStyle = "rgba(0, 242, 255, 0.4)";
                ctx.beginPath(); ctx.arc(p.x, p.y, p.size, 0, Math.PI * 2); ctx.fill();
            });
            for (let i = 0; i < particles.length; i++) {
                for (let j = i + 1; j < particles.length; j++) {
                    let dx = particles[i].x - particles[j].x;
                    let dy = particles[i].y - particles[j].y;
                    let dist = Math.sqrt(dx*dx + dy*dy);
                    if (dist < 120) {
                        ctx.strokeStyle = `rgba(0, 242, 255, ${1 - dist/120 - 0.7})`;
                        ctx.beginPath(); ctx.moveTo(particles[i].x, particles[i].y);
                        ctx.lineTo(particles[j].x, particles[j].y); ctx.stroke();
                    }
                }
            }
            requestAnimationFrame(draw);
        }
        window.addEventListener('resize', init);
        init();
        draw();

        
        if ( window.history.replaceState ) {
            window.history.replaceState( null, null, window.location.href );
        }
    </script>
</body>
</html>