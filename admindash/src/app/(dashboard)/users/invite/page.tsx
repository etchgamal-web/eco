import { Breadcrumbs } from "@/components/layout/dashboard-shell";
import { InviteUserForm } from "@/components/forms/admin-forms";
export default function InviteUserPage() { return <><Breadcrumbs items={["Users & Access", "Users", "Invite user"]} /><div className="mb-7"><p className="mb-2 text-xs font-bold uppercase tracking-[0.16em] text-[#7e9b61]">Users & Access</p><h2 className="text-3xl font-bold tracking-tight text-[#173227]">Invite user</h2><p className="mt-2 text-sm text-[#809087]">Give a teammate the access they need to help run your store.</p></div><InviteUserForm /></>; }
