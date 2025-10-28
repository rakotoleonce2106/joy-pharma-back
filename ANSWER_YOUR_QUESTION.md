# Answer: Authentication for Different Apps

## Your Question
> "What is best for security app? Add role with login OR create different API login for different user roles? Because the app is different."

---

## ✅ ANSWER: Use ONE Login with Roles (RECOMMENDED)

You should use **SAME login endpoint** (`/api/auth`) for all apps, but the response will be different based on user role.

### Why This Approach?

1. **✅ Simpler** - One authentication system
2. **✅ Standard Practice** - How Uber, Deliveroo, DoorDash work
3. **✅ Easy to Maintain** - One codebase
4. **✅ Flexible** - Users can have multiple roles
5. **✅ Secure** - Same level of security

---

## 🎯 Implementation

### 1. ALL APPS USE SAME LOGIN

**Endpoint:** `POST /api/auth`

```bash
# Customer login
POST /api/auth
{
  "email": "customer@example.com",
  "password": "password123"
}

# Delivery person login (SAME ENDPOINT)
POST /api/auth
{
  "email": "delivery@example.com",
  "password": "password123"
}

# Store owner login (SAME ENDPOINT)
POST /api/auth
{
  "email": "store@example.com",
  "password": "password123"
}
```

### 2. DIFFERENT RESPONSE BASED ON ROLE

**Customer Response:**
```json
{
  "token": "eyJ0eXAi...",
  "user": {
    "roles": ["ROLE_USER"],
    "userType": "customer"
  }
}
```

**Delivery Response:**
```json
{
  "token": "eyJ0eXAi...",
  "user": {
    "roles": ["ROLE_USER", "ROLE_DELIVER"],
    "userType": "delivery",
    "delivery": {
      "vehicleType": "motorcycle",
      "totalDeliveries": 150
    }
  }
}
```

### 3. MOBILE APP VALIDATES USER TYPE

```javascript
// In your mobile app
if (response.user.userType !== 'delivery') {
  alert('This app is for delivery persons only');
  logout();
}
```

---

## 📱 Different Apps, Same Login

| App | Login Endpoint | Registration Endpoint | Role Check |
|-----|---------------|---------------------|-----------|
| **Customer App** | `/api/auth` | `/api/register` | Check if NOT delivery/store |
| **Delivery App** | `/api/auth` | `/api/register/delivery` | Check if `ROLE_DELIVER` |
| **Store App** | `/api/auth` | `/api/register/store` | Check if `ROLE_STORE` |

---

## 🔐 What I've Implemented for You

### ✅ 1. JWT Success Handler
**File:** `src/Security/JwtAuthenticationSuccessHandler.php`

Automatically adds role-specific data to login response:
- Customer gets basic info
- Delivery person gets vehicle info, stats
- Store owner gets store details

### ✅ 2. Separate Registration Endpoints

**Delivery Registration:**
```bash
POST /api/register/delivery
{
  "email": "deliver@test.com",
  "password": "password123",
  "firstName": "Mike",
  "lastName": "Driver",
  "phone": "+1234567890",
  "vehicleType": "motorcycle",
  "vehiclePlate": "ABC123"
}
```

**Store Registration:**
```bash
POST /api/register/store
{
  "email": "store@test.com",
  "password": "password123",
  "firstName": "Sarah",
  "lastName": "Shop",
  "phone": "+1234567890",
  "storeName": "Main Pharmacy",
  "storeAddress": "123 Main St"
}
```

---

## 🚫 NOT Recommended: Separate Login Endpoints

```bash
# DON'T DO THIS
POST /api/auth/customer
POST /api/auth/delivery
POST /api/auth/store
```

### Why NOT?
- ❌ More code to maintain
- ❌ Confusing for users
- ❌ What if user has multiple roles?
- ❌ Password reset complexity
- ❌ 3x testing needed

---

## 📊 Comparison

| Approach | Single Login | Multiple Logins |
|----------|-------------|-----------------|
| **Endpoints** | 1 | 3+ |
| **Maintenance** | Easy | Complex |
| **Security** | Same | Same |
| **Testing** | Simple | Complex |
| **User Experience** | Good | Confusing |
| **Industry Standard** | ✅ Yes | ❌ No |

---

## 🎯 How Your Mobile Apps Should Work

### Customer App
```javascript
async function login(email, password) {
  const response = await fetch('/api/auth', {
    method: 'POST',
    body: JSON.stringify({ email, password })
  });
  
  const data = await response.json();
  
  // Validate user type
  if (data.user.userType === 'delivery' || data.user.userType === 'store') {
    alert('Please use the appropriate app for your account');
    return;
  }
  
  // Store token and proceed
  await AsyncStorage.setItem('token', data.token);
  navigate('Home');
}
```

### Delivery App
```javascript
async function login(email, password) {
  const response = await fetch('/api/auth', {
    method: 'POST',
    body: JSON.stringify({ email, password })
  });
  
  const data = await response.json();
  
  // Validate user type
  if (data.user.userType !== 'delivery') {
    alert('This app is for delivery persons only');
    return;
  }
  
  // Store token and proceed
  await AsyncStorage.setItem('token', data.token);
  navigate('DeliveryDashboard');
}
```

---

## 🔒 API Security (Already Configured)

```yaml
# Only delivery persons can access
/api/orders/available:
  security: 'is_granted("ROLE_DELIVER")'

# Only store owners can access
/api/store/products:
  security: 'is_granted("ROLE_STORE")'

# Any authenticated user
/api/profile:
  security: 'is_granted("ROLE_USER")'
```

---

## ✨ Summary

### What You Have Now:

✅ **One login endpoint** - `/api/auth` for all apps  
✅ **Role-based response** - Different data for each role  
✅ **Separate registrations** - `/api/register/delivery` and `/api/register/store`  
✅ **Automatic role assignment** - ROLE_USER + ROLE_DELIVER or ROLE_STORE  
✅ **Security configured** - JWT authentication with role-based access  
✅ **Complete documentation** - Ready for mobile development  

### What You Need to Do:

1. **Test the endpoints** with Postman
2. **Integrate in mobile apps** using examples
3. **Validate user role** in each app on login
4. **Show error** if wrong user type

---

## 📚 Read These Docs

1. **AUTHENTICATION_API_GUIDE.md** - Complete API guide
2. **AUTHENTICATION_IMPLEMENTATION_SUMMARY.md** - Implementation details  
3. **DELIVERY_API_DOCUMENTATION.md** - All delivery APIs

---

## 🎉 Conclusion

**ANSWER: Use ONE login with roles.** ✅

- Same login endpoint for all apps
- Response includes role-specific data
- Mobile app validates user type
- Separate registration for different roles
- Secure, simple, industry standard

This is how Uber, Deliveroo, and all major apps work! 🚀

