import React, { useState, useEffect } from 'react';
import { NavLink, useNavigate } from 'react-router-dom';
import { Logo } from '@/components/brand/Logo';
import {
  LayoutDashboard,
  Users,
  FileQuestion,
  CreditCard,
  LogOut,
  ArrowUpRight,
  Shield,
} from 'lucide-react';
import { useAuth } from '@/context/AuthContext';
import { dataService } from '@/services/dataService';

interface AdminSidebarProps {
  onCloseMobile?: () => void;
}

export const AdminSidebar: React.FC<AdminSidebarProps> = ({ onCloseMobile }) => {
  const { signOut } = useAuth();
  const navigate = useNavigate();
  const [liveSurveysCount, setLiveSurveysCount] = useState<number>(0);
  const [pendingPayoutsCount, setPendingPayoutsCount] = useState<number>(0);

  useEffect(() => {
    let isMounted = true;
    dataService.getAllSurveysAdmin().then((surveys) => {
      if (isMounted) {
        setLiveSurveysCount(surveys.filter((s) => s.status === 'active').length);
      }
    });
    dataService.getRedemptions().then((redemptions) => {
      if (isMounted) {
        setPendingPayoutsCount(redemptions.filter((r) => r.status === 'pending').length);
      }
    });
    return () => {
      isMounted = false;
    };
  }, []);

  const handleLogout = async () => {
    await signOut();
    if (onCloseMobile) onCloseMobile();
    navigate('/login');
  };

  const adminLinks = [
    { name: 'Analytics & Pulse', to: '/admin', icon: LayoutDashboard },
    {
      name: 'Survey Campaigns',
      to: '/admin/surveys',
      icon: FileQuestion,
      badge: liveSurveysCount > 0 ? `${liveSurveysCount} Live` : undefined,
    },
    { name: 'User Management', to: '/admin/users', icon: Users },
    {
      name: 'Reward Payouts',
      to: '/admin/payouts',
      icon: CreditCard,
      badge: pendingPayoutsCount > 0 ? `${pendingPayoutsCount} Pending` : undefined,
    },
  ];

  return (
    <aside className="flex h-full w-64 flex-col justify-between border-r border-brand-navy-800 bg-brand-navy-950 p-4 text-slate-300">
      <div className="space-y-6">
        {/* Brand Logo Dark Variant */}
        <div className="px-2 pt-2">
          <Logo variant="light" size="md" to="/" />
        </div>

        {/* Admin Badge */}
        <div className="flex items-center gap-2 rounded-xl border border-brand-royal-900 bg-brand-navy-900/90 px-3.5 py-2.5">
          <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-brand-royal-600 text-white font-bold text-xs shadow-glow-royal">
            ADM
          </div>
          <div className="overflow-hidden">
            <div className="flex items-center gap-1.5 text-xs font-bold text-white">
              <span>Platform Administrator</span>
            </div>
            <span className="text-[10px] text-slate-400">VERITOLYTICS Control Center</span>
          </div>
        </div>

        {/* Admin Navigation */}
        <nav className="space-y-1">
          <div className="px-3 pb-2 text-[11px] font-bold uppercase tracking-wider text-slate-400">
            Control Center
          </div>
          {adminLinks.map((item) => {
            const Icon = item.icon;
            return (
              <NavLink
                key={item.name}
                to={item.to}
                end={item.to === '/admin'}
                onClick={onCloseMobile}
                className={({ isActive }) =>
                  `flex items-center justify-between rounded-xl px-3.5 py-2.5 text-sm font-medium transition-all ${
                    isActive
                      ? 'bg-gradient-to-r from-brand-royal-700 to-brand-royal-600 text-white shadow-md shadow-brand-royal-900/50'
                      : 'text-slate-400 hover:bg-brand-navy-900 hover:text-white'
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
                            : 'bg-brand-navy-800 text-brand-royal-300 border border-brand-royal-800/40'
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

      {/* Bottom Switcher & Sign Out */}
      <div className="space-y-2 border-t border-brand-navy-800/80 pt-4">
        <NavLink
          to="/member"
          onClick={onCloseMobile}
          className="flex items-center justify-between rounded-xl border border-brand-navy-700 bg-brand-navy-900/80 px-3.5 py-2 text-xs font-semibold text-slate-300 hover:bg-brand-navy-800 hover:text-white transition-colors"
        >
          <span>View as Member</span>
          <ArrowUpRight className="h-3.5 w-3.5 text-brand-royal-400" />
        </NavLink>

        <button
          onClick={handleLogout}
          type="button"
          className="w-full flex items-center gap-2.5 rounded-xl px-3.5 py-2 text-xs text-rose-400 hover:bg-brand-navy-900 hover:text-rose-300 transition-colors text-left font-medium"
        >
          <LogOut className="h-4 w-4" />
          <span>Exit Admin Session</span>
        </button>
      </div>
    </aside>
  );
};
