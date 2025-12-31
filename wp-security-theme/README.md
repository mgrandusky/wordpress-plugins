# WP Security Kit - WordPress Core Integration

Complete login security suite integrated directly into WordPress core through theme-based plugin architecture.

## 🔐 Features Implemented

### 1. **Limiting Login Attempts**
- Tracks failed login attempts by IP address
- Configurable maximum attempts (default: 5)
- Automatic IP lockout after max attempts
- Configurable lockout duration (default: 30 minutes)
- Stores attempts in `wp_login_attempts` database table

### 2. **Two-Factor Authentication (2FA)**
- Sends verification code to user email after login
- 6-digit verification code (15-minute expiry)
- Stores codes in `wp_two_factor_codes` database table
- Automatic code cleanup on successful verification
- Optional - can be enabled/disabled in settings

### 3. **Custom Login URL**
- Change login URL from `/wp-login.php` to custom path
- Example: `/secure-login` or `/admin-portal`
- Redirects old `/wp-login.php` to custom URL
- Updates all login links throughout site

### 4. **CAPTCHA on Login Form**
- Simple math-based CAPTCHA (e.g., "5 + 3 = ?")
- Prevents automated bot attacks
- Session-based answer verification
- No external dependencies required

### 5. **Enforce Strong Passwords**
- Minimum 8 characters
- Requires at least one letter
- Requires at least one number
- Requires at least one special character (!@#$%^&*...)
- Applied to user registration and profile updates

### 6. **Password Expiration Policy**
- Force password changes after set number of days
- Configurable expiry period (default: 90 days)
- Stores password change time in user meta
- Admin notice when password expired
- Redirects to profile for password reset

## 📁 File Structure

```
wp-security-theme/
├── functions.php              # Theme setup & plugin loader
├── style.css                  # Theme stylesheet
├── wp-security-core.php       # Core security plugin (597 lines)
└── README.md                  # This file
```

## 🚀 How It Works

### Architecture
1. **Theme-based Integration**: Security features load through theme `functions.php`
2. **Plugin-style Code**: `wp-security-core.php` acts as a full-featured WordPress plugin
3. **WordPress Hooks**: Uses standard WP hooks and filters
4. **Database Tables**: Custom tables for login attempts, 2FA codes, password history

### Activation Flow
```
WordPress Loads Theme
    ↓
functions.php loaded
    ↓
wp-security-core.php required
    ↓
Plugin initialization hooks run
    ↓
Security features active
    ↓
Admin Settings Page available at Settings > WP Security Kit
```

## 🗄️ Database Tables Created

### wp_login_attempts
Tracks login attempts for rate limiting
```sql
- id (BIGINT, Primary Key)
- ip_address (VARCHAR 45)
- user_login (VARCHAR 60)
- attempt_time (DATETIME)
- success (TINYINT, 0=failed, 1=success)
- Indexes on: ip_address, attempt_time
```

### wp_two_factor_codes
Stores 2FA verification codes
```sql
- id (BIGINT, Primary Key)
- user_id (BIGINT)
- code (VARCHAR 10)
- expires_at (DATETIME)
- created_at (DATETIME)
- Indexes on: user_id, expires_at
```

### wp_password_history
Tracks password changes for expiration
```sql
- id (BIGINT, Primary Key)
- user_id (BIGINT)
- password_hash (VARCHAR 255)
- changed_at (DATETIME)
- Indexes on: user_id
```

## ⚙️ Configuration

Access settings at: **WordPress Admin → Settings → WP Security Kit**

### Login Security Options

**Limit Login Attempts**
- Enable/Disable toggle
- Maximum attempts (1-20, default: 5)
- Lockout duration in minutes (5-240, default: 30)

**Two-Factor Authentication (2FA)**
- Enable/Disable toggle
- Sends 6-digit code to user email
- 15-minute code expiration

**Custom Login URL**
- Enter custom login path (e.g., "secure-login")
- Leave blank to use default `/wp-login.php`
- Site automatically redirects old login URL

**CAPTCHA on Login**
- Enable/Disable toggle
- Simple math verification
- No external API needed

### Password Security Options

**Enforce Strong Password Policy**
- Enable/Disable toggle
- Requires: 8+ chars, letters, numbers, special chars
- Applied to all password changes and registrations

**Enforce Password Expiration**
- Enable/Disable toggle
- Expiry period in days (30-365, default: 90)
- Forces users to change password periodically

## 🔧 Technical Implementation

### Hooks Used

**Authentication Hooks:**
- `wp_login_failed` - Record failed login attempts
- `wp_authenticate_user` - Check login attempts, verify 2FA, verify CAPTCHA
- `user_profile_update_errors` - Validate password strength
- `registration_errors` - Validate registration passwords

**Admin Hooks:**
- `admin_menu` - Add settings page
- `admin_init` - Register settings
- `admin_notices` - Show password expiry warning

**Login Hooks:**
- `login_form` - Add 2FA code field and CAPTCHA
- `site_url` / `network_site_url` - Replace login URLs

**Cleanup Hooks:**
- `wp_scheduled_delete` - Clean old attempts and expired codes

### Functions Reference

**Login Attempt Limiting:**
- `wpsec_record_login_failure()` - Records failed attempt
- `wpsec_check_login_attempts()` - Validates attempt count

**Two-Factor Authentication:**
- `wpsec_generate_and_send_2fa_code()` - Sends verification code
- `wpsec_verify_2fa_code_submission()` - Verifies code
- `wpsec_2fa_code_field()` - Displays code input field

**Custom Login URL:**
- `wpsec_custom_login_redirect()` - Redirects old URLs
- `wpsec_replace_wp_login_url()` - Updates login links

**CAPTCHA:**
- `wpsec_add_login_captcha()` - Displays math question
- `wpsec_verify_login_captcha()` - Validates answer

**Password Policy:**
- `wpsec_validate_user_password()` - User profile validation
- `wpsec_validate_registration_password()` - Registration validation

**Password Expiration:**
- `wpsec_check_password_expiry()` - Checks expiration status
- `wpsec_password_expiry_notice()` - Shows expiry warning
- `wpsec_update_password_change_time()` - Updates change timestamp

## 📊 Security Features Summary

| Feature | Database | Email | IP-Based | Configurable |
|---------|----------|-------|----------|--------------|
| Login Attempt Limiting | ✅ | ❌ | ✅ | ✅ |
| 2FA Authentication | ✅ | ✅ | ❌ | ✅ |
| Custom Login URL | ❌ | ❌ | ❌ | ✅ |
| CAPTCHA Verification | ✅ | ❌ | ❌ | ✅ |
| Strong Password Policy | ❌ | ❌ | ❌ | ✅ |
| Password Expiration | ✅ | ❌ | ❌ | ✅ |

## 🔒 Security Considerations

### Best Practices Implemented
- SQL injection prevention with prepared statements
- XSS prevention with proper escaping
- Admin-only settings access with `manage_options` capability
- Secure 2FA code generation (random 6-digit)
- Password history tracking (for future duplicate prevention)
- Automatic cleanup of old data

### Additional Recommendations
1. Use HTTPS for all login traffic
2. Implement regular WordPress/plugin updates
3. Use strong server firewall rules
4. Monitor failed login attempts regularly
5. Implement regular database backups
6. Use Web Application Firewall (WAF) if available

## 🧪 Testing

### Test Login Attempt Limiting
1. Go to login page
2. Try incorrect password 5 times (default max)
3. Should see "Too many login attempts" error
4. Wait 30 minutes (default) or change setting to smaller value

### Test 2FA
1. Enable 2FA in settings
2. Log in with valid credentials
3. Check email for verification code
4. Enter code on login page
5. Should be logged in successfully

### Test Custom Login URL
1. Set custom login URL (e.g., "my-secure-login")
2. Try accessing `/wp-login.php` - should redirect
3. Try accessing `/my-secure-login` - should show login form
4. All login links should use new URL

### Test CAPTCHA
1. Enable CAPTCHA in settings
2. Go to login page
3. Should see math question (e.g., "5 + 3 = ?")
4. Enter wrong answer - should show error
5. Enter correct answer - allow login to proceed

### Test Password Policy
1. Enable strong password policy
2. Try creating user with weak password
3. Should show validation errors
4. Create user with strong password - should succeed

### Test Password Expiration
1. Enable password expiration (set to 1 day for testing)
2. Log in to admin
3. Should see expiry warning after 1 day
4. Change password - warning disappears

## 📝 Settings Storage

All settings stored in WordPress options table:
```php
get_option('wpsec_options', []);
```

Contains array:
```php
[
    'limit_login_attempts' => 1,           // boolean
    'login_attempts_max' => 5,             // int 1-20
    'login_attempts_lockout' => 30,        // int minutes
    'enable_2fa' => 1,                     // boolean
    'custom_login_url' => 'secure-login',  // string
    'add_captcha' => 1,                    // boolean
    'enforce_password_policy' => 1,        // boolean
    'enforce_password_expiry' => 1,        // boolean
    'password_expiry_days' => 90,          // int days
]
```

## 🚨 Troubleshooting

### Login Attempts Table Growing Large
- Run cleanup: `wp_scheduled_delete` hook runs daily
- Manual cleanup: Delete old entries from `wp_login_attempts`

### 2FA Codes Not Received
- Check WordPress mail configuration
- Verify user email address is correct
- Check spam folder
- Review site logs for mail errors

### Can't Access Login URL
- Check custom login URL setting
- Try `/wp-login.php` directly
- Clear browser cache
- Check `.htaccess` rules don't block custom URL

### Password Not Expiring
- Verify password expiration is enabled
- Check user hasn't changed password recently
- Verify user meta is saving correctly
- Check database `wpsec_password_changed` meta

## 📖 File Details

**functions.php** (29 lines)
- Loads security plugin during theme initialization
- Minimal overhead - just requires core plugin file
- Works with any WordPress installation

**wp-security-core.php** (597 lines)
- Complete security implementation
- Handles all authentication and validation
- Manages database tables and options
- Implements all 6 security features
- Fully self-contained and portable

## ✨ Integration Benefits

✅ **Core Integration**: Direct WordPress integration, no external dependencies
✅ **High Performance**: Minimal queries, efficient table design
✅ **Admin Friendly**: Intuitive settings page in WordPress admin
✅ **Secure**: Uses WordPress security functions and best practices
✅ **Flexible**: Each feature can be enabled/disabled independently
✅ **Portable**: Can be moved to other WordPress installations
✅ **Maintainable**: Well-documented, organized code structure

---

**Installation Complete!** All security features are now integrated into your WordPress site.
