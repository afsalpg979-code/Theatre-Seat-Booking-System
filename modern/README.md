# FALCONS Smart Theatre ERP — Node.js Migration

The modern application is the target replacement for the legacy PHP theatre system.

## Stack
- Web: Next.js + React + TypeScript
- API: Node.js + NestJS + TypeScript
- Database: PostgreSQL + Prisma
- Cache/jobs: Redis (foundation included)
- AI: Python/FastAPI in a later phase
- Mobile: React Native/Expo in a later phase

## Current Node.js modules
- Movies
- Theatres
- Screens
- Seats
- Shows
- Bookings

Booking flow: Movie -> Theatre -> Screen -> Seats -> Show -> Booking

Bookings use a database transaction and reject seats already booked for the same show.

## Run locally

    cd modern
    npm install

Create .env from .env.example, then start PostgreSQL/Redis:

    docker compose up -d

Generate Prisma Client:

    npm run db:generate

Create/update the PostgreSQL schema:

    npm --workspace packages/db run migrate -- --name init

Start the API:

    npm run dev:api

Start the web app in another terminal:

    npm run dev:web

API: http://localhost:4000/api/health
Web: http://localhost:3000

## ERP phases implemented

### Phase 4 — POS, Inventory & Procurement
- POS products and sales
- Stock deduction on POS sale
- Inventory adjustments and transaction ledger
- Low-stock endpoint
- Suppliers and purchase orders
- Purchase receiving updates stock automatically

### Phase 5 — HR & Assets
- Departments, employees and designations
- Attendance and shifts
- Theatre assets
- Maintenance requests and completion tracking

### Phase 6 — Finance, Admin, Reports & Razorpay
- Finance income/expense/refund ledger
- Management dashboard aggregates
- Sales and inventory reports
- Admin overview and audit-log feed
- Razorpay order creation and payment-signature verification

## API endpoints

- GET /api/health
- GET /api/movies
- POST /api/movies
- GET /api/movies/:id
- GET /api/theatres
- POST /api/theatres
- POST /api/theatres/:id/screens
- POST /api/theatres/:id/screens/:screenId/seats
- GET /api/shows
- POST /api/shows
- GET /api/bookings
- POST /api/bookings

## Migration policy

Legacy PHP is intentionally retained as a fallback/reference while Node.js modules are migrated and verified. PHP will be removed only after the replacement modules are tested end-to-end.


## Phase 4–6 endpoints

- GET/POST /api/pos/products
- GET/POST /api/pos/sales
- GET /api/inventory/low-stock
- GET /api/inventory/transactions
- POST /api/inventory/adjust
- GET/POST /api/procurement/suppliers
- GET/POST /api/procurement/orders
- POST /api/procurement/orders/:id/receive
- GET/POST /api/hr/departments
- GET/POST /api/hr/employees
- GET/POST /api/hr/attendance
- POST /api/hr/shifts
- GET/POST /api/assets
- GET/POST /api/assets/maintenance
- POST /api/assets/maintenance/complete
- GET/POST /api/finance/entries
- GET /api/finance/summary
- GET /api/reports/dashboard
- GET /api/reports/sales
- GET /api/reports/inventory
- GET /api/admin/overview
- POST /api/payments/razorpay/order
- POST /api/payments/razorpay/verify

Razorpay uses RAZORPAY_KEY_ID and RAZORPAY_KEY_SECRET. Live transactions require production credentials, webhook handling and HTTPS before release.


## Phase 9 — AI ERP foundation

The AI layer is intentionally built after the operational ERP modules. It currently provides:
- ERP-wide insight endpoint
- Inventory alert reasoning
- Revenue and booking summaries
- Natural-language ERP query endpoint
- AI query history
- Prediction storage model
- Baseline inventory prediction service

Endpoints:
- GET /api/ai/insights
- GET /api/ai/predictions
- POST /api/ai/query

The current query engine is a deterministic ERP-aware baseline. A production LLM provider, historical forecasting model and scheduled prediction jobs can be connected later without changing the ERP data model.
