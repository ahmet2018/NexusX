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

function getRealIP() {
    if (isset($_SERVER["HTTP_CF_CONNECTING_IP"])) {
        return $_SERVER["HTTP_CF_CONNECTING_IP"];
    }
    if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ipList = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        return trim($ipList[0]); // Proxy arkasındaysa ilk IP gerçektir
    }
    if (isset($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    }
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_user'])) {
    $new_username = trim($_POST['username']);
    $new_email = trim($_POST['email']);
    $new_password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $new_is_admin = isset($_POST['is_admin']) ? 1 : 0;
    $ip_address = getRealIP(); // Sağlamlaştırılmış IP fonksiyonunu kullandık

    if (!empty($new_username) && !empty($new_email) && !empty($_POST['password'])) {
        try {
            $stmt = $db->prepare("INSERT INTO users (username, email, password, is_admin, ip_address, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
            $stmt->execute([$new_username, $new_email, $new_password, $new_is_admin, $ip_address]);
            $success_msg = "Kullanıcı başarıyla eklendi.";
        } catch (PDOException $e) {
            $error = "Ekleme hatası (Veritabanına 'ip_address' sütununu eklediğinden emin ol): " . $e->getMessage();
        }
    } else {
        $error = "Lütfen tüm alanları doldurun.";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_user'])) {
    $edit_id = (int)$_POST['edit_id'];
    $edit_username = trim($_POST['username']);
    $edit_email = trim($_POST['email']);
    $edit_is_admin = isset($_POST['is_admin']) ? 1 : 0;

    if (!empty($edit_username) && !empty($edit_email)) {
        try {
            if (!empty($_POST['password'])) {
                $edit_password = password_hash($_POST['password'], PASSWORD_DEFAULT);
                $stmt = $db->prepare("UPDATE users SET username = ?, email = ?, password = ?, is_admin = ? WHERE id = ?");
                $stmt->execute([$edit_username, $edit_email, $edit_password, $edit_is_admin, $edit_id]);
            } else {
                $stmt = $db->prepare("UPDATE users SET username = ?, email = ?, is_admin = ? WHERE id = ?");
                $stmt->execute([$edit_username, $edit_email, $edit_is_admin, $edit_id]);
            }
            $success_msg = "Kullanıcı başarıyla güncellendi.";
        } catch (PDOException $e) {
            $error = "Güncelleme hatası: " . $e->getMessage();
        }
    } else {
        $error = "Kullanıcı adı ve e-posta boş bırakılamaz.";
    }
}

if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    if ($id !== $_SESSION['user_id']) {
        $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$id]);
        header('Location: users.php');
        exit;
    }
}

// Admin Yetkisi Değiştirme
if (isset($_GET['toggle_admin'])) {
    $id = (int)$_GET['toggle_admin'];
    $current_status = (int)$_GET['status'];
    $new_status = ($current_status === 1) ? 0 : 1;

    if ($id !== $_SESSION['user_id']) {
        $stmt = $db->prepare("UPDATE users SET is_admin = ? WHERE id = ?");
        $stmt->execute([$new_status, $id]);
        header('Location: users.php');
        exit;
    }
}

$stat_total_users = 0;
$stat_today_users = 0;
$stat_total_admins = 0;
$stat_recent_logins = 0;

try {
    $stat_total_users = $db->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $stat_today_users = $db->query("SELECT COUNT(*) FROM users WHERE DATE(created_at) = CURDATE()")->fetchColumn();
    $stat_total_admins = $db->query("SELECT COUNT(*) FROM users WHERE is_admin = 1")->fetchColumn();
    $stat_recent_logins = $db->query("SELECT COUNT(*) FROM users WHERE last_login >= NOW() - INTERVAL 1 DAY")->fetchColumn();
} catch (PDOException $e) { }

try {
    $stmt = $db->query("SELECT id, username, email, is_admin, created_at, ip_address FROM users ORDER BY created_at DESC");
    $allUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Sütun yoksa diye yedek sorgu
    $stmt = $db->query("SELECT id, username, email, is_admin, created_at FROM users ORDER BY created_at DESC");
    $allUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$current_page = 'users';
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kullanıcı Yönetimi - LogFinder Pro</title>
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
            width: 260px; background-color: var(--sidebar-bg); border-right: 1px solid var(--border-color);
            display: flex; flex-direction: column; position: fixed; height: 100vh;
        }
        .sidebar-header { padding: 2rem 1.5rem; border-bottom: 1px solid var(--border-color); }
        .sidebar-logo { display: flex; align-items: center; gap: 10px; color: var(--accent-blue); font-size: 1.2rem; font-weight: bold; letter-spacing: 1px; }
        .nav-menu { list-style: none; padding: 1rem; margin: 0; }
        .nav-item { margin-bottom: 0.5rem; }
        .nav-link { display: flex; align-items: center; padding: 0.8rem 1rem; color: var(--text-muted); text-decoration: none; border-radius: 8px; transition: all 0.3s; }
        .nav-link i { width: 25px; font-size: 1.1rem; }
        .nav-link:hover, .nav-link.active { background: rgba(59, 130, 246, 0.1); color: var(--accent-blue); }
        .nav-link.active { border-left: 3px solid var(--accent-blue); }

        .main-content { flex: 1; margin-left: 260px; padding: 2rem; }
        .content-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; }
        .page-title { font-size: 2rem; font-weight: 800; background: linear-gradient(to right, #fff, #94a3b8); -webkit-background-clip: text; -webkit-text-fill-color: transparent; margin: 0; }

        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1.5rem; margin-bottom: 2rem; }
        .stat-card { background: var(--card-bg); border: 1px solid var(--border-color); border-radius: 15px; padding: 1.5rem; display: flex; align-items: center; gap: 1.2rem; transition: transform 0.3s; }
        .stat-card:hover { transform: translateY(-5px); }
        .stat-icon { width: 55px; height: 55px; border-radius: 12px; display: flex; justify-content: center; align-items: center; font-size: 1.5rem; }
        .stat-icon.blue { background: rgba(59, 130, 246, 0.1); color: var(--accent-blue); }
        .stat-icon.green { background: rgba(16, 185, 129, 0.1); color: #10b981; }
        .stat-icon.purple { background: rgba(168, 85, 247, 0.1); color: var(--accent-purple); }
        .stat-icon.orange { background: rgba(245, 158, 11, 0.1); color: #f59e0b; }
        .stat-info h3 { margin: 0; font-size: 1.8rem; color: white; font-weight: bold; }
        .stat-info p { margin: 0; font-size: 0.85rem; color: var(--text-muted); margin-top: 5px; }

        .card { background: var(--card-bg); border-radius: 15px; border: 1px solid var(--border-color); overflow: hidden; margin-bottom: 2rem; }

        /* FORMLAR */
        .form-container { padding: 1.5rem; display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; align-items: flex-end; }
        .form-group { display: flex; flex-direction: column; gap: 0.5rem; }
        .form-group label { font-size: 0.8rem; color: var(--text-muted); }
        .form-input { background: #1a1f2e; border: 1px solid var(--border-color); padding: 0.7rem; border-radius: 8px; color: white; outline: none; }
        .btn-add, .btn-submit { background: var(--accent-blue); color: white; border: none; padding: 0.7rem; border-radius: 8px; cursor: pointer; font-weight: 600; transition: 0.3s; }
        .btn-add:hover, .btn-submit:hover { opacity: 0.9; transform: translateY(-2px); }

        /* TABLOLAR */
        .table { width: 100%; border-collapse: collapse; }
        .table th { text-align: left; padding: 1rem; background: rgba(0,0,0,0.2); color: var(--text-muted); font-size: 0.85rem; }
        .table td { padding: 1rem; border-bottom: 1px solid var(--border-color); font-size: 0.9rem; }
        .badge { padding: 4px 10px; border-radius: 20px; font-size: 0.75rem; }
        .badge-admin { background: rgba(168, 85, 247, 0.1); color: var(--accent-purple); }
        .badge-user { background: rgba(16, 185, 129, 0.1); color: #10b981; }
        .btn-action { padding: 5px 10px; border-radius: 6px; text-decoration: none; font-size: 0.8rem; transition: 0.3s; border: 1px solid transparent; display: inline-block; cursor: pointer; }
        .btn-del { color: #ef4444; border-color: rgba(239, 68, 68, 0.2); }
        .btn-del:hover { background: #ef4444; color: white; }
        .btn-priv { color: var(--accent-blue); border-color: rgba(59, 130, 246, 0.2); margin-right: 5px; }
        .btn-priv:hover { background: var(--accent-blue); color: white; }
        .btn-edit { color: #f59e0b; border-color: rgba(245, 158, 11, 0.2); margin-right: 5px; background: none; }
        .btn-edit:hover { background: #f59e0b; color: white; }

        .alert { padding: 1rem; border-radius: 8px; margin-bottom: 1rem; }
        .alert-success { background: rgba(16, 185, 129, 0.1); color: #10b981; border: 1px solid #10b981; }
        .alert-error { background: rgba(239, 68, 68, 0.1); color: #ef4444; border: 1px solid #ef4444; }

        .modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); z-index: 1000; justify-content: center; align-items: center; backdrop-filter: blur(4px); }
        .modal-content { background: var(--card-bg); padding: 2rem; border-radius: 15px; width: 100%; max-width: 450px; border: 1px solid var(--border-color); box-shadow: 0 10px 25px rgba(0,0,0,0.5); animation: fadeIn 0.3s ease; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(-20px); } to { opacity: 1; transform: translateY(0); } }
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; border-bottom: 1px solid var(--border-color); padding-bottom: 1rem; }
        .modal-header h2 { margin: 0; font-size: 1.2rem; }
        .btn-close { background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: 1.5rem; transition: 0.3s; }
        .btn-close:hover { color: white; }
        .modal-body .form-group { margin-bottom: 1rem; }
        .modal-body .form-input { width: 100%; box-sizing: border-box; }
        
        /* IP Link Style */
        .ip-link { color: var(--accent-blue); text-decoration: none; font-family: monospace; transition: 0.2s;}
        .ip-link:hover { color: white; text-decoration: underline; }
    </style>
</head>
<body>
    <div class="dashboard-layout">
        <div class="sidebar">
            <div class="sidebar-header">
                <div class="sidebar-logo">
                    <i class="fas fa-terminal"></i>
                    <span>ULTRA PANEL</span>
                </div>
            </div>
            
            <ul class="nav-menu">
                <li class="nav-item">
                    <a href="dashboard.php" class="nav-link">
                        <i class="fas fa-th-large"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="users.php" class="nav-link active">
                        <i class="fas fa-user-shield"></i>
                        <span>Kullanıcılar</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="?logout=1" class="nav-link" style="color: #ef4444;" onclick="return confirm('Çıkış yapılsın mı?')">
                        <i class="fas fa-power-off"></i>
                        <span>Güvenli Çıkış</span>
                    </a>
                </li>
            </ul>
        </div>
        
        <div class="main-content">
            <div class="content-header">
                <h1 class="page-title">Kullanıcı Yönetimi</h1>
            </div>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon blue"><i class="fas fa-users"></i></div>
                    <div class="stat-info">
                        <h3><?php echo number_format($stat_total_users); ?></h3>
                        <p>Toplam Kullanıcı</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon green"><i class="fas fa-user-clock"></i></div>
                    <div class="stat-info">
                        <h3><?php echo number_format($stat_today_users); ?></h3>
                        <p>Bugün Kayıt Olanlar</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon purple"><i class="fas fa-user-shield"></i></div>
                    <div class="stat-info">
                        <h3><?php echo number_format($stat_total_admins); ?></h3>
                        <p>Aktif Yönetici</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon orange"><i class="fas fa-sign-in-alt"></i></div>
                    <div class="stat-info">
                        <h3><?php echo number_format($stat_recent_logins); ?></h3>
                        <p>Son 24 Saatte Giriş</p>
                    </div>
                </div>
            </div>

            <?php if (isset($success_msg)): ?>
                <div class="alert alert-success"><?php echo $success_msg; ?></div>
            <?php endif; ?>
            <?php if (isset($error)): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>

            <div class="card">
                <div style="padding: 1rem 1.5rem; border-bottom: 1px solid var(--border-color); font-weight: bold;">
                    <i class="fas fa-user-plus" style="margin-right: 10px; color: var(--accent-blue);"></i> Yeni Kullanıcı Ekle
                </div>
                <form method="POST" class="form-container">
                    <div class="form-group">
                        <label>Kullanıcı Adı</label>
                        <input type="text" name="username" class="form-input" placeholder="Örn: admin" required>
                    </div>
                    <div class="form-group">
                        <label>E-Posta</label>
                        <input type="email" name="email" class="form-input" placeholder="mail@adres.com" required>
                    </div>
                    <div class="form-group">
                        <label>Şifre</label>
                        <input type="password" name="password" class="form-input" placeholder="******" required>
                    </div>
                    <div class="form-group" style="flex-direction: row; align-items: center; gap: 10px; padding-bottom: 10px;">
                        <input type="checkbox" name="is_admin" id="is_admin" style="width: 18px; height: 18px; cursor: pointer;">
                        <label for="is_admin" style="cursor: pointer;">Yönetici Yetkisi</label>
                    </div>
                    <button type="submit" name="add_user" class="btn-add">
                        <i class="fas fa-save"></i> Kaydet
                    </button>
                </form>
            </div>
            
            <div class="card">
                <div style="padding: 1rem 1.5rem; border-bottom: 1px solid var(--border-color); font-weight: bold; display: flex; align-items: center;">
                    <i class="fas fa-list" style="margin-right: 10px; color: var(--accent-purple);"></i> Kullanıcı Listesi
                </div>
                <table class="table">
                    <thead>
                        <tr>
                            <th>KULLANICI</th>
                            <th>E-POSTA</th>
                            <th>IP ADRESİ</th>
                            <th>YETKİ</th>
                            <th>KAYIT</th>
                            <th>İŞLEMLER</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($allUsers as $user): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($user['username']); ?></strong></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td>
                                    <?php if(isset($user['ip_address']) && !empty($user['ip_address'])): ?>
                                        <a href="https://ip-api.com/#<?php echo htmlspecialchars($user['ip_address']); ?>" target="_blank" class="ip-link" title="IP Detaylarını Gör">
                                            <i class="fas fa-globe"></i> <?php echo htmlspecialchars($user['ip_address']); ?>
                                        </a>
                                    <?php else: ?>
                                        <span style="font-family: monospace; color: var(--text-muted);">Bilinmiyor</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge <?php echo $user['is_admin'] ? 'badge-admin' : 'badge-user'; ?>">
                                        <?php echo $user['is_admin'] ? 'Yönetici' : 'Üye'; ?>
                                    </span>
                                </td>
                                <td><?php echo date('d.m.Y', strtotime($user['created_at'])); ?></td>
                                <td>
                                    <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                        <button type="button" class="btn-action btn-edit" title="Düzenle" 
                                            onclick="openEditModal(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars(addslashes($user['username'])); ?>', '<?php echo htmlspecialchars(addslashes($user['email'])); ?>', <?php echo $user['is_admin']; ?>)">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        
                                        <a href="?toggle_admin=<?php echo $user['id']; ?>&status=<?php echo $user['is_admin']; ?>" class="btn-action btn-priv" title="Yetkiyi Değiştir">
                                            <i class="fas fa-user-edit"></i>
                                        </a>
                                        <a href="?delete=<?php echo $user['id']; ?>" class="btn-action btn-del" onclick="return confirm('Siliyorsun, emin misin?')" title="Kullanıcıyı Sil">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    <?php else: ?>
                                        <small style="color: var(--text-muted)">Geçersiz (Siz)</small>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div id="editModal" class="modal-overlay">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-user-edit" style="color: var(--accent-blue); margin-right: 10px;"></i>Kullanıcı Düzenle</h2>
                <button class="btn-close" onclick="closeEditModal()">&times;</button>
            </div>
            <form method="POST" class="modal-body">
                <input type="hidden" name="edit_id" id="edit_id">
                <div class="form-group">
                    <label>Kullanıcı Adı</label>
                    <input type="text" name="username" id="edit_username" class="form-input" required>
                </div>
                <div class="form-group">
                    <label>E-Posta</label>
                    <input type="email" name="email" id="edit_email" class="form-input" required>
                </div>
                <div class="form-group">
                    <label>Yeni Şifre (Değiştirmek istemiyorsanız boş bırakın)</label>
                    <input type="password" name="password" class="form-input" placeholder="******">
                </div>
                <div class="form-group" style="flex-direction: row; align-items: center; gap: 10px; margin-top: 15px;">
                    <input type="checkbox" name="is_admin" id="edit_is_admin" style="width: 18px; height: 18px; cursor: pointer;">
                    <label for="edit_is_admin" style="cursor: pointer;">Yönetici Yetkisi</label>
                </div>
                <div class="form-group" style="margin-top: 1.5rem;">
                    <button type="submit" name="edit_user" class="btn-submit" style="width: 100%;">
                        <i class="fas fa-save"></i> Değişiklikleri Kaydet
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openEditModal(id, username, email, isAdmin) {
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_username').value = username;
            document.getElementById('edit_email').value = email;
            document.getElementById('edit_is_admin').checked = (isAdmin === 1);
            document.getElementById('editModal').style.display = 'flex';
        }

        function closeEditModal() {
            document.getElementById('editModal').style.display = 'none';
        }

        window.onclick = function(event) {
            var modal = document.getElementById('editModal');
            if (event.target == modal) {
                modal.style.display = "none";
            }
        }
    </script>
</body>
</html>