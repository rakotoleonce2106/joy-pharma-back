# Authentication, Password & Profile Management Guide

## 🚀 Quick Start Guide

Complete guide for authentication, password management, and profile updates.

---

## 📋 Table of Contents

1. [Authentication](#authentication)
2. [Registration](#registration)
3. [Password Management](#password-management)
4. [Profile Management](#profile-management)
5. [Mobile Integration Examples](#mobile-integration-examples)
6. [Error Handling](#error-handling)

---

## Authentication

### 1. Login

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
    "email": "user@example.com",
    "firstName": "John",
    "lastName": "Doe",
    "phone": "+1234567890",
    "roles": ["ROLE_USER"],
    "userType": "customer",
    "isActive": true
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
    "email": "delivery@example.com",
    "firstName": "Mike",
    "lastName": "Driver",
    "roles": ["ROLE_USER", "ROLE_DELIVER"],
    "userType": "delivery",
    "isActive": true,
    "delivery": {
      "vehicleType": "motorcycle",
      "vehiclePlate": "ABC123",
      "isOnline": false,
      "totalDeliveries": 150,
      "averageRating": 4.8,
      "totalEarnings": "2250.00"
    }
  }
}
```

**Response (Store Owner):**
```json
{
  "token": "eyJ0eXAiOiJKV1Qi...",
  "refresh_token": "def50200...",
  "user": {
    "id": 3,
    "email": "store@example.com",
    "firstName": "Sarah",
    "lastName": "Shop",
    "roles": ["ROLE_USER", "ROLE_STORE"],
    "userType": "store",
    "isActive": true,
    "store": {
      "id": 1,
      "name": "Main Pharmacy",
      "address": "123 Main St",
      "phone": "+1234567890"
    }
  }
}
```

**cURL Example:**
```bash
curl -X POST https://api.example.com/api/auth \
  -H "Content-Type: application/json" \
  -d '{
    "email": "user@example.com",
    "password": "password123"
  }'
```

---

### 2. Refresh Token

**Endpoint:** `POST /api/token/refresh`

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

**When to Use:**
- Access token expires after 1 hour
- Use refresh token to get new access token
- Refresh token expires after 7 days

**cURL Example:**
```bash
curl -X POST https://api.example.com/api/token/refresh \
  -H "Content-Type: application/json" \
  -d '{
    "refresh_token": "def50200..."
  }'
```

---

### 3. Get Current User

**Endpoint:** `GET /api/me`

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

**cURL Example:**
```bash
curl -X GET https://api.example.com/api/me \
  -H "Authorization: Bearer YOUR_JWT_TOKEN"
```

---

### 4. Logout

**Endpoint:** `POST /api/logout`

**Headers:**
```
Authorization: Bearer eyJ0eXAiOiJKV1Qi...
```

**Response:**
```json
{
  "message": "Logged out successfully"
}
```

**Note:** Client must also remove tokens from local storage.

**cURL Example:**
```bash
curl -X POST https://api.example.com/api/logout \
  -H "Authorization: Bearer YOUR_JWT_TOKEN"
```

---

## Registration

### 1. Register Customer

**Endpoint:** `POST /api/register`

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

**Validation:**
- Email: Valid email format, unique
- Password: Minimum 8 characters
- All fields are required

**Response (201 Created):**
```json
{
  "token": "eyJ0eXAiOiJKV1Qi...",
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

**✨ Note:** User is automatically logged in after registration (receives JWT token)!

**cURL Example:**
```bash
curl -X POST https://api.example.com/api/register \
  -H "Content-Type: application/json" \
  -d '{
    "email": "customer@example.com",
    "password": "password123",
    "firstName": "John",
    "lastName": "Doe",
    "phone": "+1234567890"
  }'
```

---

### 2. Register Delivery Person

**Endpoint:** `POST /api/register/delivery`

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

**Vehicle Types:**
- `bike`
- `motorcycle`
- `car`
- `van`

**Response (201 Created):**
```json
{
  "token": "eyJ0eXAiOiJKV1Qi...",
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

**cURL Example:**
```bash
curl -X POST https://api.example.com/api/register/delivery \
  -H "Content-Type: application/json" \
  -d '{
    "email": "delivery@example.com",
    "password": "password123",
    "firstName": "Mike",
    "lastName": "Driver",
    "phone": "+1234567891",
    "vehicleType": "motorcycle",
    "vehiclePlate": "ABC123"
  }'
```

---

### 3. Register Store Owner

**Endpoint:** `POST /api/register/store`

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

**Required Fields:**
- email, password, firstName, lastName, phone
- storeName, storeAddress

**Optional Fields:**
- storePhone, storeEmail, storeDescription
- storeCity, storeLatitude, storeLongitude

**Response (201 Created):**
```json
{
  "token": "eyJ0eXAiOiJKV1Qi...",
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

## Password Management

### 1. Forgot Password (Request Reset Code)

**Endpoint:** `POST /api/password/forgot`

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

**How It Works:**
1. System generates a 6-digit code (e.g., 123456)
2. Code is sent to user's email
3. Code expires after 15 minutes
4. Only one active code per email

**cURL Example:**
```bash
curl -X POST https://api.example.com/api/password/forgot \
  -H "Content-Type: application/json" \
  -d '{
    "email": "user@example.com"
  }'
```

---

### 2. Verify Reset Code

**Endpoint:** `POST /api/password/verify-code`

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

**Response (Error):**
```json
{
  "error": "Invalid or expired code",
  "code": 400
}
```

**cURL Example:**
```bash
curl -X POST https://api.example.com/api/password/verify-code \
  -H "Content-Type: application/json" \
  -d '{
    "email": "user@example.com",
    "code": "123456"
  }'
```

---

### 3. Reset Password

**Endpoint:** `POST /api/password/reset`

**Request:**
```json
{
  "email": "user@example.com",
  "code": "123456",
  "password": "newPassword123"
}
```

**Validation:**
- Password must be at least 8 characters

**Response (Success):**
```json
{
  "success": true,
  "message": "Password reset successfully"
}
```

**cURL Example:**
```bash
curl -X POST https://api.example.com/api/password/reset \
  -H "Content-Type: application/json" \
  -d '{
    "email": "user@example.com",
    "code": "123456",
    "password": "newPassword123"
  }'
```

---

### 4. Update Password (Authenticated)

**Endpoint:** `POST /api/user/update-password`

**Headers:**
```
Authorization: Bearer eyJ0eXAiOiJKV1Qi...
```

**Request:**
```json
{
  "currentPassword": "oldPassword123",
  "newPassword": "newPassword123",
  "confirmPassword": "newPassword123"
}
```

**Validation:**
- Must provide correct current password
- New password minimum 8 characters
- Confirm password must match new password

**Response (Success):**
```json
{
  "success": true,
  "message": "Password updated successfully"
}
```

**Response (Error):**
```json
{
  "error": "Current password is incorrect",
  "code": 400
}
```

**Note:** Sends confirmation email after successful update.

**cURL Example:**
```bash
curl -X POST https://api.example.com/api/user/update-password \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "currentPassword": "oldPassword123",
    "newPassword": "newPassword123",
    "confirmPassword": "newPassword123"
  }'
```

---

## Profile Management

### Update Profile

**Endpoint:** `POST /api/user/update`

**Content-Type:** `multipart/form-data`

**Headers:**
```
Authorization: Bearer eyJ0eXAiOiJKV1Qi...
```

**Form Data:**
```
firstName: John
lastName: Doe
phone: +1234567890
imageFile: [file upload]
```

**All Fields Are Optional:**
- `firstName` - User's first name
- `lastName` - User's last name
- `phone` - Phone number
- `imageFile` - Profile picture (image file)

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

**cURL Example (Text Fields Only):**
```bash
curl -X POST https://api.example.com/api/user/update \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -F "firstName=John" \
  -F "lastName=Doe" \
  -F "phone=+1234567890"
```

**cURL Example (With Image):**
```bash
curl -X POST https://api.example.com/api/user/update \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -F "firstName=John" \
  -F "lastName=Doe" \
  -F "phone=+1234567890" \
  -F "imageFile=@/path/to/avatar.jpg"
```

---

## Mobile Integration Examples

### React Native / JavaScript

#### 1. Login Function

```javascript
async function login(email, password) {
  try {
    const response = await fetch('https://api.example.com/api/auth', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({ email, password })
    });

    if (!response.ok) {
      const error = await response.json();
      throw new Error(error.message || 'Login failed');
    }

    const data = await response.json();

    // Store tokens securely
    await AsyncStorage.setItem('jwt_token', data.token);
    await AsyncStorage.setItem('refresh_token', data.refresh_token);
    await AsyncStorage.setItem('user', JSON.stringify(data.user));

    // Validate user type for app
    if (APP_TYPE === 'delivery' && data.user.userType !== 'delivery') {
      throw new Error('This app is for delivery persons only');
    }

    return data;
  } catch (error) {
    console.error('Login error:', error);
    throw error;
  }
}

// Usage
try {
  const result = await login('user@example.com', 'password123');
  console.log('Logged in:', result.user);
  navigation.navigate('Home');
} catch (error) {
  alert(error.message);
}
```

---

#### 2. Register Customer

```javascript
async function registerCustomer(formData) {
  try {
    const response = await fetch('https://api.example.com/api/register', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        email: formData.email,
        password: formData.password,
        firstName: formData.firstName,
        lastName: formData.lastName,
        phone: formData.phone
      })
    });

    if (!response.ok) {
      const error = await response.json();
      throw new Error(error.error || 'Registration failed');
    }

    const data = await response.json();

    // User is automatically logged in!
    await AsyncStorage.setItem('jwt_token', data.token);
    await AsyncStorage.setItem('user', JSON.stringify(data.user));

    return data;
  } catch (error) {
    console.error('Registration error:', error);
    throw error;
  }
}
```

---

#### 3. Register Delivery Person

```javascript
async function registerDelivery(formData) {
  try {
    const response = await fetch('https://api.example.com/api/register/delivery', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        email: formData.email,
        password: formData.password,
        firstName: formData.firstName,
        lastName: formData.lastName,
        phone: formData.phone,
        vehicleType: formData.vehicleType, // bike, motorcycle, car, van
        vehiclePlate: formData.vehiclePlate
      })
    });

    if (!response.ok) {
      const error = await response.json();
      throw new Error(error.error || 'Registration failed');
    }

    const data = await response.json();

    // User is automatically logged in!
    await AsyncStorage.setItem('jwt_token', data.token);
    await AsyncStorage.setItem('user', JSON.stringify(data.user));

    return data;
  } catch (error) {
    console.error('Registration error:', error);
    throw error;
  }
}
```

---

#### 4. Password Reset Flow

```javascript
// Step 1: Request reset code
async function requestPasswordReset(email) {
  const response = await fetch('https://api.example.com/api/password/forgot', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ email })
  });
  
  return await response.json();
}

// Step 2: Verify code
async function verifyResetCode(email, code) {
  const response = await fetch('https://api.example.com/api/password/verify-code', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ email, code })
  });
  
  if (!response.ok) {
    throw new Error('Invalid code');
  }
  
  return await response.json();
}

// Step 3: Reset password
async function resetPassword(email, code, password) {
  const response = await fetch('https://api.example.com/api/password/reset', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ email, code, password })
  });
  
  if (!response.ok) {
    throw new Error('Password reset failed');
  }
  
  return await response.json();
}

// Complete flow usage
try {
  // Step 1
  await requestPasswordReset('user@example.com');
  alert('Check your email for reset code');
  
  // Step 2 - User enters code
  await verifyResetCode('user@example.com', '123456');
  
  // Step 3 - User enters new password
  await resetPassword('user@example.com', '123456', 'newPassword123');
  alert('Password reset successfully');
  navigation.navigate('Login');
} catch (error) {
  alert(error.message);
}
```

---

#### 5. Update Password (Authenticated)

```javascript
async function updatePassword(currentPassword, newPassword, confirmPassword) {
  const token = await AsyncStorage.getItem('jwt_token');
  
  const response = await fetch('https://api.example.com/api/user/update-password', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'Authorization': `Bearer ${token}`
    },
    body: JSON.stringify({
      currentPassword,
      newPassword,
      confirmPassword
    })
  });
  
  if (!response.ok) {
    const error = await response.json();
    throw new Error(error.error || 'Password update failed');
  }
  
  return await response.json();
}

// Usage
try {
  await updatePassword('oldPass123', 'newPass123', 'newPass123');
  alert('Password updated successfully');
} catch (error) {
  alert(error.message);
}
```

---

#### 6. Update Profile

```javascript
async function updateProfile(formData, imageUri = null) {
  const token = await AsyncStorage.getItem('jwt_token');
  
  const formDataObj = new FormData();
  
  if (formData.firstName) {
    formDataObj.append('firstName', formData.firstName);
  }
  if (formData.lastName) {
    formDataObj.append('lastName', formData.lastName);
  }
  if (formData.phone) {
    formDataObj.append('phone', formData.phone);
  }
  
  // Add image if provided
  if (imageUri) {
    formDataObj.append('imageFile', {
      uri: imageUri,
      type: 'image/jpeg',
      name: 'profile.jpg'
    });
  }
  
  const response = await fetch('https://api.example.com/api/user/update', {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${token}`
      // Don't set Content-Type for FormData - let browser set it
    },
    body: formDataObj
  });
  
  if (!response.ok) {
    throw new Error('Profile update failed');
  }
  
  const updatedUser = await response.json();
  await AsyncStorage.setItem('user', JSON.stringify(updatedUser));
  
  return updatedUser;
}

// Usage
try {
  const updated = await updateProfile({
    firstName: 'John',
    lastName: 'Doe',
    phone: '+1234567890'
  }, 'file:///path/to/image.jpg');
  
  alert('Profile updated successfully');
} catch (error) {
  alert(error.message);
}
```

---

#### 7. Token Refresh

```javascript
async function refreshToken() {
  const refreshToken = await AsyncStorage.getItem('refresh_token');
  
  const response = await fetch('https://api.example.com/api/token/refresh', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ refresh_token: refreshToken })
  });
  
  if (!response.ok) {
    // Refresh token expired - logout user
    await logout();
    navigation.navigate('Login');
    return null;
  }
  
  const data = await response.json();
  
  await AsyncStorage.setItem('jwt_token', data.token);
  await AsyncStorage.setItem('refresh_token', data.refresh_token);
  
  return data.token;
}

// Auto-refresh on 401 error
async function makeAuthenticatedRequest(url, options = {}) {
  let token = await AsyncStorage.getItem('jwt_token');
  
  options.headers = {
    ...options.headers,
    'Authorization': `Bearer ${token}`
  };
  
  let response = await fetch(url, options);
  
  // If unauthorized, try to refresh token
  if (response.status === 401) {
    token = await refreshToken();
    
    if (token) {
      // Retry with new token
      options.headers['Authorization'] = `Bearer ${token}`;
      response = await fetch(url, options);
    }
  }
  
  return response;
}
```

---

#### 8. Logout

```javascript
async function logout() {
  try {
    const token = await AsyncStorage.getItem('jwt_token');
    
    // Call logout endpoint
    await fetch('https://api.example.com/api/logout', {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${token}`
      }
    });
  } catch (error) {
    console.error('Logout error:', error);
  } finally {
    // Always clear local storage
    await AsyncStorage.removeItem('jwt_token');
    await AsyncStorage.removeItem('refresh_token');
    await AsyncStorage.removeItem('user');
    
    // Navigate to login
    navigation.navigate('Login');
  }
}
```

---

### Flutter / Dart

#### Login Function

```dart
import 'package:http/http.dart' as http;
import 'dart:convert';
import 'package:shared_preferences/shared_preferences.dart';

Future<Map<String, dynamic>> login(String email, String password) async {
  final response = await http.post(
    Uri.parse('https://api.example.com/api/auth'),
    headers: {'Content-Type': 'application/json'},
    body: jsonEncode({
      'email': email,
      'password': password,
    }),
  );

  if (response.statusCode == 200) {
    final data = jsonDecode(response.body);
    
    // Store tokens
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString('jwt_token', data['token']);
    await prefs.setString('refresh_token', data['refresh_token']);
    await prefs.setString('user', jsonEncode(data['user']));
    
    return data;
  } else {
    throw Exception('Login failed');
  }
}

// Usage
try {
  final result = await login('user@example.com', 'password123');
  print('Logged in: ${result['user']['email']}');
  Navigator.pushReplacementNamed(context, '/home');
} catch (e) {
  showDialog(
    context: context,
    builder: (context) => AlertDialog(
      title: Text('Error'),
      content: Text(e.toString()),
    ),
  );
}
```

---

## Error Handling

### Common Errors

#### 400 Bad Request
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

#### 401 Unauthorized
```json
{
  "code": 401,
  "message": "Invalid credentials."
}
```

#### 409 Conflict (Email Already Exists)
```json
{
  "error": "Email already exists",
  "code": 409
}
```

---

### Error Handling in Mobile Apps

```javascript
async function handleApiCall(apiFunction) {
  try {
    return await apiFunction();
  } catch (error) {
    if (error.response) {
      // Server responded with error
      const { code, message, violations } = error.response.data;
      
      switch (code) {
        case 400:
          // Validation errors
          if (violations) {
            const errorMessages = violations.map(v => v.message).join('\n');
            alert(errorMessages);
          } else {
            alert(message || 'Invalid request');
          }
          break;
          
        case 401:
          // Unauthorized - try to refresh token
          const newToken = await refreshToken();
          if (!newToken) {
            // Refresh failed - logout
            await logout();
            navigation.navigate('Login');
          }
          break;
          
        case 409:
          alert('Email already exists. Please use a different email.');
          break;
          
        default:
          alert(message || 'An error occurred');
      }
    } else {
      // Network error
      alert('Network error. Please check your connection.');
    }
    throw error;
  }
}

// Usage
await handleApiCall(() => login(email, password));
```

---

## Security Best Practices

### 1. Token Storage

**✅ DO:**
- Use secure storage (iOS Keychain, Android Keystore)
- Use AsyncStorage with encryption
- Never store tokens in plain text

**❌ DON'T:**
- Don't store tokens in localStorage (web)
- Don't log tokens to console
- Don't share tokens between apps

---

### 2. Token Refresh

**✅ DO:**
- Automatically refresh when receiving 401
- Refresh before expiration (proactive)
- Handle refresh failures gracefully

```javascript
// Proactive refresh (before expiration)
setInterval(async () => {
  const tokenAge = Date.now() - lastTokenTime;
  const oneHour = 60 * 60 * 1000;
  
  if (tokenAge > oneHour * 0.8) { // Refresh at 80% of lifetime
    await refreshToken();
  }
}, 5 * 60 * 1000); // Check every 5 minutes
```

---

### 3. Password Requirements

**Client-Side Validation:**
```javascript
function validatePassword(password) {
  const errors = [];
  
  if (password.length < 8) {
    errors.push('Password must be at least 8 characters');
  }
  if (!/[A-Z]/.test(password)) {
    errors.push('Password must contain uppercase letter');
  }
  if (!/[a-z]/.test(password)) {
    errors.push('Password must contain lowercase letter');
  }
  if (!/[0-9]/.test(password)) {
    errors.push('Password must contain a number');
  }
  
  return errors;
}
```

---

### 4. Logout on App Close

```javascript
// React Native
import { AppState } from 'react-native';

AppState.addEventListener('change', (nextAppState) => {
  if (nextAppState === 'background') {
    // Optional: Clear sensitive data
    // await clearSensitiveData();
  }
});
```

---

## Quick Reference

### Authentication Endpoints
| Endpoint | Method | Auth | Purpose |
|----------|--------|------|---------|
| `/api/auth` | POST | No | Login |
| `/api/token/refresh` | POST | No | Refresh token |
| `/api/me` | GET | Yes | Get current user |
| `/api/logout` | POST | Yes | Logout |

### Registration Endpoints
| Endpoint | Method | Auth | Purpose |
|----------|--------|------|---------|
| `/api/register` | POST | No | Register customer |
| `/api/register/delivery` | POST | No | Register delivery person |
| `/api/register/store` | POST | No | Register store owner |

### Password Endpoints
| Endpoint | Method | Auth | Purpose |
|----------|--------|------|---------|
| `/api/password/forgot` | POST | No | Request reset code |
| `/api/password/verify-code` | POST | No | Verify reset code |
| `/api/password/reset` | POST | No | Reset password |
| `/api/user/update-password` | POST | Yes | Update password |

### Profile Endpoints
| Endpoint | Method | Auth | Purpose |
|----------|--------|------|---------|
| `/api/user/update` | POST | Yes | Update profile |

---

## Complete Flow Examples

### Registration → Login Flow
```javascript
// User registers
const registerResult = await registerCustomer({
  email: 'new@example.com',
  password: 'password123',
  firstName: 'John',
  lastName: 'Doe',
  phone: '+1234567890'
});

// Already logged in! Token received
console.log('Token:', registerResult.token);

// Navigate to home
navigation.navigate('Home');
```

### Password Reset Flow
```javascript
// Step 1: User clicks "Forgot Password"
await requestPasswordReset('user@example.com');
// → User receives email with 6-digit code

// Step 2: User enters code
await verifyResetCode('user@example.com', '123456');
// → Code is validated

// Step 3: User sets new password
await resetPassword('user@example.com', '123456', 'newPassword123');
// → Password updated

// Step 4: User can now login
await login('user@example.com', 'newPassword123');
```

---

## Testing

### Test with cURL

```bash
# Login
curl -X POST http://localhost/api/auth \
  -H "Content-Type: application/json" \
  -d '{"email":"user@example.com","password":"password123"}'

# Register
curl -X POST http://localhost/api/register \
  -H "Content-Type: application/json" \
  -d '{"email":"new@example.com","password":"password123","firstName":"John","lastName":"Doe","phone":"+1234567890"}'

# Get current user
curl -X GET http://localhost/api/me \
  -H "Authorization: Bearer YOUR_TOKEN"

# Update profile
curl -X POST http://localhost/api/user/update \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -F "firstName=John" \
  -F "lastName=Doe"
```

---

## Support

For more information, see:
- **ALL_AUTH_PASSWORD_USER_APIS.md** - Complete API reference
- **DELIVERY_API_DOCUMENTATION.md** - Delivery APIs
- **STATE_ORGANIZATION_SUMMARY.md** - Technical architecture

---

## 🎉 Summary

✅ **Single login endpoint** for all user types  
✅ **Registration returns JWT token** - Auto-login  
✅ **Complete password reset flow** - 6-digit code  
✅ **Profile updates** - With image upload  
✅ **Mobile-ready examples** - React Native & Flutter  
✅ **Security best practices** - Token management  
✅ **Error handling** - All scenarios covered  

Your authentication system is production-ready! 🚀

