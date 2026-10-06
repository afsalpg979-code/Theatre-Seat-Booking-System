import { Injectable, NotFoundException, BadRequestException } from "@nestjs/common";
import { PrismaService } from "../database/prisma.service";

@Injectable()
export class TheatresService {
  constructor(private readonly db: PrismaService) {}
  findAll() { return this.db.theatre.findMany({ include: { screens: { include: { seats: true } } }, orderBy: { name: "asc" } }); }
  async findOne(id: string) {
    const theatre = await this.db.theatre.findUnique({ where: { id }, include: { screens: { include: { seats: true } } } });
    if (!theatre) throw new NotFoundException("Theatre not found");
    return theatre;
  }
  create(data: { name: string; location?: string }) { return this.db.theatre.create({ data }); }
  async addScreen(theatreId: string, data: { name: string; capacity: number }) {
    if (!(await this.db.theatre.findUnique({ where: { id: theatreId } }))) throw new NotFoundException("Theatre not found");
    return this.db.screen.create({ data: { theatreId, name: data.name, capacity: Number(data.capacity) } });
  }
  async addSeats(theatreId: string, screenId: string, rows: Array<{ row: string; count: number; category?: string }>) {
    const screen = await this.db.screen.findFirst({ where: { id: screenId, theatreId } });
    if (!screen) throw new NotFoundException("Screen not found");
    if (!rows?.length) throw new BadRequestException("At least one seat row is required");
    const seats = rows.flatMap((r) =>
      Array.from({ length: Number(r.count) }, (_, i) => ({
        screenId,
        row: r.row.trim().toUpperCase(),
        number: i + 1,
        category: r.category?.trim().toUpperCase() || "STANDARD"
      }))
    );
    return this.db.$transaction(seats.map((seat) => this.db.seat.create({ data: seat })));
  }
}
