<?php
// Simulate a successful M-Pesa payment
require_once 'backend/db.php';

echo "Simulating successful M-Pesa payment...\n\n";

$pdo = get_pdo();

// Find a pending bill
$stmt = $pdo->prepare("SELECT * FROM billing WHERE status = 'pending' LIMIT 1");
$stmt->execute();
$bill = $stmt->fetch();

if (!$bill) {
    echo "❌ No pending bills found\n";
    echo "Creating a test bill...\n";
    
    // Create a test bill
    $stmt = $pdo->prepare("INSERT INTO billing (patient_id, amount, status, due_date) VALUES (?, ?, ?, ?)");
    $stmt->execute([1, 100, 'pending', date('Y-m-d')]);
    
    $bill_id = $pdo->lastInsertId();
    echo "✅ Created test bill with ID: $bill_id\n";
} else {
    echo "✅ Found pending bill ID: " . $bill['id'] . "\n";
    $bill_id = $bill['id'];
}

// Create a simulated M-Pesa transaction
$stmt = $pdo->prepare("INSERT INTO mpesa_transactions (billing_id, merchant_request_id, checkout_request_id, phone_number, amount, account_reference, transaction_desc, transaction_type, status, result_code, result_desc, mpesa_receipt_number, transaction_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
$stmt->execute([
    $bill_id,
    'TEST_MERCHANT_' . time(),
    'TEST_CHECKOUT_' . time(),
    '254708374149',
    100,
    'BILL-' . $bill_id,
    'Hospital Bill Payment',
    'STK_PUSH',
    'completed',
    '0',
    'Success',
    'TEST_RECEIPT_' . time(),
    date('Y-m-d H:i:s')
]);

// Update the bill to paid
$stmt = $pdo->prepare("UPDATE billing SET status = 'paid', payment_method = 'mobile_money', payment_date = datetime('now') WHERE id = ?");
$stmt->execute([$bill_id]);

echo "✅ Payment simulated successfully!\n";
echo "✅ Bill ID $bill_id marked as PAID\n";
echo "✅ M-Pesa transaction created\n";

// Verify
$stmt = $pdo->prepare("SELECT * FROM billing WHERE id = ?");
$stmt->execute([$bill_id]);
$updated_bill = $stmt->fetch();

echo "\nUpdated bill details:\n";
echo "Status: " . $updated_bill['status'] . "\n";
echo "Payment Method: " . $updated_bill['payment_method'] . "\n";
echo "Payment Date: " . $updated_bill['payment_date'] . "\n";
?>
