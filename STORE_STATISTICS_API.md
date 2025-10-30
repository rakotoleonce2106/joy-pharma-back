# Store Statistics API

## Overview

The Store Statistics API provides store owners with key metrics about their store's performance, including pending orders, daily activity, inventory levels, and earnings across different time periods.

## Endpoint

### Get Store Statistics

**GET** `/api/store/statistics`

Retrieve comprehensive statistics for the authenticated store owner's store.

#### Authentication
- **Required**: Yes (Bearer Token)
- **Role**: `ROLE_STORE`

#### Path Parameters

None - The store is automatically determined from the authenticated user.

#### Response

**Status Code**: `200 OK`

```json
{
  "pendingOrdersCount": 5,
  "todayOrdersCount": 12,
  "lowStockCount": 3,
  "todayEarnings": 2450.50,
  "weeklyEarnings": 15780.75,
  "monthlyEarnings": 68920.00
}
```

#### Response Fields

| Field                | Type   | Description                                                          |
|---------------------|--------|----------------------------------------------------------------------|
| `pendingOrdersCount` | integer| Number of order items pending store action (status: PENDING)         |
| `todayOrdersCount`   | integer| Total number of order items received today                           |
| `lowStockCount`      | integer| Number of products with stock ≤ 10 units                             |
| `todayEarnings`      | float  | Total earnings from delivered orders today                           |
| `weeklyEarnings`     | float  | Total earnings from delivered orders this week (Monday-today)        |
| `monthlyEarnings`    | float  | Total earnings from delivered orders this month                      |

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

## Business Logic

### Pending Orders Count
- Counts `OrderItem` records where:
  - `store` = authenticated user's store
  - `storeStatus` = `PENDING`
- These are orders waiting for the store to accept/refuse/suggest alternatives

### Today Orders Count
- Counts `OrderItem` records where:
  - `store` = authenticated user's store
  - Parent `Order.createdAt` >= today's date
- Includes all order items regardless of status

### Low Stock Count
- Counts `StoreProduct` records where:
  - `store` = authenticated user's store
  - `stock` ≤ 10 units (configurable threshold)
  - `stock` > 0 (excludes out of stock)
  - `status` = active
- Used to alert store owners about inventory that needs restocking

### Today Earnings
- Sums `(storePrice × quantity)` from `OrderItem` where:
  - `store` = authenticated user's store
  - `storeStatus` IN (`ACCEPTED`, `APPROVED`)
  - Parent `Order.status` = `DELIVERED`
  - Parent `Order.deliveredAt` >= today's date

### Weekly Earnings
- Same as Today Earnings but:
  - Parent `Order.deliveredAt` >= Monday of current week

### Monthly Earnings
- Same as Today Earnings but:
  - Parent `Order.deliveredAt` >= First day of current month

## Use Cases

### 1. Store Owner Dashboard
Display key metrics on the main dashboard to give store owners an at-a-glance view of their business performance.

### 2. Pending Order Alerts
Show notification badges or alerts when there are pending orders requiring action.

### 3. Inventory Management
Alert store owners about low stock items that need to be reordered.

### 4. Financial Tracking
Track daily, weekly, and monthly revenue to monitor business performance and trends.

### 5. Mobile App Home Screen
Display statistics in widget cards on the mobile app home screen.

## Example Requests

### cURL

```bash
curl -X GET "https://api.joy-pharma.com/api/store/statistics" \
  -H "Authorization: Bearer YOUR_STORE_OWNER_JWT_TOKEN" \
  -H "Accept: application/json"
```

### JavaScript (Fetch API)

```javascript
const response = await fetch('https://api.joy-pharma.com/api/store/statistics', {
  method: 'GET',
  headers: {
    'Authorization': `Bearer ${storeOwnerToken}`,
    'Accept': 'application/json'
  }
});

const statistics = await response.json();

console.log(`Pending orders: ${statistics.pendingOrdersCount}`);
console.log(`Today's earnings: $${statistics.todayEarnings}`);
console.log(`Low stock items: ${statistics.lowStockCount}`);
```

### React Example (Store Owner Dashboard)

```jsx
import { useState, useEffect } from 'react';

function StoreStatistics({ token }) {
  const [stats, setStats] = useState(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    async function fetchStatistics() {
      try {
        const response = await fetch('/api/store/statistics', {
          headers: {
            'Authorization': `Bearer ${token}`,
            'Accept': 'application/json'
          }
        });
        const data = await response.json();
        setStats(data);
      } catch (error) {
        console.error('Failed to fetch statistics:', error);
      } finally {
        setLoading(false);
      }
    }

    fetchStatistics();
    
    // Refresh every 30 seconds
    const interval = setInterval(fetchStatistics, 30000);
    return () => clearInterval(interval);
  }, [token]);

  if (loading) return <div>Loading statistics...</div>;
  if (!stats) return <div>Unable to load statistics</div>;

  return (
    <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
      {/* Pending Orders Card */}
      <div className="bg-white p-6 rounded-lg shadow">
        <div className="flex items-center justify-between">
          <div>
            <p className="text-gray-500 text-sm">Pending Orders</p>
            <h3 className="text-3xl font-bold text-orange-600">
              {stats.pendingOrdersCount}
            </h3>
          </div>
          <div className="text-orange-500">
            <svg className="w-12 h-12" /* icon */></svg>
          </div>
        </div>
        {stats.pendingOrdersCount > 0 && (
          <button className="mt-4 text-orange-600 hover:text-orange-700">
            View Pending Orders →
          </button>
        )}
      </div>

      {/* Today's Orders Card */}
      <div className="bg-white p-6 rounded-lg shadow">
        <div className="flex items-center justify-between">
          <div>
            <p className="text-gray-500 text-sm">Today's Orders</p>
            <h3 className="text-3xl font-bold text-blue-600">
              {stats.todayOrdersCount}
            </h3>
          </div>
          <div className="text-blue-500">
            <svg className="w-12 h-12" /* icon */></svg>
          </div>
        </div>
      </div>

      {/* Low Stock Alert Card */}
      <div className="bg-white p-6 rounded-lg shadow">
        <div className="flex items-center justify-between">
          <div>
            <p className="text-gray-500 text-sm">Low Stock Items</p>
            <h3 className="text-3xl font-bold text-red-600">
              {stats.lowStockCount}
            </h3>
          </div>
          <div className="text-red-500">
            <svg className="w-12 h-12" /* icon */></svg>
          </div>
        </div>
        {stats.lowStockCount > 0 && (
          <button className="mt-4 text-red-600 hover:text-red-700">
            View Low Stock →
          </button>
        )}
      </div>

      {/* Earnings Summary Card (Spans full width on mobile, 3 cols on desktop) */}
      <div className="bg-white p-6 rounded-lg shadow md:col-span-3">
        <h4 className="text-lg font-semibold mb-4">Earnings Overview</h4>
        <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div>
            <p className="text-gray-500 text-sm">Today</p>
            <h3 className="text-2xl font-bold text-green-600">
              ${stats.todayEarnings.toFixed(2)}
            </h3>
          </div>
          <div>
            <p className="text-gray-500 text-sm">This Week</p>
            <h3 className="text-2xl font-bold text-green-600">
              ${stats.weeklyEarnings.toFixed(2)}
            </h3>
          </div>
          <div>
            <p className="text-gray-500 text-sm">This Month</p>
            <h3 className="text-2xl font-bold text-green-600">
              ${stats.monthlyEarnings.toFixed(2)}
            </h3>
          </div>
        </div>
      </div>
    </div>
  );
}
```

### Vue.js Example

```vue
<template>
  <div v-if="loading">Loading statistics...</div>
  <div v-else-if="stats" class="statistics-dashboard">
    <!-- Pending Orders -->
    <div class="stat-card">
      <span class="stat-label">Pending Orders</span>
      <span class="stat-value">{{ stats.pendingOrdersCount }}</span>
    </div>

    <!-- Today's Orders -->
    <div class="stat-card">
      <span class="stat-label">Today's Orders</span>
      <span class="stat-value">{{ stats.todayOrdersCount }}</span>
    </div>

    <!-- Low Stock -->
    <div class="stat-card" :class="{ 'alert': stats.lowStockCount > 0 }">
      <span class="stat-label">Low Stock Items</span>
      <span class="stat-value">{{ stats.lowStockCount }}</span>
    </div>

    <!-- Earnings -->
    <div class="earnings-card">
      <h3>Earnings</h3>
      <div class="earnings-grid">
        <div>
          <span>Today</span>
          <strong>${{ stats.todayEarnings.toFixed(2) }}</strong>
        </div>
        <div>
          <span>This Week</span>
          <strong>${{ stats.weeklyEarnings.toFixed(2) }}</strong>
        </div>
        <div>
          <span>This Month</span>
          <strong>${{ stats.monthlyEarnings.toFixed(2) }}</strong>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  data() {
    return {
      stats: null,
      loading: true
    };
  },
  async mounted() {
    await this.fetchStatistics();
    // Auto-refresh every 30 seconds
    this.interval = setInterval(this.fetchStatistics, 30000);
  },
  beforeUnmount() {
    if (this.interval) {
      clearInterval(this.interval);
    }
  },
  methods: {
    async fetchStatistics() {
      try {
        const response = await fetch('/api/store/statistics', {
          headers: {
            'Authorization': `Bearer ${this.token}`,
            'Accept': 'application/json'
          }
        });
        this.stats = await response.json();
      } catch (error) {
        console.error('Failed to fetch statistics:', error);
      } finally {
        this.loading = false;
      }
    }
  }
};
</script>
```

## Implementation Details

### Provider
- **Class**: `App\State\Store\StoreStatisticsProvider`
- **Location**: `src/State/Store/StoreStatisticsProvider.php`

### DTO
- **Class**: `App\Dto\StoreStatistics`
- **Location**: `src/Dto/StoreStatistics.php`

### API Resource Configuration
- **File**: `src/ApiResource/DeliverySystem.yaml`
- **Operation Name**: `get_store_statistics`

### Related Repositories
- `StoreRepository` - Find store by owner
- `OrderItemRepository` - Query order items by store
- `StoreProductRepository` - Query product inventory

## Performance Considerations

### Caching Recommendations
Statistics data changes frequently but doesn't need to be real-time to the second:

- **Client-side cache**: 30 seconds to 1 minute
- **Refresh strategy**: Auto-refresh on interval or pull-to-refresh
- **Background updates**: Fetch in background when app is active

### Database Optimization
The provider uses optimized queries with:
- Indexed fields (`store`, `storeStatus`, `status`)
- Aggregate functions (COUNT, SUM) at database level
- Proper join strategies for related entities

## Alert Thresholds

### Low Stock Threshold
Currently set to **10 units** (configurable in provider)

You can adjust this per your business needs by modifying the `getLowStockCount()` method:

```php
private function getLowStockCount($store, int $threshold = 10): int
{
    // Change threshold value here
}
```

### Recommended Alert Levels
- **Critical**: < 5 units (Red)
- **Warning**: 5-10 units (Orange)
- **Normal**: > 10 units (Green)

## Testing

### Test as Store Owner
```bash
# Get a JWT token for a store owner (ROLE_STORE)
STORE_OWNER_TOKEN="your_store_owner_jwt_token_here"

curl -X GET "http://localhost/api/store/statistics" \
  -H "Authorization: Bearer $STORE_OWNER_TOKEN" \
  -H "Accept: application/json" | jq .
```

### Expected Response Structure
The response should always include all six statistics fields with numeric values:
```json
{
  "pendingOrdersCount": 0,
  "todayOrdersCount": 0,
  "lowStockCount": 0,
  "todayEarnings": 0.0,
  "weeklyEarnings": 0.0,
  "monthlyEarnings": 0.0
}
```

## Related Endpoints

- `GET /api/store/business-hours` - Get store operating hours
- `GET /api/store/products` - Manage store products and inventory
- `GET /api/store/orders` - View store orders

## See Also

- [Store Business Hours API](STORE_BUSINESS_HOURS_API.md)
- [Store Features Guide](STORE_FEATURES_GUIDE.md)
- [Mobile Store API Complete](MOBILE_STORE_API_COMPLETE.md)

