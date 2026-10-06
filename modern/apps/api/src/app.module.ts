import { Controller, Get, Module } from "@nestjs/common";
import { DatabaseModule } from "./database/database.module";
import { MoviesModule } from "./movies/movies.module";
import { TheatresModule } from "./theatres/theatres.module";
import { ShowsModule } from "./shows/shows.module";
import { BookingsModule } from "./bookings/bookings.module";
import { AuthModule } from "./auth/auth.module";
import { MeController } from "./auth/me.controller";
import { PaymentsModule } from "./payments/payments.module";
import { TicketsModule } from "./tickets/tickets.module";

@Controller()
class AppController {
  @Get("health")
  health() {
    return {
      ok: true,
      service: "FALCONS Smart Theatre ERP API",
      version: "0.5.0",
      stack: "NestJS + TypeScript + Prisma",
      modules: ["auth", "rbac", "movies", "theatres", "screens", "seats", "shows", "bookings", "seat-locking", "payments", "tickets"]
    };
  }
}

@Module({
  imports: [DatabaseModule, MoviesModule, TheatresModule, ShowsModule, BookingsModule, AuthModule, PaymentsModule, TicketsModule],
  controllers: [AppController, MeController]
})
export class AppModule {}
