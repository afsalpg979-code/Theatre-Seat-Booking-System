import { Injectable, NotFoundException } from "@nestjs/common";
import { PrismaService } from "../database/prisma.service";

@Injectable()
export class MoviesService {
  constructor(private readonly db: PrismaService) {}
  findAll() { return this.db.movie.findMany({ orderBy: { createdAt: "desc" } }); }
  async findOne(id: string) {
    const movie = await this.db.movie.findUnique({ where: { id }, include: { shows: true } });
    if (!movie) throw new NotFoundException("Movie not found");
    return movie;
  }
  create(data: { title: string; durationMin: number; description?: string; language?: string; releaseDate?: string }) {
    return this.db.movie.create({
      data: {
        title: data.title,
        durationMin: Number(data.durationMin),
        description: data.description,
        language: data.language,
        releaseDate: data.releaseDate ? new Date(data.releaseDate) : undefined
      }
    });
  }
}
