# 📱 Mobile Store App - Developer Guide & Integration Prompt

> **Complete integration guide for building a mobile pharmacy/store application**

---

## 🎯 Project Overview

Build a mobile application (React Native, Flutter, or Native) for a pharmacy delivery system with two main user types:
1. **Store Owners** - Manage inventory, accept/refuse orders, set availability
2. **Customers** - Browse products, place orders, track deliveries

---

## 🚀 Getting Started

### Prerequisites

- Mobile development environment (React Native, Flutter, or Native iOS/Android)
- API Base URL: `https://your-domain.com/api`
- Understanding of REST APIs and JWT authentication
- Knowledge of state management (Redux, MobX, Provider, etc.)

### Key Technologies

- **Authentication:** JWT (JSON Web Tokens)
- **API Format:** JSON REST API
- **Image Upload:** Multipart/form-data
- **Real-time Updates:** Polling or WebSockets (future)

---

## 📐 App Architecture

### Recommended Structure

```
mobile-app/
├── src/
│   ├── api/
│   │   ├── client.js           # API client configuration
│   │   ├── auth.js             # Authentication APIs
│   │   ├── products.js         # Product APIs
│   │   ├── orders.js           # Order APIs
│   │   ├── store.js            # Store management APIs
│   │   └── notifications.js    # Notification APIs
│   ├── screens/
│   │   ├── auth/
│   │   │   ├── LoginScreen.js
│   │   │   ├── RegisterScreen.js
│   │   │   └── ForgotPasswordScreen.js
│   │   ├── store/
│   │   │   ├── DashboardScreen.js
│   │   │   ├── InventoryScreen.js
│   │   │   ├── OrdersScreen.js
│   │   │   └── BusinessHoursScreen.js
│   │   ├── customer/
│   │   │   ├── HomeScreen.js
│   │   │   ├── ProductListScreen.js
│   │   │   ├── OrderScreen.js
│   │   │   └── OrderTrackingScreen.js
│   │   └── shared/
│   │       ├── ProfileScreen.js
│   │       └── NotificationsScreen.js
│   ├── components/
│   │   ├── ProductCard.js
│   │   ├── OrderCard.js
│   │   └── ...
│   ├── store/              # State management
│   ├── utils/
│   │   ├── storage.js      # Secure token storage
│   │   ├── validation.js
│   │   └── formatting.js
│   └── constants/
│       ├── colors.js
│       └── api.js
```

---

## 🔐 Authentication Implementation

### 1. Setup API Client

```javascript
// api/client.js
import axios from 'axios';
import { getToken, storeToken, removeToken, getRefreshToken } from '../utils/storage';

const API_BASE_URL = 'https://your-domain.com/api';

const apiClient = axios.create({
  baseURL: API_BASE_URL,
  timeout: 30000,
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
});

// Request interceptor - Add token
apiClient.interceptors.request.use(
  async (config) => {
    const token = await getToken();
    if (token) {
      config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
  },
  (error) => Promise.reject(error)
);

// Response interceptor - Handle token refresh
apiClient.interceptors.response.use(
  (response) => response,
  async (error) => {
    const originalRequest = error.config;

    // If 401 and not already retried
    if (error.response?.status === 401 && !originalRequest._retry) {
      originalRequest._retry = true;

      try {
        const refreshToken = await getRefreshToken();
        const response = await axios.post(`${API_BASE_URL}/token/refresh`, {
          refresh_token: refreshToken,
        });

        const { token, refresh_token } = response.data;
        await storeToken(token, refresh_token);

        originalRequest.headers.Authorization = `Bearer ${token}`;
        return apiClient(originalRequest);
      } catch (refreshError) {
        // Refresh failed - logout user
        await removeToken();
        // Navigate to login screen
        return Promise.reject(refreshError);
      }
    }

    return Promise.reject(error);
  }
);

export default apiClient;
```

### 2. Secure Token Storage

```javascript
// utils/storage.js
import * as SecureStore from 'expo-secure-store'; // For React Native with Expo
// OR
import AsyncStorage from '@react-native-async-storage/async-storage';
import { Platform } from 'react-native';

const TOKEN_KEY = 'jwt_token';
const REFRESH_TOKEN_KEY = 'refresh_token';

// Use SecureStore for tokens (iOS Keychain, Android Keystore)
export const storeToken = async (token, refreshToken) => {
  try {
    if (Platform.OS === 'web') {
      await AsyncStorage.setItem(TOKEN_KEY, token);
      await AsyncStorage.setItem(REFRESH_TOKEN_KEY, refreshToken);
    } else {
      await SecureStore.setItemAsync(TOKEN_KEY, token);
      await SecureStore.setItemAsync(REFRESH_TOKEN_KEY, refreshToken);
    }
  } catch (error) {
    console.error('Error storing token:', error);
  }
};

export const getToken = async () => {
  try {
    if (Platform.OS === 'web') {
      return await AsyncStorage.getItem(TOKEN_KEY);
    }
    return await SecureStore.getItemAsync(TOKEN_KEY);
  } catch (error) {
    console.error('Error getting token:', error);
    return null;
  }
};

export const getRefreshToken = async () => {
  try {
    if (Platform.OS === 'web') {
      return await AsyncStorage.getItem(REFRESH_TOKEN_KEY);
    }
    return await SecureStore.getItemAsync(REFRESH_TOKEN_KEY);
  } catch (error) {
    console.error('Error getting refresh token:', error);
    return null;
  }
};

export const removeToken = async () => {
  try {
    if (Platform.OS === 'web') {
      await AsyncStorage.multiRemove([TOKEN_KEY, REFRESH_TOKEN_KEY]);
    } else {
      await SecureStore.deleteItemAsync(TOKEN_KEY);
      await SecureStore.deleteItemAsync(REFRESH_TOKEN_KEY);
    }
  } catch (error) {
    console.error('Error removing token:', error);
  }
};
```

### 3. Authentication API Functions

```javascript
// api/auth.js
import apiClient from './client';
import { storeToken, removeToken } from '../utils/storage';

export const authAPI = {
  // Register Customer
  registerCustomer: async (userData) => {
    const response = await apiClient.post('/register', userData);
    const { token, refresh_token } = response.data;
    await storeToken(token, refresh_token);
    return response.data;
  },

  // Register Store Owner
  registerStore: async (storeData) => {
    const response = await apiClient.post('/register/store', storeData);
    const { token, refresh_token } = response.data;
    await storeToken(token, refresh_token);
    return response.data;
  },

  // Login
  login: async (email, password) => {
    const response = await apiClient.post('/auth', { email, password });
    const { token, refresh_token } = response.data;
    await storeToken(token, refresh_token);
    return response.data;
  },

  // Logout
  logout: async () => {
    try {
      await apiClient.post('/logout');
    } finally {
      await removeToken();
    }
  },

  // Get Current User
  getCurrentUser: async () => {
    const response = await apiClient.get('/me');
    return response.data;
  },

  // Social Login
  facebookLogin: async (accessToken) => {
    const response = await apiClient.post('/facebook_login', { accessToken });
    const { token, refresh_token } = response.data;
    await storeToken(token, refresh_token);
    return response.data;
  },

  googleLogin: async (accessToken) => {
    const response = await apiClient.post('/google_login', { accessToken });
    const { token, refresh_token } = response.data;
    await storeToken(token, refresh_token);
    return response.data;
  },

  // Password Reset
  forgotPassword: async (email) => {
    const response = await apiClient.post('/password/forgot', { email });
    return response.data;
  },

  verifyResetCode: async (email, code) => {
    const response = await apiClient.post('/password/verify-code', { email, code });
    return response.data;
  },

  resetPassword: async (email, code, password) => {
    const response = await apiClient.post('/password/reset', { email, code, password });
    return response.data;
  },

  updatePassword: async (currentPassword, newPassword, confirmPassword) => {
    const response = await apiClient.post('/user/update-password', {
      currentPassword,
      newPassword,
      confirmPassword,
    });
    return response.data;
  },
};
```

---

## 🏪 Store Owner Features

### Dashboard Screen

**Key Features:**
- Display store information
- Show pending order count
- Quick stats (today's orders, low stock items)
- Business hours status
- Quick actions (toggle open/close, view orders)

### Inventory Management

```javascript
// api/inventory.js
import apiClient from './client';

export const inventoryAPI = {
  // Get store products
  getStoreProducts: async (page = 1, filters = {}) => {
    const params = { page, limit: 50, ...filters };
    const response = await apiClient.get('/store_products', { params });
    return response.data;
  },

  // Update product
  updateStoreProduct: async (productId, updates) => {
    const response = await apiClient.put(`/store_products/${productId}`, updates);
    return response.data;
  },

  // Get low stock items
  getLowStockItems: async () => {
    const response = await apiClient.get('/store_products', {
      params: { lowStock: true },
    });
    return response.data;
  },
};
```

**UI Components:**
- Product list with search/filter
- Stock level indicators (color-coded)
- Quick edit modal for stock/price
- Bulk update functionality

### Order Management

```javascript
// api/orders.js
import apiClient from './client';

export const orderAPI = {
  // Get pending order items for store
  getPendingOrderItems: async (page = 1) => {
    const params = { page, limit: 50 };
    const response = await apiClient.get('/store/order-items/pending', { params });
    return response.data;
  },

  // Get all orders
  getOrders: async (page = 1, filters = {}) => {
    const params = { page, limit: 20, ...filters };
    const response = await apiClient.get('/orders', { params });
    return response.data;
  },

  // Get order by ID
  getOrderById: async (orderId) => {
    const response = await apiClient.get(`/order/${orderId}`);
    return response.data;
  },

  // Accept order item
  acceptOrderItem: async (orderItemId, notes) => {
    const response = await apiClient.post('/store/order-item/accept', {
      orderItemId,
      notes,
    });
    return response.data;
  },

  // Refuse order item
  refuseOrderItem: async (orderItemId, reason) => {
    const response = await apiClient.post('/store/order-item/refuse', {
      orderItemId,
      reason,
    });
    return response.data;
  },

  // Suggest alternative
  suggestAlternative: async (orderItemId, suggestedProductId, suggestion, notes) => {
    const response = await apiClient.post('/store/order-item/suggest', {
      orderItemId,
      suggestedProductId,
      suggestion,
      notes,
    });
    return response.data;
  },
};
```

**Order Item Card Component:**
```javascript
// components/OrderItemCard.js
import React from 'react';
import { View, Text, TouchableOpacity, StyleSheet } from 'react-native';

const OrderItemCard = ({ orderItem, onAccept, onRefuse, onSuggest }) => {
  const { order, product, quantity, notes, storeStatus } = orderItem;

  return (
    <View style={styles.card}>
      <View style={styles.header}>
        <Text style={styles.orderRef}>{order.reference}</Text>
        <StatusBadge status={storeStatus} />
      </View>

      <View style={styles.productInfo}>
        <Image source={{ uri: product.images[0]?.url }} style={styles.image} />
        <View style={styles.details}>
          <Text style={styles.productName}>{product.name}</Text>
          <Text style={styles.quantity}>Quantity: {quantity}</Text>
          {notes && <Text style={styles.notes}>Notes: {notes}</Text>}
        </View>
      </View>

      <View style={styles.customerInfo}>
        <Text>Customer: {order.owner.firstName} {order.owner.lastName}</Text>
        <Text>Phone: {order.phone}</Text>
        <Text>Delivery: {formatDate(order.scheduledDate)}</Text>
      </View>

      {storeStatus === 'pending' && (
        <View style={styles.actions}>
          <TouchableOpacity
            style={[styles.button, styles.acceptButton]}
            onPress={() => onAccept(orderItem.id)}
          >
            <Text style={styles.buttonText}>Accept</Text>
          </TouchableOpacity>

          <TouchableOpacity
            style={[styles.button, styles.refuseButton]}
            onPress={() => onRefuse(orderItem.id)}
          >
            <Text style={styles.buttonText}>Refuse</Text>
          </TouchableOpacity>

          <TouchableOpacity
            style={[styles.button, styles.suggestButton]}
            onPress={() => onSuggest(orderItem.id)}
          >
            <Text style={styles.buttonText}>Suggest Alt.</Text>
          </TouchableOpacity>
        </View>
      )}
    </View>
  );
};

const styles = StyleSheet.create({
  card: {
    backgroundColor: 'white',
    borderRadius: 12,
    padding: 16,
    marginBottom: 12,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.1,
    shadowRadius: 4,
    elevation: 3,
  },
  header: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 12,
  },
  orderRef: {
    fontSize: 16,
    fontWeight: 'bold',
    color: '#333',
  },
  productInfo: {
    flexDirection: 'row',
    marginBottom: 12,
  },
  image: {
    width: 60,
    height: 60,
    borderRadius: 8,
    marginRight: 12,
  },
  details: {
    flex: 1,
  },
  productName: {
    fontSize: 16,
    fontWeight: '600',
    color: '#333',
    marginBottom: 4,
  },
  quantity: {
    fontSize: 14,
    color: '#666',
  },
  notes: {
    fontSize: 12,
    color: '#999',
    marginTop: 4,
  },
  customerInfo: {
    backgroundColor: '#f5f5f5',
    padding: 12,
    borderRadius: 8,
    marginBottom: 12,
  },
  actions: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    gap: 8,
  },
  button: {
    flex: 1,
    paddingVertical: 12,
    borderRadius: 8,
    alignItems: 'center',
  },
  acceptButton: {
    backgroundColor: '#4CAF50',
  },
  refuseButton: {
    backgroundColor: '#f44336',
  },
  suggestButton: {
    backgroundColor: '#FF9800',
  },
  buttonText: {
    color: 'white',
    fontWeight: '600',
    fontSize: 14,
  },
});

export default OrderItemCard;
```

### Business Hours Management

```javascript
// api/businessHours.js
import apiClient from './client';

export const businessHoursAPI = {
  // Get business hours
  getBusinessHours: async () => {
    const response = await apiClient.get('/store/business-hours');
    return response.data;
  },

  // Update business hours
  updateBusinessHours: async (businessHours) => {
    const response = await apiClient.put('/store/business-hours', { businessHours });
    return response.data;
  },

  // Toggle store open/close
  toggleStoreStatus: async (isOpen, reason = '') => {
    const response = await apiClient.put('/store/toggle-status', { isOpen, reason });
    return response.data;
  },
};
```

---

## 🛍️ Customer Features

### Product Browsing

```javascript
// api/products.js
import apiClient from './client';

export const productAPI = {
  // Get all products
  getProducts: async (page = 1, filters = {}) => {
    const params = { page, limit: 20, ...filters };
    const response = await apiClient.get('/products', { params });
    return response.data;
  },

  // Get product by ID
  getProductById: async (productId) => {
    const response = await apiClient.get(`/product/${productId}`);
    return response.data;
  },

  // Get suggestions
  getSuggestions: async () => {
    const response = await apiClient.get('/products/suggestion');
    return response.data;
  },

  // Get categories
  getCategories: async () => {
    const response = await apiClient.get('/category');
    return response.data;
  },
};
```

### Order Creation

```javascript
// screens/customer/CreateOrderScreen.js
import React, { useState } from 'react';
import { View, ScrollView, TextInput, TouchableOpacity, Text } from 'react-native';
import { orderAPI } from '../../api/orders';

const CreateOrderScreen = ({ navigation, route }) => {
  const { cartItems } = route.params; // Cart items from previous screen
  const [locationId, setLocationId] = useState(null);
  const [phone, setPhone] = useState('');
  const [notes, setNotes] = useState('');
  const [scheduledDate, setScheduledDate] = useState(null);
  const [loading, setLoading] = useState(false);

  const handleCreateOrder = async () => {
    try {
      setLoading(true);

      const orderData = {
        locationId,
        phone,
        notes,
        scheduledDate: scheduledDate?.toISOString(),
        priority: 'standard',
        items: cartItems.map(item => ({
          productId: item.product.id,
          quantity: item.quantity,
          notes: item.notes || '',
        })),
      };

      const response = await orderAPI.createOrder(orderData);

      // Show success message
      Alert.alert('Success', 'Order created successfully!');

      // Navigate to order tracking
      navigation.navigate('OrderTracking', { orderId: response.id });
    } catch (error) {
      Alert.alert('Error', error.response?.data?.message || 'Failed to create order');
    } finally {
      setLoading(false);
    }
  };

  return (
    <ScrollView style={styles.container}>
      {/* Location Selection */}
      <LocationPicker value={locationId} onChange={setLocationId} />

      {/* Phone Number */}
      <TextInput
        style={styles.input}
        placeholder="Phone Number"
        value={phone}
        onChangeText={setPhone}
        keyboardType="phone-pad"
      />

      {/* Scheduled Date */}
      <DateTimePicker value={scheduledDate} onChange={setScheduledDate} />

      {/* Notes */}
      <TextInput
        style={[styles.input, styles.textArea]}
        placeholder="Delivery notes (optional)"
        value={notes}
        onChangeText={setNotes}
        multiline
        numberOfLines={4}
      />

      {/* Order Summary */}
      <OrderSummary items={cartItems} />

      {/* Create Button */}
      <TouchableOpacity
        style={styles.createButton}
        onPress={handleCreateOrder}
        disabled={loading || !locationId || !phone}
      >
        <Text style={styles.buttonText}>
          {loading ? 'Creating...' : 'Create Order'}
        </Text>
      </TouchableOpacity>
    </ScrollView>
  );
};

// In api/orders.js, add:
export const orderAPI = {
  // ... other methods

  createOrder: async (orderData) => {
    const response = await apiClient.post('/order', orderData);
    return response.data;
  },
};
```

---

## 🔔 Notifications Implementation

### Setup Push Notifications

```javascript
// utils/notifications.js
import * as Notifications from 'expo-notifications';
import { Platform } from 'react-native';

// Configure notifications
Notifications.setNotificationHandler({
  handleNotification: async () => ({
    shouldShowAlert: true,
    shouldPlaySound: true,
    shouldSetBadge: true,
  }),
});

export const registerForPushNotifications = async () => {
  let token;

  if (Platform.OS === 'android') {
    await Notifications.setNotificationChannelAsync('default', {
      name: 'default',
      importance: Notifications.AndroidImportance.MAX,
      vibrationPattern: [0, 250, 250, 250],
      lightColor: '#FF231F7C',
    });
  }

  const { status: existingStatus } = await Notifications.getPermissionsAsync();
  let finalStatus = existingStatus;

  if (existingStatus !== 'granted') {
    const { status } = await Notifications.requestPermissionsAsync();
    finalStatus = status;
  }

  if (finalStatus !== 'granted') {
    alert('Failed to get push token for push notification!');
    return;
  }

  token = (await Notifications.getExpoPushTokenAsync()).data;
  return token;
};

// Listen for notifications
export const addNotificationListener = (callback) => {
  return Notifications.addNotificationReceivedListener(callback);
};

export const addNotificationResponseListener = (callback) => {
  return Notifications.addNotificationResponseReceivedListener(callback);
};
```

### Polling for Updates

```javascript
// hooks/useOrderPolling.js
import { useEffect, useRef } from 'react';
import { orderAPI } from '../api/orders';

export const useOrderPolling = (orderId, interval = 30000) => {
  const [order, setOrder] = useState(null);
  const [loading, setLoading] = useState(false);
  const intervalRef = useRef(null);

  const fetchOrder = async () => {
    try {
      setLoading(true);
      const data = await orderAPI.getOrderById(orderId);
      setOrder(data);
    } catch (error) {
      console.error('Error fetching order:', error);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchOrder(); // Initial fetch

    // Start polling
    intervalRef.current = setInterval(fetchOrder, interval);

    // Cleanup
    return () => {
      if (intervalRef.current) {
        clearInterval(intervalRef.current);
      }
    };
  }, [orderId, interval]);

  return { order, loading, refetch: fetchOrder };
};
```

---

## 🎨 UI/UX Best Practices

### Color Scheme

```javascript
// constants/colors.js
export const Colors = {
  // Primary
  primary: '#00BFA5',
  primaryDark: '#00897B',
  primaryLight: '#B2DFDB',

  // Status Colors
  success: '#4CAF50',
  warning: '#FF9800',
  error: '#f44336',
  info: '#2196F3',

  // Order Status
  pending: '#FFC107',
  confirmed: '#2196F3',
  processing: '#9C27B0',
  shipped: '#FF5722',
  delivered: '#4CAF50',
  cancelled: '#757575',

  // Store Status
  accepted: '#4CAF50',
  refused: '#f44336',
  suggested: '#FF9800',
  approved: '#2196F3',

  // Neutral
  text: '#212121',
  textSecondary: '#757575',
  textLight: '#BDBDBD',
  background: '#FAFAFA',
  surface: '#FFFFFF',
  border: '#E0E0E0',
};
```

### Status Badges

```javascript
// components/StatusBadge.js
import React from 'react';
import { View, Text, StyleSheet } from 'react-native';
import { Colors } from '../constants/colors';

const StatusBadge = ({ status, type = 'order' }) => {
  const getStatusColor = () => {
    if (type === 'order') {
      return Colors[status] || Colors.pending;
    }
    if (type === 'store') {
      return Colors[status] || Colors.pending;
    }
  };

  const getStatusText = () => {
    return status.charAt(0).toUpperCase() + status.slice(1);
  };

  return (
    <View style={[styles.badge, { backgroundColor: getStatusColor() }]}>
      <Text style={styles.text}>{getStatusText()}</Text>
    </View>
  );
};

const styles = StyleSheet.create({
  badge: {
    paddingHorizontal: 12,
    paddingVertical: 6,
    borderRadius: 12,
  },
  text: {
    color: 'white',
    fontSize: 12,
    fontWeight: '600',
  },
});

export default StatusBadge;
```

### Loading States

```javascript
// components/LoadingOverlay.js
import React from 'react';
import { View, ActivityIndicator, StyleSheet } from 'react-native';
import { Colors } from '../constants/colors';

const LoadingOverlay = ({ visible }) => {
  if (!visible) return null;

  return (
    <View style={styles.overlay}>
      <ActivityIndicator size="large" color={Colors.primary} />
    </View>
  );
};

const styles = StyleSheet.create({
  overlay: {
    ...StyleSheet.absoluteFillObject,
    backgroundColor: 'rgba(0,0,0,0.5)',
    justifyContent: 'center',
    alignItems: 'center',
    zIndex: 999,
  },
});

export default LoadingOverlay;
```

---

## ⚡ Performance Optimization

### Image Optimization

```javascript
// components/OptimizedImage.js
import React from 'react';
import { Image } from 'react-native';
import FastImage from 'react-native-fast-image'; // Recommended library

const OptimizedImage = ({ source, style, ...props }) => {
  // Use FastImage for better caching and performance
  return (
    <FastImage
      source={typeof source === 'string' ? { uri: source } : source}
      style={style}
      resizeMode={FastImage.resizeMode.cover}
      {...props}
    />
  );
};

export default OptimizedImage;
```

### List Optimization

```javascript
// screens/ProductListScreen.js
import React from 'react';
import { FlatList } from 'react-native';
import ProductCard from '../components/ProductCard';

const ProductListScreen = () => {
  const [products, setProducts] = useState([]);
  const [page, setPage] = useState(1);
  const [loading, setLoading] = useState(false);
  const [hasMore, setHasMore] = useState(true);

  const loadProducts = async () => {
    if (loading || !hasMore) return;

    try {
      setLoading(true);
      const response = await productAPI.getProducts(page);
      
      setProducts(prev => [...prev, ...response.data]);
      setHasMore(response.pagination.currentPage < response.pagination.totalPages);
      setPage(prev => prev + 1);
    } catch (error) {
      console.error('Error loading products:', error);
    } finally {
      setLoading(false);
    }
  };

  const renderItem = ({ item }) => <ProductCard product={item} />;

  const keyExtractor = (item) => `product-${item.id}`;

  return (
    <FlatList
      data={products}
      renderItem={renderItem}
      keyExtractor={keyExtractor}
      onEndReached={loadProducts}
      onEndReachedThreshold={0.5}
      ListFooterComponent={loading && <ActivityIndicator />}
      removeClippedSubviews={true}
      maxToRenderPerBatch={10}
      updateCellsBatchingPeriod={50}
      initialNumToRender={10}
      windowSize={10}
    />
  );
};
```

---

## 🧪 Testing

### API Testing

```javascript
// __tests__/api/auth.test.js
import { authAPI } from '../../api/auth';
import apiClient from '../../api/client';

jest.mock('../../api/client');

describe('Auth API', () => {
  beforeEach(() => {
    jest.clearAllMocks();
  });

  test('login should return token', async () => {
    const mockResponse = {
      data: {
        token: 'mock_token',
        refresh_token: 'mock_refresh',
      },
    };

    apiClient.post.mockResolvedValue(mockResponse);

    const result = await authAPI.login('test@example.com', 'password');

    expect(apiClient.post).toHaveBeenCalledWith('/auth', {
      email: 'test@example.com',
      password: 'password',
    });
    expect(result).toEqual(mockResponse.data);
  });

  test('login should handle error', async () => {
    const mockError = {
      response: {
        data: { message: 'Invalid credentials' },
      },
    };

    apiClient.post.mockRejectedValue(mockError);

    await expect(
      authAPI.login('test@example.com', 'wrong')
    ).rejects.toThrow();
  });
});
```

---

## 📱 Platform-Specific Considerations

### iOS

- Use Keychain for token storage
- Handle safe area insets
- Configure Info.plist for permissions

### Android

- Use Keystore for token storage
- Handle back button navigation
- Configure AndroidManifest.xml for permissions
- Handle notification channels

---

## 🔍 Debugging Tips

### API Request Logging

```javascript
// api/client.js (add to interceptors)
apiClient.interceptors.request.use(
  (config) => {
    if (__DEV__) {
      console.log('API Request:', {
        method: config.method.toUpperCase(),
        url: config.url,
        data: config.data,
      });
    }
    return config;
  }
);

apiClient.interceptors.response.use(
  (response) => {
    if (__DEV__) {
      console.log('API Response:', {
        url: response.config.url,
        status: response.status,
        data: response.data,
      });
    }
    return response;
  },
  (error) => {
    if (__DEV__) {
      console.error('API Error:', {
        url: error.config?.url,
        status: error.response?.status,
        data: error.response?.data,
      });
    }
    return Promise.reject(error);
  }
);
```

---

## 📦 Recommended Libraries

### React Native

```json
{
  "dependencies": {
    "react-native": "^0.72.0",
    "react-navigation": "^6.0.0",
    "axios": "^1.5.0",
    "react-native-fast-image": "^8.6.0",
    "expo-secure-store": "^12.3.0",
    "@react-native-async-storage/async-storage": "^1.19.0",
    "expo-notifications": "^0.20.0",
    "react-native-vector-icons": "^10.0.0",
    "react-native-modal": "^13.0.0",
    "react-native-paper": "^5.10.0"
  }
}
```

### State Management

- Redux Toolkit
- MobX
- Zustand
- React Query (for API state)

---

## 🚀 Deployment Checklist

- [ ] Environment variables configured
- [ ] API base URL set correctly
- [ ] SSL pinning enabled
- [ ] Error tracking setup (Sentry, etc.)
- [ ] Analytics integrated
- [ ] Push notifications configured
- [ ] App icons and splash screens
- [ ] Store listings prepared
- [ ] Privacy policy and terms
- [ ] Beta testing completed

---

## 📞 Support & Resources

- API Documentation: See `MOBILE_STORE_API_COMPLETE.md`
- Backend Repository: [Link]
- Design Assets: [Figma Link]
- Support Email: support@joypharma.com

---

**Happy Coding! 🎉**


