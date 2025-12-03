<?php
// Use absolute paths for includes
require_once __DIR__ . '/../backend/config/mpesa_config.php';
require_once __DIR__ . '/../backend/db.php';

echo "Checking M-Pesa payment status...\n\n";

$config = get_mpesa_config();
$consumer_key = $config['consumer_key'];
$consumer_secret = $config['consumer_secret'];
$base_url = $config['base_url'];

// Get access token
$credentials = base64_encode($consumer_key . ':' . $consumer_secret);
$url = $base_url . '/oauth/v1/generate?grant_type=client_credentials';

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Basic ' . $credentials]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);
$token_data = json_decode($response, true);

if (!$token_data || !isset($token_data['access_token'])) {
    die("[ERROR] Failed to get access token\n");
}

$access_token = $token_data['access_token'];
echo "[OK] Access token obtained\n";

// Get pending transactions
$pdo = get_pdo();
$stmt = $pdo->prepare("SELECT * FROM mpesa_transactions WHERE status = 'initiated'");
$stmt->execute();
$transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Found " . count($transactions) . " pending transactions\n\n";

foreach ($transactions as $transaction) {
    echo "Checking transaction: " . $transaction['checkout_request_id'] . "\n";
    
    // Check payment status
    $query_url = $base_url . '/mpesa/stkpushquery/v1/query';
    $query_data = [
        "BusinessShortCode" => $config['shortcode'],
        "Password" => base64_encode($config['shortcode'] . $config['passkey'] . date('YmdHis')),
        "Timestamp" => date('YmdHis'),
        "CheckoutRequestID" => $transaction['checkout_request_id']
    ];
    
    $ch = curl_init($query_url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $access_token,
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($query_data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "Status Code: $status_code\n";
    echo "Response: $response\n";
    
    $result = json_decode($response, true);
    
    if (isset($result['ResultCode'])) {
        $result_code = $result['ResultCode'];
        
        if ($result_code == '0') {
            // Payment successful
            echo "[OK] Payment successful!\n";
            
            // Update transaction
            $stmt = $pdo->prepare("UPDATE mpesa_transactions SET status = 'completed', result_code = ?, result_desc = ?, mpesa_receipt_number = ? WHERE id = ?");
            $stmt->execute([
                $result_code,
                $result['ResultDesc'] ?? 'Success',
                $result['MpesaReceiptNumber'] ?? 'N/A',
                $transaction['id']
            ]);
            
            // Update billing
            $stmt = $pdo->prepare("UPDATE billing SET status = 'paid', payment_method = 'mobile_money', payment_date = datetime('now') WHERE id = ?");
            $stmt->execute([$transaction['billing_id']]);
            
            echo "[OK] Bill updated to PAID\n";
        } else {
            // Payment failed
            echo "[ERROR] Payment failed: " . ($result['ResultDesc'] ?? 'Unknown error') . "\n";
            
            // Update transaction
            $stmt = $pdo->prepare("UPDATE mpesa_transactions SET status = 'failed', result_code = ?, result_desc = ? WHERE id = ?");
            $stmt->execute([$result_code, $result['ResultDesc'] ?? 'Failed', $transaction['id']]);
        }
    }
    
    echo "---\n";
}

echo "\n[OK] Payment status check completed!\n";
?>
