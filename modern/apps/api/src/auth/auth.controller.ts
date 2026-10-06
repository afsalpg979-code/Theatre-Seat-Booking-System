import { Body, Controller, Post } from "@nestjs/common";
import { AuthService } from "./auth.service";
@Controller("auth")
export class AuthController { constructor(private readonly auth:AuthService){} @Post("register") register(@Body() b:any){return this.auth.register(b)} @Post("login") login(@Body() b:any){return this.auth.login(b.email,b.password)} }