#!/bin/bash
# Setup cron job for automatic payment status checking

# Get the absolute path to the script
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PHP_SCRIPT="$SCRIPT_DIR/check_payment_status.php"
LOG_FILE="$SCRIPT_DIR/cron.log"

# Create cron job entry
CRON_ENTRY="* * * * * /usr/bin/php $PHP_SCRIPT >> $LOG_FILE 2>&1"

echo "Setting up cron job for payment status checking..."
echo "Script: $PHP_SCRIPT"
echo "Log: $LOG_FILE"
echo "Schedule: Every minute"
echo ""

# Check if cron job already exists
if crontab -l 2>/dev/null | grep -F "$PHP_SCRIPT"; then
    echo "⚠️  Cron job already exists. Removing old entry..."
    crontab -l 2>/dev/null | grep -v -F "$PHP_SCRIPT" | crontab -
fi

# Add new cron job
(crontab -l 2>/dev/null; echo "$CRON_ENTRY") | crontab -

echo "✅ Cron job installed successfully!"
echo ""
echo "To view current cron jobs: crontab -l"
echo "To view logs: tail -f $LOG_FILE"
echo "To remove this cron job: crontab -e (then delete the line)"
echo ""
echo "The system will now check payment status every minute automatically."
