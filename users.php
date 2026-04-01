<?php
require_once __DIR__ . '/config/session.php';
if (!isset($_SESSION['is_admin'])) {
    header('Location: index.php');
    exit;
}
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['OWNER', 'IT'])) {
    header('Location: dashboard.php');
    exit;
}

require_once 'config/database.php';
$msg = '';
$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        $username = trim($_POST['username'] ?? '');
        $role = $_POST['role'] ?? 'USER';
        
        if (!empty($username) && in_array($role, ['MODERATOR', 'USER'])) {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM system_users WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetchColumn() == 0) {
                $hash = password_hash($username, PASSWORD_DEFAULT); // Default = username
                $insert = $pdo->prepare("INSERT INTO system_users (username, password, role) VALUES (?, ?, ?)");
                if ($insert->execute([$username, $hash, $role])) {
                    $msg = "User '$username' berhasil ditambah dengan password default sama seperti username.";
                } else {
                    $err = "Gagal menambah user.";
                }
            } else {
                $err = "Username sudah digunakan!";
            }
        }
    } elseif ($action === 'reset') {
        $user_id = $_POST['user_id'] ?? 0;
        // Verify not resettint IT or OWNER if not allowed
        $stmt = $pdo->prepare("SELECT username, role FROM system_users WHERE id = ?");
        $stmt->execute([$user_id]);
        $target = $stmt->fetch();
        
        if ($target && $target['role'] !== 'IT') {
            $hash = password_hash($target['username'], PASSWORD_DEFAULT);
            $update = $pdo->prepare("UPDATE system_users SET password = ? WHERE id = ?");
            $update->execute([$hash, $user_id]);
            $msg = "Password untuk '{$target['username']}' berhasil direset menjadi default (sama dengan username).";
        } else {
            $err = "Tidak dapat mereset user ini.";
        }
    } elseif ($action === 'delete') {
        $user_id = $_POST['user_id'] ?? 0;
        
        // Cek target
        $stmt = $pdo->prepare("SELECT username, role FROM system_users WHERE id = ?");
        $stmt->execute([$user_id]);
        $target = $stmt->fetch();
        
        // IT tidak bisa dihapus. OWNER hanya bisa dihapus oleh IT.
        if ($target && $target['role'] !== 'IT') {
            if ($target['role'] === 'OWNER' && $_SESSION['role'] !== 'IT') {
                $err = "Anda tidak memiliki hak untuk menghapus akun OWNER.";
            } else {
                $del = $pdo->prepare("DELETE FROM system_users WHERE id = ?");
                if ($del->execute([$user_id])) {
                    $msg = "Akun '{$target['username']}' berhasil dihapus secara permanen.";
                } else {
                    $err = "Gagal menghapus akun.";
                }
            }
        } else {
            $err = "Target akun terlarang atau tidak ditemukan.";
        }
    }
}

// Fetch Users
$stmtUsers = $pdo->query("SELECT * FROM system_users WHERE role != 'IT' ORDER BY id ASC");
$userList = $stmtUsers->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Pengguna - Absensi Roblox</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .form-add-user {
            background: var(--surface-color);
            padding: 20px;
            border-radius: var(--radius-sm);
            border: 1px solid var(--surface-border);
            margin-bottom: 25px;
            display: flex;
            align-items: flex-end;
            gap: 15px;
        }
        .form-add-user > div {
            flex: 1;
        }
        .btn-sm {
            padding: 5px 10px;
            font-size: 0.85rem;
            width: auto;
        }
    </style>
</head>
<body class="<?= isset($_SESSION['theme']) && $_SESSION['theme'] === 'light' ? 'light-theme' : '' ?>">
    <div class="app-container">
        <?php include 'includes/sidebar.php'; ?>

        <main class="main-content">
            <header class="topbar">
                <h1>Kelola Pengguna</h1>
                <div class="user-profile">
                    <span><?= htmlspecialchars($_SESSION['username']) ?></span>
                    <div class="avatar"><?= strtoupper(substr($_SESSION['username'], 0, 1)) ?></div>
                </div>
            </header>

            <?php if($msg): ?><div class="alert" style="background:var(--success-color);color:white;"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
            <?php if($err): ?><div class="alert alert-danger"><?= htmlspecialchars($err) ?></div><?php endif; ?>

            <!-- Form Tambah User -->
            <form class="form-add-user" method="POST">
                <input type="hidden" name="action" value="add">
                <div class="form-group" style="margin-bottom:0;">
                    <label>Username (Login & Password Default)</label>
                    <input type="text" name="username" required autocomplete="off">
                </div>
                <div class="form-group" style="margin-bottom:0;">
                    <label>Peran (Role)</label>
                    <select name="role" class="input-modern" style="width:100%;">
                        <option value="MODERATOR">MODERATOR</option>
                        <option value="USER">USER</option>
                    </select>
                </div>
                <div>
                    <button type="submit" class="btn btn-primary" style="height:44px;">Tambah User</button>
                </div>
            </form>

            <section class="table-section">
                <h2>Daftar Akun Pengguna</h2>
                <div class="table-responsive" style="margin-top: 20px;">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th width="50">No.</th>
                                <th>Username</th>
                                <th>Role</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $no = 1;
                            foreach ($userList as $u): 
                            ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td><strong><?= htmlspecialchars($u['username']) ?></strong></td>
                                <td><span class="badge-pro" style="background:var(--primary-hover)"><?= htmlspecialchars($u['role']) ?></span></td>
                                <td>
                                    <!-- Reset Button selalu muncul untuk OWNER dan IT ke semua user di tabel -->
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Reset password user ini kembali menjadi = <?= htmlspecialchars($u['username']) ?>?');">
                                        <input type="hidden" name="action" value="reset">
                                        <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                        <button type="submit" class="btn btn-danger-outline btn-sm">↻ Reset Password</button>
                                    </form>
                                    
                                    <!-- Hapus Button: IT bisa hapus siapapun, OWNER tak bisa hapus OWNER -->
                                    <?php if ($u['role'] !== 'OWNER' || $_SESSION['role'] === 'IT'): ?>
                                    <form method="POST" style="display:inline; margin-left: 5px;" onsubmit="return confirm('YAKIN INGIN MENGHAPUS AKUN <?= htmlspecialchars($u['username']) ?> SECARA PERMANEN?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                        <button type="submit" class="btn btn-primary btn-sm" style="background-color: var(--danger-color);">🗑️ Hapus</button>
                                    </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </main>
    </div>
</body>
</html>
