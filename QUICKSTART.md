# FALCONS Theater - Quick Start Guide

## Installation Steps (5 minutes)

### Step 1: Extract Files
- Extract the project folder to `C:\xampp\htdocs\`
- Folder name: `THEATRE BOOKING SYSTEM`

### Step 2: Start XAMPP
- Start Apache & MySQL services in XAMPP Control Panel

### Step 3: Create Database
- Open http://localhost/phpmyadmin
- Create new database: `cinema_db`
- Charset: `utf8mb4_unicode_ci`

### Step 4: Run Setup
- Visit: `http://localhost/THEATRE%20BOOKING%20SYSTEM/setup_database.php`
- This automatically creates all required tables

### Step 5: Configure Email (Optional)
- Edit: `/mail_config.php`
- Add your Gmail address
- Generate App Password from Google Account
- Save file

### Step 6: Configure Payments (Optional)
- Edit: `/razorpay_config.php`
- Add Razorpay API keys
- Save file

---

## Access the Application

### User Access
- **Home Page**: `http://localhost/THEATRE%20BOOKING%20SYSTEM/index.php`
- **Login**: Click "Login" or visit `http://localhost/THEATRE%20BOOKING%20SYSTEM/login.php`
- **Create Account**: Use signup form
- **Verify OTP**: Check email for OTP code

### Admin Access
- **Admin Panel**: `http://localhost/THEATRE%20BOOKING%20SYSTEM/admin_login.php`
- **Default Username**: `AFSALPG`
- **Default Password**: `560396`
- **Main Dashboard**: After login, click "Main Admin Dashboard"

---

## First Time Setup Checklist

- [ ] Database created and tables setup
- [ ] XAMPP services running (Apache & MySQL)
- [ ] Able to access home page
- [ ] Able to login as user
- [ ] Able to access admin panel
- [ ] (Optional) Email configured with Gmail
- [ ] (Optional) Razorpay keys configured

---

## New Features Added (v2.0)

### User Features
1. **User Profile Page** (`profile.php`)
   - Update username and email
   - View booking history
   - View payment history
   - Tab-based interface

2. **Enhanced User Menu**
   - Link to profile
   - Link to bookings
   - Link to payments
   - Quick logout

3. **Input Validation**
   - Phone number validation
   - Email validation
   - Amount validation
   - Seat number validation

### Security Features
1. **Prepared Statements**
   - All database queries now use prepared statements
   - Prevents SQL injection attacks

2. **Utility Functions** (`utils.php`)
   - Database helpers for CRUD operations
   - Input validation functions
   - Formatting functions
   - CSRF token generation

3. **Validation Rules** (`validation.php`)
   - Booking validation
   - Payment validation
   - Card validation with Luhn check
   - Movie validation
   - User profile validation

4. **Error Logging** (`logger.php`)
   - Activity logging
   - Error logging
   - Security event logging
   - Query logging

5. **Bootstrap File** (`bootstrap.php`)
   - Include all essential files
   - Centralized error handling
   - Configuration loading

### Admin Features
1. **Enhanced Admin Dashboard** (`admin_dashboard_enhanced.php`)
   - Statistics cards (total bookings, payments, clients, ratings)
   - Quick links to all admin functions
   - Paginated bookings table
   - Recent payments section
   - Professional styling

2. **Pagination Support**
   - Configurable page size
   - Previous/Next buttons
   - Direct page navigation

3. **Better Permissions**
   - Clear admin login verification
   - Session security

---

## File Structure

### Core Files
- `db.php` - Database connection
- `app_config.php` - Centralized configuration
- `utils.php` - Utility functions (200+ lines)
- `validation.php` - Form validation rules
- `logger.php` - Logging system
- `bootstrap.php` - Application bootstrap
- `mailer.php` - Email functionality

### Page Files
- `index.php` - Home page (updated with profile links)
- `login.php` - User login/signup
- `profile.php` - User profile (NEW)
- `booking-confirmation.php` - Seat selection (improved with validation)
- `buy.php` - Payment method selection
- `admin_login.php` - Admin login
- `AdminOnly.php` - Admin main dashboard
- `admin_dashboard_enhanced.php` - Enhanced admin dashboard (NEW)

### Database Files
- `setup_database.php` - Automatic database setup (NEW)
- `database_link.txt` - SQL schema

### Documentation
- `README.md` - Complete documentation (NEW)
- `QUICKSTART.md` - This file

---

## Common Issues & Solutions

### Issue: Database Connection Error
**Solution:**
- Check MySQL service is running
- Verify database name in `db.php` is `cinema_db`
- Check username/password in `db.php`

### Issue: "Table doesn't exist" Error
**Solution:**
- Visit `setup_database.php` to create tables
- Or manually run SQL from `database_link.txt`

### Issue: Email not sending
**Solution:**
- Check `mail_config.php` has correct Gmail address
- Verify app password (not Gmail password)
- Enable 2-factor authentication on Gmail account

### Issue: Can't login as admin
**Solution:**
- Default username: `AFSALPG` (case-sensitive)
- Default password: `560396`
- Check database has admin table populated

### Issue: Razorpay not working
**Solution:**
- Check `razorpay_config.php` has correct keys
- Use test mode keys for development
- Verify Razorpay account is active

---

## Testing the System

### Test User Booking
1. Create new user account via login page
2. Verify OTP from email (or development mode)
3. Browse movies on home page
4. Select movie and seats
5. Try different payment methods
6. Verify ticket email received
7. Check booking in profile

### Test Admin Functions
1. Login as admin with default credentials
2. Add a new upcoming movie
3. View all bookings in dashboard
4. View payment history
5. Check statistics
6. Manage clients

---

## Development Tips

### Enable Debug Mode
Edit `app_config.php`:
```php
const ENABLE_DEBUG = true;  // Set to true
```

### View Logs
Logs are stored in `/logs/` directory:
- `error_YYYY-MM-DD.log`
- `activity_YYYY-MM-DD.log`
- `security_YYYY-MM-DD.log`

### Database Debugging
Use PhpMyAdmin at `http://localhost/phpmyadmin`
- Check table structures
- Review data
- Run SQL queries

---

## Useful Functions Reference

### Validation
```php
validateEmail('test@example.com')           // true/false
validatePhone('9876543210')                 // true/false
validateAmount(1000.50)                     // true/false
validateSeatNumbers([1, 2, 3])             // true/false
ValidationRules::validateBooking($data)     // Returns validation result
```

### Database
```php
dbFetch($conn, "SELECT * FROM bookings WHERE id = ?", [1], 'i')
dbFetchAll($conn, "SELECT * FROM bookings")
dbInsert($conn, 'bookings', ['name' => 'John', 'phone' => '9876543210'])
dbUpdate($conn, 'bookings', ['name' => 'Jane'], 123)
dbDelete($conn, 'bookings', 123)
dbCount($conn, 'bookings')
```

### Formatting
```php
formatDateTime($date)                   // "01 Jan 2024, 10:30 AM"
formatCurrency(1000)                    // "Rs 1,000.00"
generateBookingCode(123)                // "FT000123"
sanitizeInput($userInput)               // Safe string
```

### Security
```php
generateCSRFToken()                     // Generate token
verifyCSRFToken($token)                 // Verify token
logout()                                // Safe logout
requireLogin()                          // Redirect if not logged in
requireAdminLogin()                     // Redirect if not admin
```

---

## Support

For issues or questions:
1. Check logs in `/logs/` directory
2. Review `README.md` for detailed documentation
3. Check browser console for JavaScript errors
4. Verify database in PhpMyAdmin

---

## Next Steps

1. **Customize**: Modify theme colors and branding
2. **Add Features**: Extend with new payment methods
3. **Deploy**: Move to production server
4. **Monitor**: Check logs regularly
5. **Backup**: Regular database backups

---

**Version**: 2.0  
**Last Updated**: April 2024  
**Status**: Ready for Production
