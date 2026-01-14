# Push Notification Setup Guide

This guide explains how to set up push notifications with Firebase Cloud Messaging (FCM) HTTP v1 API in the Joy Pharma application.

## Architecture Overview

```
┌─────────────────┐     ┌─────────────┐     ┌─────────────────┐     ┌──────────────┐
│  Mobile App     │────▶│  Backend    │────▶│ FirebasePushSvc │────▶│   Firebase   │
│  (FCM SDK)      │     │  (Symfony)  │     │  (HTTP v1 API)   │     │   FCM        │
└─────────────────┘     └─────────────┘     └─────────────────┘     └──────────────┘
       │                       │                       │                       │
       │                       │                       │                       │
       ▼                       ▼                       ▼                       ▼
  Device Token          DeviceToken          Push Notification          Device Display
  Registration          Storage              Processing

## Components

### 1. DeviceToken Entity
- Stores FCM registration tokens
- Supports multiple devices per user
- Tracks platform, device name, app version
- Manages token lifecycle (failed attempts, cleanup)

### 2. FcmTokenService
- Handles token registration/unregistration
- Manages token lifecycle
- Supports multi-device per user

### 3. FirebasePushService
- Direct integration with Firebase Cloud Messaging HTTP v1 API
- Supports single, multicast, topic, and broadcast notifications
- Handles batching for large recipient lists
- Uses OAuth2 authentication with service account credentials

### 4. NotificationService
- Creates in-app notifications
- Triggers push notifications to all user devices via FirebasePushService
- Sends email notifications via n8n (if configured)

## API Endpoints

### User Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/device-tokens/register` | Register FCM token |
| DELETE | `/api/device-tokens/unregister` | Unregister FCM token |
| GET | `/api/device-tokens` | List user's devices |

### Admin Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/admin/push-notifications/send` | Send push notification |

## Database Setup

Run the migration to create the device_token table:

```bash
# Generate migration (if not already created)
php bin/console doctrine:migrations:diff

# Run migration
php bin/console doctrine:migrations:migrate
```

Or manually create the table:

```sql
CREATE TABLE "device_token" (
    id SERIAL PRIMARY KEY,
    user_id INT NOT NULL,
    fcm_token VARCHAR(500) NOT NULL,
    platform VARCHAR(20) DEFAULT NULL,
    device_name VARCHAR(100) DEFAULT NULL,
    app_version VARCHAR(20) DEFAULT NULL,
    is_active BOOLEAN DEFAULT true NOT NULL,
    last_used_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
    failed_attempts INT DEFAULT 0 NOT NULL,
    created_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP NOT NULL,
    updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
    CONSTRAINT FK_device_token_user FOREIGN KEY (user_id) REFERENCES "user" (id) ON DELETE CASCADE
);

CREATE UNIQUE INDEX unique_fcm_token ON "device_token" (fcm_token);
CREATE INDEX idx_device_token_user ON "device_token" (user_id);
CREATE INDEX idx_device_token_platform ON "device_token" (platform);
```

## Environment Configuration

Add Firebase service account credentials to your `.env` file:

```env
# Firebase Configuration (HTTP v1 API)
FIREBASE_PROJECT_ID=your-firebase-project-id
FIREBASE_CLIENT_EMAIL=your-service-account@your-project.iam.gserviceaccount.com
FIREBASE_PRIVATE_KEY="-----BEGIN PRIVATE KEY-----\nYour private key here\n-----END PRIVATE KEY-----"
```

### Obtaining Firebase Credentials

1. **Go to Firebase Console**:
   - Navigate to [Firebase Console](https://console.firebase.google.com/)
   - Select your project

2. **Generate Service Account Key**:
   - Click the gear icon next to "Project Overview"
   - Select "Project settings"
   - Go to the "Service accounts" tab
   - Click "Generate new private key"
   - Download the JSON file

3. **Extract Values from JSON**:
   ```json
   {
     "type": "service_account",
     "project_id": "your-project-id",
     "private_key_id": "...",
     "private_key": "-----BEGIN PRIVATE KEY-----\nYOUR_PRIVATE_KEY_HERE\n-----END PRIVATE KEY-----\n",
     "client_email": "firebase-adminsdk-xxxxx@your-project.iam.gserviceaccount.com",
     "client_id": "...",
     "auth_uri": "https://accounts.google.com/o/oauth2/auth",
     "token_uri": "https://oauth2.googleapis.com/token",
     "auth_provider_x509_cert_url": "https://www.googleapis.com/oauth2/v1/certs",
     "client_x509_cert_url": "..."
   }
   ```

   Use the `project_id`, `client_email`, and `private_key` values in your `.env` file.

## Mobile App Integration

### 1. Initialize Firebase Messaging

```dart
// Flutter/Dart example
import 'package:firebase_messaging/firebase_messaging.dart';

class PushNotificationService {
  final FirebaseMessaging _fcm = FirebaseMessaging.instance;

  Future<void> initialize() async {
    // Request permission (iOS)
    await _fcm.requestPermission();
    
    // Get FCM token
    String? token = await _fcm.getToken();
    if (token != null) {
      await registerToken(token);
    }
    
    // Listen for token refresh
    _fcm.onTokenRefresh.listen((newToken) {
      registerToken(newToken);
    });
  }
  
  Future<void> registerToken(String token) async {
    await api.post('/api/device-tokens/register', {
      'fcmToken': token,
      'platform': Platform.isIOS ? 'ios' : 'android',
      'deviceName': await getDeviceName(),
      'appVersion': packageInfo.version,
    });
  }
  
  Future<void> unregisterToken() async {
    String? token = await _fcm.getToken();
    if (token != null) {
      await api.delete('/api/device-tokens/unregister', {
        'fcmToken': token,
      });
    }
  }
}
```

### 2. Handle Received Notifications

```dart
// Foreground messages
FirebaseMessaging.onMessage.listen((RemoteMessage message) {
  // Show local notification or update UI
  print('Got a message whilst in the foreground!');
  print('Message data: ${message.data}');

  if (message.notification != null) {
    showLocalNotification(
      title: message.notification!.title,
      body: message.notification!.body,
    );
  }
});

// Background/terminated messages
FirebaseMessaging.onBackgroundMessage(_firebaseMessagingBackgroundHandler);

Future<void> _firebaseMessagingBackgroundHandler(RemoteMessage message) async {
  await Firebase.initializeApp();
  print('Handling a background message: ${message.messageId}');
}
```

## Direct FCM Integration

The application uses Firebase Cloud Messaging HTTP v1 API directly without intermediaries. Push notifications are sent from the Symfony backend to Firebase using OAuth2 authentication with service account credentials.

### Key Features

- **HTTP v1 API**: Uses the latest FCM API with improved security and features
- **OAuth2 Authentication**: Secure authentication using Firebase service account credentials
- **Batch Processing**: Handles multiple tokens efficiently
- **Error Handling**: Automatic token cleanup for failed deliveries
- **Platform-Specific**: Custom Android and iOS notification settings

## Token Cleanup

Run periodically to clean up stale tokens:

```bash
# Clean up stale and inactive tokens
php bin/console app:cleanup-fcm-tokens

# Dry run to see what would be deleted
php bin/console app:cleanup-fcm-tokens --dry-run
```

Add to crontab for automatic cleanup:

```cron
# Clean up FCM tokens daily at 3 AM
0 3 * * * cd /path/to/project && php bin/console app:cleanup-fcm-tokens
```

## Best Practices Implemented

1. **FCM HTTP v1 API**: Uses the latest Firebase Cloud Messaging API
2. **OAuth2 Authentication**: Secure authentication with service account credentials
3. **FCM Tokens, Not Phone IDs**: Using FCM registration tokens which are the recommended approach
4. **Multi-Device Support**: One user can have multiple devices, each with its own token
5. **Token Lifecycle Management**: Track failed attempts, automatically deactivate/remove stale tokens
6. **Batch Processing**: Efficient handling of multiple tokens
7. **Platform-Specific Options**: Android and iOS specific notification settings
8. **Separation of Concerns**: In-app notifications separate from push notifications
9. **Error Handling**: Failed tokens are tracked and cleaned up automatically
10. **Logging**: Comprehensive logging for debugging and monitoring

## Troubleshooting

### Token Not Registered
- Verify the FCM SDK is properly initialized
- Check network connectivity
- Ensure the API endpoint is accessible

### Notifications Not Received
- Check FCM token validity
- Verify Firebase credentials are properly configured
- Check Firebase Console for delivery reports
- Ensure correct notification channel (Android)
- Verify OAuth2 token generation is working

### Token Refresh Issues
- Implement onTokenRefresh handler
- Call register endpoint on app startup

## Security Considerations

1. **Token Validation**: Validate token format before storing
2. **User Ownership**: Tokens are tied to authenticated users
3. **Token Transfer**: When a token is registered by a new user, ownership transfers (handles device login changes)
4. **Secure Transmission**: Always use HTTPS
5. **Rate Limiting**: Consider rate limiting token registration endpoints
