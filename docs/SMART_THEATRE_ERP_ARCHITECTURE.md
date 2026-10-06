# Smart Theatre ERP Architecture

## Project goal

Transform the existing FALCONS Theater booking application into an integrated theatre ERP while preserving the current customer booking, payment, ticket, OTP, ratings and admin functionality.

## Major modules

1. Identity & Access
2. Theatre / Branch Management
3. Movie Management
4. Screen Management
5. Seat Management
6. Show & Schedule Management
7. Booking Management
8. Payment & Billing
9. E-Ticket & QR Entry
10. Food & Beverage POS
11. Inventory Management
12. Supplier & Procurement
13. HR & Employee Management
14. Asset & Maintenance Management
15. Finance & Cash Management
16. Reports & Business Intelligence
17. AI & Prediction
18. Security, Audit & System Administration

## Existing functionality to preserve

- Customer registration/login and OTP
- Movie browsing and upcoming movies
- Seat selection and bookings
- Cash/card/UPI/Razorpay payment flows
- QR/ticket printing
- Customer profiles and booking history
- Ratings/reviews
- Admin dashboard
- Email/SMTP support
- Existing security utilities and logging

## Target application layers

```
Presentation
  -> Customer Web / Admin ERP / Employee Portal / Android WebView
Application
  -> Controllers / Services / Validation / Authorization
Domain
  -> Booking / Theatre / Inventory / HR / Finance / Maintenance / AI
Data
  -> MariaDB/MySQL + transactions + reporting views
Integration
  -> Razorpay / SMTP / QR / AI provider / notifications
Operations
  -> Logs / backups / audit trail / scheduled jobs
```

## ERP data domains

### Theatre operations
- branches
- screens
- seat maps
- seat categories
- movies
- shows
- show seats

### Commercial operations
- bookings
- booking items
- payments
- refunds
- tickets
- POS orders

### Inventory
- products
- suppliers
- purchase orders
- purchase items
- goods receipts
- stock transactions

### Workforce
- departments
- designations
- employees
- shifts
- attendance
- leave
- payroll

### Assets
- assets
- maintenance requests
- maintenance work orders

### Finance
- revenue entries
- expense entries
- cash registers
- cash movements

### Governance
- roles
- permissions
- audit logs
- notifications
- system settings

### Intelligence
- AI queries
- AI predictions
- analytics/reporting views

## Development phases

### Phase 1 — ERP foundation
Database schema, role/permission model, theatre/branch/screen/seat/show entities and migration strategy.

### Phase 2 — Booking modernization
Connect existing booking flow to structured movie/show/seat entities. Preserve legacy booking data during migration.

### Phase 3 — Operations ERP
Inventory, POS, procurement, suppliers, employees, shifts and maintenance.

### Phase 4 — Finance
Revenue, expenses, cash closing, refunds and management reports.

### Phase 5 — BI
Dashboard KPIs, occupancy, revenue, movie performance and operational reports.

### Phase 6 — AI
Natural-language assistant, revenue prediction, occupancy prediction, inventory forecasting and anomaly alerts.

### Phase 7 — Mobile & integrations
Android client, notifications, QR scanning and production integrations.

## Architecture rule

Do not delete or rewrite the existing booking application in one step. Introduce ERP tables alongside the current schema, migrate module-by-module, verify each module, and only then retire redundant legacy tables/code.
