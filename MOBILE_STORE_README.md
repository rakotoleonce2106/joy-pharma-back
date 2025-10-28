# 📱 Mobile Pharmacy Store App - Complete Documentation

> **Everything you need to build a professional mobile pharmacy/store delivery application**

---

## 📚 Documentation Overview

This is your complete guide to building a mobile pharmacy store application. All documentation is in one place, organized by purpose.

---

## 🎯 What You Get

✅ **Complete REST API Documentation** - All 37+ endpoints fully documented  
✅ **Developer Integration Guide** - Step-by-step implementation guide  
✅ **Quick Reference Card** - Cheat sheet for daily development  
✅ **AI Assistant Prompts** - Pre-written prompts for ChatGPT/Claude  
✅ **Code Examples** - React Native examples with best practices  
✅ **TypeScript Types** - Full type definitions  
✅ **Error Handling Guide** - Comprehensive error scenarios  
✅ **Security Best Practices** - Production-ready security guidelines  

---

## 📖 Documentation Files

### 1️⃣ Start Here: Complete API Reference
**File:** `MOBILE_STORE_API_COMPLETE.md` (Primary Document)

**What's Inside:**
- All 37+ API endpoints with full documentation
- Request/response examples for every endpoint
- Authentication & registration flows
- Store owner features (inventory, orders, business hours)
- Customer features (browsing, ordering, tracking)
- Order item management (accept/refuse/suggest)
- Notifications, support, and emergency features
- Complete error handling guide
- Quick integration examples

**When to Use:** This is your primary reference. Use it to understand what APIs are available and how to call them.

**📄 Sections:**
1. Authentication & Registration (7 endpoints)
2. User Profile Management (4 endpoints)
3. Store Management (5 endpoints)
4. Product Management (4 endpoints)
5. Store Inventory (2 endpoints)
6. Order Management (3 endpoints)
7. Order Item Actions (4 endpoints)
8. Store Availability (3 endpoints)
9. Categories & Brands (2 endpoints)
10. Notifications (4 endpoints)
11. Support & Emergency (2 endpoints)
12. Error Handling & Best Practices

---

### 2️⃣ Developer Integration Guide
**File:** `MOBILE_APP_DEVELOPER_GUIDE.md`

**What's Inside:**
- Complete project setup and architecture
- Secure authentication implementation
- Token storage best practices
- API client configuration with Axios
- Store owner features implementation
- Customer features implementation
- React Native code examples
- UI/UX components
- Performance optimization
- Testing strategies
- Platform-specific considerations (iOS/Android)

**When to Use:** Use this when you're implementing features. It provides detailed code examples and implementation patterns.

**📄 Key Sections:**
- 🔐 Authentication Implementation (complete code)
- 🏪 Store Owner Features (dashboard, inventory, orders)
- 🛍️ Customer Features (browsing, ordering)
- 🔔 Notifications (push & in-app)
- 🎨 UI/UX Best Practices
- ⚡ Performance Optimization
- 🧪 Testing Guide

---

### 3️⃣ Quick Reference Card
**File:** `MOBILE_API_QUICK_REFERENCE.md`

**What's Inside:**
- One-page cheat sheet
- All endpoints in table format
- Common request bodies
- Status values and codes
- Quick code snippets
- Color codes for UI
- Data models
- Error codes
- Security checklist
- Common issues & solutions

**When to Use:** Keep this open while coding. Perfect for quick lookups without scrolling through full docs.

**💡 Perfect For:**
- Quick endpoint lookup
- Copy-paste request bodies
- Status code reference
- Color scheme consistency
- Common error solutions

---

### 4️⃣ AI Coding Assistant Prompt
**File:** `AI_CODING_ASSISTANT_PROMPT.md`

**What's Inside:**
- Master prompt for AI assistants (ChatGPT, Claude, etc.)
- Specific use case prompts (10+ scenarios)
- Troubleshooting prompts
- Platform-specific prompts
- Debug helpers
- Feature implementation templates

**When to Use:** When you need AI help building features or debugging issues.

**🤖 Use Cases Covered:**
1. Setting up authentication
2. Building store owner dashboard
3. Order item management
4. Product browsing
5. Order creation flow
6. Inventory management
7. Business hours management
8. Notification system
9. Error handling
10. Navigation structure

---

## 🚀 Quick Start Guide

### For New Developers

**Step 1: Understand the API** (15 minutes)
1. Read `MOBILE_STORE_API_COMPLETE.md` - Section 1 (Authentication)
2. Try the login/register endpoints with Postman/cURL
3. Get a JWT token and test `/me` endpoint

**Step 2: Set Up Your Project** (30 minutes)
1. Open `MOBILE_APP_DEVELOPER_GUIDE.md`
2. Follow "Getting Started" section
3. Set up API client with authentication
4. Implement login screen

**Step 3: Start Building** (ongoing)
1. Keep `MOBILE_API_QUICK_REFERENCE.md` open for quick lookups
2. Use `AI_CODING_ASSISTANT_PROMPT.md` when you need AI help
3. Reference full docs when needed

**Step 4: Test & Deploy**
1. Follow testing guide in Developer Guide
2. Check security checklist
3. Deploy to TestFlight/Google Play Beta

---

## 👥 By User Type

### Store Owners Can:
✅ Register store with complete details  
✅ Manage product inventory (stock, pricing)  
✅ View incoming orders in real-time  
✅ Accept, refuse, or suggest alternatives for items  
✅ Set business hours (per day)  
✅ Toggle store open/closed  
✅ Receive notifications for new orders  
✅ Update profile and store information  

**API Endpoints:** 15+  
**Main Screens:** Dashboard, Inventory, Orders, Business Hours, Profile

### Customers Can:
✅ Browse products by category  
✅ Search products  
✅ Create orders with multiple items  
✅ Track order status in real-time  
✅ View order history  
✅ Receive notifications  
✅ Update profile  
✅ Contact support / Emergency SOS  

**API Endpoints:** 12+  
**Main Screens:** Home, Browse, Product Details, Cart, Checkout, Order Tracking, History, Profile

---

## 🏗️ System Architecture

```
┌─────────────────────────────────────────────┐
│           Mobile Application                │
│  (React Native / Flutter / Native)          │
│                                             │
│  ┌──────────────┐      ┌──────────────┐   │
│  │ Store Owner  │      │   Customer   │   │
│  │     App      │      │     App      │   │
│  └──────┬───────┘      └──────┬───────┘   │
│         │                     │            │
│         └─────────┬───────────┘            │
└───────────────────┼────────────────────────┘
                    │
                    │ HTTPS + JWT
                    │
┌───────────────────▼────────────────────────┐
│          Symfony API Backend               │
│         (https://domain.com/api)           │
│                                            │
│  37+ RESTful Endpoints:                    │
│  • Authentication & Registration           │
│  • Store & Inventory Management            │
│  • Order Processing                        │
│  • Product Catalog                         │
│  • Notifications                           │
│  • Business Hours                          │
└───────────────────┬────────────────────────┘
                    │
┌───────────────────▼────────────────────────┐
│           PostgreSQL Database               │
│                                            │
│  Tables: Users, Stores, Products,          │
│  Orders, OrderItems, StoreProducts,        │
│  Categories, Notifications, etc.           │
└────────────────────────────────────────────┘
```

---

## 🔐 Authentication Flow

```
1. User Registration
   POST /register (Customer)
   POST /register/store (Store Owner)
   
2. Login
   POST /auth
   → Returns JWT token + refresh token
   
3. Store Token Securely
   iOS: Keychain
   Android: Keystore
   
4. Use Token
   All requests:
   Authorization: Bearer {token}
   
5. Token Refresh
   POST /token/refresh
   When 401 error occurs
   
6. Logout
   POST /logout
   Clear stored tokens
```

---

## 📦 Key Business Rules

### ⚠️ Important Constraints

1. **Inventory-Based Operations**
   - Stores can ONLY accept products in their inventory
   - Prices are automatically fetched from StoreProduct table
   - Cannot manually set prices when accepting orders

2. **Order Item Status Flow**
   ```
   PENDING → ACCEPTED (by store)
          → REFUSED (by store)
          → SUGGESTED (by store) → APPROVED (by admin)
   ```

3. **Price Calculation**
   - `totalAmount`: Based on catalog prices
   - `storeTotalAmount`: Based on store-specific prices (accepted items only)

4. **Store Hours**
   - 7 days configuration
   - Each day: open time, close time, is_open flag
   - Can temporarily close store regardless of hours

---

## 🎨 Design Guidelines

### Color Palette

```javascript
const Colors = {
  // Brand
  primary: '#00BFA5',      // Teal
  primaryDark: '#00897B',
  primaryLight: '#B2DFDB',
  
  // Status
  success: '#4CAF50',      // Green
  warning: '#FF9800',      // Orange
  error: '#f44336',        // Red
  info: '#2196F3',         // Blue
  
  // Order Status
  pending: '#FFC107',      // Amber
  confirmed: '#2196F3',    // Blue
  processing: '#9C27B0',   // Purple
  shipped: '#FF5722',      // Deep Orange
  delivered: '#4CAF50',    // Green
  cancelled: '#757575',    // Gray
  
  // Store Item Status
  accepted: '#4CAF50',     // Green
  refused: '#f44336',      // Red
  suggested: '#FF9800',    // Orange
  approved: '#2196F3',     // Blue
};
```

### Typography
- Headings: Bold, 18-24px
- Body: Regular, 14-16px
- Captions: Regular, 12px
- Buttons: Semi-bold, 14-16px

---

## 📊 Data Models Overview

### User
```typescript
interface User {
  id: number;
  email: string;
  firstName: string;
  lastName: string;
  phone: string;
  roles: string[];
  userType: 'customer' | 'store';
  avatar?: string;
  store?: Store;
}
```

### Product
```typescript
interface Product {
  id: number;
  name: string;
  code: string;
  description: string;
  category: Category;
  brand?: Brand;
  images: Image[];
  isActive: boolean;
}
```

### Order
```typescript
interface Order {
  id: number;
  reference: string;
  status: OrderStatus;
  priority: Priority;
  totalAmount: number;
  storeTotalAmount: number;
  scheduledDate?: string;
  notes?: string;
  phone: string;
  location: Location;
  owner: User;
  items: OrderItem[];
  createdAt: string;
}
```

### StoreProduct
```typescript
interface StoreProduct {
  id: number;
  product: Product;
  store: Store;
  unitPrice?: number;
  price: number;
  stock: number;
  isActive: boolean;
}
```

**More types in:** `MOBILE_API_QUICK_REFERENCE.md`

---

## ✅ Implementation Checklist

### Phase 1: Foundation (Week 1)
- [ ] Project setup
- [ ] API client configuration
- [ ] Token storage implementation
- [ ] Authentication screens (Login/Register)
- [ ] Navigation structure
- [ ] Basic error handling

### Phase 2: Store Owner Features (Week 2-3)
- [ ] Dashboard screen
- [ ] Inventory management
- [ ] Order item list
- [ ] Accept/Refuse/Suggest functionality
- [ ] Business hours management
- [ ] Profile screen

### Phase 3: Customer Features (Week 2-3)
- [ ] Product browsing
- [ ] Search & filters
- [ ] Product details
- [ ] Cart functionality
- [ ] Order creation
- [ ] Order tracking

### Phase 4: Polish (Week 4)
- [ ] Notifications (push & in-app)
- [ ] Pull-to-refresh
- [ ] Loading states
- [ ] Error messages
- [ ] Image optimization
- [ ] Offline support

### Phase 5: Testing & Deployment (Week 5)
- [ ] Unit tests
- [ ] Integration tests
- [ ] Beta testing
- [ ] Bug fixes
- [ ] App store submission

---

## 🐛 Common Issues & Solutions

### Issue: 401 Unauthorized After Some Time
**Cause:** JWT token expired  
**Solution:** Implement automatic token refresh (see Developer Guide)

### Issue: "Product not in store inventory"
**Cause:** Trying to accept product not in StoreProduct table  
**Solution:** Check store inventory, only accept/suggest available products

### Issue: Images Loading Slowly
**Cause:** Large image files, no caching  
**Solution:** Use react-native-fast-image, implement proper caching

### Issue: List Performance Poor
**Cause:** Inefficient FlatList usage  
**Solution:** Use optimization props (removeClippedSubviews, maxToRenderPerBatch, etc.)

**More solutions in:** `MOBILE_API_QUICK_REFERENCE.md` - Troubleshooting section

---

## 📱 Recommended Tech Stack

### React Native (Recommended)
```json
{
  "react-native": "^0.72.0",
  "react-navigation": "^6.0.0",
  "axios": "^1.5.0",
  "@tanstack/react-query": "^4.35.0",
  "react-native-fast-image": "^8.6.0",
  "react-native-keychain": "^8.1.0",
  "react-native-vector-icons": "^10.0.0"
}
```

### Flutter
```yaml
dependencies:
  flutter:
    sdk: flutter
  dio: ^5.3.0
  provider: ^6.0.5
  flutter_secure_storage: ^8.0.0
  cached_network_image: ^3.3.0
```

### Native iOS (Swift)
- SwiftUI
- Combine
- URLSession
- Keychain Services

### Native Android (Kotlin)
- Jetpack Compose
- Coroutines
- Retrofit
- Android Keystore

---

## 🔒 Security Checklist

Before Production:
- [ ] JWT stored in secure storage (not AsyncStorage)
- [ ] HTTPS only (SSL pinning recommended)
- [ ] Token refresh implemented
- [ ] 401 errors handled globally
- [ ] No sensitive data in logs
- [ ] Input validation on client
- [ ] Proper error messages (no stack traces to user)
- [ ] Rate limiting considered
- [ ] Biometric authentication (optional)
- [ ] App transport security configured

---

## 📞 Support & Resources

### Documentation Files
1. 📖 `MOBILE_STORE_API_COMPLETE.md` - Full API Reference
2. 👨‍💻 `MOBILE_APP_DEVELOPER_GUIDE.md` - Implementation Guide
3. ⚡ `MOBILE_API_QUICK_REFERENCE.md` - Quick Cheat Sheet
4. 🤖 `AI_CODING_ASSISTANT_PROMPT.md` - AI Helper Prompts

### Backend Documentation
- `STORE_ORDER_MANAGEMENT_IMPLEMENTATION.md` - Order system details
- `STORE_FEATURES_GUIDE.md` - Store features overview
- `STORE_PRODUCT_INVENTORY_SYSTEM.md` - Inventory system
- `DELIVERY_API_DOCUMENTATION.md` - Delivery features

### Contact
- **API Issues:** Check error codes in Quick Reference
- **Feature Questions:** See Developer Guide examples
- **AI Help:** Use AI Assistant Prompt
- **Support Email:** support@joypharma.com

---

## 🎓 Learning Path

### Beginner (New to Mobile Development)
1. Read API Complete doc (skim all sections)
2. Set up a simple project
3. Implement login only
4. Use AI Assistant for help
5. Build one feature at a time

### Intermediate (Some Mobile Experience)
1. Read API Complete & Developer Guide
2. Set up project with proper architecture
3. Implement authentication flow
4. Build store OR customer features
5. Add polish (loading, errors, etc.)

### Advanced (Experienced Developer)
1. Skim all docs for API understanding
2. Use Quick Reference as needed
3. Implement complete app (2-3 weeks)
4. Optimize performance
5. Write tests

---

## 🎯 Success Metrics

Your app should:
- ✅ Load products in < 2 seconds
- ✅ Handle 1000+ products smoothly
- ✅ Work offline (view cached data)
- ✅ Refresh tokens automatically
- ✅ Show clear error messages
- ✅ Support both user types seamlessly
- ✅ Handle poor network gracefully
- ✅ Pass app store review guidelines

---

## 🚀 Next Steps

1. **Read the docs** - Start with `MOBILE_STORE_API_COMPLETE.md`
2. **Set up environment** - Follow `MOBILE_APP_DEVELOPER_GUIDE.md`
3. **Keep reference handy** - Print `MOBILE_API_QUICK_REFERENCE.md`
4. **Use AI help** - Leverage `AI_CODING_ASSISTANT_PROMPT.md`
5. **Start coding!** 🎉

---

## 📝 Version History

- **v2.0** (Oct 28, 2025) - Complete mobile documentation suite
  - Added 4 comprehensive documents
  - 37+ API endpoints documented
  - React Native code examples
  - AI assistant prompts
  - Quick reference card

- **v1.0** (Oct 27, 2025) - Initial API implementation
  - Basic API endpoints
  - Store and customer features
  - Order management system

---

## 🙏 Credits

Built with ❤️ for Joy Pharma  
API Backend: Symfony 6 + API Platform  
Documentation: Markdown  

---

**Ready to build something amazing? Let's go! 🚀**

---

## 📚 Document Index

| Document | Purpose | Pages | Status |
|----------|---------|-------|--------|
| `MOBILE_STORE_API_COMPLETE.md` | Complete API Reference | ~100 | ✅ Complete |
| `MOBILE_APP_DEVELOPER_GUIDE.md` | Implementation Guide | ~80 | ✅ Complete |
| `MOBILE_API_QUICK_REFERENCE.md` | Quick Cheat Sheet | ~20 | ✅ Complete |
| `AI_CODING_ASSISTANT_PROMPT.md` | AI Helper Prompts | ~30 | ✅ Complete |
| `MOBILE_STORE_README.md` | This Document (Index) | ~15 | ✅ Complete |

**Total Documentation:** ~245 pages of comprehensive mobile app development documentation!

---

**Last Updated:** October 28, 2025  
**Documentation Version:** 2.0  
**API Version:** 2.0


