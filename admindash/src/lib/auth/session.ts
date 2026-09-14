import { apiRequest } from "@/lib/api/client";

type SessionUser = { id: string; name: string; email: string; role: string };

export const authService = {
  me: () => apiRequest<SessionUser>("/admin/me"),
  login: (email: string, password: string) => apiRequest<SessionUser>("/admin/login", { method: "POST", body: JSON.stringify({ email, password }) }),
  logout: () => apiRequest<void>("/admin/logout", { method: "POST" }),
  forgotPassword: (email: string) => apiRequest<void>("/admin/forgot-password", { method: "POST", body: JSON.stringify({ email }) }),
};
