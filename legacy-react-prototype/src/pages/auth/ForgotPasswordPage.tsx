import React, { useState } from 'react';
import { Link } from 'react-router-dom';
import { AuthLayout } from '@/components/auth/AuthLayout';
import { Input } from '@/components/ui/Input';
import { Button } from '@/components/ui/Button';
import { ConfigNotice } from '@/components/auth/ConfigNotice';
import { useAuth } from '@/context/AuthContext';
import { Mail, ArrowLeft, ArrowRight, CheckCircle2, AlertCircle, Loader2 } from 'lucide-react';

export const ForgotPasswordPage: React.FC = () => {
  const { resetPassword } = useAuth();

  const [email, setEmail] = useState('');
  const [error, setError] = useState<string | null>(null);
  const [isSuccess, setIsSuccess] = useState(false);
  const [isSubmitting, setIsSubmitting] = useState(false);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setError(null);

    if (!email.trim()) {
      setError('Email address is required.');
      return;
    }

    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
      setError('Please enter a valid email address.');
      return;
    }

    setIsSubmitting(true);
    const { error: resetError } = await resetPassword(email);
    setIsSubmitting(false);

    if (resetError) {
      setError(resetError);
      return;
    }

    setIsSuccess(true);
  };

  return (
    <AuthLayout
      title="Reset Your Password"
      subtitle="Enter the email associated with your Veritolytics account to receive a secure recovery link."
    >
      <ConfigNotice />

      {error && (
        <div className="mb-5 flex items-start gap-3 rounded-2xl border border-rose-200 bg-rose-50/90 p-4 text-xs text-rose-800 shadow-sm animate-fadeIn">
          <AlertCircle className="h-4 w-4 shrink-0 text-rose-600 mt-0.5" />
          <div className="space-y-1">
            <span className="font-bold block">Reset Request Failed</span>
            <p className="leading-relaxed">{error}</p>
          </div>
        </div>
      )}

      {isSuccess ? (
        <div className="space-y-5 rounded-2xl border border-emerald-200 bg-emerald-50/80 p-6 text-center animate-fadeIn">
          <div className="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-600 text-white shadow-md">
            <CheckCircle2 className="h-6 w-6" />
          </div>

          <div className="space-y-1.5">
            <h3 className="text-base font-bold text-emerald-950">
              Recovery Link Dispatched
            </h3>
            <p className="text-xs text-emerald-800 leading-relaxed">
              We have sent a password reset link to{' '}
              <strong className="text-emerald-950">{email}</strong>. Please check your inbox and spam folder.
            </p>
          </div>

          <div className="pt-2">
            <Link to="/login">
              <Button variant="outline" className="w-full text-xs font-bold">
                Return to Sign In
              </Button>
            </Link>
          </div>
        </div>
      ) : (
        <form onSubmit={handleSubmit} className="space-y-4" noValidate>
          <Input
            label="Email Address"
            type="email"
            placeholder="name@example.com"
            value={email}
            onChange={(e) => setEmail(e.target.value)}
            error={error ?? undefined}
            leftIcon={<Mail className="h-4 w-4" />}
            autoComplete="email"
            required
          />

          <div className="pt-2">
            <Button
              type="submit"
              variant="royal"
              size="lg"
              className="w-full justify-center gap-2 shadow-glow-royal font-bold"
              disabled={isSubmitting}
            >
              {isSubmitting ? (
                <>
                  <Loader2 className="h-4 w-4 animate-spin" />
                  <span>Sending Link...</span>
                </>
              ) : (
                <>
                  <span>Send Recovery Link</span>
                  <ArrowRight className="h-4 w-4" />
                </>
              )}
            </Button>
          </div>
        </form>
      )}

      {/* Back to Sign In */}
      <div className="mt-8 text-center border-t border-slate-200 pt-6">
        <Link
          to="/login"
          className="inline-flex items-center gap-1.5 text-xs font-bold text-slate-600 hover:text-brand-royal-600 transition-colors"
        >
          <ArrowLeft className="h-3.5 w-3.5" />
          <span>Back to Sign In</span>
        </Link>
      </div>
    </AuthLayout>
  );
};
