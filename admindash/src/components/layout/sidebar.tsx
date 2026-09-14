"use client";

import Link from "next/link";
import { usePathname } from "next/navigation";
import { useState } from "react";
import type { NavItem } from "@/types/admin";

const nav: NavItem[] = [
  { label: "Overview", href: "/dashboard", icon: "⌂" },
  { label: "Catalog", href: "/products", icon: "▦", children: [{ label: "Products", href: "/products", icon: "•" }, { label: "Categories", href: "/categories", icon: "•" }] },
  { label: "Sales", href: "/orders", icon: "◒", children: [{ label: "Orders", href: "/orders", icon: "•" }, { label: "Coupons", href: "/coupons", icon: "•" }] },
  { label: "Customers", href: "/customers", icon: "♧" },
  { label: "Inventory", href: "/inventory", icon: "▤" },
  { label: "Users & Access", href: "/users", icon: "♙", children: [{ label: "Users", href: "/users", icon: "•" }, { label: "Roles", href: "/roles", icon: "•" }, { label: "Permissions", href: "/permissions", icon: "•" }] },
  { label: "Settings", href: "/settings", icon: "⚙" },
];

export function Sidebar({ open, onClose }: { open: boolean; onClose: () => void }) {
  const pathname = usePathname();
  const [expanded, setExpanded] = useState<string[]>(["Catalog", "Sales", "Users & Access"]);
  const isActive = (href: string) => pathname === href || (href !== "/dashboard" && pathname.startsWith(href));
  const toggle = (label: string) => setExpanded((items) => items.includes(label) ? items.filter((item) => item !== label) : [...items, label]);

  return <>
    <button aria-label="Close navigation" onClick={onClose} className={`fixed inset-0 z-30 bg-slate-950/30 backdrop-blur-sm transition-opacity lg:hidden ${open ? "opacity-100" : "pointer-events-none opacity-0"}`} />
    <aside className={`fixed inset-y-0 left-0 z-40 flex w-[264px] flex-col bg-[#10271f] px-4 text-white transition-transform duration-200 lg:static lg:translate-x-0 ${open ? "translate-x-0" : "-translate-x-full"}`}>
      <div className="flex h-[82px] items-center gap-3 border-b border-white/10 px-3">
        <div className="grid size-9 place-items-center rounded-xl bg-[#d7f26b] text-lg font-black text-[#10271f]">N</div>
        <div><p className="text-[15px] font-semibold tracking-wide">NORTHSTAR</p><p className="text-[10px] uppercase tracking-[0.2em] text-white/45">Commerce admin</p></div>
      </div>
      <nav className="flex-1 space-y-5 overflow-y-auto py-7">
        <p className="px-3 text-[10px] font-bold uppercase tracking-[0.18em] text-white/35">Workspace</p>
        <div className="space-y-1">
          {nav.map((item) => <div key={item.label}>
            <div className={`flex items-center gap-2 rounded-lg ${isActive(item.href) ? "bg-white/10" : ""}`}>
              <Link href={item.href} onClick={onClose} className={`flex min-w-0 flex-1 items-center gap-3 px-3 py-2.5 text-[13px] ${isActive(item.href) ? "font-semibold text-[#d7f26b]" : "text-white/65 hover:text-white"}`}><span className="grid w-4 place-items-center text-base">{item.icon}</span>{item.label}</Link>
              {item.children && <button onClick={() => toggle(item.label)} className="mr-2 px-2 py-2 text-white/40" aria-label={`Toggle ${item.label}`}>{expanded.includes(item.label) ? "⌄" : "›"}</button>}
            </div>
            {item.children && expanded.includes(item.label) && <div className="ml-7 mt-1 space-y-1 border-l border-white/10 pl-3">{item.children.map((child) => <Link key={child.label} href={child.href} onClick={onClose} className={`block rounded-md px-3 py-2 text-xs ${isActive(child.href) && pathname === child.href ? "bg-[#d7f26b]/10 font-semibold text-[#d7f26b]" : "text-white/45 hover:text-white"}`}>{child.label}</Link>)}</div>}
          </div>)}
        </div>
      </nav>
      <div className="border-t border-white/10 py-4"><div className="flex items-center gap-3 rounded-lg px-2 py-2"><div className="grid size-8 place-items-center rounded-full bg-[#e6b99b] text-xs font-bold text-[#3a241a]">AM</div><div className="min-w-0"><p className="truncate text-xs font-semibold">Alex Morgan</p><p className="text-[10px] text-white/40">Administrator</p></div><span className="ml-auto text-white/40">···</span></div></div>
    </aside>
  </>;
}
