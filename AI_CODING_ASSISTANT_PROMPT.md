# 🤖 AI Coding Assistant Prompt - Mobile Pharmacy Store App

> **Use this prompt with ChatGPT, Claude, or any AI coding assistant to get help building the mobile app**

---

## 📋 Copy-Paste This Prompt

```
I'm building a mobile pharmacy/store delivery application using [React Native/Flutter/Native iOS/Native Android]. 
The app has two main user types: Store Owners and Customers.

API DETAILS:
- Base URL: https://your-domain.com/api
- Authentication: JWT Bearer tokens
- Format: JSON REST API
- All endpoints documented in MOBILE_STORE_API_COMPLETE.md

KEY FEATURES:

Store Owner Features:
1. Register/Login as store owner with store details
2. Manage product inventory (stock, pricing)
3. View incoming orders and order items
4. Accept, refuse, or suggest alternatives for order items
5. Manage business hours (open/close times per day)
6. Toggle store open/closed status
7. View notifications
8. Update profile

Customer Features:
1. Register/Login as customer
2. Browse products by category
3. Search products
4. Create orders with multiple items
5. Track order status
6. View order history
7. Receive notifications
8. Update profile

TECHNICAL REQUIREMENTS:
- Secure JWT token storage (Keychain/Keystore)
- Token refresh on 401
- Image caching and optimization
- Pagination for lists
- Error handling with user-friendly messages
- Loading states
- Pull-to-refresh
- Offline support (basic)
- Push notifications

API AUTHENTICATION:
- POST /register - Register customer
- POST /register/store - Register store owner
- POST /auth - Login (returns JWT token)
- POST /token/refresh - Refresh token
- GET /me - Get current user

MAIN ENDPOINTS:
Store Owner:
- GET /store_products - Get store inventory
- PUT /store_products/{id} - Update stock/price
- GET /store/order-items/pending - Get pending items
- POST /store/order-item/accept - Accept item
- POST /store/order-item/refuse - Refuse item
- POST /store/order-item/suggest - Suggest alternative
- GET /store/business-hours - Get hours
- PUT /store/business-hours - Update hours

Customer:
- GET /products - Browse products
- GET /category - Get categories
- POST /order - Create order
- GET /order/{id} - Get order details
- GET /orders - Order history

IMPORTANT BUSINESS RULES:
1. Store can ONLY accept products that exist in their inventory (store_products table)
2. When accepting order items, price is automatically fetched from inventory
3. When suggesting alternatives, suggested product must be in store's inventory
4. Orders have status: pending, confirmed, processing, shipped, delivered, cancelled
5. Order items have store status: pending, accepted, refused, suggested, approved

COMMON ERRORS TO HANDLE:
- 401: Unauthorized (token expired, need refresh)
- 400: Bad request (validation errors)
- 404: Not found
- 409: Conflict (email already exists)

Please help me with: [YOUR SPECIFIC QUESTION/TASK HERE]
```

---

## 🎯 Specific Use Cases

### Use Case 1: Setting Up Authentication

**Prompt:**
```
Using the pharmacy store API described above, help me implement:

1. Secure token storage using react-native-keychain
2. API client with Axios that:
   - Adds JWT token to all requests
   - Handles 401 errors with automatic token refresh
   - Logs requests in development mode
3. Login screen with email/password
4. Registration flow for both customers and store owners

Please provide complete code examples with proper error handling and TypeScript types.
```

### Use Case 2: Store Owner Dashboard

**Prompt:**
```
Using the pharmacy store API described above, help me build a Store Owner Dashboard that shows:

1. Store information (name, address, hours)
2. Statistics card:
   - Number of pending order items
   - Today's accepted orders count
   - Low stock items count
3. Quick actions:
   - Toggle store open/close
   - View pending orders
   - Manage inventory
4. Real-time updates using polling (every 30 seconds)

Use React Native with hooks and proper state management. Include loading states and error handling.
```

### Use Case 3: Order Item Management

**Prompt:**
```
Using the pharmacy store API described above, help me build an Order Item Management screen for store owners that:

1. Shows a list of pending order items
2. Each card displays:
   - Order reference and customer info
   - Product name, image, quantity
   - Customer notes
3. Action buttons for each item:
   - Accept (shows confirmation modal)
   - Refuse (shows reason input modal)
   - Suggest Alternative (shows product picker from store inventory)
4. After action, updates UI and shows success message
5. Includes pull-to-refresh

Important: When accepting, don't ask for price - it's fetched automatically from store inventory.
When suggesting, only show products available in store's inventory.

Provide complete implementation with modal components and API integration.
```

### Use Case 4: Product Browsing

**Prompt:**
```
Using the pharmacy store API described above, help me build a Product Browsing screen for customers that includes:

1. Category filter (horizontal scrollable list)
2. Search bar with debounced search
3. Product grid/list with:
   - Product image, name
   - Price range (from different stores)
   - Availability indicator
4. Infinite scroll pagination
5. Product detail modal on tap
6. Add to cart functionality

Use FlatList for performance with proper optimization (removeClippedSubviews, maxToRenderPerBatch, etc.).
Include skeleton loading states.
```

### Use Case 5: Order Creation Flow

**Prompt:**
```
Using the pharmacy store API described above, help me implement the complete order creation flow:

1. Cart management (add/remove items, update quantity)
2. Location selection (from user's saved locations)
3. Delivery date/time picker
4. Order notes input
5. Order summary with total
6. Submit order button
7. Success screen with order reference

Handle validation:
- At least 1 item required
- Location required
- Phone number required
- Show total calculation

After successful creation, navigate to order tracking screen.
Provide complete flow with proper navigation.
```

### Use Case 6: Inventory Management

**Prompt:**
```
Using the pharmacy store API described above, help me build an Inventory Management screen for store owners:

1. List of store's products with:
   - Product image, name, code
   - Current stock (color-coded: red<10, yellow 10-50, green >50)
   - Unit price and total price
   - Active/Inactive toggle
2. Quick edit functionality:
   - Tap to edit stock, prices, status
   - Inline editing or modal
   - Save changes via API
3. Search/filter products
4. Low stock filter toggle
5. Pull-to-refresh

Include optimistic updates for better UX.
Handle errors gracefully with rollback.
```

### Use Case 7: Business Hours Management

**Prompt:**
```
Using the pharmacy store API described above, help me create a Business Hours Management screen:

1. List all 7 days of the week
2. For each day:
   - Toggle for open/closed
   - Time pickers for open time and close time
   - Disabled time pickers when closed
3. Save all button (updates all days at once)
4. Visual representation:
   - Green for open days
   - Gray for closed days
   - Show times clearly
5. Validation:
   - Close time must be after open time
   - Show user-friendly error messages

Make it intuitive with good UX. Include loading state while saving.
```

### Use Case 8: Notification System

**Prompt:**
```
Using the pharmacy store API described above, help me implement a complete notification system:

1. Notification list screen:
   - Unread badge on tab icon
   - List with unread highlighted
   - Tap to mark as read and navigate to relevant screen
   - Pull-to-refresh
   - Mark all as read button
2. Push notification setup:
   - Request permissions
   - Handle notification received (foreground)
   - Handle notification tapped (background/killed)
   - Navigate to correct screen based on notification type
3. Polling for new notifications:
   - Check every 60 seconds when app is active
   - Show badge count on app icon
   - Show in-app banner for new notifications

Handle different notification types:
- new_order: Navigate to order details
- order_accepted: Navigate to order tracking
- order_refused: Navigate to order details
- low_stock: Navigate to inventory
```

### Use Case 9: Error Handling & Loading States

**Prompt:**
```
Help me implement a comprehensive error handling and loading state system for the pharmacy store app:

1. Global error boundary component
2. Network error detection and retry
3. API error handling with user-friendly messages:
   - 400: Show validation errors
   - 401: Trigger token refresh
   - 403: Show permission denied
   - 404: Show not found
   - 500: Show generic error
4. Loading states:
   - Full-screen loader for initial loads
   - Skeleton screens for lists
   - Inline loaders for button actions
   - Pull-to-refresh loaders
5. Offline detection:
   - Show offline banner
   - Queue failed requests
   - Auto-retry when back online
6. Toast/Snackbar for success messages

Provide reusable components and hooks.
```

### Use Case 10: Complete Navigation Structure

**Prompt:**
```
Using the pharmacy store API described above, help me set up the complete navigation structure:

NAVIGATION STACK:
1. Auth Stack (not logged in):
   - Login
   - Register (Customer/Store selection)
   - Forgot Password
   - Reset Password

2. Store Owner Stack (logged in as store):
   - Dashboard (home)
   - Pending Orders
   - All Orders
   - Inventory
   - Business Hours
   - Profile
   - Notifications

3. Customer Stack (logged in as customer):
   - Home (product browse)
   - Product Details
   - Cart
   - Checkout
   - Order Tracking
   - Order History
   - Profile
   - Notifications

4. Shared:
   - Settings
   - Support/Help

Use React Navigation 6 with proper TypeScript types.
Include deep linking for notifications.
Handle auth state persistence.
Show different stacks based on user type (customer vs store).
```

---

## 💡 Tips for Using This Prompt

### 1. Be Specific About Your Stack
Replace `[React Native/Flutter/Native iOS/Native Android]` with your actual stack:
- "React Native with TypeScript and Redux Toolkit"
- "Flutter with Provider for state management"
- "Native iOS with Swift and SwiftUI"
- "Native Android with Kotlin and Jetpack Compose"

### 2. Include Your Current Code
When asking for help with existing code, include:
```
Here's my current implementation:
[paste your code]

The issue is: [describe problem]
```

### 3. Ask for Testing Help
```
Help me write unit tests for the above authentication logic using Jest and React Native Testing Library.
```

### 4. Request TypeScript Types
```
Provide TypeScript interfaces for all API request/response types.
```

### 5. Ask for Accessibility
```
Make sure all components follow accessibility best practices (screen reader support, proper labels, etc.).
```

---

## 🎨 Extend the Prompt for Design

Add this to your prompt for UI/UX help:

```
DESIGN REQUIREMENTS:
- Modern, clean interface
- Color scheme: Primary #00BFA5, Success #4CAF50, Error #f44336
- Use Material Design / iOS Human Interface Guidelines
- Responsive layouts
- Dark mode support
- Smooth animations and transitions
- Consistent spacing (8px grid system)

Status colors:
- Pending: #FFC107 (amber)
- Accepted: #4CAF50 (green)
- Refused: #f44336 (red)
- Suggested: #FF9800 (orange)

Please provide:
1. Component implementation with styles
2. Responsive layout considerations
3. Accessibility features
4. Animation suggestions
```

---

## 🔧 Troubleshooting Prompts

### Debug API Issues
```
I'm getting this error when calling [ENDPOINT]:
[paste error]

My request code:
[paste code]

API Documentation says it should work like this:
[paste relevant API doc section]

Help me debug this issue.
```

### Performance Issues
```
My product list is very slow when scrolling. I'm using FlatList.
Current implementation:
[paste code]

How can I optimize this for better performance?
```

### State Management Issues
```
I'm having trouble managing state for [feature].
Current approach:
[describe/paste code]

The issue is: [describe problem]

Should I use Context, Redux, or something else? Help me implement the best solution.
```

---

## 📚 Example Conversation Flow

**You:**
```
[Paste the main prompt above]

Please help me set up the initial project structure and authentication flow for React Native with TypeScript.
```

**AI Assistant:**
Will provide complete code for:
- Project structure
- API client setup
- Token storage
- Authentication screens
- Navigation setup

**You:**
```
Great! Now help me build the Store Owner Dashboard screen based on the API above.
```

**AI Assistant:**
Will provide:
- Dashboard component
- API integration
- UI components
- State management

**You:**
```
Perfect! Can you also add pull-to-refresh and show me how to handle errors?
```

**AI Assistant:**
Will enhance the code with:
- Pull-to-refresh implementation
- Error handling
- Loading states
- User feedback

---

## 🚀 Quick Start Examples

### Example 1: "Help me get started"
```
Using the pharmacy store API described above, I'm starting from scratch with React Native and TypeScript. 

Help me:
1. Initialize the project with proper structure
2. Install necessary dependencies
3. Set up API client with authentication
4. Create login screen
5. Set up navigation with auth flow

Provide step-by-step instructions with all code needed.
```

### Example 2: "Help me with specific feature"
```
Using the pharmacy store API described above, I need to implement the order item accept/refuse feature.

Requirements:
- Show pending order items in a list
- Each item has Accept and Refuse buttons
- Accept: Just send request (no price input needed)
- Refuse: Show modal to enter reason
- Update UI after action
- Show success/error toasts

I'm using React Native with React Query for data fetching.
Provide complete implementation.
```

### Example 3: "Help me debug"
```
Using the pharmacy store API described above, I'm trying to accept an order item but getting this error:

Error: "This product is not available in your store"

My code:
[paste code]

The product does exist in the catalog. What am I missing?
```

---

## 📱 Platform-Specific Prompts

### For React Native
```
I'm using React Native with:
- React Navigation 6
- Redux Toolkit for state
- React Query for API calls
- TypeScript
- React Native Paper for UI

[Your question about the pharmacy app]
```

### For Flutter
```
I'm using Flutter with:
- Provider for state management
- Dio for HTTP requests
- Flutter Secure Storage for tokens
- Go Router for navigation

[Your question about the pharmacy app]
```

### For Native iOS
```
I'm building native iOS with:
- SwiftUI
- Combine for reactive programming
- URLSession for networking
- Keychain for secure storage

[Your question about the pharmacy app]
```

### For Native Android
```
I'm building native Android with:
- Kotlin
- Jetpack Compose
- Retrofit for networking
- Hilt for dependency injection
- Keystore for secure storage

[Your question about the pharmacy app]
```

---

## ✅ Checklist: Ask AI to Help You With

- [ ] Project setup and structure
- [ ] Authentication flow
- [ ] API client configuration
- [ ] Store owner dashboard
- [ ] Inventory management
- [ ] Order management
- [ ] Product browsing
- [ ] Order creation
- [ ] Notifications
- [ ] Business hours management
- [ ] Profile management
- [ ] Error handling
- [ ] Loading states
- [ ] Pull-to-refresh
- [ ] Pagination
- [ ] Image optimization
- [ ] Offline support
- [ ] Push notifications
- [ ] Deep linking
- [ ] Testing setup
- [ ] CI/CD pipeline
- [ ] App deployment

---

## 🎓 Learning Resources

Ask AI:
```
Help me understand how JWT authentication works in mobile apps with this API.
```

```
Explain the best practices for managing API state in [your framework].
```

```
What's the difference between Context API and Redux for this use case?
```

```
How should I structure my code for maintainability as the app grows?
```

---

## 📞 Need More Help?

If the AI assistant doesn't give you what you need:

1. **Be more specific** - Include exact error messages, current code, expected behavior
2. **Break it down** - Ask for one thing at a time
3. **Provide context** - Share relevant parts of your existing code
4. **Ask for alternatives** - "What are other ways to implement this?"
5. **Request explanations** - "Can you explain why this approach is better?"

---

**Happy coding with AI assistance! 🤖✨**

