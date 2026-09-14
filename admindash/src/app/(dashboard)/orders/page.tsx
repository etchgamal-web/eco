import { ResourcePage } from "@/components/tables/resource-page";
import { recentOrders } from "@/features/dashboard/data";
export default function OrdersPage() { return <ResourcePage eyebrow="Sales" title="Orders" description="Track, process, and fulfill every customer order." action="Export orders" detailBase="/orders" rows={recentOrders.map((o) => ({ id: o.id, name: o.customer, detail: o.date, value: `$${o.total.toFixed(2)}`, status: o.status }))} />; }
