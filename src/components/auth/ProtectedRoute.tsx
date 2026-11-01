import React, { useState, useEffect } from 'react';
import { Navigate, useLocation, Outlet } from 'react-router-dom';
import { useAuth } from '@/context/AuthContext';
import { Loader2, ShieldAlert } from 'lucide-react';
import { dataService } from '@/services/dataService';
import { UserRole } from '@/types';

interface ProtectedRouteProps {
  children?: React.ReactNode;
  adminOnly?: boolean;
}

export const ProtectedRoute: React.FC<ProtectedRouteProps> = ({ children, adminOnly = false }) => {
  const { user, loading, isConfigured } = useAuth();
  const location = useLocation();
  const [userRole, setUserRole] = useState<UserRole | null>(null);
  const [checkingRole, setCheckingRole] = useState(adminOnly);

  useEffect(() => {
    let isMounted = true;
    if (user && adminOnly) {
      dataService.getUserProfile(user.id, user.user_metadata, user.email).then((p) => {
        if (isMounted) {
          setUserRole(p.role);
          setCheckingRole(false);
        }
      });
    } else {
      setCheckingRole(false);
    }
    return () => {
      isMounted = false;
    };
  }, [user, adminOnly]);

  // Show loading indicator while session state resolves
  if (loading || checkingRole) {
    return (
      <div className="min-h-screen flex flex-col items-center justify-center bg-slate-50 text-slate-600 space-y-3">
        <Loader2 className="h-8 w-8 animate-spin text-brand-royal-600" />
        <p className="text-xs font-semibold text-slate-500">Verifying security credentials...</p>
      </div>
    );
  }

  // If Supabase is configured and user is NOT authenticated, redirect to login
  if (isConfigured && !user) {
    return <Navigate to="/login" state={{ from: location }} replace />;
  }

  // Admin access check: ordinary members cannot access admin console
  if (adminOnly && user) {
    const isExplicitAdmin = user.user_metadata?.role === 'admin' || userRole === 'admin';
    if (!isExplicitAdmin) {
      return <Navigate to="/member" state={{ error: 'Access restricted to authorized administrators.' }} replace />;
    }
  }

  return children ? <>{children}</> : <Outlet />;
};
