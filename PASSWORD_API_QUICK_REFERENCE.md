# Password Management API - Quick Reference

## 🔐 Endpoints Overview

### Public Endpoints (No Authentication Required)

| Method | Endpoint | Description | Request Body |
|--------|----------|-------------|--------------|
| POST | `/api/password/forgot` | Request reset code | `{"email": "user@example.com"}` |
| POST | `/api/password/verify-code` | Verify reset code | `{"email": "...", "code": "123456"}` |
| POST | `/api/password/reset` | Reset password | `{"email": "...", "code": "...", "password": "..."}` |

### Authenticated Endpoints (JWT Required)

| Method | Endpoint | Description | Request Body |
|--------|----------|-------------|--------------|
| POST | `/api/user/update-password` | Change password | `{"currentPassword": "...", "newPassword": "...", "confirmPassword": "..."}` |

---

## 📋 Forgot Password Flow

```
┌──────────────────────────────────────────────────────────────────┐
│                    FORGOT PASSWORD FLOW                          │
└──────────────────────────────────────────────────────────────────┘

Step 1: Request Reset Code
┌─────────────┐
│   Client    │
└──────┬──────┘
       │ POST /api/password/forgot
       │ {"email": "user@example.com"}
       │
       ▼
┌─────────────┐      ┌──────────────┐      ┌────────────┐
│   Backend   │─────▶│   Database   │      │   Mailer   │
│             │      │ Save Code    │      │            │
└──────┬──────┘      └──────────────┘      └─────┬──────┘
       │                                          │
       │ Generate 6-digit code                    │
       │ Expiration: +1 hour                      │
       └─────────────────────────────────────────▶│
                                                  │
                                                  │ Send Email
                                                  │ Template: reset_password.html.twig
                                                  ▼
                                          ┌──────────────┐
                                          │     User     │
                                          │   Receives   │
                                          │     Code     │
                                          └──────────────┘

Step 2: Verify Code (Optional)
┌─────────────┐
│   Client    │
└──────┬──────┘
       │ POST /api/password/verify-code
       │ {"email": "user@example.com", "code": "123456"}
       │
       ▼
┌─────────────┐      ┌──────────────┐
│   Backend   │─────▶│   Database   │
│             │      │ Check Code   │
└──────┬──────┘      │ Check Expiry │
       │             └──────────────┘
       │
       │ Response: {"message": "Code is valid"}
       ▼
┌─────────────┐
│   Client    │
└─────────────┘

Step 3: Reset Password
┌─────────────┐
│   Client    │
└──────┬──────┘
       │ POST /api/password/reset
       │ {"email": "...", "code": "...", "password": "NewPass123"}
       │
       ▼
┌─────────────┐      ┌──────────────┐
│   Backend   │─────▶│   Database   │
│             │      │ Verify Code  │
│             │      │ Update Pass  │
│             │      │ Invalidate   │
└──────┬──────┘      └──────────────┘
       │
       │ Response: {"message": "Password reset successfully"}
       ▼
┌─────────────┐
│   Client    │
│ Can Login!  │
└─────────────┘
```

---

## 🔄 Update Password Flow (Authenticated)

```
┌──────────────────────────────────────────────────────────────────┐
│                    UPDATE PASSWORD FLOW                          │
└──────────────────────────────────────────────────────────────────┘

Step 1: Authenticate
┌─────────────┐
│   Client    │
└──────┬──────┘
       │ POST /api/auth
       │ {"email": "...", "password": "..."}
       │
       ▼
┌─────────────┐      ┌──────────────┐
│   Backend   │─────▶│   Database   │
│             │      │ Verify User  │
└──────┬──────┘      └──────────────┘
       │
       │ Response: {"token": "eyJ0eXAiOiJKV1..."}
       ▼
┌─────────────┐
│   Client    │
│ Has Token!  │
└──────┬──────┘

Step 2: Update Password
       │ POST /api/user/update-password
       │ Header: Authorization: Bearer <token>
       │ {
       │   "currentPassword": "OldPass123",
       │   "newPassword": "NewPass123",
       │   "confirmPassword": "NewPass123"
       │ }
       │
       ▼
┌─────────────┐      ┌──────────────┐      ┌────────────┐
│   Backend   │─────▶│   Database   │      │   Mailer   │
│ Verify JWT  │      │ Update Pass  │      │            │
│ Verify Old  │      └──────────────┘      └─────┬──────┘
│ Password    │                                   │
└──────┬──────┘                                   │
       │                                          │
       │ Response: {"message": "Password updated"}
       │                                          │
       └─────────────────────────────────────────▶│
                                                  │
                                                  │ Send Email
                                                  │ Template: password_changed.html.twig
                                                  ▼
                                          ┌──────────────┐
                                          │     User     │
                                          │ Confirmation │
                                          └──────────────┘
```

---

## ✅ Request & Response Examples

### 1. Request Reset Code

**Request:**
```bash
curl -X POST http://localhost/api/password/forgot \
  -H "Content-Type: application/json" \
  -d '{"email": "user@example.com"}'
```

**Response (200):**
```json
{
  "message": "If an account exists with this email, you will receive a password reset code."
}
```

### 2. Verify Code

**Request:**
```bash
curl -X POST http://localhost/api/password/verify-code \
  -H "Content-Type: application/json" \
  -d '{"email": "user@example.com", "code": "123456"}'
```

**Response (200):**
```json
{
  "message": "Code is valid"
}
```

**Response (400):**
```json
{
  "error": "Invalid or expired code"
}
```

### 3. Reset Password

**Request:**
```bash
curl -X POST http://localhost/api/password/reset \
  -H "Content-Type: application/json" \
  -d '{
    "email": "user@example.com",
    "code": "123456",
    "password": "NewSecurePass123"
  }'
```

**Response (200):**
```json
{
  "message": "Password reset successfully"
}
```

**Response (400):**
```json
{
  "message": "Invalid or expired reset code"
}
```

### 4. Update Password

**Request:**
```bash
curl -X POST http://localhost/api/user/update-password \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc..." \
  -d '{
    "currentPassword": "OldPass123",
    "newPassword": "NewSecurePass123",
    "confirmPassword": "NewSecurePass123"
  }'
```

**Response (200):**
```json
{
  "message": "Password updated successfully"
}
```

**Response (400):**
```json
{
  "message": "Current password is incorrect"
}
```

---

## 🔒 Password Requirements

| Requirement | Rule |
|-------------|------|
| Minimum Length | 8 characters |
| Uppercase | At least 1 (A-Z) |
| Lowercase | At least 1 (a-z) |
| Numbers | At least 1 (0-9) |

**Valid Examples:**
- ✅ `SecurePass123`
- ✅ `MyP@ssw0rd`
- ✅ `Test1234Abc`

**Invalid Examples:**
- ❌ `short1` (too short)
- ❌ `alllowercase1` (no uppercase)
- ❌ `ALLUPPERCASE1` (no lowercase)
- ❌ `NoNumbers` (no digits)

---

## 📧 Email Templates

### Reset Password Email
- **Template**: `templates/emails/reset_password.html.twig`
- **Subject**: Password Reset Code - Joy Pharma
- **Contains**: 6-digit code, expiration notice, security warning

### Password Changed Email
- **Template**: `templates/emails/password_changed.html.twig`
- **Subject**: Password Changed - Joy Pharma
- **Contains**: Confirmation, timestamp, security alert

---

## ⏰ Timing & Expiration

| Item | Duration |
|------|----------|
| Reset Code Validity | 1 hour |
| JWT Token Validity | 1 hour (configurable) |
| Code Generation | Random 6-digit (100000-999999) |

---

## 🛡️ Security Features

| Feature | Implementation |
|---------|----------------|
| Email Obfuscation | Same response whether email exists or not |
| Code Expiration | 1 hour automatic expiration |
| Single Use Codes | Invalidated after successful reset |
| Previous Code Invalidation | New request cancels old codes |
| Strong Passwords | Regex validation enforced |
| Current Password Check | Required for updates |
| JWT Authentication | Required for update endpoint |
| Email Notifications | Sent on password changes |

---

## 🧪 Testing Checklist

### Forgot Password
- [ ] Request code with valid email
- [ ] Request code with invalid email (should not reveal existence)
- [ ] Verify valid code
- [ ] Verify invalid code
- [ ] Verify expired code (after 1 hour)
- [ ] Reset password with valid code
- [ ] Reset password with invalid code
- [ ] Check email arrives in inbox
- [ ] Verify code is 6 digits
- [ ] Test password validation rules
- [ ] Confirm old code invalidated after new request

### Update Password
- [ ] Login and get JWT token
- [ ] Update with correct current password
- [ ] Try update with wrong current password
- [ ] Try update without JWT token
- [ ] Try update with expired JWT token
- [ ] Test password confirmation mismatch
- [ ] Test password validation rules
- [ ] Verify confirmation email sent

---

## 🚀 Quick Start

1. **Start Services:**
   ```bash
   docker-compose up -d
   ```

2. **Access Mailpit:**
   ```
   http://localhost:8025
   ```

3. **Test Endpoints:**
   ```bash
   ./test_password_apis.sh
   ```

4. **View API Docs:**
   ```
   http://localhost/api/docs
   ```

---

## 📁 File Locations

| File Type | Location |
|-----------|----------|
| Controllers | `src/Controller/Api/*PasswordController.php` |
| DTOs | `src/Dto/ResetPasswordInput.php`, `src/Dto/UpdatePasswordInput.php` |
| Email Templates | `templates/emails/*.html.twig` |
| API Config | `src/ApiResource/ResetPassword.yaml`, `src/ApiResource/User.yaml` |
| Security | `config/packages/security.yaml` |
| Service | `src/Service/ResetPasswordService.php` |
| Entity | `src/Entity/ResetPassword.php` |
| Test Script | `test_password_apis.sh` |
| Documentation | `PASSWORD_MANAGEMENT_GUIDE.md` |

---

## 🆘 Common Errors & Solutions

| Error | Cause | Solution |
|-------|-------|----------|
| 400 - Invalid or expired code | Code doesn't exist, wrong, or > 1 hour old | Request new code |
| 400 - Current password is incorrect | Wrong current password provided | Check current password |
| 400 - Password validation error | Password doesn't meet requirements | Use stronger password |
| 401 - User not authenticated | No JWT or invalid JWT | Login again to get new token |
| Email not received | Mailer not configured/running | Check Mailpit at :8025 |

---

## 🔗 Related Documentation

- Full Guide: `PASSWORD_MANAGEMENT_GUIDE.md`
- Implementation Details: `IMPLEMENTATION_SUMMARY.md`
- API Documentation: http://localhost/api/docs
- Test Script: `test_password_apis.sh`

---

**Last Updated**: October 27, 2025
**Status**: ✅ Production Ready

