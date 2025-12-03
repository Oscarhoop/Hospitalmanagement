<?php
// Stop M-Pesa payment service
echo "Stopping M-Pesa payment service...\n";

// On Windows
if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
    exec('taskkill /F /IM php.exe', $output, $return_var);
}

echo "✅ Payment service stopped!\n";
echo "📝 Check the logs: mpesa_service.log\n";
?>
