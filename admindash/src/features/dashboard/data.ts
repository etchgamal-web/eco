import type { Metric, Order, Product } from "@/types/admin";

export const metrics: Metric[] = [
  { label: "Total revenue", value: "$128,430", change: "+12.8%", trend: "up", icon: "revenue" },
  { label: "Orders", value: "1,284", change: "+8.4%", trend: "up", icon: "orders" },
  { label: "Customers", value: "8,549", change: "+4.6%", trend: "up", icon: "customers" },
  { label: "Products", value: "432", change: "−1.2%", trend: "down", icon: "products" },
];

export const recentOrders: Order[] = [
  { id: "#10482", customer: "Olivia Martin", date: "Sep 14, 2026", total: 248.0, status: "Completed" },
  { id: "#10481", customer: "James Wilson", date: "Sep 14, 2026", total: 89.5, status: "Processing" },
  { id: "#10480", customer: "Sophia Brown", date: "Sep 13, 2026", total: 412.2, status: "Pending" },
  { id: "#10479", customer: "Ethan Davis", date: "Sep 13, 2026", total: 156.0, status: "Completed" },
  { id: "#10478", customer: "Ava Taylor", date: "Sep 12, 2026", total: 64.9, status: "Cancelled" },
];

export const products: Product[] = [
  { id: "1", name: "Minimal leather wallet", sku: "WL-2048", category: "Accessories", price: 48, stock: 124, status: "Active" },
  { id: "2", name: "Classic linen shirt", sku: "SH-1042", category: "Apparel", price: 86, stock: 42, status: "Active" },
  { id: "3", name: "Everyday canvas tote", sku: "BG-3321", category: "Bags", price: 32, stock: 8, status: "Active" },
  { id: "4", name: "Studio ceramic mug", sku: "HM-8830", category: "Home", price: 24, stock: 0, status: "Draft" },
];

export const salesData = [
  { month: "Apr", value: 42 }, { month: "May", value: 58 }, { month: "Jun", value: 48 },
  { month: "Jul", value: 72 }, { month: "Aug", value: 66 }, { month: "Sep", value: 84 },
];
