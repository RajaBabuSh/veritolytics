import React, { useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { AuthLayout } from '@/components/auth/AuthLayout';
import { Input } from '@/components/ui/Input';
import { Checkbox } from '@/components/ui/Checkbox';
import { Button } from '@/components/ui/Button';
import { ConfigNotice } from '@/components/auth/ConfigNotice';
import { useAuth } from '@/context/AuthContext';
import { Mail, Lock, User, Globe, AlertCircle, ArrowRight, Loader2, Check, Shield } from 'lucide-react';

const COUNTRIES = [
  'United States',
  'United Kingdom',
  'Canada',
  'Australia',
  'Germany',
  'France',
  'Japan',
  'Singapore',
  'India',
  'Brazil',
  'Netherlands',
  'Spain',
  'Italy',
  'Sweden',
  'Switzerland',
  'Other',
];

export const SignUpPage: React.FC = () => {
  const navigate = useNavigate();
  const { signUp } = useAuth();

  const [firstName, setFirstName] = useState('');
  const [lastName, setLastName] = useState('');
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [confirmPassword, setConfirmPassword] = useState('');
  const [country, setCountry] = useState('United States');
  const [agreeTerms, setAgreeTerms] = useState(false);

  const [errors, setErrors] = useState<{
    firstName?: string;
    lastName?: string;
    email?: string;
    password?: string;
    confirmPassword?: string;
    country?: string;
    agreeTerms?: string;
    general?: string;
  }>({});
  const [isSubmitting, setIsSubmitting] = useState(false);

  // Real-time password strength indicators
  const hasMinLength = password.length >= 8;
  const hasNumber = /\d/.test(password);
  const hasUpperOrSpecial = /[A-Z!@#$%^&*()_+\-=[\]{};':"\\|,.<>/?]/.test(password);

  const validate = (): boolean => {
    const errs: typeof errors = {};

    if (!firstName.trim() || firstName.trim().length < 2) {
      errs.firstName = 'First name must be at least 2 characters.';
    }

    if (!lastName.trim() || lastName.trim().length < 2) {
      errs.lastName = 'Last name must be at least 2 characters.';
    }

    if (!email.trim()) {
      errs.email = 'Email address is required.';
    } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
      errs.email = 'Please enter a valid email address.';
    }

    if (!password) {
      errs.password = 'Password is required.';
    } else if (!hasMinLength) {
      errs.password = 'Password must be at least 8 characters.';
    } else if (!hasNumber || !hasUpperOrSpecial) {
      errs.password = 'Password must contain at least one number and one uppercase letter or symbol.';
    }

    if (!confirmPassword) {
      errs.confirmPassword = 'Please confirm your password.';
    } else if (password !== confirmPassword) {
      errs.confirmPassword = 'Passwords do not match.';
    }

    if (!country) {
      errs.country = 'Please select your country of residence.';
    }

    if (!agreeTerms) {
      errs.agreeTerms = 'You must accept the Terms of Service and Privacy Policy.';
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

    const { error, needsVerification } = await signUp(email, password, {
      firstName: firstName.trim(),
      lastName: lastName.trim(),
      country,
    });

    setIsSubmitting(false);

    if (error) {
      setErrors({ general: error });
      return;
    }

    if (needsVerification) {
      navigate(`/verify-email?email=${encodeURIComponent(email)}`);
    } else {
      navigate('/member');
    }
  };

  return (
    <AuthLayout
      title="Create Your Account"
      subtitle="Join VERITOLYTICS today. Earn 500 Welcome Points upon profile completion."
    >
      <ConfigNotice />

      {errors.general && (
        <div className="mb-5 flex items-start gap-3 rounded-2xl border border-rose-200 bg-rose-50/90 p-4 text-xs text-rose-800 shadow-sm animate-fadeIn">
          <AlertCircle className="h-4 w-4 shrink-0 text-rose-600 mt-0.5" />
          <div className="space-y-1">
            <span className="font-bold block">Registration Error</span>
            <p className="leading-relaxed">{errors.general}</p>
          </div>
        </div>
      )}

      <form onSubmit={handleSubmit} className="space-y-4" noValidate>
        {/* Name Fields (Two-column) */}
        <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <Input
            label="First Name"
            placeholder="Jane"
            value={firstName}
            onChange={(e) => setFirstName(e.target.value)}
            error={errors.firstName}
            leftIcon={<User className="h-4 w-4" />}
            autoComplete="given-name"
            required
          />

          <Input
            label="Last Name"
            placeholder="Doe"
            value={lastName}
            onChange={(e) => setLastName(e.target.value)}
            error={errors.lastName}
            autoComplete="family-name"
            required
          />
        </div>

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

        {/* Country Selection */}
        <div className="space-y-1.5 text-left">
          <label htmlFor="country" className="block text-xs font-bold text-slate-700 tracking-wide">
            Country of Residence <span className="text-rose-500">*</span>
          </label>
          <div className="relative rounded-xl shadow-sm">
            <div className="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400">
              <Globe className="h-4 w-4" />
            </div>
            <select
              id="country"
              value={country}
              onChange={(e) => setCountry(e.target.value)}
              className="block w-full rounded-xl border border-slate-200 bg-white py-2.5 pl-10 pr-4 text-sm text-slate-900 focus:border-brand-royal-500 focus:outline-none focus:ring-2 focus:ring-brand-royal-500/20"
            >
              {COUNTRIES.map((c) => (
                <option key={c} value={c}>
                  {c}
                </option>
              ))}
            </select>
          </div>
          {errors.country && <p className="text-xs text-rose-500 font-medium">• {errors.country}</p>}
        </div>

        {/* Password */}
        <div className="space-y-1.5">
          <Input
            label="Password"
            isPassword
            placeholder="At least 8 characters"
            value={password}
            onChange={(e) => setPassword(e.target.value)}
            error={errors.password}
            leftIcon={<Lock className="h-4 w-4" />}
            autoComplete="new-password"
            required
          />

          {/* Password Strength Checklist */}
          {password && (
            <div className="rounded-xl border border-slate-100 bg-slate-50/70 p-2.5 space-y-1.5 text-[11px] text-slate-500">
              <div className="flex items-center gap-1.5">
                <Check className={`h-3 w-3 ${hasMinLength ? 'text-emerald-500' : 'text-slate-300'}`} />
                <span className={hasMinLength ? 'text-emerald-700 font-medium' : ''}>8+ characters</span>
              </div>
              <div className="flex items-center gap-1.5">
                <Check className={`h-3 w-3 ${hasNumber ? 'text-emerald-500' : 'text-slate-300'}`} />
                <span className={hasNumber ? 'text-emerald-700 font-medium' : ''}>At least one number (0-9)</span>
              </div>
              <div className="flex items-center gap-1.5">
                <Check className={`h-3 w-3 ${hasUpperOrSpecial ? 'text-emerald-500' : 'text-slate-300'}`} />
                <span className={hasUpperOrSpecial ? 'text-emerald-700 font-medium' : ''}>Uppercase letter or symbol</span>
              </div>
            </div>
          )}
        </div>

        {/* Confirm Password */}
        <Input
          label="Confirm Password"
          isPassword
          placeholder="Repeat your password"
          value={confirmPassword}
          onChange={(e) => setConfirmPassword(e.target.value)}
          error={errors.confirmPassword}
          leftIcon={<Lock className="h-4 w-4" />}
          autoComplete="new-password"
          required
        />

        {/* Terms and Privacy Checkbox */}
        <div className="pt-1">
          <Checkbox
            label={
              <span>
                I agree to the{' '}
                <a href="#terms" className="text-brand-royal-600 hover:underline font-semibold">
                  Terms of Service
                </a>{' '}
                and{' '}
                <a href="#privacy" className="text-brand-royal-600 hover:underline font-semibold">
                  Privacy Policy
                </a>
                .
              </span>
            }
            checked={agreeTerms}
            onChange={(e) => setAgreeTerms(e.target.checked)}
            error={errors.agreeTerms}
          />
        </div>

        {/* Submit Button */}
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
                <span>Creating Account...</span>
              </>
            ) : (
              <>
                <span>Create Free Panelist Account</span>
                <ArrowRight className="h-4 w-4" />
              </>
            )}
          </Button>
        </div>
      </form>

      {/* Link to Sign In */}
      <div className="mt-8 text-center border-t border-slate-200 pt-6">
        <p className="text-xs text-slate-600">
          Already have a Veritolytics account?{' '}
          <Link
            to="/login"
            className="font-bold text-brand-royal-600 hover:text-brand-royal-700 hover:underline transition-colors"
          >
            Sign In
          </Link>
        </p>
      </div>
    </AuthLayout>
  );
};
