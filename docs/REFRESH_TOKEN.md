# Refresh Token Documentation

This document explains how refresh tokens work in the JoyPharma application and how to use them for authentication.

## Table of Contents

1. [Overview](#overview)
2. [Authentication Flow](#authentication-flow)
3. [Refresh Token Endpoint](#refresh-token-endpoint)
4. [Configuration](#configuration)
5. [Token Lifecycle](#token-lifecycle)
6. [Usage Examples](#usage-examples)
7. [Best Practices](#best-practices)
8. [Troubleshooting](#troubleshooting)

## Overview

The JoyPharma application uses JWT (JSON Web Tokens) for API authentication, along with refresh tokens to maintain user sessions without requiring frequent re-authentication.

### Components

- **Access Token (JWT)**: Short-lived token (1 hour) used for API requests
- **Refresh Token**: Long-lived token stored in database, used to obtain new access tokens
- **Bundle**: `gesdinet/jwt-refresh-token-bundle` for refresh token management

### Key Features

- Automatic refresh token generation on login
- Secure token storage in database
- Automatic token rotation
- Configurable token expiration
- Stateless authentication with refresh capability

## Authentication Flow

### 1. Initial Authentication (Login)

When a user logs in via `/api/auth`, the system:

1. Validates credentials (email/password)
2. Generates a JWT access token (valid for 1 hour)
3. Generates a refresh token (stored in database)
4. Returns both tokens to the client

**Request Example:**
```http
POST /api/auth HTTP/1.1
Content-Type: application/json

{
    "email": "user@example.com",
    "password": "your-password"
}
```

**Response Example:**
```json
{
    "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9...",
    "refresh_token": "d3f8a9b2c1e4f5g6h7i8j9k0l1m2n3o4p5q6r7s8t9u0v1w2x3y4z5",
    "user": {
        "id": 1,
        "email": "user@example.com",
        "firstName": "John",
        "lastName": "Doe",
        "roles": ["ROLE_USER"],
        "userType": "customer",
        "isActive": true
    }
}
```

### 2. Using Access Token

Include the JWT token in the Authorization header for authenticated requests:

```http
GET /api/products HTTP/1.1
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9...
```

### 3. Token Refresh

When the access token expires (after 1 hour), use the refresh token to obtain a new access token without re-authenticating.

## Refresh Token Endpoint

### Endpoint

**URL**: `/api/token/refresh`  
**Method**: `POST`  
**Authentication**: Public (no token required)

### Request Format

The refresh token should be sent in the request body:

```http
POST /api/token/refresh HTTP/1.1
Content-Type: application/json

{
    "refresh_token": "d3f8a9b2c1e4f5g6h7i8j9k0l1m2n3o4p5q6r7s8t9u0v1w2x3y4z5"
}
```

**cURL Example:**
```bash
curl -X POST https://api.joypharma.com/api/token/refresh \
  -H "Content-Type: application/json" \
  -d '{"refresh_token": "your-refresh-token-here"}'
```

### Response Format

**Success Response (200 OK):**
```json
{
    "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9...",
    "refresh_token": "new-refresh-token-after-rotation"
}
```

**Error Response (401 Unauthorized):**
```json
{
    "code": 401,
    "message": "An authentication exception occurred."
}
```

**Error Response (400 Bad Request) - Invalid Token:**
```json
{
    "code": 400,
    "message": "Invalid refresh token"
}
```

## Configuration

### JWT Configuration

**File**: `config/packages/lexik_jwt_authentication.yaml`

```yaml
lexik_jwt_authentication:
    secret_key: '%kernel.project_dir%/config/jwt/private.pem'
    public_key: '%kernel.project_dir%/config/jwt/public.pem'
    pass_phrase: '%env(JWT_PASSPHRASE)%'
    token_ttl: 3600  # Access token validity: 1 hour (3600 seconds)
```

### Refresh Token Configuration

**File**: `config/packages/gesdinet_jwt_refresh_token.yaml`

```yaml
gesdinet_jwt_refresh_token:
    refresh_token_class: App\Entity\RefreshToken
```

### Security Configuration

**File**: `config/packages/security.yaml`

```yaml
security:
    firewalls:
        api:
            pattern: ^/api
            stateless: true
            provider: users
            jwt: ~
            refresh_jwt:
                check_path: /api/token/refresh
            json_login:
                check_path: /api/auth
                username_path: email
                password_path: password
                success_handler: lexik_jwt_authentication.handler.authentication_success
                failure_handler: lexik_jwt_authentication.handler.authentication_failure
```

### Access Control

The refresh token endpoint is publicly accessible:

```yaml
access_control:
    - { path: ^/api/token/refresh, roles: PUBLIC_ACCESS }
```

### Routes

**File**: `config/routes/gesdinet_jwt_refresh_token.yaml`

```yaml
gesdinet_jwt_refresh_token:
    path: /api/token/refresh
```

## Token Lifecycle

### Access Token (JWT)

- **Lifetime**: 1 hour (3600 seconds)
- **Usage**: Included in `Authorization: Bearer <token>` header
- **Validation**: Checked on every API request
- **Expiration**: Token expires after 1 hour, client must refresh

### Refresh Token

- **Storage**: Database table `refresh_tokens`
- **Lifetime**: Configurable (default: 2592000 seconds = 30 days)
- **Usage**: Used only to obtain new access tokens
- **Rotation**: Typically rotated on each refresh request
- **Revocation**: Can be manually deleted from database

### Refresh Token Entity

**File**: `src/Entity/RefreshToken.php`

```php
<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gesdinet\JWTRefreshTokenBundle\Entity\RefreshToken as BaseRefreshToken;

#[ORM\Entity]
#[ORM\Table(name: 'refresh_tokens')]
class RefreshToken extends BaseRefreshToken
{
}
```

The `RefreshToken` entity extends `BaseRefreshToken` from the bundle, which includes:
- `refreshToken`: The token string
- `username`: User identifier (email)
- `valid`: Token validity flag
- `expiresAt`: Token expiration timestamp
- `createdAt`: Token creation timestamp

## Usage Examples

### JavaScript/TypeScript Example

```typescript
class AuthService {
    private accessToken: string | null = null;
    private refreshToken: string | null = null;

    async login(email: string, password: string) {
        const response = await fetch('/api/auth', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ email, password })
        });

        const data = await response.json();
        this.accessToken = data.token;
        this.refreshToken = data.refresh_token;
        
        // Store tokens
        localStorage.setItem('access_token', this.accessToken);
        localStorage.setItem('refresh_token', this.refreshToken);
        
        return data;
    }

    async refreshAccessToken() {
        if (!this.refreshToken) {
            throw new Error('No refresh token available');
        }

        const response = await fetch('/api/token/refresh', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ refresh_token: this.refreshToken })
        });

        if (!response.ok) {
            // Refresh token expired or invalid
            this.logout();
            throw new Error('Failed to refresh token');
        }

        const data = await response.json();
        this.accessToken = data.token;
        this.refreshToken = data.refresh_token;
        
        // Update stored tokens
        localStorage.setItem('access_token', this.accessToken);
        localStorage.setItem('refresh_token', this.refreshToken);
        
        return this.accessToken;
    }

    async authenticatedFetch(url: string, options: RequestInit = {}) {
        // Add access token to request
        const headers = {
            ...options.headers,
            'Authorization': `Bearer ${this.accessToken}`
        };

        let response = await fetch(url, { ...options, headers });

        // If token expired, refresh and retry
        if (response.status === 401) {
            await this.refreshAccessToken();
            
            // Retry with new token
            headers['Authorization'] = `Bearer ${this.accessToken}`;
            response = await fetch(url, { ...options, headers });
        }

        return response;
    }

    logout() {
        this.accessToken = null;
        this.refreshToken = null;
        localStorage.removeItem('access_token');
        localStorage.removeItem('refresh_token');
    }
}
```

### React Example with Axios

```typescript
import axios, { AxiosError, InternalAxiosRequestConfig } from 'axios';

const api = axios.create({
    baseURL: '/api',
});

// Request interceptor to add token
api.interceptors.request.use((config: InternalAxiosRequestConfig) => {
    const token = localStorage.getItem('access_token');
    if (token) {
        config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
});

// Response interceptor to handle token refresh
api.interceptors.response.use(
    (response) => response,
    async (error: AxiosError) => {
        const originalRequest = error.config as InternalAxiosRequestConfig & { _retry?: boolean };

        if (error.response?.status === 401 && !originalRequest._retry) {
            originalRequest._retry = true;

            try {
                const refreshToken = localStorage.getItem('refresh_token');
                const response = await axios.post('/api/token/refresh', {
                    refresh_token: refreshToken
                });

                const { token, refresh_token } = response.data;
                localStorage.setItem('access_token', token);
                localStorage.setItem('refresh_token', refresh_token);

                // Retry original request with new token
                originalRequest.headers.Authorization = `Bearer ${token}`;
                return api(originalRequest);
            } catch (refreshError) {
                // Refresh failed, redirect to login
                localStorage.removeItem('access_token');
                localStorage.removeItem('refresh_token');
                window.location.href = '/login';
                return Promise.reject(refreshError);
            }
        }

        return Promise.reject(error);
    }
);
```

### Python Example

```python
import requests
from typing import Optional, Dict

class AuthClient:
    def __init__(self, base_url: str):
        self.base_url = base_url
        self.access_token: Optional[str] = None
        self.refresh_token: Optional[str] = None

    def login(self, email: str, password: str) -> Dict:
        response = requests.post(
            f"{self.base_url}/api/auth",
            json={"email": email, "password": password}
        )
        response.raise_for_status()
        
        data = response.json()
        self.access_token = data["token"]
        self.refresh_token = data["refresh_token"]
        return data

    def refresh_access_token(self) -> str:
        if not self.refresh_token:
            raise ValueError("No refresh token available")

        response = requests.post(
            f"{self.base_url}/api/token/refresh",
            json={"refresh_token": self.refresh_token}
        )
        response.raise_for_status()
        
        data = response.json()
        self.access_token = data["token"]
        self.refresh_token = data["refresh_token"]
        return self.access_token

    def authenticated_request(self, method: str, endpoint: str, **kwargs) -> requests.Response:
        if not self.access_token:
            raise ValueError("Not authenticated")

        headers = kwargs.pop("headers", {})
        headers["Authorization"] = f"Bearer {self.access_token}"
        kwargs["headers"] = headers

        response = requests.request(method, f"{self.base_url}{endpoint}", **kwargs)

        # If token expired, refresh and retry
        if response.status_code == 401:
            self.refresh_access_token()
            headers["Authorization"] = f"Bearer {self.access_token}"
            response = requests.request(method, f"{self.base_url}{endpoint}", **kwargs)

        return response
```

## Best Practices

### 1. Secure Storage

- **Client-Side**: Store refresh tokens in `httpOnly` cookies (most secure) or `localStorage`/`sessionStorage`
- **Never** expose refresh tokens in URLs or logs
- **Never** store refresh tokens in plain text

### 2. Token Rotation

- Refresh tokens should be rotated on each use
- Always update stored refresh token after refresh
- Old refresh tokens should be invalidated

### 3. Error Handling

- Always handle 401 errors by attempting token refresh
- If refresh fails, redirect user to login
- Log refresh token failures for security monitoring

### 4. Token Expiration

- Set appropriate expiration times:
  - Access tokens: Short (1 hour)
  - Refresh tokens: Longer (30 days default)
- Monitor token expiration and refresh proactively

### 5. Security

- Use HTTPS in production
- Validate refresh tokens server-side
- Implement rate limiting on refresh endpoint
- Monitor for suspicious refresh token usage

### 6. Automatic Refresh

- Refresh access tokens before they expire (e.g., 5 minutes before)
- Implement automatic retry on 401 errors
- Handle network failures gracefully

## Troubleshooting

### Common Issues

#### 1. "Invalid refresh token" Error

**Cause**: Refresh token is expired, invalid, or doesn't exist in database

**Solution**:
- Check if refresh token exists in `refresh_tokens` table
- Verify token hasn't expired
- Ensure token wasn't manually deleted
- User must re-authenticate

#### 2. "An authentication exception occurred" (401)

**Cause**: Access token expired or invalid

**Solution**:
- Use refresh token to get new access token
- If refresh token also expired, user must re-login

#### 3. Refresh Token Not in Response

**Cause**: Bundle configuration issue or token not generated

**Solution**:
- Verify `gesdinet_jwt_refresh_token` bundle is enabled
- Check refresh token configuration
- Ensure `/api/auth` endpoint is properly configured
- Check `JwtAuthenticationSuccessHandler` returns refresh token

#### 4. Token Refresh Loop

**Cause**: Refresh token itself is invalid or expired

**Solution**:
- Check refresh token validity in database
- Verify token expiration time
- Clear stored tokens and re-authenticate

### Database Queries

Check refresh tokens in database:

```sql
-- List all refresh tokens
SELECT * FROM refresh_tokens ORDER BY expires_at DESC;

-- Find tokens for a specific user
SELECT * FROM refresh_tokens WHERE username = 'user@example.com';

-- Find expired tokens
SELECT * FROM refresh_tokens WHERE expires_at < NOW();

-- Find valid tokens
SELECT * FROM refresh_tokens WHERE valid = true AND expires_at > NOW();
```

### Debugging

Enable debug logging in `config/packages/gesdinet_jwt_refresh_token.yaml`:

```yaml
# Check Symfony logs for refresh token operations
# Tail log file: tail -f var/log/dev.log | grep refresh
```

## API Response Format

### Successful Login Response

```json
{
    "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.eyJpc3MiOiJodHRwczovL2V4YW1wbGUuY29tIiwic3ViIjoiMSIsImF1ZCI6Imh0dHBzOi8vYXBpLmV4YW1wbGUuY29tIiwiaWF0IjoxNjAwMDAwMDAwLCJleHAiOjE2MDAwMDM2MDAsInJvbGVzIjpbIlJPTEVfVVNFUiJdfQ...",
    "refresh_token": "a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6q7r8s9t0u1v2w3x4y5z6",
    "user": {
        "id": 1,
        "email": "user@example.com",
        "firstName": "John",
        "lastName": "Doe",
        "phone": "+261340000000",
        "roles": ["ROLE_USER"],
        "userType": "customer",
        "isActive": true,
        "avatar": "/uploads/profile/avatar.jpg"
    }
}
```

### Successful Token Refresh Response

```json
{
    "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.new-access-token...",
    "refresh_token": "new-refresh-token-string"
}
```

### Error Responses

**401 Unauthorized:**
```json
{
    "code": 401,
    "message": "An authentication exception occurred."
}
```

**400 Bad Request (Invalid Refresh Token):**
```json
{
    "code": 400,
    "message": "Invalid refresh token"
}
```

## Security Considerations

1. **HTTPS Only**: Always use HTTPS in production for token transmission
2. **Token Storage**: Use secure storage mechanisms (httpOnly cookies preferred)
3. **Token Rotation**: Implement token rotation to reduce attack window
4. **Expiration**: Set appropriate token expiration times
5. **Monitoring**: Monitor refresh token usage for anomalies
6. **Rate Limiting**: Implement rate limiting on refresh endpoint
7. **Revocation**: Provide mechanism to revoke refresh tokens
8. **Logging**: Log refresh token operations for security auditing

## Additional Resources

- [LexikJWTAuthenticationBundle Documentation](https://github.com/lexik/LexikJWTAuthenticationBundle)
- [JWT Refresh Token Bundle Documentation](https://github.com/markitosgv/JWTRefreshTokenBundle)
- [JWT.io - JWT Debugger](https://jwt.io/)

