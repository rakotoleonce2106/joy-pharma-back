# Store Product Inventory System

## Overview
The system ensures that stores can only accept or suggest products that are in their inventory (StoreProduct table). Prices are automatically fetched from the store's product pricing.

## Key Concepts

### 1. StoreProduct Relationship
- Each store has their own product inventory via the `StoreProduct` entity
- `StoreProduct` contains store-specific pricing and stock information
- Only products in a store's inventory can be accepted or suggested

### 2. Automatic Pricing
- Prices are **NOT** entered by store when accepting/suggesting
- Prices are **automatically fetched** from `StoreProduct` table
- Formula: `storeTotalAmount = quantity × storeProduct.price`

## Database Schema

```
┌─────────────┐       ┌──────────────┐       ┌─────────┐
│   Order     │       │  OrderItem   │       │ Product │
├─────────────┤       ├──────────────┤       ├─────────┤
│ id          │◄──┐   │ id           │   ┌──►│ id      │
│ totalAmount │   └──►│ order_id     │   │   │ name    │
│ storeTotal  │       │ product_id   ├───┘   │ code    │
│   Amount    │       │ store_id     ├───┐   └─────────┘
└─────────────┘       │ quantity     │   │
                      │ storeStatus  │   │   ┌──────────────┐
                      │ storePrice   │   │   │ StoreProduct │
                      │ suggested    │   │   ├──────────────┤
                      │   _product_id├───┼──►│ id           │
                      └──────────────┘   └──►│ store_id     │
                                             │ product_id   │
                                             │ price        │
                                             │ stock        │
                                             └──────────────┘
```

## Business Rules

### ✅ Store Can Accept IF:
1. Product exists in their `StoreProduct` inventory
2. `StoreProduct.price` is set and > 0
3. They are the owner of the store

### ✅ Store Can Suggest IF:
1. Suggested product exists in their `StoreProduct` inventory
2. Suggested product has valid price set
3. They are the owner of the store

### ❌ Store CANNOT:
- Accept products not in their inventory
- Set custom prices (prices come from `StoreProduct`)
- Suggest products not in their inventory

## API Changes

### Accept Order Item
**OLD** (Removed storePrice parameter):
```json
{
  "orderItemId": 123,
  "storePrice": 25000.00,  // ❌ No longer needed
  "notes": "Available"
}
```

**NEW**:
```json
{
  "orderItemId": 123,
  "notes": "Available"
}
```
✅ Price automatically fetched from `StoreProduct`

### Suggest Alternative
**OLD** (Removed storePrice parameter):
```json
{
  "orderItemId": 123,
  "suggestedProductId": 456,
  "storePrice": 27000.00,  // ❌ No longer needed
  "suggestion": "Better alternative"
}
```

**NEW**:
```json
{
  "orderItemId": 123,
  "suggestedProductId": 456,
  "suggestion": "Better alternative"
}
```
✅ Price automatically fetched from `StoreProduct`

## Migration Instructions

### Run the Migration
```bash
# Make sure your database is running
docker compose up -d

# Run the migration
php bin/console doctrine:migrations:migrate
```

### Verify Migration
```sql
-- Check if columns were added
SELECT column_name, data_type 
FROM information_schema.columns 
WHERE table_name = 'order_item' 
AND column_name IN ('store_status', 'suggested_product_id', 'store_price', 'store_total_amount');
```

## Error Handling

### Common Errors

#### 1. Product Not in Store Inventory
```
Error: "This product is not available in your store. You can suggest an alternative product instead."
```
**Solution:** Store must add the product to their inventory first via `StoreProduct`

#### 2. No Price Set
```
Error: "Store product has no valid price set"
```
**Solution:** Set a valid price in the `StoreProduct` table for this store-product combination

#### 3. Column Does Not Exist
```
Error: "column t0.store_total_amount does not exist"
```
**Solution:** Run the migration: `php bin/console doctrine:migrations:migrate`

## Setting Up Store Inventory

### Admin Panel
1. Navigate to `/admin/store/{id}/edit`
2. Click "Add Product"
3. Select product and set store-specific price
4. Set stock quantity

### Programmatically
```php
$storeProduct = new StoreProduct();
$storeProduct->setStore($store);
$storeProduct->setProduct($product);
$storeProduct->setPrice(25000.00);  // Store's price for this product
$storeProduct->setStock(100);

$entityManager->persist($storeProduct);
$entityManager->flush();
```

## Calculation Examples

### Example 1: Single Store Order

**Order Items:**
- Product A × 2 (StoreProduct price: 10,000 Ar)

**Calculations:**
- `totalAmount` = 2 × 10,000 = 20,000 Ar (catalog price)
- `storeTotalAmount` = 2 × 10,000 = 20,000 Ar (store price)

### Example 2: Multiple Items

**Order Items:**
- Product A × 2 (StoreProduct price: 10,000 Ar) - ACCEPTED
- Product B × 1 (StoreProduct price: 15,000 Ar) - ACCEPTED
- Product C × 3 (StoreProduct price: 5,000 Ar) - PENDING

**Calculations:**
- `totalAmount` = (2 × 10,000) + (1 × 15,000) + (3 × 5,000) = 50,000 Ar
- `storeTotalAmount` = (2 × 10,000) + (1 × 15,000) = 35,000 Ar
  - Note: Product C not included (status = PENDING, not ACCEPTED)

### Example 3: Product Suggestion Flow

**Initial:**
- Product A × 2 - Store doesn't have it

**Store Suggests:**
- Product B × 2 (StoreProduct price: 12,000 Ar)
- Status: SUGGESTED
- storePrice saved: 12,000 Ar

**Admin Approves:**
- Product A → Product B (replaced)
- Status: PENDING
- storePrice cleared

**Store Accepts:**
- Product B × 2
- Price fetched from StoreProduct: 12,000 Ar
- Status: ACCEPTED
- `storeTotalAmount` = 2 × 12,000 = 24,000 Ar

## Workflow Summary

```
┌─────────────────────────────────────────────────┐
│ 1. ORDER CREATED                                │
│    - Customer orders Product A                  │
│    - OrderItem created with status: PENDING     │
└─────────────────┬───────────────────────────────┘
                  │
                  ▼
┌─────────────────────────────────────────────────┐
│ 2. STORE CHECKS INVENTORY                       │
│    - Does store have Product A in StoreProduct? │
└─────────────────┬───────────────────────────────┘
                  │
        ┌─────────┴─────────┐
        │                   │
        ▼ YES               ▼ NO
┌───────────────┐   ┌──────────────────┐
│ ACCEPT        │   │ SUGGEST          │
│ - Get price   │   │ - Find Product B │
│   from Store  │   │ - Verify B in    │
│   Product     │   │   inventory      │
│ - Status →    │   │ - Get price from │
│   ACCEPTED    │   │   StoreProduct   │
└───────────────┘   └─────────┬────────┘
                              │
                              ▼
                    ┌──────────────────┐
                    │ ADMIN REVIEWS    │
                    │ - Approves       │
                    │ - A → B replaced │
                    │ - Status → PEND  │
                    └─────────┬────────┘
                              │
                              ▼
                    ┌──────────────────┐
                    │ STORE ACCEPTS    │
                    │ - Product B      │
                    │ - Get price      │
                    │ - Status → ACC   │
                    └──────────────────┘
```

## Testing Checklist

- [ ] Migration runs successfully
- [ ] Store can accept product in their inventory
- [ ] Store cannot accept product NOT in their inventory
- [ ] Store can suggest alternative in their inventory
- [ ] Store cannot suggest product NOT in inventory
- [ ] Prices are fetched from StoreProduct, not user input
- [ ] `storeTotalAmount` calculates correctly
- [ ] Order view shows correct store prices
- [ ] Store dashboard displays correctly

## Files Modified

- ✅ `src/Entity/Order.php` - Updated calculation to use StoreProduct price
- ✅ `src/Entity/OrderItem.php` - Added `suggestedProduct` field
- ✅ `src/Dto/AcceptOrderItemInput.php` - Removed `storePrice` field
- ✅ `src/Dto/SuggestOrderItemInput.php` - Removed `storePrice` field
- ✅ `src/State/OrderItem/AcceptOrderItemProcessor.php` - Validates StoreProduct, fetches price
- ✅ `src/State/OrderItem/SuggestOrderItemProcessor.php` - Validates StoreProduct, fetches price
- ✅ `migrations/Version20251028000000.php` - Database migration

## Conclusion

This system ensures:
- ✅ **Inventory Control**: Stores only handle products they actually have
- ✅ **Price Consistency**: Prices come from configured StoreProduct entries
- ✅ **Data Integrity**: No manual price entry errors
- ✅ **Business Logic**: Enforces proper store-product relationships

