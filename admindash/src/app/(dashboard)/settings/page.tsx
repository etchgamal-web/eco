import { Breadcrumbs } from "@/components/layout/dashboard-shell";
import { SettingsPanel } from "@/components/forms/admin-forms";
export default function SettingsPage() { return <><Breadcrumbs items={["Settings"]} /><div className="mb-7"><p className="mb-2 text-xs font-bold uppercase tracking-[0.16em] text-[#7e9b61]">Settings</p><h2 className="text-3xl font-bold tracking-tight text-[#173227]">Store settings</h2><p className="mt-2 text-sm text-[#809087]">Configure your store, payments, shipping, and notifications.</p></div><SettingsPanel /></>; }
