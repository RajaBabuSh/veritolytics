import React, { useState, useEffect } from 'react';
import { useOutletContext, Link } from 'react-router-dom';
import { DashboardHeader } from '@/components/navigation/DashboardHeader';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/Card';
import { Button } from '@/components/ui/Button';
import { Badge } from '@/components/ui/Badge';
import {
  Users,
  FileQuestion,
  CreditCard,
  ShieldCheck,
  DollarSign,
  Activity,
  Plus,
  Inbox,
} from 'lucide-react';
import { dataService } from '@/services/dataService';
import { Survey, RedemptionRequest } from '@/types';

export const AdminDashboardPage: React.FC = () => {
  const { openMobileSidebar } = useOutletContext<{ openMobileSidebar: () => void }>();

  const [surveys, setSurveys] = useState<Survey[]>([]);
  const [redemptions, setRedemptions] = useState<RedemptionRequest[]>([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    let isMounted = true;
    async function loadAdminData() {
      try {
        const s = await dataService.getAllSurveysAdmin();
        const r = await dataService.getRedemptions();
        if (isMounted) {
          setSurveys(s);
          setRedemptions(r);
          setLoading(false);
        }
      } catch (err) {
        console.error('Error loading admin dashboard:', err);
        if (isMounted) setLoading(false);
      }
    }
    loadAdminData();
    return () => {
      isMounted = false;
    };
  }, []);

  const activeSurveys = surveys.filter((s) => s.status === 'active');
  const pendingPayouts = redemptions.filter((r) => r.status === 'pending');
  const settledPayouts = redemptions.filter((r) => r.status === 'settled' || r.status === 'approved');
  const totalVolumeUsd = settledPayouts.reduce((sum, r) => sum + r.amountUsd, 0).toFixed(2);

  const systemKpis = [
    {
      title: 'Published Surveys',
      value: `${surveys.length} Campaigns`,
      change: `${activeSurveys.length} currently active`,
      icon: FileQuestion,
    },
    {
      title: 'Pending Redemptions',
      value: `${pendingPayouts.length} Requests`,
      change: pendingPayouts.length > 0 ? 'Requires administrative review' : 'Queue all clear',
      icon: CreditCard,
    },
    {
      title: 'Settled Payout Volume',
      value: `$${totalVolumeUsd}`,
      change: 'Verified disbursements',
      icon: DollarSign,
    },
    {
      title: 'Authentication Status',
      value: 'Live Supabase',
      change: 'Row Level Security Enforced',
      icon: ShieldCheck,
    },
  ];

  return (
    <div className="flex-1 pb-16">
      <DashboardHeader
        title="Admin Control Center"
        subtitle="Manage research survey cohorts, verify panelist disbursements, and monitor platform health."
        onOpenMobileSidebar={openMobileSidebar}
        isAdmin={true}
      />

      <div className="p-4 sm:p-8 max-w-7xl mx-auto space-y-8">
        {/* Real KPI Cards */}
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
          {systemKpis.map((kpi) => {
            const Icon = kpi.icon;
            return (
              <Card key={kpi.title} className="border-slate-200 bg-white shadow-sm">
                <CardHeader className="flex flex-row items-center justify-between pb-2">
                  <CardTitle className="text-xs font-bold text-slate-500 uppercase tracking-wider">
                    {kpi.title}
                  </CardTitle>
                  <div className="h-9 w-9 rounded-xl bg-brand-navy-950 flex items-center justify-center text-brand-royal-400">
                    <Icon className="h-5 w-5" />
                  </div>
                </CardHeader>
                <CardContent>
                  <div className="text-2xl font-black text-slate-900">
                    {kpi.value}
                  </div>
                  <p className="text-xs text-brand-royal-700 font-semibold mt-1">
                    {kpi.change}
                  </p>
                </CardContent>
              </Card>
            );
          })}
        </div>

        {/* Live System Performance & Active Audits */}
        <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
          <div className="lg:col-span-2 space-y-4">
            <div className="flex items-center justify-between">
              <div>
                <h3 className="text-lg font-bold text-slate-900">
                  Published Survey Campaigns
                </h3>
                <p className="text-xs text-slate-500">
                  Active research panels available to verified platform panelists.
                </p>
              </div>
              <Link to="/admin/surveys">
                <Button variant="default" size="sm" className="gap-2 text-xs font-bold">
                  <Plus className="h-4 w-4" />
                  <span>Create Survey</span>
                </Button>
              </Link>
            </div>

            {surveys.length === 0 ? (
              <div className="rounded-2xl border border-slate-200 bg-white p-10 text-center space-y-3 shadow-sm">
                <Inbox className="h-10 w-10 text-slate-300 mx-auto" />
                <h4 className="text-sm font-bold text-slate-800">No Surveys Created Yet</h4>
                <p className="text-xs text-slate-500 max-w-sm mx-auto">
                  Your platform is currently in a clean launch state. Click below to publish your first research cohort.
                </p>
                <div className="pt-2">
                  <Link to="/admin/surveys">
                    <Button variant="royal" size="sm" className="gap-2 text-xs font-bold">
                      <Plus className="h-4 w-4" />
                      <span>Create New Survey</span>
                    </Button>
                  </Link>
                </div>
              </div>
            ) : (
              <div className="space-y-3">
                {surveys.map((item) => (
                  <div
                    key={item.id}
                    className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm space-y-3"
                  >
                    <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                      <div>
                        <h4 className="font-bold text-slate-900 text-sm sm:text-base">
                          {item.title}
                        </h4>
                        <span className="text-xs text-slate-500">Category: {item.category} • Provider: {item.provider}</span>
                      </div>
                      <Badge variant={item.status === 'active' ? 'success' : 'secondary'}>
                        {item.status}
                      </Badge>
                    </div>

                    <div className="grid grid-cols-3 gap-2 border-t border-slate-100 pt-3 text-xs">
                      <div>
                        <span className="text-slate-400 block">Quota</span>
                        <span className="font-bold text-slate-800">{item.completedCount} / {item.quota}</span>
                      </div>
                      <div>
                        <span className="text-slate-400 block">Duration</span>
                        <span className="font-bold text-slate-800">{item.durationMinutes} min</span>
                      </div>
                      <div>
                        <span className="text-slate-400 block">Reward per Panelist</span>
                        <span className="font-bold text-emerald-600">+{item.rewardPoints} Pts (${item.rewardUsd.toFixed(2)})</span>
                      </div>
                    </div>
                  </div>
                ))}
              </div>
            )}
          </div>

          {/* Quick Admin Actions & Node Status */}
          <div className="space-y-4">
            <h3 className="text-lg font-bold text-slate-900">Control Actions</h3>

            <div className="rounded-2xl border border-brand-navy-800 bg-brand-navy-950 p-6 text-white space-y-5 shadow-lg">
              <div className="flex items-center justify-between border-b border-brand-navy-800 pb-4">
                <div className="flex items-center gap-2">
                  <Activity className="h-5 w-5 text-emerald-400" />
                  <span className="text-sm font-bold">Node Health</span>
                </div>
                <span className="text-xs text-emerald-400 font-bold bg-emerald-950/80 px-2.5 py-1 rounded-full border border-emerald-800/40">
                  Online
                </span>
              </div>

              <div className="space-y-3 text-xs text-slate-300">
                <div className="flex justify-between">
                  <span className="text-slate-400">Pending Redemptions:</span>
                  <span className="font-bold text-white">{pendingPayouts.length} in queue</span>
                </div>
                <div className="flex justify-between">
                  <span className="text-slate-400">Total Live Surveys:</span>
                  <span className="font-bold text-emerald-400">{activeSurveys.length} Active</span>
                </div>
              </div>

              <div className="pt-2 flex flex-col gap-2">
                <Link to="/admin/payouts">
                  <Button variant="royal" size="sm" className="w-full text-xs font-bold">
                    Review Payout Requests ({pendingPayouts.length})
                  </Button>
                </Link>
                <Link to="/admin/surveys">
                  <Button variant="outlineDark" size="sm" className="w-full text-xs font-bold">
                    Survey Campaign Manager
                  </Button>
                </Link>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};
