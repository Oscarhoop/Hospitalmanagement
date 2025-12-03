# Automatic Payment Status Checking Setup

This setup enables automatic billing status updates every minute using system scheduling.

## Files Created

- `setup_cron.sh` - For Linux/Mac systems
- `setup_cron.bat` - For Windows systems  
- `check_payment_status.php` - The payment checking script

## Setup Instructions

### For Windows (Recommended for XAMPP)

1. **Run as Administrator:**
   ```cmd
   Right-click on setup_cron.bat → "Run as administrator"
   ```

2. **Verify Installation:**
   ```cmd
   schtasks /query /tn "HospitalPaymentChecker"
   ```

3. **Check Logs:**
   ```cmd
   type scripts\cron.log
   ```

### For Linux/Mac

1. **Make executable:**
   ```bash
   chmod +x setup_cron.sh
   ```

2. **Run setup:**
   ```bash
   ./setup_cron.sh
   ```

3. **Verify installation:**
   ```bash
   crontab -l
   ```

4. **Check logs:**
   ```bash
   tail -f scripts/cron.log
   ```

## What It Does

- **Schedule:** Runs every minute
- **Action:** Checks pending M-Pesa transactions
- **Updates:** Marks bills as "paid" when payments complete
- **Logging:** All activity logged to `scripts/cron.log`

## Testing

To test immediately without waiting for the scheduler:

```cmd
# Windows
cd c:\xampp\htdocs\Hospital-management\scripts
C:\xampp\php\php.exe check_payment_status.php

# Linux/Mac  
cd /path/to/Hospital-management/scripts
php check_payment_status.php
```

## Troubleshooting

### Windows Issues
- Run Command Prompt as Administrator
- Ensure XAMPP PHP path is correct: `C:\xampp\php\php.exe`
- Check Task Scheduler for the task status

### Linux/Mac Issues
- Ensure PHP is in PATH: `which php`
- Check cron service: `sudo systemctl status cron`
- Verify script permissions: `ls -la check_payment_status.php`

## Removing the Automation

### Windows
```cmd
schtasks /delete /tn "HospitalPaymentChecker" /f
```

### Linux/Mac
```bash
crontab -e  # Then delete the line
```

## Benefits

✅ **Automatic** - No manual execution needed  
✅ **Persistent** - Survives reboots  
✅ **Efficient** - Runs only when needed  
✅ **Logged** - Complete audit trail  
✅ **Reliable** - System-level scheduling
