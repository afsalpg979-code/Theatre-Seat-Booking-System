import { Body, Controller, Get, Param, Post } from "@nestjs/common";
import { ShowsService } from "./shows.service";

@Controller("shows")
export class ShowsController {
  constructor(private readonly shows: ShowsService) {}
  @Get() findAll() { return this.shows.findAll(); }
  @Get(":id") findOne(@Param("id") id: string) { return this.shows.findOne(id); }
  @Post() create(@Body() body: { theatreId: string; screenId: string; movieId: string; startsAt: string; basePrice: number }) {
    return this.shows.create(body);
  }
}
