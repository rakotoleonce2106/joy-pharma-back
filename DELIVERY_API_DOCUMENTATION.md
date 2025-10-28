# Delivery API Documentation

## Base URL
All API endpoints are prefixed with `/api`

## Authentication
Most endpoints require JWT authentication. Include the token in the Authorization header:
```
Authorization: Bearer {your_jwt_token}
```

---

## 📋 Table of Contents
1. [Authentication APIs](#authentication-apis)
2. [Order Management APIs](#order-management-apis)
3. [Availability & Schedule APIs](#availability--schedule-apis)
4. [Location & Tracking APIs](#location--tracking-apis)
5. [Statistics & Earnings APIs](#statistics--earnings-apis)
6. [Profile Management APIs](#profile-management-apis)
7. [Notification APIs](#notification-apis)
8. [Emergency & Support APIs](#emergency--support-apis)
9. [Store APIs](#store-apis)

---

## Authentication APIs

### 1. Login
**Endpoint:** `POST /api/auth`  
**Authentication:** Public  
**Description:** Login with email and password to get JWT token

**Request Body:**
```json
{
  "email": "string",
  "password": "string"
}
```

**Response (Success):**
```json
{
  "token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
  "refresh_token": "def50200..."
}
```

---

### 2. Register
**Endpoint:** `POST /api/register`  
**Authentication:** Public  
**Content-Type:** `multipart/form-data`

**Request Body:**
```json
{
  "email": "string",
  "password": "string",
  "firstName": "string",
  "lastName": "string",
  "phone": "string",
  "roles": ["ROLE_USER"]
}
```

**Response:**
```json
{
  "id": 1,
  "email": "user@example.com",
  "firstName": "John",
  "lastName": "Doe",
  "phone": "+1234567890",
  "roles": ["ROLE_USER"]
}
```

---

### 3. Refresh Token
**Endpoint:** `POST /api/token/refresh`  
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

### 4. Forgot Password
**Endpoint:** `POST /api/password/forgot`  
**Authentication:** Public

**Request Body:**
```json
{
  "email": "user@example.com"
}
```

**Response:**
```json
{
  "message": "Reset code sent to email",
  "success": true
}
```

---

### 5. Verify Reset Code
**Endpoint:** `POST /api/password/verify-code`  
**Authentication:** Public

**Request Body:**
```json
{
  "email": "user@example.com",
  "code": "123456"
}
```

**Response:**
```json
{
  "valid": true,
  "message": "Code is valid"
}
```

---

### 6. Reset Password
**Endpoint:** `POST /api/password/reset`  
**Authentication:** Public

**Request Body:**
```json
{
  "email": "user@example.com",
  "code": "123456",
  "password": "newPassword123"
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

### 7. Update Password (Authenticated)
**Endpoint:** `POST /api/user/update-password`  
**Authentication:** Required (ROLE_USER)

**Request Body:**
```json
{
  "currentPassword": "oldPassword123",
  "newPassword": "newPassword123",
  "confirmPassword": "newPassword123"
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

### 8. Get Current User
**Endpoint:** `GET /api/me`  
**Authentication:** Required (ROLE_USER)

**Response:**
```json
{
  "id": 1,
  "email": "user@example.com",
  "firstName": "John",
  "lastName": "Doe",
  "phone": "+1234567890",
  "roles": ["ROLE_USER"],
  "vehicleType": "motorcycle",
  "vehiclePlate": "ABC123",
  "isOnline": true,
  "rating": 4.8,
  "totalDeliveries": 150
}
```

---

### 9. Logout
**Endpoint:** `POST /api/logout`  
**Authentication:** Required (ROLE_USER)

**Response:**
```json
{
  "message": "Logged out successfully"
}
```

---

### 10. Social Login - Facebook
**Endpoint:** `POST /api/facebook_login`  
**Authentication:** Public

**Request Body:**
```json
{
  "accessToken": "facebook_access_token"
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

### 11. Social Login - Google
**Endpoint:** `POST /api/google_login`  
**Authentication:** Public

**Request Body:**
```json
{
  "accessToken": "google_access_token"
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

## Order Management APIs

### 1. Get Available Orders
**Endpoint:** `GET /api/orders/available`  
**Authentication:** Required (ROLE_USER)  
**Description:** Get list of orders available for delivery persons to accept

**Query Parameters:**
- `page` (integer, default: 1)
- `limit` (integer, default: 10)

**Response:**
```json
{
  "data": [
    {
      "id": 123,
      "reference": "ORD-2025-001",
      "status": "pending",
      "priority": "urgent",
      "totalAmount": 125.50,
      "scheduledDate": "2025-10-27T14:30:00+00:00",
      "notes": "Please deliver to front desk",
      "phone": "+1234567890",
      "location": {
        "id": 1,
        "address": "123 Main St",
        "city": "New York",
        "latitude": 40.7128,
        "longitude": -74.0060
      },
      "owner": {
        "id": 45,
        "firstName": "Jane",
        "lastName": "Smith",
        "phone": "+1234567890"
      },
      "items": [
        {
          "id": 1,
          "productName": "Product A",
          "quantity": 2,
          "unitPrice": 50.00,
          "subtotal": 100.00
        }
      ],
      "estimatedDistance": 5.2,
      "estimatedTime": "20 mins"
    }
  ],
  "pagination": {
    "currentPage": 1,
    "itemsPerPage": 10,
    "totalItems": 25,
    "totalPages": 3
  }
}
```

---

### 2. Get Current Order
**Endpoint:** `GET /api/orders/current`  
**Authentication:** Required (ROLE_USER)  
**Description:** Get the current order being delivered by the user

**Response:**
```json
{
  "id": 123,
  "reference": "ORD-2025-001",
  "status": "processing",
  "priority": "urgent",
  "totalAmount": 125.50,
  "scheduledDate": "2025-10-27T14:30:00+00:00",
  "acceptedAt": "2025-10-27T14:00:00+00:00",
  "notes": "Please deliver to front desk",
  "phone": "+1234567890",
  "qrCode": "QR123456789",
  "location": {
    "id": 1,
    "address": "123 Main St",
    "city": "New York",
    "latitude": 40.7128,
    "longitude": -74.0060
  },
  "owner": {
    "id": 45,
    "firstName": "Jane",
    "lastName": "Smith",
    "phone": "+1234567890"
  },
  "items": [
    {
      "id": 1,
      "productName": "Product A",
      "quantity": 2,
      "unitPrice": 50.00,
      "subtotal": 100.00
    }
  ]
}
```

**Response (No active order):**
```json
{
  "message": "No active order",
  "data": null
}
```

---

### 3. Get Order History
**Endpoint:** `GET /api/orders/history`  
**Authentication:** Required (ROLE_USER)  
**Description:** Get delivery history for current user

**Query Parameters:**
- `page` (integer, default: 1)
- `limit` (integer, default: 20)
- `status` (string, optional: pending, confirmed, processing, shipped, delivered, cancelled)

**Response:**
```json
{
  "data": [
    {
      "id": 122,
      "reference": "ORD-2025-002",
      "status": "delivered",
      "priority": "standard",
      "totalAmount": 89.99,
      "scheduledDate": "2025-10-26T10:00:00+00:00",
      "acceptedAt": "2025-10-26T09:30:00+00:00",
      "deliveredAt": "2025-10-26T10:15:00+00:00",
      "location": {
        "address": "456 Oak Ave",
        "city": "Brooklyn"
      },
      "owner": {
        "firstName": "John",
        "lastName": "Doe"
      },
      "rating": 5,
      "earnings": 15.00
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

### 4. Accept Order
**Endpoint:** `POST /api/orders/{id}/accept`  
**Authentication:** Required (ROLE_USER)  
**Description:** Accept an available order and assign it to current delivery person

**Response:**
```json
{
  "id": 123,
  "reference": "ORD-2025-001",
  "status": "processing",
  "acceptedAt": "2025-10-27T14:00:00+00:00",
  "message": "Order accepted successfully",
  "location": {
    "address": "123 Main St",
    "city": "New York",
    "latitude": 40.7128,
    "longitude": -74.0060
  },
  "owner": {
    "firstName": "Jane",
    "lastName": "Smith",
    "phone": "+1234567890"
  }
}
```

**Error Response:**
```json
{
  "error": "Order already accepted by another delivery person",
  "code": 409
}
```

---

### 5. Reject Order
**Endpoint:** `POST /api/orders/{id}/reject`  
**Authentication:** Required (ROLE_USER)  
**Description:** Reject an order (removes from available list)

**Response:**
```json
{
  "message": "Order rejected successfully"
}
```

---

### 6. Update Order Status
**Endpoint:** `PUT /api/orders/{id}/status`  
**Authentication:** Required (ROLE_USER)  
**Description:** Update delivery order status

**Request Body:**
```json
{
  "status": "shipped",
  "latitude": 40.7128,
  "longitude": -74.0060,
  "notes": "On the way"
}
```

**Valid Status Values:**
- `pending` - Order is pending
- `confirmed` - Order is confirmed
- `processing` - Order is being processed
- `shipped` - Order is out for delivery
- `delivered` - Order has been delivered
- `cancelled` - Order was cancelled

**Response:**
```json
{
  "id": 123,
  "reference": "ORD-2025-001",
  "status": "shipped",
  "updatedAt": "2025-10-27T14:15:00+00:00",
  "message": "Order status updated successfully"
}
```

---

### 7. Validate QR Code
**Endpoint:** `POST /api/orders/{id}/validate-qr`  
**Authentication:** Required (ROLE_USER)  
**Description:** Validate order QR code and mark as delivered

**Request Body:**
```json
{
  "qrCode": "QR123456789"
}
```

**Response (Success):**
```json
{
  "id": 123,
  "reference": "ORD-2025-001",
  "status": "delivered",
  "deliveredAt": "2025-10-27T14:30:00+00:00",
  "earnings": 15.00,
  "message": "Order delivered successfully"
}
```

**Response (Invalid QR):**
```json
{
  "error": "Invalid QR code",
  "code": 400
}
```

---

### 8. Submit Rating
**Endpoint:** `POST /api/orders/{id}/rating`  
**Authentication:** Required (ROLE_USER)  
**Description:** Submit rating for delivered order

**Request Body:**
```json
{
  "rating": 5,
  "comment": "Great customer, smooth delivery"
}
```

**Validation:**
- `rating`: Required, integer between 1-5
- `comment`: Optional, string

**Response:**
```json
{
  "id": 123,
  "rating": 5,
  "comment": "Great customer, smooth delivery",
  "message": "Rating submitted successfully"
}
```

---

### 9. Report Issue
**Endpoint:** `POST /api/orders/{id}/report-issue`  
**Authentication:** Required (ROLE_USER)  
**Description:** Report an issue with an order

**Request Body:**
```json
{
  "type": "customer_unavailable",
  "description": "Customer not answering phone or door"
}
```

**Valid Issue Types:**
- `damaged_product` - Product is damaged
- `wrong_address` - Wrong delivery address
- `customer_unavailable` - Customer is unavailable
- `other` - Other issues

**Response:**
```json
{
  "id": 456,
  "orderId": 123,
  "type": "customer_unavailable",
  "description": "Customer not answering phone or door",
  "reportedAt": "2025-10-27T14:30:00+00:00",
  "status": "pending",
  "message": "Issue reported successfully. Support will contact you soon."
}
```

---

## Availability & Schedule APIs

### 1. Toggle Availability
**Endpoint:** `PUT /api/availability`  
**Authentication:** Required (ROLE_USER)  
**Description:** Toggle delivery person online/offline status

**Response:**
```json
{
  "isOnline": true,
  "message": "You are now online"
}
```

---

### 2. Set Online Status
**Endpoint:** `PUT /api/availability/online`  
**Authentication:** Required (ROLE_USER)  
**Description:** Set delivery person online/offline status explicitly

**Response:**
```json
{
  "isOnline": false,
  "message": "You are now offline"
}
```

---

### 3. Get Delivery Schedule
**Endpoint:** `GET /api/availability/schedule`  
**Authentication:** Required (ROLE_USER)  
**Description:** Get delivery person schedule

**Response:**
```json
{
  "data": [
    {
      "id": 1,
      "dayOfWeek": 1,
      "dayName": "Monday",
      "startTime": "09:00",
      "endTime": "17:00",
      "isActive": true
    },
    {
      "id": 2,
      "dayOfWeek": 2,
      "dayName": "Tuesday",
      "startTime": "09:00",
      "endTime": "17:00",
      "isActive": true
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

### 4. Update Delivery Schedule
**Endpoint:** `PUT /api/availability/schedule`  
**Authentication:** Required (ROLE_USER)  
**Description:** Update delivery person schedule

**Request Body:**
```json
{
  "schedules": [
    {
      "dayOfWeek": 1,
      "startTime": "09:00",
      "endTime": "17:00",
      "isActive": true
    },
    {
      "dayOfWeek": 2,
      "startTime": "10:00",
      "endTime": "18:00",
      "isActive": true
    },
    {
      "dayOfWeek": 3,
      "startTime": "09:00",
      "endTime": "17:00",
      "isActive": false
    }
  ]
}
```

**Response:**
```json
{
  "message": "Schedule updated successfully",
  "data": [
    {
      "id": 1,
      "dayOfWeek": 1,
      "dayName": "Monday",
      "startTime": "09:00",
      "endTime": "17:00",
      "isActive": true
    }
  ]
}
```

---

## Location & Tracking APIs

### 1. Update Location
**Endpoint:** `POST /api/location`  
**Authentication:** Required (ROLE_USER)  
**Description:** Update real-time location of delivery person

**Request Body:**
```json
{
  "latitude": 40.7128,
  "longitude": -74.0060,
  "accuracy": 10.5,
  "timestamp": "2025-10-27T14:30:00+00:00"
}
```

**Validation:**
- `latitude`: Required, float between -90 and 90
- `longitude`: Required, float between -180 and 180
- `accuracy`: Optional, float (in meters)
- `timestamp`: Optional, ISO 8601 datetime string

**Response:**
```json
{
  "id": 789,
  "latitude": 40.7128,
  "longitude": -74.0060,
  "accuracy": 10.5,
  "timestamp": "2025-10-27T14:30:00+00:00",
  "message": "Location updated successfully"
}
```

---

## Statistics & Earnings APIs

### 1. Get Dashboard Statistics
**Endpoint:** `GET /api/stats/dashboard`  
**Authentication:** Required (ROLE_USER)  
**Description:** Get dashboard stats for delivery person

**Query Parameters:**
- `period` (string, default: today)
  - Values: `today`, `week`, `month`, `year`

**Response:**
```json
{
  "period": "today",
  "totalDeliveries": 8,
  "totalEarnings": "120.00",
  "averageRating": 4.8,
  "isOnline": true,
  "hasActiveOrder": true,
  "lifetimeStats": {
    "totalDeliveries": 150,
    "totalEarnings": "2250.00",
    "averageRating": 4.7,
    "successRate": 98.5
  }
}
```

---

### 2. Get Earnings Statistics
**Endpoint:** `GET /api/stats/earnings`  
**Authentication:** Required (ROLE_USER)  
**Description:** Get detailed earnings history

**Query Parameters:**
- `period` (string, default: week)
  - Values: `week`, `month`, `year`

**Response:**
```json
{
  "period": "week",
  "totalEarnings": "450.00",
  "totalDeliveries": 35,
  "averagePerDelivery": "12.86",
  "earnings": [
    {
      "date": "2025-10-21",
      "deliveries": 5,
      "earnings": "75.00"
    },
    {
      "date": "2025-10-22",
      "deliveries": 6,
      "earnings": "90.00"
    },
    {
      "date": "2025-10-23",
      "deliveries": 4,
      "earnings": "60.00"
    },
    {
      "date": "2025-10-24",
      "deliveries": 7,
      "earnings": "105.00"
    },
    {
      "date": "2025-10-25",
      "deliveries": 5,
      "earnings": "75.00"
    },
    {
      "date": "2025-10-26",
      "deliveries": 3,
      "earnings": "45.00"
    },
    {
      "date": "2025-10-27",
      "deliveries": 5,
      "earnings": "75.00"
    }
  ]
}
```

---

### 3. Get Invoices List
**Endpoint:** `GET /api/invoices`  
**Authentication:** Required (ROLE_USER)  
**Description:** Get list of invoices for delivery person

**Response:**
```json
{
  "data": [
    {
      "id": 101,
      "invoiceNumber": "INV-2025-101",
      "period": "October 2025",
      "totalAmount": "1850.00",
      "totalDeliveries": 142,
      "generatedAt": "2025-11-01T00:00:00+00:00",
      "status": "paid"
    },
    {
      "id": 100,
      "invoiceNumber": "INV-2025-100",
      "period": "September 2025",
      "totalAmount": "1650.00",
      "totalDeliveries": 128,
      "generatedAt": "2025-10-01T00:00:00+00:00",
      "status": "paid"
    }
  ]
}
```

---

### 4. Download Invoice
**Endpoint:** `GET /api/invoices/{id}/download`  
**Authentication:** Required (ROLE_USER)  
**Description:** Download invoice as PDF

**Response:** PDF file download

---

## Profile Management APIs

### 1. Update Profile
**Endpoint:** `PUT /api/profile`  
**Authentication:** Required (ROLE_USER)  
**Description:** Update delivery person profile information

**Request Body:**
```json
{
  "firstName": "John",
  "lastName": "Doe",
  "phone": "+1234567890",
  "vehicleType": "motorcycle",
  "vehiclePlate": "ABC123"
}
```

**All fields are optional**

**Response:**
```json
{
  "id": 1,
  "firstName": "John",
  "lastName": "Doe",
  "email": "john@example.com",
  "phone": "+1234567890",
  "vehicleType": "motorcycle",
  "vehiclePlate": "ABC123",
  "message": "Profile updated successfully"
}
```

---

### 2. Update User (Multipart)
**Endpoint:** `POST /api/user/update`  
**Authentication:** Required (ROLE_USER)  
**Content-Type:** `multipart/form-data`

**Form Data:**
- `firstName` (string, optional)
- `lastName` (string, optional)
- `phone` (string, optional)
- `avatar` (file, optional)

**Response:**
```json
{
  "id": 1,
  "firstName": "John",
  "lastName": "Doe",
  "email": "john@example.com",
  "phone": "+1234567890",
  "avatar": "/uploads/avatars/john-doe.jpg"
}
```

---

## Notification APIs

### 1. Get Notifications
**Endpoint:** `GET /api/notifications`  
**Authentication:** Required (ROLE_USER)  
**Description:** Get paginated list of notifications for current user

**Query Parameters:**
- `page` (integer, default: 1)
- `limit` (integer, default: 20)

**Response:**
```json
{
  "data": [
    {
      "id": 456,
      "type": "new_order",
      "title": "New Order Available",
      "message": "A new order is available in your area",
      "data": {
        "orderId": 123,
        "orderReference": "ORD-2025-001"
      },
      "isRead": false,
      "createdAt": "2025-10-27T14:00:00+00:00"
    },
    {
      "id": 455,
      "type": "order_cancelled",
      "title": "Order Cancelled",
      "message": "Order ORD-2025-002 has been cancelled",
      "data": {
        "orderId": 122,
        "reason": "Customer request"
      },
      "isRead": true,
      "createdAt": "2025-10-27T12:00:00+00:00"
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
- `new_order` - New order available
- `order_accepted` - Order accepted confirmation
- `order_cancelled` - Order cancelled
- `payment_received` - Payment received
- `rating_received` - New rating received
- `system` - System notification

---

### 2. Get Unread Count
**Endpoint:** `GET /api/notifications/unread-count`  
**Authentication:** Required (ROLE_USER)  
**Description:** Get count of unread notifications

**Response:**
```json
{
  "unreadCount": 5
}
```

---

### 3. Mark Notification as Read
**Endpoint:** `PUT /api/notifications/{id}/read`  
**Authentication:** Required (ROLE_USER)  
**Description:** Mark a specific notification as read

**Response:**
```json
{
  "id": 456,
  "isRead": true,
  "message": "Notification marked as read"
}
```

---

### 4. Mark All Notifications as Read
**Endpoint:** `PUT /api/notifications/read-all`  
**Authentication:** Required (ROLE_USER)  
**Description:** Mark all user notifications as read

**Response:**
```json
{
  "message": "All notifications marked as read",
  "count": 5
}
```

---

## Emergency & Support APIs

### 1. Send Emergency SOS
**Endpoint:** `POST /api/emergency/sos`  
**Authentication:** Required (ROLE_USER)  
**Description:** Send emergency SOS signal with location

**Request Body:**
```json
{
  "latitude": 40.7128,
  "longitude": -74.0060,
  "notes": "Need immediate help",
  "orderId": 123
}
```

**Validation:**
- `latitude`: Required, float between -90 and 90
- `longitude`: Required, float between -180 and 180
- `notes`: Optional, string
- `orderId`: Optional, integer

**Response:**
```json
{
  "id": 789,
  "status": "sent",
  "latitude": 40.7128,
  "longitude": -74.0060,
  "sentAt": "2025-10-27T14:30:00+00:00",
  "message": "Emergency SOS sent successfully. Help is on the way.",
  "emergencyContacts": [
    {
      "name": "Emergency Services",
      "phone": "911"
    },
    {
      "name": "Company Support",
      "phone": "+1234567890"
    }
  ]
}
```

---

### 2. Contact Support / Create Ticket
**Endpoint:** `POST /api/support/contact`  
**Authentication:** Required (ROLE_USER)  
**Description:** Create a support ticket

**Request Body:**
```json
{
  "subject": "Issue with payment",
  "message": "I haven't received payment for order ORD-2025-001",
  "priority": "high"
}
```

**Validation:**
- `subject`: Required, string, 3-255 characters
- `message`: Required, string, minimum 10 characters
- `priority`: Optional, string
  - Values: `low`, `normal`, `high`, `urgent` (default: `normal`)

**Response:**
```json
{
  "id": 567,
  "ticketNumber": "TICKET-2025-567",
  "subject": "Issue with payment",
  "message": "I haven't received payment for order ORD-2025-001",
  "priority": "high",
  "status": "open",
  "createdAt": "2025-10-27T14:30:00+00:00",
  "message": "Support ticket created successfully. We'll respond within 24 hours."
}
```

---

## Store APIs

### 1. Get Stores List
**Endpoint:** `GET /api/stores`  
**Authentication:** Required (ROLE_USER)  
**Description:** Get list of available stores

**Response:**
```json
{
  "data": [
    {
      "id": 1,
      "name": "Main Pharmacy",
      "address": "123 Main St, New York, NY 10001",
      "phone": "+1234567890",
      "latitude": 40.7128,
      "longitude": -74.0060,
      "isOpen": true,
      "businessHours": {
        "monday": "09:00-18:00",
        "tuesday": "09:00-18:00",
        "wednesday": "09:00-18:00",
        "thursday": "09:00-18:00",
        "friday": "09:00-18:00",
        "saturday": "10:00-16:00",
        "sunday": "Closed"
      }
    },
    {
      "id": 2,
      "name": "Downtown Pharmacy",
      "address": "456 Oak Ave, Brooklyn, NY 11201",
      "phone": "+1234567891",
      "latitude": 40.6944,
      "longitude": -73.9865,
      "isOpen": true,
      "businessHours": {
        "monday": "08:00-20:00",
        "tuesday": "08:00-20:00",
        "wednesday": "08:00-20:00",
        "thursday": "08:00-20:00",
        "friday": "08:00-20:00",
        "saturday": "09:00-17:00",
        "sunday": "10:00-15:00"
      }
    }
  ]
}
```

---

## Error Responses

All endpoints may return the following error responses:

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
  "error": "Invalid credentials",
  "code": 401,
  "message": "Authentication required"
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
  "error": "Resource not found",
  "code": 404,
  "message": "The requested resource was not found"
}
```

### 409 Conflict
```json
{
  "error": "Order already accepted",
  "code": 409,
  "message": "This order has already been accepted by another delivery person"
}
```

### 500 Internal Server Error
```json
{
  "error": "Internal server error",
  "code": 500,
  "message": "An unexpected error occurred"
}
```

---

## Rate Limiting

API requests are rate-limited to prevent abuse:
- **Authenticated requests**: 1000 requests per hour
- **Public endpoints**: 100 requests per hour per IP

When rate limit is exceeded:
```json
{
  "error": "Rate limit exceeded",
  "code": 429,
  "message": "Too many requests. Please try again later.",
  "retryAfter": 3600
}
```

---

## Pagination

All collection endpoints support pagination with the following parameters:
- `page`: Page number (default: 1)
- `limit`: Items per page (default: varies by endpoint)

Paginated responses include:
```json
{
  "data": [...],
  "pagination": {
    "currentPage": 1,
    "itemsPerPage": 20,
    "totalItems": 150,
    "totalPages": 8,
    "hasNextPage": true,
    "hasPreviousPage": false
  }
}
```

---

## Changelog

### Version 1.0 (2025-10-27)
- Initial API documentation
- All core delivery endpoints documented
- Authentication and authorization documented
- Error handling documented

