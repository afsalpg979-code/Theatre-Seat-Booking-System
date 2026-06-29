# FALCONS Theater - Booking System
## Complete Theater Booking Web Application

### Overview
FALCONS Theater is a comprehensive web-based cinema ticket booking system with admin dashboard, payment processing, and user management.

---

## Features

### User Features
- ✅ User Registration & Login with OTP verification
- ✅ Browse movies by language (English, Hindi, Malayalam, Tamil)
- ✅ Interactive seat selection for bookings
- ✅ Multiple payment options (UPI/Razorpay, Card, Cash)
- ✅ Ticket confirmation via email
- ✅ User profile management
- ✅ Booking history
- ✅ Payment history tracking
- ✅ Movie ratings and reviews
- ✅ Contact message system

### Admin Features
- ✅ Admin Dashboard with statistics
- ✅ Manage upcoming movies
- ✅ View all bookings with pagination
- ✅ View payment history
- ✅ Manage customer messages
- ✅ View customer ratings
- ✅ Client management
- ✅ Enhanced admin interface

### Security Features
- ✅ Prepared statements to prevent SQL injection
- ✅ Input validation for all forms
- ✅ CSRF token protection
- ✅ Session management
- ✅ Error logging
- ✅ Activity logging
- ✅ Password hashing (bcrypt)

---

## Installation & Setup

### 1. Database Setup
- Create MySQL database: `cinema_db`
- Run `setup_database.php` to create all tables automatically
- Or manually import the schema from `database_link.txt`

### 2. Configuration
- Update `db.php` with your database credentials
- Update `mail_config.php` with Gmail SMTP settings
- Update `razorpay_config.php` with your Razorpay keys

### 3. File Structure
```
THEATRE BOOKING SYSTEM/
├── index.php                      # Main home page
├── login.php                      # User login/signup
├── profile.php                    # User profile & booking history
├── booking-confirmation.php       # Seat selection & booking
├── buy.php                        # Payment method selection
├── card.php                       # Card payment
├── cash.php                       # Cash payment
├── QR.php                         # UPI/Razorpay payment
├── admin_login.php               # Admin login
├── AdminOnly.php                 # Admin main dashboard
├── admin_dashboard_enhanced.php  # Enhanced admin dashboard
├── upcoming.php                  # Manage movies
├── customer.php                  # Customer messages
├── rating.php                    # Movie ratings
├── ClientDetail.php              # Client management
├── about.php                     # About page
├── contact.php                   # Contact page
├── Rate.php                      # Rate movies
├── db.php                        # Database connection
├── config.php                    # Configuration
├── utils.php                     # Utility functions
├── validation.php                # Form validation rules
├── mailer.php                    # Email functions
├── logger.php                    # Logging system
├── mail_config.php               # Email configuration
├── razorpay_config.php           # Razorpay configuration
├── razorpay_helpers.php          # Razorpay helper functions
├── razorpay_create_order.php     # Create Razorpay order
├── razorpay_verify_payment.php   # Verify Razorpay payment
├── razorpay_payment_status.php   # Check payment status
├── print1.php                    # Print ticket
├── theme.css                     # Styling
└── README.md                     # This file
```

---

## User Guide

### User Registration & Login
1. Visit `login.php`
2. Create account with username, email, and password
3. OTP verification via email
4. Login with credentials

### Booking a Ticket
1. Browse movies on home page
2. Click "Book Now" on any movie
3. Select seats (1-50 seats per booking)
4. Enter name and phone number
5. Review total amount
6. Select payment method

### Payment Methods
- **UPI/Razorpay**: Real-time payment with Razorpay gateway
- **Card**: Credit/Debit card payment (demo)
- **Cash**: Counter payment with balance calculation

### View Profile & History
1. Click profile avatar on top right
2. View profile information
3. Update username/email
4. View booking history
5. View payment history

---

## Admin Guide

### Access Admin Panel
1. Visit `admin_login.php`
2. Username: `AFSALPG`
3. Password: `560396`
4. Click "Main Admin Dashboard" or "Enhanced Dashboard"

### Admin Tasks
- **Add Movies**: Manage upcoming releases
- **View Bookings**: See all user bookings with pagination
- **Payment History**: Track all payments
- **Customer Messages**: Read and manage messages
- **Ratings**: View movie ratings
- **Clients**: Manage registered users

### Statistics
- Total Bookings
- Total Payments
- Total Clients
- Total Ratings

---

## Utility Functions

### Input Validation (utils.php)
```php
validateEmail($email)           // Validate email format
validatePhone($phone)           // Validate phone number (10-15 digits)
validateAmount($amount)         // Validate monetary amount
validateSeatNumbers($seats)     // Validate seat selection
validateMovieName($movieName)   // Validate movie name
```

### Database Helpers (utils.php)
```php
dbFetch($conn, $query, $params)          // Fetch single row
dbFetchAll($conn, $query, $params)       // Fetch multiple rows
dbInsert($conn, $table, $data)           // Insert record
dbUpdate($conn, $table, $data, $where)   // Update record
dbDelete($conn, $table, $id)             // Delete record
dbCount($conn, $table, $where)           // Count records
```

### Formatting Helpers (utils.php)
```php
formatDateTime($date)      // Format to "01 Jan 2024, 10:30 AM"
formatCurrency($amount)    // Format as "Rs 1,000.00"
generateBookingCode($id)   // Generate code like "FT000001"
truncateText($text, $len)  // Truncate text with ellipsis
```

### Security Functions (utils.php)
```php
generateCSRFToken()        // Generate CSRF token
verifyCSRFToken($token)    // Verify CSRF token
sanitizeInput($input)      // Sanitize and escape input
startSecureSession()       // Start secure session
logout()                   // Safe logout
```

---

## Validation Rules

### Booking Validation (validation.php)
```php
ValidationRules::validateBooking($data)
// Validates: name, phone, seats, movie, totalAmount
```

### Payment Validation
```php
ValidationRules::validatePayment($data)
// Validates: booking_id, amount
```

### Card Validation
```php
ValidationRules::validateCardData($data)
// Validates: card_name, card_number (Luhn check), expiry, CVV
```

---

## Email Configuration

### Gmail SMTP Setup
1. Edit `mail_config.php`
2. Add your Gmail address
3. Generate App Password (2-factor auth required)
4. Add app password to config

```php
'username' => 'your-email@gmail.com',
'password' => 'your-16-char-app-password',
'from_email' => 'your-email@gmail.com',
```

### Email Templates
- Booking confirmation
- OTP verification
- Ticket delivery

---

## Razorpay Integration

### Setup
1. Create Razorpay account
2. Get API keys from dashboard
3. Update `razorpay_config.php`:

```php
'key_id' => 'YOUR_RAZORPAY_KEY_ID',
'key_secret' => 'YOUR_RAZORPAY_KEY_SECRET',
```

### Payment Flow
1. User selects UPI payment
2. Creates order via `razorpay_create_order.php`
3. Opens Razorpay checkout
4. Verifies payment with `razorpay_verify_payment.php`
5. Sends confirmation email
6. Redirects to ticket page

---

## Database Schema

### bookings
- id, name, phone, seats, movie, totalAmount, time, created_at

### payments
- id, booking_id, name, phone, movie, seats, totalAmount, cash_given, balance, payment_method, payment_status, gateway details

### clients
- id, username, email, password, phone, created_at, last_login

### admin
- id, username, password, email, created_at, last_login

### upcoming_movies
- id, title, language, image, rating, votes, description, release_date

### ratings
- id, movie_id, user_id, stars, comment, submitted_at

### messages
- id, name, email, subject, message, status, created_at

---

## Security Best Practices

1. **SQL Injection Prevention**: All queries use prepared statements
2. **CSRF Protection**: CSRF tokens on all forms
3. **XSS Prevention**: HTML escaping on all outputs
4. **Password Security**: Bcrypt hashing for passwords
5. **Session Management**: Secure session handling with regeneration
6. **Input Validation**: Comprehensive validation on all inputs
7. **Logging**: Activity and error logging for audit trail

---

## Logging

### Log Files
Located in `/logs` directory:
- `error_YYYY-MM-DD.log` - System errors
- `activity_YYYY-MM-DD.log` - User activities
- `security_YYYY-MM-DD.log` - Security events
- `query_YYYY-MM-DD.log` - Database queries

### Usage
```php
require_once 'logger.php';

Logger::error('Error message', ['context' => 'data']);
Logger::activity('User booked ticket', $userId, ['booking_id' => 123]);
Logger::security('Failed login attempt', ['ip' => '192.168.1.1']);
```

---

## Troubleshooting

### Email Not Sending
- Check `mail_config.php` credentials
- Verify Gmail 2-factor auth and app password
- Check logs in `/logs/error_*.log`

### Payment Issues
- Verify Razorpay keys in `razorpay_config.php`
- Check payment status in admin dashboard
- Review error logs

### Booking Issues
- Validate input using ValidationRules class
- Check database connection
- Verify seat availability

---

## Support & Contact

For issues or questions:
- Email: admin@falconstheater.com
- Visit: Contact page on website

---

## License

FALCONS Theater © 2024. All rights reserved.

---

## Version
**Current Version**: 2.0
**Last Updated**: April 2024
