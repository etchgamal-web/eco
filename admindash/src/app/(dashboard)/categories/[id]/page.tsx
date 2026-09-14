"use client";

import { useState } from "react";
import { Breadcrumbs } from "@/components/layout/dashboard-shell";

export default function CategoryDetailPage() {
  const [saved, setSaved] = useState(false);
  return <><Breadcrumbs items={["Catalog", "Categories", "Accessories"]} /><div className="mb-7"><p className="mb-2 text-xs font-bold uppercase tracking-[0.16em] text-[#7e9b61]">Catalog / Categories</p><h2 className="text-3xl font-bold tracking-tight text-[#173227]">Edit category</h2><p className="mt-2 text-sm text-[#809087]">Update the name, description, and visibility of this collection.</p></div><form onSubmit={(event) => { event.preventDefault(); setSaved(true); }} className="max-w-2xl rounded-2xl border border-[#e1e9e1] bg-white p-6"><div className="space-y-5"><label className="block"><span className="mb-2 block text-xs font-semibold text-[#52655a]">Category name</span><input defaultValue="Accessories" required className="h-11 w-full rounded-lg border border-[#dfe7df] px-3 text-sm outline-none focus:border-[#8daf60]" /></label><label className="block"><span className="mb-2 block text-xs font-semibold text-[#52655a]">Description</span><textarea rows={4} defaultValue="Everyday pieces that complete the look." className="w-full resize-none rounded-lg border border-[#dfe7df] px-3 py-3 text-sm outline-none focus:border-[#8daf60]" /></label><label className="flex items-center gap-3 text-xs font-semibold text-[#52655a]"><input type="checkbox" defaultChecked className="size-4 accent-[#608428]" /> Visible in storefront navigation</label><div className="flex justify-end"><button className="rounded-lg bg-[#173b2d] px-5 py-2.5 text-xs font-bold text-white">{saved ? "Changes saved ✓" : "Save changes"}</button></div></div></form></>;
}
