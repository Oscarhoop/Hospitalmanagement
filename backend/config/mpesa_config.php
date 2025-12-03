<?php
/**
 * M-Pesa Daraja API Configuration
 * 
 * SECURITY NOTE: In production, use environment variables or .env file
 * Never commit credentials to version control
 */

// Helper to read env vars with fallback
if (!function_exists('mpesa_env')) {
    function mpesa_env(string $key, $default = null) {
        $value = getenv($key);
        return $value !== false ? $value : $default;
    }
}

// Environment: 'sandbox' or 'production'
if (!defined('MPESA_ENVIRONMENT')) {
    define('MPESA_ENVIRONMENT', mpesa_env('MPESA_ENVIRONMENT', 'sandbox'));
}

// Sandbox Configuration (for testing)
$mpesa_config_sandbox = [
    'consumer_key' => mpesa_env('MPESA_SANDBOX_CONSUMER_KEY'),
    'consumer_secret' => mpesa_env('MPESA_SANDBOX_CONSUMER_SECRET'),
    'passkey' => mpesa_env('MPESA_SANDBOX_PASSKEY'),
    'shortcode' => mpesa_env('MPESA_SANDBOX_SHORTCODE', '174379'),
    'initiator_name' => mpesa_env('MPESA_SANDBOX_INITIATOR_NAME', 'testapi'),
    'initiator_password' => mpesa_env('MPESA_SANDBOX_INITIATOR_PASSWORD'),
    'base_url' => mpesa_env('MPESA_SANDBOX_BASE_URL', 'https://sandbox.safaricom.co.ke'),
    'callback_url' => mpesa_env('MPESA_SANDBOX_CALLBACK_URL', 'https://example.com/backend/api/mpesa_callback.php'),
    'timeout_url' => mpesa_env('MPESA_SANDBOX_TIMEOUT_URL', 'https://example.com/backend/api/mpesa_timeout.php'),
    'result_url' => mpesa_env('MPESA_SANDBOX_RESULT_URL', 'https://example.com/backend/api/mpesa_result.php'),
];

// Production Configuration (for live transactions)
$mpesa_config_production = [
    'consumer_key' => mpesa_env('MPESA_PROD_CONSUMER_KEY'),
    'consumer_secret' => mpesa_env('MPESA_PROD_CONSUMER_SECRET'),
    'passkey' => mpesa_env('MPESA_PROD_PASSKEY'),
    'shortcode' => mpesa_env('MPESA_PROD_SHORTCODE'),
    'initiator_name' => mpesa_env('MPESA_PROD_INITIATOR_NAME'),
    'initiator_password' => mpesa_env('MPESA_PROD_INITIATOR_PASSWORD'),
    'base_url' => mpesa_env('MPESA_PROD_BASE_URL', 'https://api.safaricom.co.ke'),
    'callback_url' => mpesa_env('MPESA_PROD_CALLBACK_URL', 'https://example.com/backend/api/mpesa_callback.php'),
    'timeout_url' => mpesa_env('MPESA_PROD_TIMEOUT_URL', 'https://example.com/backend/api/mpesa_timeout.php'),
    'result_url' => mpesa_env('MPESA_PROD_RESULT_URL', 'https://example.com/backend/api/mpesa_result.php'),
];

// Select active configuration
$mpesa_config = MPESA_ENVIRONMENT === 'production'
    ? $mpesa_config_production
    : $mpesa_config_sandbox;

// Helper function to get config
function get_mpesa_config($key = null) {
    global $mpesa_config;
    
    if ($key === null) {
        return $mpesa_config;
    }
    
    return $mpesa_config[$key] ?? null;
}

// Transaction Types
define('MPESA_TRANSACTION_TYPE_PAYBILL', 'CustomerPayBillOnline');
define('MPESA_TRANSACTION_TYPE_BUY_GOODS', 'CustomerBuyGoodsOnline');

// Command IDs
define('MPESA_COMMAND_TRANSACTION_STATUS', 'TransactionStatusQuery');
define('MPESA_COMMAND_ACCOUNT_BALANCE', 'AccountBalance');
define('MPESA_COMMAND_REVERSAL', 'TransactionReversal');

return $mpesa_config;
