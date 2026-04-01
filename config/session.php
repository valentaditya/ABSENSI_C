<?php
require_once __DIR__ . '/database.php';

class DBSessionHandler implements SessionHandlerInterface {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    #[\ReturnTypeWillChange]
    public function open($path, $name) {
        return true;
    }
    
    #[\ReturnTypeWillChange]
    public function close() {
        return true;
    }
    
    #[\ReturnTypeWillChange]
    public function read($id) {
        $stmt = $this->pdo->prepare("SELECT data FROM php_sessions WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ? (string)$row['data'] : '';
    }
    
    #[\ReturnTypeWillChange]
    public function write($id, $data) {
        $stmt = $this->pdo->prepare("REPLACE INTO php_sessions (id, data, timestamp) VALUES (?, ?, ?)");
        return $stmt->execute([$id, $data, time()]);
    }
    
    #[\ReturnTypeWillChange]
    public function destroy($id) {
        $stmt = $this->pdo->prepare("DELETE FROM php_sessions WHERE id = ?");
        return $stmt->execute([$id]);
    }
    
    #[\ReturnTypeWillChange]
    public function gc($max_lifetime) {
        $stmt = $this->pdo->prepare("DELETE FROM php_sessions WHERE timestamp < ?");
        return $stmt->execute([time() - $max_lifetime]);
    }
}

$handler = new DBSessionHandler($pdo);
session_set_save_handler($handler, true);
session_start();
?>
