# Password Reset API Documentation

This document explains how to use the password reset functionality in the SMLARS API.

## Overview

The password reset feature allows users to securely reset their passwords through a token-based email verification system. The process involves three main steps:

1. **Request Password Reset** - User requests a password reset link
2. **Verify Token** (Optional) - Validate the reset token before showing the form
3. **Reset Password** - User submits new password with the token

## API Endpoints

### 1. Forgot Password (Request Reset Link)

**Endpoint:** `POST /api/forgot-password`

**Description:** Sends a password reset email to the user's registered email address.

**Request Body:**
```json
{
    "email": "user@example.com"
}
```

**Success Response (200):**
```json
{
    "message": "Password reset link has been sent to your email."
}
```

**Error Response (500):**
```json
{
    "message": "Failed to send password reset email. Please try again later.",
    "error": "Error details..."
}
```

**Notes:**
- For security reasons, the API returns a success message even if the email doesn't exist
- The reset token expires after 60 minutes
- The email contains both HTML and plain text versions

---

### 2. Verify Reset Token (Optional)

**Endpoint:** `POST /api/verify-reset-token`

**Description:** Validates whether a password reset token is still valid.

**Request Body:**
```json
{
    "token": "reset_token_here",
    "email": "user@example.com"
}
```

**Success Response (200):**
```json
{
    "message": "Token is valid.",
    "valid": true
}
```

**Error Response (400):**
```json
{
    "message": "Invalid or expired token.",
    "valid": false
}
```

**Use Case:**
- Validate token before showing the password reset form
- Provide better UX by checking token validity upfront

---

### 3. Reset Password

**Endpoint:** `POST /api/reset-password`

**Description:** Resets the user's password using the provided token.

**Request Body:**
```json
{
    "token": "reset_token_here",
    "email": "user@example.com",
    "password": "newPassword123",
    "password_confirmation": "newPassword123"
}
```

**Validation Rules:**
- `token`: Required
- `email`: Required, must be a valid email
- `password`: Required, minimum 8 characters, must be confirmed
- `password_confirmation`: Required, must match password

**Success Response (200):**
```json
{
    "message": "Password has been reset successfully."
}
```

**Error Response (400):**
```json
{
    "message": "This password reset token is invalid."
}
```

**Possible Error Messages:**
- "This password reset token is invalid."
- "We can't find a user with that email address."
- "This password reset token has expired."

---

## Email Template

The password reset email includes:

### HTML Version
- **Modern Design**: Gradient header with purple theme
- **Clear CTA Button**: Prominent "Reset Password" button
- **Security Information**: Token expiration notice (60 minutes)
- **Alternative Link**: Plain URL for users who can't click the button
- **Security Warning**: Notice if user didn't request the reset
- **Responsive Design**: Works on all devices

### Plain Text Version
- Simple, readable format for email clients that don't support HTML
- Contains all essential information
- Includes the reset URL

### Email Content Includes:
- User's name (if available)
- User's email address
- Reset password button/link
- Token expiration notice (60 minutes)
- Security warning
- Company branding

---

## Frontend Integration

### Example Flow

#### 1. Forgot Password Page
```javascript
// Request password reset
async function requestPasswordReset(email) {
    const response = await fetch('/api/forgot-password', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ email })
    });
    
    const data = await response.json();
    // Show success message to user
    console.log(data.message);
}
```

#### 2. Reset Password Page (from email link)
```javascript
// Extract token and email from URL
const urlParams = new URLSearchParams(window.location.search);
const token = urlParams.get('token');
const email = urlParams.get('email');

// Optional: Verify token before showing form
async function verifyToken(token, email) {
    const response = await fetch('/api/verify-reset-token', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ token, email })
    });
    
    const data = await response.json();
    return data.valid;
}

// Reset password
async function resetPassword(token, email, password, passwordConfirmation) {
    const response = await fetch('/api/reset-password', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            token,
            email,
            password,
            password_confirmation: passwordConfirmation
        })
    });
    
    const data = await response.json();
    
    if (response.ok) {
        // Redirect to login page
        window.location.href = '/login?reset=success';
    } else {
        // Show error message
        console.error(data.message);
    }
}
```

---

## Configuration

### Environment Variables

Add to your `.env` file:

```env
# Frontend URL for password reset links
FRONTEND_URL=http://localhost/smlars

# Mail Configuration
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your_username
MAIL_PASSWORD=your_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="noreply@fmdqgroup.com"
MAIL_FROM_NAME="${APP_NAME}"
```

### Mail Configuration Options

#### Development (Mailtrap)
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your_mailtrap_username
MAIL_PASSWORD=your_mailtrap_password
MAIL_ENCRYPTION=tls
```

#### Production (Gmail)
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your_email@gmail.com
MAIL_PASSWORD=your_app_password
MAIL_ENCRYPTION=tls
```

#### Production (SendGrid)
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.sendgrid.net
MAIL_PORT=587
MAIL_USERNAME=apikey
MAIL_PASSWORD=your_sendgrid_api_key
MAIL_ENCRYPTION=tls
```

---

## Testing

### Manual Testing with Postman/cURL

#### 1. Request Password Reset
```bash
curl -X POST http://localhost:8080/api/forgot-password \
  -H "Content-Type: application/json" \
  -d '{"email":"user@example.com"}'
```

#### 2. Verify Token
```bash
curl -X POST http://localhost:8080/api/verify-reset-token \
  -H "Content-Type: application/json" \
  -d '{
    "token":"your_token_here",
    "email":"user@example.com"
  }'
```

#### 3. Reset Password
```bash
curl -X POST http://localhost:8080/api/reset-password \
  -H "Content-Type: application/json" \
  -d '{
    "token":"your_token_here",
    "email":"user@example.com",
    "password":"newPassword123",
    "password_confirmation":"newPassword123"
  }'
```

---

## Security Features

1. **Token Expiration**: Reset tokens expire after 60 minutes
2. **Single Use Tokens**: Tokens are invalidated after successful password reset
3. **Email Verification**: Only the email owner can reset the password
4. **Secure Password Hashing**: Passwords are hashed using bcrypt
5. **Rate Limiting**: Consider adding rate limiting to prevent abuse
6. **Privacy Protection**: API doesn't reveal if email exists in the system

---

## Database Requirements

The password reset functionality uses Laravel's built-in password reset system, which requires the `password_reset_tokens` table. This should already exist if you've run Laravel's default migrations.

If not, run:
```bash
php artisan migrate
```

The table structure:
```sql
CREATE TABLE password_reset_tokens (
    email VARCHAR(255) PRIMARY KEY,
    token VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NULL
);
```

---

## Troubleshooting

### Email Not Sending

1. **Check Mail Configuration**
   ```bash
   php artisan config:clear
   php artisan cache:clear
   ```

2. **Test Mail Configuration**
   - Use Mailtrap.io for development testing
   - Check Laravel logs: `storage/logs/laravel.log`

3. **Verify Queue is Running** (if using queues)
   ```bash
   php artisan queue:work
   ```

### Token Invalid or Expired

1. **Check Token Expiration**
   - Default expiration is 60 minutes
   - Can be configured in `config/auth.php`

2. **Verify Email Matches**
   - Email in request must match the one used to request reset

### Frontend URL Issues

1. **Check FRONTEND_URL in .env**
   ```env
   FRONTEND_URL=http://your-frontend-url.com
   ```

2. **Clear Configuration Cache**
   ```bash
   php artisan config:clear
   ```

---

## Best Practices

1. **Use HTTPS in Production**: Always use HTTPS for password reset links
2. **Implement Rate Limiting**: Prevent abuse by limiting reset requests
3. **Log Security Events**: Log all password reset attempts
4. **User Notifications**: Notify users when password is changed
5. **Strong Password Requirements**: Enforce strong password policies
6. **Two-Factor Authentication**: Consider adding 2FA for additional security

---

## Next Steps

1. **Customize Email Template**: Modify `resources/views/emails/reset-password.blade.php` to match your brand
2. **Add Rate Limiting**: Implement rate limiting on password reset endpoints
3. **Create Frontend Pages**: Build forgot password and reset password UI
4. **Add Logging**: Log password reset attempts for security auditing
5. **Implement 2FA**: Add two-factor authentication for enhanced security

---

## Support

For issues or questions:
- Check Laravel documentation: https://laravel.com/docs/passwords
- Review application logs: `storage/logs/laravel.log`
- Contact development team

---

**Last Updated**: {{ date('Y-m-d') }}
**Version**: 1.0.0
