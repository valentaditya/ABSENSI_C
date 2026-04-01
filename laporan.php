<?php
require_once __DIR__ . '/config/session.php';
// Halaman Laporan: OWNER, IT, MODERATOR (Bukan USER)
if (!isset($_SESSION['is_admin'])) {
    header('Location: index.php');
    exit;
}
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['OWNER', 'IT', 'MODERATOR'])) {
    header('Location: dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan - Absensi Roblox</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        /* CSS Tambahan khusus laporan */
        .header-laporan {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .filter-group {
            display: flex;
            gap: 15px;
            align-items: center;
        }
        .btn-print {
            background-color: var(--success-color);
            color: white;
            padding: 10px 15px;
            border-radius: var(--radius-sm);
            border: none;
            cursor: pointer;
            font-weight: 600;
        }
        .btn-print:hover {
            opacity: 0.9;
        }
        .laporan-title {
            display: none;
        }
        @media print {
            .laporan-title {
                display: block;
                text-align: center;
                margin-bottom: 20px;
                color: black;
            }
        }
    </style>
</head>
<body class="<?= isset($_SESSION['theme']) && $_SESSION['theme'] === 'light' ? 'light-theme' : '' ?>">
    <div class="app-container">
        <!-- Sidebar Navigation -->
        <?php include 'includes/sidebar.php'; ?>

        <!-- Main Content (Laporan) -->
        <main class="main-content" id="printArea">
            <header class="topbar">
                <h1>Rekap Laporan</h1>
                <div class="user-profile">
                    <span><?= htmlspecialchars($_SESSION['username']) ?></span>
                    <div class="avatar"><?= strtoupper(substr($_SESSION['username'], 0, 1)) ?></div>
                </div>
            </header>

            <div class="header-laporan">
                <div class="filter-group">
                    <label for="laporanFilter" style="color:var(--text-secondary); font-size:0.9rem;">Periode:</label>
                    <select id="laporanFilter" class="input-modern">
                        <option value="today">Hari Ini</option>
                        <option value="week">Minggu Ini</option>
                        <option value="month">Bulan Ini</option>
                        <option value="year">Tahun Ini</option>
                        <option value="all">Semua Waktu</option>
                    </select>

                    <input type="date" id="laporanDate" class="input-modern" style="min-width: 140px;" title="Cari berdasarkan tanggal spesifik">

                    <input type="text" id="laporanSearch" placeholder="Cari username..." class="input-modern" style="min-width: 200px;">
                </div>
                <!-- Tombol Download -->
                <button type="button" class="btn-print" id="btn-download">📥 Download Laporan (CSV)</button>
            </div>

            <h2 class="laporan-title">Laporan Kehadiran Player Roblox</h2>

            <!-- Cards Statistik Laporan -->
            <section class="stats-grid">
                <div class="stat-card">
                    <h3>Total Player Unik</h3>
                    <div class="stat-value text-primary" id="L-total-users">0</div>
                    <div class="stat-desc">Berdasarkan filter/username</div>
                </div>
                <div class="stat-card">
                    <h3>Total Login (Sesi)</h3>
                    <div class="stat-value text-success" id="L-total-sessions">0</div>
                    <div class="stat-desc">Total kali keluar-masuk</div>
                </div>
                <div class="stat-card">
                    <h3>Total Jam Bermain</h3>
                    <div class="stat-value text-danger" id="L-total-hours">0 Jam 0 Menit</div>
                    <div class="stat-desc">Berdasarkan filter/username</div>
                </div>
            </section>

            <!-- Table Laporan -->
            <section class="table-section">
                <div class="table-header">
                    <h2>Rincian Absensi</h2>
                </div>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Username</th>
                                <th>User ID</th>
                                <th>Tanggal</th>
                                <th>Jam Masuk</th>
                                <th>Jam Keluar</th>
                                <th>Durasi Sesi</th>
                            </tr>
                        </thead>
                        <tbody id="laporanBody">
                            <tr>
                                <td colspan="6" class="text-center">Memuat data...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
        </main>
    </div>

    <!-- Script khusus halaman laporan -->
    <script>
        const filterEl = document.getElementById('laporanFilter');
        const dateEl = document.getElementById('laporanDate');
        const searchEl = document.getElementById('laporanSearch');
        const btnDownload = document.getElementById('btn-download');
        
        const laporanBody = document.getElementById('laporanBody');
        const lUsers = document.getElementById('L-total-users');
        const lSessions = document.getElementById('L-total-sessions');
        const lHours = document.getElementById('L-total-hours');
        const laporanTitle = document.querySelector('.laporan-title');

        let timeoutId;

        function loadLaporan() {
            const period = filterEl.value;
            const search = searchEl.value;
            const specificDate = dateEl.value;
            
            let titleText = 'Laporan Kehadiran Player Roblox - ';
            
            if (specificDate) {
                titleText += specificDate;
            } else {
                if(period === 'today') titleText += 'Hari Ini';
                else if(period === 'week') titleText += 'Minggu Ini';
                else if(period === 'month') titleText += 'Bulan Ini';
                else if(period === 'year') titleText += 'Tahun Ini';
                else if(period === 'all') titleText += 'Semua Waktu';
            }
            
            if (search) titleText += ' (Pencarian: ' + search + ')';
            
            laporanTitle.textContent = titleText;
            laporanBody.innerHTML = `<tr><td colspan="6" class="text-center">Memuat data...</td></tr>`;

            fetch('ajax/get_laporan.php?period=' + period + '&search=' + encodeURIComponent(search) + '&date=' + specificDate)
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        // 1. Update Cards
                        lUsers.textContent = data.summary.total_users;
                        lSessions.textContent = data.summary.total_sessions;
                        lHours.textContent = data.summary.total_hours;

                        // 2. Render Table
                        if (data.table.length === 0) {
                            laporanBody.innerHTML = `<tr><td colspan="6" class="text-center">Tidak ada catatan kehadiran yang cocok dengan pencarian ini</td></tr>`;
                        } else {
                            let html = '';
                            data.table.forEach(r => {
                                html += `
                                    <tr>
                                        <td><strong>${r.username}</strong></td>
                                        <td><small class="text-secondary">${r.userId}</small></td>
                                        <td>${r.tanggal_format}</td>
                                        <td>${r.jam_masuk}</td>
                                        <td>${r.jam_keluar}</td>
                                        <td>${r.duration}</td>
                                    </tr>
                                `;
                            });
                            laporanBody.innerHTML = html;
                        }
                    } else {
                        laporanBody.innerHTML = `<tr><td colspan="6" class="text-center text-danger">Gagal memuat data</td></tr>`;
                    }
                })
                .catch(err => {
                    console.error(err);
                    laporanBody.innerHTML = `<tr><td colspan="6" class="text-center text-danger">Koneksi API error</td></tr>`;
                });
        }

        // Event listener cetak diganti jadi download
        btnDownload.addEventListener('click', function() {
            const period = filterEl.value;
            const search = searchEl.value;
            const specificDate = dateEl.value;
            window.location.href = 'ajax/export_csv.php?period=' + period + '&search=' + encodeURIComponent(search) + '&date=' + specificDate;
        });

        // Event listener saat ganti filter dropdown atau date picker
        filterEl.addEventListener('change', function() {
            dateEl.value = ''; // Reset tanggal spesifik jika ganti periode
            loadLaporan();
        });
        
        dateEl.addEventListener('change', function() {
            filterEl.value = 'all'; // Reset ke semua jika pilih tanggal spesifik
            loadLaporan();
        });
        
        // Debounce untuk input pencarian username
        searchEl.addEventListener('input', function() {
            clearTimeout(timeoutId);
            timeoutId = setTimeout(loadLaporan, 400); // Tunggu user selesai ngetik
        });

        // Load awal
        loadLaporan();
    </script>
</body>
</html>
