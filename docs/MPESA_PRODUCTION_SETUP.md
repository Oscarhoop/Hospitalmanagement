# M-Pesa Production Setup Guide

## 🚀 Get Production Credentials

### Step 1: Go to M-Pesa Developer Portal
1. Visit: https://developer.safaricom.co.ke
2. Login with your account
3. Go to "My Apps" section

### Step 2: Create Production App
1. Click "Add New App"
2. Fill in app details:
   - App Name: "Hospital Management System"
   - Description: "Hospital billing and payments"
   - Callback URL: `https://yourdomain.com/backend/api/mpesa_callback.php`

### Step 3: Request Production Access
1. Select your app
2. Click "Request Production Access"
3. Fill in business details:
   - Business registration documents
   - Contact information
   - Use case description

### Step 4: Wait for Approval
- Usually takes 1-3 business days
- M-Pesa team will review your application
- You'll receive email when approved

### Step 5: Update Configuration
Once approved, update `backend/config/mpesa_config.php`:

```php
// Change from sandbox to production
'environment' => 'production',

// Update with production credentials
'consumer_key' => 'YOUR_PRODUCTION_CONSUMER_KEY',
'consumer_secret' => 'YOUR_PRODUCTION_CONSUMER_SECRET',
'base_url' => 'https://api.safaricom.co.ke',

// Use your real callback URL
'callback_url' => 'https://yourdomain.com/backend/api/mpesa_callback.php',
```

## 📋 Production Benefits
- ✅ No rate limiting (5 requests/minute → Unlimited)
- ✅ Real payments (not test)
- ✅ Production shortcode
- ✅ Real customer experience

## ⚠️ Important Notes
- Test thoroughly in sandbox first
- Ensure SSL certificate on your domain
- Set up proper callback handling
- Monitor transaction logs

## 🔄 Testing in Production
- Use small amounts (1 KES) for testing
- Keep sandbox for development
- Use production only for final testing
- Monitor all transactions carefully
?>
