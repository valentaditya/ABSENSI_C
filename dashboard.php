<?php
require_once __DIR__ . '/config/session.php';
// Proteksi halaman admin, wajib login berdasarkan Session
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header('Location: index.php'); // Kembali ke login
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Absensi Roblox</title>
    <!-- Modern Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="<?= isset($_SESSION['theme']) && $_SESSION['theme'] === 'light' ? 'light-theme' : '' ?>">
    <div class="app-container">
        <!-- Sidebar Navigation Dinamis -->
        <?php include 'includes/sidebar.php'; ?>

        <!-- Main Content (Dashboard) -->
        <main class="main-content">
            <header class="topbar">
                <h1>Overview</h1>
                <div class="user-profile">
                    <span><?= htmlspecialchars($_SESSION['username']) ?></span>
                    <div class="avatar"><?= strtoupper(substr($_SESSION['username'], 0, 1)) ?></div>
                </div>
            </header>

            <!-- Cards Statistik -->
            <section class="stats-grid">
                <div class="stat-card">
                    <h3>Online Sekarang</h3>
                    <div class="stat-value text-success" id="s-online">0</div>
                    <div class="stat-desc">Player Sedang Bermain</div>
                </div>
                <div class="stat-card">
                    <h3>Total Player Hari Ini</h3>
                    <div class="stat-value" id="s-today">0</div>
                    <div class="stat-desc">Total Kehadiran Hari Ini</div>
                </div>
                <div class="stat-card">
                    <h3>Jam Beralu Ramai</h3>
                    <div class="stat-value text-primary" id="s-busiest">-</div>
                    <div class="stat-desc">Peak Hour</div>
                </div>
            </section>

            <!-- Top Player Leaderboard (Berdasarkan Durasi) -->
            <section class="leaderboard-section">
                <h2>Leaderboard (Top Duration)</h2>
                <div class="leaderboard-list" id="leaderboard">
                    <!-- Leaderboard dimuat JS -->
                </div>
            </section>

            <!-- Main Data Table: Absensi Player -->
            <section class="table-section">
                <div class="table-header">
                    <h2>Log Absensi Player</h2>
                    <div class="table-controls">
                        <!-- Pilihan Filter -->
                        <select id="filterSelect" class="input-modern">
                            <option value="today">Hari Ini</option>
                            <option value="week">Minggu Ini</option>
                            <option value="all" selected>Semua Data</option>
                        </select>
                        <!-- Form Pencarian -->
                        <input type="text" id="searchInput" placeholder="Cari username..." class="input-modern">
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Username</th>
                                <th>User ID</th>
                                <th>Jam Masuk</th>
                                <th>Jam Keluar</th>
                                <th>Durasi Bermain</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody id="tableBody">
                            <tr>
                                <td colspan="6" class="text-center">Memuat data...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
        </main>
    </div>

    <!-- Script Utama (Fetch AJAX Real-time) -->
    <script src="assets/js/script.js"></script>
</body>
</html>
