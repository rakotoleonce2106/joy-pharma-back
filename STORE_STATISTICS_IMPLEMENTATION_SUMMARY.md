# Store Statistics API Implementation Summary

## What Was Implemented

A new API endpoint for store owners to view comprehensive business statistics including orders, inventory, and earnings.

### Endpoint
- **URL**: `GET /api/store/statistics`
- **Authentication**: Required (ROLE_STORE)
- **Purpose**: Get real-time statistics for the authenticated store owner's store

## Files Created

### 1. DTO (Data Transfer Object)
**File**: `src/Dto/StoreStatistics.php`
- Contains the response structure for store statistics
- Fields:
  - `pendingOrdersCount` (int) - Orders awaiting store action
  - `todayOrdersCount` (int) - Orders received today
  - `lowStockCount` (int) - Products with low inventory
  - `todayEarnings` (float) - Revenue earned today
  - `weeklyEarnings` (float) - Revenue earned this week
  - `monthlyEarnings` (float) - Revenue earned this month

### 2. Provider (State Provider)
**File**: `src/State/Store/StoreStatisticsProvider.php`
- Gets the authenticated user via Security component
- Fetches the store owned by the authenticated user
- Calculates six different statistics:
  1. **Pending Orders**: Counts order items with `PENDING` status
  2. **Today Orders**: Counts all order items created today
  3. **Low Stock**: Counts products with stock ≤ 10 units
  4. **Today Earnings**: Sums earnings from delivered orders today
  5. **Weekly Earnings**: Sums earnings from delivered orders this week
  6. **Monthly Earnings**: Sums earnings from delivered orders this month
- Uses optimized database queries with aggregation functions

### 3. API Resource Configuration
**File**: `src/ApiResource/DeliverySystem.yaml` (Updated)
- Added `App\Dto\StoreStatistics` resource definition
- Configured `get_store_statistics` operation
- Set up security (ROLE_STORE), routing, and OpenAPI documentation
- No path parameters - store is determined from authenticated user

### 4. Documentation
**File**: `STORE_STATISTICS_API.md`
- Complete API documentation
- Request/response examples
- Integration examples (React, Vue.js)
- Use cases and business logic
- Performance recommendations

## Response Structure

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

## Key Features

✅ **Real-time Statistics**: Live data from database aggregation queries  
✅ **Pending Order Tracking**: Know exactly how many orders need attention  
✅ **Inventory Alerts**: Identify products running low on stock  
✅ **Multi-period Earnings**: Track daily, weekly, and monthly revenue  
✅ **Automatic Store Detection**: Store determined from authenticated user  
✅ **Optimized Queries**: Uses COUNT and SUM at database level for performance  
✅ **Security**: JWT authentication required with ROLE_STORE  

## Database Queries Explained

### Pending Orders Count
```sql
SELECT COUNT(DISTINCT oi.id)
FROM order_item oi
WHERE oi.store_id = ? 
  AND oi.store_status = 'pending'
```

### Today Orders Count
```sql
SELECT COUNT(DISTINCT oi.id)
FROM order_item oi
INNER JOIN order o ON oi.order_parent_id = o.id
WHERE oi.store_id = ?
  AND o.created_at >= CURRENT_DATE
```

### Low Stock Count
```sql
SELECT COUNT(sp.id)
FROM store_product sp
WHERE sp.store_id = ?
  AND sp.stock <= 10
  AND sp.stock > 0
  AND sp.status = 1
```

### Earnings Calculations
```sql
SELECT SUM(oi.store_price * oi.quantity)
FROM order_item oi
INNER JOIN order o ON oi.order_parent_id = o.id
WHERE oi.store_id = ?
  AND oi.store_status IN ('accepted', 'approved')
  AND o.status = 'delivered'
  AND o.delivered_at >= [period_start]
```

## Business Logic

### Order Item Status Flow
1. **PENDING**: Customer places order → Store receives notification
2. **ACCEPTED**: Store accepts item → Included in earnings when delivered
3. **APPROVED**: Admin approves suggested alternative → Included in earnings

### Stock Alert Logic
- Threshold: ≤ 10 units (configurable)
- Only counts active products
- Excludes out-of-stock items (stock = 0)
- Helps prevent stockouts

### Earnings Calculation
- Only counts delivered orders (status = DELIVERED)
- Uses store price (not customer price)
- Multiplies by quantity sold
- Filters by accepted/approved items only

## Testing

### Syntax Validation
✅ StoreStatistics.php - No syntax errors  
✅ StoreStatisticsProvider.php - No syntax errors  

### Manual Testing (when Lando is running)
```bash
# Get store statistics (as store owner)
curl -X GET "http://joy-pharma.loc/api/store/statistics" \
  -H "Authorization: Bearer YOUR_STORE_OWNER_JWT_TOKEN" \
  -H "Accept: application/json"
```

### Sample Response
```json
{
  "pendingOrdersCount": 0,
  "todayOrdersCount": 0,
  "lowStockCount": 0,
  "todayEarnings": 0,
  "weeklyEarnings": 0,
  "monthlyEarnings": 0
}
```

## Integration Points

### Store Owner Dashboard
- Display key metrics in dashboard cards
- Show alerts for pending orders
- Highlight low stock warnings
- Track earnings trends

### Store Owner Mobile App
- Home screen statistics widgets
- Push notifications for pending orders
- Low stock alerts
- Daily earnings summary

### Admin Panel
- Monitor store performance
- Identify high/low performing stores
- Track inventory issues across stores

## Performance Optimization

### Query Optimization
- All statistics use database-level aggregation (COUNT, SUM)
- Proper use of indexes on `store`, `storeStatus`, `status` fields
- Joins only when necessary
- Single query per statistic

### Caching Strategy
- **Recommended TTL**: 30-60 seconds
- **Invalidation**: On new orders, stock updates, or deliveries
- **Client-side**: Use SWR or React Query for auto-refresh
- **Real-time updates**: Consider WebSocket for live statistics

### Scaling Considerations
- Statistics are calculated on-demand (no pre-aggregation)
- For very high-traffic stores, consider:
  - Redis caching layer
  - Pre-calculated statistics updated via events
  - Read replicas for statistics queries

## Configuration Options

### Low Stock Threshold
Modify in `StoreStatisticsProvider.php`:
```php
private function getLowStockCount($store, int $threshold = 10): int
```

Change the default value (10) to your preferred threshold.

### Date Range Calculations
- **Today**: Midnight to current time
- **This Week**: Monday 00:00 to current time
- **This Month**: 1st of month 00:00 to current time

All dates use server timezone.

## Error Handling

### 404 Not Found
Returned when authenticated user doesn't own a store.

### 403 Forbidden
Returned when user doesn't have `ROLE_STORE` permission.

### 401 Unauthorized
Returned when no valid authentication token provided.

## Future Enhancements (Optional)

- [ ] Add trend indicators (↑ ↓ vs previous period)
- [ ] Add configurable stock threshold per product
- [ ] Add top-selling products list
- [ ] Add customer satisfaction metrics
- [ ] Add delivery time analytics
- [ ] Add revenue by product category
- [ ] Add export functionality (PDF, CSV)
- [ ] Add historical data comparison
- [ ] Add forecasting/predictions
- [ ] Add real-time WebSocket updates

## Related Documentation

- [STORE_STATISTICS_API.md](STORE_STATISTICS_API.md) - Full API documentation
- [STORE_BUSINESS_HOURS_API.md](STORE_BUSINESS_HOURS_API.md) - Business hours API
- [STORE_FEATURES_GUIDE.md](STORE_FEATURES_GUIDE.md) - Store features guide

## Notes

- No database migrations needed (uses existing schema)
- No additional dependencies required
- Follows existing API Platform patterns
- Compatible with current authentication system
- Ready for immediate use once deployed
- All statistics return 0 for stores with no data

