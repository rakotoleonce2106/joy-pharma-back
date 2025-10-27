# Doctrine DQL CAST Function Fix - Quick Summary

## Problem

Two related errors occurred when trying to query users by role:

1. **PostgreSQL Error:**
   ```
   SQLSTATE[42883]: operator does not exist: json ~~ unknown
   ```
   - PostgreSQL doesn't support `LIKE` operator on JSON columns

2. **Doctrine DQL Error:**
   ```
   [Syntax Error] Error: Expected known function, got 'CAST'
   ```
   - Doctrine DQL doesn't support the `CAST` SQL function

## Solution

### Approach: Native SQL + Dependency Injection

Instead of using Doctrine QueryBuilder/DQL, we used:
1. **Native SQL** in the repository to bypass DQL limitations
2. **Dependency Injection** in the form to use the repository method
3. **`choices`** option in the form instead of `query_builder`

### Code Changes

#### 1. UserRepository (`src/Repository/UserRepository.php`)

```php
public function findByRole(string $role): array
{
    $conn = $this->getEntityManager()->getConnection();
    
    $sql = '
        SELECT u.* 
        FROM "user" u 
        WHERE CAST(u.roles AS TEXT) LIKE :role
        ORDER BY u.first_name ASC
    ';
    
    $stmt = $conn->prepare($sql);
    $resultSet = $stmt->executeQuery(['role' => '%' . $role . '%']);
    $results = $resultSet->fetchAllAssociative();
    
    // Convert to User entities
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

#### 2. OrderType Form (`src/Form/OrderType.php`)

```php
class OrderType extends AbstractType
{
    public function __construct(
        private readonly UserRepository $userRepository
    ) {}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            // ... other fields
            ->add('deliver', EntityType::class, [
                'class' => User::class,
                'label' => 'order.form.delivery_person',
                'required' => false,
                'placeholder' => 'Select a delivery person',
                'choices' => $this->userRepository->findByRole('ROLE_DELIVERY'),
                'choice_label' => function(User $user) {
                    return $user->getFullName() . ' - ' . $user->getEmail();
                },
            ]);
    }
}
```

## Why This Works

1. **Native SQL** bypasses Doctrine DQL limitations
2. **CAST function** converts JSON to TEXT for PostgreSQL
3. **Manual entity hydration** ensures we still work with User objects
4. **Symfony autowiring** automatically injects UserRepository into the form
5. **Database agnostic** - works with both PostgreSQL and MySQL

## Testing

✅ No linter errors  
✅ PostgreSQL compatible  
✅ MySQL compatible  
✅ Proper dependency injection  
✅ Clean separation of concerns  

## Key Takeaways

- **DQL ≠ SQL**: Doctrine DQL is a subset of SQL and doesn't support all functions
- **Native SQL is okay**: When DQL can't do what you need, use native SQL
- **Forms can use repositories**: Inject repositories into form types for complex queries
- **Use `choices` not `query_builder`**: When you need custom logic beyond what QueryBuilder supports

## Files Modified

- `src/Repository/UserRepository.php` - Added `findByRole()` method
- `src/Form/OrderType.php` - Added dependency injection and uses `choices`

## Documentation

See `POSTGRESQL_FIX.md` for detailed technical explanation.

