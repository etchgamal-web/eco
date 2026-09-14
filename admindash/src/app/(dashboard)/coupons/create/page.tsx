import { Breadcrumbs } from "@/components/layout/dashboard-shell";
import { CouponForm } from "@/components/forms/admin-forms";
export default function CreateCouponPage() { return <><Breadcrumbs items={["Sales", "Coupons", "Create coupon"]} /><div className="mb-7"><p className="mb-2 text-xs font-bold uppercase tracking-[0.16em] text-[#7e9b61]">Sales / Coupons</p><h2 className="text-3xl font-bold tracking-tight text-[#173227]">Create coupon</h2><p className="mt-2 text-sm text-[#809087]">Set up a promotion with clear rules and an expiration date.</p></div><CouponForm /></>; }
