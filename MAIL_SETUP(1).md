# Mail Setup

## What this project sends

- OTP emails from `login.php`
- Ticket emails after payment from `card.php`
- Ticket emails after payment from `cash.php`
- Ticket emails after Razorpay verification from `razorpay_verify_payment.php`

All of them use the same SMTP code in `mailer.php`.

## Files involved

- `mail_config.php`: SMTP settings
- `mailer.php`: mail creation, OTP sending, ticket sending, error handling
- `mail_test.php`: manual test page for SMTP verification
- `logs/error_YYYY-MM-DD.log`: mail failure log
- `logs/warning_YYYY-MM-DD.log`: OTP fallback log

## Recommended Gmail setup

1. Sign in to the Gmail account you want to send from.
2. Turn on Google 2-Step Verification.
3. Create a Google App Password for Mail.
4. Use that 16-character app password in this project.

## Where to put the credentials

You can configure mail in either of these ways:

### Option 1: Edit `mail_config.php`

Set these values:

```php
'username' => 'yourgmail@gmail.com',
'password' => 'your16characterapppassword',
'from_email' => 'yourgmail@gmail.com',
'from_name' => 'FALCONS Theater',
```

### Option 2: Use environment variables

Supported variables:

```text
MAIL_HOST
MAIL_PORT
MAIL_SECURE
MAIL_USERNAME
MAIL_PASSWORD
MAIL_FROM_EMAIL
MAIL_FROM_NAME
MAIL_TIMEOUT
MAIL_DEBUG
```

Environment variables override the defaults in `mail_config.php`.

## How to test

1. Open `http://localhost/THEATRE%20BOOKING%20SYSTEM/mail_test.php`
2. Enter a recipient email address.
3. Click `Send Test Email`.
4. If it succeeds, OTP and ticket emails should also work because they use the same mailer.

## Current end-to-end flow

### OTP flow

1. User requests OTP in `login.php`
2. `sendOtpMail()` in `mailer.php` sends the email
3. If email fails, the app logs the error and shows a development OTP fallback message

### Ticket flow

1. User completes payment in `card.php`, `cash.php`, or Razorpay
2. Booking email address is resolved from the logged-in user session
3. `sendTicketMail()` in `mailer.php` sends the ticket email
4. A direct ticket link to `print1.php?id=BOOKING_ID` is included

## If email is failing

Check:

- Gmail address and app password belong to the same Google account
- 2-Step Verification is still enabled
- App password is new and active
- `mail_config.php` values match exactly
- latest errors in `logs/error_YYYY-MM-DD.log`

If logs show `SMTP Error: Could not authenticate.`, Gmail rejected the login. The usual fix is creating a fresh app password and replacing the current one.
