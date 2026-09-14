import { Breadcrumbs } from "@/components/layout/dashboard-shell";
import { PermissionsMatrix } from "@/components/forms/access-forms";
export default function PermissionsPage() { return <><Breadcrumbs items={["Users & Access", "Permissions"]} /><div className="mb-7"><p className="mb-2 text-xs font-bold uppercase tracking-[0.16em] text-[#7e9b61]">Users & Access</p><h2 className="text-3xl font-bold tracking-tight text-[#173227]">Permissions</h2><p className="mt-2 text-sm text-[#809087]">Review and control capabilities across every team role.</p></div><PermissionsMatrix /></>; }
