# Turbo Frame Dialog Fix - Product DataTable

## Issue Description

When clicking "Create" or "Edit" buttons in the Product DataTable, the pages were opening in a popup/dialog instead of navigating to the full page.

## Root Cause

The `ProductDataTableType` was using Turbo Frame attributes that loaded the pages inside a dialog frame:

```php
'data-turbo-frame' => 'dialog',
'target' => 'dialog',
```

These attributes tell Hotwire Turbo to load the content inside a `<turbo-frame id="dialog">` element (typically a modal/popup) instead of navigating to a full page.

## Solution

Updated the `ProductDataTableType` to use `'data-turbo-frame' => '_top'` instead, which tells Turbo to navigate to the full page while still maintaining Turbo Drive's SPA-like behavior.

### Changes Made

**File:** `src/DataTable/Type/ProductDataTableType.php`

#### 1. Create Action Button

**Before:**
```php
'attr' => [
    'variant' => 'default',
    'data-turbo-frame' => 'dialog',
    'target' => 'dialog',
],
```

**After:**
```php
'attr' => [
    'variant' => 'default',
    'data-turbo-frame' => '_top',
],
```

#### 2. Edit Row Action Button

**Before:**
```php
'attr' => [
    'size' => 'sm',
    'variant' => 'outline',
    'data-turbo-frame' => 'dialog',
    'target' => 'dialog',
    'class' => 'whitespace-nowrap'
]
```

**After:**
```php
'attr' => [
    'size' => 'sm',
    'variant' => 'outline',
    'data-turbo-frame' => '_top',
    'class' => 'whitespace-nowrap'
]
```

## Technical Details

### Turbo Frame Targets Explained

| Target Value | Behavior |
|--------------|----------|
| `'dialog'` | Loads content inside `<turbo-frame id="dialog">` (modal/popup) |
| `'_top'` | Navigates to the full page (breaks out of any frame) |
| `'_self'` | Loads within the current frame |
| (no attribute) | Default behavior, loads in containing frame if any |

### Why `_top` Instead of Removing the Attribute?

1. **Consistency:** Matches the `OrderDataTableType` pattern
2. **Explicit Control:** Makes it clear we want full page navigation
3. **Turbo Drive:** Maintains Turbo Drive's SPA benefits (fast navigation, progress bar)
4. **Frame Breaking:** Ensures content loads in the main window, not any containing frame

## Consistency with OrderDataTableType

Both DataTable types now use the same pattern:

| DataTable | Create Action | Edit Action |
|-----------|--------------|-------------|
| ProductDataTableType | `'data-turbo-frame' => '_top'` ✅ | `'data-turbo-frame' => '_top'` ✅ |
| OrderDataTableType | `'data-turbo-frame' => '_top'` ✅ | `'data-turbo-frame' => '_top'` ✅ |

## Testing

✅ **Verified:**
- Click "Create" button → Navigates to full create page (no popup)
- Click "Edit" button → Navigates to full edit page (no popup)
- No linter errors
- Consistent with Order DataTable behavior
- Turbo Drive still provides smooth navigation

## Files Modified

✅ `src/DataTable/Type/ProductDataTableType.php`
- Updated create action to use `'data-turbo-frame' => '_top'`
- Updated edit row action to use `'data-turbo-frame' => '_top'`

## Related Fixes

This fix complements the previous updates:
- ✅ ProductController now matches OrderController pattern
- ✅ Create/Edit templates render as full pages
- ✅ DataTable actions navigate to full pages
- ✅ No more popup/dialog behavior

## How It Works Now

### Full Page Navigation Flow

1. User clicks "Create" or "Edit" in DataTable
2. Turbo Drive intercepts the click
3. `data-turbo-frame="_top"` tells Turbo to navigate the entire page
4. Controller renders the full page template
5. User sees create/edit page (not a popup)
6. After submit, redirects back to product list

### Benefits

- ✅ More screen space for forms
- ✅ Better mobile experience
- ✅ Cleaner URL bar (shows actual URL)
- ✅ Easier to bookmark/share links
- ✅ Browser back button works properly
- ✅ Still gets Turbo Drive speed benefits

## When to Use Dialog vs Full Page

### Use Dialog (`data-turbo-frame="dialog"`):
- Quick edits with few fields
- Confirmations or simple forms
- Want to keep context of current page
- Small, focused interactions

### Use Full Page (`data-turbo-frame="_top"`):
- Complex forms with many fields
- Multi-step workflows
- Need full screen space
- Want proper URLs and navigation

## Notes

- The `target="dialog"` attribute has been completely removed (it was redundant)
- Both Product and Order DataTables now use identical patterns
- This maintains the benefits of Hotwire Turbo while providing better UX for complex forms
- If you need dialog behavior in the future, just change `'_top'` back to `'dialog'`

## References

- [Hotwire Turbo Frames Documentation](https://turbo.hotwired.dev/handbook/frames)
- [Turbo Frame Target Values](https://turbo.hotwired.dev/reference/frames#the-target-attribute)

