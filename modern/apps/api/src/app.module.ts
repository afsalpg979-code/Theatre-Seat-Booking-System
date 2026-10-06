import { Controller, Get, Module } from "@nestjs/common";
import { DatabaseModule } from "./database/database.module";
import { MoviesModule } from "./movies/movies.module";
import { TheatresModule } from "./theatres/theatres.module";

@Controller()
class AppController {
  @Get("health")
  health() {
    return {
      ok: true,
      service: "FALCONS Smart Theatre ERP API",
      version: "0.2.0",
      stack: "NestJS + TypeScript",
      modules: ["movies", "theatres", "screens"]
    };
  }
}

@Module({
  imports: [DatabaseModule, MoviesModule, TheatresModule],
  controllers: [AppController]
})
export class AppModule {}
