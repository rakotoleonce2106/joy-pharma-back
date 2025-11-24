# MPGS Backend Endpoints Documentation

Complete documentation for all MPGS-related backend endpoints.

---

## 1. Create Payment Intent

**Endpoint:** `POST /api/create-payment-intent`

Creates a payment intent and MPGS checkout session.

**Request:**
```json
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

**See:** `docs/CREATE_PAYMENT_INTENT.md` for detailed documentation.

---

## 2. Verify Payment

**Endpoint:** `GET /api/verify-payment/{orderId}`

Verifies payment status for an order. Optionally validates resultIndicator for MPGS payments.

**Parameters:**
- `orderId` (path) - Order reference
- `resultIndicator` (query, optional) - For MPGS payment verification

**Response:**
```json
{
  "verified": true,
  "status": "completed",
  "orderId": "ORD-2024-001234",
  "paymentId": "transactionId789",
  "method": "mpgs"
}
```

**Example with resultIndicator:**
```
GET /api/verify-payment/ORD-2024-001234?resultIndicator=abc123xyz
```

---

## 3. Confirm Payment

**Endpoint:** `POST /api/confirm-payment`

Confirms a payment after user completes checkout. Validates successIndicator for MPGS payments and updates order status.

**Request:**
```json
{
  "orderId": "ORD-2024-001234",
  "resultIndicator": "abc123xyz"
}
```

**Response (Success):**
```json
{
  "success": true,
  "message": "Payment confirmed",
  "orderId": "ORD-2024-001234",
  "status": "completed"
}
```

**Response (Error):**
```json
{
  "error": "Payment verification failed: Invalid resultIndicator"
}
```

**What it does:**
1. Finds order and payment
2. Validates resultIndicator matches stored successIndicator (for MPGS)
3. Updates payment status to `completed`
4. Updates order status to `confirmed`
5. Returns confirmation

---

## 4. Get Payment by Order ID

**Endpoint:** `GET /api/payment/order/{orderId}`

Retrieves payment information by order reference.

**Response:**
```json
{
  "id": 123,
  "transactionId": "transactionId789",
  "amount": "150.00",
  "method": "mpgs",
  "status": "completed",
  "reference": "ORD-2024-001234",
  "createdAt": "2024-01-15 10:30:00",
  "processedAt": "2024-01-15 10:35:00",
  "orderId": "ORD-2024-001234"
}
```

---

## 5. Get Payment by Transaction ID

**Endpoint:** `GET /api/payment/transaction/{transactionId}`

Retrieves payment information by transaction ID.

**Response:**
```json
{
  "id": 123,
  "transactionId": "transactionId789",
  "amount": "150.00",
  "method": "mpgs",
  "status": "completed",
  "reference": "ORD-2024-001234",
  "createdAt": "2024-01-15 10:30:00",
  "processedAt": "2024-01-15 10:35:00",
  "orderId": "ORD-2024-001234"
}
```

---

## 6. MPGS Webhook

**Endpoint:** `POST /api/mpgs-webhook`

Receives payment status updates from MPGS gateway. This endpoint should be configured in your MPGS merchant portal.

**Request (from MPGS):**
```json
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
  },
  "resultIndicator": "abc123xyz"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Webhook processed",
  "orderId": "orderId456",
  "status": "completed"
}
```

**What it does:**
1. Receives webhook from MPGS
2. Validates webhook data
3. Finds order and payment
4. Verifies resultIndicator (if provided)
5. Maps MPGS result to payment status
6. Updates payment status
7. Updates order status if payment completed
8. Stores webhook data

**Security:**
- Configure webhook URL in MPGS merchant portal
- Consider adding webhook signature verification (if MPGS provides it)
- Use HTTPS for webhook endpoint

---

## Payment Status Mapping

| MPGS Result | Payment Status |
|-------------|----------------|
| `SUCCESS`, `CAPTURED`, `AUTHORIZED` | `completed` |
| `PENDING`, `IN_PROGRESS` | `processing` |
| `FAILURE`, `DECLINED`, `ERROR`, `CANCELLED` | `failed` |
| `REFUNDED`, `REVERSED` | `refunded` |

---

## Order Status Updates

When payment is completed:
- Order status changes from `pending` to `confirmed`
- Order `updatedAt` timestamp is updated

---

## Error Responses

All endpoints return standard error responses:

**400 Bad Request:**
```json
{
  "error": "Invalid request data",
  "message": "Missing required field: orderId"
}
```

**404 Not Found:**
```json
{
  "error": "Order not found"
}
```

**500 Internal Server Error:**
```json
{
  "error": "Internal server error",
  "message": "Error details"
}
```

---

## Authentication

All endpoints (except webhook) require JWT authentication:

```
Authorization: Bearer {JWT_TOKEN}
```

Webhook endpoint may use different authentication (configure in MPGS portal).

---

## Complete Payment Flow

```
1. Mobile App → POST /api/create-payment-intent
   ↓ Returns: sessionId, sessionVersion, successIndicator
   
2. Mobile App → Loads MPGS checkout
   ↓ User enters card details
   
3. MPGS → Processes payment
   ↓ Calls completeCallback with resultIndicator
   
4. Mobile App → POST /api/confirm-payment
   ↓ Validates resultIndicator, updates status
   
5. MPGS → POST /api/mpgs-webhook (optional)
   ↓ Updates payment status from gateway
   
6. Mobile App → GET /api/verify-payment/{orderId}
   ↓ Final verification
```

---

## Testing

### Test Payment Intent Creation
```bash
curl -X POST http://localhost:8000/api/create-payment-intent \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{
    "method": "mpgs",
    "amount": "10.00",
    "reference": "TEST-001"
  }'
```

### Test Payment Verification
```bash
curl -X GET "http://localhost:8000/api/verify-payment/TEST-001" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### Test Payment Confirmation
```bash
curl -X POST http://localhost:8000/api/confirm-payment \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{
    "orderId": "TEST-001",
    "resultIndicator": "abc123xyz"
  }'
```

### Test Get Payment
```bash
curl -X GET "http://localhost:8000/api/payment/order/TEST-001" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

---

## Security Best Practices

1. **Always verify resultIndicator** on backend
2. **Use HTTPS** for all endpoints
3. **Validate webhook signatures** (if MPGS provides)
4. **Log all payment operations** for audit trail
5. **Never trust client-side payment status**
6. **Implement rate limiting** on webhook endpoint
7. **Use environment variables** for sensitive data

---

## Related Documentation

- Payment Intent Creation: `docs/CREATE_PAYMENT_INTENT.md`
- Mobile Integration: `docs/MPGS_MOBILE_INTEGRATION.md`
- Payment Flow: `docs/MPGS_PAYMENT_FLOW.md`
- Environment Setup: `docs/ENV_SETUP.md`

