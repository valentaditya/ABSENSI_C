<?php
session_start();
if (!isset($_SESSION['is_admin'])) {
    header('Location: index.php');
    exit;
}
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'IT') {
    header('Location: dashboard.php');
    exit;
}

require_once 'config/database.php';

$stmt = $pdo->query("SELECT * FROM login_logs ORDER BY login_time DESC LIMIT 100");
$logs = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log Aktivitas Admin - IT Only</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="<?= isset($_SESSION['theme']) && $_SESSION['theme'] === 'light' ? 'light-theme' : '' ?>">
    <div class="app-container">
        <?php include 'includes/sidebar.php'; ?>
        
        <main class="main-content">
            <header class="topbar">
                <h1>Laporan Login Panel Admin</h1>
                <div class="user-profile">
                    <span><?= htmlspecialchars($_SESSION['username']) ?></span>
                    <div class="avatar"><?= strtoupper(substr($_SESSION['username'], 0, 1)) ?></div>
                </div>
            </header>
            
            <div class="alert" style="background:var(--surface-color); color:var(--text-secondary);">Halaman ini hanya dapat diakses oleh tim IT. Menampilkan riwayat login sistem.</div>

            <section class="table-section">
                <h2>Riwayat Terakhir (Top 100)</h2>
                <div class="table-responsive" style="margin-top: 20px;">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Username Admin</th>
                                <th>Alamat IP</th>
                                <th>Waktu Login</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($logs as $l): ?>
                            <tr>
                                <td><?= $l['id'] ?></td>
                                <td><strong><?= htmlspecialchars($l['username']) ?></strong></td>
                                <td><span style="color:var(--text-secondary); font-family:monospace;"><?= htmlspecialchars($l['ip_address']) ?></span></td>
                                <td><?= htmlspecialchars($l['login_time']) ?></td>
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
