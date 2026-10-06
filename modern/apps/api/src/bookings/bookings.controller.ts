import { Body, Controller, Get, Param, Post } from "@nestjs/common";
import { BookingsService } from "./bookings.service";

@Controller("bookings")
export class BookingsController {
  constructor(private readonly bookings: BookingsService) {}
  @Get() findAll() { return this.bookings.findAll(); }
  @Get(":id") findOne(@Param("id") id: string) { return this.bookings.findOne(id); }
  @Post() create(@Body() body: { showId: string; customerName: string; customerEmail?: string; seatIds: string[] }) {
    return this.bookings.create(body);
  }
}
