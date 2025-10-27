# Password Management Implementation Guide

This guide explains the password management features implemented in Joy Pharma, including forgot password and update password functionality.

## Features Implemented

### 1. Forgot Password Flow
A secure 3-step process for users who forgot their password:
- Request a reset code via email
- Verify the code
- Reset the password

### 2. Update Password (Authenticated Users)
Allows logged-in users to change their password by providing their current password.

---

## API Endpoints

### 1. Request Password Reset Code
**Endpoint:** `POST /api/password/forgot`
**Access:** Public
**Description:** Sends a 6-digit verification code to the user's email address

**Request Body:**
```json
{
  "email": "user@example.com"
}
```

**Response (Success - 200):**
```json
{
  "message": "If an account exists with this email, you will receive a password reset code."
}
```

**Notes:**
- The response is intentionally vague for security (doesn't reveal if email exists)
- Code expires in 1 hour
- Previous reset codes for the same email are automatically invalidated
- Email includes a styled HTML template with the verification code

---

### 2. Verify Reset Code
**Endpoint:** `POST /api/password/verify-code`
**Access:** Public
**Description:** Validates that the reset code is correct and not expired

**Request Body:**
```json
{
  "email": "user@example.com",
  "code": "123456"
}
```

**Response (Success - 200):**
```json
{
  "message": "Code is valid"
}
```

**Response (Error - 400):**
```json
{
  "error": "Invalid or expired code"
}
```

---

### 3. Reset Password
**Endpoint:** `POST /api/password/reset`
**Access:** Public
**Description:** Resets the user's password with the verified code

**Request Body:**
```json
{
  "email": "user@example.com",
  "code": "123456",
  "password": "NewSecurePass123"
}
```

**Response (Success - 200):**
```json
{
  "message": "Password reset successfully"
}
```

**Response (Error - 400):**
```json
{
  "message": "Invalid or expired reset code"
}
```

**Password Requirements:**
- Minimum 8 characters
- At least one uppercase letter
- At least one lowercase letter
- At least one number

---

### 4. Update Password (Authenticated)
**Endpoint:** `POST /api/user/update-password`
**Access:** Requires JWT Authentication
**Description:** Allows authenticated users to change their password

**Headers:**
```
Authorization: Bearer {jwt_token}
```

**Request Body:**
```json
{
  "currentPassword": "CurrentPass123",
  "newPassword": "NewSecurePass123",
  "confirmPassword": "NewSecurePass123"
}
```

**Response (Success - 200):**
```json
{
  "message": "Password updated successfully"
}
```

**Response (Error - 400):**
```json
{
  "message": "Current password is incorrect"
}
```

**Response (Error - 401):**
```json
{
  "message": "User not authenticated"
}
```

**Password Requirements:**
- Minimum 8 characters
- At least one uppercase letter
- At least one lowercase letter
- At least one number
- New password must match confirmation

**Email Notification:**
- User receives a confirmation email after successful password change

---

## Email Templates

### 1. Password Reset Email
**Template:** `templates/emails/reset_password.html.twig`
**Subject:** Password Reset Code - Joy Pharma

Features:
- Professional gradient header
- Large, easy-to-read verification code
- Expiration notice (1 hour)
- Security warning for unrequested resets
- Responsive design

### 2. Password Changed Confirmation
**Template:** `templates/emails/password_changed.html.twig`
**Subject:** Password Changed - Joy Pharma

Features:
- Success confirmation with checkmark
- Timestamp of the change
- Security alert if user didn't make the change
- Professional branding

---

## Security Features

### For Forgot Password:
1. **Email Obfuscation**: Response doesn't reveal if email exists in system
2. **Code Expiration**: Reset codes expire after 1 hour
3. **Single Use**: Codes are invalidated after successful password reset
4. **Previous Code Invalidation**: New requests invalidate old codes
5. **Strong Password Requirements**: Enforced through validation

### For Update Password:
1. **Authentication Required**: Must be logged in with valid JWT
2. **Current Password Verification**: Must provide correct current password
3. **Password Confirmation**: Must match new password with confirmation
4. **Email Notification**: User is notified of password change
5. **Strong Password Requirements**: Same as forgot password

---

## Database Schema

### ResetPassword Entity
- `id`: Primary key
- `email`: User's email address
- `code`: 6-digit verification code
- `expiresAt`: Expiration timestamp (1 hour from creation)
- `isValid`: Boolean flag for code validity

---

## Configuration Files

### 1. Security Configuration
**File:** `config/packages/security.yaml`

Public access granted to:
- `/api/password/forgot`
- `/api/password/verify-code`
- `/api/password/reset`

### 2. API Platform Resources
**Files:** 
- `src/ApiResource/ResetPassword.yaml` - Forgot password endpoints
- `src/ApiResource/User.yaml` - Update password endpoint

### 3. Mailer Configuration
**File:** `config/packages/mailer.yaml`
Uses Mailpit for development environment

---

## Usage Examples

### Example 1: Complete Forgot Password Flow

```bash
# Step 1: Request reset code
curl -X POST http://localhost/api/password/forgot \
  -H "Content-Type: application/json" \
  -d '{"email": "user@example.com"}'

# Step 2: Verify the code (optional but recommended for UX)
curl -X POST http://localhost/api/password/verify-code \
  -H "Content-Type: application/json" \
  -d '{"email": "user@example.com", "code": "123456"}'

# Step 3: Reset password
curl -X POST http://localhost/api/password/reset \
  -H "Content-Type: application/json" \
  -d '{
    "email": "user@example.com",
    "code": "123456",
    "password": "NewSecurePass123"
  }'
```

### Example 2: Update Password (Authenticated User)

```bash
# Get JWT token first
TOKEN=$(curl -X POST http://localhost/api/auth \
  -H "Content-Type: application/json" \
  -d '{"email": "user@example.com", "password": "CurrentPass123"}' \
  | jq -r '.token')

# Update password
curl -X POST http://localhost/api/user/update-password \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{
    "currentPassword": "CurrentPass123",
    "newPassword": "NewSecurePass123",
    "confirmPassword": "NewSecurePass123"
  }'
```

---

## Testing

### Testing with Mailpit (Development)

1. Access Mailpit UI at `http://localhost:8025` (or your configured port)
2. Request a password reset
3. Check Mailpit for the email with verification code
4. Use the code to complete the reset process

### Manual Testing Checklist

**Forgot Password:**
- [ ] Request reset for existing email
- [ ] Request reset for non-existing email (should have same response)
- [ ] Verify code validation works
- [ ] Verify expired code (wait 1 hour or manually update DB)
- [ ] Verify code invalidation after use
- [ ] Verify email is sent and formatted correctly
- [ ] Test password strength validation

**Update Password:**
- [ ] Update password with correct current password
- [ ] Try updating with incorrect current password
- [ ] Test without authentication token
- [ ] Verify password confirmation matching
- [ ] Verify password strength validation
- [ ] Confirm email notification is sent

---

## Troubleshooting

### Email Not Sending
1. Check mailer configuration in `.env`:
   ```
   MAILER_DSN=smtp://localhost:1025
   ```
2. Ensure Mailpit/Mailhog is running
3. Check logs in `var/log/dev.log`

### Code Expired Immediately
- Check server timezone configuration
- Verify ResetPassword entity `expiresAt` field is set correctly
- Check that server time is accurate

### Password Validation Failing
- Ensure password meets requirements:
  - Minimum 8 characters
  - At least one uppercase letter
  - At least one lowercase letter  
  - At least one number

### Authentication Issues (Update Password)
- Verify JWT token is valid and not expired
- Check token is included in Authorization header
- Verify user has ROLE_USER permission

---

## Future Enhancements

Possible improvements:
1. Add rate limiting to prevent brute force attacks
2. Implement CAPTCHA for forgot password requests
3. Add password history to prevent reusing recent passwords
4. Implement 2FA option
5. Add account lockout after multiple failed attempts
6. Send email notification when password reset is requested
7. Add admin panel to view/manage reset requests

---

## Code Structure

### Controllers
- `SendEmailResetPasswordController.php` - Handles sending reset codes
- `CheckCodeResetPasswordController.php` - Validates reset codes
- `ResetPasswordController.php` - Handles password reset
- `UpdatePasswordController.php` - Handles password updates for authenticated users

### DTOs
- `ResetPasswordInput.php` - Input validation for reset password flow
- `UpdatePasswordInput.php` - Input validation for password updates

### Services
- `ResetPasswordService.php` - Business logic for reset password operations
- `UserService.php` - User management operations

### Entities
- `ResetPassword.php` - Stores reset codes and expiration data
- `User.php` - User entity with authentication

---

## Support

For issues or questions:
1. Check the troubleshooting section above
2. Review API endpoint documentation
3. Check application logs in `var/log/`
4. Verify database schema is up to date with migrations

