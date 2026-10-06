import { CanActivate,ExecutionContext,Injectable,UnauthorizedException } from "@nestjs/common";
import { JwtService } from "@nestjs/jwt";
@Injectable()
export class AuthGuard implements CanActivate {
 constructor(private readonly jwt:JwtService){}
 async canActivate(c:ExecutionContext){const req=c.switchToHttp().getRequest();const token=req.headers.authorization?.replace(/^Bearer /,"");if(!token)throw new UnauthorizedException("Bearer token required");try{req.user=await this.jwt.verifyAsync(token,{secret:process.env.JWT_SECRET??"change-this-in-production"});return true}catch{throw new UnauthorizedException("Invalid or expired token")}}
}