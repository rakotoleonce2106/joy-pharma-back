# API Endpoints Documentation

This document provides comprehensive documentation for the main API endpoints: deliver, register, auth, update profile, me, parameters, and responses.

## Table of Contents

1. [Authentication (Login)](#authentication-login)
2. [Registration](#registration)
3. [Update Profile](#update-profile)
4. [Get Current User (Me)](#get-current-user-me)
5. [Delivery Endpoints](#delivery-endpoints)
6. [Request Parameters](#request-parameters)
7. [Response Format](#response-format)

---

## Authentication (Login)

### Endpoint: `POST /api/auth`

**Description:** Authenticate a user and receive JWT token.

**Authentication:** Not required

**Content-Type:** `application/json`

**Request Body Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `email` | string | Yes | User email address |
| `password` | string | Yes | User password (min 8 characters) |

**Example Request:**
```bash
curl -X POST "https://api.example.com/api/auth" \
  -H "Content-Type: application/json" \
  -d '{
    "email": "user@example.com",
    "password": "password123"
  }'
```

**Success Response (200 OK):**
```json
{
  "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9...",
  "refresh_token": "a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6q7r8s9t0u1v2w3x4y5z6",
  "user": {
    "id": 1,
    "email": "user@example.com",
    "firstName": "John",
    "lastName": "Doe",
    "phone": "+261340000000",
    "roles": ["ROLE_USER"],
    "userType": "customer",
    "isActive": true,
    "avatar": "/uploads/profile/avatar.jpg"
  }
}
```

**For Delivery Person:**
```json
{
  "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9...",
  "refresh_token": "a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6q7r8s9t0u1v2w3x4y5z6",
  "user": {
    "id": 8,
    "email": "deliver@example.com",
    "firstName": "Delivery",
    "lastName": "Person",
    "phone": "+261340000000",
    "roles": ["ROLE_DELIVER"],
    "userType": "delivery",
    "isActive": true,
    "avatar": "/uploads/profile/avatar.jpg",
    "delivery": {
      "vehicleType": "motorcycle",
      "vehiclePlate": "ABC-1234",
      "isOnline": false,
      "totalDeliveries": 45,
      "averageRating": 4.5,
      "totalEarnings": "125000.00",
      "currentLatitude": "-18.8792",
      "currentLongitude": "47.5079",
      "lastLocationUpdate": "2025-01-15T10:00:00+00:00"
    }
  }
}
```

**For Store Owner:**
```json
{
  "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9...",
  "refresh_token": "a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6q7r8s9t0u1v2w3x4y5z6",
  "user": {
    "id": 5,
    "email": "store@example.com",
    "firstName": "Store",
    "lastName": "Owner",
    "phone": "+261340000000",
    "roles": ["ROLE_USER", "ROLE_STORE"],
    "userType": "store",
    "isActive": true,
    "avatar": "/uploads/profile/avatar.jpg",
    "store": {
      "id": 3,
      "name": "Pharmacy ABC",
      "description": "Best pharmacy in town",
      "phone": "+261340000001",
      "email": "pharmacy@example.com",
      "address": "123 Main Street",
      "city": "Antananarivo",
      "latitude": -18.8792,
      "longitude": 47.5079,
      "image": "/images/store/store.jpg",
      "isActive": true
    }
  }
}
```

**Error Responses:**

- `401 Unauthorized`: Invalid credentials
  ```json
  {
    "code": 401,
    "message": "An authentication exception occurred."
  }
  ```

- `403 Forbidden`: Account inactive (for delivery persons)
  ```json
  {
    "code": 403,
    "message": "Account is inactive. Please contact administrator."
  }
  ```

---

## Registration

### 1. Customer Registration

**Endpoint:** `POST /api/register`

**Description:** Register a new customer account.

**Authentication:** Not required

**Content-Type:** `application/json`

**Request Body Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `email` | string | Yes | Email address (must be unique) |
| `password` | string | Yes | Password (minimum 8 characters) |
| `firstName` | string | Yes | First name |
| `lastName` | string | Yes | Last name |
| `phone` | string | Yes | Phone number |

**Example Request:**
```bash
curl -X POST "https://api.example.com/api/register" \
  -H "Content-Type: application/json" \
  -d '{
    "email": "customer@example.com",
    "password": "password123",
    "firstName": "John",
    "lastName": "Doe",
    "phone": "+261340000000"
  }'
```

**Success Response (201 Created):**
```json
{
  "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9...",
  "user": {
    "id": 12,
    "email": "customer@example.com",
    "firstName": "John",
    "lastName": "Doe",
    "phone": "+261340000000",
    "roles": ["ROLE_USER"],
    "userType": "customer",
    "isActive": true
  }
}
```

**Error Response (409 Conflict):**
```json
{
  "detail": "Email already exists"
}
```

---

### 2. Delivery Person Registration

**Endpoint:** `POST /api/register/delivery`

**Description:** Register a new delivery person account (inactive by default, requires admin activation).

**Authentication:** Not required

**Content-Type:** `multipart/form-data`

**Request Body Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `email` | string | Yes | Email address (must be unique) |
| `password` | string | Yes | Password (minimum 8 characters) |
| `firstName` | string | Yes | First name |
| `lastName` | string | Yes | Last name |
| `phone` | string | Yes | Phone number |
| `vehicleType` | string | Yes | Vehicle type: `bike`, `motorcycle`, `car`, or `van` |
| `vehiclePlate` | string | No | Vehicle license plate |
| `residenceDocument` | file | Yes | Proof of residence (PDF/JPG/PNG/WEBP, max 10MB) |
| `vehicleDocument` | file | Yes | Vehicle registration document (PDF/JPG/PNG/WEBP, max 10MB) |

**Example Request:**
```bash
curl -X POST "https://api.example.com/api/register/delivery" \
  -F "email=rider@example.com" \
  -F "password=password123" \
  -F "firstName=Alex" \
  -F "lastName=Rider" \
  -F "phone=+261340000000" \
  -F "vehicleType=motorcycle" \
  -F "vehiclePlate=ABC-1234" \
  -F "residenceDocument=@/path/to/residence.pdf" \
  -F "vehicleDocument=@/path/to/vehicle.pdf"
```

**Success Response (201 Created):**
```json
{
  "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9...",
  "user": {
    "id": 123,
    "email": "rider@example.com",
    "firstName": "Alex",
    "lastName": "Rider",
    "phone": "+261340000000",
    "roles": ["ROLE_DELIVER"],
    "userType": "delivery",
    "isActive": false,
    "delivery": {
      "vehicleType": "motorcycle",
      "vehiclePlate": "ABC-1234",
      "isOnline": false,
      "totalDeliveries": 0,
      "averageRating": 0,
      "totalEarnings": "0.00"
    }
  }
}
```

**Error Response (409 Conflict):**
```json
{
  "detail": "Email already exists"
}
```

**Notes:**
- Delivery accounts are created **inactive** by default
- Admin must activate the account before login is allowed
- Inactive deliverers are blocked from authentication with a clear message

---

### 3. Store Owner Registration

**Endpoint:** `POST /api/register/store`

**Description:** Register a new store owner account.

**Authentication:** Not required

**Content-Type:** `application/json`

**Request Body Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `email` | string | Yes | Email address (must be unique) |
| `password` | string | Yes | Password (minimum 8 characters) |
| `firstName` | string | Yes | First name |
| `lastName` | string | Yes | Last name |
| `phone` | string | Yes | Phone number |
| `storeName` | string | Yes | Store name |
| `storeAddress` | string | Yes | Store address |
| `storePhone` | string | Yes | Store phone number |
| `storeEmail` | string | Yes | Store email address |
| `storeDescription` | string | No | Store description |
| `storeCity` | string | No | Store city |
| `storeLatitude` | float | No | Store latitude |
| `storeLongitude` | float | No | Store longitude |

**Example Request:**
```bash
curl -X POST "https://api.example.com/api/register/store" \
  -H "Content-Type: application/json" \
  -d '{
    "email": "store@example.com",
    "password": "password123",
    "firstName": "Store",
    "lastName": "Owner",
    "phone": "+261340000000",
    "storeName": "Pharmacy ABC",
    "storeAddress": "123 Main Street",
    "storePhone": "+261340000001",
    "storeEmail": "pharmacy@example.com",
    "storeDescription": "Best pharmacy in town",
    "storeCity": "Antananarivo",
    "storeLatitude": -18.8792,
    "storeLongitude": 47.5079
  }'
```

**Success Response (201 Created):**
```json
{
  "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9...",
  "user": {
    "id": 45,
    "email": "store@example.com",
    "firstName": "Store",
    "lastName": "Owner",
    "phone": "+261340000000",
    "roles": ["ROLE_USER", "ROLE_STORE"],
    "userType": "store",
    "isActive": true,
    "store": {
      "id": 3,
      "name": "Pharmacy ABC",
      "description": "Best pharmacy in town"
    }
  }
}
```

**Error Response (409 Conflict):**
```json
{
  "detail": "Email already exists"
}
```

---

## Update Profile

**Endpoint:** `PUT /api/user/update`

**Description:** Update user profile information with optional avatar upload.

**Authentication:** Required (`ROLE_USER`)

**Content-Type:** `multipart/form-data` or `application/json`

**Request Body Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `firstName` | string | No | First name |
| `lastName` | string | No | Last name |
| `phone` | string | No | Phone number |
| `imageFile` | file | No | Profile avatar image (multipart only) |

**For Delivery Persons (Additional Parameters):**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `isOnline` | boolean | No | Online/offline status (JSON only) |
| `vehicleType` | string | No | Vehicle type: `bike`, `motorcycle`, `car`, or `van` |
| `vehiclePlate` | string | No | Vehicle license plate |

**Example Request (Multipart):**
```bash
curl -X PUT "https://api.example.com/api/user/update" \
  -H "Authorization: Bearer {JWT_TOKEN}" \
  -F "firstName=John" \
  -F "lastName=Doe" \
  -F "phone=+261340000000" \
  -F "vehicleType=motorcycle" \
  -F "vehiclePlate=ABC-1234" \
  -F "imageFile=@/path/to/avatar.jpg"
```

**Example Request (JSON):**
```bash
curl -X PUT "https://api.example.com/api/user/update" \
  -H "Authorization: Bearer {JWT_TOKEN}" \
  -H "Content-Type: application/json" \
  -d '{
    "firstName": "John",
    "lastName": "Doe",
    "phone": "+261340000000",
    "isOnline": true
  }'
```

**Success Response (200 OK):**
```json
{
  "id": 123,
  "email": "rider@example.com",
  "firstName": "John",
  "lastName": "Doe",
  "phone": "+261340000000",
  "roles": ["ROLE_DELIVER"],
  "userType": "delivery",
  "isActive": true,
  "avatar": "/uploads/profile/avatar.jpg",
  "delivery": {
    "vehicleType": "motorcycle",
    "vehiclePlate": "ABC-1234",
    "isOnline": true,
    "totalDeliveries": 45,
    "averageRating": 4.5,
    "totalEarnings": "125000.00"
  }
}
```

**Error Response (400 Bad Request):**
```json
{
  "type": "https://tools.ietf.org/html/rfc2616#section-10",
  "title": "An error occurred",
  "detail": "Validation failed",
  "violations": [
    {
      "propertyPath": "firstName",
      "message": "First name cannot be empty"
    }
  ]
}
```

---

## Get Current User (Me)

**Endpoint:** `GET /api/me`

**Description:** Get the currently authenticated user's information.

**Authentication:** Required (`ROLE_USER`)

**Example Request:**
```bash
curl -X GET "https://api.example.com/api/me" \
  -H "Authorization: Bearer {JWT_TOKEN}"
```

**Success Response (200 OK) - Customer:**
```json
{
  "id": 1,
  "email": "customer@example.com",
  "firstName": "John",
  "lastName": "Doe",
  "phone": "+261340000000",
  "roles": ["ROLE_USER"],
  "userType": "customer",
  "isActive": true,
  "avatar": "/uploads/profile/avatar.jpg"
}
```

**Success Response (200 OK) - Delivery Person:**
```json
{
  "id": 8,
  "email": "deliver@example.com",
  "firstName": "Delivery",
  "lastName": "Person",
  "phone": "+261340000000",
  "roles": ["ROLE_DELIVER"],
  "userType": "delivery",
  "isActive": true,
  "avatar": "/uploads/profile/avatar.jpg",
  "delivery": {
    "vehicleType": "motorcycle",
    "vehiclePlate": "ABC-1234",
    "isOnline": false,
    "totalDeliveries": 45,
    "averageRating": 4.5,
    "totalEarnings": "125000.00",
    "currentLatitude": "-18.8792",
    "currentLongitude": "47.5079",
    "lastLocationUpdate": "2025-01-15T10:00:00+00:00"
  }
}
```

**Success Response (200 OK) - Store Owner:**
```json
{
  "id": 5,
  "email": "store@example.com",
  "firstName": "Store",
  "lastName": "Owner",
  "phone": "+261340000000",
  "roles": ["ROLE_USER", "ROLE_STORE"],
  "userType": "store",
  "isActive": true,
  "avatar": "/uploads/profile/avatar.jpg",
  "store": {
    "id": 3,
    "name": "Pharmacy ABC",
    "description": "Best pharmacy in town",
    "phone": "+261340000001",
    "email": "pharmacy@example.com",
    "address": "123 Main Street",
    "city": "Antananarivo",
    "latitude": -18.8792,
    "longitude": 47.5079,
    "image": "/images/store/store.jpg",
    "isActive": true
  }
}
```

**Error Response (401 Unauthorized):**
```json
{
  "code": 401,
  "message": "JWT Token not found"
}
```

---

## Delivery Endpoints

For comprehensive delivery API documentation, see [DELIVER_API.md](./DELIVER_API.md).

### Key Delivery Endpoints:

1. **Get Available Orders** - `GET /api/orders/available`
   - Get list of orders available for delivery
   - Authentication: `ROLE_DELIVER`

2. **Get Current Order** - `GET /api/order/current`
   - Get currently assigned order
   - Authentication: `ROLE_DELIVER`

3. **Accept Order** - `POST /api/orders/{id}/accept`
   - Accept an available order
   - Authentication: `ROLE_USER`

4. **Update Order Status** - `PUT /api/orders/{id}/status`
   - Update order delivery status
   - Authentication: `ROLE_USER`
   - Request body: `{ "status": "processing", "latitude": -18.8792, "longitude": 47.5079, "notes": "On the way" }`

5. **Scan Store QR Code** - `POST /api/orders/{id}/scan-store-qr`
   - Scan QR code at store for pickup
   - Authentication: `ROLE_DELIVER`
   - Request body: `{ "qrCode": "ORDER-ORD-2025-001-ABC123XYZ" }`

6. **Validate Customer QR Code** - `POST /api/orders/{id}/validate-qr`
   - Validate customer QR code for delivery
   - Authentication: `ROLE_DELIVER`
   - Request body: `{ "qrCode": "ORDER-ORD-2025-001-ABC123XYZ", "latitude": -18.8792, "longitude": 47.5079 }`

7. **Toggle Availability** - `PUT /api/availability`
   - Toggle online/offline status
   - Authentication: `ROLE_USER`

---

## Request Parameters

### Common Parameter Types

| Type | Description | Example |
|------|-------------|---------|
| `string` | Text value | `"John Doe"` |
| `integer` | Whole number | `123` |
| `float` | Decimal number | `-18.8792` |
| `boolean` | True/false value | `true` or `false` |
| `email` | Valid email address | `"user@example.com"` |
| `file` | File upload (multipart) | Binary file data |

### Validation Rules

- **Email**: Must be valid email format
- **Password**: Minimum 8 characters
- **Phone**: String format (recommended: `+261340000000`)
- **File Uploads**: 
  - Max size: 10MB
  - Allowed types: PDF, JPG, PNG, WEBP
- **Vehicle Type**: Must be one of: `bike`, `motorcycle`, `car`, `van`

### Authentication Header

All authenticated endpoints require:
```
Authorization: Bearer {JWT_TOKEN}
```

---

## Response Format

### Success Response Structure

All successful responses follow this structure:

```json
{
  "token": "jwt-token-string",  // Only for auth/register endpoints
  "refresh_token": "refresh-token-string",  // Only for auth endpoint
  "user": { ... },  // User object (for auth/register endpoints)
  "id": 123,  // Resource ID (for other endpoints)
  // ... other fields
}
```

### Error Response Structure

All error responses follow this structure:

```json
{
  "type": "https://tools.ietf.org/html/rfc2616#section-10",
  "title": "An error occurred",
  "detail": "Specific error message",
  "violations": [  // Only for validation errors
    {
      "propertyPath": "fieldName",
      "message": "Error message for this field"
    }
  ]
}
```

### HTTP Status Codes

| Code | Meaning | Usage |
|------|---------|-------|
| `200 OK` | Success | Successful GET, PUT requests |
| `201 Created` | Created | Successful POST requests (registration) |
| `400 Bad Request` | Validation error | Invalid request data |
| `401 Unauthorized` | Authentication required | Missing or invalid JWT token |
| `403 Forbidden` | Access denied | Insufficient permissions or inactive account |
| `404 Not Found` | Resource not found | Invalid resource ID |
| `409 Conflict` | Resource conflict | Email already exists, order already assigned |

### User Type Values

The `userType` field can be one of:
- `customer`: Regular customer user
- `delivery`: Delivery person
- `store`: Store owner
- `admin`: Administrator

### Role Values

User roles can include:
- `ROLE_USER`: Base user role
- `ROLE_DELIVER`: Delivery person role
- `ROLE_STORE`: Store owner role
- `ROLE_ADMIN`: Administrator role

---

## Notes

1. **JWT Token Expiration**: Access tokens expire after 1 hour. Use refresh tokens to get new access tokens.

2. **Refresh Token**: Use `POST /api/token/refresh` with `{ "refresh_token": "..." }` to get a new access token.

3. **Delivery Account Activation**: Delivery accounts are created inactive. Admin must activate them before login is allowed.

4. **File Uploads**: Use `multipart/form-data` for file uploads. File size limit is 10MB.

5. **Timestamps**: All timestamps are in ISO 8601 format with timezone (e.g., `2025-01-15T10:00:00+00:00`).

6. **Currency**: All monetary values are strings with 2 decimal places (e.g., `"125000.00"`).

7. **Coordinates**: Latitude and longitude are decimal numbers (e.g., `-18.8792`, `47.5079`).

