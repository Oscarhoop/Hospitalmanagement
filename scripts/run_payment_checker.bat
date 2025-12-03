@echo off
REM Helper wrapper for Task Scheduler to ensure correct working directory
cd /d "C:\Users\toxic\OneDrive\Desktop\Hospital-management\scripts"
"C:\xampp\php\php.exe" check_payment_status.php >> cron.log 2>&1
