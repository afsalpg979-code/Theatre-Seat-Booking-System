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
