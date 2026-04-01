<?php
// Menghancurkan session
session_start();
session_unset();
session_destroy();
// Mengarahkan langsung ke login
header("Location: index.php");
exit;
?>
