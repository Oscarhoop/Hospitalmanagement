<?php
require __DIR__ . '/../backend/db.php';

$pdo = get_pdo();

echo "Billing table columns:\n";
$stmt = $pdo->query('PRAGMA table_info(billing)');
foreach ($stmt as $row) {
    echo json_encode($row, JSON_PRETTY_PRINT), "\n";
}

echo "\nPending billing rows (status != \"paid\"):\n";
$stmt = $pdo->query("SELECT id, status, payment_method, created_at FROM billing WHERE status != 'paid' ORDER BY id DESC LIMIT 10");
foreach ($stmt as $row) {
    echo json_encode($row, JSON_PRETTY_PRINT), "\n";
}

echo "\nmpesa_transactions table columns:\n";
$stmt = $pdo->query('PRAGMA table_info(mpesa_transactions)');
foreach ($stmt as $row) {
    echo json_encode($row, JSON_PRETTY_PRINT), "\n";
}

echo "\nPending mpesa_transactions (status=\"initiated\"):\n";
$stmt = $pdo->query("SELECT id, billing_id, checkout_request_id, merchant_request_id, status, result_code, created_at FROM mpesa_transactions WHERE status = 'initiated' ORDER BY id DESC LIMIT 10");
foreach ($stmt as $row) {
    echo json_encode($row, JSON_PRETTY_PRINT), "\n";
}
