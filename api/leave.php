<?php
header('Content-Type: application/json');
require_once '../config/config.php';
require_once '../config/database.php';

// Validasi Header API KEY
$headers = apache_request_headers();
$provided_key = '';
if (isset($headers['App-Api-Key'])) {
    $provided_key = $headers['App-Api-Key'];
} elseif (isset($_SERVER['HTTP_APP_API_KEY'])) {
    $provided_key = $_SERVER['HTTP_APP_API_KEY'];
}

if ($provided_key !== API_KEY) {
    http_response_code(401);
    echo json_encode(['error' => true, 'message' => 'Akses ditolak: API KEY tidak valid.']);
    exit;
}

// Tangkap JSON
$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (!isset($data['userId'])) {
    http_response_code(400);
    echo json_encode(['error' => true, 'message' => 'Data tidak lengkap (userId hilang).']);
    exit;
}

$userId = (int) $data['userId'];
$leaveTime = date('Y-m-d H:i:s'); // Waktu server saat request diterima

try {
    // Update data: cari sesi join_time terakhir milik userId tersebut yang belum tercatat leave_time nya.
    $sql = "UPDATE attendances 
            SET leave_time = :leaveTime 
            WHERE userId = :userId AND leave_time IS NULL 
            ORDER BY join_time DESC 
            LIMIT 1";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':leaveTime' => $leaveTime,
        ':userId' => $userId
    ]);

    // Berhasil update walau bisa saja "0 row affected" jika player leave tapi datanya tidak ada yang NULL (disconnect spam misalnya)
    // Tapi kita catat berhasil
    http_response_code(200);
    echo json_encode([
        'error' => false,
        'message' => 'Data leave berhasil diupdate.',
        'data' => [
            'userId' => $userId,
            'leave_time' => $leaveTime
        ]
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => true, 'message' => 'Terjadi kesalahan sistem: ' . $e->getMessage()]);
}
?>
