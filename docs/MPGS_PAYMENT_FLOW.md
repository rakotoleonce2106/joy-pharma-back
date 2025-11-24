# MPGS Payment Flow - Complete Process

This document explains the complete payment flow after creating a payment intent with MPGS.

## Overview

The MPGS payment process involves several steps from creating a payment intent to confirming the payment completion.

---

## Step-by-Step Flow

### 1. Create Payment Intent

**Request:**
```http
POST /api/create-payment-intent
Content-Type: application/json
Authorization: Bearer {JWT_TOKEN}

{
  "method": "mpgs",
  "amount": "150.00",
  "reference": "ORD-2024-001234"
}
```

**Response:**
```json
{
  "id": "sessionId123",
  "clientSecret": "sessionId123",
  "status": "pending",
  "provider": "MPGS",
  "reference": "ORD-2024-001234",
  "sessionId": "sessionId123",
  "sessionVersion": "1.0",
  "successIndicator": "abc123xyz",
  "orderId": "orderId456"
}
```

**What happens:**
- Backend creates a checkout session with MPGS
- MPGS returns session credentials
- Payment is stored in database with status "pending"

---

### 2. Initialize MPGS Checkout (Mobile App)

**In your React Native app:**

```typescript
// Load MPGS checkout.js
const checkoutUrl = `https://your-gateway-url/checkout/version/45/checkout.js`;

// Configure checkout
Checkout.configure({
  merchant: merchantId,
  order: {
    amount: 150.00,
    currency: 'USD',
    description: 'Order ORD-2024-001234',
    id: orderId
  },
  session: {
    id: sessionId,
    version: sessionVersion
  },
  interaction: {
    merchant: {
      name: 'Your Merchant Name'
    }
  }
});

// Show checkout (lightbox or payment page)
Checkout.showLightbox();
// OR
Checkout.showPaymentPage();
```

**What happens:**
- MPGS checkout interface is displayed
- User enters card details
- MPGS validates card information
- User confirms payment

---

### 3. Payment Processing

**MPGS processes the payment:**
- Validates card details
- Performs 3D Secure if required
- Processes payment with card network
- Returns result

**Callbacks triggered:**
- `completeCallback(resultIndicator)` - Payment completed
- `errorCallback(error)` - Payment failed
- `cancelCallback()` - User cancelled

---

### 4. Payment Completion

**In your mobile app:**

```typescript
function completeCallback(response) {
  const resultIndicator = response;
  const success = (resultIndicator === successIndicator);
  
  if (success) {
    // Payment successful
    // Verify with backend
    verifyPayment(orderId);
  } else {
    // Payment failed
    showError('Payment failed');
  }
}
```

---

### 5. Verify Payment Status

**Request:**
```http
GET /api/verify-payment/{orderId}
Authorization: Bearer {JWT_TOKEN}
```

**Response:**
```json
{
  "verified": true,
  "status": "completed",
  "orderId": "orderId456"
}
```

**What happens:**
- Backend checks payment status in database
- Verifies payment was processed
- Returns verification result

---

### 6. Webhook Notification (Optional but Recommended)

MPGS can send webhooks to your backend when payment status changes.

**Webhook Endpoint:**
```http
POST /api/mpgs-webhook
Content-Type: application/json

{
  "result": "SUCCESS",
  "order": {
    "id": "orderId456",
    "amount": "150.00",
    "currency": "USD"
  },
  "transaction": {
    "id": "transactionId789",
    "status": "CAPTURED"
  }
}
```

**What to do:**
1. Verify webhook signature (if MPGS provides it)
2. Update payment status in database
3. Update order status
4. Send confirmation to user
5. Trigger order fulfillment

---

## Payment Status States

| Status | Description | Next Action |
|--------|------------|-------------|
| `pending` | Payment intent created, awaiting user action | Show checkout |
| `processing` | Payment submitted, being processed | Wait for completion |
| `completed` | Payment successful | Fulfill order |
| `failed` | Payment failed | Show error, allow retry |
| `refunded` | Payment was refunded | Update records |

---

## Error Handling

### Common Errors

1. **Session Expired**
   - Session IDs expire after a period
   - Solution: Create new payment intent

2. **Invalid Session**
   - Session ID doesn't match
   - Solution: Verify sessionId and sessionVersion

3. **Payment Declined**
   - Card declined by issuer
   - Solution: Show error, allow retry with different card

4. **Network Error**
   - Connection to MPGS failed
   - Solution: Retry with exponential backoff

5. **Verification Failed**
   - resultIndicator doesn't match successIndicator
   - Solution: Don't confirm payment, investigate

---

## Security Best Practices

### 1. Always Verify on Backend

**❌ DON'T:**
```typescript
// Client-side only verification
if (resultIndicator === successIndicator) {
  // Mark as paid - UNSAFE!
}
```

**✅ DO:**
```typescript
// Always verify on backend
const verified = await verifyPayment(orderId);
if (verified) {
  // Safe to proceed
}
```

### 2. Validate successIndicator

The `successIndicator` is a secret value that proves the payment was legitimate. Always compare it on the backend:

```php
// Backend verification
if ($resultIndicator === $payment->getSuccessIndicator()) {
    // Payment is legitimate
    $payment->setStatus(PaymentStatus::STATUS_COMPLETED);
}
```

### 3. Use HTTPS

All communication must be over HTTPS:
- API endpoints
- MPGS checkout URLs
- Webhook endpoints

### 4. Store Sensitive Data Securely

- Never store card details
- Never log full card numbers
- Encrypt sensitive payment data

---

## Testing

### Test Cards

Use MPGS test cards for testing:

| Card Number | Result |
|-------------|--------|
| 5123450000000008 | Success |
| 5123450000000009 | Declined |
| 5123450000000016 | 3D Secure required |

### Test Flow

1. Create payment intent with test amount
2. Use test card in checkout
3. Verify payment status
4. Check database records
5. Test error scenarios

---

## Integration Checklist

- [ ] MPGS credentials configured in backend
- [ ] Payment intent endpoint working
- [ ] Mobile app can create payment intent
- [ ] MPGS checkout loads in WebView
- [ ] Payment completion callback works
- [ ] Payment verification endpoint implemented
- [ ] Error handling implemented
- [ ] Webhook endpoint configured (optional)
- [ ] Test with test cards
- [ ] Production credentials configured
- [ ] HTTPS enabled
- [ ] Security measures in place

---

## Next Steps After Payment

1. **Update Order Status**
   ```php
   $order->setStatus(OrderStatus::STATUS_PAID);
   $this->orderService->updateOrder($order);
   ```

2. **Send Confirmation Email**
   ```php
   $this->emailService->sendPaymentConfirmation($user, $order);
   ```

3. **Trigger Fulfillment**
   ```php
   $this->fulfillmentService->processOrder($order);
   ```

4. **Update Inventory** (if applicable)
   ```php
   foreach ($order->getItems() as $item) {
       $this->inventoryService->decrementStock($item);
   }
   ```

5. **Generate Invoice**
   ```php
   $invoice = $this->invoiceService->generateInvoice($order);
   ```

---

## Support

For MPGS-specific issues:
- Check MPGS documentation
- Review MPGS merchant portal
- Contact MPGS support

For integration issues:
- Review backend logs
- Check mobile app console
- Verify API responses

