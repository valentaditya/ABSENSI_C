<?php
require_once __DIR__ . '/config/database.php';
$pdo->exec("
CREATE TABLE IF NOT EXISTS `php_sessions` (
  `id` varchar(128) NOT NULL,
  `data` mediumtext NOT NULL,
  `timestamp` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");
echo "Sessions table created.";
