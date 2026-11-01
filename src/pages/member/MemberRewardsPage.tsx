import React, { useState, useEffect } from 'react';
import { useOutletContext, Link } from 'react-router-dom';
import { DashboardHeader } from '@/components/navigation/DashboardHeader';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/Card';
import { Button } from '@/components/ui/Button';
import { Badge } from '@/components/ui/Badge';
import {
  Gift,
  Award,
  Coins,
  Clock,
  CheckCircle2,
  AlertCircle,
  XCircle,
  ArrowRight,
  Wallet,
  ShieldCheck,
  ClipboardList,
  Filter,
  DollarSign,
  ChevronRight,
  Sparkles,
} from 'lucide-react';
import { useAuth } from '@/context/AuthContext';
import { dataService } from '@/services/dataService';
import { UserProfile, RedemptionRequest, Transaction } from '@/types';

type RewardFilter = 'all' | 'earned' | 'redeemed' | 'pending';

interface RewardItemDisplay {
  id: string;
  title: string;
  type: 'earned' | 'redeemed';
  points: number;
  amountUsd: number;
  status: 'pending' | 'approved' | 'redeemed' | 'settled' | 'rejected' | 'completed';
  date: string;
  destinationOrSource?: string;
  adminNotes?: string;
}

export const MemberRewardsPage: React.FC = () => {
  const { openMobileSidebar } = useOutletContext<{ openMobileSidebar: () => void }>();
  const { user } = useAuth();

  const [profile, setProfile] = useState<UserProfile | null>(null);
  const [redemptions, setRedemptions] = useState<RedemptionRequest[]>([]);
  const [transactions, setTransactions] = useState<Transaction[]>([]);
  const [loading, setLoading] = useState(true);
  const [filter, setFilter] = useState<RewardFilter>('all');

  useEffect(() => {
    let isMounted = true;
    async function loadRewardsData() {
      if (!user) return;
      try {
        const [userProfile, userRedemptions, userTransactions] = await Promise.all([
          dataService.getUserProfile(user.id, user.user_metadata, user.email),
          dataService.getRedemptions(user.id),
          dataService.getTransactions(user.id),
        ]);

        if (isMounted) {
          setProfile(userProfile);
          setRedemptions(userRedemptions);
          setTransactions(userTransactions);
          setLoading(false);
        }
      } catch (err) {
        console.error('Error loading rewards data:', err);
        if (isMounted) setLoading(false);
      }
    }

    loadRewardsData();
    return () => {
      isMounted = false;
    };
  }, [user]);

  const balancePoints = profile?.balancePoints || 0;
  const balanceUsd = (balancePoints / 100).toFixed(2);

  // Calculate Pending Rewards (Points and USD locked in pending redemptions or pending survey verifications)
  const pendingRedemptions = redemptions.filter((r) => r.status === 'pending');
  const pendingTransactions = transactions.filter((t) => t.status === 'pending');
  
  const pendingPoints = pendingRedemptions.reduce((sum, r) => sum + r.pointsSpent, 0) +
    pendingTransactions.filter(t => t.points > 0).reduce((sum, t) => sum + t.points, 0);

  const pendingUsd = (
    pendingRedemptions.reduce((sum, r) => sum + r.amountUsd, 0) +
    pendingTransactions.filter(t => t.amountUsd > 0).reduce((sum, t) => sum + t.amountUsd, 0)
  ).toFixed(2);

  // Calculate Redeemed / Settled Rewards
  const settledRedemptions = redemptions.filter(
    (r) => r.status === 'settled' || r.status === 'approved'
  );
  const redeemedTotalUsd = settledRedemptions
    .reduce((sum, r) => sum + r.amountUsd, 0)
    .toFixed(2);

  // Combine real earned rewards and redemptions into unified list
  const combinedRewards: RewardItemDisplay[] = [
    // 1. Redeemed Cashouts & Gift Cards
    ...redemptions.map((r): RewardItemDisplay => ({
      id: r.id,
      title: r.rewardTitle,
      type: 'redeemed',
      points: -r.pointsSpent,
      amountUsd: r.amountUsd,
      status: r.status,
      date: r.createdAt,
      destinationOrSource: r.payoutDestination,
      adminNotes: r.adminNotes,
    })),
    // 2. Earned Survey Rewards & Bonuses
    ...transactions
      .filter((t) => t.type === 'survey_reward' || t.type === 'bonus' || t.points > 0)
      .map((t): RewardItemDisplay => ({
        id: t.id,
        title: t.title,
        type: 'earned',
        points: t.points,
        amountUsd: t.amountUsd,
        status: t.status === 'completed' ? 'redeemed' : (t.status as any),
        date: t.createdAt,
        destinationOrSource: 'Verified Survey Completion',
      })),
  ].sort((a, b) => new Date(b.date).getTime() - new Date(a.date).getTime());

  // Filter rewards based on selected tab
  const filteredRewards = combinedRewards.filter((item) => {
    if (filter === 'all') return true;
    if (filter === 'earned') return item.type === 'earned';
    if (filter === 'redeemed') return item.type === 'redeemed';
    if (filter === 'pending') return item.status === 'pending';
    return true;
  });

  const getStatusBadge = (status: RewardItemDisplay['status']) => {
    switch (status) {
      case 'pending':
        return (
          <span className="inline-flex items-center gap-1 rounded-full border border-amber-200 bg-amber-50 px-2.5 py-0.5 text-xs font-semibold text-amber-700">
            <Clock className="h-3 w-3" />
            <span>Pending</span>
          </span>
        );
      case 'approved':
        return (
          <span className="inline-flex items-center gap-1 rounded-full border border-blue-200 bg-blue-50 px-2.5 py-0.5 text-xs font-semibold text-blue-700">
            <CheckCircle2 className="h-3 w-3" />
            <span>Approved</span>
          </span>
        );
      case 'settled':
      case 'redeemed':
      case 'completed':
        return (
          <span className="inline-flex items-center gap-1 rounded-full border border-emerald-200 bg-emerald-50 px-2.5 py-0.5 text-xs font-semibold text-emerald-700">
            <CheckCircle2 className="h-3 w-3" />
            <span>Redeemed</span>
          </span>
        );
      case 'rejected':
        return (
          <span className="inline-flex items-center gap-1 rounded-full border border-rose-200 bg-rose-50 px-2.5 py-0.5 text-xs font-semibold text-rose-700">
            <XCircle className="h-3 w-3" />
            <span>Rejected</span>
          </span>
        );
      default:
        return (
          <span className="inline-flex items-center gap-1 rounded-full border border-slate-200 bg-slate-50 px-2.5 py-0.5 text-xs font-semibold text-slate-700">
            {status}
          </span>
        );
    }
  };

  return (
    <div className="flex-1 pb-16">
      <DashboardHeader
        title="My Rewards"
        subtitle="Track your earned research rewards, pending approvals, and verified redemptions."
        onOpenMobileSidebar={openMobileSidebar}
        balancePoints={balancePoints}
      />

      <div className="p-4 sm:p-8 max-w-7xl mx-auto space-y-8">
        
        {/* =========================================================================
            1. REWARD BALANCE SUMMARY CARDS (Available & Pending Shown Separately)
            ========================================================================= */}
        <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
          
          {/* Available Reward Balance */}
          <div className="rounded-2xl bg-gradient-to-br from-brand-navy-950 via-brand-navy-900 to-brand-royal-950 p-6 text-white shadow-xl flex flex-col justify-between border border-brand-navy-800">
            <div>
              <div className="flex items-center justify-between">
                <span className="text-xs uppercase tracking-wider font-semibold text-slate-300">
                  Available Reward Balance
                </span>
                <span className="inline-flex items-center gap-1 text-[11px] font-semibold text-brand-royal-300 bg-brand-royal-950/70 border border-brand-royal-800/60 px-2.5 py-1 rounded-full">
                  <ShieldCheck className="h-3.5 w-3.5" />
                  <span>Real Balance</span>
                </span>
              </div>

              <div className="mt-4 flex items-baseline gap-2.5">
                <span className="text-3xl sm:text-4xl font-black">
                  {balancePoints.toLocaleString()}
                </span>
                <span className="text-sm font-bold text-brand-royal-400">POINTS</span>
              </div>
              
              <div className="text-emerald-400 font-bold text-sm mt-1">
                = ${balanceUsd} USD
              </div>
            </div>

            <div className="mt-6 pt-4 border-t border-brand-navy-800 flex items-center justify-between text-xs text-slate-300">
              <span>Exchange rate: 100 Pts = $1.00 USD</span>
              {balancePoints >= 1000 ? (
                <Link to="/member/wallet">
                  <Button size="sm" variant="royal" className="h-7 text-xs font-bold px-3">
                    Redeem Now
                  </Button>
                </Link>
              ) : (
                <span className="text-slate-400 text-[11px]">Min 1,000 Pts to redeem</span>
              )}
            </div>
          </div>

          {/* Pending Rewards */}
          <div className="rounded-2xl border border-slate-200 bg-white p-6 shadow-xs flex flex-col justify-between">
            <div>
              <div className="flex items-center justify-between">
                <span className="text-xs uppercase tracking-wider font-semibold text-slate-500">
                  Pending Rewards
                </span>
                <div className="h-8 w-8 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center">
                  <Clock className="h-4 w-4" />
                </div>
              </div>

              <div className="mt-4 flex items-baseline gap-2.5">
                <span className="text-3xl sm:text-4xl font-black text-slate-900">
                  {pendingPoints.toLocaleString()}
                </span>
                <span className="text-sm font-bold text-amber-600">POINTS</span>
              </div>

              <div className="text-slate-600 font-semibold text-sm mt-1">
                = ${pendingUsd} USD in Review
              </div>
            </div>

            <div className="mt-6 pt-4 border-t border-slate-100 text-xs text-slate-500">
              <span>{pendingRedemptions.length} cashout request(s) awaiting distribution</span>
            </div>
          </div>

          {/* Total Redeemed Rewards */}
          <div className="rounded-2xl border border-slate-200 bg-white p-6 shadow-xs flex flex-col justify-between">
            <div>
              <div className="flex items-center justify-between">
                <span className="text-xs uppercase tracking-wider font-semibold text-slate-500">
                  Total Redeemed Rewards
                </span>
                <div className="h-8 w-8 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                  <CheckCircle2 className="h-4 w-4" />
                </div>
              </div>

              <div className="mt-4 flex items-baseline gap-2.5">
                <span className="text-3xl sm:text-4xl font-black text-slate-900">
                  ${redeemedTotalUsd}
                </span>
                <span className="text-sm font-bold text-emerald-600">USD</span>
              </div>

              <div className="text-slate-600 font-semibold text-sm mt-1">
                {settledRedemptions.length} verified reward payout(s)
              </div>
            </div>

            <div className="mt-6 pt-4 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500">
              <span>Disbursed to PayPal / Gift Cards</span>
              <Link to="/rewards" className="text-brand-royal-600 font-bold hover:underline">
                Catalog
              </Link>
            </div>
          </div>

        </div>

        {/* =========================================================================
            2. REWARDS ACTIVITY & REDEMPTION HISTORY
            ========================================================================= */}
        <div className="space-y-4">
          <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
              <h3 className="text-xl font-bold text-slate-900">Reward History & Activity</h3>
              <p className="text-xs sm:text-sm text-slate-500">
                Transparent log of genuine survey earnings and payout redemptions.
              </p>
            </div>

            {/* Filter Navigation Tabs */}
            <div className="flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white p-1 shadow-2xs self-start sm:self-auto">
              <button
                onClick={() => setFilter('all')}
                className={`rounded-lg px-3 py-1.5 text-xs font-semibold transition-all ${
                  filter === 'all'
                    ? 'bg-brand-royal-600 text-white shadow-xs'
                    : 'text-slate-600 hover:text-slate-900'
                }`}
              >
                All
              </button>
              <button
                onClick={() => setFilter('earned')}
                className={`rounded-lg px-3 py-1.5 text-xs font-semibold transition-all ${
                  filter === 'earned'
                    ? 'bg-brand-royal-600 text-white shadow-xs'
                    : 'text-slate-600 hover:text-slate-900'
                }`}
              >
                Earned Rewards
              </button>
              <button
                onClick={() => setFilter('redeemed')}
                className={`rounded-lg px-3 py-1.5 text-xs font-semibold transition-all ${
                  filter === 'redeemed'
                    ? 'bg-brand-royal-600 text-white shadow-xs'
                    : 'text-slate-600 hover:text-slate-900'
                }`}
              >
                Redemptions
              </button>
              <button
                onClick={() => setFilter('pending')}
                className={`rounded-lg px-3 py-1.5 text-xs font-semibold transition-all ${
                  filter === 'pending'
                    ? 'bg-brand-royal-600 text-white shadow-xs'
                    : 'text-slate-600 hover:text-slate-900'
                }`}
              >
                Pending ({pendingRedemptions.length})
              </button>
            </div>
          </div>

          {/* Loading State */}
          {loading ? (
            <div className="rounded-2xl border border-slate-200 bg-white p-12 text-center text-slate-500">
              <div className="inline-block h-8 w-8 animate-spin rounded-full border-4 border-solid border-brand-royal-600 border-r-transparent mb-3" />
              <p className="text-sm font-medium">Loading your reward records from Supabase...</p>
            </div>
          ) : combinedRewards.length === 0 ? (
            /* =========================================================================
               3. BEAUTIFUL EMPTY STATE (Exact Prompt Requirement)
               ========================================================================= */
            <div className="rounded-3xl border border-slate-200 bg-white p-10 sm:p-16 text-center shadow-xs space-y-6 max-w-2xl mx-auto">
              <div className="mx-auto flex h-20 w-20 items-center justify-center rounded-3xl bg-brand-royal-50 text-brand-royal-600 border border-brand-royal-100 shadow-xs">
                <Gift className="h-10 w-10 text-brand-royal-600" />
              </div>

              <div className="space-y-2 max-w-lg mx-auto">
                {/* Exact requested copy: "You haven't earned any rewards yet. Complete eligible surveys to start earning rewards." */}
                <h4 className="text-xl sm:text-2xl font-bold text-slate-900">
                  You haven't earned any rewards yet. Complete eligible surveys to start earning rewards.
                </h4>
                <p className="text-sm text-slate-500 leading-relaxed">
                  Every completed survey earns points with a guaranteed valuation of 100 Points = $1.00 USD. 
                  Redeem directly for PayPal transfers, Amazon gift codes, Apple Store cards, and Virtual Visa.
                </p>
              </div>

              <div className="pt-2 flex flex-col sm:flex-row items-center justify-center gap-3">
                {/* Exact requested button: "View Available Surveys" linking to /member/surveys */}
                <Link to="/member/surveys">
                  <Button variant="royal" size="lg" className="gap-2 font-bold px-8 shadow-glow-royal">
                    <ClipboardList className="h-4 w-4" />
                    <span>View Available Surveys</span>
                  </Button>
                </Link>
                <Link to="/rewards">
                  <Button variant="outline" size="lg" className="font-semibold px-6">
                    Browse Reward Options
                  </Button>
                </Link>
              </div>

              <div className="pt-4 border-t border-slate-100 flex items-center justify-center gap-4 text-xs text-slate-400">
                <span className="flex items-center gap-1">
                  <ShieldCheck className="h-3.5 w-3.5 text-emerald-500" />
                  Verified Ledger
                </span>
                <span>•</span>
                <span>Zero Hidden Fees</span>
                <span>•</span>
                <span>Automated Payouts</span>
              </div>
            </div>
          ) : filteredRewards.length === 0 ? (
            /* Empty filter state */
            <div className="rounded-2xl border border-slate-200 bg-white p-12 text-center text-slate-500 space-y-3">
              <Filter className="h-8 w-8 mx-auto text-slate-400" />
              <p className="text-sm font-semibold text-slate-700">
                No reward records match the "{filter}" filter.
              </p>
              <Button size="sm" variant="outline" onClick={() => setFilter('all')}>
                Show All Records
              </Button>
            </div>
          ) : (
            /* Table of Real Member Reward Records */
            <div className="rounded-2xl border border-slate-200 bg-white overflow-hidden shadow-xs">
              <div className="overflow-x-auto">
                <table className="w-full text-left text-sm text-slate-600">
                  <thead className="bg-slate-50 border-b border-slate-200 text-xs uppercase font-semibold text-slate-500">
                    <tr>
                      <th scope="col" className="px-6 py-4">Reward / Activity</th>
                      <th scope="col" className="px-6 py-4">Type</th>
                      <th scope="col" className="px-6 py-4">Earned / Requested Date</th>
                      <th scope="col" className="px-6 py-4">Points & Value</th>
                      <th scope="col" className="px-6 py-4">Status</th>
                      <th scope="col" className="px-6 py-4">Details</th>
                    </tr>
                  </thead>
                  <tbody className="divide-y divide-slate-100">
                    {filteredRewards.map((item) => (
                      <tr key={item.id} className="hover:bg-slate-50/60 transition-colors">
                        <td className="px-6 py-4 font-bold text-slate-900 flex items-center gap-3">
                          <div className={`h-9 w-9 rounded-xl flex items-center justify-center shrink-0 ${
                            item.type === 'earned'
                              ? 'bg-emerald-50 text-emerald-600'
                              : 'bg-brand-royal-50 text-brand-royal-600'
                          }`}>
                            {item.type === 'earned' ? (
                              <Award className="h-4 w-4" />
                            ) : (
                              <Gift className="h-4 w-4" />
                            )}
                          </div>
                          <div>
                            <div className="font-bold text-slate-900">{item.title}</div>
                            <div className="text-[11px] text-slate-400">ID: {item.id}</div>
                          </div>
                        </td>

                        <td className="px-6 py-4">
                          <span className="text-xs font-semibold capitalize text-slate-700">
                            {item.type === 'earned' ? 'Survey Reward' : 'Redemption Payout'}
                          </span>
                        </td>

                        <td className="px-6 py-4 text-xs text-slate-500 whitespace-nowrap">
                          {new Date(item.date).toLocaleDateString('en-US', {
                            year: 'numeric',
                            month: 'short',
                            day: 'numeric',
                          })}
                        </td>

                        <td className="px-6 py-4 whitespace-nowrap">
                          <div className={`font-bold ${
                            item.points > 0 ? 'text-emerald-600' : 'text-slate-900'
                          }`}>
                            {item.points > 0 ? `+${item.points.toLocaleString()}` : item.points.toLocaleString()} Pts
                          </div>
                          <div className="text-xs text-slate-400">
                            ${item.amountUsd.toFixed(2)} USD
                          </div>
                        </td>

                        <td className="px-6 py-4 whitespace-nowrap">
                          {getStatusBadge(item.status)}
                        </td>

                        <td className="px-6 py-4 text-xs text-slate-500">
                          {item.destinationOrSource && (
                            <span className="truncate block max-w-[200px]" title={item.destinationOrSource}>
                              {item.destinationOrSource}
                            </span>
                          )}
                          {item.adminNotes && (
                            <span className="text-[11px] text-brand-royal-600 italic block">
                              Note: {item.adminNotes}
                            </span>
                          )}
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            </div>
          )}
        </div>

        {/* =========================================================================
            4. REDEMPTION ASSISTANCE & REWARDS INFO CARD
            ========================================================================= */}
        <div className="rounded-2xl border border-slate-200 bg-slate-50/80 p-6 flex flex-col sm:flex-row items-center justify-between gap-4">
          <div className="space-y-1">
            <h4 className="text-sm font-bold text-slate-900 flex items-center gap-1.5">
              <Sparkles className="h-4 w-4 text-brand-royal-600" />
              <span>How Points & Redemptions Work</span>
            </h4>
            <p className="text-xs text-slate-500 leading-relaxed max-w-xl">
              100 points = $1.00 USD. Points are credited upon verified completion of surveys and can be redeemed once your balance reaches 1,000 points ($10.00 USD). All transactions are permanently tracked in your secure ledger.
            </p>
          </div>
          <div className="flex items-center gap-2 shrink-0">
            <Link to="/member/wallet">
              <Button variant="outline" size="sm" className="text-xs font-bold">
                Wallet & Cashouts
              </Button>
            </Link>
            <Link to="/member/surveys">
              <Button variant="royal" size="sm" className="text-xs font-bold gap-1.5">
                <span>Find Surveys</span>
                <ChevronRight className="h-3.5 w-3.5" />
              </Button>
            </Link>
          </div>
        </div>

      </div>
    </div>
  );
};
