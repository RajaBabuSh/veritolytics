import React, { useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { AuthLayout } from '@/components/auth/AuthLayout';
import { Input } from '@/components/ui/Input';
import { Button } from '@/components/ui/Button';
import { ConfigNotice } from '@/components/auth/ConfigNotice';
import { useAuth } from '@/context/AuthContext';
import { Lock, CheckCircle2, AlertCircle, ArrowRight, Loader2 } from 'lucide-react';

export const ResetPasswordPage: React.FC = () => {
  const navigate = useNavigate();
  const { updatePassword } = useAuth();

  const [password, setPassword] = useState('');
  const [confirmPassword, setConfirmPassword] = useState('');
  const [errors, setErrors] = useState<{ password?: string; confirmPassword?: string; general?: string }>({});
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [isSuccess, setIsSuccess] = useState(false);

  const validate = (): boolean => {
    const errs: typeof errors = {};

    if (!password) {
      errs.password = 'New password is required.';
    } else if (password.length < 8) {
      errs.password = 'Password must be at least 8 characters.';
    } else if (!/\d/.test(password)) {
      errs.password = 'Password must include at least one number.';
    }

    if (!confirmPassword) {
      errs.confirmPassword = 'Confirm your new password.';
    } else if (password !== confirmPassword) {
      errs.confirmPassword = 'Passwords do not match.';
    }

    setErrors(errs);
    return Object.keys(errs).length === 0;
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setErrors({});

    if (!validate()) {
      return;
    }

    setIsSubmitting(true);
    const { error } = await updatePassword(password);
    setIsSubmitting(false);

    if (error) {
      setErrors({ general: error });
      return;
    }

    setIsSuccess(true);
  };

  return (
    <AuthLayout
      title="Create New Password"
      subtitle="Choose a strong, unique password to secure your Veritolytics panelist account."
    >
      <ConfigNotice />

      {errors.general && (
        <div className="mb-5 flex items-start gap-3 rounded-2xl border border-rose-200 bg-rose-50/90 p-4 text-xs text-rose-800 shadow-sm animate-fadeIn">
          <AlertCircle className="h-4 w-4 shrink-0 text-rose-600 mt-0.5" />
          <div className="space-y-1">
            <span className="font-bold block">Password Update Failed</span>
            <p className="leading-relaxed">{errors.general}</p>
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
              Password Reset Successful!
            </h3>
            <p className="text-xs text-emerald-800 leading-relaxed">
              Your password has been updated securely. You can now sign in with your new credentials.
            </p>
          </div>

          <div className="pt-2">
            <Link to="/login">
              <Button variant="royal" className="w-full text-xs font-bold gap-2">
                <span>Sign In Now</span>
                <ArrowRight className="h-4 w-4" />
              </Button>
            </Link>
          </div>
        </div>
      ) : (
        <form onSubmit={handleSubmit} className="space-y-4" noValidate>
          <Input
            label="New Password"
            isPassword
            placeholder="At least 8 characters"
            value={password}
            onChange={(e) => setPassword(e.target.value)}
            error={errors.password}
            leftIcon={<Lock className="h-4 w-4" />}
            autoComplete="new-password"
            required
          />

          <Input
            label="Confirm New Password"
            isPassword
            placeholder="Repeat new password"
            value={confirmPassword}
            onChange={(e) => setConfirmPassword(e.target.value)}
            error={errors.confirmPassword}
            leftIcon={<Lock className="h-4 w-4" />}
            autoComplete="new-password"
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
                  <span>Updating Password...</span>
                </>
              ) : (
                <>
                  <span>Save New Password</span>
                  <ArrowRight className="h-4 w-4" />
                </>
              )}
            </Button>
          </div>
        </form>
      )}

      {/* Link to Sign In */}
      <div className="mt-8 text-center border-t border-slate-200 pt-6">
        <Link
          to="/login"
          className="text-xs font-bold text-brand-royal-600 hover:text-brand-royal-700 hover:underline transition-colors"
        >
          Return to Sign In
        </Link>
      </div>
    </AuthLayout>
  );
};
