import { BadRequestException, Injectable, NotFoundException } from "@nestjs/common";
import { PrismaService } from "../database/prisma.service";

@Injectable()
export class ShowsService {
  constructor(private readonly db: PrismaService) {}
  findAll() {
    return this.db.show.findMany({
      include: { movie: true, theatre: true, screen: true },
      orderBy: { startsAt: "asc" }
    });
  }
  async findOne(id: string) {
    const show = await this.db.show.findUnique({
      where: { id },
      include: { movie: true, theatre: true, screen: { include: { seats: true } } }
    });
    if (!show) throw new NotFoundException("Show not found");
    return show;
  }
  async create(data: { theatreId: string; screenId: string; movieId: string; startsAt: string; basePrice: number }) {
    const [theatre, screen, movie] = await Promise.all([
      this.db.theatre.findUnique({ where: { id: data.theatreId } }),
      this.db.screen.findUnique({ where: { id: data.screenId } }),
      this.db.movie.findUnique({ where: { id: data.movieId } })
    ]);
    if (!theatre || !screen || !movie) throw new NotFoundException("Theatre, screen or movie not found");
    if (screen.theatreId !== theatre.id) throw new BadRequestException("Screen does not belong to theatre");
    return this.db.show.create({
      data: {
        theatreId: data.theatreId,
        screenId: data.screenId,
        movieId: data.movieId,
        startsAt: new Date(data.startsAt),
        basePrice: data.basePrice
      },
      include: { movie: true, theatre: true, screen: true }
    });
  }
}
