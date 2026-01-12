# Push Notification Setup Guide

This guide explains how to set up push notifications with n8n and Firebase Cloud Messaging (FCM) in the Joy Pharma application.

## Architecture Overview

```
┌─────────────────┐     ┌─────────────┐     ┌─────────────────┐     ┌──────────────┐
│  Mobile App     │────▶│  Backend    │────▶│     n8n         │────▶│   Firebase   │
│  (FCM SDK)      │     │  (Symfony)  │     │  (Webhook)      │     │   FCM        │
└─────────────────┘     └─────────────┘     └─────────────────┘     └──────────────┘
       │                       │                                           │
       │                       │                                           │
       ▼                       ▼                                           ▼
  Device Token          DeviceToken              Push Notification    Device Display
  Registration          Storage                  Processing
```

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
- Integrates with n8n for push delivery
- Supports single, multicast, topic, and broadcast notifications
- Handles batching for large recipient lists

### 4. NotificationService (Enhanced)
- Creates in-app notifications
- Triggers push notifications to all user devices
- Sends email notifications

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

Add to your `.env` file:

```env
# n8n Configuration
N8N_WEBHOOK_URL=http://your-n8n-instance:5678/
```

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

## n8n Workflow Setup

See [n8n-firebase-push-notifications.md](./n8n-firebase-push-notifications.md) for detailed n8n workflow configuration.

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

1. **FCM Tokens, Not Phone IDs**: Using FCM registration tokens which are the recommended approach
2. **Multi-Device Support**: One user can have multiple devices, each with its own token
3. **Token Lifecycle Management**: Track failed attempts, automatically deactivate/remove stale tokens
4. **Batching**: Large recipient lists are batched to respect FCM limits
5. **Platform-Specific Options**: Android and iOS specific notification settings
6. **Separation of Concerns**: In-app notifications separate from push notifications
7. **Error Handling**: Failed tokens are tracked and cleaned up automatically
8. **Logging**: Comprehensive logging for debugging and monitoring

## Troubleshooting

### Token Not Registered
- Verify the FCM SDK is properly initialized
- Check network connectivity
- Ensure the API endpoint is accessible

### Notifications Not Received
- Check FCM token validity
- Verify n8n workflow is running
- Check Firebase Console for delivery reports
- Ensure correct notification channel (Android)

### Token Refresh Issues
- Implement onTokenRefresh handler
- Call register endpoint on app startup

## Security Considerations

1. **Token Validation**: Validate token format before storing
2. **User Ownership**: Tokens are tied to authenticated users
3. **Token Transfer**: When a token is registered by a new user, ownership transfers (handles device login changes)
4. **Secure Transmission**: Always use HTTPS
5. **Rate Limiting**: Consider rate limiting token registration endpoints
