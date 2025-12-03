@echo off
REM Setup cron job for automatic payment status checking (Windows equivalent using Task Scheduler)

set SCRIPT_DIR=%~dp0
set PHP_SCRIPT=%SCRIPT_DIR%check_payment_status.php
set LOG_FILE=%SCRIPT_DIR%cron.log
set TASK_NAME=HospitalPaymentChecker

echo Setting up scheduled task for payment status checking...
echo Script: %PHP_SCRIPT%
echo Log: %LOG_FILE%
echo Schedule: Every minute
echo.

REM Check if task already exists and remove it
schtasks /query /tn "%TASK_NAME%" >nul 2>&1
if %ERRORLEVEL% EQU 0 (
    echo ⚠️  Task already exists. Removing old task...
    schtasks /delete /tn "%TASK_NAME%" /f >nul 2>&1
)

REM Create new scheduled task
schtasks /create /tn "%TASK_NAME%" /tr "\"C:\xampp\php\php.exe\" \"%PHP_SCRIPT%\" >> \"%LOG_FILE%\" 2>&1" /sc minute /mo 1 /f

if %ERRORLEVEL% EQU 0 (
    echo ✅ Scheduled task created successfully!
    echo.
    echo To view current tasks: schtasks /query /tn "%TASK_NAME%"
    echo To view logs: type "%LOG_FILE%"
    echo To run manually: schtasks /run /tn "%TASK_NAME%"
    echo To remove this task: schtasks /delete /tn "%TASK_NAME%" /f
    echo.
    echo The system will now check payment status every minute automatically.
) else (
    echo ❌ Failed to create scheduled task.
    echo Please run this as Administrator.
)
