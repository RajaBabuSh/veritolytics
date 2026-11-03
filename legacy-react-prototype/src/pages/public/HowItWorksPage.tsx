import React from 'react';
import { Link } from 'react-router-dom';
import { Button } from '@/components/ui/Button';
import { Badge } from '@/components/ui/Badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/Card';
import { UserCheck, Sliders, CheckCircle, Wallet, ArrowRight, Shield, Zap } from 'lucide-react';

export const HowItWorksPage: React.FC = () => {
  const steps = [
    {
      step: '01',
      title: 'Build Your Verified Profile',
      desc: 'Answer our fast onboarding questionnaire to establish your demographic baseline and VeritoScore™. This ensures 98%+ match accuracy so you never waste time.',
      icon: UserCheck,
      color: 'bg-brand-royal-600',
    },
    {
      step: '02',
      title: 'Take Precision Research Panels',
      desc: 'Receive invites matching your career, gadgets, habits, and financial preferences. Every survey explicitly lists payout, duration, and topic beforehand.',
      icon: Sliders,
      color: 'bg-brand-violet-600',
    },
    {
      step: '03',
      title: 'Instant Automated Settlement',
      desc: 'As soon as your final answer is submitted, reward points credit immediately to your verified wallet. No waiting for 30-day corporate audit cycles.',
      icon: Wallet,
      color: 'bg-brand-navy-900',
    },
  ];

  return (
    <div className="py-16 md:py-24">
      <div className="container mx-auto px-4 max-w-5xl">
        <div className="text-center max-w-2xl mx-auto mb-16">
          <Badge variant="royal" className="mb-2">Transparent Process</Badge>
          <h1 className="text-3xl sm:text-5xl font-extrabold text-slate-900 tracking-tight">
            How VERITOLYTICS Works
          </h1>
          <p className="mt-4 text-slate-600 text-base sm:text-lg">
            A frictionless three-step pipeline engineered for data integrity and respondent satisfaction.
          </p>
        </div>

        {/* Steps Grid */}
        <div className="grid grid-cols-1 md:grid-cols-3 gap-8 relative">
          {steps.map((item, idx) => {
            const Icon = item.icon;
            return (
              <div key={item.step} className="flex flex-col rounded-2xl border border-slate-200 bg-white p-7 shadow-sm hover:shadow-lg transition-all">
                <div className="flex items-center justify-between mb-6">
                  <div className={`h-12 w-12 rounded-xl ${item.color} flex items-center justify-center text-white shadow-md`}>
                    <Icon className="h-6 w-6" />
                  </div>
                  <span className="text-3xl font-black text-slate-200">{item.step}</span>
                </div>
                <h3 className="text-xl font-bold text-slate-900 mb-2">{item.title}</h3>
                <p className="text-sm text-slate-600 leading-relaxed flex-1">{item.desc}</p>
              </div>
            );
          })}
        </div>

        {/* Trust FAQ Preview Card */}
        <div className="mt-16 rounded-2xl border border-brand-royal-100 bg-brand-royal-50/50 p-8 text-center sm:text-left flex flex-col sm:flex-row items-center justify-between gap-6">
          <div>
            <h4 className="text-xl font-bold text-slate-900">Have questions about payouts or privacy?</h4>
            <p className="text-sm text-slate-600 mt-1">We never sell personally identifiable information. All telemetry is aggregated and encrypted.</p>
          </div>
          <Link to="/member">
            <Button variant="royal" className="shrink-0 gap-2">
              <span>Experience It Live</span>
              <ArrowRight className="h-4 w-4" />
            </Button>
          </Link>
        </div>
      </div>
    </div>
  );
};
