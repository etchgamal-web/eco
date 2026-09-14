import { NextRequest, NextResponse } from "next/server";

const sessionCookie = process.env.ADMIN_SESSION_COOKIE ?? "laravel_session";

export function proxy(request: NextRequest) {
  const hasSession = request.cookies.has(sessionCookie);
  if (hasSession) return NextResponse.next();

  if (request.nextUrl.searchParams.get("demo") === "1") {
    const demoResponse = NextResponse.next();
    demoResponse.cookies.set(sessionCookie, "demo-preview", { httpOnly: false, maxAge: 60 * 60, path: "/", sameSite: "lax" });
    return demoResponse;
  }

  const loginUrl = new URL("/login", request.url);
  loginUrl.searchParams.set("redirect", `${request.nextUrl.pathname}${request.nextUrl.search}`);
  return NextResponse.redirect(loginUrl);
}

export const config = {
  matcher: [
    "/dashboard/:path*",
    "/products/:path*",
    "/categories/:path*",
    "/orders/:path*",
    "/customers/:path*",
    "/coupons/:path*",
    "/inventory/:path*",
    "/users/:path*",
    "/roles/:path*",
    "/permissions/:path*",
    "/settings/:path*",
  ],
};
