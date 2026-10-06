import { Controller, Get, Module } from "@nestjs/common";
import { DatabaseModule } from "./database/database.module";
import { MoviesModule } from "./movies/movies.module";
import { TheatresModule } from "./theatres/theatres.module";
import { ShowsModule } from "./shows/shows.module";
import { BookingsModule } from "./bookings/bookings.module";

@Controller()
class AppController {
  @Get("health")
  health() {
    return {
      ok: true,
      service: "FALCONS Smart Theatre ERP API",
      version: "0.3.0",
      stack: "NestJS + TypeScript + Prisma",
      modules: ["movies", "theatres", "screens", "shows", "bookings"]
    };
  }
}

@Module({
  imports: [DatabaseModule, MoviesModule, TheatresModule, ShowsModule, BookingsModule],
  controllers: [AppController]
})
export class AppModule {}
