# Store API Quick Reference

Quick reference for all Store Owner API endpoints.

## Authentication

All endpoints require:
- **Header**: `Authorization: Bearer YOUR_STORE_OWNER_JWT_TOKEN`
- **Role**: `ROLE_STORE`

## Endpoints

### 1. Store Statistics

**GET** `/api/store/statistics`

Get comprehensive business statistics.

**Response:**
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

**Use Cases:**
- Dashboard overview
- Pending order alerts
- Low stock warnings
- Revenue tracking

---

### 2. Pending Order Items

**GET** `/api/store/order-items/pending`

Get list of order items awaiting store action.

**Query Parameters:**
- `page` (integer, default: 1) - Page number
- `limit` (integer, default: 20, max: 100) - Items per page

**Response:**
```json
[
  {
    "id": 1,
    "quantity": 2,
    "totalPrice": 15000.00,
    "storeStatus": "pending",
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
      }
    }
  }
]
```

**Use Cases:**
- View pending orders
- Process orders queue
- Check inventory before accepting
- Mobile order notifications

---

### 3. Business Hours

**GET** `/api/store/business-hours`

Get weekly operating hours for the store.

**Response:**
```json
{
  "storeId": 1,
  "storeName": "Joy Pharma Downtown",
  "hours": {
    "monday": {
      "isClosed": false,
      "openTime": "09:00",
      "closeTime": "18:00",
      "formatted": "09:00 - 18:00"
    },
    "tuesday": { ... },
    ...
  },
  "isCurrentlyOpen": true,
  "nextOpenTime": null
}
```

**Use Cases:**
- View current hours
- Check if store is open
- Display to customers
- Pre-update review

---

## Common Patterns

### Error Handling

All endpoints return consistent error responses:

**404 Not Found** - No store for user
```json
{
  "error": "No store found for this user"
}
```

**401 Unauthorized** - Missing/invalid token
```json
{
  "error": "Authentication required"
}
```

**403 Forbidden** - Wrong role
```json
{
  "error": "Access denied"
}
```

### Caching Strategy

| Endpoint                  | Recommended TTL | Refresh Strategy                  |
|--------------------------|-----------------|-----------------------------------|
| `/statistics`             | 30-60 seconds   | Auto-refresh on interval          |
| `/order-items/pending`    | 15-30 seconds   | Poll for new orders               |
| `/business-hours`         | 1 hour          | Refresh after edits               |

### Auto-Refresh Example

```javascript
// Fetch statistics every 30 seconds
useEffect(() => {
  const fetchStats = async () => {
    const response = await fetch('/api/store/statistics', {
      headers: { 'Authorization': `Bearer ${token}` }
    });
    setStats(await response.json());
  };

  fetchStats(); // Initial fetch
  const interval = setInterval(fetchStats, 30000);
  return () => clearInterval(interval);
}, [token]);
```

## Dashboard Implementation

### Minimal Dashboard

```jsx
function StoreDashboard({ token }) {
  const [stats, setStats] = useState(null);
  const [hours, setHours] = useState(null);

  useEffect(() => {
    // Fetch statistics
    fetch('/api/store/statistics', {
      headers: { 'Authorization': `Bearer ${token}` }
    })
      .then(res => res.json())
      .then(setStats);

    // Fetch business hours
    fetch('/api/store/business-hours', {
      headers: { 'Authorization': `Bearer ${token}` }
    })
      .then(res => res.json())
      .then(setHours);
  }, [token]);

  if (!stats || !hours) return <div>Loading...</div>;

  return (
    <div>
      <h1>{hours.storeName}</h1>
      
      {/* Status Badge */}
      <div className={hours.isCurrentlyOpen ? 'open' : 'closed'}>
        {hours.isCurrentlyOpen ? '🟢 Open' : '🔴 Closed'}
      </div>

      {/* Statistics Grid */}
      <div className="grid">
        <StatCard 
          label="Pending Orders" 
          value={stats.pendingOrdersCount}
          alert={stats.pendingOrdersCount > 0}
        />
        <StatCard 
          label="Today's Orders" 
          value={stats.todayOrdersCount}
        />
        <StatCard 
          label="Low Stock" 
          value={stats.lowStockCount}
          alert={stats.lowStockCount > 0}
        />
      </div>

      {/* Earnings */}
      <div className="earnings">
        <div>Today: ${stats.todayEarnings}</div>
        <div>Week: ${stats.weeklyEarnings}</div>
        <div>Month: ${stats.monthlyEarnings}</div>
      </div>
    </div>
  );
}
```

## Testing Commands

```bash
# Store owner JWT token
TOKEN="your_store_owner_jwt_token"

# Get statistics
curl -X GET "http://localhost/api/store/statistics" \
  -H "Authorization: Bearer $TOKEN" | jq .

# Get pending order items
curl -X GET "http://localhost/api/store/order-items/pending" \
  -H "Authorization: Bearer $TOKEN" | jq .

# Get business hours
curl -X GET "http://localhost/api/store/business-hours" \
  -H "Authorization: Bearer $TOKEN" | jq .
```

## Related Documentation

- [STORE_STATISTICS_API.md](STORE_STATISTICS_API.md) - Statistics endpoint details
- [STORE_PENDING_ORDER_ITEMS_API.md](STORE_PENDING_ORDER_ITEMS_API.md) - Pending orders endpoint details
- [STORE_BUSINESS_HOURS_API.md](STORE_BUSINESS_HOURS_API.md) - Business hours endpoint details
- [STORE_STATISTICS_IMPLEMENTATION_SUMMARY.md](STORE_STATISTICS_IMPLEMENTATION_SUMMARY.md) - Implementation notes
- [BUSINESS_HOURS_IMPLEMENTATION_SUMMARY.md](BUSINESS_HOURS_IMPLEMENTATION_SUMMARY.md) - Implementation notes

## Quick Stats Reference

### Statistics Fields

| Field                | Description                          | Type    |
|---------------------|--------------------------------------|---------|
| `pendingOrdersCount` | Orders awaiting store action        | integer |
| `todayOrdersCount`   | Orders received today               | integer |
| `lowStockCount`      | Products with stock ≤ 10            | integer |
| `todayEarnings`      | Revenue from today's deliveries     | float   |
| `weeklyEarnings`     | Revenue this week (Mon-today)       | float   |
| `monthlyEarnings`    | Revenue this month                  | float   |

### Business Hours Fields

| Field              | Description                      | Type    |
|-------------------|----------------------------------|---------|
| `storeId`          | Store ID                        | integer |
| `storeName`        | Store name                      | string  |
| `hours`            | Weekly schedule object          | object  |
| `isCurrentlyOpen`  | Store open right now?           | boolean |
| `nextOpenTime`     | When store opens next           | string  |

## Mobile App Integration

### Statistics Widget
```jsx
<View style={styles.statsGrid}>
  <StatCard icon="⏳" value={stats.pendingOrdersCount} label="Pending" />
  <StatCard icon="📦" value={stats.todayOrdersCount} label="Today" />
  <StatCard icon="⚠️" value={stats.lowStockCount} label="Low Stock" />
  <StatCard icon="💰" value={`$${stats.todayEarnings}`} label="Earnings" />
</View>
```

### Status Banner
```jsx
<StatusBanner 
  isOpen={hours.isCurrentlyOpen}
  nextOpen={hours.nextOpenTime}
  storeName={hours.storeName}
/>
```

## Performance Tips

1. **Batch Requests**: Fetch both endpoints on dashboard load
2. **Stale While Revalidate**: Show cached data while fetching fresh data
3. **Error Boundaries**: Handle network failures gracefully
4. **Loading States**: Show skeletons during initial load
5. **Optimistic Updates**: Update UI before API confirms (where applicable)

