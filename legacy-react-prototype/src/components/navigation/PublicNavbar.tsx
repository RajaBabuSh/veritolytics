import React, { useState } from 'react';
import { Link, useLocation, useNavigate } from 'react-router-dom';
import { Logo } from '@/components/brand/Logo';
import { Button } from '@/components/ui/Button';
import { Menu, X, ArrowRight, Sparkles, LogOut, User, Bell } from 'lucide-react';
import { useAuth } from '@/context/AuthContext';

export const PublicNavbar: React.FC = () => {
  const [mobileMenuOpen, setMobileMenuOpen] = useState(false);
  const location = useLocation();
  const navigate = useNavigate();
  const { user, signOut } = useAuth();

  const handleNavClick = (target: string, isHash: boolean = false) => {
    setMobileMenuOpen(false);
    if (isHash) {
      if (location.pathname === '/') {
        const el = document.querySelector(target);
        if (el) {
          el.scrollIntoView({ behavior: 'smooth' });
        }
      } else {
        navigate(`/${target}`);
      }
    } else {
      if (location.pathname === target) {
        window.scrollTo({ top: 0, behavior: 'smooth' });
      } else {
        navigate(target);
      }
    }
  };

  return (
    <div className="sticky top-0 z-50 w-full transition-all">
      {/* 1. Announcement Bar */}
      <aside aria-label="Platform Announcement" className="bg-brand-navy-950 text-white text-xs border-b border-brand-navy-800/80 px-4 py-2 transition-colors">
        <div className="container mx-auto flex items-center justify-between">
          <div className="flex items-center gap-2 overflow-hidden text-ellipsis whitespace-nowrap">
            <span className="flex h-2 w-2 rounded-full bg-emerald-400 animate-pulse flex-shrink-0" />
            <span className="inline-flex items-center gap-1.5 font-medium text-slate-300">
              <Sparkles className="h-3.5 w-3.5 text-brand-royal-400 flex-shrink-0" />
              <strong className="text-white font-semibold">Welcome to Veritolytics:</strong> Genuine consumer and professional research exchange. 100% Free Panelist Membership.
            </span>
          </div>
          <div className="hidden md:flex items-center gap-4 text-[11px] text-slate-400 font-medium">
            <span className="flex items-center gap-1 text-slate-300">
              <span className="h-1.5 w-1.5 rounded-full bg-brand-violet-400" />
              Fixed 100 Pts = $1.00 USD
            </span>
            <span className="text-slate-600">|</span>
            <span className="text-slate-300">Privacy-First Architecture</span>
          </div>
        </div>
      </aside>

      {/* 2. Main Navigation Bar */}
      <header className="w-full border-b border-slate-200/80 bg-white/95 backdrop-blur-md shadow-xs">
        <div className="container mx-auto flex h-20 items-center justify-between">
          {/* Logo */}
          <Logo variant="dark" size="md" />

          {/* Desktop Navigation Links */}
          <nav className="hidden lg:flex items-center space-x-1 xl:space-x-2">
            <button
              onClick={() => handleNavClick('/')}
              className={`rounded-lg px-3 py-2 text-sm font-semibold transition-colors ${
                location.pathname === '/' && !location.hash
                  ? 'text-brand-royal-600 bg-brand-royal-50/60'
                  : 'text-slate-600 hover:text-brand-navy-950 hover:bg-slate-50'
              }`}
            >
              Home
            </button>
            <button
              onClick={() => handleNavClick('#how-it-works', true)}
              className="rounded-lg px-3 py-2 text-sm font-semibold text-slate-600 hover:text-brand-navy-950 hover:bg-slate-50 transition-colors"
            >
              How It Works
            </button>
            <button
              onClick={() => handleNavClick('#categories', true)}
              className="rounded-lg px-3 py-2 text-sm font-semibold text-slate-600 hover:text-brand-navy-950 hover:bg-slate-50 transition-colors"
            >
              Research Categories
            </button>
            <button
              onClick={() => handleNavClick('/rewards')}
              className={`rounded-lg px-3 py-2 text-sm font-semibold transition-colors ${
                location.pathname === '/rewards'
                  ? 'text-brand-royal-600 bg-brand-royal-50/60'
                  : 'text-slate-600 hover:text-brand-navy-950 hover:bg-slate-50'
              }`}
            >
              Rewards
            </button>
            <button
              onClick={() => handleNavClick('#faq', true)}
              className="rounded-lg px-3 py-2 text-sm font-semibold text-slate-600 hover:text-brand-navy-950 hover:bg-slate-50 transition-colors"
            >
              FAQ
            </button>
          </nav>

          {/* Action Buttons */}
          <div className="hidden sm:flex items-center space-x-3">
            {user ? (
              <div className="flex items-center space-x-3">
                <Link to="/member">
                  <Button variant="royal" size="sm" className="gap-2 font-bold shadow-sm">
                    <User className="h-4 w-4" />
                    <span>Member Dashboard</span>
                  </Button>
                </Link>
                <Button
                  variant="ghost"
                  size="sm"
                  onClick={() => signOut()}
                  className="font-semibold text-slate-500 hover:text-rose-600 hover:bg-rose-50"
                  title="Sign Out"
                >
                  <LogOut className="h-4 w-4" />
                </Button>
              </div>
            ) : (
              <>
                <Link to="/login">
                  <Button variant="ghost" size="sm" className="font-bold text-slate-700 hover:text-brand-royal-600">
                    Login
                  </Button>
                </Link>
                <Link to="/signup">
                  <Button variant="royal" size="sm" className="gap-2 shadow-glow-royal font-bold px-5">
                    <span>Join Now</span>
                    <ArrowRight className="h-4 w-4" />
                  </Button>
                </Link>
              </>
            )}
          </div>

          {/* Mobile Hamburger Toggle */}
          <button
            onClick={() => setMobileMenuOpen(!mobileMenuOpen)}
            className="inline-flex lg:hidden items-center justify-center rounded-xl p-2.5 text-slate-600 hover:bg-slate-100 focus:outline-none focus:ring-2 focus:ring-brand-royal-500"
            aria-label="Toggle navigation"
          >
            {mobileMenuOpen ? <X className="h-6 w-6" /> : <Menu className="h-6 w-6" />}
          </button>
        </div>

        {/* Mobile Menu Dropdown */}
        {mobileMenuOpen && (
          <div className="border-t border-slate-200 bg-white px-6 py-6 lg:hidden space-y-4 shadow-xl animate-fadeIn">
            <nav className="flex flex-col space-y-1">
              <button
                onClick={() => handleNavClick('/')}
                className="text-left rounded-lg px-3.5 py-2.5 text-base font-semibold text-slate-700 hover:bg-slate-50 hover:text-brand-royal-600 transition-colors"
              >
                Home
              </button>
              <button
                onClick={() => handleNavClick('#how-it-works', true)}
                className="text-left rounded-lg px-3.5 py-2.5 text-base font-semibold text-slate-700 hover:bg-slate-50 hover:text-brand-royal-600 transition-colors"
              >
                How It Works
              </button>
              <button
                onClick={() => handleNavClick('#categories', true)}
                className="text-left rounded-lg px-3.5 py-2.5 text-base font-semibold text-slate-700 hover:bg-slate-50 hover:text-brand-royal-600 transition-colors"
              >
                Research Categories
              </button>
              <button
                onClick={() => handleNavClick('/rewards')}
                className="text-left rounded-lg px-3.5 py-2.5 text-base font-semibold text-slate-700 hover:bg-slate-50 hover:text-brand-royal-600 transition-colors"
              >
                Rewards
              </button>
              <button
                onClick={() => handleNavClick('#faq', true)}
                className="text-left rounded-lg px-3.5 py-2.5 text-base font-semibold text-slate-700 hover:bg-slate-50 hover:text-brand-royal-600 transition-colors"
              >
                FAQ
              </button>
            </nav>

            <div className="pt-4 border-t border-slate-100 flex flex-col gap-2.5">
              {user ? (
                <>
                  <Link to="/member" onClick={() => setMobileMenuOpen(false)}>
                    <Button variant="royal" className="w-full justify-center font-bold">
                      <User className="h-4 w-4 mr-2" />
                      Member Dashboard
                    </Button>
                  </Link>
                  <Button
                    variant="outline"
                    onClick={() => {
                      signOut();
                      setMobileMenuOpen(false);
                    }}
                    className="w-full justify-center text-rose-600 hover:bg-rose-50"
                  >
                    <LogOut className="h-4 w-4 mr-2" />
                    Sign Out
                  </Button>
                </>
              ) : (
                <>
                  <Link to="/login" onClick={() => setMobileMenuOpen(false)}>
                    <Button variant="outline" className="w-full justify-center font-bold text-slate-800">
                      Login
                    </Button>
                  </Link>
                  <Link to="/signup" onClick={() => setMobileMenuOpen(false)}>
                    <Button variant="royal" className="w-full justify-center gap-2 font-bold shadow-md">
                      <span>Join Now</span>
                      <ArrowRight className="h-4 w-4" />
                    </Button>
                  </Link>
                </>
              )}
            </div>
          </div>
        )}
      </header>
    </div>
  );
};
