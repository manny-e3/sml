# Password Reset - Quick Start Guide

## 🚀 What Was Added

### 1. **API Endpoints** (3 new routes)
- `POST /api/forgot-password` - Request password reset
- `POST /api/reset-password` - Reset password with token
- `POST /api/verify-reset-token` - Verify token validity

### 2. **Backend Files**
- ✅ `app/Http/Controllers/Api/AuthController.php` - Updated with 3 new methods
- ✅ `app/Mail/ResetPasswordMail.php` - Mailable class for emails
- ✅ `resources/views/emails/reset-password.blade.php` - Beautiful HTML email template
- ✅ `resources/views/emails/reset-password-text.blade.php` - Plain text email template
- ✅ `routes/api.php` - Updated with new routes

### 3. **Frontend Demo Pages**
- ✅ `public/forgot-password.html` - Forgot password form
- ✅ `public/reset-password.html` - Reset password form

### 4. **Documentation**
- ✅ `PASSWORD_RESET_DOCUMENTATION.md` - Complete API documentation

---

## 📋 Quick Test (5 Minutes)

### Step 1: Configure Email (Development)
Add to your `.env` file:
```env
MAIL_MAILER=log
MAIL_FROM_ADDRESS="noreply@smlars.com"
MAIL_FROM_NAME="SMLARS"
```

For testing, use `log` driver to see emails in `storage/logs/laravel.log`

### Step 2: Test the Flow

1. **Visit Forgot Password Page**
   ```
   http://localhost:8080/forgot-password.html
   ```

2. **Enter an email** from your users table

3. **Check the log file** for the reset link:
   ```
   storage/logs/laravel.log
   ```

4. **Copy the reset URL** and open it in your browser

5. **Enter new password** and submit

---

## 🔧 Production Setup

### Option 1: Gmail (Easy)
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="noreply@smlars.com"
MAIL_FROM_NAME="SMLARS"
```

**Note**: Use [App Password](https://support.google.com/accounts/answer/185833), not your regular password!

### Option 2: Mailtrap (Development)
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your-mailtrap-username
MAIL_PASSWORD=your-mailtrap-password
MAIL_ENCRYPTION=tls
```

### Option 3: SendGrid (Production)
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.sendgrid.net
MAIL_PORT=587
MAIL_USERNAME=apikey
MAIL_PASSWORD=your-sendgrid-api-key
MAIL_ENCRYPTION=tls
```

---

## 📱 API Usage Examples

### 1. Request Password Reset
```bash
curl -X POST http://localhost:8080/api/forgot-password \
  -H "Content-Type: application/json" \
  -d '{"email":"user@example.com"}'
```

**Response:**
```json
{
  "message": "Password reset link has been sent to your email."
}
```

### 2. Verify Token (Optional)
```bash
curl -X POST http://localhost:8080/api/verify-reset-token \
  -H "Content-Type: application/json" \
  -d '{
    "token":"YOUR_TOKEN",
    "email":"user@example.com"
  }'
```

**Response:**
```json
{
  "message": "Token is valid.",
  "valid": true
}
```

### 3. Reset Password
```bash
curl -X POST http://localhost:8080/api/reset-password \
  -H "Content-Type: application/json" \
  -d '{
    "token":"YOUR_TOKEN",
    "email":"user@example.com",
    "password":"newPassword123",
    "password_confirmation":"newPassword123"
  }'
```

**Response:**
```json
{
  "message": "Password has been reset successfully."
}
```

---

## 🎨 Email Template Preview

The email includes:
- ✨ Modern gradient design (purple theme)
- 🔘 Clear "Reset Password" button
- ⏰ 60-minute expiration notice
- 🔗 Alternative plain text link
- ⚠️ Security warning
- 📱 Mobile responsive

---

## 🔐 Security Features

1. **Token Expiration**: 60 minutes
2. **Single Use**: Tokens invalidated after use
3. **Email Verification**: Only email owner can reset
4. **Secure Hashing**: Bcrypt password hashing
5. **Privacy**: Doesn't reveal if email exists

---

## 🐛 Troubleshooting

### Email Not Sending?
```bash
# Clear cache
php artisan config:clear
php artisan cache:clear

# Check logs
tail -f storage/logs/laravel.log
```

### Token Invalid?
- Tokens expire after 60 minutes
- Email must match exactly
- Token can only be used once

### Frontend URL Wrong?
Add to `.env`:
```env
FRONTEND_URL=http://your-frontend-url.com
```

Then clear config:
```bash
php artisan config:clear
```

---

## 📝 Next Steps

### For Development:
1. ✅ Test with `MAIL_MAILER=log`
2. ✅ Check `storage/logs/laravel.log` for emails
3. ✅ Test the full flow

### For Production:
1. 🔧 Configure real SMTP (Gmail/SendGrid)
2. 🎨 Customize email template colors/branding
3. 🔒 Add rate limiting to prevent abuse
4. 📊 Add logging for security audits
5. 🌐 Update `FRONTEND_URL` in production `.env`

---

## 📚 Files Reference

| File | Purpose |
|------|---------|
| `AuthController.php` | Password reset logic |
| `ResetPasswordMail.php` | Email configuration |
| `reset-password.blade.php` | HTML email template |
| `reset-password-text.blade.php` | Plain text email |
| `forgot-password.html` | Demo forgot password page |
| `reset-password.html` | Demo reset password page |
| `PASSWORD_RESET_DOCUMENTATION.md` | Full documentation |

---

## 💡 Tips

1. **Use Mailtrap** for development testing
2. **Use Gmail** for quick production setup
3. **Use SendGrid/Mailgun** for high-volume production
4. **Customize email colors** in `reset-password.blade.php`
5. **Add your logo** to the email template
6. **Test the flow** before deploying to production

---

## 🆘 Need Help?

Check the full documentation:
```
PASSWORD_RESET_DOCUMENTATION.md
```

Or check Laravel's password reset docs:
https://laravel.com/docs/passwords

---

**Created**: {{ date('Y-m-d') }}
**Version**: 1.0.0
