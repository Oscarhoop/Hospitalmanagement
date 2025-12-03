<?php
// Start Hospital Management System with M-Pesa service
echo "🏥 Starting Hospital Management System...\n";

// Start M-Pesa payment service
echo "📱 Starting M-Pesa payment service...\n";
$command = 'start /B php auto_check_payments.php > mpesa_service.log 2>&1';
exec($command, $output, $return_var);

if ($return_var === 0) {
    echo "✅ M-Pesa service started!\n";
} else {
    echo "⚠️ M-Pesa service may not have started properly\n";
}

// Start web server
echo "🌐 Starting web server...\n";
echo "📍 URL: http://localhost:8000/frontend/\n";
echo "⏹️ Press Ctrl+C to stop all services\n\n";

// Start PHP server (this will block)
exec('php -S localhost:8000', $output, $return_var);
?>
