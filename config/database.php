<?php
// Sembunyikan warning deprecation dari PHP 8.4 agar tidak merusak layout HTML atau JSON Response
error_reporting(E_ALL & ~E_DEPRECATED);

// Pengaturan Koneksi Database (Mendukung Vercel & TiDB Cloud)
$db_host = getenv('DB_HOST') ?: 'gateway01.ap-southeast-1.prod.aws.tidbcloud.com';
$db_user = getenv('DB_USER') ?: '2xRNcJKfCW3yKva.root';
$db_pass = getenv('DB_PASS') ?: 'VYc2KmvqUHPSZovT'; // Password TiDB Anda
$db_name = getenv('DB_NAME') ?: 'absensi'; 
$db_port = getenv('DB_PORT') ?: '4000'; 

try {
    // Membuat koneksi menggunakan PDO, menambahkan param port
    $dsn = "mysql:host=$db_host;port=$db_port;dbname=$db_name;charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    
    // TiDB Cloud mewajibkan koneksi SSL
    if (strpos($db_host, 'tidbcloud') !== false) {
        // Fallback untuk Vercel PHP 8.4+ dan localhost
        $attrVerify = defined('Pdo\Mysql::ATTR_SSL_VERIFY_SERVER_CERT') ? constant('Pdo\Mysql::ATTR_SSL_VERIFY_SERVER_CERT') : @constant('PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT');
        $attrCa = defined('Pdo\Mysql::ATTR_SSL_CA') ? constant('Pdo\Mysql::ATTR_SSL_CA') : @constant('PDO::MYSQL_ATTR_SSL_CA');
        
        $options[$attrVerify] = true;
        $options[$attrCa] = __DIR__ . '/cacert.pem'; // File Sertifikat CA
    }

    $pdo = new PDO($dsn, $db_user, $db_pass, $options);
} catch (PDOException $e) {
    // Jika koneksi gagal
    die(json_encode([
        'error' => true,
        'message' => 'Koneksi database gagal: ' . $e->getMessage()
    ]));
}
?>
