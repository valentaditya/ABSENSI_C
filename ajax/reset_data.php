<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'OWNER') {
    http_response_code(403);
    exit(json_encode(['error' => 'Forbidden']));
}

require_once '../config/database.php';

// Hanya menerima POST untuk keamanan extra
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Kosongkan tabel (TRUNCATE mereset auto increment ke 1 juga)
        $pdo->exec("TRUNCATE TABLE attendances");
        
        echo json_encode(['success' => true, 'message' => 'Semua data telah direset.']);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Gagal mereset data: ' . $e->getMessage()]);
    }
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
}
?>
