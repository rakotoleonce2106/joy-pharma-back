# Complete Authentication, Password & User Management APIs

## 📋 Table of Contents
1. [Authentication APIs](#authentication-apis)
2. [Registration APIs](#registration-apis)
3. [Password Management APIs](#password-management-apis)
4. [User Management APIs](#user-management-apis)
5. [Social Login APIs](#social-login-apis)

---

## Base URL
All API endpoints are prefixed with `/api`

---

## Authentication APIs

### 1. Login
**Endpoint:** `POST /api/auth`  
**Authentication:** Public  
**Description:** Login with email and password to get JWT token

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
  "token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
  "refresh_token": "def50200...",
  "user": {
    "id": 1,
    "email": "customer@example.com",
    "firstName": "John",
    "lastName": "Doe",
    "phone": "+1234567890",
    "roles": ["ROLE_USER"],
    "userType": "customer",
    "isActive": true,
    "avatar": "/uploads/profile/avatar.jpg"
  }
}
```

**Response (Delivery Person):**
```json
{
  "token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
  "refresh_token": "def50200...",
  "user": {
    "id": 2,
    "email": "delivery@example.com",
    "firstName": "Mike",
    "lastName": "Driver",
    "phone": "+1234567891",
    "roles": ["ROLE_USER", "ROLE_DELIVER"],
    "userType": "delivery",
    "isActive": true,
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

**Response (Store Owner):**
```json
{
  "token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
  "refresh_token": "def50200...",
  "user": {
    "id": 3,
    "email": "store@example.com",
    "firstName": "Sarah",
    "lastName": "Shop",
    "phone": "+1234567892",
    "roles": ["ROLE_USER", "ROLE_STORE"],
    "userType": "store",
    "isActive": true,
    "store": {
      "id": 1,
      "name": "Main Pharmacy",
      "description": "Your trusted pharmacy",
      "isActive": true,
      "phone": "+1234567890",
      "email": "store@pharmacy.com",
      "address": "123 Main St",
      "city": "New York",
      "latitude": 40.7128,
      "longitude": -74.0060
    }
  }
}
```

**Error Response:**
```json
{
  "code": 401,
  "message": "Invalid credentials."
}
```

---

### 2. Refresh Token
**Endpoint:** `POST /api/token/refresh`  
**Authentication:** Public  
**Description:** Refresh JWT token using refresh token

**Request:**
```json
{
  "refresh_token": "def50200..."
}
```

**Response:**
```json
{
  "token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
  "refresh_token": "def50200..."
}
```

---

### 3. Get Current User
**Endpoint:** `GET /api/me`  
**Authentication:** Required (Bearer Token)  
**Description:** Get current authenticated user information

**Headers:**
```
Authorization: Bearer eyJ0eXAiOiJKV1Qi...
```

**Response:**
```json
{
  "id": 1,
  "email": "user@example.com",
  "firstName": "John",
  "lastName": "Doe",
  "phone": "+1234567890",
  "roles": ["ROLE_USER"],
  "userType": "customer",
  "isActive": true,
  "avatar": "/uploads/profile/avatar.jpg"
}
```

---

### 4. Logout
**Endpoint:** `POST /api/logout`  
**Authentication:** Required (Bearer Token)  
**Description:** Logout user (client should remove tokens)

**Response:**
```json
{
  "message": "Logged out successfully"
}
```

---

## Registration APIs

### 1. Register Customer
**Endpoint:** `POST /api/register`  
**Authentication:** Public  
**Description:** Register a new customer account

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

**Validation Rules:**
- `email`: Required, must be valid email, unique
- `password`: Required, minimum 8 characters
- `firstName`: Required
- `lastName`: Required
- `phone`: Required

**Response (201 Created):**
```json
{
  "token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
  "user": {
    "id": 1,
    "email": "customer@example.com",
    "firstName": "John",
    "lastName": "Doe",
    "phone": "+1234567890",
    "roles": ["ROLE_USER"],
    "userType": "customer",
    "isActive": true
  }
}
```

**Error Response (409 Conflict):**
```json
{
  "error": "Email already exists",
  "code": 409
}
```

---

### 2. Register Delivery Person
**Endpoint:** `POST /api/register/delivery`  
**Authentication:** Public  
**Description:** Register a new delivery person account

**Request:**
```json
{
  "email": "delivery@example.com",
  "password": "password123",
  "firstName": "Mike",
  "lastName": "Driver",
  "phone": "+1234567891",
  "vehicleType": "motorcycle",
  "vehiclePlate": "ABC123"
}
```

**Validation Rules:**
- `email`: Required, must be valid email, unique
- `password`: Required, minimum 8 characters
- `firstName`: Required
- `lastName`: Required
- `phone`: Required
- `vehicleType`: Required, must be one of: `bike`, `motorcycle`, `car`, `van`
- `vehiclePlate`: Optional

**Response (201 Created):**
```json
{
  "token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
  "user": {
    "id": 2,
    "email": "delivery@example.com",
    "firstName": "Mike",
    "lastName": "Driver",
    "phone": "+1234567891",
    "roles": ["ROLE_USER", "ROLE_DELIVER"],
    "userType": "delivery",
    "isActive": true,
    "delivery": {
      "vehicleType": "motorcycle",
      "vehiclePlate": "ABC123",
      "isOnline": false,
      "totalDeliveries": 0,
      "averageRating": null,
      "totalEarnings": "0.00"
    }
  }
}
```

---

### 3. Register Store Owner
**Endpoint:** `POST /api/register/store`  
**Authentication:** Public  
**Description:** Register a new store owner account

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
  "storeDescription": "Your trusted pharmacy",
  "storeCity": "New York",
  "storeLatitude": 40.7128,
  "storeLongitude": -74.0060
}
```

**Validation Rules:**
- `email`: Required, must be valid email, unique
- `password`: Required, minimum 8 characters
- `firstName`: Required
- `lastName`: Required
- `phone`: Required
- `storeName`: Required
- `storeAddress`: Required
- `storePhone`: Optional (defaults to user phone)
- `storeEmail`: Optional (defaults to user email)
- `storeDescription`: Optional
- `storeCity`: Optional
- `storeLatitude`: Optional (float)
- `storeLongitude`: Optional (float)

**Response (201 Created):**
```json
{
  "token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
  "user": {
    "id": 3,
    "email": "store@example.com",
    "firstName": "Sarah",
    "lastName": "Shop",
    "phone": "+1234567892",
    "roles": ["ROLE_USER", "ROLE_STORE"],
    "userType": "store",
    "isActive": true,
    "store": {
      "id": 1,
      "name": "Main Pharmacy",
      "description": "Your trusted pharmacy",
      "address": "123 Main St, New York, NY 10001",
      "city": "New York",
      "phone": "+1234567890",
      "email": "store@pharmacy.com"
    }
  }
}
```

---

## Password Management APIs

### 1. Forgot Password (Send Reset Code)
**Endpoint:** `POST /api/password/forgot`  
**Authentication:** Public  
**Description:** Send a 6-digit reset code to user's email

**Request:**
```json
{
  "email": "user@example.com"
}
```

**Response:**
```json
{
  "success": true,
  "message": "If an account exists with this email, you will receive a password reset code."
}
```

**Notes:**
- Same response whether email exists or not (security best practice)
- Reset code expires after 15 minutes
- Only one active reset code per email
- Code is 6 digits (e.g., 123456)

---

### 2. Verify Reset Code
**Endpoint:** `POST /api/password/verify-code`  
**Authentication:** Public  
**Description:** Verify that the reset code is valid

**Request:**
```json
{
  "email": "user@example.com",
  "code": "123456"
}
```

**Response (Success):**
```json
{
  "valid": true,
  "message": "Code is valid"
}
```

**Response (Error - 400):**
```json
{
  "error": "Invalid or expired code",
  "code": 400
}
```

---

### 3. Reset Password
**Endpoint:** `POST /api/password/reset`  
**Authentication:** Public  
**Description:** Reset password using verified code

**Request:**
```json
{
  "email": "user@example.com",
  "code": "123456",
  "password": "newPassword123"
}
```

**Validation Rules:**
- `email`: Required, must be valid email
- `code`: Required, 4-6 characters
- `password`: Required, minimum 8 characters

**Response (Success):**
```json
{
  "success": true,
  "message": "Password reset successfully"
}
```

**Response (Error - 400):**
```json
{
  "error": "Invalid or expired reset code",
  "code": 400
}
```

---

### 4. Update Password (Authenticated)
**Endpoint:** `POST /api/user/update-password`  
**Authentication:** Required (Bearer Token)  
**Description:** Update password for authenticated user

**Request:**
```json
{
  "currentPassword": "oldPassword123",
  "newPassword": "newPassword123",
  "confirmPassword": "newPassword123"
}
```

**Validation Rules:**
- `currentPassword`: Required
- `newPassword`: Required, minimum 8 characters
- `confirmPassword`: Required, must match newPassword

**Response (Success):**
```json
{
  "success": true,
  "message": "Password updated successfully"
}
```

**Response (Error - 400):**
```json
{
  "error": "Current password is incorrect",
  "code": 400
}
```

**Notes:**
- Sends confirmation email after successful password change
- Requires valid current password

---

## User Management APIs

### 1. Update User Profile
**Endpoint:** `POST /api/user/update`  
**Authentication:** Required (Bearer Token)  
**Content-Type:** `multipart/form-data`  
**Description:** Update user profile information

**Form Data:**
```
firstName: John
lastName: Doe
phone: +1234567890
imageFile: [file upload]
```

**Fields:**
- `firstName`: Optional
- `lastName`: Optional
- `phone`: Optional
- `imageFile`: Optional (image file for avatar)

**Response:**
```json
{
  "id": 1,
  "email": "user@example.com",
  "firstName": "John",
  "lastName": "Doe",
  "phone": "+1234567890",
  "roles": ["ROLE_USER"],
  "avatar": "/uploads/profile/new-avatar.jpg"
}
```

---

## Social Login APIs

### 1. Facebook Login
**Endpoint:** `POST /api/facebook_login`  
**Authentication:** Public  
**Description:** Login with Facebook access token

**Request:**
```json
{
  "accessToken": "facebook_access_token_here"
}
```

**Response:**
```json
{
  "token": "eyJ0eXAiOiJKV1Qi...",
  "refresh_token": "def50200...",
  "user": {
    "id": 1,
    "email": "user@facebook.com",
    "firstName": "John",
    "lastName": "Doe",
    "roles": ["ROLE_USER"],
    "userType": "customer"
  }
}
```

**Notes:**
- If user doesn't exist, creates new account
- Links Facebook ID to user account
- Returns JWT token like regular login

---

### 2. Google Login
**Endpoint:** `POST /api/google_login`  
**Authentication:** Public  
**Description:** Login with Google access token

**Request:**
```json
{
  "accessToken": "google_access_token_here"
}
```

**Response:**
```json
{
  "token": "eyJ0eXAiOiJKV1Qi...",
  "refresh_token": "def50200...",
  "user": {
    "id": 1,
    "email": "user@gmail.com",
    "firstName": "John",
    "lastName": "Doe",
    "roles": ["ROLE_USER"],
    "userType": "customer"
  }
}
```

**Notes:**
- If user doesn't exist, creates new account
- Links Google ID to user account
- Returns JWT token like regular login

---

## Error Responses

### 400 Bad Request
```json
{
  "error": "Validation failed",
  "code": 400,
  "violations": [
    {
      "field": "email",
      "message": "This value should be a valid email."
    }
  ]
}
```

### 401 Unauthorized
```json
{
  "code": 401,
  "message": "Invalid credentials."
}
```

### 403 Forbidden
```json
{
  "error": "Access denied",
  "code": 403,
  "message": "You don't have permission to access this resource"
}
```

### 404 Not Found
```json
{
  "error": "User not found",
  "code": 404
}
```

### 409 Conflict
```json
{
  "error": "Email already exists",
  "code": 409
}
```

---

## Authentication Flow

### Registration Flow
```
1. User submits registration form
   ↓
2. System validates data
   ↓
3. System creates user account
   ↓
4. System generates JWT token
   ↓
5. User receives token + user data
   ↓
6. User is automatically logged in
```

### Login Flow
```
1. User submits credentials
   ↓
2. System validates credentials
   ↓
3. System generates JWT token
   ↓
4. User receives token + role-specific data
   ↓
5. User stores token for future requests
```

### Password Reset Flow
```
1. User requests reset (forgot password)
   ↓
2. System sends 6-digit code to email
   ↓
3. User enters code (verify)
   ↓
4. System validates code
   ↓
5. User enters new password
   ↓
6. System updates password
   ↓
7. User can login with new password
```

---

## Token Usage

### Making Authenticated Requests

```bash
curl -X GET https://api.example.com/api/orders/current \
  -H "Authorization: Bearer eyJ0eXAiOiJKV1Qi..."
```

### Token Expiration

- **Access Token (JWT)**: Expires after 1 hour
- **Refresh Token**: Expires after 7 days
- Use refresh token to get new access token

### Refreshing Token

```javascript
async function refreshToken() {
  const refreshToken = await storage.get('refresh_token');
  
  const response = await fetch('/api/token/refresh', {
    method: 'POST',
    body: JSON.stringify({ refresh_token: refreshToken })
  });
  
  const data = await response.json();
  
  await storage.set('jwt_token', data.token);
  await storage.set('refresh_token', data.refresh_token);
}
```

---

## Complete API Summary

### Authentication (4 endpoints)
- `POST /api/auth` - Login
- `POST /api/token/refresh` - Refresh token
- `GET /api/me` - Get current user
- `POST /api/logout` - Logout

### Registration (3 endpoints)
- `POST /api/register` - Register customer
- `POST /api/register/delivery` - Register delivery person
- `POST /api/register/store` - Register store owner

### Password Management (4 endpoints)
- `POST /api/password/forgot` - Send reset code
- `POST /api/password/verify-code` - Verify reset code
- `POST /api/password/reset` - Reset password
- `POST /api/user/update-password` - Update password (authenticated)

### User Management (1 endpoint)
- `POST /api/user/update` - Update profile

### Social Login (2 endpoints)
- `POST /api/facebook_login` - Facebook login
- `POST /api/google_login` - Google login

**Total: 14 Authentication/User APIs**

---

## Testing with cURL

### Register Customer
```bash
curl -X POST http://localhost/api/register \
  -H "Content-Type: application/json" \
  -d '{
    "email": "customer@test.com",
    "password": "password123",
    "firstName": "John",
    "lastName": "Doe",
    "phone": "+1234567890"
  }'
```

### Register Delivery Person
```bash
curl -X POST http://localhost/api/register/delivery \
  -H "Content-Type: application/json" \
  -d '{
    "email": "delivery@test.com",
    "password": "password123",
    "firstName": "Mike",
    "lastName": "Driver",
    "phone": "+1234567891",
    "vehicleType": "motorcycle",
    "vehiclePlate": "ABC123"
  }'
```

### Login
```bash
curl -X POST http://localhost/api/auth \
  -H "Content-Type: application/json" \
  -d '{
    "email": "delivery@test.com",
    "password": "password123"
  }'
```

### Get Current User
```bash
curl -X GET http://localhost/api/me \
  -H "Authorization: Bearer YOUR_JWT_TOKEN"
```

### Forgot Password
```bash
curl -X POST http://localhost/api/password/forgot \
  -H "Content-Type: application/json" \
  -d '{
    "email": "user@test.com"
  }'
```

### Reset Password
```bash
curl -X POST http://localhost/api/password/reset \
  -H "Content-Type: application/json" \
  -d '{
    "email": "user@test.com",
    "code": "123456",
    "password": "newPassword123"
  }'
```

---

## Notes

### Key Changes from Controllers to State Processors
1. ✅ All registration endpoints now return JWT tokens (like login)
2. ✅ Consistent response format across all endpoints
3. ✅ Better error handling with HTTP exceptions
4. ✅ Cleaner code with dedicated processors
5. ✅ Automatic validation with DTO classes
6. ✅ OpenAPI documentation auto-generated

### Security Best Practices
1. ✅ Passwords are hashed with bcrypt
2. ✅ JWT tokens expire after 1 hour
3. ✅ Refresh tokens for long-term auth
4. ✅ Same response for existing/non-existing emails (password reset)
5. ✅ Rate limiting should be configured
6. ✅ HTTPS required in production

---

## Related Documentation
- [DELIVERY_API_DOCUMENTATION.md](DELIVERY_API_DOCUMENTATION.md) - All delivery APIs
- [AUTHENTICATION_STRATEGY.md](AUTHENTICATION_STRATEGY.md) - Authentication strategy
- [AUTHENTICATION_API_GUIDE.md](AUTHENTICATION_API_GUIDE.md) - Mobile app integration guide

