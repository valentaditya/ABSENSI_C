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
    http_response_code(401); // Unauthorized
    echo json_encode(['error' => true, 'message' => 'Akses ditolak: API KEY tidak valid.']);
    exit;
}

// Menangkap Data JSON dari Request Body (Lua HTTPService mengirimkan JSON)
$json = file_get_contents('php://input');
$data = json_decode($json, true);

// Validasi input data
if (!isset($data['userId']) || !isset($data['username'])) {
    http_response_code(400); // Bad Request
    echo json_encode(['error' => true, 'message' => 'Data tidak lengkap (userId atau username hilang).']);
    exit;
}

$userId = (int) $data['userId'];
// Sanitasi basic (PDO statement sudah aman, namun lebih baik untuk clean string)
$username = htmlspecialchars(strip_tags($data['username']));

// Catat waktu masuk (Server Time)
$joinTime = date('Y-m-d H:i:s');

try {
    // Insert data baru ke tabel attendances
    $sql = "INSERT INTO attendances (userId, username, join_time) VALUES (:userId, :username, :joinTime)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':userId' => $userId,
        ':username' => $username,
        ':joinTime' => $joinTime
    ]);

    http_response_code(200);
    echo json_encode([
        'error' => false,
        'message' => 'Data join berhasil dicatat.',
        'data' => [
            'userId' => $userId,
            'username' => $username,
            'join_time' => $joinTime
        ]
    ]);
} catch (PDOException $e) {
    http_response_code(500); // Internal Server Error
    echo json_encode(['error' => true, 'message' => 'Terjadi kesalahan sistem: ' . $e->getMessage()]);
}
?>
