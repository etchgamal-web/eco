import { Breadcrumbs } from "@/components/layout/dashboard-shell";
import { ProductForm } from "@/components/forms/product-form";
export default function CreateProductPage() { return <><Breadcrumbs items={["Catalog", "Products", "Create product"]} /><div className="mb-7"><p className="mb-2 text-xs font-bold uppercase tracking-[0.16em] text-[#7e9b61]">Catalog / Products</p><h2 className="text-3xl font-bold tracking-tight text-[#173227]">Create product</h2><p className="mt-2 text-sm text-[#809087]">Add a new product to your store catalog.</p></div><ProductForm /></>; }
