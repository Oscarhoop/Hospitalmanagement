<?php
// Auto-check M-Pesa payment status and update bills
require_once __DIR__ . '/../backend/config/mpesa_config.php';
require_once __DIR__ . '/../backend/db.php';

echo "Auto-checking M-Pesa payments...\n";
echo "Press Ctrl+C to stop\n\n";

$config = get_mpesa_config();
$consumer_key = $config['consumer_key'];
$consumer_secret = $config['consumer_secret'];
$base_url = $config['base_url'];

while (true) {
    try {
        // Get access token
        $credentials = base64_encode($consumer_key . ':' . $consumer_secret);
        $url = $base_url . '/oauth/v1/generate?grant_type=client_credentials';

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Basic ' . $credentials]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);

        $response = curl_exec($ch);
        $token_data = json_decode($response, true);

        if (!$token_data || !isset($token_data['access_token'])) {
            echo "❌ Failed to get access token, retrying...\n";
            sleep(5);
            continue;
        }

        $access_token = $token_data['access_token'];

        // Get pending transactions
        $pdo = get_pdo();
        $stmt = $pdo->prepare("SELECT * FROM mpesa_transactions WHERE status = 'initiated' ORDER BY created_at DESC LIMIT 3");
        $stmt->execute();
        $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($transactions)) {
            echo "No pending transactions to check\n";
        } else {
            echo "Checking " . count($transactions) . " pending transactions\n";
        }

        foreach ($transactions as $transaction) {
            echo "Checking: " . $transaction['checkout_request_id'] . "\n";

            // Check payment status
            $query_url = $base_url . '/mpesa/stkpushquery/v1/query';
            $timestamp = date('YmdHis');
            $password = base64_encode($config['shortcode'] . $config['passkey'] . $timestamp);
            
            $query_data = [
                "BusinessShortCode" => $config['shortcode'],
                "Password" => $password,
                "Timestamp" => $timestamp,
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
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            
            $response = curl_exec($ch);
            $status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($status_code == 200) {
                $result = json_decode($response, true);
                
                if (isset($result['ResultCode'])) {
                    $result_code = $result['ResultCode'];
                    
                    if ($result_code == '0') {
                        // Payment successful
                        echo "✅ PAYMENT SUCCESSFUL!\n";
                        
                        // Update transaction
                        $stmt = $pdo->prepare("UPDATE mpesa_transactions SET status = 'completed', result_code = ?, result_desc = ?, mpesa_receipt_number = ?, transaction_date = datetime('now') WHERE id = ?");
                        $stmt->execute([
                            $result_code,
                            $result['ResultDesc'] ?? 'Success',
                            $result['MpesaReceiptNumber'] ?? 'N/A',
                            $transaction['id']
                        ]);
                        
                        // Update billing
                        $stmt = $pdo->prepare("UPDATE billing SET status = 'paid', payment_method = 'mobile_money', payment_date = datetime('now') WHERE id = ?");
                        $stmt->execute([$transaction['billing_id']]);
                        
                        echo "🎉 Bill ID " . $transaction['billing_id'] . " marked as PAID!\n";
                        
                    } elseif ($result_code == '1032') {
                        echo "❌ User cancelled payment\n";
                        $stmt = $pdo->prepare("UPDATE mpesa_transactions SET status = 'cancelled', result_code = ?, result_desc = ? WHERE id = ?");
                        $stmt->execute([$result_code, $result['ResultDesc'] ?? 'Cancelled', $transaction['id']]);
                        
                    } elseif ($result_code == '1037') {
                        echo "⏳ No response from user\n";
                        // Keep as initiated for now
                        
                    } else {
                        echo "❌ Payment failed: " . ($result['ResultDesc'] ?? 'Unknown') . "\n";
                        $stmt = $pdo->prepare("UPDATE mpesa_transactions SET status = 'failed', result_code = ?, result_desc = ? WHERE id = ?");
                        $stmt->execute([$result_code, $result['ResultDesc'] ?? 'Failed', $transaction['id']]);
                    }
                }
            } else {
                echo "⚠️ Status check failed: $status_code\n";
            }
        }
        
        echo "Waiting 10 seconds before next check...\n";
        sleep(10);
        
    } catch (Exception $e) {
        echo "❌ Error: " . $e->getMessage() . "\n";
        sleep(10);
    }
}
?>
