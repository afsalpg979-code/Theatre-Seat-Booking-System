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
import { PosModule } from "./pos/pos.module";
import { InventoryModule } from "./inventory/inventory.module";
import { ProcurementModule } from "./procurement/procurement.module";
import { HrModule } from "./hr/hr.module";
import { AssetsModule } from "./assets/assets.module";
import { FinanceModule } from "./finance/finance.module";
import { ReportsModule } from "./reports/reports.module";
import { AdminModule } from "./admin/admin.module";
import { AiModule } from "./ai/ai.module";

@Controller()
class AppController {
  @Get("health")
  health() {
    return {
      ok: true,
      service: "FALCONS Smart Theatre ERP API",
      version: "0.5.0",
      stack: "NestJS + TypeScript + Prisma",
      modules: ["auth", "rbac", "movies", "theatres", "screens", "seats", "shows", "bookings", "seat-locking", "payments", "tickets", "pos", "inventory", "procurement", "hr", "assets", "finance", "reports", "admin", "ai"]
    };
  }
}

@Module({
  imports: [DatabaseModule, MoviesModule, TheatresModule, ShowsModule, BookingsModule, AuthModule, PaymentsModule, TicketsModule, PosModule, InventoryModule, ProcurementModule, HrModule, AssetsModule, FinanceModule, ReportsModule, AdminModule, AiModule],
  controllers: [AppController, MeController]
})
export class AppModule {}
