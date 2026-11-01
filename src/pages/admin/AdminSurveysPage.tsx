import React, { useState, useEffect } from 'react';
import { useOutletContext } from 'react-router-dom';
import { DashboardHeader } from '@/components/navigation/DashboardHeader';
import { Card } from '@/components/ui/Card';
import { Button } from '@/components/ui/Button';
import { Badge } from '@/components/ui/Badge';
import { Plus, Inbox, CheckCircle2, X } from 'lucide-react';
import { dataService } from '@/services/dataService';
import { Survey } from '@/types';

export const AdminSurveysPage: React.FC = () => {
  const { openMobileSidebar } = useOutletContext<{ openMobileSidebar: () => void }>();

  const [surveys, setSurveys] = useState<Survey[]>([]);
  const [showCreateModal, setShowCreateModal] = useState(false);
  const [successMessage, setSuccessMessage] = useState<string | null>(null);

  // Form State
  const [title, setTitle] = useState('');
  const [description, setDescription] = useState('');
  const [category, setCategory] = useState('Technology');
  const [rewardPoints, setRewardPoints] = useState(500);
  const [durationMinutes, setDurationMinutes] = useState(10);
  const [quota, setQuota] = useState(100);
  const [provider, setProvider] = useState('Veritolytics Direct');
  const [isSubmitting, setIsSubmitting] = useState(false);

  useEffect(() => {
    let isMounted = true;
    dataService.getAllSurveysAdmin().then((list) => {
      if (isMounted) setSurveys(list);
    });
    return () => {
      isMounted = false;
    };
  }, []);

  const handleCreateSurvey = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!title.trim()) return;

    setIsSubmitting(true);
    const newSurvey = await dataService.createSurvey({
      title: title.trim(),
      description: description.trim(),
      category,
      rewardPoints: Number(rewardPoints),
      rewardUsd: Number(rewardPoints) / 100,
      durationMinutes: Number(durationMinutes),
      quota: Number(quota),
      status: 'active',
      provider: provider.trim() || 'Veritolytics Direct',
    });

    setIsSubmitting(false);
    setShowCreateModal(false);
    setTitle('');
    setDescription('');
    setSuccessMessage(`Survey campaign "${newSurvey.title}" published successfully!`);

    // Refresh list
    const updated = await dataService.getAllSurveysAdmin();
    setSurveys(updated);

    setTimeout(() => setSuccessMessage(null), 4000);
  };

  return (
    <div className="flex-1 pb-16">
      <DashboardHeader
        title="Survey Campaigns"
        subtitle="Publish genuine research studies, allocate respondent quotas, and set point rewards."
        onOpenMobileSidebar={openMobileSidebar}
        isAdmin={true}
      />

      <div className="p-4 sm:p-8 max-w-7xl mx-auto space-y-6">
        {successMessage && (
          <div className="flex items-center gap-2.5 rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-xs font-bold text-emerald-800 shadow-sm animate-fadeIn">
            <CheckCircle2 className="h-4 w-4 text-emerald-600 shrink-0" />
            <span>{successMessage}</span>
          </div>
        )}

        <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
          <div>
            <h3 className="text-lg font-bold text-slate-900">Research Campaigns Directory</h3>
            <p className="text-xs text-slate-500">{surveys.length} total campaigns configured.</p>
          </div>
          <Button
            variant="default"
            size="sm"
            className="gap-2 font-bold"
            onClick={() => setShowCreateModal(true)}
          >
            <Plus className="h-4 w-4" />
            <span>Create New Survey</span>
          </Button>
        </div>

        {/* Create Survey Modal / Panel */}
        {showCreateModal && (
          <div className="rounded-2xl border border-brand-royal-200 bg-gradient-to-br from-brand-royal-50/80 via-white to-brand-violet-50/50 p-6 shadow-md animate-fadeIn">
            <div className="flex items-center justify-between border-b border-slate-200 pb-3 mb-4">
              <h4 className="font-bold text-slate-900 text-sm">Publish New Research Survey</h4>
              <button
                type="button"
                onClick={() => setShowCreateModal(false)}
                className="p-1 rounded-lg text-slate-400 hover:text-slate-600"
              >
                <X className="h-4 w-4" />
              </button>
            </div>

            <form onSubmit={handleCreateSurvey} className="space-y-4">
              <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                  <label className="text-xs font-bold text-slate-700 block mb-1">Survey Title *</label>
                  <input
                    type="text"
                    required
                    value={title}
                    onChange={(e) => setTitle(e.target.value)}
                    placeholder="e.g. Global Renewable Energy & EV Adoption"
                    className="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-royal-500"
                  />
                </div>
                <div>
                  <label className="text-xs font-bold text-slate-700 block mb-1">Category *</label>
                  <select
                    value={category}
                    onChange={(e) => setCategory(e.target.value)}
                    className="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-royal-500"
                  >
                    <option value="Technology">Technology</option>
                    <option value="Fintech">Fintech</option>
                    <option value="Consumer Insights">Consumer Insights</option>
                    <option value="Healthcare">Healthcare</option>
                    <option value="Gaming & Media">Gaming & Media</option>
                  </select>
                </div>
              </div>

              <div className="grid grid-cols-1 sm:grid-cols-4 gap-4">
                <div>
                  <label className="text-xs font-bold text-slate-700 block mb-1">Reward Points *</label>
                  <input
                    type="number"
                    min="50"
                    step="50"
                    required
                    value={rewardPoints}
                    onChange={(e) => setRewardPoints(Number(e.target.value))}
                    className="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-royal-500"
                  />
                  <span className="text-[10px] text-slate-400 mt-0.5 block">
                    = ${(rewardPoints / 100).toFixed(2)} USD
                  </span>
                </div>
                <div>
                  <label className="text-xs font-bold text-slate-700 block mb-1">Duration (Min) *</label>
                  <input
                    type="number"
                    min="1"
                    required
                    value={durationMinutes}
                    onChange={(e) => setDurationMinutes(Number(e.target.value))}
                    className="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-royal-500"
                  />
                </div>
                <div>
                  <label className="text-xs font-bold text-slate-700 block mb-1">Respondent Quota *</label>
                  <input
                    type="number"
                    min="5"
                    required
                    value={quota}
                    onChange={(e) => setQuota(Number(e.target.value))}
                    className="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-royal-500"
                  />
                </div>
                <div>
                  <label className="text-xs font-bold text-slate-700 block mb-1">Sponsor / Provider</label>
                  <input
                    type="text"
                    value={provider}
                    onChange={(e) => setProvider(e.target.value)}
                    placeholder="Veritolytics Direct"
                    className="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-royal-500"
                  />
                </div>
              </div>

              <div>
                <label className="text-xs font-bold text-slate-700 block mb-1">Short Description</label>
                <textarea
                  value={description}
                  onChange={(e) => setDescription(e.target.value)}
                  placeholder="Outline the core objective of this study..."
                  rows={2}
                  className="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-royal-500"
                />
              </div>

              <div className="flex items-center gap-3 pt-2">
                <Button type="submit" variant="royal" size="sm" disabled={isSubmitting} className="font-bold">
                  {isSubmitting ? 'Publishing...' : 'Publish Survey Live'}
                </Button>
                <Button type="button" variant="outline" size="sm" onClick={() => setShowCreateModal(false)}>
                  Cancel
                </Button>
              </div>
            </form>
          </div>
        )}

        {/* Surveys Table */}
        {surveys.length === 0 ? (
          <div className="rounded-2xl border border-slate-200 bg-white p-12 text-center space-y-3 shadow-sm">
            <Inbox className="h-10 w-10 text-slate-300 mx-auto" />
            <h4 className="text-sm font-bold text-slate-800">No Surveys in Database</h4>
            <p className="text-xs text-slate-500 max-w-sm mx-auto">
              Your platform currently has no active research panels. Use the button above to add genuine surveys.
            </p>
          </div>
        ) : (
          <Card className="border-slate-200 overflow-hidden">
            <div className="overflow-x-auto">
              <table className="w-full text-left text-xs">
                <thead className="bg-slate-50 border-b border-slate-200 text-slate-500 font-bold uppercase tracking-wider">
                  <tr>
                    <th className="py-3 px-4">Title</th>
                    <th className="py-3 px-4">Category</th>
                    <th className="py-3 px-4">Quota</th>
                    <th className="py-3 px-4">Reward</th>
                    <th className="py-3 px-4">Status</th>
                    <th className="py-3 px-4">Provider</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-slate-100">
                  {surveys.map((c) => (
                    <tr key={c.id} className="hover:bg-slate-50/70 transition-colors">
                      <td className="py-3.5 px-4 font-bold text-slate-900">{c.title}</td>
                      <td className="py-3.5 px-4 text-slate-700">{c.category}</td>
                      <td className="py-3.5 px-4">
                        <span className="font-bold text-slate-900">{c.completedCount}</span>
                        <span className="text-slate-400"> / {c.quota}</span>
                      </td>
                      <td className="py-3.5 px-4 font-bold text-emerald-600">
                        +{c.rewardPoints} Pts (${c.rewardUsd.toFixed(2)})
                      </td>
                      <td className="py-3.5 px-4">
                        <Badge variant={c.status === 'active' ? 'success' : 'secondary'}>
                          {c.status}
                        </Badge>
                      </td>
                      <td className="py-3.5 px-4 text-slate-500">{c.provider}</td>
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
