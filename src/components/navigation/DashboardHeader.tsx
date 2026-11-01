import React, { useState, useEffect, useRef } from 'react';
import { useNavigate, Link } from 'react-router-dom';
import {
  Menu,
  Bell,
  BellOff,
  Search,
  Sparkles,
  Shield,
  User,
  KeyRound,
  Settings,
  LogOut,
  ChevronDown,
  ShieldCheck,
  CheckCircle2,
  AlertCircle,
  Loader2,
  Coins,
  ExternalLink,
  CheckCheck,
  Trash2,
  X,
  ClipboardList,
  Gift,
  Info,
} from 'lucide-react';
import { useAuth } from '@/context/AuthContext';
import { supabase } from '@/lib/supabase';
import { dataService } from '@/services/dataService';
import { AppNotification } from '@/types';
import { ChangePasswordModal } from '@/components/auth/ChangePasswordModal';
import { Button } from '@/components/ui/Button';

interface DashboardHeaderProps {
  title: string;
  subtitle?: string;
  onOpenMobileSidebar: () => void;
  isAdmin?: boolean;
  balancePoints?: number;
}

function formatNotificationTime(isoString: string): string {
  try {
    const date = new Date(isoString);
    const now = new Date();
    const diffMs = now.getTime() - date.getTime();
    const diffSec = Math.floor(diffMs / 1000);
    const diffMin = Math.floor(diffSec / 60);
    const diffHours = Math.floor(diffMin / 60);
    const diffDays = Math.floor(diffHours / 24);

    if (diffMin < 1) return 'Just now';
    if (diffMin < 60) return `${diffMin}m ago`;
    if (diffHours < 24) return `${diffHours}h ago`;
    if (diffDays === 1) return 'Yesterday';
    if (diffDays < 7) return `${diffDays}d ago`;
    return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
  } catch {
    return 'Recently';
  }
}

export const DashboardHeader: React.FC<DashboardHeaderProps> = ({
  title,
  subtitle,
  onOpenMobileSidebar,
  isAdmin = false,
  balancePoints = 0,
}) => {
  const { user, signOut: contextSignOut } = useAuth();
  const navigate = useNavigate();

  // Dropdown & Modal States
  const [dropdownOpen, setDropdownOpen] = useState(false);
  const [notifDropdownOpen, setNotifDropdownOpen] = useState(false);
  const [showPasswordModal, setShowPasswordModal] = useState(false);
  const [showLogoutConfirm, setShowLogoutConfirm] = useState(false);
  const [isLoggingOut, setIsLoggingOut] = useState(false);
  const [logoutError, setLogoutError] = useState<string | null>(null);

  // Real Database Notifications State
  const [notifications, setNotifications] = useState<AppNotification[]>([]);
  const [loadingNotifications, setLoadingNotifications] = useState(true);

  const dropdownRef = useRef<HTMLDivElement>(null);
  const notifDropdownRef = useRef<HTMLDivElement>(null);

  // Fetch real notifications for the authenticated user
  const loadNotifications = async () => {
    if (!user?.id) {
      setLoadingNotifications(false);
      return;
    }
    try {
      const list = await dataService.getNotifications(user.id);
      setNotifications(list);
    } catch (err) {
      console.warn('Error fetching member notifications:', err);
    } finally {
      setLoadingNotifications(false);
    }
  };

  useEffect(() => {
    loadNotifications();
  }, [user?.id]);

  const unreadCount = notifications.filter((n) => !n.isRead).length;

  // Close dropdowns on outside click or Escape key
  useEffect(() => {
    const handleClickOutside = (e: MouseEvent) => {
      const target = e.target as Node;
      if (dropdownRef.current && !dropdownRef.current.contains(target)) {
        setDropdownOpen(false);
      }
      if (notifDropdownRef.current && !notifDropdownRef.current.contains(target)) {
        setNotifDropdownOpen(false);
      }
    };

    const handleKeyDown = (e: KeyboardEvent) => {
      if (e.key === 'Escape') {
        setDropdownOpen(false);
        setNotifDropdownOpen(false);
      }
    };

    if (dropdownOpen || notifDropdownOpen) {
      document.addEventListener('mousedown', handleClickOutside);
      document.addEventListener('keydown', handleKeyDown);
    }
    return () => {
      document.removeEventListener('mousedown', handleClickOutside);
      document.removeEventListener('keydown', handleKeyDown);
    };
  }, [dropdownOpen, notifDropdownOpen]);

  // Authenticated user profile details
  const firstName = user?.user_metadata?.first_name || '';
  const lastName = user?.user_metadata?.last_name || '';
  const fullName = `${firstName} ${lastName}`.trim() || user?.email?.split('@')[0] || 'Panelist';
  const email = user?.email || 'panelist@veritolytics.com';
  const isEmailVerified = Boolean(user?.email_confirmed_at);
  const initials = (firstName && lastName ? `${firstName[0]}${lastName[0]}` : fullName.slice(0, 2)).toUpperCase();

  // Notification Action Handlers
  const handleMarkAsRead = async (notifId: string) => {
    await dataService.markNotificationAsRead(notifId);
    setNotifications((prev) =>
      prev.map((n) => (n.id === notifId ? { ...n, isRead: true } : n))
    );
  };

  const handleMarkAllAsRead = async () => {
    if (!user?.id) return;
    await dataService.markAllNotificationsAsRead(user.id);
    setNotifications((prev) => prev.map((n) => ({ ...n, isRead: true })));
  };

  const handleDeleteNotification = async (e: React.MouseEvent, notifId: string) => {
    e.stopPropagation();
    await dataService.deleteNotification(notifId);
    setNotifications((prev) => prev.filter((n) => n.id !== notifId));
  };

  const handleClearAllNotifications = async () => {
    if (!user?.id) return;
    await dataService.clearAllNotifications(user.id);
    setNotifications([]);
  };

  const handleNotificationClick = async (notif: AppNotification) => {
    if (!notif.isRead) {
      await handleMarkAsRead(notif.id);
    }
    setNotifDropdownOpen(false);
    if (notif.link) {
      navigate(notif.link);
    }
  };

  // Sign out handler with Supabase signOut and session clearing
  const handleSignOut = async () => {
    setIsLoggingOut(true);
    setLogoutError(null);

    try {
      if (supabase) {
        const { error } = await supabase.auth.signOut();
        if (error) {
          setLogoutError(error.message);
          setIsLoggingOut(false);
          return;
        }
      }

      await contextSignOut();
      setShowLogoutConfirm(false);
      setDropdownOpen(false);
      navigate('/login', { replace: true });
    } catch (err: any) {
      const msg = err instanceof Error ? err.message : 'An error occurred while logging out.';
      setLogoutError(msg);
      setIsLoggingOut(false);
    }
  };

  const getNotificationIcon = (type: AppNotification['type']) => {
    switch (type) {
      case 'survey':
        return (
          <div className="flex h-8 w-8 items-center justify-center rounded-xl bg-brand-royal-50 text-brand-royal-600 shrink-0">
            <ClipboardList className="h-4 w-4" />
          </div>
        );
      case 'reward':
        return (
          <div className="flex h-8 w-8 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 shrink-0">
            <Gift className="h-4 w-4" />
          </div>
        );
      case 'warning':
        return (
          <div className="flex h-8 w-8 items-center justify-center rounded-xl bg-amber-50 text-amber-600 shrink-0">
            <AlertCircle className="h-4 w-4" />
          </div>
        );
      case 'success':
        return (
          <div className="flex h-8 w-8 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 shrink-0">
            <CheckCircle2 className="h-4 w-4" />
          </div>
        );
      default:
        return (
          <div className="flex h-8 w-8 items-center justify-center rounded-xl bg-brand-violet-50 text-brand-violet-600 shrink-0">
            <Sparkles className="h-4 w-4" />
          </div>
        );
    }
  };

  return (
    <>
      <header className="sticky top-0 z-30 flex h-20 w-full items-center justify-between border-b border-slate-200/80 bg-white/95 px-4 sm:px-8 backdrop-blur-md">
        {/* Left: Mobile Toggle & Page Title */}
        <div className="flex items-center gap-3">
          <button
            onClick={onOpenMobileSidebar}
            className="rounded-lg p-2 text-slate-600 hover:bg-slate-100 lg:hidden focus:outline-none focus:ring-2 focus:ring-brand-royal-500"
            aria-label="Open navigation sidebar"
          >
            <Menu className="h-6 w-6" />
          </button>

          <div>
            <h1 className="text-xl sm:text-2xl font-bold tracking-tight text-slate-900">
              {title}
            </h1>
            {subtitle && (
              <p className="hidden sm:block text-xs text-slate-500 font-medium">
                {subtitle}
              </p>
            )}
          </div>
        </div>

        {/* Right: Search, Balance Pill, Notifications & Account Dropdown */}
        <div className="flex items-center gap-2 sm:gap-4">
          {/* Search Input on tablets/desktop */}
          <div className="relative hidden md:block w-52 lg:w-64">
            <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
            <input
              type="text"
              placeholder={isAdmin ? 'Search surveys or users...' : 'Search available surveys...'}
              className="w-full rounded-xl border border-slate-200 bg-slate-50/70 py-2 pl-9 pr-4 text-xs font-medium text-slate-800 placeholder-slate-400 focus:border-brand-royal-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-brand-royal-500/20 transition-all"
            />
          </div>

          {/* Real Member Points or Admin Status Pill */}
          {!isAdmin ? (
            <Link to="/member/rewards">
              <div className="flex items-center gap-2 rounded-xl border border-brand-royal-100 bg-brand-royal-50/80 px-3 py-1.5 text-xs font-bold text-brand-royal-700 shadow-2xs hover:bg-brand-royal-100/70 transition-colors cursor-pointer">
                <Sparkles className="h-3.5 w-3.5 text-brand-royal-600" />
                <span className="hidden sm:inline">Balance:</span>
                <span className="font-extrabold text-brand-royal-900">
                  {balancePoints.toLocaleString()} Pts
                </span>
              </div>
            </Link>
          ) : (
            <div className="flex items-center gap-1.5 rounded-xl border border-brand-navy-700 bg-brand-navy-950 px-3 py-1.5 text-xs font-bold text-brand-royal-300">
              <Shield className="h-3.5 w-3.5 text-brand-royal-400" />
              <span className="hidden sm:inline">Role:</span>
              <span>Administrator</span>
            </div>
          )}

          {/* =========================================================================
              FUNCTIONAL NOTIFICATION BELL WITH FLOATING PANEL
              ========================================================================= */}
          <div className="relative" ref={notifDropdownRef}>
            <button
              onClick={() => {
                setNotifDropdownOpen(!notifDropdownOpen);
                if (dropdownOpen) setDropdownOpen(false);
              }}
              className={`relative rounded-xl border p-2 text-slate-600 transition-all focus:outline-none focus:ring-2 focus:ring-brand-royal-500 ${
                notifDropdownOpen
                  ? 'border-brand-royal-400 bg-brand-royal-50/60 text-brand-royal-600 ring-2 ring-brand-royal-400/20'
                  : 'border-slate-200 hover:bg-slate-50 hover:text-slate-900'
              }`}
              aria-label="Notifications"
              aria-expanded={notifDropdownOpen}
              aria-haspopup="true"
            >
              <Bell className="h-4.5 w-4.5" />
              {/* Actual unread count indicator from database only; hidden if 0 */}
              {unreadCount > 0 && (
                <span className="absolute -top-1 -right-1 flex h-4 min-w-4 items-center justify-center rounded-full bg-brand-royal-600 px-1 text-[10px] font-extrabold text-white ring-2 ring-white animate-fadeIn shadow-xs">
                  {unreadCount > 9 ? '9+' : unreadCount}
                </span>
              )}
            </button>

            {/* Notification Dropdown Panel */}
            {notifDropdownOpen && (
              <div
                className="absolute right-0 sm:-right-24 md:right-0 mt-2.5 w-[310px] sm:w-[380px] rounded-2xl border border-slate-200/90 bg-white shadow-2xl z-50 overflow-hidden animate-fadeIn space-y-0"
                style={{
                  boxShadow: '0 20px 40px -15px rgba(11, 21, 54, 0.16), 0 0 1px 1px rgba(11, 21, 54, 0.05)',
                }}
              >
                {/* Header: Title & Actions */}
                <div className="flex items-center justify-between border-b border-slate-100 bg-slate-50/80 px-4 py-3">
                  <div className="flex items-center gap-2">
                    <h3 className="text-sm font-bold text-slate-900">Notifications</h3>
                    {unreadCount > 0 && (
                      <span className="rounded-full bg-brand-royal-100 px-2 py-0.5 text-[11px] font-extrabold text-brand-royal-700">
                        {unreadCount} new
                      </span>
                    )}
                  </div>

                  <div className="flex items-center gap-2">
                    {unreadCount > 0 && (
                      <button
                        onClick={handleMarkAllAsRead}
                        className="text-[11px] font-bold text-brand-royal-600 hover:text-brand-royal-700 flex items-center gap-1 transition-colors"
                        title="Mark all as read"
                      >
                        <CheckCheck className="h-3.5 w-3.5" />
                        <span>Mark read</span>
                      </button>
                    )}

                    {notifications.length > 0 && (
                      <button
                        onClick={handleClearAllNotifications}
                        className="text-[11px] font-semibold text-slate-400 hover:text-rose-600 transition-colors"
                        title="Clear all notifications"
                      >
                        Clear
                      </button>
                    )}
                  </div>
                </div>

                {/* Notifications List or Empty State */}
                <div className="max-h-80 sm:max-h-96 overflow-y-auto divide-y divide-slate-100">
                  {loadingNotifications ? (
                    <div className="p-8 text-center text-xs text-slate-400">
                      <Loader2 className="h-5 w-5 animate-spin mx-auto mb-2 text-brand-royal-600" />
                      <span>Checking for notifications...</span>
                    </div>
                  ) : notifications.length === 0 ? (
                    /* Attractive Empty State */
                    <div className="p-8 text-center space-y-3">
                      <div className="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-brand-royal-50 text-brand-royal-600 border border-brand-royal-100">
                        <CheckCheck className="h-7 w-7 text-brand-royal-600" />
                      </div>
                      <div className="space-y-1">
                        <h4 className="text-sm font-bold text-slate-900">
                          You&apos;re all caught up!
                        </h4>
                        <p className="text-xs text-slate-500 leading-relaxed max-w-[240px] mx-auto">
                          No new notifications right now. When you qualify for research cohorts or rewards are processed, updates will appear here.
                        </p>
                      </div>
                    </div>
                  ) : (
                    notifications.map((notif) => (
                      <div
                        key={notif.id}
                        onClick={() => handleNotificationClick(notif)}
                        className={`group relative flex items-start gap-3 p-3.5 transition-colors cursor-pointer hover:bg-slate-50 ${
                          !notif.isRead ? 'bg-brand-royal-50/30' : 'bg-white'
                        }`}
                      >
                        {/* Type Icon */}
                        {getNotificationIcon(notif.type)}

                        {/* Content Area */}
                        <div className="flex-1 min-w-0 pr-6">
                          <div className="flex items-center justify-between gap-1 mb-0.5">
                            <span className={`text-xs truncate ${
                              !notif.isRead ? 'font-bold text-slate-900' : 'font-semibold text-slate-700'
                            }`}>
                              {notif.title}
                            </span>
                            <span className="text-[10px] text-slate-400 shrink-0">
                              {formatNotificationTime(notif.createdAt)}
                            </span>
                          </div>

                          <p className="text-xs text-slate-500 line-clamp-2 leading-relaxed">
                            {notif.message}
                          </p>

                          {notif.link && (
                            <span className="inline-flex items-center gap-1 text-[11px] font-semibold text-brand-royal-600 mt-1.5 group-hover:underline">
                              <span>View Details</span>
                              <ExternalLink className="h-3 w-3" />
                            </span>
                          )}
                        </div>

                        {/* Delete / Dismiss Single Notification */}
                        <button
                          onClick={(e) => handleDeleteNotification(e, notif.id)}
                          className="absolute top-3 right-3 rounded-md p-1 text-slate-300 opacity-0 group-hover:opacity-100 hover:bg-slate-100 hover:text-rose-600 transition-all"
                          title="Remove notification"
                        >
                          <X className="h-3.5 w-3.5" />
                        </button>

                        {/* Unread Indicator Dot */}
                        {!notif.isRead && (
                          <span className="absolute bottom-3 right-3 h-2 w-2 rounded-full bg-brand-royal-600" />
                        )}
                      </div>
                    ))
                  )}
                </div>

                {/* Footer */}
                {notifications.length > 0 && (
                  <div className="border-t border-slate-100 bg-slate-50/50 p-2.5 text-center text-[11px] text-slate-400 font-medium">
                    <span>Updates for your authenticated research panel</span>
                  </div>
                )}
              </div>
            )}
          </div>

          {/* Vertical Separator */}
          <div className="hidden sm:block h-6 w-[1px] bg-slate-200" />

          {/* =========================================================================
              ACCOUNT DROPDOWN TRIGGER BUTTON & MENU
              ========================================================================= */}
          <div className="relative" ref={dropdownRef}>
            <button
              onClick={() => {
                setDropdownOpen(!dropdownOpen);
                if (notifDropdownOpen) setNotifDropdownOpen(false);
              }}
              className={`flex items-center gap-2.5 rounded-2xl border p-1.5 sm:px-3 sm:py-1.5 transition-all focus:outline-none focus:ring-2 focus:ring-brand-royal-500 ${
                dropdownOpen
                  ? 'border-brand-royal-400 bg-brand-royal-50/60 shadow-xs ring-2 ring-brand-royal-400/20'
                  : 'border-slate-200/90 bg-white hover:border-slate-300 hover:bg-slate-50 shadow-2xs'
              }`}
              aria-haspopup="true"
              aria-expanded={dropdownOpen}
              aria-label="User account menu"
            >
              {/* Circular Avatar Badge with Active Dot */}
              <div className="relative flex h-8 w-8 sm:h-9 sm:w-9 items-center justify-center rounded-xl bg-gradient-to-tr from-brand-navy-950 via-brand-navy-900 to-brand-royal-600 font-bold text-white shadow-xs text-xs shrink-0 select-none">
                {initials}
                <span className={`absolute -bottom-0.5 -right-0.5 h-2.5 w-2.5 rounded-full border-2 border-white ${
                  isEmailVerified ? 'bg-emerald-500' : 'bg-amber-400'
                }`} />
              </div>

              {/* User Name & Chevron (Desktop) */}
              <div className="hidden sm:flex flex-col text-left">
                <span className="text-xs font-bold text-slate-900 leading-tight truncate max-w-[110px]">
                  {fullName}
                </span>
                <span className="text-[10px] text-slate-400 font-medium">
                  {isAdmin ? 'Administrator' : 'Panelist'}
                </span>
              </div>

              <ChevronDown className={`h-3.5 w-3.5 text-slate-400 transition-transform duration-200 ${
                dropdownOpen ? 'rotate-180 text-brand-royal-600' : ''
              }`} />
            </button>

            {/* Premium Floating Account Dropdown Menu */}
            {dropdownOpen && (
              <div
                className="absolute right-0 mt-2.5 w-72 sm:w-80 rounded-2xl border border-slate-200/90 bg-white p-2 shadow-2xl z-50 animate-fadeIn space-y-1.5 divide-y divide-slate-100"
                style={{
                  boxShadow: '0 20px 40px -15px rgba(11, 21, 54, 0.16), 0 0 1px 1px rgba(11, 21, 54, 0.05)',
                }}
              >
                {/* 1. Header Profile Summary */}
                <div className="px-3 py-3 space-y-2">
                  <div className="flex items-center gap-3">
                    <div className="relative flex h-11 w-11 items-center justify-center rounded-xl bg-gradient-to-tr from-brand-navy-950 to-brand-royal-600 font-bold text-white shadow-sm text-sm shrink-0">
                      {initials}
                    </div>
                    <div className="overflow-hidden">
                      <div className="text-sm font-bold text-slate-900 truncate">
                        {fullName}
                      </div>
                      <div className="text-xs text-slate-500 truncate" title={email}>
                        {email}
                      </div>
                    </div>
                  </div>

                  {/* Verification Status Pill */}
                  <div className="flex items-center justify-between pt-1">
                    <div className="flex items-center gap-1.5 text-[11px] font-semibold">
                      {isEmailVerified ? (
                        <span className="inline-flex items-center gap-1 text-emerald-700 bg-emerald-50 border border-emerald-200 px-2 py-0.5 rounded-full">
                          <CheckCircle2 className="h-3 w-3 text-emerald-600" />
                          <span>Verified Account</span>
                        </span>
                      ) : (
                        <span className="inline-flex items-center gap-1 text-amber-700 bg-amber-50 border border-amber-200 px-2 py-0.5 rounded-full">
                          <AlertCircle className="h-3 w-3 text-amber-600" />
                          <span>Pending Verification</span>
                        </span>
                      )}
                    </div>
                    {!isAdmin && (
                      <span className="text-[11px] font-bold text-brand-royal-600">
                        {balancePoints.toLocaleString()} Pts
                      </span>
                    )}
                  </div>
                </div>

                {/* 2. Menu Options */}
                <div className="pt-1.5 space-y-0.5">
                  {/* Option 1: My Profile */}
                  <Link
                    to="/member/settings"
                    onClick={() => setDropdownOpen(false)}
                    className="flex items-center gap-3 rounded-xl px-3 py-2.5 text-xs font-semibold text-slate-700 hover:bg-slate-50 hover:text-brand-royal-600 transition-colors group"
                  >
                    <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-slate-100 text-slate-600 group-hover:bg-brand-royal-50 group-hover:text-brand-royal-600 transition-colors shrink-0">
                      <User className="h-4 w-4" />
                    </div>
                    <div className="flex-1 text-left">
                      <div className="font-bold text-slate-900 group-hover:text-brand-royal-600">
                        My Profile
                      </div>
                      <div className="text-[11px] text-slate-400 font-normal">
                        Personal & demographic details
                      </div>
                    </div>
                  </Link>

                  {/* Option 2: Change Password */}
                  <button
                    onClick={() => {
                      setDropdownOpen(false);
                      setShowPasswordModal(true);
                    }}
                    className="w-full flex items-center gap-3 rounded-xl px-3 py-2.5 text-xs font-semibold text-slate-700 hover:bg-slate-50 hover:text-brand-royal-600 transition-colors group text-left"
                  >
                    <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-slate-100 text-slate-600 group-hover:bg-brand-violet-50 group-hover:text-brand-violet-600 transition-colors shrink-0">
                      <KeyRound className="h-4 w-4" />
                    </div>
                    <div className="flex-1">
                      <div className="font-bold text-slate-900 group-hover:text-brand-violet-600">
                        Change Password
                      </div>
                      <div className="text-[11px] text-slate-400 font-normal">
                        Update account credentials
                      </div>
                    </div>
                  </button>

                  {/* Option 3: Account Settings */}
                  <Link
                    to="/member/settings#notifications"
                    onClick={() => setDropdownOpen(false)}
                    className="flex items-center gap-3 rounded-xl px-3 py-2.5 text-xs font-semibold text-slate-700 hover:bg-slate-50 hover:text-brand-royal-600 transition-colors group"
                  >
                    <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-slate-100 text-slate-600 group-hover:bg-slate-200 group-hover:text-slate-900 transition-colors shrink-0">
                      <Settings className="h-4 w-4" />
                    </div>
                    <div className="flex-1 text-left">
                      <div className="font-bold text-slate-900 group-hover:text-brand-royal-600">
                        Account Settings
                      </div>
                      <div className="text-[11px] text-slate-400 font-normal">
                        Preferences & notifications
                      </div>
                    </div>
                  </Link>
                </div>

                {/* 3. Sign Out Option */}
                <div className="pt-1.5">
                  <button
                    onClick={() => {
                      setDropdownOpen(false);
                      setShowLogoutConfirm(true);
                    }}
                    className="w-full flex items-center gap-3 rounded-xl px-3 py-2.5 text-xs font-semibold text-rose-600 hover:bg-rose-50 hover:text-rose-700 transition-colors group text-left"
                  >
                    <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-rose-50 text-rose-600 group-hover:bg-rose-100 transition-colors shrink-0">
                      <LogOut className="h-4 w-4" />
                    </div>
                    <div className="flex-1">
                      <div className="font-bold text-rose-600 group-hover:text-rose-700">
                        Sign Out
                      </div>
                      <div className="text-[11px] text-rose-400 font-normal">
                        End your authenticated session
                      </div>
                    </div>
                  </button>
                </div>
              </div>
            )}
          </div>
        </div>
      </header>

      {/* =========================================================================
          CHANGE PASSWORD MODAL
          ========================================================================= */}
      <ChangePasswordModal
        isOpen={showPasswordModal}
        onClose={() => setShowPasswordModal(false)}
      />

      {/* =========================================================================
          LOGOUT CONFIRMATION DIALOG MODAL
          ========================================================================= */}
      {showLogoutConfirm && (
        <div className="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm animate-fadeIn">
          <div
            className="fixed inset-0"
            onClick={() => !isLoggingOut && setShowLogoutConfirm(false)}
          />
          <div className="relative z-10 w-full max-w-sm rounded-2xl border border-slate-200 bg-white p-6 shadow-2xl space-y-4">
            <div className="flex items-center gap-3">
              <div className="flex h-11 w-11 items-center justify-center rounded-2xl bg-rose-100 text-rose-600 shrink-0 shadow-sm">
                <LogOut className="h-5 w-5" />
              </div>
              <div>
                <h3 className="text-base font-bold text-slate-900">Confirm Sign Out</h3>
                <p className="text-xs text-slate-500">Are you sure you want to log out?</p>
              </div>
            </div>

            {logoutError && (
              <div className="flex items-start gap-2 rounded-xl border border-rose-200 bg-rose-50 p-2.5 text-xs font-semibold text-rose-700">
                <AlertCircle className="h-4 w-4 shrink-0 mt-0.5 text-rose-600" />
                <span>{logoutError}</span>
              </div>
            )}

            <p className="text-xs text-slate-600 leading-relaxed">
              You will be signed out of your Veritolytics panel account and redirected to the login screen.
            </p>

            <div className="flex items-center justify-end gap-2.5 pt-2 border-t border-slate-100">
              <Button
                type="button"
                variant="outline"
                size="sm"
                disabled={isLoggingOut}
                onClick={() => setShowLogoutConfirm(false)}
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
                    <span>Signing out...</span>
                  </>
                ) : (
                  <>
                    <LogOut className="h-3.5 w-3.5" />
                    <span>Sign Out</span>
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
