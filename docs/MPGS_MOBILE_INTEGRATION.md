# MPGS Mobile Integration Guide - React Native Expo

This guide explains how to integrate MPGS (Mastercard Payment Gateway Services) payment in a React Native Expo mobile application.

## Overview

The MPGS payment flow consists of:
1. **Create Payment Intent** - Get a checkout session from your backend
2. **Initialize MPGS Checkout** - Use the MPGS JavaScript SDK in a WebView
3. **Process Payment** - User enters card details in the hosted checkout
4. **Verify Payment** - Confirm payment status with your backend

---

## Prerequisites

- React Native Expo app
- Backend API endpoint: `POST /api/create-payment-intent`
- MPGS merchant credentials (configured in backend)

---

## Step 1: Install Dependencies

```bash
npm install react-native-webview expo-web-browser
# or
yarn add react-native-webview expo-web-browser
```

---

## Step 2: Create Payment Intent Service

Create a service file to communicate with your backend:

```typescript
// services/paymentService.ts

interface CreatePaymentIntentRequest {
  method: 'mpgs';
  amount: string;
  reference: string;
}

interface PaymentIntentResponse {
  id: string;
  clientSecret: string;
  status: string;
  provider: string;
  reference: string;
  sessionId: string;
  sessionVersion: string;
  successIndicator: string;
  orderId: string;
}

const API_BASE_URL = process.env.EXPO_PUBLIC_API_URL || 'https://your-api.com';

export const createPaymentIntent = async (
  amount: string,
  reference: string,
  token: string
): Promise<PaymentIntentResponse> => {
  const response = await fetch(`${API_BASE_URL}/api/create-payment-intent`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'Authorization': `Bearer ${token}`,
    },
    body: JSON.stringify({
      method: 'mpgs',
      amount,
      reference,
    }),
  });

  if (!response.ok) {
    const error = await response.json();
    throw new Error(error.detail || 'Failed to create payment intent');
  }

  return response.json();
};
```

---

## Step 3: MPGS Checkout Component

Create a component that uses WebView to display MPGS hosted checkout:

```typescript
// components/MPGSCheckout.tsx

import React, { useRef, useState } from 'react';
import { View, StyleSheet, ActivityIndicator, Alert } from 'react-native';
import { WebView } from 'react-native-webview';

interface MPGSCheckoutProps {
  sessionId: string;
  sessionVersion: string;
  successIndicator: string;
  orderId: string;
  merchantId: string;
  amount: number;
  currency: string;
  onSuccess: (orderId: string) => void;
  onError: (error: string) => void;
  onCancel: () => void;
}

const MPGS_CHECKOUT_JS_URL = process.env.EXPO_PUBLIC_MPGS_CHECKOUT_JS_URL || 
  'https://your-gateway-url/checkout/version/45/checkout.js';

export const MPGSCheckout: React.FC<MPGSCheckoutProps> = ({
  sessionId,
  sessionVersion,
  successIndicator,
  orderId,
  merchantId,
  amount,
  currency,
  onSuccess,
  onError,
  onCancel,
}) => {
  const [loading, setLoading] = useState(true);
  const webViewRef = useRef<WebView>(null);

  const htmlContent = `
<!DOCTYPE html>
<html>
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />
    <style>
        body {
            margin: 0;
            padding: 20px;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 500px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .button {
            background-color: #007AFF;
            color: white;
            padding: 15px 30px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            width: 100%;
            margin-top: 20px;
        }
        .button:hover {
            background-color: #0051D5;
        }
        .button:disabled {
            background-color: #ccc;
            cursor: not-allowed;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Complete Payment</h2>
        <p>Order ID: ${orderId}</p>
        <p>Amount: ${amount} ${currency}</p>
        <button id="payButton" class="button" onclick="initiatePayment()">Pay Now</button>
    </div>

    <script src="${MPGS_CHECKOUT_JS_URL}"
            data-error="errorCallback"
            data-cancel="cancelCallback"
            data-complete="completeCallback">
    </script>

    <script>
        var merchantId = "${merchantId}";
        var sessionId = "${sessionId}";
        var sessionVersion = "${sessionVersion}";
        var successIndicator = "${successIndicator}";
        var orderId = "${orderId}";
        var resultIndicator = null;

        function errorCallback(error) {
            console.error('MPGS Error:', JSON.stringify(error));
            window.ReactNativeWebView.postMessage(JSON.stringify({
                type: 'error',
                error: JSON.stringify(error)
            }));
        }

        function cancelCallback() {
            console.log('Payment cancelled');
            window.ReactNativeWebView.postMessage(JSON.stringify({
                type: 'cancel'
            }));
        }

        function completeCallback(response) {
            resultIndicator = response;
            var result = (resultIndicator === successIndicator) ? 'SUCCESS' : 'ERROR';
            
            window.ReactNativeWebView.postMessage(JSON.stringify({
                type: 'complete',
                result: result,
                resultIndicator: resultIndicator,
                orderId: orderId
            }));
        }

        function initiatePayment() {
            Checkout.configure({
                merchant: merchantId,
                order: {
                    amount: ${amount},
                    currency: '${currency}',
                    description: 'Order ${orderId}',
                    id: orderId
                },
                session: {
                    id: sessionId,
                    version: sessionVersion
                },
                interaction: {
                    merchant: {
                        name: 'Your Merchant Name'
                    }
                }
            });

            // Show lightbox checkout
            Checkout.showLightbox();
        }

        // Auto-initiate payment when page loads
        window.addEventListener('load', function() {
            setTimeout(initiatePayment, 500);
        });
    </script>
</body>
</html>
  `;

  const handleMessage = (event: any) => {
    try {
      const message = JSON.parse(event.nativeEvent.data);
      
      switch (message.type) {
        case 'complete':
          if (message.result === 'SUCCESS') {
            onSuccess(message.orderId);
          } else {
            onError('Payment verification failed');
          }
          break;
        case 'error':
          onError(message.error || 'Payment error occurred');
          break;
        case 'cancel':
          onCancel();
          break;
      }
    } catch (error) {
      console.error('Error parsing message:', error);
    }
  };

  return (
    <View style={styles.container}>
      {loading && (
        <View style={styles.loader}>
          <ActivityIndicator size="large" color="#007AFF" />
        </View>
      )}
      <WebView
        ref={webViewRef}
        source={{ html: htmlContent }}
        onMessage={handleMessage}
        onLoadEnd={() => setLoading(false)}
        style={styles.webview}
        javaScriptEnabled={true}
        domStorageEnabled={true}
        startInLoadingState={true}
        scalesPageToFit={true}
      />
    </View>
  );
};

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#f5f5f5',
  },
  webview: {
    flex: 1,
  },
  loader: {
    position: 'absolute',
    top: 0,
    left: 0,
    right: 0,
    bottom: 0,
    justifyContent: 'center',
    alignItems: 'center',
    backgroundColor: 'rgba(255, 255, 255, 0.8)',
    zIndex: 1,
  },
});
```

---

## Step 4: Payment Screen Implementation

Create a payment screen that orchestrates the flow:

```typescript
// screens/PaymentScreen.tsx

import React, { useState } from 'react';
import { View, StyleSheet, Alert, ActivityIndicator } from 'react-native';
import { MPGSCheckout } from '../components/MPGSCheckout';
import { createPaymentIntent } from '../services/paymentService';
import { verifyPayment } from '../services/paymentService'; // You'll need to create this

interface PaymentScreenProps {
  amount: string;
  orderReference: string;
  authToken: string;
  onPaymentSuccess: () => void;
  onPaymentCancel: () => void;
}

export const PaymentScreen: React.FC<PaymentScreenProps> = ({
  amount,
  orderReference,
  authToken,
  onPaymentSuccess,
  onPaymentCancel,
}) => {
  const [loading, setLoading] = useState(true);
  const [paymentIntent, setPaymentIntent] = useState<any>(null);
  const [error, setError] = useState<string | null>(null);

  React.useEffect(() => {
    initializePayment();
  }, []);

  const initializePayment = async () => {
    try {
      setLoading(true);
      const intent = await createPaymentIntent(amount, orderReference, authToken);
      setPaymentIntent(intent);
      setError(null);
    } catch (err: any) {
      setError(err.message || 'Failed to initialize payment');
      Alert.alert('Error', err.message || 'Failed to initialize payment');
    } finally {
      setLoading(false);
    }
  };

  const handlePaymentSuccess = async (orderId: string) => {
    try {
      // Verify payment with your backend
      const verified = await verifyPayment(orderId, authToken);
      
      if (verified) {
        Alert.alert(
          'Success',
          'Payment completed successfully!',
          [{ text: 'OK', onPress: onPaymentSuccess }]
        );
      } else {
        Alert.alert('Error', 'Payment verification failed');
      }
    } catch (error: any) {
      Alert.alert('Error', error.message || 'Failed to verify payment');
    }
  };

  const handlePaymentError = (errorMessage: string) => {
    Alert.alert('Payment Error', errorMessage);
  };

  const handlePaymentCancel = () => {
    Alert.alert(
      'Payment Cancelled',
      'The payment was cancelled.',
      [{ text: 'OK', onPress: onPaymentCancel }]
    );
  };

  if (loading) {
    return (
      <View style={styles.center}>
        <ActivityIndicator size="large" color="#007AFF" />
      </View>
    );
  }

  if (error || !paymentIntent) {
    return (
      <View style={styles.center}>
        <Text style={styles.error}>{error || 'Failed to load payment'}</Text>
      </View>
    );
  }

  return (
    <MPGSCheckout
      sessionId={paymentIntent.sessionId}
      sessionVersion={paymentIntent.sessionVersion}
      successIndicator={paymentIntent.successIndicator}
      orderId={paymentIntent.orderId}
      merchantId={process.env.EXPO_PUBLIC_MPGS_MERCHANT_ID || ''}
      amount={parseFloat(amount)}
      currency="USD" // or get from paymentIntent
      onSuccess={handlePaymentSuccess}
      onError={handlePaymentError}
      onCancel={handlePaymentCancel}
    />
  );
};

const styles = StyleSheet.create({
  center: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
    padding: 20,
  },
  error: {
    color: 'red',
    textAlign: 'center',
  },
});
```

---

## Step 5: Payment Verification Endpoint

You'll need to create an endpoint to verify payment status. Add this to your backend:

```php
// src/Controller/Api/VerifyPayment.php

<?php

namespace App\Controller\Api;

use App\Service\OrderService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class VerifyPayment extends AbstractController
{
    public function __construct(
        private readonly OrderService $orderService
    ) {}

    #[Route('/api/verify-payment/{orderId}', methods: ['GET'])]
    public function __invoke(string $orderId, Request $request): JsonResponse
    {
        $order = $this->orderService->findByReference($orderId);
        
        if (!$order) {
            return new JsonResponse(['verified' => false, 'error' => 'Order not found'], 404);
        }

        $payment = $order->getPayment();
        
        if (!$payment) {
            return new JsonResponse(['verified' => false, 'error' => 'Payment not found'], 404);
        }

        // Check payment status
        $isVerified = $payment->isCompleted() || $payment->isProcessing();
        
        return new JsonResponse([
            'verified' => $isVerified,
            'status' => $payment->getStatus()->value,
            'orderId' => $orderId
        ]);
    }
}
```

Add verification service in React Native:

```typescript
// services/paymentService.ts (add this function)

export const verifyPayment = async (
  orderId: string,
  token: string
): Promise<boolean> => {
  const response = await fetch(`${API_BASE_URL}/api/verify-payment/${orderId}`, {
    method: 'GET',
    headers: {
      'Authorization': `Bearer ${token}`,
    },
  });

  if (!response.ok) {
    throw new Error('Failed to verify payment');
  }

  const data = await response.json();
  return data.verified === true;
};
```

---

## Step 6: Environment Variables

Create a `.env` file in your React Native Expo project:

```env
# API Configuration
EXPO_PUBLIC_API_URL=https://your-api.com

# MPGS Configuration
EXPO_PUBLIC_MPGS_MERCHANT_ID=your_merchant_id
EXPO_PUBLIC_MPGS_CHECKOUT_JS_URL=https://your-gateway-url/checkout/version/45/checkout.js
```

---

## Complete Payment Flow

```
1. User initiates payment
   ↓
2. App calls: POST /api/create-payment-intent
   ↓
3. Backend creates MPGS checkout session
   ↓
4. Backend returns: sessionId, sessionVersion, successIndicator
   ↓
5. App loads MPGS checkout in WebView
   ↓
6. User enters card details in MPGS hosted checkout
   ↓
7. MPGS processes payment
   ↓
8. MPGS calls completeCallback with resultIndicator
   ↓
9. App verifies: resultIndicator === successIndicator
   ↓
10. App calls: GET /api/verify-payment/{orderId}
   ↓
11. Backend confirms payment status
   ↓
12. App shows success/error message
```

---

## Alternative: Using Expo Web Browser

If you prefer to use a full browser instead of WebView:

```typescript
import * as WebBrowser from 'expo-web-browser';

const openMPGSCheckout = async (
  sessionId: string,
  sessionVersion: string,
  merchantId: string,
  orderId: string,
  amount: number,
  currency: string
) => {
  const returnUrl = `${process.env.EXPO_PUBLIC_APP_URL}/payment-return`;
  
  // Create HTML page URL or use your backend to serve the checkout page
  const checkoutUrl = `${process.env.EXPO_PUBLIC_API_URL}/mpgs-checkout?sessionId=${sessionId}&sessionVersion=${sessionVersion}&merchantId=${merchantId}&orderId=${orderId}&amount=${amount}&currency=${currency}&returnUrl=${encodeURIComponent(returnUrl)}`;
  
  const result = await WebBrowser.openBrowserAsync(checkoutUrl);
  
  // Handle result
  if (result.type === 'cancel') {
    // User cancelled
  }
};
```

---

## Testing

1. **Test Mode**: Use MPGS test credentials
2. **Test Cards**: Use MPGS test card numbers
3. **Error Handling**: Test various error scenarios
4. **Network Issues**: Test offline/online scenarios

---

## Security Considerations

1. **Never store MPGS credentials in the mobile app**
2. **Always verify payment on backend** - Don't trust client-side results
3. **Use HTTPS** for all API calls
4. **Validate successIndicator** on backend before confirming payment
5. **Implement proper error handling** and user feedback

---

## Troubleshooting

### WebView not loading
- Check if `javaScriptEnabled={true}`
- Verify MPGS checkout.js URL is accessible
- Check CORS settings on backend

### Payment not completing
- Verify sessionId and sessionVersion are correct
- Check successIndicator matching
- Review backend logs for errors

### Network errors
- Verify API_BASE_URL is correct
- Check authentication token is valid
- Ensure backend is accessible

---

## Next Steps After Payment Intent

1. **Payment Processing**: User completes payment in MPGS checkout
2. **Webhook Handling**: MPGS sends webhook to your backend (configure webhook URL in MPGS dashboard)
3. **Payment Verification**: Verify payment status via API
4. **Order Fulfillment**: Update order status and proceed with fulfillment

For webhook implementation, see: `docs/MPGS_WEBHOOKS.md` (create this if needed)

