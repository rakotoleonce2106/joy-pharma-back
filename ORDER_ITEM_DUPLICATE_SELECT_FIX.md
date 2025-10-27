# Fix: Duplicate Store Select in Order Items

## 🐛 Problem

When adding new items to an order, the **Store** select field appeared **twice**:
1. Once in the field label/header
2. Once as the actual select dropdown

This created a confusing UX where users saw two identical dropdowns for selecting a store.

**Visual Issue:**
```
Quantity          Product                    Select a store (optional)
[3]              [3C PHARMA® DysmeCalm®]    [Pharmacie métropole ▼]  ← DUPLICATE!
                                             [Pharmacie métropole ▼]  ← DUPLICATE!
```

---

## 🔍 Root Cause

### Problem 1: Prototype Structure

**Location:** `templates/components/admin/order-form.html.twig` (line 144)

The prototype was using `form_widget()` which generates **only the form controls** (inputs/selects) without their labels or proper `form_row` structure:

```twig
❌ BEFORE:
data-prototype="{{ form_widget(form.items.vars.prototype)|e('html_attr') }}"
```

**Result:** This generated HTML like:
```html
<input type="number" id="order_items___name___quantity" ...>
<select id="order_items___name___product" ...></select>
<select id="order_items___name___store" ...></select>
```

No labels, no wrapper divs, just raw form controls!

### Problem 2: JavaScript Parsing

**Location:** `assets/controllers/form_collection_controller.js`

The JavaScript tried to parse these raw controls and wrap them in a grid structure. But without proper `form_row` structure, it created improper DOM nesting, causing selects to appear multiple times.

```javascript
❌ BEFORE:
// Tried to parse raw widgets and wrap them
const formRows = Array.from(tempContainer.children);
formRows.forEach((row, index) => {
    const column = this.createColumn(index);
    column.appendChild(row);
    grid.appendChild(column);
});
```

---

## ✅ Solution

### 1. Created Dedicated Prototype Template

**New File:** `templates/components/admin/order-item-prototype.html.twig`

This template uses `form_row()` to generate **complete form fields** with proper structure:

```twig
<div>
    {{ form_row(item.quantity, {
        'label': 'Quantity',
        'attr': {
            'min': 1,
            'placeholder': '1',
            'data-action': 'change->order-total#itemChanged input->order-total#itemChanged'
        }
    }) }}
</div>
<div class="md:col-span-2">
    {{ form_row(item.product, {
        'label': 'Product',
        'attr': {
            'class': 'select2-enable',
            'data-action': 'change->order-total#itemChanged'
        }
    }) }}
</div>
<div>
    {{ form_row(item.store, {
        'label': 'Store',
        'attr': {
            'class': 'select2-enable',
            'data-action': 'change->order-total#itemChanged'
        }
    }) }}
</div>
```

**Benefits:**
- ✅ Each field has proper label
- ✅ Each field wrapped in a div
- ✅ Proper grid column classes (`md:col-span-2` for product)
- ✅ All data attributes for Stimulus controllers
- ✅ Same structure as existing items

### 2. Updated Prototype Reference

**File:** `templates/components/admin/order-form.html.twig`

Changed from `form_widget()` to `include()` the new template:

```twig
✅ AFTER:
data-prototype="{{ include('components/admin/order-item-prototype.html.twig', {item: form.items.vars.prototype})|e('html_attr') }}"
```

**Result:** Now generates proper HTML:
```html
<div>
    <label for="order_items___name___quantity">Quantity</label>
    <input type="number" id="order_items___name___quantity" ...>
</div>
<div class="md:col-span-2">
    <label for="order_items___name___product">Product</label>
    <select id="order_items___name___product" ...></select>
</div>
<div>
    <label for="order_items___name___store">Store</label>
    <select id="order_items___name___store" ...></select>
</div>
```

### 3. Simplified JavaScript

**File:** `assets/controllers/form_collection_controller.js`

Simplified the `addItem()` method to directly insert the prototype HTML:

```javascript
✅ AFTER:
const grid = this.createGrid();
grid.innerHTML = newItemHtml; // Insert the prototype HTML directly
```

**Removed unnecessary code:**
- ❌ Removed complex child parsing
- ❌ Removed `createColumn()` method
- ❌ Removed manual DOM construction loop

**Why it works now:**
- The prototype template already has the correct structure
- We just insert it directly into the grid
- No need to parse and reconstruct the DOM

### 4. Added Product Price Attributes

**File:** `src/Form/OrderItemType.php`

Added `choice_attr` to embed price data for frontend calculation:

```php
'choice_attr' => function(Product $product) {
    $price = $product->getTotalPrice() ?? $product->getUnitPrice() ?? 0;
    return ['data-price' => $price];
},
```

**Result:**
```html
<option value="42" data-price="15000">3C PHARMA® DysmeCalm®</option>
```

This allows the `order-total` controller to calculate totals in real-time.

---

## 📊 Before vs After

| Aspect | Before | After |
|--------|--------|-------|
| **Prototype Method** | `form_widget()` | `include()` with template |
| **Structure** | Raw widgets only | Complete `form_row()` |
| **Labels** | ❌ Missing | ✅ Proper labels |
| **DOM Nesting** | ❌ Broken | ✅ Correct |
| **Store Selects** | 2 (duplicate) | 1 (correct) |
| **JavaScript Complexity** | High (parsing) | Low (direct insert) |
| **Price Calculation** | ❌ Missing | ✅ Working |

---

## 🎯 How It Works Now

### Adding a New Item:

```
1. User clicks "Add Item" button
2. JavaScript gets prototype HTML from data-prototype attribute
3. Replaces __name__ with current index
4. Creates wrapper div with border/padding
5. Creates grid div (4 columns)
6. Inserts prototype HTML directly into grid
7. Adds remove button
8. Appends to container
9. Triggers animation
10. Attaches event listeners for total calculation
```

### Prototype HTML Structure:

```html
<div class="grid gap-4 md:grid-cols-4">
    <div>                              ← Quantity (1 column)
        <label>Quantity</label>
        <input type="number" ...>
    </div>
    <div class="md:col-span-2">        ← Product (2 columns)
        <label>Product</label>
        <select data-price="...">...</select>
    </div>
    <div>                              ← Store (1 column)
        <label>Store</label>
        <select>...</select>
    </div>
</div>
```

---

## 🧪 Testing

### Test 1: Add New Item ✅

```bash
1. Go to /admin/order/new
2. Click "Add Item" button
3. ✅ New item appears with proper structure
4. ✅ Each field has ONE label
5. ✅ Each field has ONE input/select
6. ✅ Store field shows "Store" label once
7. ✅ Store dropdown appears once
8. ✅ Grid layout: [Qty][Product-------][Store]
```

### Test 2: Multiple Items ✅

```bash
1. Add 3 items
2. ✅ All items have same structure
3. ✅ No duplicate selects
4. ✅ Labels properly aligned
5. ✅ Remove buttons work
```

### Test 3: Total Calculation ✅

```bash
1. Add item
2. Select product with price
3. Enter quantity
4. ✅ Total updates in real-time
5. ✅ data-price attribute present on options
6. ✅ Calculation correct
```

### Test 4: Store Selection ✅

```bash
1. Add item
2. Open Store dropdown
3. ✅ See list of stores ONCE
4. ✅ Can select a store
5. ✅ Selected store displayed correctly
6. ✅ Optional field (can leave empty)
```

---

## 📁 Files Modified

```
✅ templates/components/admin/order-form.html.twig
   - Changed prototype from form_widget to include

✅ templates/components/admin/order-item-prototype.html.twig (NEW)
   - Created dedicated prototype template
   - Uses form_row for proper structure
   - Matches existing item layout

✅ assets/controllers/form_collection_controller.js
   - Simplified addItem() method
   - Direct HTML insertion instead of parsing
   - Removed createColumn() method
   
✅ src/Form/OrderItemType.php
   - Added choice_attr with data-price
   - Added explicit label for product field
```

---

## 💡 Key Lessons

### 1. Use `form_row()` for Prototypes

**Don't:**
```twig
❌ {{ form_widget(form.items.vars.prototype) }}
```
This generates only the input/select without label or structure.

**Do:**
```twig
✅ {{ include('prototype-template.html.twig', {item: form.items.vars.prototype}) }}
```
Then use `form_row()` in the template for complete structure.

### 2. Keep Prototype Consistent

The prototype should have **exactly the same structure** as the items rendered in the initial loop. This ensures consistency and prevents layout issues.

### 3. Simplify JavaScript

When your template provides proper structure, your JavaScript doesn't need to be complex. Just:
```javascript
grid.innerHTML = prototype; // That's it!
```

### 4. Data Attributes for JS Interaction

Use `choice_attr` to add data attributes that JavaScript needs:
```php
'choice_attr' => function($entity) {
    return ['data-something' => $entity->getSomething()];
}
```

---

## 🔧 Related Improvements

### Future Enhancements:

1. **Select2 Integration**
   ```javascript
   // After adding item, initialize Select2
   $(grid).find('.select2-enable').select2();
   ```

2. **Product Filtering by Store**
   ```javascript
   // When store changes, filter available products
   storeSelect.addEventListener('change', filterProducts);
   ```

3. **Store Price Override**
   ```php
   // Use StoreProduct price if available
   if ($storeProduct = $product->getStoreProduct($store)) {
       return ['data-price' => $storeProduct->getPrice()];
   }
   ```

4. **Validation Feedback**
   ```javascript
   // Show error if quantity > stock
   if (quantity > availableStock) {
       showError('Insufficient stock');
   }
   ```

---

## 📝 Summary

### Problem:
- ❌ Store select appeared twice (duplicate)
- ❌ Broken prototype structure with `form_widget()`
- ❌ Complex JavaScript trying to fix bad HTML
- ❌ Missing product price data

### Solution:
- ✅ Created dedicated prototype template with `form_row()`
- ✅ Proper HTML structure matching existing items
- ✅ Simplified JavaScript (direct HTML insertion)
- ✅ Added `choice_attr` for product prices
- ✅ Single store select with proper label

### Result:
- 🎯 Clean, consistent UI
- 🎯 No duplicate fields
- 🎯 Real-time total calculation
- 🎯 Maintainable code
- 🎯 Better UX

---

**Date:** 2025-10-27  
**Status:** ✅ Fixed and Tested  
**Files Modified:** 4 (1 new, 3 updated)

🎉 **Order items now render perfectly with proper form structure!**

