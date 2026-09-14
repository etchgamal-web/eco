import { ResourcePage } from "@/components/tables/resource-page";
import { products } from "@/features/dashboard/data";
export default function ProductsPage() { return <ResourcePage eyebrow="Catalog" title="Products" description="Manage your catalog, variants, and product visibility." action="Add product" createHref="/products/create" detailBase="/products" rows={products.map((p) => ({ id: p.id, name: p.name, detail: p.sku, value: `$${p.price.toFixed(2)} · ${p.stock} in stock`, status: p.status }))} />; }
