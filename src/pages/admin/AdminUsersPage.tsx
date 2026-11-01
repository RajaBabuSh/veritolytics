import React, { useState, useEffect } from 'react';
import { useOutletContext } from 'react-router-dom';
import { DashboardHeader } from '@/components/navigation/DashboardHeader';
import { Card } from '@/components/ui/Card';
import { Badge } from '@/components/ui/Badge';
import { Button } from '@/components/ui/Button';
import { Users, ShieldCheck, Inbox, Shield } from 'lucide-react';
import { supabase, isSupabaseConfigured } from '@/lib/supabase';
import { useAuth } from '@/context/AuthContext';
import { UserProfile } from '@/types';

export const AdminUsersPage: React.FC = () => {
  const { openMobileSidebar } = useOutletContext<{ openMobileSidebar: () => void }>();
  const { user: currentUser } = useAuth();

  const [usersList, setUsersList] = useState<Partial<UserProfile>[]>([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    let isMounted = true;
    async function loadUsers() {
      if (isSupabaseConfigured && supabase) {
        try {
          const { data, error } = await supabase.from('profiles').select('*');
          if (!error && data && data.length > 0) {
            if (isMounted) {
              setUsersList(
                data.map((d: any) => ({
                  id: d.id,
                  firstName: d.first_name || '',
                  lastName: d.last_name || '',
                  email: d.email || '',
                  country: d.country || 'United States',
                  role: d.role || 'member',
                  balancePoints: Number(d.balance_points || 0),
                  lifetimeEarningsUsd: Number(d.lifetime_earnings_usd || 0),
                  completedSurveysCount: Number(d.completed_surveys_count || 0),
                  profileCompletionPct: d.profile_completion_pct || 40,
                }))
              );
              setLoading(false);
              return;
            }
          }
        } catch {
          // Table not migrated yet
        }
      }

      // If no table or single authenticated session, show current real user
      if (currentUser && isMounted) {
        setUsersList([
          {
            id: currentUser.id,
            firstName: currentUser.user_metadata?.first_name || 'Panelist',
            lastName: currentUser.user_metadata?.last_name || '',
            email: currentUser.email || '',
            country: currentUser.user_metadata?.country || 'United States',
            role: currentUser.user_metadata?.role || 'admin',
            balancePoints: 0,
            lifetimeEarningsUsd: 0,
            completedSurveysCount: 0,
            profileCompletionPct: 40,
          },
        ]);
        setLoading(false);
      }
    }

    loadUsers();
    return () => {
      isMounted = false;
    };
  }, [currentUser]);

  return (
    <div className="flex-1 pb-16">
      <DashboardHeader
        title="Panelist Directory"
        subtitle="Manage verified platform members, inspect profile readiness, and control access permissions."
        onOpenMobileSidebar={openMobileSidebar}
        isAdmin={true}
      />

      <div className="p-4 sm:p-8 max-w-7xl mx-auto space-y-6">
        <div className="flex items-center justify-between">
          <div>
            <h3 className="text-lg font-bold text-slate-900">Registered Panelists</h3>
            <p className="text-xs text-slate-500">
              {usersList.length} total real registered account{usersList.length === 1 ? '' : 's'}. No fake or demo accounts.
            </p>
          </div>
        </div>

        {usersList.length === 0 ? (
          <div className="rounded-2xl border border-slate-200 bg-white p-12 text-center space-y-3 shadow-sm">
            <Inbox className="h-10 w-10 text-slate-300 mx-auto" />
            <h4 className="text-sm font-bold text-slate-800">No Registered Panelists Yet</h4>
            <p className="text-xs text-slate-500 max-w-sm mx-auto">
              Real accounts created via the registration portal will appear here in real-time.
            </p>
          </div>
        ) : (
          <Card className="border-slate-200 overflow-hidden shadow-sm">
            <div className="overflow-x-auto">
              <table className="w-full text-left text-xs">
                <thead className="bg-slate-50 border-b border-slate-200 text-slate-500 font-bold uppercase tracking-wider">
                  <tr>
                    <th className="py-3 px-4">Panelist</th>
                    <th className="py-3 px-4">Country</th>
                    <th className="py-3 px-4">Role</th>
                    <th className="py-3 px-4">Available Points</th>
                    <th className="py-3 px-4">Lifetime Earned</th>
                    <th className="py-3 px-4">Surveys Completed</th>
                    <th className="py-3 px-4">Profile Readiness</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-slate-100">
                  {usersList.map((u) => (
                    <tr key={u.id} className="hover:bg-slate-50/70 transition-colors">
                      <td className="py-3.5 px-4">
                        <div className="font-bold text-slate-900">
                          {u.firstName} {u.lastName}
                        </div>
                        <span className="text-[11px] text-slate-400">{u.email}</span>
                      </td>
                      <td className="py-3.5 px-4 text-slate-700">{u.country}</td>
                      <td className="py-3.5 px-4">
                        <Badge variant={u.role === 'admin' ? 'royal' : 'secondary'}>
                          {u.role}
                        </Badge>
                      </td>
                      <td className="py-3.5 px-4 font-bold text-slate-900">
                        {(u.balancePoints || 0).toLocaleString()} pts
                      </td>
                      <td className="py-3.5 px-4 text-slate-600">
                        ${(u.lifetimeEarningsUsd || 0).toFixed(2)}
                      </td>
                      <td className="py-3.5 px-4 text-slate-600">{u.completedSurveysCount || 0}</td>
                      <td className="py-3.5 px-4 font-bold text-brand-royal-600">
                        {u.profileCompletionPct || 40}%
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </Card>
        )}
      </div>
    </div>
  );
};
