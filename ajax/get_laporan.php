<?php
require_once __DIR__ . '/../config/session.php';
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    http_response_code(401);
    exit(json_encode(['error' => 'Unauthorized']));
}

require_once '../config/database.php';

$period = $_GET['period'] ?? 'today';
$search = $_GET['search'] ?? '';

$where = "1=1";
$params = [];

if ($period === 'today') {
    $where .= " AND DATE(join_time) = CURDATE()";
} elseif ($period === 'week') {
    $where .= " AND YEARWEEK(join_time, 1) = YEARWEEK(CURDATE(), 1)";
} elseif ($period === 'month') {
    $where .= " AND MONTH(join_time) = MONTH(CURDATE()) AND YEAR(join_time) = YEAR(CURDATE())";
} elseif ($period === 'year') {
    $where .= " AND YEAR(join_time) = YEAR(CURDATE())";
}

if ($search !== '') {
    $where .= " AND username LIKE :search";
    // Pakai array mapping key -> value untuk PDO
    $params[':search'] = "%$search%";
}

try {
    // 1. Ambil data list untuk tabel laporan
    $sqlTable = "SELECT * FROM attendances WHERE $where ORDER BY join_time DESC";
    $stmtTable = $pdo->prepare($sqlTable);
    $stmtTable->execute($params);
    $dataRaw = $stmtTable->fetchAll();

    $tableData = [];
    foreach ($dataRaw as $row) {
        $joinTimestamp = strtotime($row['join_time']);
        $leaveTimestamp = $row['leave_time'] ? strtotime($row['leave_time']) : time();
        $diffSeconds = $leaveTimestamp - $joinTimestamp;

        $h = floor($diffSeconds / 3600);
        $m = floor(($diffSeconds % 3600) / 60);
        $s = $diffSeconds % 60;
        
        $row['duration'] = sprintf("%02d Jam %02d Menit", $h, $m);
        $row['join_time_format'] = date('d M Y, H:i', $joinTimestamp);
        $row['leave_time_format'] = $row['leave_time'] ? date('d M Y, H:i', $leaveTimestamp) : 'Masih Bermain';
        
        $tableData[] = $row;
    }

    // 2. Ambil Rekapitulasi (Total User, Total Sesi, Total Jam Bermain)
    $sqlSummary = "
        SELECT 
            COUNT(DISTINCT userId) as total_users,
            COUNT(id) as total_sessions,
            SUM(TIMESTAMPDIFF(SECOND, join_time, IFNULL(leave_time, NOW()))) as total_seconds
        FROM attendances
        WHERE $where
    ";
    $stmtSummary = $pdo->prepare($sqlSummary);
    $stmtSummary->execute($params);
    $summary = $stmtSummary->fetch();

    $totSec = $summary['total_seconds'] ?: 0;
    $sumH = floor($totSec / 3600);
    $sumM = floor(($totSec % 3600) / 60);
    $totalHoursFormatted = sprintf("%d Jam %d Menit", $sumH, $sumM);

    echo json_encode([
        'success' => true,
        'table' => $tableData,
        'summary' => [
            'total_users' => $summary['total_users'] ?: 0,
            'total_sessions' => $summary['total_sessions'] ?: 0,
            'total_hours' => $totalHoursFormatted
        ]
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error', 'message' => $e->getMessage()]);
}
?>
