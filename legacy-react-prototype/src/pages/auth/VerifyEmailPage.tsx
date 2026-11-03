import React, { useState, useEffect } from 'react';
import { Link, useSearchParams } from 'react-router-dom';
import { AuthLayout } from '@/components/auth/AuthLayout';
import { Button } from '@/components/ui/Button';
import { ConfigNotice } from '@/components/auth/ConfigNotice';
import { useAuth } from '@/context/AuthContext';
import { Mail, RefreshCw, CheckCircle2, AlertCircle, ArrowLeft, Inbox, Clock } from 'lucide-react';

export const VerifyEmailPage: React.FC = () => {
  const [searchParams] = useSearchParams();
  const { resendVerificationEmail } = useAuth();

  const emailParam = searchParams.get('email') || '';
  const [email, setEmail] = useState(emailParam);
  const [statusMessage, setStatusMessage] = useState<{ type: 'success' | 'error'; text: string } | null>(null);
  const [isResending, setIsResending] = useState(false);
  const [countdown, setCountdown] = useState(0);

  useEffect(() => {
    let timer: NodeJS.Timeout;
    if (countdown > 0) {
      timer = setTimeout(() => setCountdown(countdown - 1), 1000);
    }
    return () => clearTimeout(timer);
  }, [countdown]);

  const handleResend = async () => {
    if (!email) {
      setStatusMessage({ type: 'error', text: 'No email address provided to resend.' });
      return;
    }

    setIsResending(true);
    setStatusMessage(null);

    const { error } = await resendVerificationEmail(email);

    setIsResending(false);

    if (error) {
      setStatusMessage({ type: 'error', text: error });
    } else {
      setStatusMessage({
        type: 'success',
        text: 'A fresh verification link has been sent to your inbox.',
      });
      setCountdown(60); // 60s cooldown to prevent email spam
    }
  };

  return (
    <AuthLayout
      title="Verify Your Email"
      subtitle="We've dispatched a secure confirmation link to verify your panelist identity."
    >
      <ConfigNotice />

      <div className="space-y-6">
        {/* Email Visual Emblem */}
        <div className="rounded-2xl border border-brand-royal-100 bg-gradient-to-br from-brand-royal-50/70 via-white to-brand-violet-50/50 p-6 text-center space-y-3">
          <div className="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-gradient-to-tr from-brand-royal-600 to-brand-violet-600 text-white shadow-glow-royal">
            <Mail className="h-7 w-7" />
          </div>

          <div className="space-y-1">
            <h3 className="text-base font-bold text-slate-900">
              Check Your Inbox
            </h3>
            <p className="text-xs text-slate-500 leading-relaxed max-w-sm mx-auto">
              Please click the verification button sent to{' '}
              {email ? (
                <strong className="text-slate-900 font-semibold">{email}</strong>
              ) : (
                'your email address'
              )}{' '}
              to activate your account and claim your 500 Welcome Points.
            </p>
          </div>
        </div>

        {/* Status Feedback Alerts */}
        {statusMessage && (
          <div
            className={`flex items-start gap-3 rounded-2xl border p-4 text-xs shadow-sm animate-fadeIn ${
              statusMessage.type === 'success'
                ? 'border-emerald-200 bg-emerald-50 text-emerald-800'
                : 'border-rose-200 bg-rose-50 text-rose-800'
            }`}
          >
            {statusMessage.type === 'success' ? (
              <CheckCircle2 className="h-4 w-4 shrink-0 text-emerald-600 mt-0.5" />
            ) : (
              <AlertCircle className="h-4 w-4 shrink-0 text-rose-600 mt-0.5" />
            )}
            <p className="leading-relaxed font-medium">{statusMessage.text}</p>
          </div>
        )}

        {/* Helpful Tips Card */}
        <div className="rounded-xl border border-slate-200/80 bg-white p-4 space-y-2 text-xs text-slate-600">
          <h4 className="font-bold text-slate-800 flex items-center gap-1.5">
            <Inbox className="h-4 w-4 text-brand-royal-600" />
            <span>Didn&apos;t receive the email?</span>
          </h4>
          <ul className="list-disc list-inside space-y-1 text-[11px] text-slate-500 pl-1">
            <li>Check your spam, junk, or promotions folders.</li>
            <li>Verification links remain valid for 24 hours.</li>
            <li>Ensure no firewall or enterprise email filter blocked the message.</li>
          </ul>
        </div>

        {/* Resend Action */}
        <div className="space-y-3 pt-1">
          <Button
            type="button"
            variant="outline"
            className="w-full justify-center gap-2 text-xs font-bold"
            onClick={handleResend}
            disabled={isResending || countdown > 0}
          >
            {isResending ? (
              <>
                <RefreshCw className="h-3.5 w-3.5 animate-spin" />
                <span>Sending new link...</span>
              </>
            ) : countdown > 0 ? (
              <>
                <Clock className="h-3.5 w-3.5 text-slate-400" />
                <span>Resend available in {countdown}s</span>
              </>
            ) : (
              <>
                <RefreshCw className="h-3.5 w-3.5 text-brand-royal-600" />
                <span>Resend Verification Email</span>
              </>
            )}
          </Button>

          <Link to="/login" className="block w-full">
            <Button variant="default" className="w-full justify-center text-xs font-bold">
              Proceed to Sign In
            </Button>
          </Link>
        </div>

        {/* Back Link */}
        <div className="text-center pt-2">
          <Link
            to="/"
            className="inline-flex items-center gap-1 text-xs font-semibold text-slate-500 hover:text-slate-800 transition-colors"
          >
            <ArrowLeft className="h-3.5 w-3.5" />
            <span>Back to Veritolytics Home</span>
          </Link>
        </div>
      </div>
    </AuthLayout>
  );
};
