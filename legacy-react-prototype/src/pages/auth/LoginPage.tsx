import React, { useState } from 'react';
import { Link, useNavigate, useLocation } from 'react-router-dom';
import { AuthLayout } from '@/components/auth/AuthLayout';
import { Input } from '@/components/ui/Input';
import { Checkbox } from '@/components/ui/Checkbox';
import { Button } from '@/components/ui/Button';
import { ConfigNotice } from '@/components/auth/ConfigNotice';
import { useAuth } from '@/context/AuthContext';
import { Mail, Lock, AlertCircle, ArrowRight, Loader2 } from 'lucide-react';

export const LoginPage: React.FC = () => {
  const navigate = useNavigate();
  const location = useLocation();
  const { signIn, isConfigured } = useAuth();

  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [rememberMe, setRememberMe] = useState(false);

  const [errors, setErrors] = useState<{ email?: string; password?: string; general?: string }>({});
  const [isSubmitting, setIsSubmitting] = useState(false);

  const validate = (): boolean => {
    const errs: { email?: string; password?: string } = {};

    if (!email.trim()) {
      errs.email = 'Email address is required.';
    } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
      errs.email = 'Please enter a valid email address.';
    }

    if (!password) {
      errs.password = 'Password is required.';
    } else if (password.length < 6) {
      errs.password = 'Password must be at least 6 characters.';
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

    const { error } = await signIn(email, password);

    setIsSubmitting(false);

    if (error) {
      setErrors({ general: error });
      return;
    }

    // Redirect to requested page or default to member dashboard
    const destination = (location.state as { from?: { pathname: string } })?.from?.pathname || '/member';
    navigate(destination, { replace: true });
  };

  return (
    <AuthLayout
      title="Welcome Back"
      subtitle="Sign in to your Veritolytics panel account to access active surveys and rewards."
    >
      <ConfigNotice />

      {errors.general && (
        <div className="mb-5 flex items-start gap-3 rounded-2xl border border-rose-200 bg-rose-50/90 p-4 text-xs text-rose-800 shadow-sm animate-fadeIn">
          <AlertCircle className="h-4 w-4 shrink-0 text-rose-600 mt-0.5" />
          <div className="space-y-1">
            <span className="font-bold block">Sign In Failed</span>
            <p className="leading-relaxed">{errors.general}</p>
          </div>
        </div>
      )}

      <form onSubmit={handleSubmit} className="space-y-4" noValidate>
        {/* Email Address */}
        <Input
          label="Email Address"
          type="email"
          placeholder="name@example.com"
          value={email}
          onChange={(e) => setEmail(e.target.value)}
          error={errors.email}
          leftIcon={<Mail className="h-4 w-4" />}
          autoComplete="email"
          required
        />

        {/* Password */}
        <Input
          label="Password"
          isPassword
          placeholder="Enter your account password"
          value={password}
          onChange={(e) => setPassword(e.target.value)}
          error={errors.password}
          leftIcon={<Lock className="h-4 w-4" />}
          autoComplete="current-password"
          required
        />

        {/* Remember Me & Forgot Password */}
        <div className="flex items-center justify-between pt-1">
          <Checkbox
            label="Remember this device"
            checked={rememberMe}
            onChange={(e) => setRememberMe(e.target.checked)}
          />

          <Link
            to="/forgot-password"
            className="text-xs font-semibold text-brand-royal-600 hover:text-brand-royal-700 hover:underline transition-colors"
          >
            Forgot password?
          </Link>
        </div>

        {/* Submit Button */}
        <div className="pt-2">
          <Button
            type="submit"
            variant="royal"
            size="lg"
            className="w-full justify-center gap-2 shadow-glow-royal"
            disabled={isSubmitting}
          >
            {isSubmitting ? (
              <>
                <Loader2 className="h-4 w-4 animate-spin" />
                <span>Signing In...</span>
              </>
            ) : (
              <>
                <span>Sign In to Veritolytics</span>
                <ArrowRight className="h-4 w-4" />
              </>
            )}
          </Button>
        </div>
      </form>

      {/* Link to Sign Up */}
      <div className="mt-8 text-center border-t border-slate-200 pt-6">
        <p className="text-xs text-slate-600">
          Don&apos;t have a Veritolytics account yet?{' '}
          <Link
            to="/signup"
            className="font-bold text-brand-royal-600 hover:text-brand-royal-700 hover:underline transition-colors"
          >
            Create an Account
          </Link>
        </p>
      </div>
    </AuthLayout>
  );
};
