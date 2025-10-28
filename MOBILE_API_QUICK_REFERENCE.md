# 📱 Mobile Store API - Quick Reference Card

> **Quick cheat sheet for developers** - Keep this handy while coding!

---

## 🔗 Base URL
```
https://your-domain.com/api
```

---

## 🔐 Authentication Header
```http
Authorization: Bearer {jwt_token}
```

---

## 📋 Quick API Reference

### 🔑 Authentication (Public)

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/register` | Register customer |
| POST | `/register/store` | Register store owner |
| POST | `/auth` | Login |
| POST | `/facebook_login` | Facebook login |
| POST | `/google_login` | Google login |
| POST | `/token/refresh` | Refresh token |
| POST | `/logout` | Logout (authenticated) |

### 👤 Profile (Authenticated)

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/me` | Get current user |
| POST | `/user/update` | Update profile (multipart) |
| POST | `/password/forgot` | Send reset code |
| POST | `/password/verify-code` | Verify code |
| POST | `/password/reset` | Reset password |
| POST | `/user/update-password` | Change password |

### 🏪 Store Management

| Method | Endpoint | Description | Auth |
|--------|----------|-------------|------|
| GET | `/stores` | List all stores | ✓ |
| GET | `/stores/{id}` | Get store details | ✓ |
| GET | `/store/business-hours` | Get hours | STORE |
| PUT | `/store/business-hours` | Update hours | STORE |
| PUT | `/store/toggle-status` | Open/close store | STORE |

### 📦 Products

| Method | Endpoint | Description | Auth |
|--------|----------|-------------|------|
| GET | `/products` | List products | Optional |
| GET | `/product/{id}` | Get product | Optional |
| GET | `/products/suggestion` | Homepage suggestions | Optional |
| POST | `/products` | Add product | ADMIN |

### 🛒 Store Inventory

| Method | Endpoint | Description | Auth |
|--------|----------|-------------|------|
| GET | `/store_products` | Store's products | STORE |
| PUT | `/store_products/{id}` | Update stock/price | STORE |

### 📝 Orders

| Method | Endpoint | Description | Auth |
|--------|----------|-------------|------|
| POST | `/order` | Create order | CUSTOMER |
| GET | `/order/{id}` | Get order | ✓ |
| GET | `/orders` | List orders | STORE |

### ✅ Order Item Actions

| Method | Endpoint | Description | Auth |
|--------|----------|-------------|------|
| GET | `/store/order-items/pending` | Pending items | STORE |
| POST | `/store/order-item/accept` | Accept item | STORE |
| POST | `/store/order-item/refuse` | Refuse item | STORE |
| POST | `/store/order-item/suggest` | Suggest alternative | STORE |

### 🗂️ Categories

| Method | Endpoint | Description | Auth |
|--------|----------|-------------|------|
| GET | `/category` | List categories | Optional |
| GET | `/category/{id}` | Get category | Optional |

### 🔔 Notifications

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/notifications` | List notifications |
| GET | `/notifications/unread-count` | Unread count |
| PUT | `/notifications/{id}/read` | Mark as read |
| PUT | `/notifications/read-all` | Mark all read |

### 🆘 Support

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/emergency/sos` | Send SOS |
| POST | `/support/contact` | Create ticket |

---

## 📤 Common Request Bodies

### Register Customer
```json
{
  "email": "customer@example.com",
  "password": "SecurePass123!",
  "firstName": "John",
  "lastName": "Doe",
  "phone": "+261234567890"
}
```

### Register Store Owner
```json
{
  "email": "store@example.com",
  "password": "SecurePass123!",
  "firstName": "Sarah",
  "lastName": "Shop",
  "phone": "+261234567891",
  "storeName": "Joy Pharmacy Downtown",
  "storeAddress": "123 Main St, Antananarivo",
  "storeCity": "Antananarivo",
  "storeLatitude": -18.8792,
  "storeLongitude": 47.5079
}
```

### Login
```json
{
  "email": "user@example.com",
  "password": "password"
}
```

### Create Order
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
    }
  ]
}
```

### Accept Order Item
```json
{
  "orderItemId": 201,
  "notes": "Item available in stock"
}
```

### Refuse Order Item
```json
{
  "orderItemId": 201,
  "reason": "Product out of stock"
}
```

### Suggest Alternative
```json
{
  "orderItemId": 201,
  "suggestedProductId": 15,
  "suggestion": "We have a generic version",
  "notes": "Same active ingredient"
}
```

### Update Store Product
```json
{
  "unitPrice": 250.00,
  "price": 5000.00,
  "stock": 200,
  "isActive": true
}
```

---

## 🎨 Status Values

### Order Status
- `pending` - Order pending
- `confirmed` - Order confirmed
- `processing` - Being processed
- `shipped` - Out for delivery
- `delivered` - Delivered
- `cancelled` - Cancelled

### Store Item Status
- `pending` - Waiting for store action
- `accepted` - Store accepted
- `refused` - Store refused
- `suggested` - Store suggested alternative
- `approved` - Admin approved suggestion

### Priority Levels
- `standard` - Normal delivery
- `urgent` - Urgent delivery
- `express` - Express delivery

---

## ⚠️ Error Codes

| Code | Meaning |
|------|---------|
| 200 | OK |
| 201 | Created |
| 400 | Bad Request |
| 401 | Unauthorized |
| 403 | Forbidden |
| 404 | Not Found |
| 409 | Conflict |
| 422 | Validation Error |
| 429 | Rate Limited |
| 500 | Server Error |

---

## 🎯 Query Parameters

### Pagination
```
?page=1&limit=20
```

### Filtering
```
?status=pending&category=1
```

### Search
```
?search=paracetamol
```

### Combined
```
?page=2&limit=50&status=pending&search=aspirin
```

---

## 📦 Response Structure

### Success Response
```json
{
  "id": 123,
  "name": "Product Name",
  "status": "active",
  "message": "Success message"
}
```

### List Response
```json
{
  "data": [...],
  "pagination": {
    "currentPage": 1,
    "itemsPerPage": 20,
    "totalItems": 150,
    "totalPages": 8
  }
}
```

### Error Response
```json
{
  "error": "Error title",
  "code": 400,
  "message": "Detailed message",
  "violations": [
    {
      "field": "email",
      "message": "Invalid email"
    }
  ]
}
```

---

## 🔧 Quick Code Snippets

### Axios Setup
```javascript
import axios from 'axios';

const api = axios.create({
  baseURL: 'https://your-domain.com/api',
  timeout: 30000,
});

// Add token
api.interceptors.request.use(async (config) => {
  const token = await getToken();
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});
```

### Login Function
```javascript
const login = async (email, password) => {
  const { data } = await api.post('/auth', { email, password });
  await storeToken(data.token, data.refresh_token);
  return data;
};
```

### Get Products
```javascript
const getProducts = async (page = 1) => {
  const { data } = await api.get('/products', {
    params: { page, limit: 20 }
  });
  return data;
};
```

### Accept Order Item
```javascript
const acceptItem = async (itemId, notes) => {
  const { data } = await api.post('/store/order-item/accept', {
    orderItemId: itemId,
    notes
  });
  return data;
};
```

---

## 🎨 Color Codes (Recommended)

```javascript
const Colors = {
  primary: '#00BFA5',
  success: '#4CAF50',
  warning: '#FF9800',
  error: '#f44336',
  info: '#2196F3',
  
  // Status
  pending: '#FFC107',
  accepted: '#4CAF50',
  refused: '#f44336',
  suggested: '#FF9800',
};
```

---

## 📊 Data Models

### User Object
```javascript
{
  id: number,
  email: string,
  firstName: string,
  lastName: string,
  phone: string,
  roles: string[],
  userType: 'customer' | 'store',
  isActive: boolean,
  avatar: string,
  store?: Store
}
```

### Product Object
```javascript
{
  id: number,
  name: string,
  description: string,
  code: string,
  category: Category,
  brand?: Brand,
  images: Image[],
  isActive: boolean
}
```

### Order Object
```javascript
{
  id: number,
  reference: string,
  status: string,
  priority: string,
  totalAmount: number,
  storeTotalAmount: number,
  scheduledDate: string,
  notes: string,
  phone: string,
  location: Location,
  owner: User,
  items: OrderItem[],
  createdAt: string,
  updatedAt: string
}
```

### OrderItem Object
```javascript
{
  id: number,
  product: Product,
  quantity: number,
  unitPrice: number,
  subtotal: number,
  notes: string,
  storeStatus: string,
  storePrice: number,
  storeTotalAmount: number,
  storeNotes: string,
  storeActionAt: string,
  store: Store,
  suggestedProduct?: Product
}
```

---

## 🔒 Security Checklist

- [ ] Store JWT in secure storage (Keychain/Keystore)
- [ ] Never log sensitive data
- [ ] Use HTTPS only
- [ ] Implement token refresh
- [ ] Handle 401 globally
- [ ] Validate inputs client-side
- [ ] Handle errors gracefully
- [ ] Clear tokens on logout
- [ ] Set request timeouts
- [ ] Rate limit requests

---

## 🚀 Performance Tips

- [ ] Cache API responses
- [ ] Use image optimization (FastImage)
- [ ] Implement pagination
- [ ] Use FlatList for long lists
- [ ] Debounce search inputs
- [ ] Implement pull-to-refresh
- [ ] Show loading states
- [ ] Handle offline mode
- [ ] Compress images before upload
- [ ] Use memoization

---

## 🐛 Common Issues

### Issue: 401 Unauthorized
**Solution:** Check token expiry, implement refresh

### Issue: Slow API calls
**Solution:** Check network, optimize requests, add caching

### Issue: Image upload fails
**Solution:** Check file size, use multipart/form-data

### Issue: Products not in inventory
**Solution:** Check StoreProduct table, prices must be set

---

## 📞 Quick Links

- **Full API Docs:** `MOBILE_STORE_API_COMPLETE.md`
- **Developer Guide:** `MOBILE_APP_DEVELOPER_GUIDE.md`
- **Support:** support@joypharma.com

---

**Print this page and keep it at your desk! 📌**


