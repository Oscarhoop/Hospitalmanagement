<?php
require __DIR__ . '/../backend/db.php';

$pdo = get_pdo();
$testCheckoutIds = [
    'test_1764596927',
    'test_1764597111',
];

$placeholders = implode(',', array_fill(0, count($testCheckoutIds), '?'));
$stmt = $pdo->prepare("DELETE FROM mpesa_transactions WHERE checkout_request_id IN ($placeholders)");
$stmt->execute($testCheckoutIds);

$deleted = $stmt->rowCount();
echo "Removed $deleted test transaction(s).\n";
