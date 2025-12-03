<?php
/**
 * M-Pesa STK Push Callback Handler
 * Receives payment confirmations from Safaricom
 */

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../config/mpesa_config.php';

// Get callback data
$callback_data = file_get_contents('php://input');
$callback_json = json_decode($callback_data, true);

// Log the raw callback for debugging
error_log('M-Pesa Callback Received: ' . $callback_data);

try {
    $pdo = get_pdo();
    
    // Log to mpesa_logs table
    $stmt = $pdo->prepare('
        INSERT INTO mpesa_logs (request_type, response_data, ip_address)
        VALUES (?, ?, ?)
    ');
    $stmt->execute([
        'stk_callback',
        $callback_data,
        $_SERVER['REMOTE_ADDR'] ?? null
    ]);
    
    // Extract callback data
    if (!isset($callback_json['Body']['stkCallback'])) {
        error_log('Invalid callback format');
        echo json_encode(['ResultCode' => 1, 'ResultDesc' => 'Invalid callback format']);
        exit;
    }
    
    $callback = $callback_json['Body']['stkCallback'];
    $merchant_request_id = $callback['MerchantRequestID'] ?? null;
    $checkout_request_id = $callback['CheckoutRequestID'] ?? null;
    $result_code = $callback['ResultCode'] ?? null;
    $result_desc = $callback['ResultDesc'] ?? null;
    
    if (!$checkout_request_id) {
        error_log('Missing checkout request ID');
        echo json_encode(['ResultCode' => 1, 'ResultDesc' => 'Missing checkout request ID']);
        exit;
    }
    
    // Find the transaction
    $stmt = $pdo->prepare('
        SELECT * FROM mpesa_transactions
        WHERE checkout_request_id = ?
        ORDER BY created_at DESC
        LIMIT 1
    ');
    $stmt->execute([$checkout_request_id]);
    $transaction = $stmt->fetch();
    
    if (!$transaction) {
        error_log('Transaction not found for checkout_request_id: ' . $checkout_request_id);
        echo json_encode(['ResultCode' => 0, 'ResultDesc' => 'Accepted']);
        exit;
    }
    
    // Process based on result code
    // 0 = Success, anything else = Failure
    if ($result_code == 0) {
        // Success - extract payment details
        $callback_metadata = $callback['CallbackMetadata']['Item'] ?? [];
        
        $mpesa_receipt_number = null;
        $transaction_date = null;
        $amount = null;
        $phone_number = null;
        
        foreach ($callback_metadata as $item) {
            $name = $item['Name'] ?? '';
            $value = $item['Value'] ?? null;
            
            switch ($name) {
                case 'MpesaReceiptNumber':
                    $mpesa_receipt_number = $value;
                    break;
                case 'TransactionDate':
                    // Format: YYYYMMDDHHmmss to YYYY-MM-DD HH:mm:ss
                    if ($value) {
                        $year = substr($value, 0, 4);
                        $month = substr($value, 4, 2);
                        $day = substr($value, 6, 2);
                        $hour = substr($value, 8, 2);
                        $minute = substr($value, 10, 2);
                        $second = substr($value, 12, 2);
                        $transaction_date = "$year-$month-$day $hour:$minute:$second";
                    }
                    break;
                case 'Amount':
                    $amount = $value;
                    break;
                case 'PhoneNumber':
                    $phone_number = $value;
                    break;
            }
        }
        
        // Update mpesa_transactions table
        $stmt = $pdo->prepare('
            UPDATE mpesa_transactions SET
                merchant_request_id = ?,
                result_code = ?,
                result_desc = ?,
                mpesa_receipt_number = ?,
                transaction_date = ?,
                status = ?,
                callback_data = ?,
                updated_at = CURRENT_TIMESTAMP
            WHERE checkout_request_id = ?
        ');
        $stmt->execute([
            $merchant_request_id,
            $result_code,
            $result_desc,
            $mpesa_receipt_number,
            $transaction_date,
            'completed',
            $callback_data,
            $checkout_request_id
        ]);
        
        // Update billing table
        if ($transaction['billing_id']) {
            $stmt = $pdo->prepare('
                UPDATE billing SET
                    status = ?,
                    payment_method = ?,
                    payment_date = ?,
                    mpesa_transaction_id = ?,
                    mpesa_receipt_number = ?,
                    transaction_status = ?,
                    mpesa_response_description = ?,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = ?
            ');
            $stmt->execute([
                'paid',
                'mpesa',
                $transaction_date,
                $merchant_request_id,
                $mpesa_receipt_number,
                'completed',
                $result_desc,
                $transaction['billing_id']
            ]);
            
            error_log("Bill #{$transaction['billing_id']} marked as paid. M-Pesa Receipt: $mpesa_receipt_number");
        }
        
    } else {
        // Payment failed or cancelled
        $status = $result_code == 1032 ? 'cancelled' : 'failed';
        
        // Update mpesa_transactions table
        $stmt = $pdo->prepare('
            UPDATE mpesa_transactions SET
                merchant_request_id = ?,
                result_code = ?,
                result_desc = ?,
                status = ?,
                callback_data = ?,
                updated_at = CURRENT_TIMESTAMP
            WHERE checkout_request_id = ?
        ');
        $stmt->execute([
            $merchant_request_id,
            $result_code,
            $result_desc,
            $status,
            $callback_data,
            $checkout_request_id
        ]);
        
        // Update billing table
        if ($transaction['billing_id']) {
            $stmt = $pdo->prepare('
                UPDATE billing SET
                    transaction_status = ?,
                    mpesa_response_description = ?,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = ?
            ');
            $stmt->execute([
                $status,
                $result_desc,
                $transaction['billing_id']
            ]);
        }
        
        error_log("Payment failed for Bill #{$transaction['billing_id']}. Result: $result_desc");
    }
    
    // Respond to M-Pesa
    echo json_encode(['ResultCode' => 0, 'ResultDesc' => 'Accepted']);
    
} catch (Exception $e) {
    error_log('M-Pesa Callback Error: ' . $e->getMessage());
    echo json_encode(['ResultCode' => 1, 'ResultDesc' => 'Internal server error']);
}
?>
