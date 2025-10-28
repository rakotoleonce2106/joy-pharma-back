# Authentication Strategy for Multiple Apps

## Overview
You have **multiple mobile apps** for different user types:
- **Customer App** - For customers ordering products
- **Delivery App** - For delivery persons
- **Store Owner App** - For store management
- **Admin Panel** - Web-based admin interface

## ✅ Recommended Approach: Single Login with Role-Based Response

### Why This Approach?
1. **Simpler to maintain** - One authentication system
2. **Single source of truth** - One user database
3. **Flexible** - Users can have multiple roles if needed
4. **Standard practice** - Most apps work this way (Uber, Deliveroo, etc.)
5. **Easy to test** - One authentication flow

---

## Current Roles in System

```php
ROLE_USER      // Base role - all authenticated users (customers)
ROLE_DELIVER   // Delivery persons
ROLE_STORE     // Store owners
ROLE_ADMIN     // System administrators
```

---

## API Endpoint Structure

### 1. Login (Same for all apps)
**Endpoint:** `POST /api/auth`

**Request:**
```json
{
  "email": "user@example.com",
  "password": "password123"
}
```

**Response (Customer):**
```json
{
  "token": "eyJ0eXAiOiJKV1Qi...",
  "refresh_token": "def50200...",
  "user": {
    "id": 1,
    "email": "customer@example.com",
    "firstName": "John",
    "lastName": "Doe",
    "phone": "+1234567890",
    "roles": ["ROLE_USER"],
    "userType": "customer"
  }
}
```

**Response (Delivery Person):**
```json
{
  "token": "eyJ0eXAiOiJKV1Qi...",
  "refresh_token": "def50200...",
  "user": {
    "id": 2,
    "email": "deliver@example.com",
    "firstName": "Mike",
    "lastName": "Driver",
    "phone": "+1234567891",
    "roles": ["ROLE_USER", "ROLE_DELIVER"],
    "userType": "delivery",
    "vehicleType": "motorcycle",
    "vehiclePlate": "ABC123",
    "isOnline": false,
    "totalDeliveries": 150,
    "averageRating": 4.8,
    "totalEarnings": "2250.00"
  }
}
```

**Response (Store Owner):**
```json
{
  "token": "eyJ0yXAiOiJKV1Qi...",
  "refresh_token": "def50200...",
  "user": {
    "id": 3,
    "email": "store@example.com",
    "firstName": "Sarah",
    "lastName": "Shop",
    "phone": "+1234567892",
    "roles": ["ROLE_USER", "ROLE_STORE"],
    "userType": "store",
    "store": {
      "id": 1,
      "name": "Main Pharmacy",
      "address": "123 Main St"
    }
  }
}
```

---

## Client-Side Implementation

### How Apps Should Handle Login

```javascript
// Example: React Native / Flutter / Swift

async function login(email, password) {
  const response = await fetch('https://api.example.com/api/auth', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ email, password })
  });
  
  const data = await response.json();
  
  // Store token
  await AsyncStorage.setItem('jwt_token', data.token);
  await AsyncStorage.setItem('refresh_token', data.refresh_token);
  
  // Check user type and navigate accordingly
  const userType = data.user.userType; // or check roles array
  
  if (userType === 'customer') {
    // Navigate to customer home screen
    navigation.navigate('CustomerHome');
  } else if (userType === 'delivery') {
    // Navigate to delivery dashboard
    navigation.navigate('DeliveryDashboard');
  } else if (userType === 'store') {
    // Navigate to store management
    navigation.navigate('StoreManagement');
  }
}
```

### App-Specific Role Validation

**Customer App** - Check on app startup:
```javascript
if (!user.roles.includes('ROLE_USER') || user.roles.includes('ROLE_DELIVER')) {
  alert('Please use the Customer App');
  logout();
}
```

**Delivery App** - Check on app startup:
```javascript
if (!user.roles.includes('ROLE_DELIVER')) {
  alert('This app is for delivery persons only. Please use the Customer App.');
  logout();
}
```

**Store Owner App** - Check on app startup:
```javascript
if (!user.roles.includes('ROLE_STORE')) {
  alert('This app is for store owners only.');
  logout();
}
```

---

## Alternative Approach: Separate Login Endpoints (NOT Recommended)

If you really want separate endpoints:

### Customer Login
```
POST /api/auth/customer
```

### Delivery Login
```
POST /api/auth/delivery
```

### Store Login
```
POST /api/auth/store
```

### ❌ Why This is NOT Recommended:
1. **More code to maintain** - 3x authentication logic
2. **Confusing for users** - What if someone is both customer and delivery person?
3. **Registration complexity** - Need separate registration endpoints too
4. **Testing overhead** - 3x test cases
5. **Database queries** - Still querying same user table
6. **Password reset** - Which endpoint to use?

---

## Registration Strategy

### Separate Registration by User Type ✅

Unlike login, **registration SHOULD be different** for each user type:

#### 1. Customer Registration
```
POST /api/register/customer
```
- Simpler form
- Only basic info needed
- Auto-assign `ROLE_USER`

#### 2. Delivery Person Registration
```
POST /api/register/delivery
```
- Additional fields: vehicle type, license plate, ID verification
- Auto-assign `ROLE_USER` + `ROLE_DELIVER`
- May require approval

#### 3. Store Owner Registration
```
POST /api/register/store
```
- Business details
- Tax info
- Store information
- Auto-assign `ROLE_USER` + `ROLE_STORE`
- Requires admin approval

---

## Security Implementation

### Update JWT Success Handler

Create a custom handler to return role-specific data:

**File: `src/Security/JwtAuthenticationSuccessHandler.php`**

```php
<?php

namespace App\Security;

use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationSuccessEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Events;
use App\Entity\User;

class JwtAuthenticationSuccessHandler implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            Events::AUTHENTICATION_SUCCESS => 'onAuthenticationSuccess',
        ];
    }

    public function onAuthenticationSuccess(AuthenticationSuccessEvent $event): void
    {
        $data = $event->getData();
        $user = $event->getUser();

        if (!$user instanceof User) {
            return;
        }

        // Add user data to response
        $userData = [
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'firstName' => $user->getFirstName(),
            'lastName' => $user->getLastName(),
            'phone' => $user->getPhone(),
            'roles' => $user->getRoles(),
            'userType' => $this->getUserType($user),
        ];

        // Add role-specific data
        if (in_array('ROLE_DELIVER', $user->getRoles())) {
            $userData['vehicleType'] = $user->getVehicleType();
            $userData['vehiclePlate'] = $user->getVehiclePlate();
            $userData['isOnline'] = $user->isOnline();
            $userData['totalDeliveries'] = $user->getTotalDeliveries();
            $userData['averageRating'] = $user->getAverageRating();
            $userData['totalEarnings'] = $user->getTotalEarnings();
        }

        if (in_array('ROLE_STORE', $user->getRoles())) {
            $store = $user->getStore();
            if ($store) {
                $userData['store'] = [
                    'id' => $store->getId(),
                    'name' => $store->getName(),
                    'address' => $store->getAddress(),
                ];
            }
        }

        $data['user'] = $userData;
        $event->setData($data);
    }

    private function getUserType(User $user): string
    {
        $roles = $user->getRoles();
        
        if (in_array('ROLE_ADMIN', $roles)) {
            return 'admin';
        }
        if (in_array('ROLE_STORE', $roles)) {
            return 'store';
        }
        if (in_array('ROLE_DELIVER', $roles)) {
            return 'delivery';
        }
        
        return 'customer';
    }
}
```

### Register the Service

**File: `config/services.yaml`**

```yaml
services:
    # ... existing services

    App\Security\JwtAuthenticationSuccessHandler:
        tags:
            - { name: kernel.event_subscriber }
```

---

## API Access Control

### Delivery-Only Endpoints

```yaml
# src/ApiResource/DeliveryOrder.yaml
get_available_orders:
    security: 'is_granted("ROLE_DELIVER")'
```

### Store-Only Endpoints

```yaml
# src/ApiResource/StoreProduct.yaml
add_product:
    security: 'is_granted("ROLE_STORE")'
```

### Customer Endpoints

```yaml
# src/ApiResource/Order.yaml
create_order:
    security: 'is_granted("ROLE_USER")'
```

### Multi-Role Endpoints

```yaml
# Any authenticated user
get_profile:
    security: 'is_granted("ROLE_USER")'

# Multiple specific roles
view_order:
    security: 'is_granted("ROLE_USER") or is_granted("ROLE_DELIVER") or is_granted("ROLE_STORE")'
```

---

## Testing Strategy

### Test Cases

1. **Customer Login**
   - Should get `ROLE_USER`
   - Should NOT have delivery fields
   - Should be able to create orders

2. **Delivery Login**
   - Should get `ROLE_USER` + `ROLE_DELIVER`
   - Should have vehicle info
   - Should access delivery endpoints
   - Should NOT access store endpoints

3. **Store Login**
   - Should get `ROLE_USER` + `ROLE_STORE`
   - Should have store info
   - Should access store management
   - Should NOT access delivery endpoints

4. **Wrong App**
   - Customer tries to access delivery endpoints → 403 Forbidden
   - Delivery tries to access store endpoints → 403 Forbidden

---

## Summary

### ✅ DO THIS:
1. **Single login endpoint** `/api/auth` for all apps
2. **Return role-specific data** in login response
3. **Client validates user type** on app startup
4. **Separate registration endpoints** for different user types
5. **Use roles** for API access control

### ❌ DON'T DO THIS:
1. Don't create separate login endpoints
2. Don't duplicate authentication logic
3. Don't allow wrong user types in wrong apps

---

## Quick Reference

| App Type | Roles | Login Endpoint | Registration Endpoint |
|----------|-------|----------------|----------------------|
| Customer | `ROLE_USER` | `/api/auth` | `/api/register` or `/api/register/customer` |
| Delivery | `ROLE_USER`, `ROLE_DELIVER` | `/api/auth` | `/api/register/delivery` |
| Store | `ROLE_USER`, `ROLE_STORE` | `/api/auth` | `/api/register/store` |
| Admin | `ROLE_USER`, `ROLE_ADMIN` | `/login` (web) | Manual creation |

---

## Next Steps

1. Create the JWT success handler
2. Update registration endpoints to assign correct roles
3. Test login from different apps
4. Update mobile app login logic
5. Add role validation on app startup

