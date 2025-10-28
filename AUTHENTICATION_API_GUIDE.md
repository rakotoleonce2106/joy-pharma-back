# Authentication API Guide for Multiple Apps

## 🎯 Overview

This guide explains how authentication works when you have **different mobile apps** for different user types:
- **Customer App** - For customers ordering products  
- **Delivery App** - For delivery persons  
- **Store Owner App** - For store management  

---

## 🔐 Authentication Strategy

### ✅ Single Login Endpoint + Role-Based Response

All apps use **the same login endpoint** but receive different data based on their role:

```
POST /api/auth
```

**Why this approach?**
- ✅ Single authentication system
- ✅ Easy to maintain
- ✅ Users can have multiple roles
- ✅ Standard industry practice
- ✅ Same user database

---

## 📱 Complete Authentication Flow

### 1. Login (All Apps)

**Endpoint:** `POST /api/auth`  
**Authentication:** Public

**Request:**
```json
{
  "email": "user@example.com",
  "password": "password123"
}
```

**Response for CUSTOMER:**
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
    "userType": "customer",
    "status": "active",
    "avatar": "/uploads/profile/avatar.jpg"
  }
}
```

**Response for DELIVERY PERSON:**
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
    "status": "active",
    "avatar": "/uploads/profile/mike.jpg",
    "delivery": {
      "vehicleType": "motorcycle",
      "vehiclePlate": "ABC123",
      "isOnline": false,
      "totalDeliveries": 150,
      "averageRating": 4.8,
      "totalEarnings": "2250.00",
      "currentLatitude": null,
      "currentLongitude": null,
      "lastLocationUpdate": null
    }
  }
}
```

**Response for STORE OWNER:**
```json
{
  "token": "eyJ0eXAiOiJKV1Qi...",
  "refresh_token": "def50200...",
  "user": {
    "id": 3,
    "email": "store@example.com",
    "firstName": "Sarah",
    "lastName": "Shop",
    "phone": "+1234567892",
    "roles": ["ROLE_USER", "ROLE_STORE"],
    "userType": "store",
    "status": "active",
    "avatar": "/uploads/profile/sarah.jpg",
    "store": {
      "id": 1,
      "name": "Main Pharmacy",
      "slug": "main-pharmacy",
      "description": "Your trusted pharmacy",
      "address": "123 Main St, New York, NY 10001",
      "phone": "+1234567890",
      "email": "store@example.com",
      "status": "active"
    }
  }
}
```

---

### 2. Registration (Different for Each App)

#### A. Customer Registration
**Endpoint:** `POST /api/register` or `POST /api/register/customer`  
**Authentication:** Public

**Request:**
```json
{
  "email": "customer@example.com",
  "password": "password123",
  "firstName": "John",
  "lastName": "Doe",
  "phone": "+1234567890"
}
```

**Response:**
```json
{
  "id": 1,
  "email": "customer@example.com",
  "firstName": "John",
  "lastName": "Doe",
  "phone": "+1234567890",
  "roles": ["ROLE_USER"],
  "status": "active"
}
```

---

#### B. Delivery Person Registration
**Endpoint:** `POST /api/register/delivery`  
**Authentication:** Public

**Request:**
```json
{
  "email": "deliver@example.com",
  "password": "password123",
  "firstName": "Mike",
  "lastName": "Driver",
  "phone": "+1234567891",
  "vehicleType": "motorcycle",
  "vehiclePlate": "ABC123",
  "requiresApproval": false
}
```

**Valid Vehicle Types:**
- `bike`
- `motorcycle`
- `car`
- `van`

**Response:**
```json
{
  "message": "Delivery person registered successfully",
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
    "status": "active"
  }
}
```

---

#### C. Store Owner Registration
**Endpoint:** `POST /api/register/store`  
**Authentication:** Public

**Request:**
```json
{
  "email": "store@example.com",
  "password": "password123",
  "firstName": "Sarah",
  "lastName": "Shop",
  "phone": "+1234567892",
  "storeName": "Main Pharmacy",
  "storeAddress": "123 Main St, New York, NY 10001",
  "storePhone": "+1234567890",
  "storeEmail": "store@pharmacy.com",
  "storeDescription": "Your trusted pharmacy"
}
```

**Response:**
```json
{
  "message": "Store owner registered successfully. Your account is pending approval.",
  "user": {
    "id": 3,
    "email": "store@example.com",
    "firstName": "Sarah",
    "lastName": "Shop",
    "phone": "+1234567892",
    "roles": ["ROLE_USER", "ROLE_STORE"],
    "userType": "store",
    "status": "pending",
    "store": {
      "id": 1,
      "name": "Main Pharmacy",
      "slug": "main-pharmacy",
      "address": "123 Main St, New York, NY 10001",
      "status": "pending"
    }
  }
}
```

**Note:** Store registrations require admin approval before they can login.

---

### 3. Social Login

#### Facebook Login
**Endpoint:** `POST /api/facebook_login`  
**Authentication:** Public

**Request:**
```json
{
  "accessToken": "facebook_access_token_here"
}
```

**Response:** Same as regular login

---

#### Google Login
**Endpoint:** `POST /api/google_login`  
**Authentication:** Public

**Request:**
```json
{
  "accessToken": "google_access_token_here"
}
```

**Response:** Same as regular login

---

### 4. Token Refresh
**Endpoint:** `POST /api/token/refresh`  
**Authentication:** Public

**Request:**
```json
{
  "refresh_token": "def50200..."
}
```

**Response:**
```json
{
  "token": "eyJ0eXAiOiJKV1Qi...",
  "refresh_token": "def50200..."
}
```

---

### 5. Get Current User
**Endpoint:** `GET /api/me`  
**Authentication:** Required (Bearer Token)

**Headers:**
```
Authorization: Bearer eyJ0eXAiOiJKV1Qi...
```

**Response:** Same structure as login response (based on user role)

---

### 6. Logout
**Endpoint:** `POST /api/logout`  
**Authentication:** Required (Bearer Token)

**Response:**
```json
{
  "message": "Logged out successfully"
}
```

**Note:** Client should also remove token from local storage.

---

## 📲 Mobile App Implementation

### React Native / Flutter Example

```javascript
// 1. Login Function
async function login(email, password) {
  try {
    const response = await fetch('https://api.example.com/api/auth', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ email, password })
    });
    
    if (!response.ok) {
      throw new Error('Login failed');
    }
    
    const data = await response.json();
    
    // Store tokens
    await AsyncStorage.setItem('jwt_token', data.token);
    await AsyncStorage.setItem('refresh_token', data.refresh_token);
    await AsyncStorage.setItem('user', JSON.stringify(data.user));
    
    // Validate user role for current app
    validateUserRole(data.user);
    
    return data;
  } catch (error) {
    console.error('Login error:', error);
    throw error;
  }
}

// 2. Validate User Role (App-Specific)
function validateUserRole(user) {
  const APP_TYPE = 'delivery'; // or 'customer' or 'store'
  
  if (APP_TYPE === 'delivery') {
    if (!user.roles.includes('ROLE_DELIVER')) {
      alert('This app is for delivery persons only. Please download the Customer App.');
      logout();
      return false;
    }
  }
  
  if (APP_TYPE === 'customer') {
    if (user.roles.includes('ROLE_DELIVER') || user.roles.includes('ROLE_STORE')) {
      alert('Please use the appropriate app for your account type.');
      logout();
      return false;
    }
  }
  
  if (APP_TYPE === 'store') {
    if (!user.roles.includes('ROLE_STORE')) {
      alert('This app is for store owners only.');
      logout();
      return false;
    }
  }
  
  return true;
}

// 3. Register Delivery Person
async function registerDelivery(formData) {
  const response = await fetch('https://api.example.com/api/register/delivery', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      email: formData.email,
      password: formData.password,
      firstName: formData.firstName,
      lastName: formData.lastName,
      phone: formData.phone,
      vehicleType: formData.vehicleType,
      vehiclePlate: formData.vehiclePlate
    })
  });
  
  return await response.json();
}

// 4. API Call with Token
async function makeAuthenticatedRequest(endpoint, method = 'GET', body = null) {
  const token = await AsyncStorage.getItem('jwt_token');
  
  const options = {
    method,
    headers: {
      'Content-Type': 'application/json',
      'Authorization': `Bearer ${token}`
    }
  };
  
  if (body) {
    options.body = JSON.stringify(body);
  }
  
  const response = await fetch(`https://api.example.com${endpoint}`, options);
  
  // Handle token expiration
  if (response.status === 401) {
    await refreshToken();
    // Retry the request
    return makeAuthenticatedRequest(endpoint, method, body);
  }
  
  return await response.json();
}

// 5. Refresh Token
async function refreshToken() {
  const refreshToken = await AsyncStorage.getItem('refresh_token');
  
  const response = await fetch('https://api.example.com/api/token/refresh', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ refresh_token: refreshToken })
  });
  
  const data = await response.json();
  
  await AsyncStorage.setItem('jwt_token', data.token);
  await AsyncStorage.setItem('refresh_token', data.refresh_token);
}

// 6. Logout
async function logout() {
  const token = await AsyncStorage.getItem('jwt_token');
  
  // Call logout endpoint
  await fetch('https://api.example.com/api/logout', {
    method: 'POST',
    headers: { 'Authorization': `Bearer ${token}` }
  });
  
  // Clear local storage
  await AsyncStorage.removeItem('jwt_token');
  await AsyncStorage.removeItem('refresh_token');
  await AsyncStorage.removeItem('user');
  
  // Navigate to login screen
  navigation.navigate('Login');
}
```

---

## 🔒 Role-Based Access Control

### User Roles

| Role | Description | Access |
|------|-------------|--------|
| `ROLE_USER` | Base role for all users | Basic access |
| `ROLE_DELIVER` | Delivery person | Delivery endpoints |
| `ROLE_STORE` | Store owner | Store management |
| `ROLE_ADMIN` | Administrator | All access |

### API Endpoint Access

#### Customer Only (ROLE_USER)
- Create orders
- View own orders
- Add to cart
- Add favorites
- View products

#### Delivery Only (ROLE_DELIVER)
- `/api/orders/available` - Get available orders
- `/api/orders/current` - Get current delivery
- `/api/orders/{id}/accept` - Accept order
- `/api/orders/{id}/status` - Update order status
- `/api/orders/{id}/validate-qr` - Validate delivery
- `/api/availability` - Toggle online/offline
- `/api/location` - Update location
- `/api/stats/dashboard` - Delivery stats
- `/api/stats/earnings` - Earnings stats

#### Store Only (ROLE_STORE)
- `/api/store/products` - Manage products
- `/api/store/orders` - View store orders
- `/api/store/inventory` - Manage inventory
- `/api/store/settings` - Store settings

---

## 🛡️ Error Handling

### Wrong User Type in Wrong App

```json
{
  "error": "Access denied",
  "code": 403,
  "message": "This app is for delivery persons only"
}
```

### Invalid Credentials

```json
{
  "error": "Invalid credentials",
  "code": 401,
  "message": "Email or password is incorrect"
}
```

### Email Already Exists

```json
{
  "error": "Email already exists",
  "code": 409
}
```

### Account Pending Approval

```json
{
  "error": "Account pending approval",
  "code": 403,
  "message": "Your account is pending admin approval"
}
```

---

## 📝 Testing

### Test Accounts

**Customer:**
```json
{
  "email": "customer@test.com",
  "password": "password123",
  "expected_role": "ROLE_USER"
}
```

**Delivery Person:**
```json
{
  "email": "delivery@test.com",
  "password": "password123",
  "expected_roles": ["ROLE_USER", "ROLE_DELIVER"]
}
```

**Store Owner:**
```json
{
  "email": "store@test.com",
  "password": "password123",
  "expected_roles": ["ROLE_USER", "ROLE_STORE"]
}
```

---

## 🚀 Quick Start Checklist

### For Mobile App Developers

- [ ] Implement login with `/api/auth`
- [ ] Store JWT token securely
- [ ] Validate user role on app startup
- [ ] Implement token refresh logic
- [ ] Add role-specific registration endpoint
- [ ] Handle authentication errors
- [ ] Implement logout functionality
- [ ] Test with different user roles

---

## 🔗 Related Documentation

- [Full Delivery API Documentation](DELIVERY_API_DOCUMENTATION.md)
- [Authentication Strategy](AUTHENTICATION_STRATEGY.md)
- [Password Management Guide](PASSWORD_MANAGEMENT_GUIDE.md)

---

## 💡 Best Practices

1. **Store tokens securely** - Use secure storage (Keychain on iOS, Keystore on Android)
2. **Validate on startup** - Check user role when app starts
3. **Handle token expiration** - Implement automatic token refresh
4. **Logout on role mismatch** - If wrong user type, logout immediately
5. **Clear error messages** - Tell users which app to use
6. **Test thoroughly** - Test all role combinations
7. **Use refresh tokens** - Don't let users re-login frequently
8. **Handle offline mode** - Store user data locally

---

## ❓ FAQ

**Q: Can a user be both customer and delivery person?**  
A: Yes! Users can have multiple roles. The app should check for the specific role needed.

**Q: Should I create separate apps or one app with role switching?**  
A: Separate apps are recommended for better UX and smaller app size.

**Q: What if a delivery person tries to use the customer app?**  
A: Validate on login and show message: "Please use the Delivery App for your account."

**Q: How do I handle admin users?**  
A: Admins use the web panel, not mobile apps.

**Q: What about account approval for stores?**  
A: Store registrations set status to "pending". Admin must approve before they can login.

