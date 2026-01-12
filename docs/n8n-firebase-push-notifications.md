# n8n Workflow Configuration for Firebase Cloud Messaging Push Notifications

This document describes how to set up the n8n workflow to handle push notifications through Firebase Cloud Messaging (FCM).

## Prerequisites

1. A Firebase project with Cloud Messaging enabled
2. Firebase Admin SDK service account credentials (JSON file)
3. n8n instance running and accessible

## Environment Variables

Add these to your `.env` file:

```env
# n8n Configuration
N8N_WEBHOOK_URL=http://your-n8n-instance:5678/

# Firebase Configuration (for direct integration if needed)
FIREBASE_PROJECT_ID=your-firebase-project-id
FIREBASE_CLIENT_EMAIL=your-service-account@your-project.iam.gserviceaccount.com
FIREBASE_PRIVATE_KEY="-----BEGIN PRIVATE KEY-----\nYour private key here\n-----END PRIVATE KEY-----"
```

## n8n Workflow Setup

### 1. Create a New Workflow

Create a new workflow in n8n called "Push Notifications Handler".

### 2. Add Webhook Trigger Node

1. Add a **Webhook** node as the trigger
2. Configure it:
   - **HTTP Method**: POST
   - **Path**: `push-notification`
   - **Authentication**: None (or add basic auth for security)
   - **Response Mode**: When Last Node Finishes

### 3. Add Switch Node for Notification Types

Add a **Switch** node to route based on notification type:

```json
{
  "conditions": {
    "string": [
      {
        "value1": "={{$json.type}}",
        "value2": "single"
      },
      {
        "value1": "={{$json.type}}",
        "value2": "multicast"
      },
      {
        "value1": "={{$json.type}}",
        "value2": "topic"
      },
      {
        "value1": "={{$json.type}}",
        "value2": "data_only"
      }
    ]
  }
}
```

### 4. Add Firebase Cloud Messaging Nodes

For each notification type, add an **HTTP Request** node to call Firebase FCM API.

#### Single Notification Node

```json
{
  "method": "POST",
  "url": "https://fcm.googleapis.com/v1/projects/{{$env.FIREBASE_PROJECT_ID}}/messages:send",
  "authentication": "predefinedCredentialType",
  "nodeCredentialType": "googleServiceAccount",
  "sendHeaders": true,
  "headerParameters": {
    "parameters": [
      {
        "name": "Content-Type",
        "value": "application/json"
      }
    ]
  },
  "sendBody": true,
  "bodyParameters": {
    "parameters": []
  },
  "jsonBody": "={{ JSON.stringify({ message: { token: $json.fcmToken, notification: $json.notification, data: $json.data, android: $json.options.android, apns: $json.options.apns } }) }}"
}
```

#### Multicast Notification Node (for multiple tokens)

For multicast, you'll need to loop through tokens or use Firebase's batch API:

```javascript
// Code node before HTTP request
const tokens = $input.first().json.tokens;
const notification = $input.first().json.notification;
const data = $input.first().json.data;
const options = $input.first().json.options;

const messages = tokens.map(token => ({
  message: {
    token: token,
    notification: notification,
    data: data,
    android: options?.android,
    apns: options?.apns
  }
}));

return messages.map(msg => ({ json: msg }));
```

#### Topic Notification Node

```json
{
  "message": {
    "topic": "{{$json.topic}}",
    "notification": {
      "title": "{{$json.notification.title}}",
      "body": "{{$json.notification.body}}"
    },
    "data": "={{$json.data}}"
  }
}
```

### 5. Add Response Handler Node

Add a **Code** node to format the response:

```javascript
const responses = $input.all();

let successCount = 0;
let failureCount = 0;
const failedTokens = [];

for (const response of responses) {
  if (response.json.error) {
    failureCount++;
    if (response.json.token) {
      failedTokens.push(response.json.token);
    }
  } else {
    successCount++;
  }
}

return [{
  json: {
    success: successCount > 0,
    success_count: successCount,
    failure_count: failureCount,
    failed_tokens: failedTokens
  }
}];
```

### 6. Error Handling

Add an **Error Trigger** node to catch and log errors:

```javascript
// Log error to monitoring system
console.error('FCM Push Error:', $json.error);

return [{
  json: {
    success: false,
    error: $json.error.message,
    stack: $json.error.stack
  }
}];
```

## Complete Workflow JSON Export

Here's the complete n8n workflow you can import:

```json
{
  "name": "Push Notifications Handler",
  "nodes": [
    {
      "parameters": {
        "httpMethod": "POST",
        "path": "push-notification",
        "responseMode": "lastNode",
        "options": {}
      },
      "id": "webhook-trigger",
      "name": "Webhook",
      "type": "n8n-nodes-base.webhook",
      "typeVersion": 1,
      "position": [250, 300]
    },
    {
      "parameters": {
        "mode": "expression",
        "conditions": {
          "string": [
            {
              "value1": "={{$json.type}}",
              "value2": "single"
            },
            {
              "value1": "={{$json.type}}",
              "value2": "multicast"
            },
            {
              "value1": "={{$json.type}}",
              "value2": "topic"
            }
          ]
        }
      },
      "id": "switch-type",
      "name": "Switch Type",
      "type": "n8n-nodes-base.switch",
      "typeVersion": 1,
      "position": [450, 300]
    }
  ],
  "connections": {
    "Webhook": {
      "main": [
        [
          {
            "node": "Switch Type",
            "type": "main",
            "index": 0
          }
        ]
      ]
    }
  }
}
```

## Firebase Admin SDK Setup in n8n

### Option 1: Google Service Account Credentials

1. In n8n, go to **Credentials**
2. Add new **Google Service Account** credential
3. Upload your Firebase Admin SDK JSON key file
4. Use this credential in your HTTP Request nodes

### Option 2: OAuth2 Authentication

1. Create OAuth2 credentials in Google Cloud Console
2. Add **Google OAuth2 API** credential in n8n
3. Configure with appropriate Firebase scopes

## Testing the Integration

### Test Single Notification

```bash
curl -X POST http://your-n8n-instance:5678/webhook/push-notification \
  -H "Content-Type: application/json" \
  -d '{
    "type": "single",
    "fcmToken": "your_fcm_token_here",
    "notification": {
      "title": "Test Notification",
      "body": "This is a test message"
    },
    "data": {
      "screen": "home"
    },
    "options": {
      "priority": "high"
    }
  }'
```

### Test Multicast Notification

```bash
curl -X POST http://your-n8n-instance:5678/webhook/push-notification \
  -H "Content-Type: application/json" \
  -d '{
    "type": "multicast",
    "tokens": ["token1", "token2", "token3"],
    "notification": {
      "title": "Broadcast Test",
      "body": "This goes to multiple devices"
    },
    "data": {}
  }'
```

### Test Topic Notification

```bash
curl -X POST http://your-n8n-instance:5678/webhook/push-notification \
  -H "Content-Type: application/json" \
  -d '{
    "type": "topic",
    "topic": "promotions",
    "notification": {
      "title": "Flash Sale!",
      "body": "50% off for the next hour"
    },
    "data": {
      "screen": "promotions"
    }
  }'
```

## Security Considerations

1. **Authentication**: Add API key or basic auth to the webhook
2. **Rate Limiting**: Configure n8n rate limiting to prevent abuse
3. **Token Validation**: Validate FCM tokens format before processing
4. **Logging**: Enable logging for audit and debugging
5. **HTTPS**: Always use HTTPS in production

## Monitoring and Debugging

1. Enable n8n execution logging
2. Monitor Firebase Console for delivery reports
3. Track failed tokens and clean them up regularly
4. Set up alerting for high failure rates

## Related Commands

```bash
# Clean up stale FCM tokens
php bin/console app:cleanup-fcm-tokens

# Dry run (show what would be deleted)
php bin/console app:cleanup-fcm-tokens --dry-run
```
