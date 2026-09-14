export type OrderStatus = "Pending" | "Processing" | "Completed" | "Cancelled";

export type NavItem = {
  label: string;
  href: string;
  icon: string;
  children?: NavItem[];
};

export type Product = {
  id: string;
  name: string;
  sku: string;
  category: string;
  price: number;
  stock: number;
  status: "Active" | "Draft";
};

export type Order = {
  id: string;
  customer: string;
  date: string;
  total: number;
  status: OrderStatus;
};

export type Metric = {
  label: string;
  value: string;
  change: string;
  trend: "up" | "down";
  icon: string;
};
