import React, { useState, useEffect } from 'react';
import { useOutletContext, Link } from 'react-router-dom';
import { DashboardHeader } from '@/components/navigation/DashboardHeader';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/Card';
import { Button } from '@/components/ui/Button';
import { Badge } from '@/components/ui/Badge';
import { Wallet, Coins, ShieldCheck, Zap, AlertCircle, CheckCircle2, Clock, Inbox, ArrowUpRight } from 'lucide-react';
import { useAuth } from '@/context/AuthContext';
import { dataService, STANDARD_REWARDS } from '@/services/dataService';
import { UserProfile, RedemptionRequest, Transaction, RewardOption } from '@/types';

export const MemberWalletPage: React.FC = () => {
  const { openMobileSidebar } = useOutletContext<{ openMobileSidebar: () => void }>();
  const { user } = useAuth();

  const [profile, setProfile] = useState<UserProfile | null>(null);
  const [redemptions, setRedemptions] = useState<RedemptionRequest[]>([]);
  const [transactions, setTransactions] = useState<Transaction[]>([]);
  const [loading, setLoading] = useState(true);

  // Redemption Form State
  const [selectedReward, setSelectedReward] = useState<RewardOption | null>(null);
  const [destinationInput, setDestinationInput] = useState('');
  const [redemptionStatus, setRedemptionStatus] = useState<{ type: 'success' | 'error'; text: string } | null>(null);
  const [isSubmitting, setIsSubmitting] = useState(false);

  useEffect(() => {
    let isMounted = true;
    async function loadWalletData() {
      if (!user) return;
      try {
        const p = await dataService.getUserProfile(user.id, user.user_metadata, user.email);
        const r = await dataService.getRedemptions(user.id);
        const t = await dataService.getTransactions(user.id);
        if (isMounted) {
          setProfile(p);
          setRedemptions(r);
          setTransactions(t);
          setLoading(false);
        }
      } catch (err) {
        console.error('Error loading wallet data:', err);
        if (isMounted) setLoading(false);
      }
    }
    loadWalletData();
    return () => {
      isMounted = false;
    };
  }, [user]);

  const balancePoints = profile?.balancePoints || 0;
  const balanceUsd = (balancePoints / 100).toFixed(2);

  // Calculate real settled total
  const settledTotalUsd = redemptions
    .filter((r) => r.status === 'settled' || r.status === 'approved')
    .reduce((sum, r) => sum + r.amountUsd, 0)
    .toFixed(2);

  const pendingRedemptions = redemptions.filter((r) => r.status === 'pending');
  const pastRedemptions = redemptions.filter((r) => r.status !== 'pending');

  const handleOpenRedeemModal = (reward: RewardOption) => {
    setSelectedReward(reward);
    setDestinationInput(user?.email || '');
    setRedemptionStatus(null);
  };

  const handleConfirmRedemption = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!profile || !selectedReward) return;

    if (!destinationInput.trim()) {
      setRedemptionStatus({ type: 'error', text: 'Please enter a valid payout destination address.' });
      return;
    }

    setIsSubmitting(true);
    setRedemptionStatus(null);

    const res = await dataService.requestRedemption(profile, selectedReward, destinationInput.trim());

    setIsSubmitting(false);

    if (res.error) {
      setRedemptionStatus({ type: 'error', text: res.error });
      return;
    }

    setRedemptionStatus({
      type: 'success',
      text: `Redemption requested successfully for ${selectedReward.title}. Our team will review and release your payout.`,
    });

    // Refresh wallet
    const updatedRedemptions = await dataService.getRedemptions(user?.id);
    const updatedTransactions = await dataService.getTransactions(user?.id || '');
    const updatedProfile = await dataService.getUserProfile(user?.id || '', user?.user_metadata, user?.email);
    setRedemptions(updatedRedemptions);
    setTransactions(updatedTransactions);
    setProfile(updatedProfile);
    setSelectedReward(null);
  };

  return (
    <div className="flex-1 pb-16">
      <DashboardHeader
        title="Wallet & Cashouts"
        subtitle="Manage your earned points balance and submit real reward redemption requests."
        onOpenMobileSidebar={openMobileSidebar}
        balancePoints={balancePoints}
      />

      <div className="p-4 sm:p-8 max-w-7xl mx-auto space-y-8">
        {/* Wallet Balance Hero Card */}
        <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
          <div className="md:col-span-2 rounded-2xl bg-gradient-to-br from-brand-navy-950 via-brand-navy-900 to-brand-royal-950 p-6 sm:p-8 text-white shadow-xl flex flex-col justify-between">
            <div>
              <div className="flex items-center justify-between">
                <span className="text-xs uppercase tracking-wider font-semibold text-slate-300">
                  Available Redeemable Balance
                </span>
                <span className="flex items-center gap-1.5 text-xs text-brand-royal-300 bg-brand-royal-950/70 border border-brand-royal-800/50 px-3 py-1 rounded-full">
                  <ShieldCheck className="h-3.5 w-3.5" />
                  Verified Ledger
                </span>
              </div>
              <div className="mt-4 flex items-baseline gap-3">
                <span className="text-4xl sm:text-5xl font-black">
                  {balancePoints.toLocaleString()}
                </span>
                <span className="text-lg font-bold text-brand-royal-400">POINTS</span>
              </div>
              <div className="text-emerald-400 font-bold text-sm mt-1">
                = ${balanceUsd} USD Available to Withdraw
              </div>
            </div>

            <div className="mt-8 pt-6 border-t border-brand-navy-800 flex flex-wrap items-center justify-between text-xs text-slate-300 gap-4">
              <div>
                <span className="text-slate-400 block">Total Lifetime Redeemed:</span>
                <span className="font-bold text-white text-sm">${settledTotalUsd} USD</span>
              </div>
              <div>
                <span className="text-slate-400 block">Redemption Requirement:</span>
                <span className="font-bold text-emerald-400 text-sm">Min. 1,000 Points ($10.00)</span>
              </div>
            </div>
          </div>

          <div className="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm flex flex-col justify-between space-y-4">
            <div className="space-y-2">
              <div className="h-10 w-10 rounded-xl bg-brand-royal-50 flex items-center justify-center text-brand-royal-600">
                <Zap className="h-6 w-6" />
              </div>
              <h4 className="font-bold text-slate-900">Zero Deduction Cashouts</h4>
              <p className="text-xs text-slate-500 leading-relaxed">
                Every 100 points equals exactly $1.00 USD value. No hidden fees or surprise point deductions upon redemption.
              </p>
            </div>
            <div className="pt-3 border-t border-slate-100 text-[11px] text-slate-400">
              Need assistance? Support team responds within 1 business day.
            </div>
          </div>
        </div>

        {/* Feedback Alert */}
        {redemptionStatus && (
          <div
            className={`flex items-start gap-3 rounded-2xl border p-4 text-xs shadow-sm animate-fadeIn ${
              redemptionStatus.type === 'success'
                ? 'border-emerald-200 bg-emerald-50 text-emerald-800'
                : 'border-rose-200 bg-rose-50 text-rose-800'
            }`}
          >
            {redemptionStatus.type === 'success' ? (
              <CheckCircle2 className="h-4 w-4 shrink-0 text-emerald-600 mt-0.5" />
            ) : (
              <AlertCircle className="h-4 w-4 shrink-0 text-rose-600 mt-0.5" />
            )}
            <p className="leading-relaxed font-medium">{redemptionStatus.text}</p>
          </div>
        )}

        {/* Modal / Panel: Initiate Redemption */}
        {selectedReward && (
          <div className="rounded-2xl border border-brand-royal-200 bg-gradient-to-br from-brand-royal-50/80 via-white to-brand-violet-50/50 p-6 shadow-md animate-fadeIn">
            <div className="flex items-center justify-between border-b border-slate-200 pb-4 mb-4">
              <div className="flex items-center gap-2">
                <span className="text-2xl">{selectedReward.icon}</span>
                <div>
                  <h4 className="font-bold text-slate-900 text-sm">{selectedReward.title}</h4>
                  <span className="text-xs text-slate-500">
                    Cost: {selectedReward.pointsRequired.toLocaleString()} Points (${selectedReward.cashEquivalentUsd.toFixed(2)})
                  </span>
                </div>
              </div>
              <Button variant="ghost" size="sm" onClick={() => setSelectedReward(null)}>
                Cancel
              </Button>
            </div>

            <form onSubmit={handleConfirmRedemption} className="space-y-4 max-w-md">
              <div>
                <label className="block text-xs font-bold text-slate-700 mb-1">
                  Payout Destination (PayPal Email, Wallet or Delivery Address)
                </label>
                <input
                  type="text"
                  required
                  value={destinationInput}
                  onChange={(e) => setDestinationInput(e.target.value)}
                  placeholder="e.g. your-paypal@email.com"
                  className="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-royal-500"
                />
              </div>

              <div className="flex items-center gap-3">
                <Button
                  type="submit"
                  variant="royal"
                  size="sm"
                  disabled={isSubmitting || balancePoints < selectedReward.pointsRequired}
                  className="font-bold"
                >
                  {isSubmitting ? 'Submitting Request...' : `Confirm & Redeem (${selectedReward.pointsRequired} Pts)`}
                </Button>
                <Button type="button" variant="outline" size="sm" onClick={() => setSelectedReward(null)}>
                  Close
                </Button>
              </div>
            </form>
          </div>
        )}

        {/* Real Redemption Catalog Channels */}
        <div className="space-y-4">
          <div className="flex items-center justify-between">
            <h3 className="text-lg font-bold text-slate-900">Choose Payout Method</h3>
            <span className="text-xs text-slate-400">100 Points = $1.00 USD</span>
          </div>

          <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
            {STANDARD_REWARDS.map((opt) => {
              const canAfford = balancePoints >= opt.pointsRequired;
              return (
                <Card
                  key={opt.id}
                  className={`border-slate-200 transition-all flex flex-col justify-between ${
                    canAfford ? 'hover:border-brand-royal-300 hover:shadow-md' : 'opacity-85'
                  }`}
                >
                  <CardHeader className="pb-3">
                    <div className="text-3xl mb-2">{opt.icon}</div>
                    <CardTitle className="text-base font-bold">{opt.title}</CardTitle>
                    <CardDescription className="text-xs space-y-1 pt-1">
                      <span className="block text-slate-500">{opt.deliveryType}</span>
                      <span className="block font-bold text-brand-royal-600">
                        {opt.pointsRequired.toLocaleString()} Points
                      </span>
                    </CardDescription>
                  </CardHeader>
                  <CardContent className="pt-2">
                    {canAfford ? (
                      <Button
                        variant="royal"
                        size="sm"
                        className="w-full text-xs font-bold"
                        onClick={() => handleOpenRedeemModal(opt)}
                      >
                        Redeem Reward
                      </Button>
                    ) : (
                      <Button variant="outline" size="sm" disabled className="w-full text-xs font-medium text-slate-400">
                        Requires {opt.pointsRequired.toLocaleString()} Pts
                      </Button>
                    )}
                  </CardContent>
                </Card>
              );
            })}
          </div>
        </div>

        {/* Pending & Historical Redemptions */}
        <div className="space-y-4">
          <h3 className="text-lg font-bold text-slate-900">Redemption History</h3>

          {redemptions.length === 0 ? (
            <div className="rounded-2xl border border-slate-200 bg-white p-8 text-center space-y-2">
              <Inbox className="h-8 w-8 text-slate-300 mx-auto" />
              <h5 className="text-xs font-bold text-slate-700">No redemptions requested yet</h5>
              <p className="text-[11px] text-slate-400 max-w-sm mx-auto">
                Once you accumulate at least 1,000 points from surveys, your requested cashouts and digital gift codes will be tracked here.
              </p>
            </div>
          ) : (
            <div className="rounded-2xl border border-slate-200 bg-white overflow-hidden shadow-sm">
              <div className="overflow-x-auto">
                <table className="w-full text-left text-xs">
                  <thead className="bg-slate-50 border-b border-slate-200 text-slate-500 font-bold uppercase tracking-wider">
                    <tr>
                      <th className="py-3 px-4">Reward</th>
                      <th className="py-3 px-4">Amount</th>
                      <th className="py-3 px-4">Points Spent</th>
                      <th className="py-3 px-4">Destination</th>
                      <th className="py-3 px-4">Date</th>
                      <th className="py-3 px-4 text-right">Status</th>
                    </tr>
                  </thead>
                  <tbody className="divide-y divide-slate-100">
                    {redemptions.map((r) => (
                      <tr key={r.id}>
                        <td className="py-3.5 px-4 font-bold text-slate-900">{r.rewardTitle}</td>
                        <td className="py-3.5 px-4 font-bold text-emerald-600">${r.amountUsd.toFixed(2)}</td>
                        <td className="py-3.5 px-4 text-slate-500">{r.pointsSpent} pts</td>
                        <td className="py-3.5 px-4 text-slate-600">{r.payoutDestination}</td>
                        <td className="py-3.5 px-4 text-slate-400">
                          {new Date(r.createdAt).toLocaleDateString()}
                        </td>
                        <td className="py-3.5 px-4 text-right">
                          <Badge
                            variant={
                              r.status === 'settled' || r.status === 'approved'
                                ? 'success'
                                : r.status === 'pending'
                                ? 'secondary'
                                : 'outline'
                            }
                          >
                            {r.status}
                          </Badge>
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            </div>
          )}
        </div>
      </div>
    </div>
  );
};
