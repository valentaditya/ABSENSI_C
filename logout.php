<?php
// Menghancurkan session
require_once __DIR__ . '/config/session.php';
session_unset();
session_destroy();
// Mengarahkan langsung ke login
header("Location: index.php");
exit;
?>
