import React, { useState, useEffect } from 'react';
import { NavLink, useNavigate } from 'react-router-dom';
import { Logo } from '@/components/brand/Logo';
import {
  LayoutDashboard,
  ClipboardList,
  Gift,
  Wallet,
  Settings,
  LogOut,
  ChevronRight,
  ShieldCheck,
  AlertCircle,
  Loader2,
} from 'lucide-react';
import { Button } from '@/components/ui/Button';
import { useAuth } from '@/context/AuthContext';
import { supabase } from '@/lib/supabase';
import { dataService } from '@/services/dataService';

interface MemberSidebarProps {
  onCloseMobile?: () => void;
}

export const MemberSidebar: React.FC<MemberSidebarProps> = ({ onCloseMobile }) => {
  const { user, signOut: contextSignOut } = useAuth();
  const navigate = useNavigate();

  const [balancePoints, setBalancePoints] = useState<number>(0);
  const [availableSurveyCount, setAvailableSurveyCount] = useState<number>(0);
  const [showConfirm, setShowConfirm] = useState(false);
  const [isLoggingOut, setIsLoggingOut] = useState(false);
  const [logoutError, setLogoutError] = useState<string | null>(null);

  useEffect(() => {
    let isMounted = true;
    if (user) {
      dataService.getUserProfile(user.id, user.user_metadata, user.email).then((profile) => {
        if (isMounted) setBalancePoints(profile.balancePoints);
      });
      dataService.getSurveys().then((surveys) => {
        if (isMounted) setAvailableSurveyCount(surveys.length);
      });
    }
    return () => {
      isMounted = false;
    };
  }, [user]);

  const handleSignOut = async () => {
    setIsLoggingOut(true);
    setLogoutError(null);

    try {
      // Direct Supabase sign out
      if (supabase) {
        const { error } = await supabase.auth.signOut();
        if (error) {
          setLogoutError(error.message);
          setIsLoggingOut(false);
          return;
        }
      }

      // Clear local auth context state
      await contextSignOut();

      setShowConfirm(false);
      if (onCloseMobile) onCloseMobile();

      // Navigate to login page
      navigate('/login', { replace: true });
    } catch (err: any) {
      const msg = err instanceof Error ? err.message : 'An error occurred while logging out.';
      setLogoutError(msg);
      setIsLoggingOut(false);
    }
  };

  const navItems = [
    { name: 'Overview', to: '/member', icon: LayoutDashboard },
    {
      name: 'Surveys',
      to: '/member/surveys',
      icon: ClipboardList,
      badge: availableSurveyCount > 0 ? `${availableSurveyCount} Live` : undefined,
    },
    { name: 'My Rewards', to: '/member/rewards', icon: Gift },
    { name: 'Wallet & Cashouts', to: '/member/wallet', icon: Wallet },
    { name: 'Settings', to: '/member/settings', icon: Settings },
  ];

  const firstName = user?.user_metadata?.first_name || '';
  const lastName = user?.user_metadata?.last_name || '';
  const fullName = `${firstName} ${lastName}`.trim() || user?.email?.split('@')[0] || 'Panelist';
  const initialLetters = (firstName && lastName ? `${firstName[0]}${lastName[0]}` : fullName.slice(0, 2)).toUpperCase();

  return (
    <>
      <aside className="flex h-full w-full flex-col justify-between border-r border-slate-200 bg-white p-4 overflow-y-auto">
        {/* Top Section */}
        <div className="space-y-5">
          {/* Brand Logo */}
          <div className="px-2 pt-1">
            <Logo variant="dark" size="md" to="/" />
          </div>

          {/* Real User Profile Pill */}
          <div className="rounded-xl border border-brand-royal-100 bg-gradient-to-br from-brand-royal-50/70 via-white to-brand-violet-50/50 p-3.5 shadow-sm">
            <div className="flex items-center gap-3">
              <div className="relative flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-tr from-brand-navy-900 to-brand-royal-600 font-bold text-white shadow-sm text-xs shrink-0">
                {initialLetters}
                <span className="absolute -bottom-0.5 -right-0.5 h-3 w-3 rounded-full border-2 border-white bg-emerald-500" />
              </div>
              <div className="overflow-hidden">
                <h4 className="truncate text-sm font-bold text-slate-900">{fullName}</h4>
                <div className="flex items-center gap-1 text-[11px] font-semibold text-brand-royal-600">
                  <ShieldCheck className="h-3 w-3" />
                  <span>Verified Panelist</span>
                </div>
              </div>
            </div>

            <div className="mt-3 flex items-center justify-between border-t border-slate-100 pt-2 text-xs">
              <span className="text-slate-500">Available Points</span>
              <span className="font-extrabold text-brand-royal-600">
                {balancePoints.toLocaleString()} pts
              </span>
            </div>
          </div>

          {/* Navigation Section */}
          <nav className="space-y-1">
            <div className="px-3 pb-2 text-[11px] font-bold uppercase tracking-wider text-slate-400">
              Member Hub
            </div>
            {navItems.map((item) => {
              const Icon = item.icon;
              return (
                <NavLink
                  key={item.name}
                  to={item.to}
                  end={item.to === '/member'}
                  onClick={onCloseMobile}
                  className={({ isActive }) =>
                    `flex items-center justify-between rounded-xl px-3.5 py-2.5 text-sm font-medium transition-all ${
                      isActive
                        ? 'bg-brand-royal-600 text-white shadow-md shadow-brand-royal-600/20'
                        : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900'
                    }`
                  }
                >
                  {({ isActive }) => (
                    <>
                      <div className="flex items-center gap-3">
                        <Icon className={`h-4 w-4 ${isActive ? 'text-white' : 'text-slate-400'}`} />
                        <span>{item.name}</span>
                      </div>
                      {item.badge && (
                        <span
                          className={`rounded-full px-2 py-0.5 text-[10px] font-bold ${
                            isActive
                              ? 'bg-white/20 text-white'
                              : 'bg-brand-royal-100 text-brand-royal-700'
                          }`}
                        >
                          {item.badge}
                        </span>
                      )}
                    </>
                  )}
                </NavLink>
              );
            })}
          </nav>
        </div>

        {/* Bottom Actions: Anchored to Bottom with mt-auto, positioned below Settings */}
        <div className="mt-auto pt-4 border-t border-slate-100 space-y-2 shrink-0">
          {/* Admin Switcher (Only visible to admin role) */}
          {user?.user_metadata?.role === 'admin' && (
            <NavLink
              to="/admin"
              onClick={onCloseMobile}
              className="flex items-center justify-between rounded-xl border border-brand-violet-200/80 bg-brand-violet-50/50 px-3.5 py-2 text-xs font-semibold text-brand-violet-700 hover:bg-brand-violet-100/60 transition-colors"
            >
              <span>Admin Control Center</span>
              <ChevronRight className="h-3.5 w-3.5" />
            </NavLink>
          )}

          {/* Logout Error Banner */}
          {logoutError && (
            <div className="rounded-xl border border-rose-200 bg-rose-50 p-2.5 text-xs text-rose-800 flex items-start gap-1.5 animate-fadeIn">
              <AlertCircle className="h-4 w-4 shrink-0 text-rose-600 mt-0.5" />
              <span className="text-[11px] leading-tight">{logoutError}</span>
            </div>
          )}

          {/* VISIBLE LOGOUT BUTTON: Directly below Settings, anchored at bottom */}
          <button
            type="button"
            id="member-logout-btn"
            onClick={() => setShowConfirm(true)}
            className="group flex w-full items-center justify-between rounded-xl border border-rose-200 bg-rose-50/80 px-3.5 py-2.5 text-xs font-bold text-rose-600 hover:bg-rose-100 hover:border-rose-300 transition-all shadow-sm focus:outline-none focus:ring-2 focus:ring-rose-500/20 active:scale-[0.99]"
          >
            <div className="flex items-center gap-2.5">
              <LogOut className="h-4 w-4 text-rose-600 group-hover:-translate-x-0.5 transition-transform" />
              <span className="text-red-600 font-bold">Logout</span>
            </div>
            <span className="text-[10px] text-rose-400 group-hover:text-rose-600 font-medium transition-colors">
              End Session
            </span>
          </button>
        </div>
      </aside>

      {/* Confirmation Dialog Modal */}
      {showConfirm && (
        <div className="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm animate-fadeIn">
          <div
            className="fixed inset-0"
            onClick={() => !isLoggingOut && setShowConfirm(false)}
          />
          <div className="relative z-10 w-full max-w-sm rounded-2xl border border-slate-200 bg-white p-6 shadow-2xl space-y-4">
            <div className="flex items-center gap-3">
              <div className="flex h-11 w-11 items-center justify-center rounded-2xl bg-rose-100 text-rose-600 shrink-0 shadow-sm">
                <LogOut className="h-5 w-5" />
              </div>
              <div>
                <h3 className="text-base font-bold text-slate-900">Confirm Logout</h3>
                <p className="text-xs text-slate-500">Are you sure you want to log out?</p>
              </div>
            </div>

            <p className="text-xs text-slate-600 leading-relaxed">
              You will be signed out of your Veritolytics panel account and redirected to the login screen.
            </p>

            <div className="flex items-center justify-end gap-2.5 pt-2 border-t border-slate-100">
              <Button
                type="button"
                variant="outline"
                size="sm"
                disabled={isLoggingOut}
                onClick={() => setShowConfirm(false)}
                className="text-xs font-semibold"
              >
                Cancel
              </Button>
              <Button
                type="button"
                size="sm"
                disabled={isLoggingOut}
                onClick={handleSignOut}
                className="bg-red-600 hover:bg-red-700 text-white font-bold text-xs gap-1.5 shadow-sm"
              >
                {isLoggingOut ? (
                  <>
                    <Loader2 className="h-3.5 w-3.5 animate-spin" />
                    <span>Logging out...</span>
                  </>
                ) : (
                  <>
                    <LogOut className="h-3.5 w-3.5" />
                    <span>Logout</span>
                  </>
                )}
              </Button>
            </div>
          </div>
        </div>
      )}
    </>
  );
};
