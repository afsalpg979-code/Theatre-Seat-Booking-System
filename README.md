# FALCONS Theater — Theatre Seat Booking System

A PHP/MySQL theatre booking application for local XAMPP deployment. The project includes customer authentication, movie listings, seat selection, bookings, payment flows, email/OTP support, tickets, ratings, contact messages and an admin area.

## Current maintenance branch

The active cleanup/fix work is being developed on `bugfix/stability-pass`. Keep `main` as the stable baseline until the local XAMPP test cycle is complete.

## Requirements

- Windows + XAMPP (Apache and MySQL)
- PHP 8.x recommended
- MySQL/MariaDB supplied by XAMPP
- Composer for PHPMailer/Razorpay dependencies where required
- A modern browser

## Installation

1. Copy the project into:
   `C:\xampp\htdocs\THEATRE BOOKING SYSTEM`
2. Start **Apache** and **MySQL** in XAMPP.
3. Open phpMyAdmin and create a database named `cinema_db`.
4. Import the project's database SQL/schema file. Prefer the maintained SQL/schema file over executing schema changes from application pages.
5. Check `db.php` and `config.php` and change the local database credentials if your MySQL installation is not using `root` with an empty password.
6. Install PHP dependencies from `composer.json` with Composer when the project is deployed from a clean checkout.
7. Configure mail/Razorpay/Google credentials in local configuration. **Do not commit real passwords, SMTP app passwords, API secrets or private keys.**
8. Open:
   `http://localhost/THEATRE%20BOOKING%20SYSTEM/`

## Configuration

### Database

The application uses `cinema_db`. Database connection failures are logged server-side and do not expose the raw MySQL error to visitors.

### Email / OTP

Configure the mail settings used by `mailer.php` / `mail_config.php`. Gmail requires an App Password when using SMTP with 2-step verification.

OTP fallback is disabled by default because displaying or logging a real OTP when mail delivery fails is unsafe. If a private development fallback is ever added, it must never be enabled in production.

### Razorpay

Use test keys during development. Keep the Razorpay secret outside Git. Payment amounts and payment verification must be performed server-side; never trust a browser-supplied total as proof of payment.

### Google Sign-In

Set the Google client ID through a private deployment configuration rather than committing production credentials.

## Main application flow

```text
Login / Signup / OTP
        ↓
Movie listing
        ↓
Movie + show selection
        ↓
Seat selection
        ↓
Server-side booking validation
        ↓
Payment method
   ┌────┼────┐
   ↓    ↓    ↓
Razorpay Card Cash
   └────┼────┘
        ↓
Booking / payment record
        ↓
Ticket / confirmation / history
```

## Security baseline

- Use prepared statements for values supplied by users.
- Validate and normalize all POST/GET input before using it.
- Escape output with `htmlspecialchars()` when rendering HTML.
- Do not use `mysqli_real_escape_string()` as a substitute for prepared statements.
- Never trust `totalAmount`, seat price, payment status or payment IDs sent by JavaScript.
- Validate seat numbers and enforce the allowed seat range on the server.
- Use password hashing with `password_hash()` and verify with `password_verify()`.
- Regenerate the session ID after successful authentication.
- Keep OTPs, SMTP passwords, Razorpay secrets and Google credentials out of source control.
- Do not expose SQL/MySQL exception text to visitors.
- Add CSRF protection to state-changing forms/endpoints before production deployment.
- Restrict admin-only pages with server-side authorization; hiding an admin link is not security.

## Database

The principal application tables include:

- `clients` — customer accounts
- `bookings` — theatre bookings and selected seats
- `payments` — payment records and gateway information
- `upcoming_movies` — movie catalogue/show information
- `ratings` — movie ratings/reviews
- `messages` — contact/customer messages
- `admin` — administrator accounts

The exact schema should be taken from the maintained SQL/setup file in the repository rather than being altered automatically during normal page requests.

## File organization

Keep only the active application files and their dependencies. Files with names such as `(1)`, `.save`, or other backup/test copies should not be used by the application. Do not reintroduce duplicate page versions unless they have a distinct, documented purpose.

Important application areas include:

- Customer/authentication pages
- Booking and seat APIs
- Payment pages and Razorpay endpoints
- Ticket/print pages
- Profile/history pages
- Admin pages
- Shared configuration, validation, logging and mail helpers
- CSS/JavaScript/image assets actually referenced by the application

## Testing checklist

After installation, test these flows in order:

1. Database connection
2. Signup
3. OTP delivery and verification
4. Normal login/logout
5. Password reset
6. Movie listing and language filters
7. Seat selection
8. Already-booked seat rejection
9. Booking total calculation
10. Cash payment
11. Card/demo payment
12. Razorpay test payment and server-side verification
13. Ticket/confirmation
14. Booking history and payment history
15. Rating/contact forms
16. Admin login and protected admin pages
17. Movie management
18. Customer/message management
19. Responsive layout and browser console errors

## Important deployment note

This project is designed for local development/XAMPP. Before public deployment, move database and third-party credentials to environment/private configuration, enable HTTPS, review CSRF coverage, configure secure session cookies, disable development/debug features and test every payment callback with gateway test credentials.

## License

FALCONS Theater © 2026.
