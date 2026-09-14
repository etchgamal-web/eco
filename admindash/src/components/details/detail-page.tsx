import Link from "next/link";
import { Breadcrumbs } from "@/components/layout/dashboard-shell";
import { StatusBadge } from "@/features/dashboard/components";

export function DetailPage({ eyebrow, title, subtitle, status, children }: { eyebrow: string; title: string; subtitle: string; status?: string; children: React.ReactNode }) { return <><Breadcrumbs items={[eyebrow, title]} /><div className="mb-7 flex flex-col justify-between gap-4 sm:flex-row sm:items-end"><div><p className="mb-2 text-xs font-bold uppercase tracking-[0.16em] text-[#7e9b61]">{eyebrow}</p><div className="flex flex-wrap items-center gap-3"><h2 className="text-3xl font-bold tracking-tight text-[#173227]">{title}</h2>{status && <StatusBadge status={status} />}</div><p className="mt-2 text-sm text-[#809087]">{subtitle}</p></div><div className="flex gap-2"><Link href=".." className="rounded-lg border border-[#dfe7df] bg-white px-4 py-2.5 text-xs font-bold text-[#52655a]">← Back</Link><button className="rounded-lg bg-[#173b2d] px-4 py-2.5 text-xs font-bold text-white">Edit</button></div></div>{children}</> }

export function DetailCard({ title, children }: { title: string; children: React.ReactNode }) { return <section className="rounded-2xl border border-[#e1e9e1] bg-white p-6 shadow-[0_5px_22px_rgba(24,49,37,0.035)]"><h3 className="font-bold">{title}</h3><div className="mt-5">{children}</div></section>; }
