import { adminApi, apiRequest } from "@/lib/api/client";
import type { Product } from "@/types/admin";

export const productService = {
  list: () => adminApi.products() as Promise<Product[]>,
  get: (id: string) => apiRequest<Product>(`/admin/products/${id}`),
  create: (payload: Omit<Product, "id">) => apiRequest<Product>("/admin/products", { method: "POST", body: JSON.stringify(payload) }),
  update: (id: string, payload: Partial<Product>) => apiRequest<Product>(`/admin/products/${id}`, { method: "PUT", body: JSON.stringify(payload) }),
};
