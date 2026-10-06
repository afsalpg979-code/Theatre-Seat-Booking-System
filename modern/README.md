# FALCONS Smart Theatre ERP — Modern Stack

Modern migration foundation for the existing Theatre Seat Booking System.

## Stack
- Next.js + React + TypeScript
- NestJS + Node.js + TypeScript
- Prisma ORM
- PostgreSQL
- Redis/BullMQ and Python AI will be added in later phases

## Apps
- `apps/web` — customer/admin web application
- `apps/api` — ERP REST API
- `packages/db` — shared Prisma database schema

## Start
```bash
npm install
npm run dev:web
npm run dev:api
```

The legacy PHP application remains in the repository while the modern application is migrated module-by-module.
