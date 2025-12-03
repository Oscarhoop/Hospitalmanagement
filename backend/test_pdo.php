<?php
header('Content-Type: text/plain');
require_once __DIR__ . '/db.php';

echo "Loaded php.ini: " . php_ini_loaded_file() . "\n";

echo "Attempting PDO connection...\n";
try {
    $pdo = get_pdo();
    echo "Success! PDO driver: " . $pdo->getAttribute(PDO::ATTR_DRIVER_NAME) . "\n";
} catch (Exception $ex) {
    echo "FAILED: " . $ex->getMessage() . "\n";
}
