<?php
require_once __DIR__ . '/config/session.php';
if (!isset($_SESSION['is_admin'])) {
    header('Location: index.php');
    exit;
}
if (!isset($_SESSION['role'])) {
    header('Location: dashboard.php');
    exit;
}


$msg = '';
$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $user_id = $_SESSION['user_id'];
    
    // GANTI TEMA
    if ($action === 'theme') {
        $theme = $_POST['theme'] === 'light' ? 'light' : 'dark';
        
        $upd = $pdo->prepare("UPDATE system_users SET theme = ? WHERE id = ?");
        if ($upd->execute([$theme, $user_id])) {
            $_SESSION['theme'] = $theme;
            $msg = "Tema berhasil diubah menjadi " . ucfirst($theme) . " Mode.";
        } else {
            $err = "Gagal mengubah profil tema.";
        }
    }
    
    // GANTI PASSWORD
    if ($action === 'password') {
        $old_pass = $_POST['old_pass'] ?? '';
        $new_pass = $_POST['new_pass'] ?? '';
        $conf_pass = $_POST['conf_pass'] ?? '';
        
        $stmt = $pdo->prepare("SELECT password FROM system_users WHERE id = ?");
        $stmt->execute([$user_id]);
        $currentHas = $stmt->fetchColumn();
        
        if (password_verify($old_pass, $currentHas)) {
            if ($new_pass === $conf_pass && strlen($new_pass) >= 4) {
                $hash = password_hash($new_pass, PASSWORD_DEFAULT);
                $upd = $pdo->prepare("UPDATE system_users SET password = ? WHERE id = ?");
                if ($upd->execute([$hash, $user_id])) {
                    $msg = "Password Anda berhasil diperbarui!";
                }
            } else {
                $err = "Konfirmasi password baru tidak cocok atau terlalu pendek!";
            }
        } else {
            $err = "Password lama yang Anda masukkan salah!";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Konfigurasi Akun - R-Absensi</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .setting-card {
            background-color: var(--surface-color);
            border: 1px solid var(--surface-border);
            border-radius: var(--radius-soft);
            padding: 30px;
            max-width: 500px;
            margin-bottom: 30px;
        }
        .setting-card h2 {
            font-size: 1.2rem;
            margin-bottom: 20px;
            border-bottom: 1px solid var(--surface-border);
            padding-bottom: 10px;
        }
    </style>
</head>
<body class="<?= isset($_SESSION['theme']) && $_SESSION['theme'] === 'light' ? 'light-theme' : '' ?>">
    <div class="app-container">
        <?php include 'includes/sidebar.php'; ?>
        
        <main class="main-content">
            <header class="topbar">
                <h1>Konfigurasi Pengguna</h1>
                <div class="user-profile">
                    <span><?= htmlspecialchars($_SESSION['username']) ?></span>
                    <div class="avatar"><?= strtoupper(substr($_SESSION['username'], 0, 1)) ?></div>
                </div>
            </header>

            <?php if($msg): ?><div class="alert" style="background:var(--success-color);color:white;"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
            <?php if($err): ?><div class="alert alert-danger"><?= htmlspecialchars($err) ?></div><?php endif; ?>

            <!-- Setting Tema -->
            <div class="setting-card">
                <h2>Ubah Tema Tampilan</h2>
                <form method="POST">
                    <input type="hidden" name="action" value="theme">
                    <div class="form-group">
                        <label>Tema Pilihan</label>
                        <select name="theme" class="input-modern" style="width:100%;">
                            <option value="dark" <?= (!isset($_SESSION['theme']) || $_SESSION['theme'] === 'dark') ? 'selected' : '' ?>>Dark Mode</option>
                            <option value="light" <?= (isset($_SESSION['theme']) && $_SESSION['theme'] === 'light') ? 'selected' : '' ?>>Light Mode</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">Simpan Tema</button>
                </form>
            </div>

            <!-- Ganti Password -->
            <div class="setting-card">
                <h2>Ganti Password</h2>
                <form method="POST">
                    <input type="hidden" name="action" value="password">
                    <div class="form-group">
                        <label>Password Lama</label>
                        <input type="password" name="old_pass" required>
                    </div>
                    <div class="form-group">
                        <label>Password Baru</label>
                        <input type="password" name="new_pass" required>
                    </div>
                    <div class="form-group">
                        <label>Konfirmasi Password Baru</label>
                        <input type="password" name="conf_pass" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Perbarui Password</button>
                    <p style="font-size:0.8rem; color:var(--text-secondary); margin-top:10px;">Catatan: User dan Moderator dapat meminta OWNER/IT jika lupa password (Reset Default).</p>
                </form>
            </div>
            
        </main>
    </div>
</body>
</html>
