# Store Pending Order Items API

## Overview

The Store Pending Order Items API allows store owners to retrieve a list of order items that are awaiting their action (status: PENDING). Store owners can then accept, refuse, or suggest alternatives for these items.

## Endpoint

### Get Pending Order Items

**GET** `/api/store/order-items/pending`

Retrieve a paginated list of order items pending action for the authenticated store owner's store.

#### Authentication
- **Required**: Yes (Bearer Token)
- **Role**: `ROLE_STORE`

#### Query Parameters

| Parameter | Type    | Required | Default | Description                    |
|-----------|---------|----------|---------|--------------------------------|
| `page`    | integer | No       | 1       | Page number (starts at 1)      |
| `limit`   | integer | No       | 20      | Items per page (max: 100)      |

#### Response

**Status Code**: `200 OK`

Returns an array of `OrderItem` objects with their associated data.

```json
[
  {
    "id": 1,
    "quantity": 2,
    "totalPrice": 15000.00,
    "storeStatus": "pending",
    "storeNotes": null,
    "storeSuggestion": null,
    "storePrice": null,
    "storeActionAt": null,
    "product": {
      "id": 45,
      "name": "Paracetamol 500mg",
      "code": "PARA500",
      "description": "Pain reliever and fever reducer",
      "images": []
    },
    "suggestedProduct": null,
    "orderParent": {
      "id": 123,
      "reference": "ORD-20250128-001",
      "status": "pending",
      "priority": "standard",
      "phone": "+261340000000",
      "scheduledDate": "2025-01-28T14:00:00+00:00",
      "notes": "Please call before delivery",
      "createdAt": "2025-01-28T10:30:00+00:00",
      "owner": {
        "id": 88,
        "email": "customer@example.com",
        "name": "John Doe"
      },
      "location": {
        "id": 12,
        "address": "123 Main Street, Antananarivo",
        "latitude": -18.8792,
        "longitude": 47.5079
      }
    }
  },
  {
    "id": 2,
    "quantity": 1,
    "totalPrice": 8500.00,
    "storeStatus": "pending",
    "product": {
      "id": 67,
      "name": "Amoxicillin 250mg",
      "code": "AMOX250"
    },
    "orderParent": {
      "id": 124,
      "reference": "ORD-20250128-002",
      "status": "pending",
      "createdAt": "2025-01-28T11:15:00+00:00",
      "owner": {
        "email": "jane@example.com",
        "name": "Jane Smith"
      }
    }
  }
]
```

#### Response Fields

##### OrderItem Fields

| Field               | Type     | Description                                           |
|--------------------|----------|-------------------------------------------------------|
| `id`                | integer  | Order item ID                                         |
| `quantity`          | integer  | Quantity ordered                                      |
| `totalPrice`        | float    | Total price (customer's view)                         |
| `storeStatus`       | string   | Status: "pending", "accepted", "refused", "suggested" |
| `storeNotes`        | string   | Store's notes (null for pending items)                |
| `storeSuggestion`   | string   | Suggestion text (null for pending items)              |
| `storePrice`        | float    | Store's price (null until accepted/suggested)         |
| `storeActionAt`     | datetime | When store took action (null for pending)             |
| `product`           | object   | Product details                                       |
| `suggestedProduct`  | object   | Alternative product (null for pending)                |
| `orderParent`       | object   | Parent order details                                  |

##### Nested Order Fields

| Field           | Type     | Description                              |
|----------------|----------|------------------------------------------|
| `id`            | integer  | Order ID                                 |
| `reference`     | string   | Order reference number                   |
| `status`        | string   | Order status                             |
| `priority`      | string   | Priority: "urgent", "standard", etc.     |
| `phone`         | string   | Customer phone number                    |
| `scheduledDate` | datetime | Scheduled delivery date                  |
| `notes`         | string   | Customer notes                           |
| `createdAt`     | datetime | When order was placed                    |
| `owner`         | object   | Customer information                     |
| `location`      | object   | Delivery location                        |

#### Error Responses

**404 Not Found** - No store associated with the authenticated user
```json
{
  "error": "No store found for this user"
}
```

**401 Unauthorized** - Missing or invalid authentication token
```json
{
  "error": "Authentication required"
}
```

**403 Forbidden** - User does not have ROLE_STORE
```json
{
  "error": "Access denied"
}
```

## Order Item Status Flow

```
PENDING ──┬──> ACCEPTED ──> (Delivered/Completed)
          │
          ├──> REFUSED ──> (Item removed from order)
          │
          └──> SUGGESTED ──┬──> APPROVED ──> (Delivered with alternative)
                           └──> REJECTED ──> (Back to pending or refused)
```

## Use Cases

### 1. Order Management Dashboard
Display a list of pending orders that require immediate attention.

### 2. Order Processing Queue
Store owners can process orders one by one, deciding to accept, refuse, or suggest alternatives.

### 3. Inventory Check
Before accepting an item, store owners can verify they have it in stock.

### 4. Mobile Order Notifications
Mobile apps can poll this endpoint or receive push notifications when new pending items arrive.

### 5. Batch Processing
Store owners can review multiple pending items and process them in bulk.

## Example Requests

### cURL

```bash
# Get first page (default 20 items)
curl -X GET "https://api.joy-pharma.com/api/store/order-items/pending" \
  -H "Authorization: Bearer YOUR_STORE_OWNER_JWT_TOKEN" \
  -H "Accept: application/json"

# Get second page with 50 items per page
curl -X GET "https://api.joy-pharma.com/api/store/order-items/pending?page=2&limit=50" \
  -H "Authorization: Bearer YOUR_STORE_OWNER_JWT_TOKEN" \
  -H "Accept: application/json"
```

### JavaScript (Fetch API)

```javascript
async function fetchPendingOrders(page = 1, limit = 20) {
  const response = await fetch(
    `/api/store/order-items/pending?page=${page}&limit=${limit}`,
    {
      headers: {
        'Authorization': `Bearer ${storeOwnerToken}`,
        'Accept': 'application/json'
      }
    }
  );

  const pendingItems = await response.json();
  
  console.log(`Found ${pendingItems.length} pending order items`);
  return pendingItems;
}
```

### React Example (Pending Orders List)

```jsx
import { useState, useEffect } from 'react';

function PendingOrdersList({ token }) {
  const [items, setItems] = useState([]);
  const [loading, setLoading] = useState(true);
  const [page, setPage] = useState(1);

  useEffect(() => {
    fetchPendingItems();
  }, [page]);

  async function fetchPendingItems() {
    setLoading(true);
    try {
      const response = await fetch(
        `/api/store/order-items/pending?page=${page}&limit=20`,
        {
          headers: {
            'Authorization': `Bearer ${token}`,
            'Accept': 'application/json'
          }
        }
      );
      const data = await response.json();
      setItems(data);
    } catch (error) {
      console.error('Failed to fetch pending items:', error);
    } finally {
      setLoading(false);
    }
  }

  function handleAccept(itemId) {
    // Call accept API
    // Then refresh the list
    fetchPendingItems();
  }

  function handleRefuse(itemId) {
    // Call refuse API
    // Then refresh the list
    fetchPendingItems();
  }

  if (loading) return <div>Loading...</div>;

  return (
    <div className="pending-orders">
      <h2>Pending Orders ({items.length})</h2>
      
      {items.length === 0 ? (
        <div className="empty-state">
          <p>No pending orders! 🎉</p>
        </div>
      ) : (
        <div className="orders-list">
          {items.map(item => (
            <div key={item.id} className="order-card">
              {/* Order Header */}
              <div className="order-header">
                <span className="order-ref">
                  {item.orderParent.reference}
                </span>
                <span className="order-date">
                  {new Date(item.orderParent.createdAt).toLocaleString()}
                </span>
              </div>

              {/* Customer Info */}
              <div className="customer-info">
                <strong>{item.orderParent.owner.name}</strong>
                <span>{item.orderParent.phone}</span>
              </div>

              {/* Product Info */}
              <div className="product-info">
                <h4>{item.product.name}</h4>
                <p>Quantity: {item.quantity}</p>
                <p>Customer Price: ${item.totalPrice}</p>
              </div>

              {/* Delivery Info */}
              {item.orderParent.location && (
                <div className="location-info">
                  <p>📍 {item.orderParent.location.address}</p>
                </div>
              )}

              {/* Notes */}
              {item.orderParent.notes && (
                <div className="notes">
                  <strong>Notes:</strong> {item.orderParent.notes}
                </div>
              )}

              {/* Action Buttons */}
              <div className="actions">
                <button 
                  className="btn-accept"
                  onClick={() => handleAccept(item.id)}
                >
                  ✓ Accept
                </button>
                <button 
                  className="btn-refuse"
                  onClick={() => handleRefuse(item.id)}
                >
                  ✗ Refuse
                </button>
                <button className="btn-suggest">
                  💡 Suggest Alternative
                </button>
              </div>
            </div>
          ))}
        </div>
      )}

      {/* Pagination */}
      <div className="pagination">
        <button 
          disabled={page === 1}
          onClick={() => setPage(page - 1)}
        >
          Previous
        </button>
        <span>Page {page}</span>
        <button 
          disabled={items.length < 20}
          onClick={() => setPage(page + 1)}
        >
          Next
        </button>
      </div>
    </div>
  );
}
```

## Implementation Details

### Provider
- **Class**: `App\State\Store\PendingOrderItemsProvider`
- **Location**: `src/State/Store/PendingOrderItemsProvider.php`

### API Resource Configuration
- **File**: `src/ApiResource/OrderItem.yaml`
- **Operation Name**: `get_pending_order_items`

### Related Endpoints

#### After retrieving pending items, use these endpoints to process them:

- **POST** `/api/store/order-item/accept` - Accept an order item
- **POST** `/api/store/order-item/refuse` - Refuse an order item
- **POST** `/api/store/order-item/suggest` - Suggest an alternative

## Query Optimization

The provider uses optimized queries with:
- **Left Joins**: Loads related entities (product, order, customer, location) in a single query
- **Indexed Fields**: Queries on `store` and `storeStatus` which should be indexed
- **Pagination**: Limits results to prevent memory issues
- **Ordering**: Newest orders first (`ORDER BY o.createdAt DESC`)

## Pagination Strategy

### Default Behavior
- Default page: 1
- Default limit: 20 items
- Maximum limit: 100 items per page

### Handling Large Result Sets

```javascript
async function loadAllPendingItems() {
  let allItems = [];
  let page = 1;
  let hasMore = true;

  while (hasMore) {
    const response = await fetch(
      `/api/store/order-items/pending?page=${page}&limit=100`
    );
    const items = await response.json();
    
    allItems = [...allItems, ...items];
    hasMore = items.length === 100; // If we got 100, there might be more
    page++;
  }

  return allItems;
}
```

## Real-time Updates

### Polling Strategy

```javascript
// Poll every 30 seconds for new pending items
useEffect(() => {
  const interval = setInterval(() => {
    fetchPendingItems();
  }, 30000);

  return () => clearInterval(interval);
}, []);
```

### WebSocket Alternative
For real-time updates without polling, consider implementing WebSocket notifications when new pending items arrive.

## Testing

### Test as Store Owner
```bash
# Get a JWT token for a store owner (ROLE_STORE)
STORE_OWNER_TOKEN="your_store_owner_jwt_token_here"

# Get pending items
curl -X GET "http://localhost/api/store/order-items/pending" \
  -H "Authorization: Bearer $STORE_OWNER_TOKEN" \
  -H "Accept: application/json" | jq .

# Get second page
curl -X GET "http://localhost/api/store/order-items/pending?page=2&limit=10" \
  -H "Authorization: Bearer $STORE_OWNER_TOKEN" \
  -H "Accept: application/json" | jq .
```

## Related Documentation

- [Store Statistics API](STORE_STATISTICS_API.md) - Get pending count
- [Store Business Hours API](STORE_BUSINESS_HOURS_API.md) - Operating hours
- [Store Features Guide](STORE_FEATURES_GUIDE.md) - Complete store features

## Business Rules

1. **Only Pending Items**: Only items with `storeStatus = PENDING` are returned
2. **Store Ownership**: Only items for the authenticated user's store are returned
3. **Newest First**: Items are ordered by creation date (newest first)
4. **Complete Data**: Includes product, order, customer, and location information
5. **Pagination**: Results are paginated for performance

## Common Workflows

### Workflow 1: Accept All Items in Stock

```javascript
async function acceptItemsInStock(pendingItems, inventory) {
  for (const item of pendingItems) {
    const inStock = inventory.find(
      i => i.productId === item.product.id && i.quantity >= item.quantity
    );
    
    if (inStock) {
      await acceptOrderItem({
        orderItemId: item.id,
        storePrice: inStock.price,
        notes: 'In stock - ready to ship'
      });
    }
  }
}
```

### Workflow 2: Refuse Out-of-Stock Items

```javascript
async function refuseOutOfStock(pendingItems, inventory) {
  for (const item of pendingItems) {
    const inStock = inventory.find(
      i => i.productId === item.product.id
    );
    
    if (!inStock || inStock.quantity < item.quantity) {
      await refuseOrderItem({
        orderItemId: item.id,
        notes: 'Out of stock'
      });
    }
  }
}
```

## See Also

- [STORE_API_QUICK_REFERENCE.md](STORE_API_QUICK_REFERENCE.md) - Quick reference
- [MOBILE_STORE_API_COMPLETE.md](MOBILE_STORE_API_COMPLETE.md) - Mobile integration

