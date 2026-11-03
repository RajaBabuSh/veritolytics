import React from 'react';
import { Link } from 'react-router-dom';
import { Logo } from '@/components/brand/Logo';
import { ShieldCheck, Zap, Sparkles, ArrowLeft, Lock, Award, CheckCircle } from 'lucide-react';

interface AuthLayoutProps {
  children: React.ReactNode;
  title: string;
  subtitle: string;
}

export const AuthLayout: React.FC<AuthLayoutProps> = ({ children, title, subtitle }) => {
  return (
    <div className="flex min-h-screen w-full bg-white antialiased">
      {/* LEFT COLUMN: Modern SaaS Visual & Brand Showcase (Desktop / Tablet-wide) */}
      <div className="relative hidden lg:flex lg:w-1/2 flex-col justify-between overflow-hidden bg-brand-navy-950 p-12 text-white">
        {/* Glow ambient background effects */}
        <div className="pointer-events-none absolute -left-20 -top-20 h-96 w-96 rounded-full bg-brand-royal-600/25 blur-[120px]" />
        <div className="pointer-events-none absolute bottom-10 right-10 h-96 w-96 rounded-full bg-brand-violet-600/25 blur-[120px]" />

        {/* Top Header / Brand Logo */}
        <div className="relative z-10">
          <Logo variant="light" size="lg" to="/" />
        </div>

        {/* Center Marketing Graphic & SaaS Value Proposition */}
        <div className="relative z-10 max-w-lg space-y-8 my-auto py-10">
          {/* Eyebrow badge */}
          <div className="inline-flex items-center gap-2 rounded-full border border-brand-royal-400/30 bg-brand-navy-900/90 px-4 py-1.5 text-xs font-semibold text-brand-royal-300 backdrop-blur-md">
            <Sparkles className="h-3.5 w-3.5 text-brand-violet-400" />
            <span>Next-Gen Consumer Intelligence Exchange</span>
          </div>

          <h2 className="text-3xl xl:text-4xl font-extrabold tracking-tight leading-tight">
            Turn verified insights into{' '}
            <span className="text-gradient-light">instant digital payouts.</span>
          </h2>

          <p className="text-sm text-slate-300 leading-relaxed">
            VERITOLYTICS provides a transparent, privacy-centric research exchange connecting verified consumer opinions with academic and institutional research cohorts.
          </p>

          {/* Genuine Feature Highlights */}
          <div className="grid grid-cols-1 gap-3.5 pt-2">
            <div className="flex items-center gap-3 rounded-xl border border-white/10 bg-white/5 p-3.5 backdrop-blur-md">
              <div className="flex h-9 w-9 items-center justify-center rounded-lg bg-brand-royal-600 text-white shrink-0 shadow-glow-royal">
                <Zap className="h-4 w-4" />
              </div>
              <div>
                <h4 className="text-xs font-bold text-white">Transparent Exchange Rate</h4>
                <p className="text-[11px] text-slate-400">Fixed rate: 100 Points = $1.00 USD across all payout channels.</p>
              </div>
            </div>

            <div className="flex items-center gap-3 rounded-xl border border-white/10 bg-white/5 p-3.5 backdrop-blur-md">
              <div className="flex h-9 w-9 items-center justify-center rounded-lg bg-brand-violet-600 text-white shrink-0 shadow-glow-violet">
                <ShieldCheck className="h-4 w-4" />
              </div>
              <div>
                <h4 className="text-xs font-bold text-white">Data Privacy First</h4>
                <p className="text-[11px] text-slate-400">Your personal data is encrypted and never sold to third-party brokers.</p>
              </div>
            </div>

            <div className="flex items-center gap-3 rounded-xl border border-white/10 bg-white/5 p-3.5 backdrop-blur-md">
              <div className="flex h-9 w-9 items-center justify-center rounded-lg bg-brand-navy-800 text-brand-royal-400 shrink-0">
                <Award className="h-4 w-4" />
              </div>
              <div>
                <h4 className="text-xs font-bold text-white">Flexible Payout Methods</h4>
                <p className="text-[11px] text-slate-400">Redeem starting at $10.00 via PayPal, Amazon, Apple, and Visa cards.</p>
              </div>
            </div>
          </div>
        </div>

        {/* Bottom Security Footer */}
        <div className="relative z-10 flex items-center justify-between border-t border-brand-navy-800/80 pt-6 text-xs text-slate-400">
          <div className="flex items-center gap-1.5">
            <Lock className="h-3.5 w-3.5 text-brand-violet-400" />
            <span>256-Bit SSL Encryption</span>
          </div>
          <span>GDPR & CCPA Compliant</span>
        </div>
      </div>

      {/* RIGHT COLUMN: The Auth Form Container (Responsive on all screens) */}
      <div className="flex flex-1 flex-col justify-between p-6 sm:p-10 lg:p-14 overflow-y-auto bg-slate-50/40">
        {/* Top bar with Back to Home & Mobile Logo */}
        <div className="flex items-center justify-between w-full max-w-md mx-auto">
          <Link
            to="/"
            className="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-500 hover:text-brand-royal-600 transition-colors py-1 px-2.5 rounded-lg hover:bg-slate-100/80"
          >
            <ArrowLeft className="h-3.5 w-3.5" />
            <span>Back to Home</span>
          </Link>

          {/* Mobile Logo: visible on small screens */}
          <div className="lg:hidden">
            <Logo variant="dark" size="sm" to="/" />
          </div>
        </div>

        {/* Main Form Center */}
        <div className="w-full max-w-md mx-auto my-auto py-8">
          <div className="mb-6 space-y-1.5">
            <h1 className="text-2xl sm:text-3xl font-extrabold tracking-tight text-slate-900">
              {title}
            </h1>
            <p className="text-xs sm:text-sm text-slate-500 leading-relaxed">
              {subtitle}
            </p>
          </div>

          {children}
        </div>

        {/* Bottom Micro Footer */}
        <div className="w-full max-w-md mx-auto text-center text-xs text-slate-400 pt-6">
          <p>© {new Date().getFullYear()} VERITOLYTICS Inc. All rights reserved.</p>
        </div>
      </div>
    </div>
  );
};
