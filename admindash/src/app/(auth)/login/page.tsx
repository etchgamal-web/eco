"use client";

import Link from "next/link";
import { FormEvent, useState } from "react";
import { ApiError } from "@/lib/api/client";
import { authService } from "@/lib/auth/session";

export default function LoginPage() {
  const [email, setEmail] = useState("alex@northstar.com");
  const [password, setPassword] = useState("");
  const [error, setError] = useState("");
  const [loading, setLoading] = useState(false);

  async function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setError("");
    setLoading(true);
    try {
      await authService.login(email, password);
      const redirectTo = new URLSearchParams(window.location.search).get("redirect");
      window.location.assign(redirectTo?.startsWith("/") ? redirectTo : "/dashboard");
    } catch (cause) {
      setError(cause instanceof ApiError && cause.status === 422 ? "Check your email and password and try again." : "Unable to sign in right now. Please try again.");
      setLoading(false);
    }
  }

  return <div className="grid min-h-screen lg:grid-cols-[1fr_0.8fr]"><section className="hidden flex-col justify-between bg-[#d7f26b] p-10 text-[#173b2d] lg:flex"><div><div className="flex items-center gap-3"><div className="grid size-9 place-items-center rounded-xl bg-[#173b2d] text-lg font-black text-[#d7f26b]">N</div><span className="text-sm font-bold tracking-[0.18em]">NORTHSTAR</span></div><div className="mt-32 max-w-md"><p className="text-xs font-bold uppercase tracking-[0.18em]">Commerce, clarified.</p><h1 className="mt-5 text-6xl font-bold leading-[.98] tracking-[-0.05em]">Everything your store needs, in one clear view.</h1><p className="mt-7 max-w-sm text-sm leading-6 text-[#42634e]">A calm, focused workspace for the people building what comes next.</p></div></div><p className="text-xs text-[#42634e]">© 2026 Northstar Commerce</p></section><section className="flex items-center justify-center bg-[#10271f] px-6 py-12"><div className="w-full max-w-sm"><div className="mb-12 flex items-center gap-3 lg:hidden"><div className="grid size-9 place-items-center rounded-xl bg-[#d7f26b] text-lg font-black text-[#173b2d]">N</div><span className="text-sm font-bold tracking-[0.18em] text-white">NORTHSTAR</span></div><div className="mb-9"><p className="text-xs font-bold uppercase tracking-[0.18em] text-[#d7f26b]">Welcome back</p><h2 className="mt-3 text-3xl font-bold tracking-tight text-white">Sign in to your workspace</h2><p className="mt-3 text-sm text-white/50">Enter your details to continue to Northstar Admin.</p></div><form onSubmit={handleSubmit} className="space-y-5"><label className="block"><span className="mb-2 block text-xs font-semibold text-white/75">Email address</span><input required type="email" value={email} onChange={(event) => setEmail(event.target.value)} className="h-12 w-full rounded-lg border border-white/15 bg-white/5 px-4 text-sm text-white outline-none placeholder:text-white/30 focus:border-[#d7f26b]" /></label><label className="block"><div className="mb-2 flex justify-between"><span className="text-xs font-semibold text-white/75">Password</span><Link href="/forgot-password" className="text-xs font-semibold text-[#d7f26b]">Forgot password?</Link></div><input required minLength={6} type="password" value={password} onChange={(event) => setPassword(event.target.value)} className="h-12 w-full rounded-lg border border-white/15 bg-white/5 px-4 text-sm text-white outline-none focus:border-[#d7f26b]" /></label>{error && <p role="alert" className="rounded-lg border border-[#f09b86]/30 bg-[#f09b86]/10 px-3 py-2 text-xs text-[#ffc0b0]">{error}</p>}<button disabled={loading} type="submit" className="flex h-12 w-full items-center justify-center rounded-lg bg-[#d7f26b] text-sm font-bold text-[#173b2d] transition hover:bg-[#e4fa91] disabled:cursor-wait disabled:opacity-60">{loading ? "Signing in…" : "Sign in →"}</button></form><div className="my-6 flex items-center gap-3 text-[10px] uppercase tracking-[0.16em] text-white/25"><span className="h-px flex-1 bg-white/10" />or<span className="h-px flex-1 bg-white/10" /></div><Link href="/dashboard?demo=1" className="flex h-11 w-full items-center justify-center rounded-lg border border-[#d7f26b]/40 text-xs font-bold text-[#d7f26b] transition hover:bg-[#d7f26b]/10">Preview demo workspace</Link><p className="mt-3 text-center text-[10px] text-white/35">Demo mode uses sample data only and does not require an account.</p><p className="mt-8 text-center text-xs text-white/35">Protected by Northstar access control</p></div></section></div>;
}
