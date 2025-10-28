# Store Product Suggestion Workflow

## Overview
This document describes the complete workflow for store product suggestions in the order management system.

## Workflow Steps

### Step 1: Store Suggests Alternative Product

When a store cannot fulfill an order item with the requested product, they can suggest an alternative product from the catalog.

**API Endpoint:**
```
POST /api/store/order-item/suggest
```

**Request Body:**
```json
{
  "orderItemId": 123,
  "suggestedProductId": 456,
  "storePrice": 27000.00,
  "suggestion": "This is a better quality product with similar effects",
  "notes": "Original product is out of stock"
}
```

**What Happens:**
- Order item status changes to `SUGGESTED`
- `suggestedProduct` field is set to the alternative product
- `storePrice` is set (the price store will charge for the alternative)
- Optional text explanation can be added in `suggestion` field
- Timestamp is recorded in `storeActionAt`

### Step 2: Admin Reviews Suggestion

The admin reviews the suggestion in the order detail page where they can see:
- Original product requested
- Suggested alternative product
- Store's price and explanation
- All relevant order details

### Step 3: Admin Approves Suggestion

**API Endpoint:**
```
POST /api/admin/order-item/approve-suggestion
```

**Request Body:**
```json
{
  "orderItemId": 123,
  "adminNotes": "Customer contacted and agreed to the alternative"
}
```

**What Happens:**
1. **Product Replacement**: The original product is replaced with the suggested product
2. **Status Reset**: Order item status changes from `SUGGESTED` to `PENDING`
3. **Price Reset**: Store price is cleared (will be set when store accepts)
4. **Suggested Product Cleared**: The `suggestedProduct` field is cleared
5. **Admin Notes Added**: Admin's notes are appended to store notes

### Step 4: Store Accepts with Price

Now that the order item has the new product and is back to `PENDING` status, the store can accept it with their price.

**API Endpoint:**
```
POST /api/store/order-item/accept
```

**Request Body:**
```json
{
  "orderItemId": 123,
  "storePrice": 27000.00,
  "notes": "Alternative product ready for delivery"
}
```

**What Happens:**
- Order item status changes to `ACCEPTED`
- Store price is set
- Order totals are recalculated with the new product and store price

## Status Flow Diagram

```
┌──────────┐
│ PENDING  │ ← Order item created
└────┬─────┘
     │
     ├─────────────────────────────────┐
     │                                 │
     ▼                                 ▼
┌──────────┐                    ┌────────────┐
│ ACCEPTED │                    │ SUGGESTED  │ ← Store suggests alternative
└──────────┘                    └─────┬──────┘
                                      │
                                      ▼
                             [Admin Reviews]
                                      │
                                      ▼
                              ┌───────────────┐
                              │ Admin Approves│
                              └───────┬───────┘
                                      │
                          ┌───────────┴───────────┐
                          │ Product Replaced      │
                          │ Status → PENDING      │
                          └───────────┬───────────┘
                                      │
                                      ▼
                              ┌──────────┐
                              │ ACCEPTED │ ← Store accepts new product
                              └──────────┘
```

## Data Structure

### OrderItem Fields Related to Suggestions

| Field | Type | Description |
|-------|------|-------------|
| `product` | Product | Current product (updated when suggestion approved) |
| `suggestedProduct` | Product | Alternative product suggested by store |
| `storeStatus` | Enum | Current status (PENDING/SUGGESTED/ACCEPTED/etc) |
| `storeSuggestion` | Text | Optional text explanation from store |
| `storePrice` | Float | Store's price for the product |
| `storeNotes` | Text | Notes from store and admin |
| `storeActionAt` | DateTime | When last action was taken |

## Example Complete Flow

### 1. Customer Orders Product A
```json
{
  "product": "Product A - ID: 100",
  "quantity": 2,
  "storeStatus": "pending"
}
```

### 2. Store Suggests Product B
```http
POST /api/store/order-item/suggest
Content-Type: application/json

{
  "orderItemId": 123,
  "suggestedProductId": 200,
  "storePrice": 28000.00,
  "suggestion": "Product B has better quality and longer shelf life",
  "notes": "Product A is currently out of stock"
}
```

**Result:**
```json
{
  "product": "Product A - ID: 100",
  "suggestedProduct": "Product B - ID: 200",
  "storeStatus": "suggested",
  "storeSuggestion": "Product B has better quality...",
  "storePrice": 28000.00,
  "storeNotes": "Product A is currently out of stock"
}
```

### 3. Admin Approves
```http
POST /api/admin/order-item/approve-suggestion
Content-Type: application/json

{
  "orderItemId": 123,
  "adminNotes": "Customer agreed to the alternative"
}
```

**Result:**
```json
{
  "product": "Product B - ID: 200",  // ← Changed!
  "suggestedProduct": null,          // ← Cleared!
  "storeStatus": "pending",          // ← Back to pending!
  "storeSuggestion": "Product B has better quality...",
  "storePrice": null,                // ← Cleared!
  "storeNotes": "Product A is currently out of stock\n[Admin Approved]: Customer agreed..."
}
```

### 4. Store Accepts New Product
```http
POST /api/store/order-item/accept
Content-Type: application/json

{
  "orderItemId": 123,
  "storePrice": 28000.00,
  "notes": "Product B ready for delivery"
}
```

**Final Result:**
```json
{
  "product": "Product B - ID: 200",
  "suggestedProduct": null,
  "storeStatus": "accepted",
  "storePrice": 28000.00,
  "storeNotes": "Product A is currently out of stock\n[Admin Approved]: Customer agreed...\nProduct B ready for delivery"
}
```

## UI Display

### Admin Order View
When viewing order details, admins see:

**For Suggested Items:**
```
┌─────────────────────────────────────────────┐
│ Product A                        5,000 Ar   │
│ Quantity: 2                                 │
│ Store: Pharmacy XYZ                         │
│                                             │
│ Store Status: [SUGGESTED] 2024-10-28 14:30 │
│                                             │
│ ┌─ Suggested Alternative Product ────────┐ │
│ │ Product B                   28,000 Ar   │ │
│ │ Product B has better quality and        │ │
│ │ longer shelf life                       │ │
│ └─────────────────────────────────────────┘ │
│                                             │
│ [Approve Suggestion] [Reject]               │
└─────────────────────────────────────────────┘
```

**After Admin Approval:**
```
┌─────────────────────────────────────────────┐
│ Product B                                   │
│ Quantity: 2                                 │
│ Store: Pharmacy XYZ                         │
│                                             │
│ Store Status: [PENDING] 2024-10-28 14:35   │
│                                             │
│ Notes: Product A is currently out of stock  │
│ [Admin Approved]: Customer agreed to the    │
│ alternative                                 │
└─────────────────────────────────────────────┘
```

### Store Dashboard
Stores see their suggestions and can track status:

```
┌─────────────────────────────────────────────┐
│ Order #ORD-2024-123456                      │
│ Product A → Product B (Suggested)           │
│ Status: Waiting for admin approval          │
│ Your Price: 28,000 Ar                       │
└─────────────────────────────────────────────┘
```

## Business Rules

1. **Only store owners** can suggest alternatives for their store's items
2. **Only admins** can approve suggestions
3. **Product replacement** happens only after admin approval
4. **Status resets to PENDING** after approval so store can confirm price
5. **Store must accept** the new product with their final price
6. **All actions are timestamped** for audit trail
7. **Notes are preserved** through the entire workflow

## API Security

- Store suggestion: Requires `ROLE_STORE` and ownership verification
- Admin approval: Requires `ROLE_ADMIN`
- All endpoints require JWT authentication
- Authorization checks prevent unauthorized access

## Testing the Workflow

### Test Script

```bash
# Set your tokens
STORE_TOKEN="store_jwt_token"
ADMIN_TOKEN="admin_jwt_token"

# 1. Store suggests alternative
curl -X POST http://localhost/api/store/order-item/suggest \
  -H "Authorization: Bearer $STORE_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "orderItemId": 1,
    "suggestedProductId": 5,
    "storePrice": 28000,
    "suggestion": "Better quality alternative",
    "notes": "Original out of stock"
  }'

# 2. Admin approves
curl -X POST http://localhost/api/admin/order-item/approve-suggestion \
  -H "Authorization: Bearer $ADMIN_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "orderItemId": 1,
    "adminNotes": "Customer agreed"
  }'

# 3. Store accepts with final price
curl -X POST http://localhost/api/store/order-item/accept \
  -H "Authorization: Bearer $STORE_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "orderItemId": 1,
    "storePrice": 28000,
    "notes": "Ready for delivery"
  }'
```

## Benefits

1. **Flexibility**: Stores can offer alternatives when products unavailable
2. **Customer Satisfaction**: Admin can consult customer before making changes
3. **Transparency**: All changes are tracked and visible
4. **Price Accuracy**: Store sets final price after product change
5. **Audit Trail**: Complete history of product substitutions

## Future Enhancements

- [ ] Automatic customer notification for suggestions
- [ ] Customer direct approval via mobile app
- [ ] Multiple product suggestions per item
- [ ] Suggested product recommendations based on similarity
- [ ] Auto-approval rules for trusted stores

