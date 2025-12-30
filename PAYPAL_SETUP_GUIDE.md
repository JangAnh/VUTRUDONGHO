# PayPal Integration Setup Guide

## Problem
You're getting a 400 error when loading the PayPal SDK:
```
Failed to load resource: the server responded with a status of 400 ()
Cannot load PayPal SDK
```

## Root Cause
The PayPal Client ID in `payment.php` is either:
1. **Invalid or expired**
2. **Not properly configured in your PayPal account**
3. **Using a sandbox ID when it should be live (or vice versa)**

## Solution Steps

### Step 1: Create a PayPal Developer Account
1. Go to https://developer.paypal.com/
2. Sign up or log in with your PayPal account
3. Accept the developer agreement

### Step 2: Create a Sandbox Application (for testing)
1. In the Dashboard, go to **Apps & Credentials**
2. Make sure you're on the **Sandbox** tab
3. Click **Create App** under "Sandbox apps"
4. Enter an app name (e.g., "VuTruDongHo Store")
5. Choose **Merchant** type
6. Click **Create App**
7. Copy the **Client ID** shown below the app name

### Step 3: Update the Client ID in payment.php
1. Open [payment.php](payment.php)
2. Find this line (around line 173):
   ```javascript
   const PAYPAL_CLIENT_ID = "AfH7LU1YDV8qQHfYCLc7802uj-9D810FUWzNPc6oJdxzalC6Ub4i1gF-anOPTcvHzBDK20-8eOvfEYbn";
   ```
3. Replace the entire Client ID with your sandbox Client ID from Step 2
4. **Important:** Keep the entire URL structure the same, only change the ID value

### Step 4: Test in Sandbox Mode
1. Clear browser cache (Ctrl+Shift+Delete)
2. Reload the payment page
3. Select PayPal as payment method
4. You should see PayPal buttons appear
5. Click PayPal button and use sandbox credentials:
   - **Buyer Email:** sb-xxxxx@personal.sandbox.paypal.com
   - **Password:** See PayPal Dashboard for sandbox credentials
   - Or use "2000" as a quick test sandbox buyer

### Step 5: Handle Console Logs
Monitor the browser console (F12 → Console) for these messages:
- `[PayPal] SDK script loaded successfully` ✅ Good
- `[PayPal] createOrder called` ✅ Button clicked
- `[PayPal] Payment captured successfully` ✅ Payment confirmed
- `[PayPal] Submitting form` ✅ Order being created

## Common Issues & Fixes

### Issue: Still getting 400 error
**Solution:**
1. Double-check that you copied the Client ID correctly (no extra spaces)
2. Verify the Client ID is from the correct sandbox app
3. Make sure you're not using a production Client ID with sandbox settings

### Issue: PayPal button appears but doesn't work
**Solution:**
1. Open DevTools Console (F12)
2. Look for error messages with `[PayPal]` prefix
3. Check that all form fields are populated:
   - UserID
   - Address
   - ShippingFee
   - Total

### Issue: Getting "ClientId is invalid" error in PayPal
**Solution:**
1. Go back to PayPal Developer Dashboard
2. Verify the app is in **Sandbox** mode
3. Copy the Client ID again and replace it in payment.php
4. Make sure there are no typos or extra characters

## For Production (Live)
When ready to go live:
1. Create a **Live** application in PayPal Dashboard
2. Get the **Live Client ID** from that app
3. Replace the Sandbox Client ID with the Live Client ID in payment.php
4. Change the payment.php environment configuration if needed

## Testing Checklist
- [ ] Valid Client ID from PayPal Dashboard
- [ ] Client ID copied correctly with no extra spaces
- [ ] Sandbox vs. Live environment matched correctly
- [ ] Browser cache cleared
- [ ] Delivery method selected before clicking PayPal
- [ ] Console shows `[PayPal] SDK script loaded successfully`
- [ ] PayPal buttons render without errors
- [ ] Order created after successful payment

## Support
If you still have issues:
1. Check the PayPal Developer Dashboard for any notifications
2. Verify your app is active in the dashboard
3. Try creating a new sandbox app and using its Client ID
4. Check that JavaScript is enabled in your browser
5. Try a different browser to rule out browser cache issues
