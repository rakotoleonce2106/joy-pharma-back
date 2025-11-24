# MPGS Quick Start Guide

Quick reference for integrating MPGS payment in your mobile app.

## 1. Backend Setup

### Add Environment Variables

Add to your `.env` file:

```env
# MPGS Configuration
MPGS_GATEWAY_BASE_URL=https://test-gateway.mastercard.com
MPGS_PKI_BASE_URL=https://test-gateway.mastercard.com
MPGS_MERCHANT_ID=your_merchant_id
MPGS_API_PASSWORD=your_api_password
MPGS_API_VERSION=45
MPGS_DEFAULT_CURRENCY=USD
MPGS_VERIFY_PEER=false
MPGS_VERIFY_HOST=0
```

### Test Backend

```bash
# Create payment intent
curl -X POST http://localhost:8000/api/create-payment-intent \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{
    "method": "mpgs",
    "amount": "10.00",
    "reference": "TEST-001"
  }'
```

---

## 2. Mobile App Setup

### Install Dependencies

```bash
npm install react-native-webview
# or
yarn add react-native-webview
```

### Environment Variables

Create `.env` in your Expo project:

```env
EXPO_PUBLIC_API_URL=https://your-api.com
EXPO_PUBLIC_MPGS_MERCHANT_ID=your_merchant_id
EXPO_PUBLIC_MPGS_CHECKOUT_JS_URL=https://test-gateway.mastercard.com/checkout/version/45/checkout.js
```

---

## 3. Payment Flow

### Step 1: Create Payment Intent

```typescript
const response = await fetch(`${API_URL}/api/create-payment-intent`, {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'Authorization': `Bearer ${token}`,
  },
  body: JSON.stringify({
    method: 'mpgs',
    amount: '10.00',
    reference: 'ORD-001',
  }),
});

const paymentIntent = await response.json();
// Returns: { sessionId, sessionVersion, successIndicator, orderId, ... }
```

### Step 2: Show MPGS Checkout

```typescript
// Load MPGS checkout.js
const checkoutUrl = `${MPGS_CHECKOUT_JS_URL}`;

// In WebView HTML
Checkout.configure({
  merchant: merchantId,
  order: {
    amount: 10.00,
    currency: 'USD',
    description: 'Order ORD-001',
    id: orderId
  },
  session: {
    id: sessionId,
    version: sessionVersion
  }
});

Checkout.showLightbox();
```

### Step 3: Handle Payment Result

```typescript
function completeCallback(response) {
  const resultIndicator = response;
  const success = (resultIndicator === successIndicator);
  
  if (success) {
    // Verify with backend
    verifyPayment(orderId);
  }
}
```

### Step 4: Verify Payment

```typescript
const verifyResponse = await fetch(`${API_URL}/api/verify-payment/${orderId}`, {
  headers: {
    'Authorization': `Bearer ${token}`,
  },
});

const { verified, status } = await verifyResponse.json();
```

---

## 4. Complete Example

See `docs/MPGS_MOBILE_INTEGRATION.md` for complete React Native implementation.

---

## 5. Testing

### Test Cards

- **Success**: `5123450000000008`
- **Declined**: `5123450000000009`
- **3D Secure**: `5123450000000016`

### Test Flow

1. Create payment intent
2. Use test card in checkout
3. Verify payment status
4. Check order status

---

## 6. Next Steps

1. ✅ Backend configured
2. ✅ Payment intent endpoint working
3. ✅ Mobile app integrated
4. ✅ Payment verification working
5. ⬜ Webhook implementation (optional)
6. ⬜ Production deployment

---

## Troubleshooting

| Issue | Solution |
|-------|----------|
| Session expired | Create new payment intent |
| Invalid session | Check sessionId and sessionVersion |
| Payment declined | Use valid test card |
| Verification failed | Check successIndicator match |

---

## Resources

- Full Integration Guide: `docs/MPGS_MOBILE_INTEGRATION.md`
- Payment Flow: `docs/MPGS_PAYMENT_FLOW.md`
- Environment Setup: `docs/ENV_SETUP.md`
- API Documentation: `docs/CREATE_PAYMENT_INTENT.md`

