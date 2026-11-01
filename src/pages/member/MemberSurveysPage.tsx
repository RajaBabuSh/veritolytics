import React, { useState, useEffect } from 'react';
import { useOutletContext, Link } from 'react-router-dom';
import { DashboardHeader } from '@/components/navigation/DashboardHeader';
import { Button } from '@/components/ui/Button';
import { Badge } from '@/components/ui/Badge';
import { Clock, Coins, FileQuestion, UserCheck, CheckCircle2, AlertCircle } from 'lucide-react';
import { useAuth } from '@/context/AuthContext';
import { dataService } from '@/services/dataService';
import { Survey, UserProfile } from '@/types';

export const MemberSurveysPage: React.FC = () => {
  const { openMobileSidebar } = useOutletContext<{ openMobileSidebar: () => void }>();
  const { user } = useAuth();

  const [profile, setProfile] = useState<UserProfile | null>(null);
  const [surveys, setSurveys] = useState<Survey[]>([]);
  const [selectedCategory, setSelectedCategory] = useState('All');
  const [loading, setLoading] = useState(true);
  const [activeSurveyNotice, setActiveSurveyNotice] = useState<string | null>(null);

  useEffect(() => {
    let isMounted = true;
    async function loadData() {
      if (!user) return;
      try {
        const p = await dataService.getUserProfile(user.id, user.user_metadata, user.email);
        const s = await dataService.getSurveys();
        if (isMounted) {
          setProfile(p);
          setSurveys(s);
          setLoading(false);
        }
      } catch (err) {
        console.error('Error loading surveys:', err);
        if (isMounted) setLoading(false);
      }
    }
    loadData();
    return () => {
      isMounted = false;
    };
  }, [user]);

  const categories = ['All', 'Technology', 'Fintech', 'Consumer Insights', 'Healthcare', 'Gaming & Media'];

  const filteredSurveys =
    selectedCategory === 'All'
      ? surveys
      : surveys.filter((s) => s.category.toLowerCase() === selectedCategory.toLowerCase());

  const handleStartSurvey = (surveyId: string, title: string) => {
    setActiveSurveyNotice(`Survey "${title}" initialized. Follow the questionnaire instructions.`);
    setTimeout(() => {
      setActiveSurveyNotice(null);
    }, 4000);
  };

  return (
    <div className="flex-1 pb-16">
      <DashboardHeader
        title="Survey Marketplace"
        subtitle="Genuine research panels published by verified corporate & academic partners."
        onOpenMobileSidebar={openMobileSidebar}
        balancePoints={profile?.balancePoints || 0}
      />

      <div className="p-4 sm:p-8 max-w-7xl mx-auto space-y-6">
        {/* Category Filter Navigation */}
        <div className="flex items-center gap-2 overflow-x-auto pb-2">
          {categories.map((cat) => (
            <button
              key={cat}
              onClick={() => setSelectedCategory(cat)}
              className={`rounded-xl px-4 py-2 text-xs font-semibold whitespace-nowrap transition-all ${
                selectedCategory === cat
                  ? 'bg-brand-royal-600 text-white shadow-md shadow-brand-royal-600/20'
                  : 'bg-white border border-slate-200 text-slate-600 hover:bg-slate-50'
              }`}
            >
              {cat}
            </button>
          ))}
        </div>

        {activeSurveyNotice && (
          <div className="flex items-center gap-2.5 rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-xs font-bold text-emerald-800 shadow-sm animate-fadeIn">
            <CheckCircle2 className="h-4 w-4 text-emerald-600" />
            <span>{activeSurveyNotice}</span>
          </div>
        )}

        {/* Survey Listing or Genuine Professional Empty State */}
        {filteredSurveys.length === 0 ? (
          <div className="rounded-2xl border border-slate-200 bg-white p-10 sm:p-16 text-center space-y-4 shadow-sm">
            <div className="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-slate-50 text-slate-400 border border-slate-100">
              <FileQuestion className="h-8 w-8 text-brand-royal-500" />
            </div>

            <div className="space-y-1.5 max-w-md mx-auto">
              <h3 className="text-lg font-bold text-slate-900">
                No Surveys Available Yet
              </h3>
              <p className="text-xs text-slate-500 leading-relaxed">
                There are currently no active survey panels published
                {selectedCategory !== 'All' ? ` in the "${selectedCategory}" category` : ''}.
                We do not display simulated or fake surveys. Verified research studies will appear here as soon as they are launched by administrators.
              </p>
            </div>

            <div className="pt-3 flex flex-col sm:flex-row items-center justify-center gap-3">
              <Link to="/member/settings">
                <Button variant="default" size="sm" className="gap-2 text-xs font-bold">
                  <UserCheck className="h-4 w-4" />
                  <span>Update Profile & Demographics</span>
                </Button>
              </Link>
              {selectedCategory !== 'All' && (
                <Button
                  variant="outline"
                  size="sm"
                  className="text-xs"
                  onClick={() => setSelectedCategory('All')}
                >
                  Clear Category Filter
                </Button>
              )}
            </div>
          </div>
        ) : (
          <div className="space-y-4">
            {filteredSurveys.map((survey) => (
              <div
                key={survey.id}
                className="flex flex-col md:flex-row md:items-center justify-between gap-5 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm hover:border-brand-royal-200 hover:shadow-md transition-all"
              >
                <div className="space-y-2 max-w-2xl">
                  <div className="flex items-center gap-2.5">
                    <Badge variant="royal">{survey.category}</Badge>
                    <span className="text-xs text-slate-400">By {survey.provider}</span>
                    <span className="text-[11px] font-medium text-slate-500 bg-slate-100 px-2.5 py-0.5 rounded-full">
                      Quota: {survey.quota - survey.completedCount} spots left
                    </span>
                  </div>

                  <h3 className="text-base sm:text-lg font-bold text-slate-900">
                    {survey.title}
                  </h3>

                  <div className="flex items-center gap-4 text-xs text-slate-500 font-medium">
                    <span className="flex items-center gap-1.5">
                      <Clock className="h-4 w-4 text-slate-400" />
                      Estimated: {survey.durationMinutes} min
                    </span>
                    <span>•</span>
                    <span className="text-emerald-600 font-bold flex items-center gap-1">
                      <Coins className="h-4 w-4 text-emerald-500" />
                      +{survey.rewardPoints} Points (${survey.rewardUsd.toFixed(2)})
                    </span>
                  </div>
                </div>

                <Button
                  variant="royal"
                  size="sm"
                  onClick={() => handleStartSurvey(survey.id, survey.title)}
                  className="w-full md:w-auto font-bold px-6 shadow-sm"
                >
                  Start Survey
                </Button>
              </div>
            ))}
          </div>
        )}
      </div>
    </div>
  );
};
