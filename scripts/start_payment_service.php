<?php
// Start M-Pesa payment service as background process
echo "Starting M-Pesa payment service...\n";

$command = 'php auto_check_payments.php > mpesa_service.log 2>&1 &';

// On Windows
if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
    $command = 'start /B php auto_check_payments.php > mpesa_service.log 2>&1';
}

// Start the background process
exec($command, $output, $return_var);

if ($return_var === 0) {
    echo "✅ M-Pesa payment service started in background!\n";
    echo "📝 Logs are being written to: mpesa_service.log\n";
    echo "🔄 The service will check payments every 10 seconds\n";
    echo "⏹️ To stop: Close the terminal or run: taskkill /F /IM php.exe\n";
} else {
    echo "❌ Failed to start payment service\n";
}
?>
