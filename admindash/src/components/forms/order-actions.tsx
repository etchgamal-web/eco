"use client";

import { useState } from "react";
import type { OrderStatus } from "@/types/admin";

const statuses: OrderStatus[] = ["Pending", "Processing", "Completed", "Cancelled"];

export function OrderActions({ initialStatus }: { initialStatus: OrderStatus }) {
  const [status, setStatus] = useState(initialStatus);
  const [saved, setSaved] = useState(false);
  return <div className="rounded-2xl border border-[#e1e9e1] bg-white p-6"><div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"><div><h3 className="font-bold">Order status</h3><p className="mt-1 text-xs text-[#8b9991]">Update the fulfillment stage for this order.</p></div><span className="rounded-full bg-[#edf7dc] px-2.5 py-1 text-[10px] font-bold text-[#608428]">{saved ? "Saved just now" : "Synced with API"}</span></div><div className="mt-5 flex flex-col gap-3 sm:flex-row"><select value={status} onChange={(event) => { setStatus(event.target.value as OrderStatus); setSaved(false); }} className="h-11 flex-1 rounded-lg border border-[#dfe7df] bg-white px-3 text-sm outline-none focus:border-[#8daf60]">{statuses.map((item) => <option key={item}>{item}</option>)}</select><button onClick={() => setSaved(true)} className="rounded-lg bg-[#173b2d] px-5 py-2.5 text-xs font-bold text-white">{saved ? "Status saved ✓" : "Save status"}</button></div></div>;
}

export function ActivityTimeline({ items }: { items: { title: string; detail: string; time: string }[] }) { return <div className="rounded-2xl border border-[#e1e9e1] bg-white p-6"><h3 className="font-bold">Activity</h3><div className="mt-5 space-y-5">{items.map((item, index) => <div key={`${item.title}-${item.time}`} className="flex gap-3"><div className="flex flex-col items-center"><span className={`mt-1 size-2.5 rounded-full ${index === 0 ? "bg-[#d7f26b] ring-4 ring-[#edf7dc]" : "bg-[#c8d4cc]"}`} />{index !== items.length - 1 && <span className="mt-2 h-full w-px bg-[#edf1ed]" />}</div><div className="-mt-1 flex-1"><p className="text-xs font-semibold text-[#203a2d]">{item.title}</p><p className="mt-1 text-xs text-[#8b9991]">{item.detail}</p><p className="mt-1 text-[10px] text-[#b0bbb4]">{item.time}</p></div></div>)}</div></div>; }
