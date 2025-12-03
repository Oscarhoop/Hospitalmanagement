<?php
// Check paid bills
require_once 'backend/db.php';

try {
    $pdo = get_pdo();
    $stmt = $pdo->query('SELECT * FROM billing WHERE status = "paid"');
    $bills = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Paid Bills:\n";
    print_r($bills);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
