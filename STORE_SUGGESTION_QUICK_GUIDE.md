# Store Product Suggestion - Quick Reference

## 🔄 Updated Workflow

The store suggestion system now works with **actual products** instead of text suggestions:

### 1️⃣ Store Suggests Alternative Product

**API:** `POST /api/store/order-item/suggest`

```json
{
  "orderItemId": 123,
  "suggestedProductId": 456,  // ← Product ID from catalog
  "storePrice": 27000.00,
  "suggestion": "Optional explanation text",
  "notes": "Optional store notes"
}
```

**Status:** `PENDING` → `SUGGESTED`

### 2️⃣ Admin Approves Suggestion

**API:** `POST /api/admin/order-item/approve-suggestion`

```json
{
  "orderItemId": 123,
  "adminNotes": "Customer agreed to alternative"
}
```

**What Happens:**
- ✅ Original product **replaced** with suggested product
- ✅ Status returns to `PENDING`
- ✅ Store price cleared (store must re-accept with final price)

### 3️⃣ Store Accepts New Product

**API:** `POST /api/store/order-item/accept`

```json
{
  "orderItemId": 123,
  "storePrice": 27000.00,
  "notes": "Ready for delivery"
}
```

**Status:** `PENDING` → `ACCEPTED`

## 🎯 Key Points

| Aspect | Details |
|--------|---------|
| **Suggestion Type** | Product from catalog (not text) |
| **After Approval** | Product replaced, status → PENDING |
| **Store Action** | Must accept new product with price |
| **Security** | Store can only suggest for their items |
| **Admin Role** | Required for approval |

## 📊 Status Flow

```
PENDING → SUGGESTED → [Approved] → PENDING → ACCEPTED
         (Store)      (Admin)      (Store)
```

## 💡 Example

**Original Order:** Product A (ID: 100) x 2 = 10,000 Ar

**Store Suggests:** Product B (ID: 200) @ 28,000 Ar
- Status: `SUGGESTED`
- Suggested Product: Product B

**Admin Approves:**
- Product A → Product B (replaced!)
- Status: `PENDING`
- Suggested Product: cleared

**Store Accepts:**
- Product: Product B
- Store Price: 28,000 Ar
- Status: `ACCEPTED`

## 🗄️ Database Changes

**New Field:** `suggested_product_id` (foreign key to Product)

**Migration:** `Version20251028000000.php`

## ✅ Complete!

The workflow ensures:
- ✅ Proper product substitution
- ✅ Admin oversight
- ✅ Final price confirmation by store
- ✅ Full audit trail

