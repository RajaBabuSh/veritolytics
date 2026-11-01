import React, { useState, useEffect } from 'react';
import { useOutletContext, Link } from 'react-router-dom';
import { DashboardHeader } from '@/components/navigation/DashboardHeader';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/Card';
import { Button } from '@/components/ui/Button';
import { Badge } from '@/components/ui/Badge';
import {
  Coins,
  TrendingUp,
  CheckCircle2,
  Clock,
  ArrowRight,
  ShieldCheck,
  UserCheck,
  Inbox,
  FileQuestion,
  Sparkles,
  Wallet,
} from 'lucide-react';
import { useAuth } from '@/context/AuthContext';
import { dataService } from '@/services/dataService';
import { UserProfile, Survey, Transaction } from '@/types';

export const MemberDashboardPage: React.FC = () => {
  const { openMobileSidebar } = useOutletContext<{ openMobileSidebar: () => void }>();
  const { user } = useAuth();

  const [profile, setProfile] = useState<UserProfile | null>(null);
  const [surveys, setSurveys] = useState<Survey[]>([]);
  const [transactions, setTransactions] = useState<Transaction[]>([]);
  const [loading, setLoading] = useState<boolean>(true);

  useEffect(() => {
    let isMounted = true;
    async function loadData() {
      if (!user) return;
      try {
        const p = await dataService.getUserProfile(user.id, user.user_metadata, user.email);
        const s = await dataService.getSurveys();
        const t = await dataService.getTransactions(user.id);
        if (isMounted) {
          setProfile(p);
          setSurveys(s);
          setTransactions(t);
          setLoading(false);
        }
      } catch (err) {
        console.error('Error loading member dashboard data:', err);
        if (isMounted) setLoading(false);
      }
    }
    loadData();
    return () => {
      isMounted = false;
    };
  }, [user]);

  const firstName = profile?.firstName || user?.user_metadata?.first_name || '';
  const greetingName = firstName ? `${firstName}` : 'Panelist';
  const balancePoints = profile?.balancePoints || 0;
  const lifetimeUsd = (profile?.lifetimeEarningsUsd || 0).toFixed(2);
  const completedCount = profile?.completedSurveysCount || 0;
  const completionPct = profile?.profileCompletionPct || 40;

  return (
    <div className="flex-1 pb-16">
      <DashboardHeader
        title="Member Overview"
        subtitle={`Welcome back, ${greetingName}! Here is your research panel status.`}
        onOpenMobileSidebar={openMobileSidebar}
        balancePoints={balancePoints}
      />

      <div className="p-4 sm:p-8 max-w-7xl mx-auto space-y-8">
        {/* Onboarding Welcome & Profile Completion Banner */}
        <div className="rounded-2xl border border-brand-royal-100 bg-gradient-to-r from-brand-navy-950 via-brand-navy-900 to-brand-royal-950 p-6 sm:p-8 text-white shadow-lg relative overflow-hidden">
          <div className="relative z-10 flex flex-col lg:flex-row lg:items-center justify-between gap-6">
            <div className="space-y-2 max-w-2xl">
              <div className="inline-flex items-center gap-1.5 rounded-full bg-brand-royal-500/20 px-3 py-1 text-xs font-semibold text-brand-royal-300 border border-brand-royal-500/30">
                <Sparkles className="h-3.5 w-3.5 text-brand-royal-400" />
                <span>Launch State • Verified Panel Account</span>
              </div>
              <h2 className="text-2xl sm:text-3xl font-extrabold tracking-tight">
                Welcome to VERITOLYTICS, {greetingName}!
              </h2>
              <p className="text-xs sm:text-sm text-slate-300 leading-relaxed">
                Your account is set up with a fresh balance. As genuine research opportunities matching your demographic background are launched, they will appear directly in your dashboard.
              </p>
            </div>

            {/* Profile Completion Dial / Widget */}
            <div className="rounded-xl border border-white/10 bg-white/5 p-4 sm:p-5 backdrop-blur-md shrink-0 w-full lg:w-72 space-y-3">
              <div className="flex items-center justify-between text-xs">
                <span className="font-semibold text-slate-300">Profile Completion</span>
                <span className="font-extrabold text-brand-royal-400">{completionPct}%</span>
              </div>
              <div className="h-2 w-full rounded-full bg-brand-navy-800 overflow-hidden">
                <div
                  className="h-full bg-gradient-to-r from-brand-royal-500 to-brand-violet-500 rounded-full transition-all duration-500"
                  style={{ width: `${completionPct}%` }}
                />
              </div>
              <p className="text-[11px] text-slate-400">
                {completionPct < 100
                  ? 'Add your demographic details to increase study match rates.'
                  : 'Your profile is fully completed and verified!'}
              </p>
              {completionPct < 100 && (
                <Link to="/member/settings" className="block pt-1">
                  <Button variant="outlineDark" size="sm" className="w-full text-xs font-semibold">
                    Complete Profile
                  </Button>
                </Link>
              )}
            </div>
          </div>
        </div>

        {/* Real Key Metrics Cards */}
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
          {/* Available Points */}
          <Card className="border-brand-royal-100 bg-white shadow-sm">
            <CardHeader className="flex flex-row items-center justify-between pb-2">
              <CardTitle className="text-xs font-bold text-slate-500 uppercase tracking-wider">
                Available Points
              </CardTitle>
              <div className="h-9 w-9 rounded-xl bg-brand-royal-50 flex items-center justify-center text-brand-royal-600">
                <Coins className="h-5 w-5" />
              </div>
            </CardHeader>
            <CardContent>
              <div className="text-2xl sm:text-3xl font-black text-slate-900">
                {balancePoints.toLocaleString()}{' '}
                <span className="text-sm font-semibold text-brand-royal-600">Pts</span>
              </div>
              <p className="text-xs text-slate-500 mt-1 font-medium">
                Equivalent to <span className="font-bold text-emerald-600">${(balancePoints / 100).toFixed(2)} USD</span>
              </p>
            </CardContent>
          </Card>

          {/* Lifetime Earnings */}
          <Card className="border-slate-200 bg-white shadow-sm">
            <CardHeader className="flex flex-row items-center justify-between pb-2">
              <CardTitle className="text-xs font-bold text-slate-500 uppercase tracking-wider">
                Lifetime Earnings
              </CardTitle>
              <div className="h-9 w-9 rounded-xl bg-emerald-50 flex items-center justify-center text-emerald-600">
                <TrendingUp className="h-5 w-5" />
              </div>
            </CardHeader>
            <CardContent>
              <div className="text-2xl sm:text-3xl font-black text-slate-900">
                ${lifetimeUsd}
              </div>
              <p className="text-xs text-slate-500 mt-1 font-medium">
                Actual verified paid rewards
              </p>
            </CardContent>
          </Card>

          {/* Completed Surveys */}
          <Card className="border-slate-200 bg-white shadow-sm">
            <CardHeader className="flex flex-row items-center justify-between pb-2">
              <CardTitle className="text-xs font-bold text-slate-500 uppercase tracking-wider">
                Completed Surveys
              </CardTitle>
              <div className="h-9 w-9 rounded-xl bg-brand-violet-50 flex items-center justify-center text-brand-violet-600">
                <CheckCircle2 className="h-5 w-5" />
              </div>
            </CardHeader>
            <CardContent>
              <div className="text-2xl sm:text-3xl font-black text-slate-900">
                {completedCount}
              </div>
              <p className="text-xs text-slate-500 mt-1 font-medium">
                Verified research submissions
              </p>
            </CardContent>
          </Card>

          {/* Profile Status */}
          <Card className="border-slate-200 bg-white shadow-sm">
            <CardHeader className="flex flex-row items-center justify-between pb-2">
              <CardTitle className="text-xs font-bold text-slate-500 uppercase tracking-wider">
                Profile Readiness
              </CardTitle>
              <div className="h-9 w-9 rounded-xl bg-brand-navy-50 flex items-center justify-center text-brand-navy-900">
                <UserCheck className="h-5 w-5 text-brand-royal-600" />
              </div>
            </CardHeader>
            <CardContent>
              <div className="text-2xl sm:text-3xl font-black text-brand-royal-600">
                {completionPct}%
              </div>
              <p className="text-xs text-slate-500 mt-1 font-medium">
                {completionPct === 100 ? 'Fully Qualified' : 'Demographics Pending'}
              </p>
            </CardContent>
          </Card>
        </div>

        {/* Main Grid: Research Opportunities & Activity */}
        <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
          {/* Research Opportunities List (Real / Clean Empty State) */}
          <div className="lg:col-span-2 space-y-4">
            <div className="flex items-center justify-between">
              <div>
                <h3 className="text-lg font-bold text-slate-900">
                  Available Research Opportunities
                </h3>
                <p className="text-xs text-slate-500">
                  Published research studies matching your verified demographic baseline.
                </p>
              </div>
              <Link to="/member/surveys">
                <Button variant="ghost" size="sm" className="text-brand-royal-600 font-semibold text-xs">
                  View All
                </Button>
              </Link>
            </div>

            {surveys.length === 0 ? (
              /* Genuine Professional Empty State for Surveys */
              <div className="rounded-2xl border border-slate-200 bg-white p-8 sm:p-12 text-center space-y-4 shadow-sm">
                <div className="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 text-slate-400">
                  <FileQuestion className="h-7 w-7" />
                </div>
                <div className="space-y-1.5 max-w-md mx-auto">
                  <h4 className="text-base font-bold text-slate-900">
                    No Surveys Available Yet
                  </h4>
                  <p className="text-xs text-slate-500 leading-relaxed">
                    There are currently no active survey cohorts launched. Research panels are published on a regular basis by verified sponsors.
                  </p>
                </div>
                <div className="pt-2">
                  <Link to="/member/settings">
                    <Button variant="outline" size="sm" className="gap-2 text-xs font-semibold">
                      <UserCheck className="h-4 w-4 text-brand-royal-600" />
                      <span>Update Demographic Profile</span>
                    </Button>
                  </Link>
                </div>
              </div>
            ) : (
              /* Real Live Surveys List */
              <div className="space-y-3">
                {surveys.map((survey) => (
                  <div
                    key={survey.id}
                    className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm hover:border-brand-royal-300 transition-all"
                  >
                    <div className="space-y-1.5 max-w-xl">
                      <div className="flex items-center gap-2">
                        <Badge variant="royal">{survey.category}</Badge>
                        <span className="text-xs text-slate-400">By {survey.provider}</span>
                      </div>
                      <h4 className="font-bold text-slate-900 text-sm sm:text-base">
                        {survey.title}
                      </h4>
                      <div className="flex items-center gap-3 text-xs text-slate-500">
                        <span className="flex items-center gap-1">
                          <Clock className="h-3.5 w-3.5" />
                          {survey.durationMinutes} min
                        </span>
                        <span>•</span>
                        <span className="text-emerald-600 font-bold">
                          +{survey.rewardPoints} Points (${survey.rewardUsd.toFixed(2)})
                        </span>
                      </div>
                    </div>

                    <Link to="/member/surveys">
                      <Button variant="royal" size="sm" className="w-full sm:w-auto font-bold">
                        Start Survey
                      </Button>
                    </Link>
                  </div>
                ))}
              </div>
            )}
          </div>

          {/* Right Column: Wallet & Recent Activity */}
          <div className="space-y-6">
            {/* Quick Wallet Summary Card */}
            <div className="rounded-2xl border border-slate-200 bg-white p-5 space-y-4 shadow-sm">
              <div className="flex items-center justify-between">
                <h4 className="text-sm font-bold text-slate-900 flex items-center gap-2">
                  <Wallet className="h-4 w-4 text-brand-royal-600" />
                  <span>Wallet Summary</span>
                </h4>
                <Link to="/member/wallet">
                  <span className="text-xs font-semibold text-brand-royal-600 hover:underline">
                    Cashouts
                  </span>
                </Link>
              </div>

              <div className="rounded-xl border border-slate-100 bg-slate-50/70 p-3.5 space-y-1">
                <span className="text-[11px] text-slate-500 uppercase tracking-wider block font-semibold">
                  Redeemable Balance
                </span>
                <div className="text-xl font-black text-slate-900">
                  {balancePoints.toLocaleString()}{' '}
                  <span className="text-xs font-bold text-slate-400">Pts</span>
                </div>
                <span className="text-xs font-semibold text-emerald-600 block">
                  ${(balancePoints / 100).toFixed(2)} USD
                </span>
              </div>

              <div className="text-[11px] text-slate-500 space-y-1 border-t border-slate-100 pt-3">
                <p>• Zero minimum fees on verified withdrawals.</p>
                <p>• Payout options: PayPal, Amazon, Apple, Visa.</p>
              </div>

              <Link to="/rewards" className="block pt-1">
                <Button variant="default" size="sm" className="w-full text-xs font-bold">
                  Browse Rewards Catalog
                </Button>
              </Link>
            </div>

            {/* Recent Activity (Clean Empty State) */}
            <div className="rounded-2xl border border-slate-200 bg-white p-5 space-y-4 shadow-sm">
              <h4 className="text-sm font-bold text-slate-900 flex items-center gap-2">
                <Clock className="h-4 w-4 text-slate-400" />
                <span>Recent Activity</span>
              </h4>

              {transactions.length === 0 ? (
                <div className="py-6 text-center space-y-2">
                  <Inbox className="h-8 w-8 text-slate-300 mx-auto" />
                  <p className="text-xs text-slate-500 font-medium">
                    No transactions yet.
                  </p>
                  <p className="text-[11px] text-slate-400 max-w-xs mx-auto">
                    Your survey reward points and redemption history will be recorded here.
                  </p>
                </div>
              ) : (
                <div className="space-y-3">
                  {transactions.slice(0, 5).map((tx) => (
                    <div
                      key={tx.id}
                      className="flex items-start justify-between border-b border-slate-100 pb-2.5 last:border-0 last:pb-0 text-xs"
                    >
                      <div>
                        <span className="font-bold text-slate-900 block">{tx.title}</span>
                        <span className="text-[10px] text-slate-400">
                          {new Date(tx.createdAt).toLocaleDateString()}
                        </span>
                      </div>
                      <div className="text-right">
                        <span
                          className={`font-black ${
                            tx.points >= 0 ? 'text-emerald-600' : 'text-slate-800'
                          }`}
                        >
                          {tx.points >= 0 ? `+${tx.points}` : tx.points} pts
                        </span>
                      </div>
                    </div>
                  ))}
                </div>
              )}
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};
