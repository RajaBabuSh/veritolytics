import React, { useState, useEffect } from 'react';
import { Link, useLocation } from 'react-router-dom';
import { Button } from '@/components/ui/Button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/Card';
import { Badge } from '@/components/ui/Badge';
import {
  Sparkles,
  ArrowRight,
  ShieldCheck,
  Zap,
  Award,
  Clock,
  Coins,
  ChevronDown,
  ChevronUp,
  Inbox,
  UserCheck,
  CheckCircle2,
  Lock,
  Globe2,
  Stethoscope,
  Cpu,
  ShoppingBag,
  TrendingUp,
  Car,
  Briefcase,
  GraduationCap,
  Layers,
  HeartHandshake,
  MessageSquare,
  HelpCircle,
  ExternalLink,
  ShieldAlert,
  ChevronRight,
  Gift,
  CreditCard,
  DollarSign,
  Users2,
} from 'lucide-react';
import { dataService } from '@/services/dataService';
import { Survey } from '@/types';

export const LandingPage: React.FC = () => {
  const [liveSurveys, setLiveSurveys] = useState<Survey[]>([]);
  const [loadingSurveys, setLoadingSurveys] = useState(true);
  const [openFaqIndex, setOpenFaqIndex] = useState<number | null>(null);
  const location = useLocation();

  // Scroll to hash anchor on mount or hash change
  useEffect(() => {
    if (location.hash) {
      const el = document.querySelector(location.hash);
      if (el) {
        setTimeout(() => {
          el.scrollIntoView({ behavior: 'smooth' });
        }, 100);
      }
    }
  }, [location.hash]);

  // Load real published surveys from data service
  useEffect(() => {
    let isMounted = true;
    dataService.getSurveys().then((list) => {
      if (isMounted) {
        setLiveSurveys(list);
        setLoadingSurveys(false);
      }
    }).catch(() => {
      if (isMounted) setLoadingSurveys(false);
    });
    return () => {
      isMounted = false;
    };
  }, []);

  // 4. Trust and Quality Pillars
  const trustPillars = [
    {
      icon: HeartHandshake,
      title: 'Ethical Research',
      subtitle: 'Respect for Participant Time',
      description:
        'We value your time and thoughtful contributions. Every research study features upfront duration expectations, transparent requirements, and fair reward value.',
      accent: 'from-brand-royal-600 to-brand-royal-400',
    },
    {
      icon: ShieldCheck,
      title: 'Data Quality',
      subtitle: 'High-Integrity Methodologies',
      description:
        'Academic and enterprise institutions trust Veritolytics for genuine human perspectives. Quality responses ensure real-world decisions are guided by authentic participant voices.',
      accent: 'from-brand-navy-900 to-brand-royal-700',
    },
    {
      icon: Globe2,
      title: 'Global Research Expertise',
      subtitle: 'Diverse International Reach',
      description:
        'Connecting participants across consumer, professional, and healthcare domains to international research studies shaping products, treatments, and public policy.',
      accent: 'from-brand-violet-600 to-brand-violet-400',
    },
    {
      icon: Lock,
      title: 'Privacy and Transparency',
      subtitle: 'Bank-Grade Data Protection',
      description:
        'Your individual responses are strictly anonymized and aggregated. Personal demographics are encrypted and never sold, leased, or distributed to data brokers.',
      accent: 'from-brand-royal-500 to-brand-violet-500',
    },
  ];

  // 6. Research Categories (7 Cards)
  const researchCategories = [
    {
      id: 'healthcare',
      title: 'Healthcare & Medical Research',
      icon: Stethoscope,
      badge: 'Healthcare & Life Sciences',
      description:
        'Contribute to groundbreaking research evaluating patient care pathways, digital health technologies, medical equipment, and consumer wellness innovations.',
      color: 'text-rose-600 bg-rose-50 border-rose-100',
      iconBg: 'bg-rose-500 text-white',
    },
    {
      id: 'technology',
      title: 'Technology & IT',
      icon: Cpu,
      badge: 'Enterprise & Consumer Tech',
      description:
        'Provide user feedback on cloud computing, enterprise software architectures, developer platforms, AI tools, and cybersecurity solutions.',
      color: 'text-brand-royal-600 bg-brand-royal-50 border-brand-royal-100',
      iconBg: 'bg-brand-royal-600 text-white',
    },
    {
      id: 'consumer',
      title: 'Consumer & Lifestyle',
      icon: ShoppingBag,
      badge: 'B2C & Retail Markets',
      description:
        'Share your daily habits and preferences regarding e-commerce, sustainable packaging, smart home electronics, streaming media, and food & beverage.',
      color: 'text-amber-600 bg-amber-50 border-amber-100',
      iconBg: 'bg-amber-500 text-white',
    },
    {
      id: 'finance',
      title: 'Finance & FinTech',
      icon: TrendingUp,
      badge: 'Banking & Financial Services',
      description:
        'Influence next-generation mobile banking apps, personal wealth management tools, payment gateways, and insurance technologies.',
      color: 'text-emerald-600 bg-emerald-50 border-emerald-100',
      iconBg: 'bg-emerald-600 text-white',
    },
    {
      id: 'automotive',
      title: 'Automotive & Mobility',
      icon: Car,
      badge: 'Mobility & Transportation',
      description:
        'Evaluate electric vehicle adoption, autonomous driving features, smart connected dashboards, urban transit, and logistics systems.',
      color: 'text-sky-600 bg-sky-50 border-sky-100',
      iconBg: 'bg-sky-600 text-white',
    },
    {
      id: 'business',
      title: 'Business & Professional Research',
      icon: Briefcase,
      badge: 'B2B & Executive Decision-Making',
      description:
        'Offer professional insights into workplace collaboration, HR software, procurement pipelines, remote work dynamics, and leadership strategy.',
      color: 'text-indigo-600 bg-indigo-50 border-indigo-100',
      iconBg: 'bg-indigo-600 text-white',
    },
    {
      id: 'education',
      title: 'Education and Other Research',
      icon: GraduationCap,
      badge: 'EdTech & Cross-Disciplinary',
      description:
        'Examine online learning methodologies, educational credentialing, student engagement platforms, and academic social science research.',
      color: 'text-purple-600 bg-purple-50 border-purple-100',
      iconBg: 'bg-purple-600 text-white',
    },
  ];

  // 7. How It Works - Four Steps
  const howItWorksSteps = [
    {
      step: '01',
      title: 'Join Veritolytics',
      tagline: 'Simple, Free Registration',
      description:
        'Create your verified panelist account in under two minutes. No fees, subscriptions, or credit card requirements—ever.',
      icon: UserCheck,
    },
    {
      step: '02',
      title: 'Complete Your Profile',
      tagline: 'Precision Matching',
      description:
        'Share your demographic baseline, hobbies, industry experience, and device usage so we can match you with relevant research cohorts.',
      icon: Layers,
    },
    {
      step: '03',
      title: 'Discover Eligible Research',
      tagline: 'Tailored Study Invitations',
      description:
        'Receive personalized notifications for live studies matching your profile. Review the estimated duration and reward points before starting.',
      icon: Sparkles,
    },
    {
      step: '04',
      title: 'Share Feedback & Receive Rewards',
      tagline: 'Direct Automated Payouts',
      description:
        'Complete surveys thoughtfully. Earn points upon verified submission and redeem them directly for PayPal cash, gift cards, or virtual cards.',
      icon: Award,
    },
  ];

  // 8. Why Veritolytics - Corporate themes translated for panelists
  const whyVeritolytics = [
    {
      title: 'Global Research Experience',
      desc: 'Bringing institutional research rigour to panels worldwide. Connect with studies conducted by multinational organizations and academic pioneers.',
      icon: Globe2,
    },
    {
      title: 'B2B, B2C and Healthcare Depth',
      desc: 'Specialized cohorts across industry verticals mean you are invited to research studies that truly match your lived professional and personal experience.',
      icon: Users2,
    },
    {
      title: 'Quality-Focused Research Operations',
      desc: 'We respect your time by eliminating deceptive screening loops and repetitive questionnaires. We reward thoughtful, high-integrity responses.',
      icon: CheckCircle2,
    },
    {
      title: 'Responsible Data Practices',
      desc: 'Strict participant privacy protocols. Your responses are anonymized for analytical insights; personal identity details remain protected and encrypted.',
      icon: Lock,
    },
    {
      title: 'Professional Research Standards',
      desc: 'Transparent reward formulas: every 100 points equals exactly $1.00 USD with zero hidden administrative fees or redemption markdowns.',
      icon: ShieldCheck,
    },
  ];

  // 11. FAQ Section - 9 Specific Questions
  const faqs = [
    {
      question: 'What is Veritolytics?',
      answer:
        'Veritolytics is a global consumer and professional research platform. We bridge the gap between forward-thinking organizations—ranging from Fortune 500 enterprises to academic researchers—and real people whose verified feedback helps shape the products, services, and technologies of tomorrow.',
    },
    {
      question: 'Is joining free?',
      answer:
        'Yes, joining Veritolytics is 100% free. We will never ask you for membership fees, subscription charges, or financial payment details to participate in research studies.',
    },
    {
      question: 'How do I create an account?',
      answer:
        'Click on the "Join Now" button at the top of the page. You will fill in your name, email address, country, and create a secure password. Once you confirm your email address, your account is activated and ready to match research opportunities.',
    },
    {
      question: 'How do I qualify for research opportunities?',
      answer:
        'Research qualification is based on demographic compatibility (such as your industry, region, tech usage, or interests) set by study sponsors. By completing your profile honestly and thoroughly, our system matches you with surveys for which you are genuinely eligible, reducing screen-outs.',
    },
    {
      question: 'How do survey rewards work?',
      answer:
        'Whenever you complete an eligible survey, reward points are credited to your Veritolytics wallet. Our exchange rate is completely transparent: 100 Points = $1.00 USD. Once you reach the minimum threshold of 1,000 points ($10.00 USD), you can redeem them for digital rewards such as PayPal transfers, Amazon gift cards, Apple cards, or Virtual Visa cards.',
    },
    {
      question: 'How long does a survey take?',
      answer:
        'Study lengths vary depending on the depth of the research topic. Most standard surveys take between 5 to 20 minutes to complete. The estimated duration and points reward are always displayed on the survey card before you begin.',
    },
    {
      question: 'How can I contact support?',
      answer:
        'You can reach our dedicated support team directly by emailing support@veritolytics.com or by using the floating Customer Support Assistant located on the bottom-right corner of your dashboard. Our operations team is available to assist you with any questions or account inquiries.',
    },
    {
      question: 'How is my personal information protected?',
      answer:
        'We adhere to strict data protection standards. Your survey responses are anonymized and aggregated into statistical reports before being shared with research clients. Your personally identifiable information (such as your full name, email, and credentials) is encrypted with 256-bit SSL and is never sold to third-party advertisers or data brokers.',
    },
    {
      question: 'What if I have a problem with a survey?',
      answer:
        'If you encounter a technical glitch, unexpected screen freeze, or topic concern during a survey, please take note of the survey title and submit a ticket via our Customer Support widget or email support@veritolytics.com. Our support team will investigate the issue promptly and review your participation status.',
    },
  ];

  return (
    <div className="flex flex-col bg-white text-slate-900 selection:bg-brand-royal-100 selection:text-brand-royal-900">
      {/* =========================================================================
          3. HERO SECTION
          ========================================================================= */}
      <section className="relative overflow-hidden bg-brand-navy-950 text-white pt-24 pb-28 md:pt-32 md:pb-36 border-b border-brand-navy-900">
        {/* Ambient Gradients and Glow Orbs */}
        <div className="absolute top-0 left-1/2 -translate-x-1/2 w-[800px] h-[450px] bg-brand-royal-600/15 rounded-full blur-[150px] pointer-events-none" />
        <div className="absolute top-1/3 -right-20 w-[450px] h-[450px] bg-brand-violet-600/15 rounded-full blur-[140px] pointer-events-none" />
        <div className="absolute -bottom-10 left-10 w-[350px] h-[350px] bg-brand-royal-500/10 rounded-full blur-[120px] pointer-events-none" />

        {/* Subtle Background Pattern */}
        <div 
          className="absolute inset-0 opacity-[0.03] pointer-events-none"
          style={{
            backgroundImage: `radial-gradient(#ffffff 1px, transparent 1px)`,
            backgroundSize: '32px 32px',
          }}
        />

        <div className="container mx-auto px-4 relative z-10">
          <div className="grid grid-cols-1 lg:grid-cols-12 gap-12 lg:gap-8 items-center">
            
            {/* Hero Left: Text Content & CTAs */}
            <div className="lg:col-span-7 text-center lg:text-left space-y-6">
              {/* Eyebrow Pill */}
              <div className="inline-flex items-center gap-2 rounded-full border border-brand-royal-400/30 bg-brand-navy-900/80 px-4 py-1.5 text-xs font-semibold text-brand-royal-300 backdrop-blur-md">
                <Sparkles className="h-4 w-4 text-brand-violet-400 animate-pulse" />
                <span>Next-Generation Research & Insights Panel</span>
                <span className="flex h-2 w-2 rounded-full bg-emerald-400" />
              </div>

              {/* Exact Tagline & Headline */}
              <h1 className="text-4xl sm:text-5xl md:text-6xl lg:text-6xl font-extrabold tracking-tight leading-[1.1] text-white">
                Your Voice. <span className="text-gradient-light">Your Insights.</span>{' '}
                <span className="text-brand-royal-400">Your Impact.</span>
              </h1>

              {/* Exact Supporting Text */}
              <p className="max-w-2xl mx-auto lg:mx-0 text-base sm:text-lg md:text-xl text-slate-300 font-normal leading-relaxed">
                Join the Veritolytics research community and share your perspectives on products, 
                services, technology and innovations shaping the world.
              </p>

              {/* Primary & Secondary CTAs */}
              <div className="pt-2 flex flex-col sm:flex-row items-center justify-center lg:justify-start gap-4">
                <Link to="/signup" className="w-full sm:w-auto">
                  <Button size="lg" variant="royal" className="w-full sm:w-auto gap-2.5 px-8 text-base font-bold shadow-glow-royal">
                    <span>Join for Free</span>
                    <ArrowRight className="h-5 w-5" />
                  </Button>
                </Link>
                <a href="#how-it-works" className="w-full sm:w-auto">
                  <Button size="lg" variant="outlineDark" className="w-full sm:w-auto px-8 text-base font-semibold">
                    Explore How It Works
                  </Button>
                </a>
              </div>

              {/* Trust Badges Bar (No Fake Statistics) */}
              <div className="pt-8 grid grid-cols-2 sm:grid-cols-4 gap-4 border-t border-brand-navy-800/80 text-left">
                <div>
                  <div className="text-lg font-black text-white flex items-center gap-1.5">
                    <CheckCircle2 className="h-4 w-4 text-emerald-400" />
                    <span>100% Free</span>
                  </div>
                  <div className="text-xs text-slate-400 mt-0.5">No Membership Fees</div>
                </div>
                <div>
                  <div className="text-lg font-black text-brand-royal-300 flex items-center gap-1.5">
                    <Award className="h-4 w-4 text-brand-royal-400" />
                    <span>100 Pts = $1</span>
                  </div>
                  <div className="text-xs text-slate-400 mt-0.5">Clear Point Valuation</div>
                </div>
                <div>
                  <div className="text-lg font-black text-brand-violet-300 flex items-center gap-1.5">
                    <ShieldCheck className="h-4 w-4 text-brand-violet-400" />
                    <span>Encrypted</span>
                  </div>
                  <div className="text-xs text-slate-400 mt-0.5">Anonymized Insights</div>
                </div>
                <div>
                  <div className="text-lg font-black text-white flex items-center gap-1.5">
                    <Zap className="h-4 w-4 text-amber-400" />
                    <span>Direct Payouts</span>
                  </div>
                  <div className="text-xs text-slate-400 mt-0.5">PayPal & Gift Cards</div>
                </div>
              </div>
            </div>

            {/* Hero Right: Original Research, People, Insights & Connected Data Illustration */}
            <div className="lg:col-span-5 relative flex items-center justify-center">
              <div className="relative w-full max-w-[480px] aspect-square rounded-3xl border border-brand-navy-800/90 bg-brand-navy-900/60 p-6 backdrop-blur-xl shadow-2xl overflow-hidden group">
                
                {/* SVG Global Research Grid & Data Nodes */}
                <svg
                  className="absolute inset-0 w-full h-full opacity-60 pointer-events-none"
                  viewBox="0 0 500 500"
                  fill="none"
                  xmlns="http://www.w3.org/2000/svg"
                >
                  <circle cx="250" cy="250" r="210" stroke="rgba(37,99,235,0.2)" strokeDasharray="4 4" />
                  <circle cx="250" cy="250" r="150" stroke="rgba(124,58,237,0.25)" strokeDasharray="3 3" />
                  <circle cx="250" cy="250" r="90" stroke="rgba(37,99,235,0.3)" />

                  {/* Interconnecting Latitudinal and Longitudinal Arcs */}
                  <ellipse cx="250" cy="250" rx="210" ry="70" stroke="rgba(255,255,255,0.06)" />
                  <ellipse cx="250" cy="250" rx="70" ry="210" stroke="rgba(255,255,255,0.06)" />

                  {/* Pulsing Connected Data Nodes */}
                  <g className="animate-pulse">
                    <line x1="250" y1="250" x2="110" y2="140" stroke="rgba(37,99,235,0.4)" strokeWidth="1.5" />
                    <line x1="250" y1="250" x2="390" y2="130" stroke="rgba(124,58,237,0.4)" strokeWidth="1.5" />
                    <line x1="250" y1="250" x2="130" y2="370" stroke="rgba(37,99,235,0.4)" strokeWidth="1.5" />
                    <line x1="250" y1="250" x2="380" y2="360" stroke="rgba(16,185,129,0.4)" strokeWidth="1.5" />
                  </g>

                  <circle cx="110" cy="140" r="6" fill="#3B82F6" />
                  <circle cx="390" cy="130" r="6" fill="#8B5CF6" />
                  <circle cx="130" cy="370" r="6" fill="#60A5FA" />
                  <circle cx="380" cy="360" r="6" fill="#10B981" />
                </svg>

                {/* Central Veritolytics Core Orb */}
                <div className="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 flex flex-col items-center justify-center text-center z-10">
                  <div className="h-20 w-20 rounded-2xl bg-gradient-to-tr from-brand-royal-600 via-brand-royal-500 to-brand-violet-600 p-[2px] shadow-glow-royal animate-pulse">
                    <div className="h-full w-full rounded-2xl bg-brand-navy-950 flex items-center justify-center">
                      <span className="text-2xl font-black text-white tracking-wider">V</span>
                    </div>
                  </div>
                  <span className="mt-2 text-[11px] font-bold tracking-wider uppercase text-slate-300">
                    VERITOLYTICS CORE
                  </span>
                  <span className="text-[10px] text-brand-royal-400">Insights Exchange</span>
                </div>

                {/* Floating Glassmorphic Insight Cards */}
                {/* Top Left Card: Real-time Respondent Validation */}
                <div className="absolute top-6 left-6 z-20 rounded-xl border border-white/10 bg-brand-navy-900/90 p-3 shadow-lg backdrop-blur-md max-w-[190px]">
                  <div className="flex items-center gap-2 mb-1">
                    <div className="h-6 w-6 rounded-lg bg-emerald-500/20 text-emerald-400 flex items-center justify-center">
                      <CheckCircle2 className="h-3.5 w-3.5" />
                    </div>
                    <span className="text-[11px] font-bold text-white">Verified Voice</span>
                  </div>
                  <p className="text-[10px] text-slate-400">Demographic baseline authenticated</p>
                </div>

                {/* Top Right Card: Global Research Opportunity */}
                <div className="absolute top-8 right-6 z-20 rounded-xl border border-white/10 bg-brand-navy-900/90 p-3 shadow-lg backdrop-blur-md max-w-[180px]">
                  <div className="flex items-center gap-2 mb-1">
                    <div className="h-6 w-6 rounded-lg bg-brand-royal-500/20 text-brand-royal-400 flex items-center justify-center">
                      <Cpu className="h-3.5 w-3.5" />
                    </div>
                    <span className="text-[11px] font-bold text-white">Tech Study</span>
                  </div>
                  <div className="flex items-center justify-between text-[10px]">
                    <span className="text-slate-400">15 Min</span>
                    <span className="text-emerald-400 font-bold">250 Pts</span>
                  </div>
                </div>

                {/* Bottom Left Card: Data Privacy Protocol */}
                <div className="absolute bottom-6 left-6 z-20 rounded-xl border border-white/10 bg-brand-navy-900/90 p-3 shadow-lg backdrop-blur-md max-w-[190px]">
                  <div className="flex items-center gap-2 mb-1">
                    <div className="h-6 w-6 rounded-lg bg-brand-violet-500/20 text-brand-violet-400 flex items-center justify-center">
                      <Lock className="h-3.5 w-3.5" />
                    </div>
                    <span className="text-[11px] font-bold text-white">Anonymized</span>
                  </div>
                  <p className="text-[10px] text-slate-400">Aggregated research only</p>
                </div>

                {/* Bottom Right Card: Transparent Value */}
                <div className="absolute bottom-8 right-6 z-20 rounded-xl border border-white/10 bg-brand-navy-900/90 p-3 shadow-lg backdrop-blur-md max-w-[180px]">
                  <div className="flex items-center gap-2 mb-1">
                    <div className="h-6 w-6 rounded-lg bg-amber-500/20 text-amber-400 flex items-center justify-center">
                      <Coins className="h-3.5 w-3.5" />
                    </div>
                    <span className="text-[11px] font-bold text-white">Direct Rewards</span>
                  </div>
                  <span className="text-[10px] text-slate-300 font-medium">PayPal & Gift Cards</span>
                </div>
              </div>
            </div>

          </div>
        </div>
      </section>

      {/* =========================================================================
          4. TRUST AND QUALITY SECTION
          ========================================================================= */}
      <section id="trust" className="py-24 bg-slate-50/80 border-b border-slate-200/80">
        <div className="container mx-auto px-4">
          <div className="text-center max-w-3xl mx-auto mb-16">
            <Badge variant="royal" className="mb-3">Integrity & Quality Standards</Badge>
            <h2 className="text-3xl sm:text-4xl font-extrabold text-slate-900 tracking-tight">
              A Research Platform Grounded in Authentic Values
            </h2>
            <p className="mt-3 text-slate-600 text-base leading-relaxed">
              We hold our participant relationships to the same high standards demanded by our institutional research partners.
            </p>
          </div>

          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            {trustPillars.map((pillar) => {
              const Icon = pillar.icon;
              return (
                <div
                  key={pillar.title}
                  className="rounded-2xl border border-slate-200 bg-white p-7 shadow-xs hover:shadow-lg transition-all duration-300 flex flex-col justify-between"
                >
                  <div>
                    <div className={`mb-5 inline-flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-tr ${pillar.accent} text-white shadow-sm`}>
                      <Icon className="h-6 w-6" />
                    </div>
                    <h3 className="text-lg font-bold text-slate-900 mb-1">
                      {pillar.title}
                    </h3>
                    <div className="text-xs font-semibold text-brand-royal-600 mb-3">
                      {pillar.subtitle}
                    </div>
                    <p className="text-sm text-slate-600 leading-relaxed">
                      {pillar.description}
                    </p>
                  </div>
                </div>
              );
            })}
          </div>
        </div>
      </section>

      {/* =========================================================================
          5. FEATURED RESEARCH OPPORTUNITIES
          ========================================================================= */}
      <section id="surveys" className="py-24 bg-white border-b border-slate-200/80">
        <div className="container mx-auto px-4">
          <div className="flex flex-col md:flex-row md:items-end justify-between mb-12">
            <div>
              <Badge variant="violet" className="mb-2">Real-Time Cohorts</Badge>
              <h2 className="text-3xl sm:text-4xl font-extrabold text-slate-900 tracking-tight">
                Featured Research Opportunities
              </h2>
              <p className="text-slate-600 text-sm mt-1">
                Real published research cohorts actively seeking consumer and professional perspectives.
              </p>
            </div>
            <Link to="/signup" className="mt-4 md:mt-0">
              <Button variant="ghost" className="gap-2 text-brand-royal-600 font-bold hover:text-brand-royal-700">
                <span>View All In Member Portal</span>
                <ChevronRight className="h-4 w-4" />
              </Button>
            </Link>
          </div>

          {loadingSurveys ? (
            <div className="py-16 text-center text-slate-500 text-sm">
              <div className="inline-block h-8 w-8 animate-spin rounded-full border-4 border-solid border-brand-royal-600 border-r-transparent mb-3" />
              <p>Connecting to research cohort registry...</p>
            </div>
          ) : liveSurveys.length === 0 ? (
            /* Clean launch state: No fake surveys displayed as real opportunities */
            <div className="rounded-3xl border border-slate-200 bg-gradient-to-b from-slate-50 to-white p-10 md:p-14 text-center max-w-2xl mx-auto shadow-sm space-y-5">
              <div className="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-brand-royal-50 text-brand-royal-600 border border-brand-royal-100 shadow-xs">
                <Inbox className="h-8 w-8" />
              </div>
              <div className="space-y-2">
                <h3 className="text-xl font-bold text-slate-900">
                  New Opportunities Published Periodically
                </h3>
                <p className="text-sm text-slate-600 leading-relaxed max-w-md mx-auto">
                  We never display invented or placeholder surveys. Enterprise and academic research cohorts are published directly by our research partners. Complete your profile today to receive priority access as soon as matching cohorts open.
                </p>
              </div>
              <div className="pt-2 flex flex-col sm:flex-row items-center justify-center gap-3">
                <Link to="/signup">
                  <Button variant="royal" className="gap-2 font-bold px-6">
                    <UserCheck className="h-4 w-4" />
                    <span>Create Your Free Profile</span>
                  </Button>
                </Link>
                <Link to="/login">
                  <Button variant="outline" className="font-semibold">
                    Sign In
                  </Button>
                </Link>
              </div>
            </div>
          ) : (
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
              {liveSurveys.slice(0, 6).map((survey) => (
                <Card key={survey.id} className="hover:shadow-lg transition-all duration-300 border-slate-200 flex flex-col justify-between">
                  <CardHeader className="pb-3">
                    <div className="flex items-center justify-between mb-2">
                      <Badge variant="secondary" className="font-semibold text-slate-700 text-xs">
                        {survey.category}
                      </Badge>
                      <span className="text-xs text-slate-500 font-medium">
                        {survey.quota - survey.completedCount > 0
                          ? `${survey.quota - survey.completedCount} spots open`
                          : 'Cohort Active'}
                      </span>
                    </div>
                    <CardTitle className="text-lg font-bold text-slate-900 line-clamp-2">
                      {survey.title}
                    </CardTitle>
                    <div className="flex items-center gap-4 pt-2 text-xs text-slate-500">
                      <span className="inline-flex items-center gap-1 font-medium">
                        <Clock className="h-3.5 w-3.5 text-slate-400" />
                        {survey.durationMinutes} min
                      </span>
                      <span className="inline-flex items-center gap-1 text-emerald-600 font-bold">
                        <Coins className="h-3.5 w-3.5" />
                        {survey.rewardPoints} Pts (${survey.rewardUsd.toFixed(2)} USD)
                      </span>
                    </div>
                  </CardHeader>
                  <CardContent className="pt-2">
                    <div className="border-t border-slate-100 pt-3 flex items-center justify-between">
                      <span className="text-xs text-slate-500">
                        {survey.provider || 'Verified Panelists'}
                      </span>
                      <Link to="/signup">
                        <Button size="sm" variant="outline" className="gap-1 font-semibold group hover:bg-brand-royal-600 hover:text-white hover:border-brand-royal-600">
                          <span>View Opportunity</span>
                          <ArrowRight className="h-3.5 w-3.5 transition-transform group-hover:translate-x-0.5" />
                        </Button>
                      </Link>
                    </div>
                  </CardContent>
                </Card>
              ))}
            </div>
          )}
        </div>
      </section>

      {/* =========================================================================
          6. RESEARCH CATEGORIES
          ========================================================================= */}
      <section id="categories" className="py-24 bg-slate-50/70 border-b border-slate-200/80">
        <div className="container mx-auto px-4">
          <div className="text-center max-w-3xl mx-auto mb-16">
            <Badge variant="royal" className="mb-3">Diverse Domains</Badge>
            <h2 className="text-3xl sm:text-4xl font-extrabold text-slate-900 tracking-tight">
              Research Categories You Can Influence
            </h2>
            <p className="mt-3 text-slate-600 text-base leading-relaxed">
              Explore the wide spectrum of industries and research specialties where your perspectives directly inform decision-makers.
            </p>
          </div>

          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            {researchCategories.map((cat) => {
              const Icon = cat.icon;
              return (
                <div
                  key={cat.id}
                  className="rounded-2xl border border-slate-200 bg-white p-7 shadow-xs hover:shadow-lg transition-all duration-300 flex flex-col justify-between group"
                >
                  <div>
                    <div className="flex items-center justify-between mb-5">
                      <div className={`h-12 w-12 rounded-xl flex items-center justify-center shadow-xs ${cat.iconBg}`}>
                        <Icon className="h-6 w-6" />
                      </div>
                      <span className={`text-[11px] font-bold px-2.5 py-1 rounded-full border ${cat.color}`}>
                        {cat.badge}
                      </span>
                    </div>
                    <h3 className="text-lg font-bold text-slate-900 mb-2 group-hover:text-brand-royal-600 transition-colors">
                      {cat.title}
                    </h3>
                    <p className="text-sm text-slate-600 leading-relaxed">
                      {cat.description}
                    </p>
                  </div>
                  <div className="pt-5 mt-5 border-t border-slate-100 flex items-center justify-between text-xs font-semibold text-brand-royal-600">
                    <span>Participate in studies</span>
                    <ArrowRight className="h-4 w-4 transition-transform group-hover:translate-x-1" />
                  </div>
                </div>
              );
            })}
          </div>
        </div>
      </section>

      {/* =========================================================================
          7. HOW IT WORKS (Horizontal on Desktop, Vertical on Mobile)
          ========================================================================= */}
      <section id="how-it-works" className="py-24 bg-white border-b border-slate-200/80">
        <div className="container mx-auto px-4">
          <div className="text-center max-w-3xl mx-auto mb-16">
            <Badge variant="violet" className="mb-3">Frictionless Participation</Badge>
            <h2 className="text-3xl sm:text-4xl font-extrabold text-slate-900 tracking-tight">
              How Veritolytics Works
            </h2>
            <p className="mt-3 text-slate-600 text-base leading-relaxed">
              A transparent four-step process connecting your genuine feedback with verified digital rewards.
            </p>
          </div>

          {/* Desktop Horizontal Timeline / Mobile Vertical Timeline */}
          <div className="relative">
            {/* Desktop Connecting Line */}
            <div className="hidden lg:block absolute top-1/2 left-0 right-0 h-0.5 bg-gradient-to-r from-brand-royal-200 via-brand-violet-200 to-brand-royal-200 -translate-y-12 z-0" />

            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8 relative z-10">
              {howItWorksSteps.map((stepItem) => {
                const Icon = stepItem.icon;
                return (
                  <div
                    key={stepItem.step}
                    className="flex flex-col rounded-2xl border border-slate-200 bg-white p-7 shadow-xs hover:shadow-xl transition-all duration-300 relative group"
                  >
                    {/* Step Number Badge */}
                    <div className="flex items-center justify-between mb-5">
                      <div className="h-12 w-12 rounded-xl bg-brand-royal-600 text-white flex items-center justify-center font-bold shadow-md shadow-brand-royal-600/20 group-hover:scale-105 transition-transform">
                        <Icon className="h-6 w-6" />
                      </div>
                      <span className="text-3xl font-black text-slate-200 group-hover:text-brand-royal-200 transition-colors">
                        {stepItem.step}
                      </span>
                    </div>

                    <h3 className="text-lg font-bold text-slate-900 mb-1">
                      {stepItem.title}
                    </h3>
                    <div className="text-xs font-semibold text-brand-royal-600 mb-2">
                      {stepItem.tagline}
                    </div>
                    <p className="text-sm text-slate-600 leading-relaxed">
                      {stepItem.description}
                    </p>
                  </div>
                );
              })}
            </div>
          </div>

          {/* Call to action underneath How It Works */}
          <div className="mt-14 text-center">
            <Link to="/signup">
              <Button size="lg" variant="royal" className="gap-2 font-bold shadow-glow-royal">
                <span>Start Step 01: Create Free Account</span>
                <ArrowRight className="h-4 w-4" />
              </Button>
            </Link>
          </div>
        </div>
      </section>

      {/* =========================================================================
          8. WHY VERITOLYTICS (Corporate Brand Themes for Panelists)
          ========================================================================= */}
      <section id="why-us" className="py-24 bg-brand-navy-950 text-white border-b border-brand-navy-900 relative overflow-hidden">
        {/* Glow */}
        <div className="absolute top-1/2 left-1/3 -translate-y-1/2 w-[600px] h-[350px] bg-brand-royal-600/10 rounded-full blur-[140px] pointer-events-none" />

        <div className="container mx-auto px-4 relative z-10">
          <div className="text-center max-w-3xl mx-auto mb-16">
            <div className="inline-flex items-center gap-2 rounded-full border border-brand-royal-400/30 bg-brand-navy-900/80 px-4 py-1.5 text-xs font-semibold text-brand-royal-300 backdrop-blur-md mb-3">
              <ShieldCheck className="h-3.5 w-3.5 text-brand-royal-400" />
              <span>The Veritolytics Distinction</span>
            </div>
            <h2 className="text-3xl sm:text-4xl font-extrabold tracking-tight">
              Why Panelists Trust Veritolytics
            </h2>
            <p className="mt-3 text-slate-300 text-base leading-relaxed">
              We apply enterprise-grade research standards to create an equitable, transparent panelist community.
            </p>
          </div>

          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            {whyVeritolytics.map((item) => {
              const Icon = item.icon;
              return (
                <div
                  key={item.title}
                  className="rounded-2xl border border-brand-navy-800 bg-brand-navy-900/60 p-7 backdrop-blur-sm hover:border-brand-royal-500/50 transition-all duration-300 space-y-3"
                >
                  <div className="h-11 w-11 rounded-xl bg-brand-royal-600/20 text-brand-royal-400 border border-brand-royal-500/30 flex items-center justify-center">
                    <Icon className="h-5 w-5" />
                  </div>
                  <h3 className="text-lg font-bold text-white">
                    {item.title}
                  </h3>
                  <p className="text-sm text-slate-400 leading-relaxed">
                    {item.desc}
                  </p>
                </div>
              );
            })}
          </div>
        </div>
      </section>

      {/* =========================================================================
          9. REWARDS SECTION
          ========================================================================= */}
      <section id="rewards" className="py-24 bg-white border-b border-slate-200/80">
        <div className="container mx-auto px-4">
          <div className="grid grid-cols-1 lg:grid-cols-12 gap-12 items-center">
            
            {/* Left Column: Reward Explanation */}
            <div className="lg:col-span-6 space-y-6">
              <Badge variant="royal">Transparent Rewards</Badge>
              <h2 className="text-3xl sm:text-4xl font-extrabold text-slate-900 tracking-tight leading-tight">
                Clear Point Values. Direct Digital Cashouts.
              </h2>
              <p className="text-slate-600 text-base leading-relaxed">
                We believe in simple, predictable rewards. We never obscure payout values behind variable point conversion scales or unexpected administrative withdrawal deductions.
              </p>

              {/* Exact Transparent Formula */}
              <div className="rounded-2xl border border-brand-royal-100 bg-brand-royal-50/50 p-6 space-y-4">
                <div className="flex items-center gap-4">
                  <div className="h-12 w-12 rounded-xl bg-brand-royal-600 text-white flex items-center justify-center font-bold text-xl shadow-xs">
                    $
                  </div>
                  <div>
                    <div className="text-lg font-bold text-slate-900">100 Points = $1.00 USD</div>
                    <div className="text-xs text-slate-500">Guaranteed fixed baseline exchange rate</div>
                  </div>
                </div>
                <div className="border-t border-brand-royal-100 pt-3 text-xs text-slate-600 leading-relaxed">
                  Redeem starting at <strong>1,000 points ($10.00 USD)</strong> directly to your preferred digital payout gateway.
                </div>
              </div>

              <div className="pt-2">
                <Link to="/rewards">
                  <Button variant="royal" size="lg" className="gap-2 font-bold shadow-md">
                    <Gift className="h-4 w-4" />
                    <span>View Rewards Catalog</span>
                    <ArrowRight className="h-4 w-4" />
                  </Button>
                </Link>
              </div>
            </div>

            {/* Right Column: Supported Payout Channels */}
            <div className="lg:col-span-6">
              <div className="rounded-3xl border border-slate-200 bg-slate-50/70 p-8 space-y-6">
                <h3 className="text-lg font-bold text-slate-900 flex items-center gap-2">
                  <CreditCard className="h-5 w-5 text-brand-royal-600" />
                  <span>Verified Redemption Gateways</span>
                </h3>

                <div className="grid grid-cols-2 gap-4">
                  <div className="rounded-xl border border-slate-200 bg-white p-4 shadow-2xs space-y-1">
                    <div className="font-bold text-slate-900 text-sm">PayPal Transfer</div>
                    <div className="text-xs text-slate-500">Direct electronic cash to your verified PayPal account.</div>
                  </div>
                  <div className="rounded-xl border border-slate-200 bg-white p-4 shadow-2xs space-y-1">
                    <div className="font-bold text-slate-900 text-sm">Amazon Gift Card</div>
                    <div className="text-xs text-slate-500">Instant digital claim code redeemable at Amazon.com.</div>
                  </div>
                  <div className="rounded-xl border border-slate-200 bg-white p-4 shadow-2xs space-y-1">
                    <div className="font-bold text-slate-900 text-sm">Apple Gift Card</div>
                    <div className="text-xs text-slate-500">Applicable to App Store, Apple Music, and devices.</div>
                  </div>
                  <div className="rounded-xl border border-slate-200 bg-white p-4 shadow-2xs space-y-1">
                    <div className="font-bold text-slate-900 text-sm">Virtual Visa Card</div>
                    <div className="text-xs text-slate-500">Prepaid virtual debit card usable online globally.</div>
                  </div>
                </div>

                <div className="text-xs text-slate-500 bg-white rounded-xl p-3 border border-slate-200/80 flex items-center gap-2">
                  <CheckCircle2 className="h-4 w-4 text-emerald-500 flex-shrink-0" />
                  <span>Digital reward codes are generated automatically upon approved redemption.</span>
                </div>
              </div>
            </div>

          </div>
        </div>
      </section>

      {/* =========================================================================
          10. MEMBER EXPERIENCES (Authentic Component - Zero Fabricated Reviews)
          ========================================================================= */}
      <section id="experiences" className="py-24 bg-slate-50/80 border-b border-slate-200/80">
        <div className="container mx-auto px-4">
          <div className="text-center max-w-3xl mx-auto mb-16">
            <Badge variant="violet" className="mb-3">Panelist Community</Badge>
            <h2 className="text-3xl sm:text-4xl font-extrabold text-slate-900 tracking-tight">
              The Member Experience
            </h2>
            <p className="mt-3 text-slate-600 text-base leading-relaxed">
              We cultivate a community founded on mutual respect, transparent rules, and genuine impact.
            </p>
          </div>

          <div className="grid grid-cols-1 md:grid-cols-3 gap-8 max-w-5xl mx-auto">
            <div className="rounded-2xl border border-slate-200 bg-white p-8 shadow-xs flex flex-col justify-between">
              <div>
                <div className="h-10 w-10 rounded-xl bg-brand-royal-50 text-brand-royal-600 flex items-center justify-center mb-4">
                  <MessageSquare className="h-5 w-5" />
                </div>
                <h3 className="text-lg font-bold text-slate-900 mb-2">
                  Real Influence on Real Products
                </h3>
                <p className="text-sm text-slate-600 leading-relaxed">
                  Your feedback does not vanish into a black box. Research sponsors actively analyze cohort insights to guide feature roadmaps, healthcare accessibility, and market innovations.
                </p>
              </div>
              <div className="mt-6 pt-4 border-t border-slate-100 text-xs font-semibold text-brand-royal-600">
                Verified Research Impact
              </div>
            </div>

            <div className="rounded-2xl border border-slate-200 bg-white p-8 shadow-xs flex flex-col justify-between">
              <div>
                <div className="h-10 w-10 rounded-xl bg-brand-violet-50 text-brand-violet-600 flex items-center justify-center mb-4">
                  <Coins className="h-5 w-5" />
                </div>
                <h3 className="text-lg font-bold text-slate-900 mb-2">
                  Transparent Reward Delivery
                </h3>
                <p className="text-sm text-slate-600 leading-relaxed">
                  No misleading games, no bait-and-switch point balances, and no hidden deductions. Complete surveys thoughtfully, earn points, and redeem for digital value.
                </p>
              </div>
              <div className="mt-6 pt-4 border-t border-slate-100 text-xs font-semibold text-brand-violet-600">
                Predictable Settlement
              </div>
            </div>

            <div className="rounded-2xl border border-slate-200 bg-white p-8 shadow-xs flex flex-col justify-between">
              <div>
                <div className="h-10 w-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center mb-4">
                  <HeartHandshake className="h-5 w-5" />
                </div>
                <h3 className="text-lg font-bold text-slate-900 mb-2">
                  Community Feedback Welcomed
                </h3>
                <p className="text-sm text-slate-600 leading-relaxed">
                  We actively seek panelist suggestions to improve questionnaire clarity, screening efficiency, and dashboard convenience.
                </p>
              </div>
              <div className="mt-6 pt-4 border-t border-slate-100 text-xs font-semibold text-emerald-600">
                Continuous Panel Improvement
              </div>
            </div>
          </div>

          <div className="mt-12 text-center text-xs text-slate-500">
            <span>Are you an active panelist? Reach our operations desk anytime via </span>
            <a href="mailto:support@veritolytics.com" className="text-brand-royal-600 font-semibold underline">
              support@veritolytics.com
            </a>
          </div>
        </div>
      </section>

      {/* =========================================================================
          11. FAQ SECTION (Accordion with Anchor ID "faq" and Exact 9 Questions)
          ========================================================================= */}
      <section id="faq" className="py-24 bg-white border-b border-slate-200/80">
        <div className="container mx-auto px-4 max-w-4xl">
          <div className="text-center mb-16">
            <Badge variant="royal" className="mb-3">Answers & Clarity</Badge>
            <h2 className="text-3xl sm:text-4xl font-extrabold text-slate-900 tracking-tight">
              Frequently Asked Questions
            </h2>
            <p className="mt-3 text-slate-600 text-base">
              Everything you need to know about joining and participating in Veritolytics.
            </p>
          </div>

          {/* Interactive Accordion */}
          <div className="space-y-4">
            {faqs.map((faq, index) => {
              const isOpen = openFaqIndex === index;
              return (
                <div
                  key={faq.question}
                  className={`rounded-2xl border transition-all duration-200 overflow-hidden ${
                    isOpen
                      ? 'border-brand-royal-300 bg-brand-royal-50/20 shadow-sm'
                      : 'border-slate-200 bg-white hover:border-slate-300'
                  }`}
                >
                  <button
                    onClick={() => setOpenFaqIndex(isOpen ? null : index)}
                    className="w-full flex items-center justify-between p-6 text-left transition-colors focus:outline-none"
                    aria-expanded={isOpen}
                  >
                    <span className="font-bold text-slate-900 text-base sm:text-lg pr-4">
                      {faq.question}
                    </span>
                    <div className={`h-8 w-8 rounded-full flex items-center justify-center flex-shrink-0 transition-transform ${
                      isOpen ? 'bg-brand-royal-600 text-white rotate-180' : 'bg-slate-100 text-slate-600'
                    }`}>
                      <ChevronDown className="h-4 w-4" />
                    </div>
                  </button>
                  {isOpen && (
                    <div className="px-6 pb-6 pt-1 text-slate-600 text-sm leading-relaxed border-t border-slate-100 animate-fadeIn">
                      {faq.answer}
                    </div>
                  )}
                </div>
              );
            })}
          </div>

          {/* Help Callout */}
          <div className="mt-12 text-center bg-slate-50 rounded-2xl p-6 border border-slate-200 flex flex-col sm:flex-row items-center justify-between gap-4">
            <div className="text-left">
              <div className="font-bold text-slate-900 text-sm">Have more questions?</div>
              <div className="text-xs text-slate-500">Our support desk is always here to help you.</div>
            </div>
            <a href="mailto:support@veritolytics.com">
              <Button variant="outline" size="sm" className="font-semibold text-slate-800">
                Contact Support Desk
              </Button>
            </a>
          </div>
        </div>
      </section>

      {/* =========================================================================
          12. FINAL CTA
          ========================================================================= */}
      <section className="py-24 bg-gradient-to-b from-white to-slate-50">
        <div className="container mx-auto px-4">
          <div className="relative overflow-hidden rounded-3xl bg-gradient-to-br from-brand-navy-950 via-brand-navy-900 to-brand-royal-950 p-10 md:p-16 text-white text-center shadow-2xl border border-brand-navy-800">
            {/* Ambient Background Glow */}
            <div className="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[600px] h-[300px] bg-brand-royal-600/20 rounded-full blur-[140px] pointer-events-none" />

            <div className="relative z-10 max-w-3xl mx-auto space-y-6">
              <Badge variant="violet" className="bg-brand-violet-950/80 text-brand-violet-300 border-brand-violet-700/50">
                Join Our Global Network
              </Badge>

              {/* Exact Requested Headline & Supporting Text */}
              <h2 className="text-3xl sm:text-5xl font-extrabold tracking-tight text-white leading-tight">
                Your Perspective Matters.
              </h2>
              <p className="text-slate-300 text-base sm:text-xl max-w-xl mx-auto leading-relaxed">
                Become part of a global research community.
              </p>

              {/* Exact Requested CTA */}
              <div className="pt-4 flex flex-col sm:flex-row items-center justify-center gap-4">
                <Link to="/signup" className="w-full sm:w-auto">
                  <Button size="lg" variant="royal" className="w-full sm:w-auto px-9 text-base font-bold shadow-glow-royal gap-2">
                    <span>Join Veritolytics Today</span>
                    <ArrowRight className="h-5 w-5" />
                  </Button>
                </Link>
                <Link to="/login" className="w-full sm:w-auto">
                  <Button size="lg" variant="outlineDark" className="w-full sm:w-auto px-8 text-base font-semibold">
                    Member Login
                  </Button>
                </Link>
              </div>

              <div className="pt-6 text-xs text-slate-400">
                <span>Free Registration • No Obligation • Encrypted Responses</span>
              </div>
            </div>
          </div>
        </div>
      </section>
    </div>
  );
};
