"use client";

import Link from "next/link";
import { FormEvent, useState } from "react";
import { authService } from "@/lib/auth/session";

export default function ForgotPasswordPage() {
  const [email, setEmail] = useState("");
  const [loading, setLoading] = useState(false);
  const [sent, setSent] = useState(false);
  const [error, setError] = useState("");

  async function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setLoading(true);
    setError("");
    try { await authService.forgotPassword(email); setSent(true); } catch { setError("We could not send the reset link. Please verify the email and try again."); } finally { setLoading(false); }
  }

  return <div className="flex min-h-screen items-center justify-center px-6"><div className="w-full max-w-sm"><Link href="/login" className="text-sm font-bold text-[#d7f26b]">← Back to sign in</Link><div className="mt-12"><p className="text-xs font-bold uppercase tracking-[0.18em] text-[#d7f26b]">Account recovery</p><h1 className="mt-3 text-3xl font-bold tracking-tight text-white">Reset your password</h1><p className="mt-3 text-sm leading-6 text-white/50">Enter the email associated with your account and we’ll send you a secure reset link.</p></div>{sent ? <div role="status" className="mt-8 rounded-xl border border-[#d7f26b]/25 bg-[#d7f26b]/10 p-5"><p className="font-semibold text-[#d7f26b]">Check your inbox</p><p className="mt-2 text-xs leading-5 text-white/60">If an account exists for {email}, a reset link is on its way.</p></div> : <form onSubmit={handleSubmit} className="mt-8 space-y-5"><label className="block"><span className="mb-2 block text-xs font-semibold text-white/75">Email address</span><input required type="email" value={email} onChange={(event) => setEmail(event.target.value)} placeholder="you@company.com" className="h-12 w-full rounded-lg border border-white/15 bg-white/5 px-4 text-sm text-white outline-none focus:border-[#d7f26b]" /></label>{error && <p role="alert" className="rounded-lg border border-[#f09b86]/30 bg-[#f09b86]/10 px-3 py-2 text-xs text-[#ffc0b0]">{error}</p>}<button disabled={loading} type="submit" className="h-12 w-full rounded-lg bg-[#d7f26b] text-sm font-bold text-[#173b2d] disabled:cursor-wait disabled:opacity-60">{loading ? "Sending…" : "Send reset link"}</button></form>}</div></div>;
}
