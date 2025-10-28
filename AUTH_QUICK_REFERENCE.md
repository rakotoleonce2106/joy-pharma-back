# Auth API Quick Reference

## 🚀 Quick Start

All APIs are available at `/api` base URL.

---

## 📱 Registration (Returns JWT Token)

### Customer
```bash
POST /api/register
{
  "email": "user@example.com",
  "password": "password123",
  "firstName": "John",
  "lastName": "Doe",
  "phone": "+1234567890"
}
```

### Delivery Person
```bash
POST /api/register/delivery
{
  "email": "driver@example.com",
  "password": "password123",
  "firstName": "Mike",
  "lastName": "Driver",
  "phone": "+1234567891",
  "vehicleType": "motorcycle",  # bike|motorcycle|car|van
  "vehiclePlate": "ABC123"
}
```

### Store Owner
```bash
POST /api/register/store
{
  "email": "store@example.com",
  "password": "password123",
  "firstName": "Sarah",
  "lastName": "Shop",
  "phone": "+1234567892",
  "storeName": "Main Pharmacy",
  "storeAddress": "123 Main St"
}
```

**All return:**
```json
{
  "token": "eyJ0eXAi...",
  "user": { ... }
}
```

---

## 🔐 Login

```bash
POST /api/auth
{
  "email": "user@example.com",
  "password": "password123"
}
```

**Returns:**
```json
{
  "token": "eyJ0eXAi...",
  "refresh_token": "def50200...",
  "user": {
    "userType": "customer|delivery|store",
    "roles": [...],
    "delivery": { ... },  # if delivery
    "store": { ... }      # if store
  }
}
```

---

## 🔄 Token Refresh

```bash
POST /api/token/refresh
{
  "refresh_token": "def50200..."
}
```

---

## 👤 Current User

```bash
GET /api/me
Authorization: Bearer {token}
```

---

## 🔑 Password Reset

### 1. Request Reset Code
```bash
POST /api/password/forgot
{
  "email": "user@example.com"
}
```

### 2. Verify Code
```bash
POST /api/password/verify-code
{
  "email": "user@example.com",
  "code": "123456"
}
```

### 3. Reset Password
```bash
POST /api/password/reset
{
  "email": "user@example.com",
  "code": "123456",
  "password": "newPassword123"
}
```

---

## 🔐 Update Password (Authenticated)

```bash
POST /api/user/update-password
Authorization: Bearer {token}
{
  "currentPassword": "oldPassword123",
  "newPassword": "newPassword123",
  "confirmPassword": "newPassword123"
}
```

---

## 👥 Update Profile

```bash
POST /api/user/update
Authorization: Bearer {token}
Content-Type: multipart/form-data

firstName: John
lastName: Doe
phone: +1234567890
imageFile: [file]
```

---

## 📱 Social Login

### Facebook
```bash
POST /api/facebook_login
{
  "accessToken": "facebook_token"
}
```

### Google
```bash
POST /api/google_login
{
  "accessToken": "google_token"
}
```

---

## ⚠️ Common Errors

```json
// 400 - Validation Error
{
  "error": "Validation failed",
  "violations": [...]
}

// 401 - Unauthorized
{
  "code": 401,
  "message": "Invalid credentials."
}

// 409 - Conflict
{
  "error": "Email already exists",
  "code": 409
}
```

---

## 📚 Full Documentation

- **ALL_AUTH_PASSWORD_USER_APIS.md** - Complete API docs
- **AUTHENTICATION_API_GUIDE.md** - Mobile integration
- **REFACTORED_AUTH_SUMMARY.md** - What changed

---

## 🎯 Key Points

✅ Registration returns JWT token (auto-login)  
✅ Same login endpoint for all apps  
✅ Role-based response data  
✅ Token expires in 1 hour  
✅ Use refresh token to renew  
✅ All endpoints use State processors  

---

## 🧪 Test Credentials

After running the app, create test users:

```bash
# Customer
email: customer@test.com
password: password123

# Delivery
email: delivery@test.com
password: password123

# Store
email: store@test.com
password: password123
```

