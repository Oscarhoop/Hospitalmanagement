# M-Pesa Rate Limiting Explained

## 🚦 What is Rate Limiting?

M-Pesa API limits how many requests you can make in a time period to prevent abuse and ensure fair usage.

## 📊 Current Limits

### Sandbox Environment
- **5 requests per minute**
- **300 requests per hour**
- **7200 requests per day**

### Production Environment
- **No rate limiting** (unlimited requests)
- **Only limited by your business tier**

## ⏱️ How It Affects Your System

### Auto-Checker Behavior
- **Normal operation:** Every 10 seconds
- **When rate limited:** Gets 403 Forbidden error
- **Recovery:** Waits longer between attempts

### Error Messages
```
Status Code: 403
Response: Incapsula incident ID (rate limit block)
```

## 🔄 Workarounds

### 1. Exponential Backoff
Your auto-checker uses this strategy:
- First failure: Wait 2 seconds
- Second failure: Wait 4 seconds  
- Third failure: Wait 8 seconds
- Maximum wait: 60 seconds

### 2. Manual Checking
Use when rate limited:
```bash
php check_payment_status.php
```

### 3. Simulation
For testing without API calls:
```bash
php simulate_payment.php
```

## 🚀 Solutions

### Short-Term
- Wait 1 hour for rate limit reset
- Use manual checks
- Simulate payments

### Long-Term
- Get production credentials
- No rate limiting
- Real payments

## 📈 Rate Limit Reset Times

Sandbox rate limits reset:
- **Every hour** on the hour
- **Daily** at midnight
- **Weekly** on Monday

## 🎯 Best Practices

1. **Don't spam the API** - respect limits
2. **Use exponential backoff** - handle failures gracefully
3. **Cache responses** - reduce unnecessary calls
4. **Monitor usage** - track request patterns
5. **Go production** - eliminate limits entirely

## 🔍 Monitor Rate Limit Status

Check your current status:
```bash
# Check if rate limited
php test_mpesa_token.php

# Monitor auto-checker logs
Get-Content -Path "mpesa_service.log" -Wait
```

## 💡 Pro Tips

- **Batch operations** - check multiple transactions at once
- **Smart scheduling** - avoid peak times
- **Fallback mechanisms** - manual checks when auto fails
- **Production upgrade** - best long-term solution
?>
