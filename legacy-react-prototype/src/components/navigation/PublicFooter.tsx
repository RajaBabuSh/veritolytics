import React from 'react';
import { Link, useNavigate, useLocation } from 'react-router-dom';
import { Logo } from '@/components/brand/Logo';
import { ShieldCheck, Mail, ArrowUpRight, HelpCircle, FileText, Lock, Globe2 } from 'lucide-react';

export const PublicFooter: React.FC = () => {
  const navigate = useNavigate();
  const location = useLocation();

  const handleHashLink = (target: string) => {
    if (location.pathname === '/') {
      const el = document.querySelector(target);
      if (el) {
        el.scrollIntoView({ behavior: 'smooth' });
      }
    } else {
      navigate(`/${target}`);
    }
  };

  return (
    <footer className="border-t border-brand-navy-900 bg-brand-navy-950 text-slate-300">
      <div className="container mx-auto px-4 py-16">
        <div className="grid grid-cols-1 gap-10 md:grid-cols-2 lg:grid-cols-5">
          {/* Brand & Mission */}
          <div className="lg:col-span-2 space-y-4">
            <Logo variant="light" size="lg" />
            <p className="text-sm text-slate-400 leading-relaxed max-w-sm">
              VERITOLYTICS is a premier global consumer and professional research platform. 
              We connect verified panel members with institutional and enterprise research cohorts, 
              delivering actionable insights through transparent, respectful, and direct digital reward exchanges.
            </p>
            <div className="flex flex-wrap items-center gap-3 pt-2">
              <div className="inline-flex items-center gap-1.5 text-xs text-brand-royal-300 bg-brand-royal-950/70 border border-brand-royal-800/50 px-3 py-1.5 rounded-full">
                <Globe2 className="h-3.5 w-3.5 text-brand-royal-400" />
                <span>Global Research Panel</span>
              </div>
              <div className="inline-flex items-center gap-1.5 text-xs text-brand-violet-300 bg-brand-violet-950/70 border border-brand-violet-800/50 px-3 py-1.5 rounded-full">
                <ShieldCheck className="h-3.5 w-3.5 text-brand-violet-400" />
                <span>Encrypted Respondent Data</span>
              </div>
            </div>
          </div>

          {/* Platform Navigation */}
          <div className="space-y-3">
            <h4 className="text-xs font-bold uppercase tracking-wider text-white">Platform</h4>
            <ul className="space-y-2.5 text-sm">
              <li>
                <button
                  onClick={() => handleHashLink('#how-it-works')}
                  className="text-slate-400 hover:text-white transition-colors text-left"
                >
                  How It Works
                </button>
              </li>
              <li>
                <button
                  onClick={() => handleHashLink('#categories')}
                  className="text-slate-400 hover:text-white transition-colors text-left"
                >
                  Research Categories
                </button>
              </li>
              <li>
                <Link to="/rewards" className="text-slate-400 hover:text-white transition-colors">
                  Rewards Catalog
                </Link>
              </li>
              <li>
                <button
                  onClick={() => handleHashLink('#faq')}
                  className="text-slate-400 hover:text-white transition-colors text-left"
                >
                  Frequently Asked Questions
                </button>
              </li>
              <li>
                <Link to="/signup" className="text-brand-royal-400 hover:text-brand-royal-300 font-semibold transition-colors flex items-center gap-1">
                  <span>Join Panel for Free</span>
                  <ArrowUpRight className="h-3.5 w-3.5" />
                </Link>
              </li>
            </ul>
          </div>

          {/* Portals & Support */}
          <div className="space-y-3">
            <h4 className="text-xs font-bold uppercase tracking-wider text-white">Help & Portals</h4>
            <ul className="space-y-2.5 text-sm">
              <li>
                <Link to="/login" className="text-slate-400 hover:text-white transition-colors">
                  Member Login
                </Link>
              </li>
              <li>
                <Link to="/member" className="text-slate-400 hover:text-white transition-colors">
                  Member Dashboard
                </Link>
              </li>
              <li>
                <Link to="/admin" className="text-slate-400 hover:text-white transition-colors">
                  Admin Console
                </Link>
              </li>
              <li>
                <button
                  onClick={() => handleHashLink('#faq')}
                  className="text-slate-400 hover:text-white transition-colors text-left flex items-center gap-1.5"
                >
                  <HelpCircle className="h-3.5 w-3.5 text-brand-royal-400" />
                  <span>Help & Support Center</span>
                </button>
              </li>
              <li>
                <a
                  href="mailto:support@veritolytics.com"
                  className="text-slate-400 hover:text-white transition-colors flex items-center gap-1.5"
                >
                  <Mail className="h-3.5 w-3.5 text-brand-violet-400" />
                  <span>support@veritolytics.com</span>
                </a>
              </li>
            </ul>
          </div>

          {/* Trust & Legal */}
          <div className="space-y-3">
            <h4 className="text-xs font-bold uppercase tracking-wider text-white">Legal & Compliance</h4>
            <ul className="space-y-2.5 text-sm text-slate-400">
              <li>
                <span className="hover:text-white cursor-pointer transition-colors flex items-center gap-1.5">
                  <Lock className="h-3.5 w-3.5 text-slate-500" />
                  <span>Privacy Policy</span>
                </span>
              </li>
              <li>
                <span className="hover:text-white cursor-pointer transition-colors flex items-center gap-1.5">
                  <FileText className="h-3.5 w-3.5 text-slate-500" />
                  <span>Terms & Conditions</span>
                </span>
              </li>
              <li>
                <span className="hover:text-white cursor-pointer transition-colors">
                  Cookie Policy
                </span>
              </li>
              <li>
                <span className="hover:text-white cursor-pointer transition-colors">
                  Participant Ethics Code
                </span>
              </li>
            </ul>
            <div className="rounded-xl border border-brand-navy-800 bg-brand-navy-900/60 p-3 text-xs text-slate-400 mt-3">
              <span className="font-semibold text-white">Fair Valuation Rule:</span> Fixed 100 points = $1.00 USD. Minimum cashout starting at 1,000 points.
            </div>
          </div>
        </div>

        {/* Bottom Bar */}
        <div className="mt-14 border-t border-brand-navy-900 pt-8 flex flex-col sm:flex-row items-center justify-between text-xs text-slate-500 gap-4">
          <p>© {new Date().getFullYear()} VERITOLYTICS. All rights reserved. Your Voice. Your Insights. Your Impact.</p>
          <div className="flex items-center space-x-6 text-slate-400">
            <span className="hover:text-slate-200 cursor-pointer">Privacy</span>
            <span>•</span>
            <span className="hover:text-slate-200 cursor-pointer">Terms</span>
            <span>•</span>
            <span className="hover:text-slate-200 cursor-pointer">Cookies</span>
            <span>•</span>
            <span className="hover:text-slate-200 cursor-pointer">Contact</span>
          </div>
        </div>
      </div>
    </footer>
  );
};
