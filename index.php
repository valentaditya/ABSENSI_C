<?php
require_once __DIR__ . '/config/session.php';
// Jika sudah admin (sudah login), skip login page
if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true) {
    header('Location: dashboard.php');
    exit;
}

require_once 'config/database.php';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Basic sanitization
    $usernameInput = trim($_POST['username'] ?? '');
    $passwordInput = trim($_POST['password'] ?? '');

    // Cek dengan credential di database
    $stmt = $pdo->prepare("SELECT id, username, password, role FROM system_users WHERE username = ?");
    $stmt->execute([$usernameInput]);
    $user = $stmt->fetch();

    if ($user && password_verify($passwordInput, $user['password'])) {
        // Set sesi login
        $_SESSION['is_admin'] = true;
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        
        // Log aktivitas login ke database login_logs
        $userIp = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
        $logStmt = $pdo->prepare("INSERT INTO login_logs (username, ip_address) VALUES (?, ?)");
        $logStmt->execute([$user['username'], $userIp]);

        // Redirect ke admin dashboard
        header('Location: dashboard.php');
        exit;
    } else {
        $error = 'Username atau Password salah.';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Admin Absensi Roblox</title>
    <!-- Google Fonts for Modern Typography -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="login-body">
    <div class="login-container">
        <div class="login-box">
            <h2>Admin Login</h2>
            <p class="subtitle">Playtime Player Roblox</p>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" required autocomplete="off" placeholder="Masukkan username admin">
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required placeholder="Masukkan password admin">
                </div>
                <button type="submit" class="btn btn-primary btn-block">Masuk</button>
            </form>
        </div>
    </div>
</body>
</html>
