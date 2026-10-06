import { Body, Controller, Get, Param, Post } from "@nestjs/common";
import { MoviesService } from "./movies.service";

@Controller("movies")
export class MoviesController {
  constructor(private readonly movies: MoviesService) {}
  @Get() findAll() { return this.movies.findAll(); }
  @Get(":id") findOne(@Param("id") id: string) { return this.movies.findOne(id); }
  @Post() create(@Body() body: { title: string; durationMin: number; description?: string; language?: string; releaseDate?: string }) {
    return this.movies.create(body);
  }
}
