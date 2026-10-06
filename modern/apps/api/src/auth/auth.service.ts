import { ConflictException, Injectable, UnauthorizedException } from "@nestjs/common";
import { JwtService } from "@nestjs/jwt";
import { PrismaService } from "../database/prisma.service";
import * as bcrypt from "bcryptjs";
@Injectable()
export class AuthService {
 constructor(private readonly db: PrismaService, private readonly jwt: JwtService) {}
 async register(data:{name:string;email:string;password:string}) {
  const email=data.email.trim().toLowerCase();
  if(await this.db.user.findUnique({where:{email}})) throw new ConflictException("Email already registered");
  const role=await this.db.role.upsert({where:{name:"CUSTOMER"},update:{},create:{name:"CUSTOMER"}});
  const user=await this.db.user.create({data:{name:data.name.trim(),email,passwordHash:await bcrypt.hash(data.password,12),roles:{create:{roleId:role.id}}},include:{roles:{include:{role:true}}}});
  return this.issue(user);
 }
 async login(email:string,password:string) {
  const user=await this.db.user.findUnique({where:{email:email.trim().toLowerCase()},include:{roles:{include:{role:true}}}});
  if(!user||!(await bcrypt.compare(password,user.passwordHash))||user.status!=="ACTIVE") throw new UnauthorizedException("Invalid credentials");
  return this.issue(user);
 }
 private async issue(user:any){const roles=user.roles.map((x:any)=>x.role.name);return {accessToken:await this.jwt.signAsync({sub:user.id,email:user.email,roles}),user:{id:user.id,name:user.name,email:user.email,roles}};}
}