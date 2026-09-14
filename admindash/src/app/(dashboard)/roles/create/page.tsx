import { Breadcrumbs } from "@/components/layout/dashboard-shell";
import { RoleEditor } from "@/components/forms/access-forms";
export default function CreateRolePage() { return <><Breadcrumbs items={["Users & Access", "Roles", "Create role"]} /><div className="mb-7"><p className="mb-2 text-xs font-bold uppercase tracking-[0.16em] text-[#7e9b61]">Users & Access / Roles</p><h2 className="text-3xl font-bold tracking-tight text-[#173227]">Create role</h2><p className="mt-2 text-sm text-[#809087]">Create a focused access profile for your team.</p></div><RoleEditor /></>; }
