# API Implementation Analysis - Joy Pharma

## Summary
**Total Required Endpoints:** 30  
**Already Implemented:** 7  
**To Implement:** 23  

---

## ✅ Already Implemented (7 endpoints)

### Authentication
- ✅ `POST /api/register` - Register new user (User.yaml)
- ✅ `POST /api/auth` - Login (security.yaml)  
- ✅ `GET /api/me` - Get current user (User.yaml)
- ✅ `POST /api/token/refresh` - Refresh JWT token (security.yaml)

### Orders
- ✅ `POST /api/order` - Create order (Order.yaml)
- ✅ `GET /api/order/{id}` - Get order details (Order.yaml)
- ✅ `GET /api/orders` - Get all orders (Order.yaml)

---

## ❌ Missing Implementation (23 endpoints)

### 🔐 Authentication (2 missing)
- ❌ `PUT /api/availability` - Toggle delivery person online/offline
- ❌ `POST /api/logout` - Logout user

### 📦 Orders (9 missing - Delivery Person Specific)
- ❌ `GET /api/orders/available` - Get available orders for delivery
- ❌ `GET /api/orders/current` - Get current active order
- ❌ `GET /api/orders/history` - Get order history
- ❌ `POST /api/orders/{id}/accept` - Accept order for delivery
- ❌ `POST /api/orders/{id}/reject` - Reject order
- ❌ `PUT /api/orders/{id}/status` - Update order status
- ❌ `POST /api/orders/{id}/validate-qr` - Validate QR code
- ❌ `POST /api/orders/{id}/rating` - Submit rating
- ❌ `POST /api/orders/{id}/report-issue` - Report issue

### 📅 Availability (3 missing)
- ❌ `GET /api/availability/schedule` - Get delivery schedule
- ❌ `PUT /api/availability/schedule` - Update schedule
- ❌ `PUT /api/availability/online` - Toggle online status

### 📊 Stats & Profile (6 missing)
- ❌ `GET /api/stats/dashboard` - Dashboard statistics
- ❌ `GET /api/stats/earnings` - Earnings history
- ❌ `GET /api/invoices` - List invoices
- ❌ `GET /api/invoices/{id}/download` - Download invoice PDF
- ❌ `PUT /api/profile` - Update profile
- ❌ `POST /api/location` - Update real-time location

### 🔔 Notifications (4 missing)
- ❌ `GET /api/notifications` - Get notifications
- ❌ `GET /api/notifications/unread-count` - Unread count
- ❌ `PUT /api/notifications/{id}/read` - Mark as read
- ❌ `PUT /api/notifications/read-all` - Mark all as read

### 🏪 Other (3 missing)
- ❌ `GET /api/stores` - Get stores list
- ❌ `POST /api/emergency/sos` - Send SOS signal
- ❌ `POST /api/support/contact` - Contact support

---

## 🗄️ Required New Entities

### 1. Notification
```php
- id
- user (ManyToOne User)
- title
- message
- type (enum: order_new, order_status, system, promotion)
- isRead (boolean)
- data (json)
- createdAt
```

### 2. DeliverySchedule
```php
- id
- deliveryPerson (ManyToOne User)
- dayOfWeek (0-6)
- startTime
- endTime
- isActive
```

### 3. Invoice
```php
- id
- deliveryPerson (ManyToOne User)
- reference
- period (start/end date)
- totalEarnings
- totalDeliveries
- status (pending, paid, cancelled)
- pdfPath
- createdAt
```

### 4. Rating
```php
- id
- order (OneToOne Order)
- deliveryPerson (ManyToOne User)
- customer (ManyToOne User)
- rating (1-5)
- comment
- createdAt
```

### 5. Issue
```php
- id
- order (ManyToOne Order)
- reportedBy (ManyToOne User)
- type (enum: damaged_product, wrong_address, customer_unavailable, other)
- description
- status (open, in_progress, resolved)
- resolution
- createdAt
- resolvedAt
```

### 6. DeliveryLocation
```php
- id
- deliveryPerson (ManyToOne User)
- latitude
- longitude
- accuracy
- timestamp
- createdAt
```

### 7. EmergencySOS
```php
- id
- deliveryPerson (ManyToOne User)
- order (ManyToOne Order, nullable)
- latitude
- longitude
- status (active, resolved, false_alarm)
- createdAt
- resolvedAt
```

### 8. SupportTicket
```php
- id
- user (ManyToOne User)
- subject
- message
- status (open, in_progress, resolved, closed)
- priority (low, normal, high, urgent)
- createdAt
- updatedAt
```

---

## 🔧 Required Entity Updates

### User Entity - Add Delivery Person Fields
```php
- isOnline (boolean) - Currently online/offline
- currentLatitude (float, nullable)
- currentLongitude (float, nullable)
- lastLocationUpdate (datetime, nullable)
- totalDeliveries (int, default 0)
- averageRating (float, nullable)
- totalEarnings (float, default 0)
- vehicleType (enum: bike, motorcycle, car, nullable)
- vehiclePlate (string, nullable)
```

### Order Entity - Add Delivery Fields
```php
- acceptedAt (datetime, nullable)
- pickedUpAt (datetime, nullable)
- deliveredAt (datetime, nullable)
- estimatedDeliveryTime (datetime, nullable)
- actualDeliveryTime (datetime, nullable)
- qrCode (string, unique)
- qrCodeValidatedAt (datetime, nullable)
- deliveryFee (float)
- deliveryNotes (text, nullable)
```

---

## 📁 Required New Files

### Controllers
- `src/Controller/Api/Availability/ToggleAvailabilityController.php`
- `src/Controller/Api/Availability/ScheduleController.php`
- `src/Controller/Api/Availability/OnlineStatusController.php`
- `src/Controller/Api/Orders/AvailableOrdersController.php`
- `src/Controller/Api/Orders/CurrentOrderController.php`
- `src/Controller/Api/Orders/OrderHistoryController.php`
- `src/Controller/Api/Orders/AcceptOrderController.php`
- `src/Controller/Api/Orders/RejectOrderController.php`
- `src/Controller/Api/Orders/UpdateOrderStatusController.php`
- `src/Controller/Api/Orders/ValidateQRController.php`
- `src/Controller/Api/Orders/RatingController.php`
- `src/Controller/Api/Orders/ReportIssueController.php`
- `src/Controller/Api/Stats/DashboardController.php`
- `src/Controller/Api/Stats/EarningsController.php`
- `src/Controller/Api/Invoices/InvoicesController.php`
- `src/Controller/Api/Invoices/DownloadInvoiceController.php`
- `src/Controller/Api/Profile/UpdateProfileController.php`
- `src/Controller/Api/Location/UpdateLocationController.php`
- `src/Controller/Api/Notifications/NotificationsController.php`
- `src/Controller/Api/Notifications/UnreadCountController.php`
- `src/Controller/Api/Notifications/MarkReadController.php`
- `src/Controller/Api/Notifications/MarkAllReadController.php`
- `src/Controller/Api/Store/StoresController.php`
- `src/Controller/Api/Emergency/SOSController.php`
- `src/Controller/Api/Support/ContactController.php`
- `src/Controller/Api/Auth/LogoutController.php`

### DTOs (Input)
- `src/Dto/ToggleAvailabilityInput.php`
- `src/Dto/ScheduleInput.php`
- `src/Dto/UpdateOrderStatusInput.php`
- `src/Dto/ValidateQRInput.php`
- `src/Dto/RatingInput.php`
- `src/Dto/ReportIssueInput.php`
- `src/Dto/UpdateLocationInput.php`
- `src/Dto/SOSInput.php`
- `src/Dto/SupportTicketInput.php`

### Services
- `src/Service/DeliveryService.php`
- `src/Service/NotificationService.php`
- `src/Service/StatsService.php`
- `src/Service/InvoiceService.php`
- `src/Service/QRCodeService.php`
- `src/Service/LocationService.php`

### Repositories (add methods)
- Update `OrderRepository` with delivery-specific queries
- Update `UserRepository` with delivery person queries
- Create `NotificationRepository`
- Create `DeliveryScheduleRepository`
- Create `InvoiceRepository`
- Create `RatingRepository`
- Create `IssueRepository`
- Create `DeliveryLocationRepository`
- Create `EmergencySOSRepository`
- Create `SupportTicketRepository`

---

## 🎯 Implementation Priority

### Phase 1: Core Delivery Features (High Priority)
1. Create missing entities (Notification, DeliverySchedule, Rating, Issue, DeliveryLocation)
2. Update User and Order entities with delivery fields
3. Create migrations
4. Implement availability endpoints
5. Implement order management endpoints (accept, reject, update status)

### Phase 2: Location & QR Features
6. Implement location tracking
7. Implement QR code validation
8. Implement rating system

### Phase 3: Stats & Reports
9. Implement dashboard statistics
10. Implement earnings tracking
11. Implement invoice system

### Phase 4: Communication
12. Implement notification system
13. Implement support system
14. Implement emergency SOS

### Phase 5: Additional Features
15. Order history and filtering
16. Store listing
17. Profile management

---

## 🔒 Security Considerations

### Role-Based Access
- Add `ROLE_DELIVERY` for delivery persons
- Ensure delivery endpoints check for this role
- Orders should only be visible to assigned delivery person

### Data Validation
- Validate location coordinates
- Validate QR codes
- Validate status transitions

### Rate Limiting
- Implement rate limiting for location updates
- Implement rate limiting for SOS signals

---

## 📝 Notes

1. **WebSocket Support**: The API documentation mentions WebSocket, but this needs separate implementation using Mercure or similar.
2. **Push Notifications**: Consider integrating Firebase Cloud Messaging for mobile push notifications.
3. **Pagination**: All list endpoints should support pagination.
4. **Filtering**: Order lists need filtering by status, date, priority.
5. **Sorting**: Support sorting by date, priority, distance.

---

## Next Steps

1. ✅ Analysis complete
2. ⏳ Create database migrations
3. ⏳ Create entities
4. ⏳ Create DTOs
5. ⏳ Create controllers
6. ⏳ Create services
7. ⏳ Test endpoints
8. ⏳ Update documentation

