import React, { useState, useEffect } from 'react';
import { useOutletContext } from 'react-router-dom';
import { DashboardHeader } from '@/components/navigation/DashboardHeader';
import { Card } from '@/components/ui/Card';
import { Button } from '@/components/ui/Button';
import { Badge } from '@/components/ui/Badge';
import { Check, X, Inbox, CheckCircle2 } from 'lucide-react';
import { dataService } from '@/services/dataService';
import { RedemptionRequest } from '@/types';

export const AdminPayoutsPage: React.FC = () => {
  const { openMobileSidebar } = useOutletContext<{ openMobileSidebar: () => void }>();

  const [redemptions, setRedemptions] = useState<RedemptionRequest[]>([]);
  const [actionNotice, setActionNotice] = useState<string | null>(null);

  useEffect(() => {
    let isMounted = true;
    dataService.getRedemptions().then((list) => {
      if (isMounted) setRedemptions(list);
    });
    return () => {
      isMounted = false;
    };
  }, []);

  const handleUpdateStatus = async (id: string, status: 'approved' | 'rejected' | 'settled') => {
    await dataService.updateRedemptionStatus(id, status);
    setActionNotice(`Payout ${id} marked as ${status}.`);
    const updated = await dataService.getRedemptions();
    setRedemptions(updated);
    setTimeout(() => setActionNotice(null), 4000);
  };

  const pendingRequests = redemptions.filter((r) => r.status === 'pending');
  const resolvedRequests = redemptions.filter((r) => r.status !== 'pending');

  return (
    <div className="flex-1 pb-16">
      <DashboardHeader
        title="Payout Disbursements"
        subtitle="Review, release, or hold member reward redemption requests."
        onOpenMobileSidebar={openMobileSidebar}
        isAdmin={true}
      />

      <div className="p-4 sm:p-8 max-w-7xl mx-auto space-y-6">
        {actionNotice && (
          <div className="flex items-center gap-2.5 rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-xs font-bold text-emerald-800 shadow-sm animate-fadeIn">
            <CheckCircle2 className="h-4 w-4 text-emerald-600 shrink-0" />
            <span>{actionNotice}</span>
          </div>
        )}

        <div className="flex items-center justify-between">
          <div>
            <h3 className="text-lg font-bold text-slate-900">Pending Outgoing Disbursements</h3>
            <p className="text-xs text-slate-500">
              {pendingRequests.length} pending request{pendingRequests.length === 1 ? '' : 's'} in queue.
            </p>
          </div>
        </div>

        {pendingRequests.length === 0 ? (
          <div className="rounded-2xl border border-slate-200 bg-white p-12 text-center space-y-3 shadow-sm">
            <Inbox className="h-10 w-10 text-slate-300 mx-auto" />
            <h4 className="text-sm font-bold text-slate-800">No Pending Payout Requests</h4>
            <p className="text-xs text-slate-500 max-w-sm mx-auto">
              All member reward redemption requests have been processed or none have been submitted yet.
            </p>
          </div>
        ) : (
          <Card className="border-slate-200 overflow-hidden shadow-sm">
            <div className="overflow-x-auto">
              <table className="w-full text-left text-xs">
                <thead className="bg-slate-50 border-b border-slate-200 text-slate-500 font-bold uppercase tracking-wider">
                  <tr>
                    <th className="py-3 px-4">Panelist</th>
                    <th className="py-3 px-4">Reward Title</th>
                    <th className="py-3 px-4">Points</th>
                    <th className="py-3 px-4">Disbursement Amount</th>
                    <th className="py-3 px-4">Payout Destination</th>
                    <th className="py-3 px-4 text-right">Review Action</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-slate-100">
                  {pendingRequests.map((p) => (
                    <tr key={p.id} className="hover:bg-slate-50/70 transition-colors">
                      <td className="py-3.5 px-4">
                        <div className="font-bold text-slate-900">{p.userName}</div>
                        <span className="text-[11px] text-slate-400">{p.userEmail}</span>
                      </td>
                      <td className="py-3.5 px-4 font-semibold text-slate-700">{p.rewardTitle}</td>
                      <td className="py-3.5 px-4 text-slate-500">{p.pointsSpent.toLocaleString()} pts</td>
                      <td className="py-3.5 px-4 font-extrabold text-emerald-600">
                        ${p.amountUsd.toFixed(2)}
                      </td>
                      <td className="py-3.5 px-4 font-mono text-[11px] text-slate-600">
                        {p.payoutDestination}
                      </td>
                      <td className="py-3.5 px-4 text-right">
                        <div className="flex items-center justify-end gap-2">
                          <Button
                            variant="royal"
                            size="sm"
                            className="text-xs h-8 px-3 font-bold"
                            onClick={() => handleUpdateStatus(p.id, 'approved')}
                          >
                            <Check className="h-3.5 w-3.5 mr-1" />
                            <span>Release</span>
                          </Button>
                          <Button
                            variant="outline"
                            size="sm"
                            className="text-xs h-8 px-3 text-rose-600 hover:bg-rose-50"
                            onClick={() => handleUpdateStatus(p.id, 'rejected')}
                          >
                            <X className="h-3.5 w-3.5 mr-1" />
                            <span>Reject</span>
                          </Button>
                        </div>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </Card>
        )}

        {resolvedRequests.length > 0 && (
          <div className="space-y-3 pt-6">
            <h4 className="text-base font-bold text-slate-900">Historical Processed Disbursements</h4>
            <Card className="border-slate-200 overflow-hidden shadow-sm">
              <div className="overflow-x-auto">
                <table className="w-full text-left text-xs">
                  <thead className="bg-slate-50 border-b border-slate-200 text-slate-500 font-bold uppercase tracking-wider">
                    <tr>
                      <th className="py-3 px-4">Panelist</th>
                      <th className="py-3 px-4">Reward</th>
                      <th className="py-3 px-4">Amount</th>
                      <th className="py-3 px-4">Date</th>
                      <th className="py-3 px-4 text-right">Status</th>
                    </tr>
                  </thead>
                  <tbody className="divide-y divide-slate-100">
                    {resolvedRequests.map((r) => (
                      <tr key={r.id}>
                        <td className="py-3.5 px-4 font-medium text-slate-900">{r.userName}</td>
                        <td className="py-3.5 px-4 text-slate-700">{r.rewardTitle}</td>
                        <td className="py-3.5 px-4 font-bold text-emerald-600">${r.amountUsd.toFixed(2)}</td>
                        <td className="py-3.5 px-4 text-slate-400">
                          {new Date(r.createdAt).toLocaleDateString()}
                        </td>
                        <td className="py-3.5 px-4 text-right">
                          <Badge variant={r.status === 'approved' || r.status === 'settled' ? 'success' : 'secondary'}>
                            {r.status}
                          </Badge>
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            </Card>
          </div>
        )}
      </div>
    </div>
  );
};
