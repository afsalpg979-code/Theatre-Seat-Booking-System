import { BadRequestException, Injectable, NotFoundException } from "@nestjs/common";
import { PrismaService } from "../database/prisma.service";

@Injectable()
export class BookingsService {
  constructor(private readonly db: PrismaService) {}
  findAll() {
    return this.db.booking.findMany({
      include: { show: { include: { movie: true, theatre: true, screen: true } }, seats: { include: { seat: true } } },
      orderBy: { createdAt: "desc" }
    });
  }
  async findOne(id: string) {
    const booking = await this.db.booking.findUnique({
      where: { id },
      include: { show: { include: { movie: true, theatre: true, screen: true } }, seats: { include: { seat: true } } }
    });
    if (!booking) throw new NotFoundException("Booking not found");
    return booking;
  }
  async create(data: { showId: string; customerName: string; customerEmail?: string; seatIds: string[] }) {
    if (!data.seatIds?.length) throw new BadRequestException("At least one seat is required");
    return this.db.$transaction(async (tx) => {
      const show = await tx.show.findUnique({ where: { id: data.showId } });
      if (!show) throw new NotFoundException("Show not found");
      const seats = await tx.seat.findMany({ where: { id: { in: data.seatIds }, screenId: show.screenId } });
      if (seats.length !== data.seatIds.length) throw new BadRequestException("One or more seats are invalid for this screen");
      const existing = await tx.bookingSeat.findMany({
        where: {
          seatId: { in: data.seatIds },
          booking: { showId: data.showId, status: { in: ["PENDING", "CONFIRMED", "COMPLETED"] } }
        }
      });
      if (existing.length) throw new BadRequestException("One or more selected seats are already booked");
      const total = Number(show.basePrice) * seats.length;
      return tx.booking.create({
        data: {
          showId: data.showId,
          customerName: data.customerName,
          customerEmail: data.customerEmail,
          total,
          status: "CONFIRMED",
          seats: { create: seats.map((seat) => ({ seatId: seat.id })) }
        },
        include: { show: { include: { movie: true, theatre: true, screen: true } }, seats: { include: { seat: true } } }
      });
    });
  }
}
