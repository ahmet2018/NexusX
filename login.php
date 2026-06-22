<?php
session_start();
require_once 'config/database.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'Kullanıcı adı ve şifre gerekli';
    } else {
        try {

            $stmt = $db->prepare("SELECT id, username, password, email, is_admin FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['password'])) {

                $_SESSION['user_id']  = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['email']    = $user['email'];
                
                
                $_SESSION['is_admin'] = (bool)$user['is_admin'];

                header('Location: index.php');
                exit;
            } else {
                $error = 'Geçersiz kullanıcı adı veya şifre';
            }
        } catch (PDOException $e) {
            $error = 'Giriş sırasında sistem hatası oluştu';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LogFinder Pro - Ultimate Cyber Edition</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;700&family=Plus+Jakarta+Sans:wght@400;600;800&display=swap" rel="stylesheet">

    <style>
        :root {
            --bg: #030305;
            --card-bg: rgba(10,10,12,0.85);
            --accent: #00f2ff;
            --accent-hover: #00d1db;
            --accent-glow: rgba(0,242,255,0.4);
            --text: #e2e8f0;
            --border: rgba(0,242,255,0.2);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Plus Jakarta Sans', sans-serif;
        }

        body {
            background-color: var(--bg);
            color: var(--text);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            position: relative;
        }

        #cyber-canvas {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 1;
        }

        .login-card {
            width: 100%;
            max-width: 420px;
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 28px;
            padding: 40px;
            box-shadow: 0 0 50px rgba(0,242,255,0.1);
            position: relative;
            z-index: 10;
            backdrop-filter: blur(15px);
            animation: cardAppear 1s ease-out;
        }

        @keyframes cardAppear {
            from { opacity: 0; transform: translateY(20px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        .header { text-align: center; margin-bottom: 30px; }

        .logo-icon {
            font-size: 50px;
            color: var(--accent);
            margin-bottom: 15px;
            filter: drop-shadow(0 0 20px var(--accent));
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%   { transform: scale(1); opacity: 1; }
            50%  { transform: scale(1.05); opacity: 0.8; }
            100% { transform: scale(1); opacity: 1; }
        }

        h1 { font-size: 28px; font-weight: 800; letter-spacing: -1px; }

        .error-box {
            background: rgba(255,71,71,0.15);
            border: 1px solid #ff4747;
            color: #ff4747;
            padding: 15px;
            border-radius: 12px;
            font-size: 14px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: shake 0.5s ease-in-out;
        }

        @keyframes shake {
            0%,100% { transform: translateX(0); }
            25%     { transform: translateX(-5px); }
            75%     { transform: translateX(5px); }
        }

        .form-group { margin-bottom: 20px; position: relative; }

        label {
            display: block;
            font-size: 11px;
            font-weight: 800;
            color: var(--accent);
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 2px;
        }

        input {
            width: 100%;
            background: rgba(0,0,0,0.5);
            border: 1px solid var(--border);
            padding: 16px;
            border-radius: 14px;
            color: #fff;
            font-size: 15px;
            transition: all 0.4s cubic-bezier(0.175,0.885,0.32,1.275);
            position: relative;
            z-index: 2;
        }

        input:hover, input:focus {
            outline: none;
            border-color: var(--accent);
            background: rgba(0,242,255,0.08);
            box-shadow: 0 0 15px var(--border), inset 0 0 10px rgba(0,242,255,0.1);
            transform: translateY(-2px);
        }

        .btn-submit {
            width: 100%;
            padding: 16px;
            background: var(--accent);
            color: #000;
            border: none;
            border-radius: 14px;
            font-weight: 800;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            box-shadow: 0 0 20px rgba(0,242,255,0.3);
            position: relative;
            overflow: hidden;
        }

        .btn-submit:hover {
            letter-spacing: 1px;
            background: var(--accent-hover);
            box-shadow: 0 0 25px var(--accent), 0 0 45px var(--accent-glow);
            transform: scale(1.02);
        }

        .btn-submit:active {
            transform: scale(0.98);
            box-shadow: 0 0 10px var(--accent);
        }

        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 14px;
            color: #64748b;
        }

        .footer a {
            color: var(--accent);
            text-decoration: none;
            font-weight: 700;
            transition: 0.3s;
        }

        .footer a:hover { text-shadow: 0 0 10px var(--accent); }

        .mono { font-family: 'JetBrains Mono', monospace; font-size: 12px; }
    </style>
</head>
<body>

    <canvas id="cyber-canvas"></canvas>

    <div class="login-card">
        <div class="header">
            <div class="logo-icon"><i class="fas fa-shield-halved"></i></div>
            <h1>LogFinder <span style="color:var(--accent)"></span></h1>
            <p class="mono" style="opacity: 0.5;">SECURE ACCESS GATEWAY</p>
        </div>

        <?php if ($error): ?>
            <div class="error-box">
                <i class="fas fa-exclamation-triangle"></i>
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label>User</label>
                <input type="text" id="userInput" name="username" placeholder="Username" required autofocus>
            </div>

            <div class="form-group">
                <label>Password</label>
                <input type="password" id="passInput" name="password" placeholder="••••••••" required>
            </div>

            <button type="submit" class="btn-submit" id="authBtn">
                AUTHENTICATE <i id="lockIcon" class="fas fa-lock"></i>
            </button>
        </form>

        <div class="footer">
            New here? <a href="register.php">Create Account</a>
        </div>
    </div>

    <script>
        
        const authBtn = document.getElementById('authBtn');
        const lockIcon = document.getElementById('lockIcon');
        const userInput = document.getElementById('userInput');
        const passInput = document.getElementById('passInput');

        authBtn.addEventListener('mouseenter', () => {
            if (userInput.value.trim() !== "" && passInput.value.trim() !== "") {
                lockIcon.classList.remove('fa-lock');
                lockIcon.classList.add('fa-lock-open');
            }
        });

        authBtn.addEventListener('mouseleave', () => {
            lockIcon.classList.remove('fa-lock-open');
            lockIcon.classList.add('fa-lock');
        });

        const canvas = document.getElementById('cyber-canvas');
        const ctx = canvas.getContext('2d');
        let particles = [];
        let snowParticles = [];
        const particleCount = 225;
        const snowCount = 100;
        const connectionDistance = 150;
        const mouse = { x: null, y: null, radius: 150 };

        window.addEventListener('mousemove', (e) => {
            mouse.x = e.clientX;
            mouse.y = e.clientY;
        });

        class Particle {
            constructor() { this.reset(); }
            reset() {
                this.x = Math.random() * canvas.width;
                this.y = Math.random() * canvas.height;
                this.vx = (Math.random() - 0.5) * 0.6;
                this.vy = (Math.random() - 0.5) * 0.6;
                this.radius = Math.random() * 2 + 1;
            }
            update() {
                this.x += this.vx;
                this.y += this.vy;
                if (this.x < 0 || this.x > canvas.width) this.vx *= -1;
                if (this.y < 0 || this.y > canvas.height) this.vy *= -1;
                let dx = mouse.x - this.x;
                let dy = mouse.y - this.y;
                let distance = Math.sqrt(dx * dx + dy * dy);
                if (distance < mouse.radius) {
                    this.x -= dx * 0.01;
                    this.y -= dy * 0.01;
                }
            }
            draw() {
                ctx.beginPath();
                ctx.arc(this.x, this.y, this.radius, 0, Math.PI * 2);
                ctx.fillStyle = 'rgba(0, 242, 255, 0.5)';
                ctx.fill();
            }
        }

        class Snow {
            constructor() { this.init(); }
            init() {
                this.x = Math.random() * canvas.width;
                this.y = Math.random() * -canvas.height;
                this.vy = Math.random() * 2 + 1;
                this.vx = (Math.random() - 0.5) * 0.5;
                this.radius = Math.random() * 1.5 + 0.5;
                this.alpha = Math.random() * 0.5 + 0.2;
            }
            update() {
                this.y += this.vy;
                this.x += this.vx;
                if (this.y > canvas.height) {
                    this.y = -10;
                    this.x = Math.random() * canvas.width;
                }
            }
            draw() {
                ctx.beginPath();
                ctx.arc(this.x, this.y, this.radius, 0, Math.PI * 2);
                ctx.fillStyle = `rgba(255, 255, 255, ${this.alpha})`;
                ctx.fill();
            }
        }

        function init() {
            canvas.width = window.innerWidth;
            canvas.height = window.innerHeight;
            particles = [];
            snowParticles = [];
            for (let i = 0; i < particleCount; i++) particles.push(new Particle());
            for (let i = 0; i < snowCount; i++) snowParticles.push(new Snow());
        }

        function drawLines() {
            for (let i = 0; i < particles.length; i++) {
                for (let j = i + 1; j < particles.length; j++) {
                    const dx = particles[i].x - particles[j].x;
                    const dy = particles[i].y - particles[j].y;
                    const distance = Math.sqrt(dx * dx + dy * dy);
                    if (distance < connectionDistance) {
                        ctx.beginPath();
                        ctx.strokeStyle = `rgba(0, 242, 255, ${1 - distance / connectionDistance - 0.3})`;
                        ctx.lineWidth = 0.8;
                        ctx.moveTo(particles[i].x, particles[i].y);
                        ctx.lineTo(particles[j].x, particles[j].y);
                        ctx.stroke();
                        ctx.closePath();
                    }
                }
            }
        }

        function animate() {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            ctx.strokeStyle = 'rgba(0, 242, 255, 0.03)';
            ctx.lineWidth = 1;
            for (let i = 0; i < canvas.width; i += 50) {
                ctx.beginPath(); ctx.moveTo(i, 0); ctx.lineTo(i, canvas.height); ctx.stroke();
            }
            for (let i = 0; i < canvas.height; i += 50) {
                ctx.beginPath(); ctx.moveTo(0, i); ctx.lineTo(canvas.width, i); ctx.stroke();
            }
            snowParticles.forEach(s => { s.update(); s.draw(); });
            particles.forEach(p => { p.update(); p.draw(); });
            drawLines();
            requestAnimationFrame(animate);
        }

        window.addEventListener('resize', init);
        init();
        animate();
    </script>
</body>
</html>