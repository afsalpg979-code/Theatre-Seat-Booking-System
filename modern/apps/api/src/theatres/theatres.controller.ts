import { Body, Controller, Get, Param, Post } from "@nestjs/common";
import { TheatresService } from "./theatres.service";

@Controller("theatres")
export class TheatresController {
  constructor(private readonly theatres: TheatresService) {}
  @Get() findAll() { return this.theatres.findAll(); }
  @Get(":id") findOne(@Param("id") id: string) { return this.theatres.findOne(id); }
  @Post() create(@Body() body: { name: string; location?: string }) { return this.theatres.create(body); }
  @Post(":id/screens") addScreen(@Param("id") theatreId: string, @Body() body: { name: string; capacity: number }) {
    return this.theatres.addScreen(theatreId, body);
  }
}
