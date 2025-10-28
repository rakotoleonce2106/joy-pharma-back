# 📱 Mobile Store App - Complete API Documentation

> **Version:** 2.0  
> **Last Updated:** October 28, 2025  
> **Base URL:** `https://your-domain.com/api`

---

## 📋 Table of Contents

1. [Authentication & Registration](#1-authentication--registration)
2. [User Profile Management](#2-user-profile-management)
3. [Store Management](#3-store-management)
4. [Product Management](#4-product-management)
5. [Store Inventory (Store Products)](#5-store-inventory-store-products)
6. [Order Management](#6-order-management)
7. [Order Item Actions (Accept/Refuse/Suggest)](#7-order-item-actions-acceptrefusesuggest)
8. [Store Availability & Business Hours](#8-store-availability--business-hours)
9. [Categories & Brands](#9-categories--brands)
10. [Notifications](#10-notifications)
11. [Support & Emergency](#11-support--emergency)
12. [Error Handling](#12-error-handling)

---

## 🔑 Authentication

All authenticated endpoints require a JWT token in the Authorization header:

```http
Authorization: Bearer {your_jwt_token}
```

### Token Response Format
```json
{
  "token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
  "refresh_token": "def50200..."
}
```

---

## 1. Authentication & Registration

### 1.1 Register as Customer
**Endpoint:** `POST /register`  
**Authentication:** Public  
**Description:** Register a new customer account

**Request Body:**
```json
{
  "email": "customer@example.com",
  "password": "SecurePass123!",
  "firstName": "John",
  "lastName": "Doe",
  "phone": "+261234567890"
}
```

**Validation:**
- `email`: Required, valid email, unique
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
    "phone": "+261234567890",
    "roles": ["ROLE_USER"],
    "userType": "customer",
    "isActive": true
  }
}
```

---

### 1.2 Register as Store Owner
**Endpoint:** `POST /register/store`  
**Authentication:** Public  
**Description:** Register a new store owner account with store information

**Request Body:**
```json
{
  "email": "store@example.com",
  "password": "SecurePass123!",
  "firstName": "Sarah",
  "lastName": "Shop",
  "phone": "+261234567891",
  "storeName": "Joy Pharmacy Downtown",
  "storeAddress": "123 Main St, Antananarivo",
  "storePhone": "+261234567890",
  "storeEmail": "contact@joypharmacy.com",
  "storeDescription": "Your trusted neighborhood pharmacy",
  "storeCity": "Antananarivo",
  "storeLatitude": -18.8792,
  "storeLongitude": 47.5079
}
```

**Validation:**
- `email`: Required, valid email, unique
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
    "id": 2,
    "email": "store@example.com",
    "firstName": "Sarah",
    "lastName": "Shop",
    "phone": "+261234567891",
    "roles": ["ROLE_USER", "ROLE_STORE"],
    "userType": "store",
    "isActive": true,
    "store": {
      "id": 1,
      "name": "Joy Pharmacy Downtown",
      "description": "Your trusted neighborhood pharmacy",
      "address": "123 Main St, Antananarivo",
      "city": "Antananarivo",
      "phone": "+261234567890",
      "email": "contact@joypharmacy.com",
      "latitude": -18.8792,
      "longitude": 47.5079
    }
  }
}
```

---

### 1.3 Login
**Endpoint:** `POST /auth`  
**Authentication:** Public  
**Description:** Login with email and password

**Request Body:**
```json
{
  "email": "store@example.com",
  "password": "SecurePass123!"
}
```

**Response (200 OK):**
```json
{
  "token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
  "refresh_token": "def50200..."
}
```

**Error Response (401 Unauthorized):**
```json
{
  "code": 401,
  "message": "Invalid credentials."
}
```

---

### 1.4 Social Login - Facebook
**Endpoint:** `POST /facebook_login`  
**Authentication:** Public

**Request Body:**
```json
{
  "accessToken": "facebook_access_token_here"
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

### 1.5 Social Login - Google
**Endpoint:** `POST /google_login`  
**Authentication:** Public

**Request Body:**
```json
{
  "accessToken": "google_access_token_here"
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

### 1.6 Refresh Token
**Endpoint:** `POST /token/refresh`  
**Authentication:** Public

**Request Body:**
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

### 1.7 Logout
**Endpoint:** `POST /logout`  
**Authentication:** Required (ROLE_USER)

**Response:**
```json
{
  "message": "Logged out successfully"
}
```

---

## 2. User Profile Management

### 2.1 Get Current User
**Endpoint:** `GET /me`  
**Authentication:** Required (ROLE_USER)  
**Description:** Get currently authenticated user information

**Response:**
```json
{
  "id": 2,
  "email": "store@example.com",
  "firstName": "Sarah",
  "lastName": "Shop",
  "phone": "+261234567891",
  "roles": ["ROLE_USER", "ROLE_STORE"],
  "userType": "store",
  "isActive": true,
  "avatar": "/uploads/avatars/user-2.jpg",
  "store": {
    "id": 1,
    "name": "Joy Pharmacy Downtown",
    "description": "Your trusted neighborhood pharmacy",
    "address": "123 Main St, Antananarivo",
    "city": "Antananarivo",
    "phone": "+261234567890",
    "email": "contact@joypharmacy.com",
    "latitude": -18.8792,
    "longitude": 47.5079,
    "isOpen": true
  }
}
```

---

### 2.2 Update Profile
**Endpoint:** `POST /user/update`  
**Authentication:** Required (ROLE_USER)  
**Content-Type:** `multipart/form-data`

**Form Data:**
- `firstName` (string, optional)
- `lastName` (string, optional)
- `phone` (string, optional)
- `avatar` (file, optional) - Image file (jpg, png, max 5MB)

**Response:**
```json
{
  "id": 2,
  "email": "store@example.com",
  "firstName": "Sarah",
  "lastName": "Shop",
  "phone": "+261234567891",
  "avatar": "/uploads/avatars/user-2.jpg"
}
```

---

### 2.3 Forgot Password (Send Reset Code)
**Endpoint:** `POST /password/forgot`  
**Authentication:** Public

**Request Body:**
```json
{
  "email": "store@example.com"
}
```

**Response:**
```json
{
  "success": true,
  "message": "If an account exists with this email, you will receive a password reset code."
}
```

---

### 2.4 Verify Reset Code
**Endpoint:** `POST /password/verify-code`  
**Authentication:** Public

**Request Body:**
```json
{
  "email": "store@example.com",
  "code": "123456"
}
```

**Response (200 OK):**
```json
{
  "valid": true,
  "message": "Code is valid"
}
```

**Response (400 Bad Request):**
```json
{
  "valid": false,
  "message": "Invalid or expired code"
}
```

---

### 2.5 Reset Password
**Endpoint:** `POST /password/reset`  
**Authentication:** Public

**Request Body:**
```json
{
  "email": "store@example.com",
  "code": "123456",
  "password": "NewSecurePass123!"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Password reset successfully"
}
```

---

### 2.6 Update Password (Authenticated)
**Endpoint:** `POST /user/update-password`  
**Authentication:** Required (ROLE_USER)

**Request Body:**
```json
{
  "currentPassword": "OldPassword123!",
  "newPassword": "NewSecurePass123!",
  "confirmPassword": "NewSecurePass123!"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Password updated successfully"
}
```

---

## 3. Store Management

### 3.1 Get All Stores
**Endpoint:** `GET /stores`  
**Authentication:** Required (ROLE_USER)  
**Description:** Get list of all stores

**Response:**
```json
{
  "data": [
    {
      "id": 1,
      "name": "Joy Pharmacy Downtown",
      "description": "Your trusted neighborhood pharmacy",
      "address": "123 Main St, Antananarivo",
      "city": "Antananarivo",
      "phone": "+261234567890",
      "email": "contact@joypharmacy.com",
      "latitude": -18.8792,
      "longitude": 47.5079,
      "isOpen": true,
      "businessHours": {
        "monday": { "open": "08:00", "close": "20:00", "isOpen": true },
        "tuesday": { "open": "08:00", "close": "20:00", "isOpen": true },
        "wednesday": { "open": "08:00", "close": "20:00", "isOpen": true },
        "thursday": { "open": "08:00", "close": "20:00", "isOpen": true },
        "friday": { "open": "08:00", "close": "20:00", "isOpen": true },
        "saturday": { "open": "09:00", "close": "18:00", "isOpen": true },
        "sunday": { "open": null, "close": null, "isOpen": false }
      }
    }
  ]
}
```

---

### 3.2 Get Store by ID
**Endpoint:** `GET /stores/{id}`  
**Authentication:** Required (ROLE_USER)

**Response:**
```json
{
  "id": 1,
  "name": "Joy Pharmacy Downtown",
  "description": "Your trusted neighborhood pharmacy",
  "address": "123 Main St, Antananarivo",
  "city": "Antananarivo",
  "phone": "+261234567890",
  "email": "contact@joypharmacy.com",
  "latitude": -18.8792,
  "longitude": 47.5079,
  "isOpen": true,
  "businessHours": {
    "monday": { "open": "08:00", "close": "20:00", "isOpen": true },
    "tuesday": { "open": "08:00", "close": "20:00", "isOpen": true }
  },
  "productCount": 150,
  "rating": 4.8
}
```

---

## 4. Product Management

### 4.1 Get All Products
**Endpoint:** `GET /products`  
**Authentication:** Public/Optional  
**Description:** Get all products in the catalog

**Query Parameters:**
- `page` (integer, optional, default: 1)
- `limit` (integer, optional, default: 20)
- `category` (integer, optional) - Filter by category ID
- `search` (string, optional) - Search products by name/description

**Response:**
```json
{
  "data": [
    {
      "id": 1,
      "name": "Paracetamol 500mg",
      "description": "Pain reliever and fever reducer",
      "code": "PARA-500",
      "category": {
        "id": 1,
        "name": "Medications",
        "image": "/uploads/categories/medications.jpg"
      },
      "brand": {
        "id": 1,
        "name": "PharmaCare"
      },
      "images": [
        {
          "id": 1,
          "url": "/uploads/products/paracetamol.jpg"
        }
      ],
      "isActive": true
    }
  ],
  "pagination": {
    "currentPage": 1,
    "itemsPerPage": 20,
    "totalItems": 150,
    "totalPages": 8
  }
}
```

---

### 4.2 Get Product by ID
**Endpoint:** `GET /product/{id}`  
**Authentication:** Public/Optional

**Response:**
```json
{
  "id": 1,
  "name": "Paracetamol 500mg",
  "description": "Pain reliever and fever reducer. Effective for headaches, muscle aches, arthritis, backache, toothaches, colds, and fevers.",
  "code": "PARA-500",
  "category": {
    "id": 1,
    "name": "Medications",
    "image": "/uploads/categories/medications.jpg"
  },
  "brand": {
    "id": 1,
    "name": "PharmaCare"
  },
  "images": [
    {
      "id": 1,
      "url": "/uploads/products/paracetamol.jpg"
    }
  ],
  "isActive": true,
  "specifications": {
    "dosage": "500mg",
    "form": "Tablet",
    "packaging": "Box of 20 tablets"
  }
}
```

---

### 4.3 Get Product Suggestions (Homepage)
**Endpoint:** `GET /products/suggestion`  
**Authentication:** Public/Optional  
**Description:** Get featured/suggested products for homepage

**Response:**
```json
{
  "data": [
    {
      "id": 1,
      "name": "Paracetamol 500mg",
      "description": "Pain reliever and fever reducer",
      "code": "PARA-500",
      "category": {
        "id": 1,
        "name": "Medications"
      },
      "images": [
        {
          "id": 1,
          "url": "/uploads/products/paracetamol.jpg"
        }
      ],
      "averagePrice": 5000.00,
      "storeCount": 15
    }
  ]
}
```

---

### 4.4 Add Products (Admin)
**Endpoint:** `POST /products`  
**Authentication:** Required (ROLE_ADMIN)  
**Content-Type:** `multipart/form-data`

**Form Data:**
- `name` (string, required)
- `description` (string, required)
- `code` (string, required, unique)
- `categoryId` (integer, required)
- `brandId` (integer, optional)
- `images[]` (files, optional)

**Response:**
```json
{
  "id": 50,
  "name": "New Product",
  "code": "NEW-PROD-001",
  "message": "Product created successfully"
}
```

---

## 5. Store Inventory (Store Products)

### 5.1 Get Store Products
**Endpoint:** `GET /store_products`  
**Authentication:** Required (ROLE_STORE)  
**Description:** Get all products in the authenticated store's inventory

**Query Parameters:**
- `page` (integer, optional, default: 1)
- `limit` (integer, optional, default: 50)
- `status` (boolean, optional) - Filter by active status
- `lowStock` (boolean, optional) - Show only low stock items (< 10)

**Response:**
```json
{
  "data": [
    {
      "id": 1,
      "product": {
        "id": 1,
        "name": "Paracetamol 500mg",
        "code": "PARA-500",
        "images": [
          {
            "url": "/uploads/products/paracetamol.jpg"
          }
        ]
      },
      "store": {
        "id": 1,
        "name": "Joy Pharmacy Downtown"
      },
      "unitPrice": 250.00,
      "price": 5000.00,
      "stock": 150,
      "isActive": true
    },
    {
      "id": 2,
      "product": {
        "id": 2,
        "name": "Aspirin 100mg",
        "code": "ASP-100",
        "images": [
          {
            "url": "/uploads/products/aspirin.jpg"
          }
        ]
      },
      "store": {
        "id": 1,
        "name": "Joy Pharmacy Downtown"
      },
      "unitPrice": 200.00,
      "price": 4000.00,
      "stock": 8,
      "isActive": true
    }
  ],
  "pagination": {
    "currentPage": 1,
    "itemsPerPage": 50,
    "totalItems": 120,
    "totalPages": 3
  }
}
```

---

### 5.2 Update Store Product (Stock & Pricing)
**Endpoint:** `PUT /store_products/{id}`  
**Authentication:** Required (ROLE_STORE)  
**Description:** Update store product stock and pricing

**Request Body:**
```json
{
  "unitPrice": 250.00,
  "price": 5000.00,
  "stock": 200,
  "isActive": true
}
```

**Response:**
```json
{
  "id": 1,
  "product": {
    "id": 1,
    "name": "Paracetamol 500mg"
  },
  "unitPrice": 250.00,
  "price": 5000.00,
  "stock": 200,
  "isActive": true,
  "message": "Store product updated successfully"
}
```

---

## 6. Order Management

### 6.1 Create Order
**Endpoint:** `POST /order`  
**Authentication:** Required (ROLE_USER)  
**Description:** Create a new order

**Request Body:**
```json
{
  "locationId": 5,
  "phone": "+261234567890",
  "scheduledDate": "2025-10-30T14:00:00",
  "notes": "Please call before delivery",
  "priority": "standard",
  "items": [
    {
      "productId": 1,
      "quantity": 2,
      "notes": "Brand preference: Generic OK"
    },
    {
      "productId": 5,
      "quantity": 1,
      "notes": ""
    }
  ]
}
```

**Validation:**
- `locationId`: Required, must exist
- `phone`: Required
- `scheduledDate`: Optional
- `priority`: Optional, values: `standard`, `urgent`, `express`
- `items`: Required, minimum 1 item
- `items[].productId`: Required
- `items[].quantity`: Required, minimum 1

**Response (201 Created):**
```json
{
  "id": 123,
  "reference": "ORD-2025-00123",
  "status": "pending",
  "priority": "standard",
  "totalAmount": 10000.00,
  "storeTotalAmount": 0.00,
  "scheduledDate": "2025-10-30T14:00:00+00:00",
  "notes": "Please call before delivery",
  "phone": "+261234567890",
  "location": {
    "id": 5,
    "address": "456 Oak Street, Antananarivo",
    "city": "Antananarivo",
    "latitude": -18.8792,
    "longitude": 47.5079
  },
  "owner": {
    "id": 1,
    "firstName": "John",
    "lastName": "Doe",
    "phone": "+261234567890"
  },
  "items": [
    {
      "id": 201,
      "product": {
        "id": 1,
        "name": "Paracetamol 500mg",
        "code": "PARA-500"
      },
      "quantity": 2,
      "unitPrice": 5000.00,
      "subtotal": 10000.00,
      "notes": "Brand preference: Generic OK",
      "storeStatus": "pending",
      "store": null
    }
  ],
  "createdAt": "2025-10-28T10:30:00+00:00"
}
```

---

### 6.2 Get Order by ID
**Endpoint:** `GET /order/{id}`  
**Authentication:** Required (ROLE_USER)

**Response:**
```json
{
  "id": 123,
  "reference": "ORD-2025-00123",
  "status": "processing",
  "priority": "standard",
  "totalAmount": 10000.00,
  "storeTotalAmount": 9500.00,
  "scheduledDate": "2025-10-30T14:00:00+00:00",
  "notes": "Please call before delivery",
  "phone": "+261234567890",
  "location": {
    "id": 5,
    "address": "456 Oak Street, Antananarivo",
    "city": "Antananarivo",
    "latitude": -18.8792,
    "longitude": 47.5079
  },
  "owner": {
    "id": 1,
    "firstName": "John",
    "lastName": "Doe",
    "phone": "+261234567890"
  },
  "items": [
    {
      "id": 201,
      "product": {
        "id": 1,
        "name": "Paracetamol 500mg",
        "code": "PARA-500",
        "images": [
          {
            "url": "/uploads/products/paracetamol.jpg"
          }
        ]
      },
      "quantity": 2,
      "unitPrice": 5000.00,
      "subtotal": 10000.00,
      "notes": "Brand preference: Generic OK",
      "storeStatus": "accepted",
      "storePrice": 4750.00,
      "storeTotalAmount": 9500.00,
      "storeNotes": "Available in stock",
      "storeActionAt": "2025-10-28T11:00:00+00:00",
      "store": {
        "id": 1,
        "name": "Joy Pharmacy Downtown",
        "phone": "+261234567890"
      }
    }
  ],
  "createdAt": "2025-10-28T10:30:00+00:00",
  "updatedAt": "2025-10-28T11:00:00+00:00"
}
```

---

### 6.3 Get All Orders (for Store)
**Endpoint:** `GET /orders`  
**Authentication:** Required (ROLE_STORE)  
**Description:** Get all orders assigned to the authenticated store

**Query Parameters:**
- `page` (integer, optional, default: 1)
- `limit` (integer, optional, default: 20)
- `status` (string, optional) - Filter by order status
  - Values: `pending`, `confirmed`, `processing`, `shipped`, `delivered`, `cancelled`
- `storeStatus` (string, optional) - Filter by store item status
  - Values: `pending`, `accepted`, `refused`, `suggested`, `approved`

**Response:**
```json
{
  "data": [
    {
      "id": 123,
      "reference": "ORD-2025-00123",
      "status": "pending",
      "priority": "standard",
      "totalAmount": 10000.00,
      "scheduledDate": "2025-10-30T14:00:00+00:00",
      "phone": "+261234567890",
      "location": {
        "address": "456 Oak Street, Antananarivo",
        "city": "Antananarivo"
      },
      "owner": {
        "firstName": "John",
        "lastName": "Doe"
      },
      "itemsCount": 2,
      "pendingItemsCount": 1,
      "acceptedItemsCount": 1,
      "createdAt": "2025-10-28T10:30:00+00:00"
    }
  ],
  "pagination": {
    "currentPage": 1,
    "itemsPerPage": 20,
    "totalItems": 45,
    "totalPages": 3
  }
}
```

---

## 7. Order Item Actions (Accept/Refuse/Suggest)

### 7.1 Accept Order Item
**Endpoint:** `POST /store/order-item/accept`  
**Authentication:** Required (ROLE_STORE)  
**Description:** Accept an order item. Price is automatically fetched from store inventory.

**Request Body:**
```json
{
  "orderItemId": 201,
  "notes": "Item available in stock, ready for pickup"
}
```

**Business Rules:**
- Product MUST be in store's inventory (StoreProduct table)
- Price is automatically fetched from StoreProduct
- Only store owner of the assigned store can accept

**Response (200 OK):**
```json
{
  "id": 201,
  "orderItem": {
    "id": 201,
    "product": {
      "id": 1,
      "name": "Paracetamol 500mg"
    },
    "quantity": 2
  },
  "storeStatus": "accepted",
  "storePrice": 4750.00,
  "storeTotalAmount": 9500.00,
  "storeNotes": "Item available in stock, ready for pickup",
  "storeActionAt": "2025-10-28T11:00:00+00:00",
  "message": "Order item accepted successfully"
}
```

**Error Response (400 Bad Request):**
```json
{
  "error": "This product is not available in your store. You can suggest an alternative product instead.",
  "code": 400
}
```

---

### 7.2 Refuse Order Item
**Endpoint:** `POST /store/order-item/refuse`  
**Authentication:** Required (ROLE_STORE)  
**Description:** Refuse an order item with reason

**Request Body:**
```json
{
  "orderItemId": 201,
  "reason": "Product temporarily out of stock. Expected restock in 3 days."
}
```

**Response (200 OK):**
```json
{
  "id": 201,
  "orderItem": {
    "id": 201,
    "product": {
      "id": 1,
      "name": "Paracetamol 500mg"
    },
    "quantity": 2
  },
  "storeStatus": "refused",
  "storeNotes": "Product temporarily out of stock. Expected restock in 3 days.",
  "storeActionAt": "2025-10-28T11:00:00+00:00",
  "message": "Order item refused successfully"
}
```

---

### 7.3 Suggest Alternative Product
**Endpoint:** `POST /store/order-item/suggest`  
**Authentication:** Required (ROLE_STORE)  
**Description:** Suggest an alternative product. Requires admin approval.

**Request Body:**
```json
{
  "orderItemId": 201,
  "suggestedProductId": 15,
  "suggestion": "We have a generic version of this medication that works equally well and costs less.",
  "notes": "Generic brand, same active ingredient"
}
```

**Business Rules:**
- Suggested product MUST be in store's inventory
- Price automatically fetched from StoreProduct
- Requires admin approval before customer sees it

**Response (200 OK):**
```json
{
  "id": 201,
  "orderItem": {
    "id": 201,
    "product": {
      "id": 1,
      "name": "Paracetamol 500mg"
    },
    "quantity": 2
  },
  "storeStatus": "suggested",
  "suggestedProduct": {
    "id": 15,
    "name": "Generic Paracetamol 500mg",
    "code": "GEN-PARA-500"
  },
  "storeSuggestion": "We have a generic version of this medication that works equally well and costs less.",
  "storePrice": 3500.00,
  "storeTotalAmount": 7000.00,
  "storeNotes": "Generic brand, same active ingredient",
  "storeActionAt": "2025-10-28T11:00:00+00:00",
  "message": "Alternative suggestion submitted successfully. Waiting for admin approval."
}
```

**Error Response (400 Bad Request):**
```json
{
  "error": "The suggested product is not available in your store inventory",
  "code": 400
}
```

---

### 7.4 Get Store's Pending Order Items
**Endpoint:** `GET /store/order-items/pending`  
**Authentication:** Required (ROLE_STORE)  
**Description:** Get all pending order items for the authenticated store

**Query Parameters:**
- `page` (integer, optional, default: 1)
- `limit` (integer, optional, default: 50)

**Response:**
```json
{
  "data": [
    {
      "id": 201,
      "order": {
        "id": 123,
        "reference": "ORD-2025-00123",
        "status": "pending",
        "scheduledDate": "2025-10-30T14:00:00+00:00",
        "phone": "+261234567890",
        "owner": {
          "firstName": "John",
          "lastName": "Doe"
        }
      },
      "product": {
        "id": 1,
        "name": "Paracetamol 500mg",
        "code": "PARA-500",
        "images": [
          {
            "url": "/uploads/products/paracetamol.jpg"
          }
        ]
      },
      "quantity": 2,
      "unitPrice": 5000.00,
      "subtotal": 10000.00,
      "notes": "Brand preference: Generic OK",
      "storeStatus": "pending",
      "createdAt": "2025-10-28T10:30:00+00:00"
    }
  ],
  "pagination": {
    "currentPage": 1,
    "itemsPerPage": 50,
    "totalItems": 15,
    "totalPages": 1
  }
}
```

---

## 8. Store Availability & Business Hours

### 8.1 Get Store Business Hours
**Endpoint:** `GET /store/business-hours`  
**Authentication:** Required (ROLE_STORE)  
**Description:** Get business hours for the authenticated store

**Response:**
```json
{
  "storeId": 1,
  "storeName": "Joy Pharmacy Downtown",
  "businessHours": [
    {
      "id": 1,
      "dayOfWeek": 1,
      "dayName": "Monday",
      "openTime": "08:00:00",
      "closeTime": "20:00:00",
      "isOpen": true
    },
    {
      "id": 2,
      "dayOfWeek": 2,
      "dayName": "Tuesday",
      "openTime": "08:00:00",
      "closeTime": "20:00:00",
      "isOpen": true
    },
    {
      "id": 3,
      "dayOfWeek": 0,
      "dayName": "Sunday",
      "openTime": null,
      "closeTime": null,
      "isOpen": false
    }
  ]
}
```

**Day of Week Values:**
- `0` = Sunday
- `1` = Monday
- `2` = Tuesday
- `3` = Wednesday
- `4` = Thursday
- `5` = Friday
- `6` = Saturday

---

### 8.2 Update Store Business Hours
**Endpoint:** `PUT /store/business-hours`  
**Authentication:** Required (ROLE_STORE)

**Request Body:**
```json
{
  "businessHours": [
    {
      "dayOfWeek": 1,
      "openTime": "08:00",
      "closeTime": "20:00",
      "isOpen": true
    },
    {
      "dayOfWeek": 2,
      "openTime": "08:00",
      "closeTime": "20:00",
      "isOpen": true
    },
    {
      "dayOfWeek": 0,
      "openTime": null,
      "closeTime": null,
      "isOpen": false
    }
  ]
}
```

**Response:**
```json
{
  "message": "Business hours updated successfully",
  "businessHours": [
    {
      "id": 1,
      "dayOfWeek": 1,
      "dayName": "Monday",
      "openTime": "08:00:00",
      "closeTime": "20:00:00",
      "isOpen": true
    }
  ]
}
```

---

### 8.3 Toggle Store Open/Close
**Endpoint:** `PUT /store/toggle-status`  
**Authentication:** Required (ROLE_STORE)  
**Description:** Temporarily open or close the store

**Request Body:**
```json
{
  "isOpen": false,
  "reason": "Closed for inventory"
}
```

**Response:**
```json
{
  "storeId": 1,
  "storeName": "Joy Pharmacy Downtown",
  "isOpen": false,
  "message": "Store is now closed"
}
```

---

## 9. Categories & Brands

### 9.1 Get All Categories
**Endpoint:** `GET /category`  
**Authentication:** Public/Optional

**Response:**
```json
{
  "data": [
    {
      "id": 1,
      "name": "Medications",
      "description": "Prescription and over-the-counter medications",
      "image": {
        "url": "/uploads/categories/medications.jpg"
      },
      "productCount": 250
    },
    {
      "id": 2,
      "name": "Vitamins & Supplements",
      "description": "Health supplements and vitamins",
      "image": {
        "url": "/uploads/categories/vitamins.jpg"
      },
      "productCount": 120
    }
  ]
}
```

---

### 9.2 Get Category by ID
**Endpoint:** `GET /category/{id}`  
**Authentication:** Public/Optional

**Response:**
```json
{
  "id": 1,
  "name": "Medications",
  "description": "Prescription and over-the-counter medications",
  "image": {
    "url": "/uploads/categories/medications.jpg"
  },
  "productCount": 250
}
```

---

## 10. Notifications

### 10.1 Get Notifications
**Endpoint:** `GET /notifications`  
**Authentication:** Required (ROLE_USER)

**Query Parameters:**
- `page` (integer, optional, default: 1)
- `limit` (integer, optional, default: 20)

**Response:**
```json
{
  "data": [
    {
      "id": 456,
      "type": "new_order",
      "title": "New Order Received",
      "message": "You have received a new order ORD-2025-00123",
      "data": {
        "orderId": 123,
        "orderReference": "ORD-2025-00123"
      },
      "isRead": false,
      "createdAt": "2025-10-28T10:30:00+00:00"
    },
    {
      "id": 455,
      "type": "order_accepted",
      "title": "Order Item Accepted",
      "message": "Your order item has been accepted by Joy Pharmacy Downtown",
      "data": {
        "orderItemId": 201,
        "storeName": "Joy Pharmacy Downtown"
      },
      "isRead": true,
      "createdAt": "2025-10-28T09:00:00+00:00"
    }
  ],
  "pagination": {
    "currentPage": 1,
    "itemsPerPage": 20,
    "totalItems": 45,
    "totalPages": 3
  }
}
```

**Notification Types:**
- `new_order` - New order received
- `order_accepted` - Order item accepted by store
- `order_refused` - Order item refused by store
- `order_suggestion` - Store suggested alternative
- `order_confirmed` - Order confirmed
- `order_shipped` - Order out for delivery
- `order_delivered` - Order delivered
- `order_cancelled` - Order cancelled
- `low_stock` - Product low in stock
- `system` - System notification

---

### 10.2 Get Unread Count
**Endpoint:** `GET /notifications/unread-count`  
**Authentication:** Required (ROLE_USER)

**Response:**
```json
{
  "unreadCount": 5
}
```

---

### 10.3 Mark Notification as Read
**Endpoint:** `PUT /notifications/{id}/read`  
**Authentication:** Required (ROLE_USER)

**Response:**
```json
{
  "id": 456,
  "isRead": true,
  "message": "Notification marked as read"
}
```

---

### 10.4 Mark All Notifications as Read
**Endpoint:** `PUT /notifications/read-all`  
**Authentication:** Required (ROLE_USER)

**Response:**
```json
{
  "message": "All notifications marked as read",
  "count": 5
}
```

---

## 11. Support & Emergency

### 11.1 Send Emergency SOS
**Endpoint:** `POST /emergency/sos`  
**Authentication:** Required (ROLE_USER)

**Request Body:**
```json
{
  "latitude": -18.8792,
  "longitude": 47.5079,
  "notes": "Need immediate help",
  "orderId": 123
}
```

**Response:**
```json
{
  "id": 789,
  "status": "sent",
  "latitude": -18.8792,
  "longitude": 47.5079,
  "sentAt": "2025-10-28T14:30:00+00:00",
  "message": "Emergency SOS sent successfully. Help is on the way.",
  "emergencyContacts": [
    {
      "name": "Emergency Services",
      "phone": "117"
    },
    {
      "name": "Company Support",
      "phone": "+261234567890"
    }
  ]
}
```

---

### 11.2 Contact Support
**Endpoint:** `POST /support/contact`  
**Authentication:** Required (ROLE_USER)

**Request Body:**
```json
{
  "subject": "Issue with order",
  "message": "My order hasn't arrived and I can't reach the delivery person",
  "priority": "high",
  "orderId": 123
}
```

**Validation:**
- `subject`: Required, 3-255 characters
- `message`: Required, minimum 10 characters
- `priority`: Optional, values: `low`, `normal`, `high`, `urgent`

**Response:**
```json
{
  "id": 567,
  "ticketNumber": "TICKET-2025-567",
  "subject": "Issue with order",
  "message": "My order hasn't arrived and I can't reach the delivery person",
  "priority": "high",
  "status": "open",
  "createdAt": "2025-10-28T14:30:00+00:00",
  "message": "Support ticket created successfully. We'll respond within 24 hours."
}
```

---

## 12. Error Handling

### Standard Error Response Format

All errors follow this structure:

```json
{
  "error": "Error title/message",
  "code": 400,
  "message": "Detailed error message",
  "violations": [
    {
      "field": "email",
      "message": "This value should be a valid email."
    }
  ]
}
```

---

### HTTP Status Codes

| Code | Meaning | Description |
|------|---------|-------------|
| 200 | OK | Request successful |
| 201 | Created | Resource created successfully |
| 400 | Bad Request | Invalid request data or validation error |
| 401 | Unauthorized | Authentication required or invalid token |
| 403 | Forbidden | User doesn't have permission |
| 404 | Not Found | Resource not found |
| 409 | Conflict | Resource conflict (e.g., email already exists) |
| 422 | Unprocessable Entity | Validation failed |
| 429 | Too Many Requests | Rate limit exceeded |
| 500 | Internal Server Error | Server error |

---

### Common Error Scenarios

#### 400 Bad Request - Validation Error
```json
{
  "error": "Validation failed",
  "code": 400,
  "violations": [
    {
      "field": "email",
      "message": "This value should be a valid email."
    },
    {
      "field": "password",
      "message": "This value is too short. It should have 8 characters or more."
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

#### 403 Forbidden
```json
{
  "error": "Access denied",
  "code": 403,
  "message": "You don't have permission to access this resource"
}
```

#### 404 Not Found
```json
{
  "error": "Resource not found",
  "code": 404,
  "message": "Order with ID 123 not found"
}
```

#### 409 Conflict
```json
{
  "error": "Email already exists",
  "code": 409,
  "message": "A user with this email already exists in the system"
}
```

---

## 📊 Complete API Summary

### By Feature

| Feature | Endpoints | Authentication |
|---------|-----------|----------------|
| **Authentication** | 7 | Public + ROLE_USER |
| **Profile** | 4 | ROLE_USER |
| **Store Management** | 2 | ROLE_USER |
| **Products** | 4 | Public/ROLE_ADMIN |
| **Store Inventory** | 2 | ROLE_STORE |
| **Orders** | 3 | ROLE_USER/ROLE_STORE |
| **Order Items** | 4 | ROLE_STORE |
| **Business Hours** | 3 | ROLE_STORE |
| **Categories** | 2 | Public |
| **Notifications** | 4 | ROLE_USER |
| **Support** | 2 | ROLE_USER |

**Total Endpoints:** 37

---

## 🔐 Security Best Practices

### For Mobile App Developers

1. **Store JWT Securely**
   - Use secure storage (Keychain on iOS, Keystore on Android)
   - Never store in AsyncStorage/SharedPreferences

2. **Token Refresh**
   - Implement automatic token refresh
   - Handle 401 errors globally

3. **HTTPS Only**
   - Always use HTTPS in production
   - Pin SSL certificates for extra security

4. **Input Validation**
   - Validate on client-side before sending
   - Don't trust client-side validation alone

5. **Error Handling**
   - Handle all HTTP status codes
   - Show user-friendly error messages
   - Log errors for debugging

---

## 📝 Example: Complete Order Flow

### 1. Customer Creates Order
```http
POST /api/order
Authorization: Bearer {customer_token}
```

### 2. Store Receives Order
```http
GET /api/store/order-items/pending
Authorization: Bearer {store_token}
```

### 3. Store Reviews Item
```http
GET /api/order/123
Authorization: Bearer {store_token}
```

### 4. Store Accepts Item
```http
POST /api/store/order-item/accept
Authorization: Bearer {store_token}
{
  "orderItemId": 201,
  "notes": "Available in stock"
}
```

### 5. Customer Gets Notification
```http
GET /api/notifications
Authorization: Bearer {customer_token}
```

### 6. Customer Checks Order Status
```http
GET /api/order/123
Authorization: Bearer {customer_token}
```

---

## 🚀 Quick Start for Frontend Developers

### React Native Example

```javascript
// API Client Setup
import axios from 'axios';

const API_BASE_URL = 'https://your-domain.com/api';

const apiClient = axios.create({
  baseURL: API_BASE_URL,
  headers: {
    'Content-Type': 'application/json',
  },
});

// Add token to requests
apiClient.interceptors.request.use((config) => {
  const token = getStoredToken(); // Your token storage function
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});

// Handle token refresh on 401
apiClient.interceptors.response.use(
  (response) => response,
  async (error) => {
    if (error.response?.status === 401) {
      const newToken = await refreshToken();
      if (newToken) {
        error.config.headers.Authorization = `Bearer ${newToken}`;
        return axios.request(error.config);
      }
    }
    return Promise.reject(error);
  }
);

// Example: Login
const login = async (email, password) => {
  try {
    const response = await apiClient.post('/auth', {
      email,
      password,
    });
    const { token, refresh_token } = response.data;
    await storeTokens(token, refresh_token);
    return response.data;
  } catch (error) {
    console.error('Login failed:', error.response?.data);
    throw error;
  }
};

// Example: Get Store Products
const getStoreProducts = async (page = 1) => {
  try {
    const response = await apiClient.get('/store_products', {
      params: { page, limit: 50 },
    });
    return response.data;
  } catch (error) {
    console.error('Failed to fetch products:', error.response?.data);
    throw error;
  }
};

// Example: Accept Order Item
const acceptOrderItem = async (orderItemId, notes) => {
  try {
    const response = await apiClient.post('/store/order-item/accept', {
      orderItemId,
      notes,
    });
    return response.data;
  } catch (error) {
    console.error('Failed to accept order:', error.response?.data);
    throw error;
  }
};
```

---

## 📱 Mobile App Features Checklist

### For Store Owners

- [ ] Register store account
- [ ] Login with email/password
- [ ] View/edit profile
- [ ] View store inventory
- [ ] Update product stock/pricing
- [ ] View pending orders
- [ ] Accept/refuse order items
- [ ] Suggest alternative products
- [ ] Manage business hours
- [ ] Toggle store open/close
- [ ] View notifications
- [ ] Contact support

### For Customers

- [ ] Register customer account
- [ ] Login with email/password/social
- [ ] View/edit profile
- [ ] Browse products
- [ ] Search products
- [ ] Filter by category
- [ ] Create orders
- [ ] View order history
- [ ] Track order status
- [ ] View notifications
- [ ] Contact support
- [ ] Emergency SOS

---

## 📞 Support

For API issues or questions:
- Email: support@joypharma.com
- Phone: +261 XX XX XXX XX
- Documentation: https://docs.joypharma.com

---

## 📚 Additional Resources

- [Postman Collection](link-to-postman-collection)
- [API Changelog](link-to-changelog)
- [Integration Examples](link-to-examples)
- [Video Tutorials](link-to-tutorials)

---

**Last Updated:** October 28, 2025  
**API Version:** 2.0  
**Documentation Version:** 1.0


