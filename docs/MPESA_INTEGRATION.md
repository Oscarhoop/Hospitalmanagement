# M-Pesa Payment Integration Guide

## Overview

This Hospital Management System now supports **automated M-Pesa payments** using the Safaricom Daraja API. Patients can pay their bills directly from their mobile phones using STK Push (Lipa Na M-Pesa Online).

---

## Features Implemented

✅ **STK Push Payment** - Send payment prompts directly to patient phones  
✅ **Real-time Payment Verification** - Automatic bill status updates  
✅ **Transaction Tracking** - Complete audit trail of all M-Pesa transactions  
✅ **Payment Receipts** - M-Pesa receipt numbers stored with bills  
✅ **Status Polling** - Automatic payment confirmation  
✅ **Error Handling** - Comprehensive error messages and logging  
✅ **Security** - Proper authentication and authorization checks  

---

## Setup Instructions

### 1. Get Daraja API Credentials

1. Go to https://developer.safaricom.co.ke
2. Create an account or log in
3. Create a new app
4. Note down:
   - **Consumer Key**
   - **Consumer Secret**
   - **Passkey** (for STK Push)
   - **Shortcode** (Business number)

### 2. Configure Credentials

Set the following environment variables (Render Dashboard → Environment, `.env` file excluded from git, etc.). These are read automatically by `backend/config/mpesa_config.php`:

| Environment | Variable | Purpose |
|-------------|----------|---------|
| Sandbox | `MPESA_SANDBOX_CONSUMER_KEY` | Daraja consumer key |
| Sandbox | `MPESA_SANDBOX_CONSUMER_SECRET` | Daraja consumer secret |
| Sandbox | `MPESA_SANDBOX_PASSKEY` | STK push passkey |
| Sandbox | `MPESA_SANDBOX_SHORTCODE` *(optional)* | Defaults to 174379 |
| Sandbox | `MPESA_SANDBOX_INITIATOR_NAME` *(optional)* | Defaults to `testapi` |
| Sandbox | `MPESA_SANDBOX_INITIATOR_PASSWORD` | Initiator password |
| Sandbox | `MPESA_SANDBOX_CALLBACK_URL`, `MPESA_SANDBOX_TIMEOUT_URL`, `MPESA_SANDBOX_RESULT_URL` *(optional)* | Override URLs when needed |
| Production | `MPESA_PROD_CONSUMER_KEY` | Live credentials |
| Production | `MPESA_PROD_CONSUMER_SECRET` | Live credentials |
| Production | `MPESA_PROD_PASSKEY` | Live STK passkey |
| Production | `MPESA_PROD_SHORTCODE` | Your business shortcode |
| Production | `MPESA_PROD_INITIATOR_NAME` / `MPESA_PROD_INITIATOR_PASSWORD` | Live initiator creds |
| Production | `MPESA_PROD_CALLBACK_URL`, `MPESA_PROD_TIMEOUT_URL`, `MPESA_PROD_RESULT_URL` *(optional)* | HTTPS endpoints for callbacks |

> ⚠️ Do **not** commit real credentials. Store them only in environment variables or secret managers.

### 3. Set Environment

Set the `MPESA_ENVIRONMENT` environment variable (`sandbox` or `production`). Default is `sandbox` if the variable is undefined.

### 4. Configure Callback URL

**IMPORTANT**: For production, you need a **publicly accessible HTTPS URL** for M-Pesa callbacks.

Update in `mpesa_config.php`:

```php
'callback_url' => 'https://yourdomain.com/backend/api/mpesa_callback.php',
```

**For Development/Testing:**
- Use ngrok: `ngrok http 80`
- Use the ngrok URL: `https://your-ngrok-url.ngrok.io/Hospital-management/backend/api/mpesa_callback.php`

### 5. Database Migration

The database tables were already created when you ran:

```bash
php backend/migrations/add_mpesa_tables.php
```

**Tables created:**
- `mpesa_transactions` - All M-Pesa transaction records
- `mpesa_logs` - API request/response logs for debugging
- Updated `billing` table with M-Pesa columns

---

## How It Works

### Payment Flow

1. **Staff creates a bill** for a patient
2. **Patient or staff** clicks "Pay with M-Pesa" button
3. **Staff enters** patient's M-Pesa phone number
4. **System sends** STK Push request to Safaricom
5. **Patient receives** M-Pesa prompt on their phone
6. **Patient enters** M-Pesa PIN to confirm
7. **Safaricom processes** payment
8. **M-Pesa sends callback** to your system
9. **System automatically updates** bill status to "Paid"
10. **Receipt number saved** with the bill record

### Technical Flow

```
Frontend (app.js)
    ↓
Backend (mpesa.php) - Initiates STK Push
    ↓
Safaricom Daraja API - Sends STK to phone
    ↓
Patient Phone - Enters PIN
    ↓
Safaricom - Processes Payment
    ↓
Callback (mpesa_callback.php) - Receives confirmation
    ↓
Database - Updates billing & mpesa_transactions tables
    ↓
Frontend - Polls and displays success
```

---

## Usage Instructions

### For Staff Users

1. **Navigate to Billing section**
2. **Find unpaid bill** (Status: Pending)
3. **Click "Pay with M-Pesa"** button
4. **Enter patient's M-Pesa phone number**:
   - Format: `0712345678` or `254712345678`
   - Must be Safaricom number (07XX or 01XX)
5. **Click "Send Payment Request"**
6. **Patient checks their phone** for M-Pesa prompt
7. **Patient enters PIN** on their phone
8. **Wait for confirmation** (appears automatically in ~10 seconds)
9. **Bill automatically marked as "Paid"**

### Phone Number Formats Accepted

- `0712345678`
- `254712345678`
- `+254712345678`

---

## Testing with Sandbox

### Sandbox Test Credentials

**Test Phone Number:** `254708374149`  
**Test Amount:** Any amount works in sandbox  
**Test PIN:** `1234` (sandbox default)

### Test Scenarios

1. **Successful Payment**:
   - Use test phone number
   - Amount: KES 100
   - Enter PIN: `1234`
   - Result: Payment completes successfully

2. **Cancelled Payment**:
   - Use test phone number
   - When prompt appears, click "Cancel"
   - Result: Transaction marked as cancelled

3. **Timeout**:
   - Use test phone number
   - Ignore the prompt (don't enter PIN)
   - Result: Transaction times out after 60 seconds

---

## API Endpoints

### 1. Initiate Payment

**Endpoint:** `POST /backend/api/mpesa.php`

**Request:**
```json
{
    "action": "initiate_payment",
    "billing_id": 123,
    "phone_number": "0712345678"
}
```

**Response (Success):**
```json
{
    "success": true,
    "message": "Payment initiated successfully",
    "merchant_request_id": "29115-34620561-1",
    "checkout_request_id": "ws_CO_191220191020363925",
    "customer_message": "Success. Request accepted for processing"
}
```

### 2. Query Transaction Status

**Endpoint:** `POST /backend/api/mpesa.php`

**Request:**
```json
{
    "action": "query_status",
    "checkout_request_id": "ws_CO_191220191020363925"
}
```

**Response:**
```json
{
    "success": true,
    "data": {
        "ResultCode": "0",
        "ResultDesc": "The service request is processed successfully",
        "MpesaReceiptNumber": "NLJ7RT61SV"
    }
}
```

### 3. Get M-Pesa Transactions

**Endpoint:** `GET /backend/api/mpesa.php?billing_id=123`

**Response:**
```json
[
    {
        "id": 1,
        "billing_id": 123,
        "checkout_request_id": "ws_CO_191220191020363925",
        "mpesa_receipt_number": "NLJ7RT61SV",
        "phone_number": "254712345678",
        "amount": 5000.00,
        "status": "completed",
        "created_at": "2024-12-01 12:00:00"
    }
]
```

---

## Database Schema

### mpesa_transactions Table

| Column | Type | Description |
|--------|------|-------------|
| id | INTEGER | Primary key |
| billing_id | INTEGER | Foreign key to billing table |
| merchant_request_id | VARCHAR(100) | M-Pesa merchant request ID |
| checkout_request_id | VARCHAR(100) | M-Pesa checkout request ID |
| result_code | INTEGER | M-Pesa result code (0 = success) |
| mpesa_receipt_number | VARCHAR(100) | M-Pesa receipt/transaction ID |
| transaction_date | DATETIME | Payment date from M-Pesa |
| phone_number | VARCHAR(20) | Customer phone number |
| amount | DECIMAL(10,2) | Payment amount |
| status | TEXT | initiated, processing, completed, failed, cancelled |
| callback_data | TEXT | Full callback JSON for audit |
| created_at | TIMESTAMP | Record creation time |

### Updated billing Table Columns

| Column | Type | Description |
|--------|------|-------------|
| mpesa_checkout_request_id | VARCHAR(100) | Links to M-Pesa transaction |
| mpesa_transaction_id | VARCHAR(100) | M-Pesa merchant request ID |
| mpesa_receipt_number | VARCHAR(100) | M-Pesa receipt number |
| mpesa_phone_number | VARCHAR(20) | Phone used for payment |
| mpesa_amount | DECIMAL(10,2) | Amount paid via M-Pesa |
| transaction_status | TEXT | Current M-Pesa transaction status |
| mpesa_response_description | TEXT | M-Pesa response message |

---

## Troubleshooting

### Common Issues

#### 1. "Failed to generate access token"

**Cause:** Invalid Consumer Key or Consumer Secret

**Solution:**
- Verify credentials in `mpesa_config.php`
- Ensure using correct environment (sandbox vs production)
- Check credentials are active on Daraja portal

#### 2. "Invalid Access Token"

**Cause:** Expired token or wrong base URL

**Solution:**
- Token is auto-generated, check your internet connection
- Verify `base_url` matches environment (sandbox vs production)

#### 3. "Invalid Shortcode"

**Cause:** Wrong shortcode for environment

**Solution:**
- Sandbox: Use `174379`
- Production: Use your business shortcode

#### 4. "Callback URL not reachable"

**Cause:** M-Pesa cannot access your callback URL

**Solution:**
- Use HTTPS (required for production)
- URL must be publicly accessible
- For local dev, use ngrok or similar tunneling service
- Test callback URL in browser first

#### 5. Payment succeeds but bill not updated

**Cause:** Callback not being processed

**Solution:**
- Check `mpesa_logs` table for callback data
- Verify callback URL is correct
- Check PHP error logs
- Ensure database permissions are correct

### Debug Mode

Enable detailed logging by checking:

1. **M-Pesa Logs Table:**
```sql
SELECT * FROM mpesa_logs ORDER BY created_at DESC LIMIT 10;
```

2. **Transaction History:**
```sql
SELECT * FROM mpesa_transactions WHERE status != 'completed' ORDER BY created_at DESC;
```

3. **PHP Error Logs:**
Check your web server error logs for detailed error messages.

---

## Security Considerations

### Production Checklist

- [ ] Use environment variables for credentials (don't commit to git)
- [ ] Enable HTTPS on your domain
- [ ] Verify callback IP is from Safaricom
- [ ] Implement rate limiting on payment endpoints
- [ ] Set up monitoring/alerts for failed transactions
- [ ] Regular backup of `mpesa_transactions` table
- [ ] Rotate API credentials periodically
- [ ] Validate phone numbers before sending requests
- [ ] Log all transactions for audit purposes

### Gitignore

Add to `.gitignore`:
```
*.env
```

`backend/config/mpesa_config.php` is now safe to keep in git because it no longer stores raw credentials, but ensure `.env`/secret files remain private.

---

## Support & Resources

### Official Documentation
- **Daraja API Docs:** https://developer.safaricom.co.ke/docs
- **STK Push Guide:** https://developer.safaricom.co.ke/APIs/MpesaExpressSimulate

### Test Environment
- **Portal:** https://developer.safaricom.co.ke
- **Sandbox API:** https://sandbox.safaricom.co.ke

### Contact Safaricom
- **Email:** apisupport@safaricom.co.ke
- **Community:** https://developer.safaricom.co.ke/community

---

## File Structure

```
Hospital-management/
├── backend/
│   ├── config/
│   │   └── mpesa_config.php          # M-Pesa configuration
│   ├── api/
│   │   ├── mpesa.php                 # Main M-Pesa API handler
│   │   └── mpesa_callback.php        # Callback handler
│   └── migrations/
│       └── add_mpesa_tables.php      # Database migration
├── frontend/
│   ├── js/
│   │   └── app.js                    # M-Pesa payment functions
│   ├── css/
│   │   └── styles.css                # M-Pesa UI styles
│   └── index.html                    # M-Pesa payment modal
└── MPESA_INTEGRATION.md              # This file
```

---

## Future Enhancements

Potential features to add:

- [ ] B2C (Business to Customer) - Refunds/payouts
- [ ] C2B (Customer to Business) - Paybill integration
- [ ] Transaction reversal capability
- [ ] Bulk payment processing
- [ ] Payment reminders via SMS
- [ ] Multi-currency support
- [ ] Payment analytics dashboard
- [ ] Automated reconciliation reports

---

## License & Credits

This integration uses the **Safaricom Daraja API**.  
Developed for Mbagathi County Hospital Management System.

**Last Updated:** December 2024  
**Version:** 1.0.0
