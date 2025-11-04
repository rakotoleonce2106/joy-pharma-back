# Delivery Person API Documentation

This document describes the API endpoints available for delivery persons (users with `ROLE_DELIVER`) to manage orders, interact with stores and customers, and track deliveries.

## Table of Contents

1. [Authentication](#authentication)
2. [Order Management](#order-management)
   - [Get Available Orders](#get-available-orders)
   - [Get Current Order](#get-current-order)
   - [Get Order Details](#get-order-details)
   - [Accept Order](#accept-order)
   - [Reject Order](#reject-order)
   - [Update Order Status](#update-order-status)
3. [Store Interactions](#store-interactions)
   - [Scan Store QR Code (Pickup)](#scan-store-qr-code-pickup)
4. [Customer Interactions](#customer-interactions)
   - [Validate Customer QR Code (Delivery)](#validate-customer-qr-code-delivery)
5. [Availability Management](#availability-management)
   - [Toggle Availability](#toggle-availability)
   - [Set Online Status](#set-online-status)
   - [Update Location](#update-location)
6. [Delivery Workflow](#delivery-workflow)

## Authentication

All delivery API endpoints require JWT authentication with the `ROLE_DELIVER` role.

**Authorization Header:**
```
Authorization: Bearer {JWT_TOKEN}
```

## Order Management

### Get Available Orders

Get a list of all orders that are available for delivery (pending status and not assigned to any delivery person).

**Endpoint:** `GET /api/orders/available`

**Authentication:** Required (`ROLE_DELIVER`)

**Note:** This endpoint returns all results without pagination.

**Example Request:**
```bash
curl -X GET "https://api.example.com/api/orders/available" \
  -H "Authorization: Bearer {JWT_TOKEN}"
```

**Example Response:**
```json
[
  {
    "id": 78,
    "reference": "ORD-2025-001",
    "totalAmount": 5000.00,
    "storeTotalAmount": 4500.00,
    "status": "pending",
    "priority": "standard",
    "phone": "+261340000000",
    "createdAt": "2025-01-15T08:00:00+00:00",
    "scheduledDate": "2025-01-16T14:00:00+00:00",
    "notes": "Customer prefers morning delivery",
    "owner": {
      "id": 12,
      "email": "customer@example.com",
      "firstName": "John",
      "lastName": "Doe",
      "phone": "+261340000000"
    },
    "location": {
      "id": 34,
      "address": "123 Main Street, Antananarivo",
      "latitude": -18.8792,
      "longitude": 47.5079,
      "city": "Antananarivo"
    },
    "items": [
      {
        "id": 123,
        "product": {
          "id": 45,
          "name": "Paracetamol 500mg"
        },
        "quantity": 2,
        "totalPrice": 3000.00,
        "store": {
          "id": 5,
          "name": "Pharmacy ABC",
          "location": {
            "address": "456 Store Street",
            "latitude": -18.9000,
            "longitude": 47.5200
          }
        },
        "storeStatus": "accepted"
      }
    ]
  }
]
```

### Get Current Order

Get the currently active order assigned to the authenticated delivery person.

**Endpoint:** `GET /api/order/current`

**Authentication:** Required (`ROLE_DELIVER`)

**Example Request:**
```bash
curl -X GET "https://api.example.com/api/order/current" \
  -H "Authorization: Bearer {JWT_TOKEN}"
```

**Example Response:**
```json
[
  {
    "id": 78,
    "reference": "ORD-2025-001",
    "totalAmount": 5000.00,
    "status": "confirmed",
    "priority": "standard",
    "phone": "+261340000000",
    "acceptedAt": "2025-01-15T09:00:00+00:00",
    "estimatedDeliveryTime": "2025-01-15T09:30:00+00:00",
    "owner": {
      "id": 12,
      "email": "customer@example.com",
      "firstName": "John",
      "lastName": "Doe",
      "phone": "+261340000000"
    },
    "location": {
      "id": 34,
      "address": "123 Main Street, Antananarivo",
      "latitude": -18.8792,
      "longitude": 47.5079
    },
    "items": [
      {
        "id": 123,
        "product": {
          "id": 45,
          "name": "Paracetamol 500mg"
        },
        "quantity": 2,
        "totalPrice": 3000.00,
        "store": {
          "id": 5,
          "name": "Pharmacy ABC",
          "location": {
            "address": "456 Store Street",
            "latitude": -18.9000,
            "longitude": 47.5200
          }
        },
        "storeStatus": "accepted"
      }
    ],
    "deliver": {
      "id": 8,
      "firstName": "Delivery",
      "lastName": "Person"
    }
  }
]
```

**Note:** Returns an empty array `[]` if no current order is assigned.

### Get Order Details

Get detailed information about a specific order.

**Endpoint:** `GET /api/order/{id}`

**Authentication:** Required (any authenticated user)

**Path Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `id` | integer | Yes | The ID of the order |

**Example Request:**
```bash
curl -X GET "https://api.example.com/api/order/78" \
  -H "Authorization: Bearer {JWT_TOKEN}"
```

**Example Response:**
```json
{
  "id": 78,
  "reference": "ORD-2025-001",
  "totalAmount": 5000.00,
  "storeTotalAmount": 4500.00,
  "status": "confirmed",
  "priority": "standard",
  "phone": "+261340000000",
  "createdAt": "2025-01-15T08:00:00+00:00",
  "acceptedAt": "2025-01-15T09:00:00+00:00",
  "estimatedDeliveryTime": "2025-01-15T09:30:00+00:00",
  "deliveryFee": "500.00",
  "deliveryNotes": "Ring doorbell twice",
  "owner": {
    "id": 12,
    "email": "customer@example.com",
    "firstName": "John",
    "lastName": "Doe",
    "phone": "+261340000000"
  },
  "location": {
    "id": 34,
    "address": "123 Main Street, Antananarivo",
    "latitude": -18.8792,
    "longitude": 47.5079,
    "city": "Antananarivo"
  },
  "items": [
    {
      "id": 123,
      "product": {
        "id": 45,
        "name": "Paracetamol 500mg"
      },
      "quantity": 2,
      "totalPrice": 3000.00,
      "store": {
        "id": 5,
        "name": "Pharmacy ABC",
        "location": {
          "address": "456 Store Street",
          "latitude": -18.9000,
          "longitude": 47.5200
        }
      },
      "storeStatus": "accepted"
    }
  ]
}
```

### Accept Order

Accept an available order and assign it to the current delivery person. This endpoint assigns the order to you and changes its status to `confirmed`.

**Endpoint:** `POST /api/orders/{id}/accept`

**Authentication:** Required (`ROLE_USER` - delivery persons have this role)

**Path Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `id` | integer | Yes | The ID of the order to accept |

**Request Body:** None required

**Example Request:**
```bash
curl -X POST "https://api.example.com/api/orders/78/accept" \
  -H "Authorization: Bearer {JWT_TOKEN}" \
  -H "Content-Type: application/json"
```

**Success Response (200 OK):**
```json
{
  "id": 78,
  "reference": "ORD-2025-001",
  "status": "confirmed",
  "acceptedAt": "2025-01-15T09:00:00+00:00",
  "estimatedDeliveryTime": "2025-01-15T09:30:00+00:00",
  "deliver": {
    "id": 8,
    "firstName": "Delivery",
    "lastName": "Person"
  }
}
```

**What Happens:**

1. Order is assigned to the authenticated delivery person
2. Order status changes from `pending` to `confirmed`
3. `acceptedAt` timestamp is recorded
4. `estimatedDeliveryTime` is set to 30 minutes from now (default)
5. Delivery person cannot accept another order until current order is completed

**Error Responses:**

- `404 Not Found`: Order not found
- `409 Conflict`: 
  - Order already assigned to another delivery person
  - Order is not available for assignment (not in pending status)
  - You already have an active order
- `401 Unauthorized`: Authentication required

### Reject Order

Reject an available order (removes it from your available list).

**Endpoint:** `POST /api/orders/{id}/reject`

**Authentication:** Required (`ROLE_USER`)

**Path Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `id` | integer | Yes | The ID of the order to reject |

**Request Body:** None required

**Example Request:**
```bash
curl -X POST "https://api.example.com/api/orders/78/reject" \
  -H "Authorization: Bearer {JWT_TOKEN}" \
  -H "Content-Type: application/json"
```

**Success Response (200 OK):**
```json
{
  "message": "Order rejected successfully"
}
```

### Update Order Status

Update the delivery status of an order. Use this to track the order progress through different stages.

**Endpoint:** `PUT /api/orders/{id}/status`

**Authentication:** Required (`ROLE_USER`)

**Path Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `id` | integer | Yes | The ID of the order to update |

**Request Body:**

```json
{
  "status": "processing",
  "latitude": -18.8792,
  "longitude": 47.5079,
  "notes": "On the way to store"
}
```

**Request Fields:**

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `status` | string | Yes | Order status: `pending`, `confirmed`, `processing`, `shipped`, `collected`, `delivered`, `cancelled` |
| `latitude` | float | No | Current latitude for location tracking |
| `longitude` | float | No | Current longitude for location tracking |
| `notes` | string | No | Optional delivery notes |

**Valid Status Values:**

- `pending`: Initial status
- `confirmed`: Order accepted by delivery person
- `processing`: Delivery person is processing the order (on the way to store)
- `shipped`: Order collected from store and en route to customer
- `collected`: Order collected from store (alternative to shipped)
- `delivered`: Order delivered to customer
- `cancelled`: Order cancelled

**Example Request:**
```bash
curl -X PUT "https://api.example.com/api/orders/78/status" \
  -H "Authorization: Bearer {JWT_TOKEN}" \
  -H "Content-Type: application/json" \
  -d '{
    "status": "processing",
    "latitude": -18.8792,
    "longitude": 47.5079,
    "notes": "On the way to store"
  }'
```

**Success Response (200 OK):**
```json
{
  "id": 78,
  "reference": "ORD-2025-001",
  "status": "processing",
  "pickedUpAt": "2025-01-15T09:15:00+00:00",
  "deliveryNotes": "2025-01-15 09:15:00: On the way to store",
  "owner": {
    "id": 12,
    "firstName": "John",
    "lastName": "Doe"
  }
}
```

**What Happens:**

1. Order status is updated
2. Timestamps are automatically set based on status:
   - `processing` or `collected`: Sets `pickedUpAt` if not already set
   - `delivered`: Sets `deliveredAt` and `actualDeliveryTime`, increments delivery person's total deliveries, and adds delivery fee to earnings
3. If location is provided, updates delivery person's current location
4. If notes are provided, appends to `deliveryNotes` with timestamp

**Note:** You must be assigned to the order to update its status.

## Store Interactions

### Scan Store QR Code (Pickup)

Scan the order QR code displayed at the store to verify order collection (récupération). This marks order items from the store as "recuperated" and updates the order status if all items are collected.

**Endpoint:** `POST /api/orders/{id}/scan-store-qr`

**Authentication:** Required (`ROLE_DELIVER`)

**Path Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `id` | integer | Yes | The ID of the order to collect |

**Request Body:**

```json
{
  "qrCode": "ORDER-ORD-2025-001-ABC123XYZ"
}
```

**Request Fields:**

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `qrCode` | string | Yes | The QR code displayed at the store |

**Example Request:**
```bash
curl -X POST "https://api.example.com/api/orders/78/scan-store-qr" \
  -H "Authorization: Bearer {JWT_TOKEN}" \
  -H "Content-Type: application/json" \
  -d '{
    "qrCode": "ORDER-ORD-2025-001-ABC123XYZ"
  }'
```

**Success Response (200 OK):**
```json
{
  "id": 78,
  "reference": "ORD-2025-001",
  "status": "shipped",
  "pickedUpAt": "2025-01-15T09:30:00+00:00",
  "items": [
    {
      "id": 123,
      "storeStatus": "recuperated",
      "store": {
        "id": 5,
        "name": "Pharmacy ABC"
      }
    }
  ]
}
```

**What Happens:**

1. Verifies the QR code matches the order
2. Updates all accepted order items from the store to `recuperated` status
3. Sets `storeActionAt` timestamp for updated items
4. If all items from all stores are recuperated or refused, changes order status to `shipped`
5. Sets `pickedUpAt` timestamp if order status changes to `shipped`
6. Creates a scan log entry for tracking

**Error Responses:**

- `404 Not Found`: Order not found
- `400 Bad Request`: 
  - QR Code invalide pour cette commande (Invalid QR code for this order)
  - Aucun article trouvé pour ce magasin dans cette commande (No items found for this store in this order)
  - Aucun article accepté trouvé pour ce magasin dans cette commande (No accepted items found for this store in this order)
- `403 Forbidden`: You are not assigned to this order
- `401 Unauthorized`: Authentication required

**Note:** Error messages are returned in French.

## Customer Interactions

### Validate Customer QR Code (Delivery)

Scan and validate the customer's QR code to confirm order delivery. This marks the order as delivered and updates delivery person statistics.

**Endpoint:** `POST /api/orders/{id}/validate-qr`

**Authentication:** Required (`ROLE_DELIVER`)

**Path Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `id` | integer | Yes | The ID of the order to deliver |

**Request Body:**

```json
{
  "qrCode": "ORDER-ORD-2025-001-ABC123XYZ",
  "latitude": -18.8792,
  "longitude": 47.5079
}
```

**Request Fields:**

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `qrCode` | string | Yes | The customer's QR code |
| `latitude` | float | No | Delivery location latitude |
| `longitude` | float | No | Delivery location longitude |

**Example Request:**
```bash
curl -X POST "https://api.example.com/api/orders/78/validate-qr" \
  -H "Authorization: Bearer {JWT_TOKEN}" \
  -H "Content-Type: application/json" \
  -d '{
    "qrCode": "ORDER-ORD-2025-001-ABC123XYZ",
    "latitude": -18.8792,
    "longitude": 47.5079
  }'
```

**Success Response (200 OK):**
```json
{
  "id": 78,
  "reference": "ORD-2025-001",
  "status": "delivered",
  "deliveredAt": "2025-01-15T10:00:00+00:00",
  "actualDeliveryTime": "2025-01-15T10:00:00+00:00",
  "qrCodeValidatedAt": "2025-01-15T10:00:00+00:00",
  "owner": {
    "id": 12,
    "firstName": "John",
    "lastName": "Doe"
  }
}
```

**What Happens:**

1. Verifies the QR code matches the order
2. Marks QR code as validated (`qrCodeValidatedAt`)
3. Changes order status to `delivered`
4. Sets `deliveredAt` and `actualDeliveryTime` timestamps
5. Increments delivery person's `totalDeliveries` count
6. Adds `deliveryFee` to delivery person's `totalEarnings`
7. Creates a scan log entry with geolocation (if provided)

**Error Responses:**

- `404 Not Found`: Order not found
- `400 Bad Request`: QR Code invalide pour cette commande (Invalid QR code for this order)
- `403 Forbidden`: You are not assigned to this order
- `401 Unauthorized`: Authentication required

**Note:** Error messages are returned in French.

## Availability Management

### Toggle Availability

Toggle the delivery person's online/offline status.

**Endpoint:** `PUT /api/availability`

**Authentication:** Required (`ROLE_USER`)

**Request Body:** None required

**Example Request:**
```bash
curl -X PUT "https://api.example.com/api/availability" \
  -H "Authorization: Bearer {JWT_TOKEN}" \
  -H "Content-Type: application/json"
```

**Success Response (200 OK):**
```json
{
  "isOnline": true
}
```

### Set Online Status

Set the delivery person's online/offline status explicitly.

**Endpoint:** `PUT /api/availability/online`

**Authentication:** Required (`ROLE_USER`)

**Request Body:** None required

**Example Request:**
```bash
curl -X PUT "https://api.example.com/api/availability/online" \
  -H "Authorization: Bearer {JWT_TOKEN}" \
  -H "Content-Type: application/json"
```

### Update Location

Update the delivery person's current location. This can be done via the Update Order Status endpoint or directly through user profile updates.

**Note:** Location is automatically updated when you update order status with latitude/longitude.

## Delivery Workflow

Here's a complete workflow for delivering an order:

### Step 1: Go Online

```bash
curl -X PUT "https://api.example.com/api/availability" \
  -H "Authorization: Bearer {JWT_TOKEN}"
```

### Step 2: Get Available Orders

```bash
curl -X GET "https://api.example.com/api/orders/available" \
  -H "Authorization: Bearer {JWT_TOKEN}"
```

### Step 3: Accept an Order

```bash
curl -X POST "https://api.example.com/api/orders/78/accept" \
  -H "Authorization: Bearer {JWT_TOKEN}"
```

### Step 4: Update Status to "Processing" (Heading to Store)

```bash
curl -X PUT "https://api.example.com/api/orders/78/status" \
  -H "Authorization: Bearer {JWT_TOKEN}" \
  -H "Content-Type: application/json" \
  -d '{
    "status": "processing",
    "latitude": -18.8792,
    "longitude": 47.5079,
    "notes": "On the way to store"
  }'
```

### Step 5: Scan Store QR Code (Pickup from Store)

```bash
curl -X POST "https://api.example.com/api/orders/78/scan-store-qr" \
  -H "Authorization: Bearer {JWT_TOKEN}" \
  -H "Content-Type: application/json" \
  -d '{
    "qrCode": "ORDER-ORD-2025-001-ABC123XYZ"
  }'
```

**Note:** After scanning the store QR code, the order status automatically changes to `shipped` if all items are collected.

### Step 6: Update Status to "Shipped" (En Route to Customer)

```bash
curl -X PUT "https://api.example.com/api/orders/78/status" \
  -H "Authorization: Bearer {JWT_TOKEN}" \
  -H "Content-Type: application/json" \
  -d '{
    "status": "shipped",
    "latitude": -18.8800,
    "longitude": 47.5100,
    "notes": "On the way to customer"
  }'
```

### Step 7: Validate Customer QR Code (Delivery)

```bash
curl -X POST "https://api.example.com/api/orders/78/validate-qr" \
  -H "Authorization: Bearer {JWT_TOKEN}" \
  -H "Content-Type: application/json" \
  -d '{
    "qrCode": "ORDER-ORD-2025-001-ABC123XYZ",
    "latitude": -18.8792,
    "longitude": 47.5079
  }'
```

**Note:** After validating the customer QR code, the order status automatically changes to `delivered`, and your delivery statistics are updated.

### Step 8: Check Current Order (Verify Completion)

```bash
curl -X GET "https://api.example.com/api/order/current" \
  -H "Authorization: Bearer {JWT_TOKEN}"
```

If no current order is returned, you can accept a new order.

## Order Status Flow

The order status follows this flow:

1. **`pending`**: Order created, waiting for delivery person to accept
2. **`confirmed`**: Order accepted by delivery person
3. **`processing`**: Delivery person is processing (heading to store)
4. **`shipped`** or **`collected`**: Order collected from store, en route to customer
5. **`delivered`**: Order delivered to customer (after QR validation)
6. **`cancelled`**: Order cancelled

## Order Item Status Flow (Store Side)

Order items go through these statuses:

1. **`pending`**: Waiting for store action
2. **`accepted`**: Store accepted the item
3. **`refused`**: Store refused the item
4. **`recuperated`**: Item collected by delivery person (set after scanning store QR)
5. **`suggested`**: Store suggested alternative (requires admin approval)
6. **`approved`**: Admin approved suggestion
7. **`rejected`**: Admin rejected suggestion

## Error Handling

All endpoints return standard HTTP status codes:

- `200 OK`: Success
- `201 Created`: Resource created successfully
- `400 Bad Request`: Validation error or bad request
- `401 Unauthorized`: Authentication required
- `403 Forbidden`: Insufficient permissions
- `404 Not Found`: Resource not found
- `409 Conflict`: Resource conflict (e.g., order already assigned)
- `500 Internal Server Error`: Server error

Error response format:
```json
{
  "type": "https://tools.ietf.org/html/rfc2616#section-10",
  "title": "An error occurred",
  "detail": "Specific error message",
  "violations": [
    {
      "propertyPath": "qrCode",
      "message": "QR Code is required"
    }
  ]
}
```

## Best Practices

1. **Always verify QR codes**: Both store and customer QR codes must match the order
2. **Update location frequently**: Provide location updates when updating order status for better tracking
3. **Use notes effectively**: Add helpful notes at each stage for better communication
4. **Check current order**: Before accepting a new order, verify you don't have an active order
5. **Complete delivery flow**: Always complete the full workflow (accept → processing → pickup → shipped → delivered)
6. **Handle errors gracefully**: QR code validation errors are returned in French - handle them appropriately in your app
7. **Track earnings**: Earnings are automatically calculated when you complete a delivery

## Integration Notes

- All timestamps are in ISO 8601 format with timezone
- Prices are in the local currency (typically MGA for Madagascar)
- The API uses JSON for request and response bodies
- QR codes are unique per order and cannot be reused
- Location updates are optional but recommended for better tracking
- Delivery fees are automatically added to earnings when order is delivered
- You can only have one active order at a time
- Order status updates are idempotent - updating to the same status is safe

