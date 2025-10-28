# Store Order Management - Fixes Summary

## Issue Resolved

**Error:** `column t0.store_total_amount does not exist`

## Root Causes

1. Migration not run yet
2. Store pricing logic needed clarification
3. Inventory validation missing

## Solutions Implemented

### 1. Database Schema ✅

**Migration File:** `migrations/Version20251028000000.php`

**Added to `order_item`:**
- `store_status` - Order item status from store perspective
- `store_notes` - Store's notes
- `store_suggestion` - Text explanation for suggestion
- `suggested_product_id` - Foreign key to Product (alternative product)
- `store_price` - Store's price (from StoreProduct)
- `store_action_at` - Timestamp of store action

**Added to `order`:**
- `store_total_amount` - Total based on store prices

### 2. Store Inventory Validation ✅

**Business Rules:**
- Stores can ONLY accept products in their inventory (StoreProduct table)
- Stores can ONLY suggest products in their inventory
- Both validation checks added to processors

**Error Messages:**
```
"This product is not available in your store. You can suggest an alternative product instead."
"The suggested product is not available in your store inventory"
"Store product has no valid price set"
```

### 3. Automatic Price Fetching ✅

**OLD Behavior (REMOVED):**
```json
// Store manually enters price ❌
{
  "orderItemId": 123,
  "storePrice": 25000.00,  // Manual input
  "notes": "Available"
}
```

**NEW Behavior:**
```json
// Price fetched from StoreProduct ✅
{
  "orderItemId": 123,
  "notes": "Available"
}
// System fetches price from: 
// SELECT price FROM store_product 
// WHERE store_id = X AND product_id = Y
```

### 4. Store Total Calculation ✅

**Formula:**
```
storeTotalAmount = SUM(quantity × storeProduct.price)
                   WHERE storeStatus = 'accepted'
```

**Implementation:**
```php
// In Order::calculateTotalAmount()
foreach ($this->items as $item) {
    if ($item->getStoreStatus() === OrderItemStatus::ACCEPTED) {
        $storeProduct = getStoreProductForItem($item);
        $storeTotal += $storeProduct->getPrice() * $item->getQuantity();
    }
}
```

## Files Modified

| File | Changes |
|------|---------|
| `Order.php` | Updated calculation to use StoreProduct price |
| `OrderItem.php` | Added `suggestedProduct` field |
| `AcceptOrderItemInput.php` | Removed `storePrice` parameter |
| `SuggestOrderItemInput.php` | Removed `storePrice` parameter |
| `AcceptOrderItemProcessor.php` | Added inventory validation, auto-fetch price |
| `SuggestOrderItemProcessor.php` | Added inventory validation, auto-fetch price |
| `ApproveOrderItemSuggestionProcessor.php` | Product replacement logic |

## Migration Instructions

### Option 1: Run Script (Recommended)
```bash
./run_store_migration.sh
```

### Option 2: Manual
```bash
# Start database if needed
docker compose up -d

# Run migration
php bin/console doctrine:migrations:migrate

# Verify
php bin/console doctrine:query:sql "SELECT column_name FROM information_schema.columns WHERE table_name = 'order_item'"
```

## API Changes

### Accept Order Item

**Before:**
```http
POST /api/store/order-item/accept
{
  "orderItemId": 123,
  "storePrice": 25000.00,  ❌ REMOVED
  "notes": "Available"
}
```

**After:**
```http
POST /api/store/order-item/accept
{
  "orderItemId": 123,
  "notes": "Available"  ✅ Price auto-fetched
}
```

### Suggest Alternative

**Before:**
```http
POST /api/store/order-item/suggest
{
  "orderItemId": 123,
  "suggestedProductId": 456,
  "storePrice": 27000.00,  ❌ REMOVED
  "suggestion": "Better product"
}
```

**After:**
```http
POST /api/store/order-item/suggest
{
  "orderItemId": 123,
  "suggestedProductId": 456,  ✅ Must be in store inventory
  "suggestion": "Better product"  ✅ Price auto-fetched
}
```

## Testing the Fix

### 1. Run Migration
```bash
./run_store_migration.sh
```

### 2. Verify Database
```sql
-- Check if columns exist
\d order_item

-- Should show:
-- store_status, store_notes, store_suggestion, 
-- suggested_product_id, store_price, store_action_at
```

### 3. Test Accept Endpoint
```bash
# Get JWT token first
TOKEN="your_store_jwt_token"

# Try to accept an order item
curl -X POST http://localhost/api/store/order-item/accept \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "orderItemId": 1,
    "notes": "Ready to process"
  }'

# Should return:
# - Success if product is in store's inventory
# - Error if product not in store's inventory
```

### 4. Test Suggest Endpoint
```bash
curl -X POST http://localhost/api/store/order-item/suggest \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "orderItemId": 1,
    "suggestedProductId": 5,
    "suggestion": "Better alternative"
  }'

# Should return:
# - Success if suggested product is in store's inventory
# - Error if suggested product not in store's inventory
```

## Expected Behavior After Fix

### ✅ Working Scenarios

**Scenario 1: Accept Product in Inventory**
1. Order item has Product A
2. Store has Product A in StoreProduct table
3. Store accepts → Price fetched automatically
4. Status → ACCEPTED

**Scenario 2: Suggest Alternative**
1. Order item has Product A (not in store inventory)
2. Store has Product B in StoreProduct table
3. Store suggests Product B → Price fetched from StoreProduct
4. Status → SUGGESTED
5. Admin approves → Product A replaced with Product B
6. Status → PENDING
7. Store accepts → Price fetched again
8. Status → ACCEPTED

### ❌ Error Scenarios

**Error 1: Product Not in Inventory**
```
Store tries to accept Product A
→ Product A not in their StoreProduct table
→ Error: "This product is not available in your store"
```

**Error 2: Suggest Product Not in Inventory**
```
Store tries to suggest Product B
→ Product B not in their StoreProduct table
→ Error: "The suggested product is not available in your store inventory"
```

**Error 3: No Price Set**
```
Store tries to accept Product A
→ Product A exists in StoreProduct but price = NULL
→ Error: "Store product has no valid price set"
```

## Benefits

1. ✅ **Data Integrity**: Only products in inventory can be processed
2. ✅ **Price Consistency**: Prices come from configured StoreProduct
3. ✅ **No Manual Errors**: Automatic price fetching eliminates typos
4. ✅ **Audit Trail**: All actions tracked with timestamps
5. ✅ **Business Logic**: Enforces proper store-product relationships

## Troubleshooting

### Issue: Migration Fails

**Check:**
```bash
# Verify database is running
docker compose ps

# Check migrations status
php bin/console doctrine:migrations:status

# Try migrating one version at a time
php bin/console doctrine:migrations:migrate --allow-no-migration
```

### Issue: Store Cannot Accept Product

**Check:**
1. Does StoreProduct entry exist?
   ```sql
   SELECT * FROM store_product 
   WHERE store_id = ? AND product_id = ?
   ```
2. Is price set and > 0?
3. Is the correct store owner logged in?

### Issue: Price is NULL

**Fix:**
```sql
-- Update missing prices
UPDATE store_product 
SET price = <default_price> 
WHERE price IS NULL OR price = 0;
```

## Documentation

📚 **Comprehensive Guides:**
- `STORE_PRODUCT_INVENTORY_SYSTEM.md` - Complete system overview
- `STORE_PRODUCT_SUGGESTION_WORKFLOW.md` - Detailed workflow
- `STORE_SUGGESTION_QUICK_GUIDE.md` - Quick reference
- `STORE_ORDER_MANAGEMENT_IMPLEMENTATION.md` - Original implementation

## Summary

✅ **Migration created** - Ready to run
✅ **Inventory validation** - Stores can only process their products
✅ **Automatic pricing** - Fetched from StoreProduct
✅ **Store total calculation** - Formula: quantity × storeProduct.price
✅ **Product suggestions** - Linked to actual products
✅ **Error handling** - Clear messages for validation failures

**Next Step:** Run `./run_store_migration.sh` to apply changes! 🚀

