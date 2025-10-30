# Store Pending Order Items Implementation Summary

## What Was Implemented

A new API endpoint for store owners to retrieve pending order items awaiting their action.

### Endpoint
- **URL**: `GET /api/store/order-items/pending`
- **Authentication**: Required (ROLE_STORE)
- **Purpose**: Get paginated list of order items with status PENDING for the authenticated store owner

## Files Created

### 1. Provider (State Provider)
**File**: `src/State/Store/PendingOrderItemsProvider.php`
- Gets the authenticated user via Security component
- Fetches the store owned by the authenticated user
- Queries OrderItem entities with:
  - Filter: `store = authenticated user's store`
  - Filter: `storeStatus = PENDING`
  - Left joins: product, order, customer, location
  - Ordering: newest first (by order creation date)
  - Pagination: configurable page and limit
- Returns array of OrderItem entities with full related data

### 2. API Resource Configuration
**File**: `src/ApiResource/OrderItem.yaml` (Updated)
- Added `get_pending_order_items` operation to existing OrderItem resource
- Configured GetCollection endpoint
- Set up security (ROLE_STORE), routing, and OpenAPI documentation
- Configured normalization groups: `order:read`, `product:read`
- Added pagination parameters (page, limit)

### 3. Documentation
**File**: `STORE_PENDING_ORDER_ITEMS_API.md`
- Complete API documentation
- Request/response examples
- Integration examples (React)
- Use cases and workflows
- Testing guide

### 4. Quick Reference Update
**File**: `STORE_API_QUICK_REFERENCE.md` (Updated)
- Added pending orders endpoint
- Updated caching strategy
- Added testing command

## Response Structure

Returns an array of OrderItem objects:

```json
[
  {
    "id": 1,
    "quantity": 2,
    "totalPrice": 15000.00,
    "storeStatus": "pending",
    "storeNotes": null,
    "product": {
      "id": 45,
      "name": "Paracetamol 500mg",
      "code": "PARA500"
    },
    "orderParent": {
      "id": 123,
      "reference": "ORD-20250128-001",
      "status": "pending",
      "phone": "+261340000000",
      "owner": {
        "name": "John Doe"
      },
      "location": {
        "address": "123 Main Street"
      }
    }
  }
]
```

## Key Features

✅ **Automatic Store Detection**: Gets store from authenticated user  
✅ **Complete Order Data**: Includes product, order, customer, and location via joins  
✅ **Pagination**: Supports page/limit parameters (max 100 per page)  
✅ **Optimized Query**: Single query with left joins for all related data  
✅ **Newest First**: Orders by creation date descending  
✅ **Security**: Requires `ROLE_STORE` authentication  
✅ **Ready to Process**: Returned items can be accepted/refused/suggested  

## Query Details

### SQL Query (Simplified)
```sql
SELECT oi.*, p.*, o.*, customer.*, loc.*
FROM order_item oi
LEFT JOIN product p ON oi.product_id = p.id
LEFT JOIN `order` o ON oi.order_parent_id = o.id
LEFT JOIN user customer ON o.owner_id = customer.id
LEFT JOIN location loc ON o.location_id = loc.id
WHERE oi.store_id = ?
  AND oi.store_status = 'pending'
ORDER BY o.created_at DESC
LIMIT ? OFFSET ?
```

### Query Optimization
- **Indexed Fields**: Queries use indexed fields (store_id, store_status)
- **Left Joins**: Efficiently loads all related data in single query
- **Pagination**: Prevents loading too many records at once
- **Selective Loading**: Only loads pending items, not all items

## Related Endpoints

After fetching pending items, store owners can process them using:

### Accept Order Item
**POST** `/api/store/order-item/accept`
```json
{
  "orderItemId": 1,
  "storePrice": 15000.00,
  "notes": "In stock - ready to ship"
}
```

### Refuse Order Item
**POST** `/api/store/order-item/refuse`
```json
{
  "orderItemId": 1,
  "notes": "Out of stock"
}
```

### Suggest Alternative
**POST** `/api/store/order-item/suggest`
```json
{
  "orderItemId": 1,
  "suggestedProductId": 67,
  "storePrice": 12000.00,
  "notes": "Similar product, lower price"
}
```

## Order Item Status Flow

```
PENDING ──┬──> ACCEPTED ──> Delivered
          │
          ├──> REFUSED ──> Cancelled
          │
          └──> SUGGESTED ──┬──> APPROVED ──> Delivered
                           └──> REJECTED ──> Cancelled
```

## Testing

### Syntax Validation
✅ PendingOrderItemsProvider.php - No syntax errors  
✅ OrderItem.yaml - No syntax errors  

### Manual Testing
```bash
# Get pending order items (first page, 20 items)
STORE_TOKEN="your_store_owner_jwt_token"

curl -X GET "http://localhost/api/store/order-items/pending" \
  -H "Authorization: Bearer $STORE_TOKEN" | jq .

# Get second page with 50 items
curl -X GET "http://localhost/api/store/order-items/pending?page=2&limit=50" \
  -H "Authorization: Bearer $STORE_TOKEN" | jq .
```

## Integration Points

### Store Owner Dashboard
- Display pending orders count from statistics endpoint
- Show list of pending items for processing
- Quick actions to accept/refuse items

### Store Owner Mobile App
- Home screen badge showing pending count
- Pending orders screen with list
- Swipe actions for quick accept/refuse
- Push notifications for new pending items

### Order Processing Workflow
1. Fetch pending items
2. Check inventory for each item
3. Accept items in stock with store price
4. Refuse out-of-stock items
5. Suggest alternatives when available

## Performance Considerations

### Database Performance
- Single optimized query with joins
- Indexed fields used in WHERE clause
- Pagination prevents large result sets
- Ordered by date for consistent pagination

### Caching Strategy
- **Recommended TTL**: 15-30 seconds
- **Polling Interval**: 30 seconds for real-time feel
- **Invalidation**: On accept/refuse/suggest actions
- **Alternative**: WebSocket for real-time push notifications

### Pagination Best Practices
- Default: 20 items per page
- Maximum: 100 items per page
- Use limit=100 for batch processing
- Use limit=10-20 for mobile apps

## Error Handling

### 404 Not Found
Store owner has no store associated with their account.

### 403 Forbidden
User doesn't have `ROLE_STORE` permission.

### 401 Unauthorized
No valid authentication token provided.

## Common Use Cases

### 1. Dashboard Overview
```javascript
// Fetch pending count from statistics
const stats = await fetch('/api/store/statistics');
// If pendingOrdersCount > 0, show alert

// Fetch first 10 pending items for preview
const pending = await fetch('/api/store/order-items/pending?limit=10');
```

### 2. Batch Processing
```javascript
// Fetch all pending items
async function processAllPending() {
  const items = await fetch('/api/store/order-items/pending?limit=100');
  
  for (const item of items) {
    const inStock = checkInventory(item.product.id, item.quantity);
    
    if (inStock) {
      await acceptItem(item.id, getStorePrice(item.product.id));
    } else {
      await refuseItem(item.id, 'Out of stock');
    }
  }
}
```

### 3. Auto-Accept with Rules
```javascript
// Auto-accept items based on business rules
async function autoAcceptEligible() {
  const pending = await fetch('/api/store/order-items/pending');
  
  for (const item of pending) {
    if (item.quantity <= 10 && inStandardCatalog(item.product.id)) {
      await acceptItem(item.id, getStandardPrice(item.product.id));
    }
  }
}
```

## Future Enhancements (Optional)

- [ ] Add filtering by order priority (urgent, standard)
- [ ] Add filtering by date range
- [ ] Add sorting options (by price, quantity, date)
- [ ] Add search by customer name or order reference
- [ ] Add bulk accept/refuse endpoints
- [ ] Add WebSocket real-time notifications
- [ ] Add estimated value calculation
- [ ] Add export to CSV/Excel
- [ ] Add auto-assignment rules engine

## Related Documentation

- [STORE_PENDING_ORDER_ITEMS_API.md](STORE_PENDING_ORDER_ITEMS_API.md) - Full API documentation
- [STORE_STATISTICS_API.md](STORE_STATISTICS_API.md) - Statistics endpoint (includes pending count)
- [STORE_API_QUICK_REFERENCE.md](STORE_API_QUICK_REFERENCE.md) - Quick reference guide

## Notes

- No database migrations needed (uses existing OrderItem entity)
- No additional dependencies required
- Follows existing API Platform patterns
- Compatible with current authentication system
- Ready for immediate use once deployed
- Returns empty array `[]` when no pending items exist

