<?php
require_once __DIR__ . '/../config/session.php';
// Hanya admin
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header('Location: ../index.php');
    exit;
}



$period = $_GET['period'] ?? 'all';
$search = $_GET['search'] ?? '';
$dateVal = $_GET['date'] ?? '';

$where = "1=1";
$params = [];

if (!empty($dateVal)) {
    $where .= " AND DATE(join_time) = :dateVal";
    $params[':dateVal'] = $dateVal;
} else {
    if ($period === 'today') {
        $where .= " AND DATE(join_time) = CURDATE()";
    } elseif ($period === 'week') {
        $where .= " AND YEARWEEK(join_time, 1) = YEARWEEK(CURDATE(), 1)";
    } elseif ($period === 'month') {
        $where .= " AND MONTH(join_time) = MONTH(CURDATE()) AND YEAR(join_time) = YEAR(CURDATE())";
    } elseif ($period === 'year') {
        $where .= " AND YEAR(join_time) = YEAR(CURDATE())";
    }
}

if ($search !== '') {
    $where .= " AND username LIKE :search";
    $params[':search'] = "%$search%";
}

// Ambil semua data
$stmt = $pdo->prepare("SELECT * FROM attendances WHERE $where ORDER BY join_time DESC");
$stmt->execute($params);
$data = $stmt->fetchAll();

// Setting Header untuk format CSV/Excel
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="Laporan_Absensi_Roblox_' . date('Y-m-d') . '.csv"');

// Buka output stream
$output = fopen('php://output', 'w');

// Header Kolom CSV
fputcsv($output, ['ID', 'User ID', 'Username', 'Tanggal', 'Jam Masuk', 'Jam Keluar', 'Durasi Bermain (Detik)', 'Status']);

// Isi baris data
foreach ($data as $row) {
    $joinTimestamp = strtotime($row['join_time']);
    
    if ($row['leave_time']) {
        $leaveTimestamp = strtotime($row['leave_time']);
        $diffSeconds = $leaveTimestamp - $joinTimestamp;
        $statusStr = 'Offline';
    } else {
        $diffSeconds = time() - $joinTimestamp;
        $statusStr = 'Online';
    }

    $tanggal = date('Y-m-d', $joinTimestamp);
    $jamMasuk = date('H:i:s', $joinTimestamp);
    $jamKeluar = $row['leave_time'] ? date('H:i:s', strtotime($row['leave_time'])) : '-';

    fputcsv($output, [
        $row['id'],
        $row['userId'],
        $row['username'],
        $tanggal,
        $jamMasuk,
        $jamKeluar,
        $diffSeconds,
        $statusStr
    ]);
}

fclose($output);
exit;
?>
