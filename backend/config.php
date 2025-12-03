<?php
// Database configuration switched back to SQLite
// Allow overriding the database path via environment variable (useful on Render)
$dbPath = getenv('DB_PATH') ?: (__DIR__ . '/data/database.sqlite');

return [
    'dsn' => 'sqlite:' . $dbPath,
    'user' => null,
    'pass' => null,
];
