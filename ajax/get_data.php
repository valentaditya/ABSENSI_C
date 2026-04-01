<?php
require_once __DIR__ . '/../config/session.php';
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    http_response_code(401);
    exit(json_encode(['error' => 'Unauthorized']));
}

require_once '../config/database.php';

// Ambil parameter filter dan search
$filter = $_GET['filter'] ?? 'all';
$search = $_GET['search'] ?? '';

// Kondisi dasar
$where = "1=1";
$params = [];

// Filter berdasar waktu (Hari Ini / Minggu Ini)
if ($filter === 'today') {
    $where .= " AND DATE(join_time) = CURDATE()";
} elseif ($filter === 'week') {
    // YEARWEEK dengan mode 1 (Senin-Minggu)
    $where .= " AND YEARWEEK(join_time, 1) = YEARWEEK(CURDATE(), 1)";
}

// Filter berdasar pencarian username
if ($search !== '') {
    $where .= " AND username LIKE :search";
    $params[':search'] = "%$search%";
}

try {
    // 1. Ambil data tabel utama (Data Absensi Terbaru Diatas)
    $sqlTable = "SELECT * FROM attendances WHERE $where ORDER BY join_time DESC";
    $stmtTable = $pdo->prepare($sqlTable);
    $stmtTable->execute($params);
    $dataRaw = $stmtTable->fetchAll();

    // Proses data (Hitung Durasi)
    $tableData = [];
    foreach ($dataRaw as $row) {
        $joinTimestamp = strtotime($row['join_time']);
        $isOnline = false;
        
        if ($row['leave_time']) {
            $leaveTimestamp = strtotime($row['leave_time']);
            $diffSeconds = $leaveTimestamp - $joinTimestamp;
            $statusStr = 'Offline';
        } else {
            // Jika belum leave, durasi dihitung dari join hingga waktu sekarang
            $diffSeconds = time() - $joinTimestamp;
            $statusStr = 'Online';
            $isOnline = true;
        }

        // Format ke Jam:Menit:Detik
        $h = floor($diffSeconds / 3600);
        $m = floor(($diffSeconds % 3600) / 60);
        $s = $diffSeconds % 60;
        $durationStr = sprintf("%02d:%02d:%02d", $h, $m, $s);

        $row['duration'] = $durationStr;
        $row['status'] = $statusStr;
        $row['is_online'] = $isOnline;
        
        // Format jam masuk/keluar untuk tampilan yang rapih
        $row['join_time_format'] = date('d-M-Y H:i', $joinTimestamp);
        $row['leave_time_format'] = $row['leave_time'] ? date('d-M-Y H:i', strtotime($row['leave_time'])) : '-';

        $tableData[] = $row;
    }

    // 2. Ambil Statistik: Total Kehadiran Player Hari Ini
    $sqlTotalToday = "SELECT COUNT(DISTINCT userId) as total FROM attendances WHERE DATE(join_time) = CURDATE()";
    $stmtToday = $pdo->query($sqlTotalToday);
    $totalToday = $stmtToday->fetch()['total'];

    // 3. Ambil Statistik: Player Online Sekarang
    $sqlOnline = "SELECT COUNT(DISTINCT userId) as current_online FROM attendances WHERE leave_time IS NULL";
    $stmtOnline = $pdo->query($sqlOnline);
    $onlineNow = $stmtOnline->fetch()['current_online'];

    // 4. Ambil Leaderboard: Top Player Total Bermain Paling Lama
    // Fitur Pro (akumulasi durasi di semua sesi)
    $sqlLeaderboard = "
        SELECT username, 
               SUM(TIMESTAMPDIFF(SECOND, join_time, IFNULL(leave_time, NOW()))) as total_seconds
        FROM attendances
        GROUP BY userId, username
        ORDER BY total_seconds DESC
        LIMIT 5
    ";
    $stmtLB = $pdo->query($sqlLeaderboard);
    $leaderboardRaw = $stmtLB->fetchAll();
    
    $leaderboard = [];
    foreach ($leaderboardRaw as $row) {
        $h = floor($row['total_seconds'] / 3600);
        $m = floor(($row['total_seconds'] % 3600) / 60);
        $durationStr = sprintf("%dh %dm", $h, $m);
        $row['duration_format'] = $durationStr;
        $leaderboard[] = $row;
    }

    // 5. Statistik Jam Berlalu Paling Ramai
    $sqlBusiest = "
        SELECT HOUR(join_time) as hour, COUNT(*) as count 
        FROM attendances 
        GROUP BY HOUR(join_time) 
        ORDER BY count DESC 
        LIMIT 1
    ";
    $stmtBusy = $pdo->query($sqlBusiest);
    $busiestRow = $stmtBusy->fetch();
    $busiestHour = $busiestRow ? sprintf("%02d:00", $busiestRow['hour']) : '-';

    // Output JSON untuk frontend (Datatable & Stats)
    echo json_encode([
        'success' => true,
        'table' => $tableData,
        'stats' => [
            'total_today' => $totalToday,
            'online_now' => $onlineNow,
            'busiest_hour' => $busiestHour
        ],
        'leaderboard' => $leaderboard
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error', 'message' => $e->getMessage()]);
}
?>
