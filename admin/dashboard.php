<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header('Location: index.php');
    exit;
}

if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: index.php');
    exit;
}

$recentActivities = [];
$totalUsers = 0;
$totalAdmins = 0;
$newUsers = 0;

try {
    $stmt = $db->query("SELECT COUNT(*) FROM users");
    $totalUsers = $stmt->fetchColumn();

    $stmt = $db->query("SELECT COUNT(*) FROM users WHERE is_admin = 1");
    $totalAdmins = $stmt->fetchColumn();

    $stmt = $db->query("SELECT COUNT(*) FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)");
    $newUsers = $stmt->fetchColumn();

    // NOT: Veritabanında 'ip_address' yoksa hata almamak için burayı 'id' olarak güncelledim.
    // Eğer veritabanına IP sütunu eklersen burayı tekrar 'ip_address' yapabilirsin.
    $stmt = $db->query("SELECT username, email, id as ip_placeholder, created_at FROM users ORDER BY created_at DESC LIMIT 5");
    $recentActivities = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $db->query("SELECT id, username, email, is_admin, created_at FROM users ORDER BY created_at DESC");
    $allUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $error = "Veritabanı hatası: " . $e->getMessage();
}

$current_page = 'dashboard';
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - LogFinder Pro</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --bg-dark: #090b10;
            --sidebar-bg: #0c0f16;
            --card-bg: #11141d;
            --accent-purple: #a855f7;
            --accent-blue: #3b82f6;
            --text-main: #e2e8f0;
            --text-muted: #94a3b8;
            --border-color: #1e293b;
        }

        body {
            margin: 0;
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background-color: var(--bg-dark);
            color: var(--text-main);
            overflow-x: hidden;
        }

        .dashboard-layout { display: flex; min-height: 100vh; }

        .sidebar {
            width: 260px;
            background-color: var(--sidebar-bg);
            border-right: 1px solid var(--border-color);
            display: flex;
            flex-direction: column;
            position: fixed;
            height: 100vh;
        }

        .sidebar-header { padding: 2rem 1.5rem; border-bottom: 1px solid var(--border-color); }

        .sidebar-logo {
            display: flex; align-items: center; gap: 10px; color: var(--accent-blue);
            font-size: 1.2rem; font-weight: bold; text-transform: uppercase; letter-spacing: 1px;
        }

        .nav-menu { list-style: none; padding: 1rem; margin: 0; }
        .nav-item { margin-bottom: 0.5rem; }
        .nav-link {
            display: flex; align-items: center; padding: 0.8rem 1rem; color: var(--text-muted);
            text-decoration: none; border-radius: 8px; transition: all 0.3s;
        }
        .nav-link:hover, .nav-link.active { background: rgba(59, 130, 246, 0.1); color: var(--accent-blue); }
        .nav-link.active { border-left: 3px solid var(--accent-blue); }

        .main-content { flex: 1; margin-left: 260px; padding: 2rem; }

        .content-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; }
        .page-title {
            font-size: 2rem; font-weight: 800; background: linear-gradient(to right, #fff, #94a3b8);
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
        }

        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1.5rem; margin-bottom: 2rem; }
        .stat-card { background: var(--card-bg); padding: 1.5rem; border-radius: 15px; border: 1px solid var(--border-color); transition: 0.3s; }
        .stat-card:hover { transform: translateY(-5px); border-color: var(--accent-purple); }
        .stat-value { font-size: 1.8rem; font-weight: 700; margin-top: 0.5rem; }

        .card { background: var(--card-bg); border-radius: 15px; border: 1px solid var(--border-color); overflow: hidden; }
        .card-header { padding: 1.2rem; background: rgba(255,255,255,0.02); border-bottom: 1px solid var(--border-color); }

        .table { width: 100%; border-collapse: collapse; }
        .table th { text-align: left; padding: 1rem; background: rgba(0,0,0,0.2); color: var(--text-muted); font-size: 0.85rem; }
        .table td { padding: 1rem; border-bottom: 1px solid var(--border-color); font-size: 0.9rem; }

        .badge { padding: 4px 10px; border-radius: 20px; font-size: 0.75rem; background: rgba(16, 185, 129, 0.1); color: #10b981; }
        .ip-badge { font-family: monospace; background: rgba(59, 130, 246, 0.1); color: var(--accent-blue); padding: 2px 6px; border-radius: 4px; }
        
        .alert-error { background: rgba(239, 68, 68, 0.1); border: 1px solid #ef4444; color: #ef4444; padding: 1rem; border-radius: 10px; margin-bottom: 1.5rem; }
        
        .btn { padding: 0.7rem 1.2rem; border-radius: 8px; text-decoration: none; font-weight: 600; display: inline-flex; align-items: center; gap: 8px; transition: 0.3s; border: none; cursor: pointer; color: white; background: var(--accent-blue); }
    </style>
</head>
<body>
    <div class="dashboard-layout">
        <div class="sidebar">
            <div class="sidebar-header">
                <div class="sidebar-logo"><i class="fas fa-terminal"></i> <span>ULTRA PANEL</span></div>
            </div>
            <ul class="nav-menu">
                <li class="nav-item"><a href="dashboard.php" class="nav-link active"><i class="fas fa-th-large"></i> <span>Dashboard</span></a></li>
                <li class="nav-item">
                    <a href="users.php" class="nav-link active">
                        <i class="fas fa-user-shield"></i>
                        <span>Kullanıcılar</span>
                    </a>
                </li>
                <li class="nav-item"><a href="?logout=1" class="nav-link" style="color: #ef4444;"><i class="fas fa-power-off"></i> <span>Güvenli Çıkış</span></a></li>
            </ul>
        </div>
        
        <div class="main-content">
            <div class="content-header">
                <h1 class="page-title">Kontrol Paneli</h1>
                <div class="user-info" style="background: var(--card-bg); padding: 10px; border-radius: 8px; border: 1px solid var(--border-color);">
                    <i class="fas fa-circle" style="color: #10b981; font-size: 0.7rem;"></i>
                    <span><b><?php echo htmlspecialchars($_SESSION['username'] ?? 'Admin'); ?></b></span>
                </div>
            </div>
            
            <?php if (isset($error)): ?>
                <div class="alert-error"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
            <?php endif; ?>

            <div class="stats-grid">
                <div class="stat-card">
                    <span style="color: var(--text-muted); font-size: 0.8rem;">TOPLAM KULLANICI</span>
                    <div class="stat-value"><?php echo $totalUsers; ?></div>
                </div>
                <div class="stat-card">
                    <span style="color: var(--text-muted); font-size: 0.8rem;">YETKİLİ SAYISI</span>
                    <div class="stat-value"><?php echo $totalAdmins; ?></div>
                </div>
                <div class="stat-card">
                    <span style="color: var(--text-muted); font-size: 0.8rem;">SİSTEM DURUMU</span>
                    <div class="stat-value" style="color: #10b981;">AKTİF</div>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h2 style="font-size: 1.1rem; margin: 0;">Son Kayıt Olanlar</h2></div>
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>KULLANICI</th>
                                <th>E-POSTA</th>
                                <th>ID / IP</th>
                                <th>TARİH</th>
                                <th>DURUM</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($recentActivities)): ?>
                                <?php foreach ($recentActivities as $activity): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($activity['username']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($activity['email']); ?></td>
                                        <td><span class="ip-badge"><?php echo htmlspecialchars($activity['ip_placeholder'] ?? 'N/A'); ?></span></td>
                                        <td><?php echo date('d.m.Y H:i', strtotime($activity['created_at'])); ?></td>
                                        <td><span class="badge">Aktif</span></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="5" style="text-align:center; padding: 2rem;">Veri bulunamadı.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</body>
</html>