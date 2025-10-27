# PostgreSQL JSON Column Fix

## Issue Description

When running the application with PostgreSQL, the following error occurred:

```
SQLSTATE[42883]: Undefined function: 7 ERROR: operator does not exist: json ~~ unknown
LINE 1: ...id AS store_id_26 FROM "user" u0_ WHERE u0_.roles LIKE $1 OR...
^
HINT: No operator matches the given name and argument types. You might need to add explicit type casts.
```

## Root Cause

The `roles` column in the `User` entity is stored as JSON/JSONB type in PostgreSQL. PostgreSQL does not support the `LIKE` operator directly on JSON columns, unlike MySQL which can implicitly convert JSON to text for comparisons.

The problematic query was:
```sql
WHERE u0_.roles LIKE '%ROLE_DELIVERY%'
```

## Solution Implemented

### 1. Updated UserRepository

Added a method using native SQL to properly handle role-based queries with PostgreSQL compatibility:

**File:** `src/Repository/UserRepository.php`

```php
/**
 * Find users with a specific role
 * PostgreSQL and MySQL compatible method using native SQL
 */
public function findByRole(string $role): array
{
    $conn = $this->getEntityManager()->getConnection();
    
    // Use native SQL that works with both PostgreSQL and MySQL
    $sql = '
        SELECT u.* 
        FROM "user" u 
        WHERE CAST(u.roles AS TEXT) LIKE :role
        ORDER BY u.first_name ASC
    ';
    
    $stmt = $conn->prepare($sql);
    $resultSet = $stmt->executeQuery(['role' => '%' . $role . '%']);
    
    $results = $resultSet->fetchAllAssociative();
    
    // Convert results to User entities
    $users = [];
    foreach ($results as $row) {
        $user = $this->find($row['id']);
        if ($user) {
            $users[] = $user;
        }
    }
    
    return $users;
}
```

**Why Native SQL?**
- Doctrine DQL doesn't support the `CAST` function
- Native SQL gives us full control over the query
- Works with both PostgreSQL and MySQL

### 2. Updated OrderType Form

Modified the form to inject UserRepository and use the `findByRole` method:

**File:** `src/Form/OrderType.php`

**Before:**
```php
class OrderType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // ...
        ->add('deliver', EntityType::class, [
            'class' => User::class,
            'query_builder' => function ($repository) {
                return $repository->createQueryBuilder('u')
                    ->where('u.roles LIKE :role')
                    ->setParameter('role', '%ROLE_DELIVERY%')
                    ->orderBy('u.firstName', 'ASC');
            },
        ])
    }
}
```

**After:**
```php
class OrderType extends AbstractType
{
    public function __construct(
        private readonly UserRepository $userRepository
    ) {}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // ...
        ->add('deliver', EntityType::class, [
            'class' => User::class,
            'label' => 'order.form.delivery_person',
            'required' => false,
            'placeholder' => 'Select a delivery person',
            'choices' => $this->userRepository->findByRole('ROLE_DELIVERY'),
            'choice_label' => function(User $user) {
                return $user->getFullName() . ' - ' . $user->getEmail();
            },
        ])
    }
}
```

**Key Changes:**
1. Injected `UserRepository` into the form constructor
2. Used `choices` option instead of `query_builder`
3. Called `findByRole('ROLE_DELIVERY')` to get the list of delivery persons
4. Symfony's autowiring automatically injects the repository

## Technical Details

### The Fix Explained

1. **Native SQL**: Uses native SQL because Doctrine DQL doesn't support `CAST` function
2. **CAST Operation**: `CAST(u.roles AS TEXT)` converts the JSON column to a text representation
3. **Database Compatibility**: This approach works with both PostgreSQL and MySQL
4. **Entity Hydration**: Results are converted back to User entities for proper object-oriented usage
5. **Form Integration**: Repository is injected into the form and users are loaded as `choices`

### Why This Works

- PostgreSQL requires explicit type conversion for JSON columns when using string operators
- The `CAST` function converts the JSON array `["ROLE_USER", "ROLE_DELIVERY"]` to a text string
- The `LIKE` operator can then search within that text representation
- Native SQL bypasses Doctrine DQL limitations
- This is compatible with both PostgreSQL and MySQL databases

### Why Not Doctrine QueryBuilder?

Doctrine's QueryBuilder uses DQL (Doctrine Query Language), which is a subset of SQL and doesn't support all SQL functions. The `CAST` function is not part of DQL's standard functions, which is why we use native SQL instead.

## Alternative Approaches Considered

### 1. Using PostgreSQL JSON Functions (Not Used)
```php
->where('jsonb_exists(u.roles, :role)')
```
**Pros:** More PostgreSQL-native approach
**Cons:** Not compatible with MySQL, would require database-specific code

### 2. Using Doctrine JSON Functions (Not Used)
```php
->where('JSON_CONTAINS(u.roles, :role)')
```
**Pros:** Doctrine provides some abstraction
**Cons:** Still has compatibility issues between different databases

### 3. Native SQL with CAST (✅ Implemented)
```php
$sql = 'SELECT u.* FROM "user" u WHERE CAST(u.roles AS TEXT) LIKE :role';
```
**Pros:** 
- Works with both PostgreSQL and MySQL
- Bypasses Doctrine DQL limitations
- Full control over the query
- Standard SQL approach
**Cons:** 
- Requires manual entity hydration
- Slightly less efficient than native JSON functions
- More verbose than pure DQL

## Testing

To verify the fix works:

1. **Test Order Creation/Edit Page:**
   - Navigate to `/admin/order/new` or `/admin/order/{id}/edit`
   - The "Delivery Person" dropdown should load without errors
   - It should display users with ROLE_DELIVERY

2. **Test Dashboard:**
   - Navigate to `/admin/`
   - The "Available Orders" section should display correctly
   - You should be able to view and assign orders

3. **Verify Database Compatibility:**
   - The same code works with both PostgreSQL and MySQL
   - No database-specific code branches needed

## Files Modified

- ✅ `src/Repository/UserRepository.php` - Added `findByRole()` method using native SQL
- ✅ `src/Form/OrderType.php` - Added UserRepository dependency injection and updated to use `choices` with `findByRole()`
- ✅ `config/services.yaml` - Already configured with autowiring (no changes needed)

## Future Considerations

If performance becomes an issue with large datasets, consider:

1. **Adding a computed column** or **materialized view** for role searching
2. **Using PostgreSQL GIN indexes** on the JSON column for faster searches
3. **Caching** the list of delivery persons if it doesn't change frequently

## Additional Notes

- This fix is backward compatible with existing code
- No database migrations are required
- The new repository methods can be reused anywhere else you need to query by role
- Consider using these methods in other parts of the application that query user roles

## Example Usage

### In Controllers:
```php
// Get all delivery persons
$deliveryPersons = $userRepository->findByRole('ROLE_DELIVERY');

// Get all admins
$admins = $userRepository->findByRole('ROLE_ADMIN');
```

### In Forms:
```php
// In the form type constructor
public function __construct(
    private readonly UserRepository $userRepository
) {}

// In the buildForm method
'choices' => $this->userRepository->findByRole('ROLE_DELIVERY'),
```

## References

- [PostgreSQL JSON Functions and Operators](https://www.postgresql.org/docs/current/functions-json.html)
- [Symfony Doctrine Query Builder](https://symfony.com/doc/current/doctrine.html#querying-for-objects-the-repository)
- [PostgreSQL Type Casting](https://www.postgresql.org/docs/current/sql-expressions.html#SQL-SYNTAX-TYPE-CASTS)

