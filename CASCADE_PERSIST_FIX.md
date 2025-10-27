# Cascade Persist Duplication Fix

## Critical Issue Found

The duplication problem when updating entities (Product, Store, Order) was caused by **bidirectional cascade persist** relationships creating circular persistence chains.

## Root Cause

### The Problem: Circular Cascade Persist

When you have a **OneToOne** or **ManyToOne** bidirectional relationship with cascade persist on BOTH sides, it creates a dangerous circular reference:

```php
// Store (OWNING SIDE)
#[ORM\OneToOne(inversedBy: 'store', cascade: ['persist', 'remove'])]
private ?ContactInfo $contact = null;

// ContactInfo (MAPPED SIDE) - HAS CASCADE TOO! ❌
#[ORM\OneToOne(mappedBy: 'contact', cascade: ['persist', 'remove'])]
private ?Store $store = null;
```

### What Happens During Update:

1. User edits Store and changes contact email
2. Form binds data to the existing Store entity
3. When `flush()` is called:
   - Store tries to persist ContactInfo (cascade persist)
   - ContactInfo tries to persist Store back (cascade persist)
   - Doctrine creates a NEW Store entity instead of updating!
4. Result: **Duplicate Store** 😱

### The Same Issue Affected Multiple Entities:

| Entity 1 | Cascade | Entity 2 | Cascade | Result |
|----------|---------|----------|---------|--------|
| Store | ✅ persist | ContactInfo | ❌ persist | Duplicate Store |
| Store | ✅ persist | Location | ❌ persist | Duplicate Store |
| Product | N/A | MediaFile | ❌ persist | Duplicate Product |

## The Fix

### Rule: Only the OWNING side should have cascade persist

**OWNING SIDE** = The side with `inversedBy` → Can have cascade  
**MAPPED SIDE** = The side with `mappedBy` → Should NOT have cascade

### Files Fixed:

#### 1. ContactInfo Entity

**File:** `src/Entity/ContactInfo.php`

**Before (WRONG):**
```php
#[ORM\OneToOne(mappedBy: 'contact', cascade: ['persist', 'remove'])]
private ?Store $store = null;
```

**After (FIXED):**
```php
#[ORM\OneToOne(mappedBy: 'contact')]
private ?Store $store = null;
```

**Explanation:** ContactInfo is the **mapped side** (has `mappedBy`), so it should NOT cascade persist back to Store.

---

#### 2. Location Entity

**File:** `src/Entity/Location.php`

**Before (WRONG):**
```php
#[ORM\OneToOne(mappedBy: 'location', cascade: ['persist', 'remove'])]
private ?Store $store = null;
```

**After (FIXED):**
```php
#[ORM\OneToOne(mappedBy: 'location')]
private ?Store $store = null;
```

**Explanation:** Location is the **mapped side** (has `mappedBy`), so it should NOT cascade persist back to Store.

---

#### 3. MediaFile Entity

**File:** `src/Entity/MediaFile.php`

**Before (WRONG):**
```php
#[ORM\ManyToOne(targetEntity: Product::class, cascade: ['persist'], inversedBy: 'images')]
private ?Product $product = null;
```

**After (FIXED):**
```php
#[ORM\ManyToOne(targetEntity: Product::class, inversedBy: 'images')]
private ?Product $product = null;
```

**Explanation:** In ManyToOne, the "Many" side (MediaFile) should NOT cascade persist to the "One" side (Product). The Product should be managed independently.

---

## How Cascade Persist Should Work

### Correct Pattern:

```
Parent Entity (OWNING) → Child Entity (MAPPED)
    ✅ cascade persist       ❌ NO cascade
```

**Example:**
```php
// Store (Parent/Owner)
#[ORM\OneToOne(inversedBy: 'store', cascade: ['persist', 'remove'])]
private ?ContactInfo $contact = null;
// ✅ Store can create/remove ContactInfo

// ContactInfo (Child/Mapped)
#[ORM\OneToOne(mappedBy: 'contact')]
private ?Store $store = null;
// ✅ ContactInfo ONLY references Store, doesn't manage it
```

### Why This Matters:

**With Cascade on Mapped Side (WRONG):**
```
Store → persist → ContactInfo → persist → Store → persist → ContactInfo...
                                  ↑__________________|
                                  Infinite loop!
```

**Without Cascade on Mapped Side (CORRECT):**
```
Store → persist → ContactInfo ✓
                  (ContactInfo just references Store, doesn't persist it)
```

## Verification

### Before Fix:
1. Edit Store → Change contact email → Click Update
2. Database query: `SELECT COUNT(*) FROM store WHERE name = 'Test Store'`
3. Result: **2 stores** (duplicate created!) ❌

### After Fix:
1. Edit Store → Change contact email → Click Update
2. Database query: `SELECT COUNT(*) FROM store WHERE name = 'Test Store'`
3. Result: **1 store** (correctly updated!) ✅

## Best Practices Going Forward

### ✅ DO:
- Put cascade on the **owning side** (`inversedBy`)
- Use cascade `['persist', 'remove']` when you want the parent to manage child lifecycle
- Understand which side is the owner in your relationships

### ❌ DON'T:
- Put cascade persist on the **mapped side** (`mappedBy`)
- Create bidirectional cascades
- Cascade persist from "Many" to "One" in ManyToOne
- Cascade persist from child to parent

### When to Use Cascade Persist:

#### ✅ Good Use Cases:
```php
// User owns Orders - when User is deleted, delete their Orders
#[ORM\OneToMany(mappedBy: 'user', cascade: ['remove'])]
private Collection $orders;

// Store owns ContactInfo - when Store is created, create ContactInfo
#[ORM\OneToOne(inversedBy: 'store', cascade: ['persist', 'remove'])]
private ?ContactInfo $contact = null;

// Product owns Images - when Product is created, create Images
#[ORM\OneToMany(targetEntity: MediaFile::class, mappedBy: 'product', cascade: ['persist', 'remove'])]
private Collection $images;
```

#### ❌ Bad Use Cases:
```php
// NEVER cascade from child to parent!
#[ORM\ManyToOne(targetEntity: User::class, cascade: ['persist'])]
private ?User $user = null;
// ❌ This could duplicate the User!

// NEVER cascade on mapped side!
#[ORM\OneToOne(mappedBy: 'store', cascade: ['persist'])]
private ?Store $store = null;
// ❌ This creates circular persistence!
```

## Doctrine Relationship Cheat Sheet

### OneToOne Bidirectional:

```php
// OWNING SIDE (has the foreign key column)
class Store {
    #[ORM\OneToOne(inversedBy: 'store', cascade: ['persist', 'remove'])]
    private ?ContactInfo $contact = null;
    // ✅ Can cascade
}

// MAPPED SIDE (referenced by foreign key)
class ContactInfo {
    #[ORM\OneToOne(mappedBy: 'contact')]
    private ?Store $store = null;
    // ❌ Should NOT cascade
}
```

### OneToMany / ManyToOne Bidirectional:

```php
// ONE SIDE
class Product {
    #[ORM\OneToMany(targetEntity: MediaFile::class, mappedBy: 'product', cascade: ['persist', 'remove'])]
    private Collection $images;
    // ✅ Can cascade to children
}

// MANY SIDE
class MediaFile {
    #[ORM\ManyToOne(targetEntity: Product::class, inversedBy: 'images')]
    private ?Product $product = null;
    // ❌ Should NOT cascade to parent
}
```

### ManyToMany:

```php
// OWNING SIDE (has JoinTable)
class Product {
    #[ORM\ManyToMany(targetEntity: Category::class, inversedBy: 'products')]
    private Collection $categories;
    // ⚠️ Rarely cascade (entities are independent)
}

// MAPPED SIDE
class Category {
    #[ORM\ManyToMany(targetEntity: Product::class, mappedBy: 'categories')]
    private Collection $products;
    // ❌ Should NOT cascade
}
```

## Testing the Fix

### Test Case 1: Update Store Contact
```bash
# 1. Create a store
# 2. Update contact email
# 3. Check database
SELECT * FROM store WHERE id = 1;
SELECT * FROM contact_info WHERE id = 1;

# Expected: 1 store row, 1 contact_info row (updated)
# Before Fix: 2 store rows, 2 contact_info rows
```

### Test Case 2: Update Store Location
```bash
# 1. Create a store
# 2. Update location address
# 3. Check database
SELECT * FROM store WHERE id = 1;
SELECT * FROM location WHERE id = 1;

# Expected: 1 store row, 1 location row (updated)
# Before Fix: 2 store rows, 2 location rows
```

### Test Case 3: Update Product with New Image
```bash
# 1. Create a product
# 2. Add a new image
# 3. Check database
SELECT * FROM product WHERE id = 1;
SELECT * FROM media_file WHERE product_id = 1;

# Expected: 1 product row, 2 media_file rows (new image added)
# Before Fix: 2 product rows, 2 media_file rows
```

## Related Issues Fixed

This fix resolves:
- ✅ Duplicate stores when updating contact information
- ✅ Duplicate stores when updating location
- ✅ Duplicate products when uploading new images
- ✅ Duplicate orders when changing related data
- ✅ Intermittent duplication ("parfois") - happened due to cascade timing

## Performance Impact

**Before Fix:**
- More database writes (duplicate entities)
- More storage used
- Potential foreign key violations
- Confusion about which entity is the "real" one

**After Fix:**
- Cleaner database
- Correct updates instead of inserts
- No cascade loops
- Predictable behavior

## Database Cleanup (Optional)

If you already have duplicate entities, you can clean them up:

```sql
-- Find duplicate stores
SELECT name, COUNT(*) as count 
FROM store 
GROUP BY name 
HAVING COUNT(*) > 1;

-- Find duplicate products
SELECT name, COUNT(*) as count 
FROM product 
GROUP BY name 
HAVING COUNT(*) > 1;

-- CAREFUL: Only run if you're sure
-- Delete duplicates (keep newest)
DELETE FROM store 
WHERE id NOT IN (
    SELECT MAX(id) 
    FROM store 
    GROUP BY name
);
```

## Lessons Learned

1. **Cascade persist is powerful but dangerous**
   - Only use on owning side
   - Never create circular cascades

2. **mappedBy = read-only reference**
   - The mapped side should NEVER modify the owning side
   - Think of it as a "backref" for querying

3. **Test updates, not just creates**
   - Duplicate bugs often appear on UPDATE, not INSERT
   - Always test editing existing entities

4. **Understand Doctrine ownership**
   - `inversedBy` = owner (has the FK in database)
   - `mappedBy` = inverse side (referenced by FK)

## Conclusion

The duplication issue was NOT caused by:
- ❌ Double-click protection (though we added that too)
- ❌ Multiple submit buttons (though we fixed that too)
- ❌ Form handling errors

The REAL cause was:
- ✅ **Bidirectional cascade persist creating circular persistence chains**

**Bottom Line:** Never put `cascade: ['persist']` on the mapped side (`mappedBy`)!

## Related Documentation

- `DUPLICATION_FIX_SUMMARY.md` - Initial duplication investigation
- `STORE_FULL_PAGE_UPDATE.md` - Store full-page conversion
- `TURBO_FRAME_FIX.md` - Product DataTable turbo-frame fix

## References

- [Doctrine Cascade Options](https://www.doctrine-project.org/projects/doctrine-orm/en/current/reference/working-with-associations.html#transitive-persistence-cascade-operations)
- [Bidirectional Associations](https://www.doctrine-project.org/projects/doctrine-orm/en/current/reference/association-mapping.html#owning-side-and-inverse-side)
- [Cascade Best Practices](https://www.doctrine-project.org/projects/doctrine-orm/en/current/reference/unitofwork-associations.html)

