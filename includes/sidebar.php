<?php
// Validasi file dipanggil melalui file lain (bukan akses langsung)
if (!isset($_SESSION['role'])) exit;

$role = $_SESSION['role'];
$currentPage = basename($_SERVER['PHP_SELF']);

function isNavActive($pageName, $current) {
    return ($pageName === $current) ? 'active' : '';
}
?>
<aside class="sidebar">
    <div class="brand">
        <h2>C-Playtime</h2>
        <div class="badge-pro"><?= htmlspecialchars($role) ?></div>
    </div>
    <nav class="nav-menu">
        <!-- SEMUA ROLE BISA AKSES DASHBOARD -->
        <a href="dashboard.php" class="nav-item <?= isNavActive('dashboard.php', $currentPage) ?>">🏠 Dashboard</a>
        
        <!-- MODERATOR, IT, OWNER -->
        <?php if (in_array($role, ['MODERATOR', 'IT', 'OWNER'])): ?>
            <a href="laporan.php" class="nav-item <?= isNavActive('laporan.php', $currentPage) ?>">📊 Laporan Absensi</a>
        <?php endif; ?>

        <!-- KHUSUS IT -->
        <?php if ($role === 'IT'): ?>
            <a href="login_logs.php" class="nav-item <?= isNavActive('login_logs.php', $currentPage) ?>">🔐 Laporan Login (IT)</a>
        <?php endif; ?>

        <!-- IT, OWNER -->
        <?php if (in_array($role, ['IT', 'OWNER'])): ?>
            <a href="users.php" class="nav-item <?= isNavActive('users.php', $currentPage) ?>">👥 Kelola Pengguna</a>
            <a href="ajax/export_csv.php" class="nav-item">📥 Export CSV Laporan</a>
        <?php endif; ?>

        <!-- SEMUA ROLE BISA AKSES KONFIGURASI -->
        <a href="konfigurasi.php" class="nav-item <?= isNavActive('konfigurasi.php', $currentPage) ?>">⚙️ Konfigurasi</a>

        <!-- HANYA OWNER YANG BISA RESET SEMUA DATA ABSENSI -->
        <?php if ($role === 'OWNER'): ?>
            <button id="btn-reset" class="nav-item btn-danger-outline">🗑️ Reset Data Absensi</button>
        <?php endif; ?>
    </nav>
    <div class="nav-footer">
        <div class="nav-item" style="color:var(--text-secondary); font-size:0.8rem; margin-bottom:5px;">User: <?= htmlspecialchars($_SESSION['username']) ?></div>
        <a href="logout.php" class="nav-item">🚪 Logout</a>
    </div>
</aside>
