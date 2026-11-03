import React from 'react';
import { BrowserRouter, Routes, Route, Navigate } from 'react-router-dom';
import { AuthProvider } from '@/context/AuthContext';
import { ProtectedRoute } from '@/components/auth/ProtectedRoute';

// Layouts
import { PublicLayout } from '@/layouts/PublicLayout';
import { MemberLayout } from '@/layouts/MemberLayout';
import { AdminLayout } from '@/layouts/AdminLayout';

// Public Pages
import { LandingPage } from '@/pages/public/LandingPage';
import { HowItWorksPage } from '@/pages/public/HowItWorksPage';
import { RewardsCatalogPage } from '@/pages/public/RewardsCatalogPage';

// Auth Pages
import { LoginPage } from '@/pages/auth/LoginPage';
import { SignUpPage } from '@/pages/auth/SignUpPage';
import { ForgotPasswordPage } from '@/pages/auth/ForgotPasswordPage';
import { ResetPasswordPage } from '@/pages/auth/ResetPasswordPage';
import { VerifyEmailPage } from '@/pages/auth/VerifyEmailPage';

// Member Pages
import { MemberDashboardPage } from '@/pages/member/MemberDashboardPage';
import { MemberSurveysPage } from '@/pages/member/MemberSurveysPage';
import { MemberRewardsPage } from '@/pages/member/MemberRewardsPage';
import { MemberWalletPage } from '@/pages/member/MemberWalletPage';
import { MemberSettingsPage } from '@/pages/member/MemberSettingsPage';

// Admin Pages
import { AdminDashboardPage } from '@/pages/admin/AdminDashboardPage';
import { AdminSurveysPage } from '@/pages/admin/AdminSurveysPage';
import { AdminUsersPage } from '@/pages/admin/AdminUsersPage';
import { AdminPayoutsPage } from '@/pages/admin/AdminPayoutsPage';

// Fallback
import { NotFoundPage } from '@/pages/NotFoundPage';

export const App: React.FC = () => {
  return (
    <AuthProvider>
      <BrowserRouter>
        <Routes>
          {/* Public Website Routes */}
          <Route element={<PublicLayout />}>
            <Route path="/" element={<LandingPage />} />
            <Route path="/how-it-works" element={<HowItWorksPage />} />
            <Route path="/rewards" element={<RewardsCatalogPage />} />
            <Route path="/404" element={<NotFoundPage />} />
          </Route>

          {/* Authentication Split-Screen Routes */}
          <Route path="/login" element={<LoginPage />} />
          <Route path="/signup" element={<SignUpPage />} />
          <Route path="/register" element={<SignUpPage />} />
          <Route path="/forgot-password" element={<ForgotPasswordPage />} />
          <Route path="/reset-password" element={<ResetPasswordPage />} />
          <Route path="/verify-email" element={<VerifyEmailPage />} />

          {/* Protected Member Dashboard Routes */}
          <Route
            path="/member"
            element={
              <ProtectedRoute>
                <MemberLayout />
              </ProtectedRoute>
            }
          >
            <Route index element={<MemberDashboardPage />} />
            <Route path="surveys" element={<MemberSurveysPage />} />
            <Route path="rewards" element={<MemberRewardsPage />} />
            <Route path="wallet" element={<MemberWalletPage />} />
            <Route path="settings" element={<MemberSettingsPage />} />
          </Route>

          {/* Protected Admin Dashboard Routes (Admin-Only RBAC) */}
          <Route
            path="/admin"
            element={
              <ProtectedRoute adminOnly={true}>
                <AdminLayout />
              </ProtectedRoute>
            }
          >
            <Route index element={<AdminDashboardPage />} />
            <Route path="surveys" element={<AdminSurveysPage />} />
            <Route path="users" element={<AdminUsersPage />} />
            <Route path="payouts" element={<AdminPayoutsPage />} />
          </Route>

          {/* 404 Route */}
          <Route path="*" element={<Navigate to="/404" replace />} />
        </Routes>
      </BrowserRouter>
    </AuthProvider>
  );
};

export default App;
