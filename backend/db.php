<?php
// db.php - helper to obtain a PDO instance based on config
$config = include __DIR__ . '/config.php';

function get_pdo() {
    static $pdo = null;
    if ($pdo) return $pdo;

    $cfg = include __DIR__ . '/config.php';
    $dsn = $cfg['dsn'];
    $user = $cfg['user'];
    $pass = $cfg['pass'];

    try {
        // Enable PDO error information
        error_reporting(E_ALL);
        ini_set('display_errors', 1);

        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        error_log('Database error: ' . $e->getMessage());
        echo json_encode(['error' => 'DB connection failed: ' . $e->getMessage()]);
        exit;
    }
    return $pdo;
}
