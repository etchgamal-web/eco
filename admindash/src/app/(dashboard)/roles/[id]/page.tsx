import { Breadcrumbs } from "@/components/layout/dashboard-shell";
import { RoleEditor } from "@/components/forms/access-forms";
export default function EditRolePage() { return <><Breadcrumbs items={["Users & Access", "Roles", "Catalog manager"]} /><div className="mb-7"><p className="mb-2 text-xs font-bold uppercase tracking-[0.16em] text-[#7e9b61]">Users & Access / Roles</p><h2 className="text-3xl font-bold tracking-tight text-[#173227]">Edit role</h2><p className="mt-2 text-sm text-[#809087]">Update the permissions assigned to this role.</p></div><RoleEditor mode="edit" /></>; }
