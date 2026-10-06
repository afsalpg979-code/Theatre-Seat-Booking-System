import { Controller, Get, Module } from "@nestjs/common";

@Controller()
class AppController {
  @Get("health")
  health() {
    return {
      ok: true,
      service: "FALCONS Smart Theatre ERP API",
      version: "0.1.0",
      stack: "NestJS + TypeScript"
    };
  }
}

@Module({
  controllers: [AppController]
})
export class AppModule {}
