# Store API Documentation

This document describes the API endpoints available for store owners (users with `ROLE_STORE`) to manage orders and order items.

## Table of Contents

1. [Authentication](#authentication)
2. [Order Management](#order-management)
   - [List All Orders](#list-all-orders)
   - [Get Order Details](#get-order-details)
   - [Update Order with Order Item Actions](#update-order-with-order-item-actions)
3. [Store Settings](#store-settings)
   - [Get Store Settings](#get-store-settings)
   - [Update Store Settings](#update-store-settings)

## Authentication

All store API endpoints require JWT authentication with the `ROLE_STORE` role.

**Authorization Header:**
```
Authorization: Bearer {JWT_TOKEN}
```

## Order Management

Stores manage orders by updating order items within each order. Each order can contain multiple items, and each item can be assigned to a specific store.

### List All Orders

Get a list of all orders. Stores with `ROLE_STORE` can view all orders.

**Endpoint:** `GET /api/orders`

**Authentication:** Required (`ROLE_STORE`, `ROLE_ADMIN`, `ROLE_USER`, or `ROLE_DELIVER`)

**Example Request:**
```bash
curl -X GET "https://api.example.com/api/orders" \
  -H "Authorization: Bearer {JWT_TOKEN}"
```

**Example Response:**
```json
[
  {
    "id": 78,
    "reference": "ORD-2025-001",
    "totalAmount": 5000.00,
    "status": "Pending",
    "priority": "Standard",
    "createdAt": "2025-01-15T08:00:00+00:00",
    "owner": {
      "id": 12,
      "email": "customer@example.com"
    },
    "location": {
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
        "storeStatus": "pending"
      }
    ]
  }
]
```

### Get Order Details

Get detailed information about a specific order.

**Endpoint:** `GET /api/order/{id}`

**Authentication:** Required (`ROLE_STORE`, `ROLE_ADMIN`, `ROLE_USER`, or `ROLE_DELIVER`)

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
  "status": "Pending",
  "priority": "Standard",
  "phone": "+261340000000",
  "createdAt": "2025-01-15T08:00:00+00:00",
  "scheduledDate": "2025-01-16T14:00:00+00:00",
  "notes": "Customer prefers morning delivery",
  "deliveryNotes": "Ring doorbell twice",
  "owner": {
    "id": 12,
    "email": "customer@example.com",
    "firstName": "John",
    "lastName": "Doe"
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
        "name": "Paracetamol 500mg",
        "price": 1500.00
      },
      "quantity": 2,
      "totalPrice": 3000.00,
      "store": {
        "id": 5,
        "name": "Pharmacy ABC"
      },
      "storeStatus": "pending",
      "storePrice": null,
      "storeNotes": null
    }
  ]
}
```

### Update Order with Order Item Actions

Update an order by accepting, refusing, or suggesting alternatives for multiple order items in a single request.

**Endpoint:** `PUT /api/store/order/{id}`

**Authentication:** Required (`ROLE_STORE`)

**Path Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `id` | integer | Yes | The ID of the order to update |

**Request Body:**

```json
{
  "orderItemActions": [
    {
      "orderItemId": 123,
      "action": "accept",
      "notes": "Item will be ready for pickup tomorrow morning"
    },
    {
      "orderItemId": 124,
      "action": "refuse",
      "reason": "Product is currently out of stock. Expected restock in 3 days."
    },
    {
      "orderItemId": 125,
      "action": "suggest",
      "suggestedProductId": 56,
      "suggestion": "We have a generic version available at 20% lower price",
      "notes": "Same active ingredient and dosage"
    }
  ]
}
```

**Request Fields:**

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `orderItemActions` | array | Yes | Array of order item actions (minimum 1 action required) |

**Order Item Action Object:**

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `orderItemId` | integer | Yes | ID of the order item to update |
| `action` | string | Yes | Action to perform: `accept`, `refuse`, or `suggest` |
| `notes` | string | No | Optional notes (for `accept` action) |
| `reason` | string | Yes | Required reason (for `refuse` action) |
| `suggestedProductId` | integer | Yes | ID of alternative product (for `suggest` action) |
| `suggestion` | string | No | Explanation of suggestion (for `suggest` action) |

**Validation Rules:**

1. The order must exist
2. The authenticated user must be the owner of the store assigned to each order item
3. Each order item must belong to the order being updated
4. For `accept` action:
   - Product must be available in store's inventory
   - Store must have a valid price set for the product
5. For `refuse` action:
   - Reason is required
6. For `suggest` action:
   - Suggested product ID is required
   - Suggested product must be available in store's inventory

**Example Request:**
```bash
curl -X PUT "https://api.example.com/api/store/order/78" \
  -H "Authorization: Bearer {JWT_TOKEN}" \
  -H "Content-Type: application/json" \
  -d '{
    "orderItemActions": [
      {
        "orderItemId": 123,
        "action": "accept",
        "notes": "Item will be ready for pickup tomorrow morning"
      },
      {
        "orderItemId": 124,
        "action": "refuse",
        "reason": "Product is currently out of stock"
      }
    ]
  }'
```

**Success Response (200 OK):**
```json
{
  "id": 78,
  "reference": "ORD-2025-001",
  "totalAmount": 4500.00,
  "status": "Pending",
  "items": [
    {
      "id": 123,
      "product": {
        "id": 45,
        "name": "Paracetamol 500mg"
      },
      "quantity": 2,
      "storeStatus": "accepted",
      "storePrice": 1500.00,
      "storeNotes": "Item will be ready for pickup tomorrow morning",
      "storeActionAt": "2025-01-15T10:30:00+00:00"
    },
    {
      "id": 124,
      "product": {
        "id": 46,
        "name": "Aspirin 100mg"
      },
      "quantity": 1,
      "storeStatus": "refused",
      "storeNotes": "Product is currently out of stock",
      "storeActionAt": "2025-01-15T10:30:00+00:00"
    }
  ]
}
```

**What Happens:**

1. Each order item action is processed sequentially
2. For `accept` action:
   - Order item `storeStatus` is set to `accepted`
   - Store's price for the product is set as `storePrice`
   - Optional notes are saved in `storeNotes`
   - `storeActionAt` timestamp is recorded
3. For `refuse` action:
   - Order item `storeStatus` is set to `refused`
   - Reason is saved in `storeNotes`
   - `storeActionAt` timestamp is recorded
4. For `suggest` action:
   - Order item `storeStatus` is set to `suggested`
   - Suggested product is linked to the order item
   - Store's price for the suggested product is stored
   - Suggestion text and notes are saved
   - `storeActionAt` timestamp is recorded
   - **Note:** Suggestions require admin approval before they can be applied
5. After all actions are processed, the parent order's total amount is recalculated

**Error Responses:**

- `404 Not Found`: Order not found or order item not found
- `401 Unauthorized`: Authentication required
- `403 Forbidden`: You are not authorized to update this order or order item
- `400 Bad Request`: 
  - Order item actions are required
  - Invalid action type (must be: accept, refuse, suggest)
  - Product is not available in your store inventory (for accept/suggest)
  - Store product has no valid price set (for accept/suggest)
  - Reason is required (for refuse)
  - Suggested product ID is required (for suggest)
  - Order item does not belong to the order being updated

## Store Settings

### Get Store Settings

Get the store settings for the authenticated store owner.

**Endpoint:** `GET /api/store_settings` or `GET /api/store_settings/{id}`

**Authentication:** Required (`ROLE_STORE`)

**Path Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `id` | string | No | Store settings ID (optional - falls back to authenticated user store settings) |

**Example Request:**
```bash
curl -X GET "https://api.example.com/api/store_settings" \
  -H "Authorization: Bearer {JWT_TOKEN}"
```

**Example Response:**
```json
{
  "id": 10,
  "store": {
    "id": 5,
    "name": "Pharmacy ABC"
  },
  "businessHours": [
    {
      "id": 1,
      "dayOfWeek": 1,
      "openTime": "08:00",
      "closeTime": "18:00",
      "isClosed": false
    },
    {
      "id": 2,
      "dayOfWeek": 2,
      "openTime": "08:00",
      "closeTime": "18:00",
      "isClosed": false
    }
  ]
}
```

### Update Store Settings

Update store settings including business hours.

**Endpoint:** `PUT /api/store_settings/{id}`

**Authentication:** Required (`ROLE_STORE`)

**Path Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `id` | string | Yes | The ID of the store settings |

**Request Body:**

```json
{
  "businessHours": [
    {
      "@id": "/business_hours/1",
      "dayOfWeek": 1,
      "openTime": "08:00",
      "closeTime": "18:00",
      "isClosed": false
    },
    {
      "dayOfWeek": 2,
      "openTime": "09:00",
      "closeTime": "17:00",
      "isClosed": false
    }
  ]
}
```

**Note:** Include `@id` for existing business hours to update them, or omit `@id` to create new ones.

**Example Request:**
```bash
curl -X PUT "https://api.example.com/api/store_settings/10" \
  -H "Authorization: Bearer {JWT_TOKEN}" \
  -H "Content-Type: application/json" \
  -d '{
    "businessHours": [
      {
        "@id": "/business_hours/1",
        "dayOfWeek": 1,
        "openTime": "08:00",
        "closeTime": "18:00",
        "isClosed": false
      }
    ]
  }'
```

## Order Item Status Flow

The status of an order item follows this flow:

1. **`pending`**: Initial status when order is created and assigned to store
2. **`accepted`**: Store accepts the order item with their price
3. **`refused`**: Store refuses the order item with a reason
4. **`suggested`**: Store suggests an alternative product (requires admin approval)
5. **`approved`**: Admin approves the suggestion (returns item to `pending` status for store to accept)
6. **`rejected`**: Admin rejects the suggestion

## Workflow Examples

### Example 1: Accepting Multiple Order Items

1. Store owner calls `GET /api/orders` to see all orders
2. Store owner selects an order and calls `GET /api/order/78` to see details
3. Store owner reviews the order items and their products
4. Store owner calls `PUT /api/store/order/78` with actions to accept items:
   ```json
   {
     "orderItemActions": [
       {
         "orderItemId": 123,
         "action": "accept",
         "notes": "Ready for pickup"
       },
       {
         "orderItemId": 124,
         "action": "accept",
         "notes": "Will be ready tomorrow"
       }
     ]
   }
   ```
5. System sets `storeStatus` to `accepted` for both items and applies store's prices
6. Order total is recalculated

### Example 2: Mixing Actions (Accept, Refuse, Suggest)

1. Store owner reviews an order with multiple items
2. Store owner calls `PUT /api/store/order/78` with mixed actions:
   ```json
   {
     "orderItemActions": [
       {
         "orderItemId": 123,
         "action": "accept",
         "notes": "Available"
       },
       {
         "orderItemId": 124,
         "action": "refuse",
         "reason": "Out of stock"
       },
       {
         "orderItemId": 125,
         "action": "suggest",
         "suggestedProductId": 56,
         "suggestion": "Generic alternative available",
         "notes": "Same dosage"
       }
     ]
   }
   ```
3. System processes all actions in one transaction
4. Order total is recalculated based on accepted items

### Example 3: Suggesting an Alternative Product

1. Store owner finds a product is out of stock or wants to suggest a better option
2. Store owner calls `PUT /api/store/order/78` with a suggest action:
   ```json
   {
     "orderItemActions": [
       {
         "orderItemId": 125,
         "action": "suggest",
         "suggestedProductId": 56,
         "suggestion": "We have a generic version available at 20% lower price",
         "notes": "Same active ingredient and dosage"
       }
     ]
   }
   ```
3. System sets `storeStatus` to `suggested`
4. Admin reviews the suggestion
5. If approved, admin calls `POST /api/admin/order-item/approve-suggestion`
6. The order item returns to `pending` status with the new product
7. Store owner can now accept the item using the update order endpoint

## Error Handling

All endpoints return standard HTTP status codes:

- `200 OK`: Success
- `201 Created`: Resource created successfully
- `400 Bad Request`: Validation error or bad request
- `401 Unauthorized`: Authentication required
- `403 Forbidden`: Insufficient permissions
- `404 Not Found`: Resource not found
- `500 Internal Server Error`: Server error

Error response format:
```json
{
  "type": "https://tools.ietf.org/html/rfc2616#section-10",
  "title": "An error occurred",
  "detail": "Specific error message",
  "violations": [
    {
      "propertyPath": "orderItemActions[0].orderItemId",
      "message": "Order item ID is required"
    }
  ]
}
```

## Best Practices

1. **Batch Operations**: Use the update order endpoint to process multiple order items in a single request for better performance
2. **Always check inventory**: Before accepting, verify the product is in your store's inventory
3. **Use notes effectively**: Provide helpful notes to customers when accepting items
4. **Clear refusal reasons**: When refusing, provide detailed reasons for better customer experience
5. **Suggest alternatives**: Instead of refusing, consider suggesting alternative products
6. **Transaction Safety**: All order item actions are processed in a single transaction - if one fails, all are rolled back
7. **Order Totals**: Order totals are automatically recalculated after processing all actions

## Integration Notes

- All timestamps are in ISO 8601 format with timezone
- Prices are in the local currency (typically MGA for Madagascar)
- The API uses JSON for request and response bodies
- Store prices are automatically applied from StoreProduct when accepting items
- The order total is recalculated automatically after all actions are processed
- All order item actions in a single request are processed in one transaction
