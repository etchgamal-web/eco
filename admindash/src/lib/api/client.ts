const API_URL = process.env.NEXT_PUBLIC_API_URL ?? "http://localhost:8000/api";

export class ApiError extends Error {
  constructor(public status: number, message: string) {
    super(message);
    this.name = "ApiError";
  }
}

export async function apiRequest<T>(path: string, options: RequestInit = {}): Promise<T> {
  const response = await fetch(`${API_URL}${path}`, {
    ...options,
    headers: { Accept: "application/json", "Content-Type": "application/json", ...options.headers },
    credentials: "include",
  });

  if (!response.ok) {
    const message = await response.text().catch(() => "Request failed");
    throw new ApiError(response.status, message || "Request failed");
  }

  return response.json() as Promise<T>;
}

export const adminApi = {
  dashboard: () => apiRequest<unknown>("/admin/dashboard"),
  products: () => apiRequest<unknown>("/admin/products"),
  orders: () => apiRequest<unknown>("/admin/orders"),
};
