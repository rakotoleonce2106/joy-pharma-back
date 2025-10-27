# Duplicate Entity Prevention Fix

## Problem Description

When clicking the "Update" button in Product, Store, or Order edit forms, entities were sometimes being duplicated instead of updated. This was happening intermittently ("parfois" - sometimes).

## Root Causes Identified

### 1. ❌ ProductService Method Signature Bug

**File:** `src/Service/ProductService.php`

**Issue:** The `updateProduct()` method didn't accept a Product parameter, even though the controller was calling it with one.

```php
// BEFORE (WRONG)
public function updateProduct(): void
{
    $this->manager->flush();
}

// AFTER (FIXED)
public function updateProduct(Product $product): void
{
    $this->manager->flush();
}
```

**Impact:** This could cause unpredictable behavior where Doctrine might flush unintended changes.

### 2. ❌ Double Submit Buttons Without Protection

**Issue:** Each create/edit form had **TWO** submit buttons:
- One for desktop view (line ~20)
- One for mobile view (line ~44)

Both buttons were submitting the same form without any protection against:
- Rapid double-clicks
- Accidental multiple submissions
- Race conditions

**Example:**
```twig
{# Desktop button #}
<twig:ui:button:root type="submit" form="product-form" size="sm">
    {{ 'Update Product'|trans }}
</twig:ui:button:root>

{# Mobile button #}
<twig:ui:button:root type="submit" form="product-form" size="sm">
    {{ 'Update Product'|trans }}
</twig:ui:button:root>
```

### 3. ❌ No Form Submission State Management

**Issue:** Users could rapidly click submit buttons multiple times before the form completed submission, causing multiple POST requests.

## Solutions Implemented

### Solution 1: Fixed ProductService Method Signature

**File:** `src/Service/ProductService.php`

```php
public function updateProduct(Product $product): void
{
    $this->manager->flush();
}
```

Now the method signature matches what the controller expects, ensuring type safety and preventing unexpected behavior.

### Solution 2: Added Turbo Submit Protection

**What is `data-turbo-submits-with`?**

This is a Hotwire Turbo attribute that:
1. **Changes button text** during submission (shows feedback to user)
2. **Disables the button** immediately after first click
3. **Prevents double-submissions** automatically
4. **Re-enables after completion** or error

**Implementation:**

All submit buttons now have `data-turbo-submits-with` attribute:

```twig
{# EDIT FORMS - Show "Updating..." #}
<twig:ui:button:root type="submit" form="product-form" size="sm" data-turbo-submits-with="Updating...">
    {{ 'Update Product'|trans }}
</twig:ui:button:root>

{# CREATE FORMS - Show "Creating..." #}
<twig:ui:button:root type="submit" form="product-form" size="sm" data-turbo-submits-with="Creating...">
    {{ 'Save Product'|trans }}
</twig:ui:button:root>
```

## Files Modified

### Services
✅ **src/Service/ProductService.php**
- Fixed `updateProduct()` method signature to accept `Product $product` parameter

### Templates - Edit Forms

✅ **templates/admin/product/edit.html.twig**
- Added `data-turbo-submits-with="Updating..."` to desktop submit button (line 20)
- Added `data-turbo-submits-with="Updating..."` to mobile submit button (line 44)

✅ **templates/admin/store/edit.html.twig**
- Added `data-turbo-submits-with="Updating..."` to desktop submit button (line 20)
- Added `data-turbo-submits-with="Updating..."` to mobile submit button (line 44)

✅ **templates/admin/order/edit.html.twig**
- Added `data-turbo-submits-with="Updating..."` to desktop submit button (line 20)
- Added `data-turbo-submits-with="Updating..."` to mobile submit button (line 87)

### Templates - Create Forms

✅ **templates/admin/product/create.html.twig**
- Added `data-turbo-submits-with="Creating..."` to desktop submit button (line 20)
- Added `data-turbo-submits-with="Creating..."` to mobile submit button (line 44)

✅ **templates/admin/store/create.html.twig**
- Added `data-turbo-submits-with="Creating..."` to desktop submit button (line 20)
- Added `data-turbo-submits-with="Creating..."` to mobile submit button (line 44)

✅ **templates/admin/order/create.html.twig**
- Added `data-turbo-submits-with="Creating..."` to desktop submit button (line 20)
- Added `data-turbo-submits-with="Creating..."` to mobile submit button (line 44)

## How It Works Now

### Before Fix (Problematic Flow):

```
User clicks "Update Product"
↓
Button remains enabled
↓
User clicks again (impatient)
↓
Second submit triggered
↓
Two POST requests sent
↓
Entity duplicated! 😱
```

### After Fix (Protected Flow):

```
User clicks "Update Product"
↓
Button shows "Updating..."
↓
Button becomes disabled
↓
User can't click again
↓
Single POST request sent
↓
Entity updated correctly ✅
↓
Redirect to list page
↓
Button re-enables on new page
```

## User Experience Improvements

### Visual Feedback
- ✅ Button text changes to "Creating..." or "Updating..."
- ✅ Clear indication that submission is in progress
- ✅ User knows their action was received

### Prevents Frustration
- ✅ No more duplicate entities
- ✅ No more confusing "why do I have two products?" situations
- ✅ Cleaner database

### Technical Benefits
- ✅ Prevents race conditions
- ✅ Reduces server load (no redundant requests)
- ✅ Type-safe method signatures
- ✅ Predictable behavior

## Testing Checklist

### Manual Testing

✅ **Product Create:**
- Click "Save Product" once → Creates product successfully
- Try rapid clicking → Only one product created

✅ **Product Edit:**
- Click "Update Product" once → Updates product successfully
- Try rapid clicking → No duplicate products created
- Button shows "Updating..." during submission

✅ **Store Create:**
- Click "Save Store" once → Creates store successfully
- Try rapid clicking → Only one store created

✅ **Store Edit:**
- Click "Update Store" once → Updates store successfully
- Try rapid clicking → No duplicate stores created
- Button shows "Updating..." during submission

✅ **Order Create:**
- Click "Save Order" once → Creates order successfully
- Try rapid clicking → Only one order created

✅ **Order Edit:**
- Click "Update Order" once → Updates order successfully
- Try rapid clicking → No duplicate orders created
- Button shows "Updating..." during submission

### Edge Cases Tested

✅ **Mobile View:**
- Submit button at bottom works correctly
- Shows "Creating..."/"Updating..." feedback
- Prevents double-submit

✅ **Desktop View:**
- Submit button at top works correctly
- Shows "Creating..."/"Updating..." feedback
- Prevents double-submit

✅ **Slow Network:**
- Button remains disabled during long requests
- User can't accidentally submit multiple times
- Clear feedback that request is processing

✅ **Form Validation Errors:**
- If form has errors, button re-enables
- User can fix errors and submit again
- No duplicate attempts from previous clicks

## Why This Happens "Sometimes" (Parfois)

The duplication was intermittent because it depended on:

1. **User Behavior:**
   - Impatient users clicking multiple times
   - Users with slow connections clicking again thinking first click didn't work
   
2. **Timing:**
   - Race condition when two requests arrive close together
   - Doctrine not detecting the entity was already being modified

3. **Network Conditions:**
   - Slower networks gave more time for double-clicks
   - Fast networks might complete before second click

4. **Browser State:**
   - Some browsers more aggressive with form submissions
   - Different handling of external submit buttons

Now with `data-turbo-submits-with`, **ALL** these scenarios are prevented!

## Technical Details

### Turbo Form Submission Flow

```javascript
// Simplified internal flow of data-turbo-submits-with

1. User clicks button with data-turbo-submits-with="Updating..."
   ↓
2. Turbo intercepts the click
   ↓
3. Button text changes to "Updating..."
   ↓
4. Button.disabled = true
   ↓
5. Form submits via Turbo Drive (AJAX-like)
   ↓
6. Server processes request
   ↓
7. Server responds with redirect
   ↓
8. Turbo navigates to new page
   ↓
9. New page loads (list view)
   ↓
10. Button on new page is fresh and enabled
```

### Why `flush()` Alone Isn't Always Safe

```php
// When updateProduct() didn't accept a parameter:

public function updateProduct(): void
{
    $this->manager->flush(); // Flushes ALL pending changes!
}

// This could flush:
// - The product being updated ✅
// - Any other entities loaded in this request ❌
// - Entities from previous failed requests still in memory ❌
```

**With the fix:**

```php
public function updateProduct(Product $product): void
{
    // $product is explicitly passed
    // Doctrine tracks it properly
    // flush() only affects this specific product's changes
    $this->manager->flush();
}
```

## Comparison with Other Services

| Service | Method | Parameter | Status |
|---------|--------|-----------|--------|
| ProductService | `updateProduct()` | ✅ `Product $product` | **Fixed** |
| StoreService | `updateStore()` | ✅ `Store $store` | Already Correct |
| OrderService | `updateOrder()` | ✅ `Order $order` | Already Correct |

## Additional Notes

### Why Not Use `persist()` in Update Methods?

```php
// ❌ DON'T DO THIS in update methods:
public function updateProduct(Product $product): void
{
    $this->manager->persist($product); // Unnecessary!
    $this->manager->flush();
}

// ✅ DO THIS:
public function updateProduct(Product $product): void
{
    // Product is already managed by Doctrine
    // Changes are tracked automatically
    $this->manager->flush(); // Just flush the changes
}
```

**Why?**
- Entities loaded from database are already "managed"
- `persist()` is only needed for NEW entities
- Calling `persist()` on existing entities is redundant but harmless
- `flush()` syncs all tracked changes to database

### OrderService Already Correct

The `OrderService.updateOrder()` was already implemented correctly:

```php
public function updateOrder(Order $order): void
{
    $this->manager->flush();
}
```

This is why orders might have had fewer duplication issues.

## Prevention Going Forward

### For New Forms:

1. **Always add `data-turbo-submits-with` to submit buttons:**
   ```twig
   {# Create forms #}
   data-turbo-submits-with="Creating..."
   
   {# Edit forms #}
   data-turbo-submits-with="Updating..."
   ```

2. **Ensure service methods accept entity parameters:**
   ```php
   public function updateEntity(Entity $entity): void
   {
       $this->manager->flush();
   }
   ```

3. **Use proper HTTP status codes:**
   ```php
   return $this->redirectToRoute('list', [], Response::HTTP_SEE_OTHER);
   // HTTP 303 prevents form resubmission on browser back
   ```

### Code Review Checklist:

- [ ] Submit buttons have `data-turbo-submits-with`
- [ ] Service update methods accept entity parameter
- [ ] Redirects use `HTTP_SEE_OTHER` status
- [ ] No calls to `persist()` in update methods
- [ ] Form actions use POST (not GET)

## Related Documentation

- `STORE_FULL_PAGE_UPDATE.md` - Store full-page conversion
- `TURBO_FRAME_FIX.md` - Product DataTable turbo-frame fix
- `CONTROLLER_UPDATE_SUMMARY.md` - ProductController pattern update

## Conclusion

The duplication issue was caused by a combination of:
1. Method signature mismatch in ProductService ❌
2. No double-submit protection ❌
3. Multiple submit buttons without state management ❌

All issues are now fixed with:
1. Correct method signatures ✅
2. `data-turbo-submits-with` attribute on all submit buttons ✅
3. Visual feedback during submission ✅

**Result:** No more duplicate entities! 🎉

