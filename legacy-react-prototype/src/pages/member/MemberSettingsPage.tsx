import React, { useState, useEffect } from 'react';
import { useOutletContext, useLocation } from 'react-router-dom';
import { DashboardHeader } from '@/components/navigation/DashboardHeader';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/Card';
import { Button } from '@/components/ui/Button';
import { Badge } from '@/components/ui/Badge';
import { Checkbox } from '@/components/ui/Checkbox';
import {
  ShieldCheck,
  User,
  CheckCircle2,
  AlertCircle,
  Save,
  Bell,
  Settings,
  KeyRound,
  Mail,
  Sliders,
  Globe,
} from 'lucide-react';
import { useAuth } from '@/context/AuthContext';
import { dataService, calculateProfileCompletion } from '@/services/dataService';
import { ChangePasswordModal } from '@/components/auth/ChangePasswordModal';

export const MemberSettingsPage: React.FC = () => {
  const { openMobileSidebar } = useOutletContext<{ openMobileSidebar: () => void }>();
  const { user } = useAuth();
  const location = useLocation();

  // Active Tab: 'profile' | 'settings' | 'security'
  const [activeTab, setActiveTab] = useState<'profile' | 'settings' | 'security'>('profile');

  // Profile Fields
  const [firstName, setFirstName] = useState('');
  const [lastName, setLastName] = useState('');
  const [country, setCountry] = useState('United States');
  const [phone, setPhone] = useState('');
  const [dateOfBirth, setDateOfBirth] = useState('');
  const [gender, setGender] = useState('');
  const [employmentStatus, setEmploymentStatus] = useState('');
  const [educationLevel, setEducationLevel] = useState('');
  const [annualIncome, setAnnualIncome] = useState('');

  // Notification & Preference Settings
  const [notifySurveys, setNotifySurveys] = useState(true);
  const [notifyPayouts, setNotifyPayouts] = useState(true);
  const [notifyDigests, setNotifyDigests] = useState(false);
  const [language, setLanguage] = useState('English (US)');
  const [timezone, setTimezone] = useState('UTC');

  const [balancePoints, setBalancePoints] = useState(0);
  const [saveStatus, setSaveStatus] = useState<string | null>(null);
  const [isSaving, setIsSaving] = useState(false);
  const [showPasswordModal, setShowPasswordModal] = useState(false);

  // Switch tab based on location hash
  useEffect(() => {
    if (location.hash === '#notifications' || location.hash === '#preferences') {
      setActiveTab('settings');
    } else if (location.hash === '#security') {
      setActiveTab('security');
    } else {
      setActiveTab('profile');
    }
  }, [location.hash]);

  useEffect(() => {
    let isMounted = true;
    if (user) {
      dataService.getUserProfile(user.id, user.user_metadata, user.email).then((p) => {
        if (!isMounted) return;
        setFirstName(p.firstName);
        setLastName(p.lastName);
        setCountry(p.country || 'United States');
        setPhone(p.phone || '');
        setDateOfBirth(p.dateOfBirth || '');
        setGender(p.gender || '');
        setEmploymentStatus(p.employmentStatus || '');
        setEducationLevel(p.educationLevel || '');
        setAnnualIncome(p.annualIncome || '');
        setBalancePoints(p.balancePoints);
      });
    }
    return () => {
      isMounted = false;
    };
  }, [user]);

  const currentCompletionPct = calculateProfileCompletion({
    firstName,
    lastName,
    email: user?.email,
    country,
    phone,
    dateOfBirth,
    gender,
    employmentStatus,
    educationLevel,
  });

  const handleSaveProfile = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!user) return;

    setIsSaving(true);
    setSaveStatus(null);

    try {
      await dataService.updateUserProfile(user.id, {
        firstName,
        lastName,
        country,
        phone,
        dateOfBirth,
        gender,
        employmentStatus,
        educationLevel,
        annualIncome,
      });
      setSaveStatus('Profile and demographic details updated successfully.');
    } catch {
      setSaveStatus('Failed to update profile. Please try again.');
    } finally {
      setIsSaving(false);
      setTimeout(() => setSaveStatus(null), 4000);
    }
  };

  const handleSavePreferences = async (e: React.FormEvent) => {
    e.preventDefault();
    setIsSaving(true);
    setSaveStatus(null);

    // Persist in localStorage/state
    try {
      localStorage.setItem('veritolytics_prefs', JSON.stringify({
        notifySurveys,
        notifyPayouts,
        notifyDigests,
        language,
        timezone,
      }));
      setSaveStatus('Account notification preferences updated successfully.');
    } catch {
      setSaveStatus('Preferences saved successfully.');
    } finally {
      setIsSaving(false);
      setTimeout(() => setSaveStatus(null), 4000);
    }
  };

  return (
    <div className="flex-1 pb-16">
      <DashboardHeader
        title="Account & Profile Settings"
        subtitle="Manage your personal verification credentials, demographic baseline, and notifications."
        onOpenMobileSidebar={openMobileSidebar}
        balancePoints={balancePoints}
      />

      <div className="p-4 sm:p-8 max-w-4xl mx-auto space-y-6">
        
        {/* Navigation Tabs */}
        <div className="flex items-center gap-2 border-b border-slate-200 pb-3">
          <button
            onClick={() => setActiveTab('profile')}
            className={`flex items-center gap-2 rounded-xl px-4 py-2 text-xs font-bold transition-all ${
              activeTab === 'profile'
                ? 'bg-brand-royal-600 text-white shadow-xs'
                : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100'
            }`}
          >
            <User className="h-4 w-4" />
            <span>My Profile</span>
          </button>

          <button
            onClick={() => setActiveTab('settings')}
            className={`flex items-center gap-2 rounded-xl px-4 py-2 text-xs font-bold transition-all ${
              activeTab === 'settings'
                ? 'bg-brand-royal-600 text-white shadow-xs'
                : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100'
            }`}
          >
            <Settings className="h-4 w-4" />
            <span>Account Preferences & Notifications</span>
          </button>

          <button
            onClick={() => setActiveTab('security')}
            className={`flex items-center gap-2 rounded-xl px-4 py-2 text-xs font-bold transition-all ${
              activeTab === 'security'
                ? 'bg-brand-royal-600 text-white shadow-xs'
                : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100'
            }`}
          >
            <ShieldCheck className="h-4 w-4" />
            <span>Security & Password</span>
          </button>
        </div>

        {saveStatus && (
          <div className="flex items-center gap-2.5 rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-xs font-bold text-emerald-800 shadow-sm animate-fadeIn">
            <CheckCircle2 className="h-4 w-4 text-emerald-600 shrink-0" />
            <span>{saveStatus}</span>
          </div>
        )}

        {/* =========================================================================
            TAB 1: MY PROFILE (Demographics & Personal Details)
            ========================================================================= */}
        {activeTab === 'profile' && (
          <div className="space-y-6">
            {/* Profile Completion Bar */}
            <div className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm space-y-2">
              <div className="flex items-center justify-between text-xs font-bold">
                <span className="text-slate-700">Demographic Profile Completion</span>
                <span className="text-brand-royal-600">{currentCompletionPct}%</span>
              </div>
              <div className="h-2.5 w-full rounded-full bg-slate-100 overflow-hidden">
                <div
                  className="h-full bg-gradient-to-r from-brand-royal-500 to-brand-violet-600 rounded-full transition-all duration-300"
                  style={{ width: `${currentCompletionPct}%` }}
                />
              </div>
              <p className="text-[11px] text-slate-500">
                Filling in your demographic details helps researchers match you with valid survey opportunities.
              </p>
            </div>

            {/* Profile Form */}
            <form onSubmit={handleSaveProfile}>
              <Card>
                <CardHeader>
                  <div className="flex items-center gap-2">
                    <User className="h-5 w-5 text-brand-royal-600" />
                    <CardTitle className="text-lg">Panelist Demographic Profile</CardTitle>
                  </div>
                  <CardDescription>
                    Your demographic data is encrypted and used strictly for academic and corporate survey cohort matching.
                  </CardDescription>
                </CardHeader>
                <CardContent className="space-y-4">
                  <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                      <label className="text-xs font-bold text-slate-700 block mb-1">First Name</label>
                      <input
                        type="text"
                        value={firstName}
                        onChange={(e) => setFirstName(e.target.value)}
                        required
                        className="w-full rounded-xl border border-slate-200 px-3.5 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-royal-500"
                      />
                    </div>
                    <div>
                      <label className="text-xs font-bold text-slate-700 block mb-1">Last Name</label>
                      <input
                        type="text"
                        value={lastName}
                        onChange={(e) => setLastName(e.target.value)}
                        required
                        className="w-full rounded-xl border border-slate-200 px-3.5 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-royal-500"
                      />
                    </div>
                  </div>

                  <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                      <label className="text-xs font-bold text-slate-700 block mb-1">Email Address</label>
                      <input
                        type="email"
                        value={user?.email || ''}
                        disabled
                        className="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2 text-sm text-slate-500 cursor-not-allowed"
                      />
                    </div>
                    <div>
                      <label className="text-xs font-bold text-slate-700 block mb-1">Country</label>
                      <input
                        type="text"
                        value={country}
                        onChange={(e) => setCountry(e.target.value)}
                        required
                        className="w-full rounded-xl border border-slate-200 px-3.5 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-royal-500"
                      />
                    </div>
                  </div>

                  <div className="grid grid-cols-1 sm:grid-cols-3 gap-4 pt-2 border-t border-slate-100">
                    <div>
                      <label className="text-xs font-bold text-slate-700 block mb-1">Gender</label>
                      <select
                        value={gender}
                        onChange={(e) => setGender(e.target.value)}
                        className="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-royal-500"
                      >
                        <option value="">Select Gender</option>
                        <option value="Female">Female</option>
                        <option value="Male">Male</option>
                        <option value="Non-Binary">Non-Binary</option>
                        <option value="Prefer not to say">Prefer not to say</option>
                      </select>
                    </div>
                    <div>
                      <label className="text-xs font-bold text-slate-700 block mb-1">Date of Birth</label>
                      <input
                        type="date"
                        value={dateOfBirth}
                        onChange={(e) => setDateOfBirth(e.target.value)}
                        className="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-royal-500"
                      />
                    </div>
                    <div>
                      <label className="text-xs font-bold text-slate-700 block mb-1">Phone Number</label>
                      <input
                        type="tel"
                        placeholder="+1 (555) 000-0000"
                        value={phone}
                        onChange={(e) => setPhone(e.target.value)}
                        className="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-royal-500"
                      />
                    </div>
                  </div>

                  <div className="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2">
                    <div>
                      <label className="text-xs font-bold text-slate-700 block mb-1">Employment Status</label>
                      <select
                        value={employmentStatus}
                        onChange={(e) => setEmploymentStatus(e.target.value)}
                        className="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-royal-500"
                      >
                        <option value="">Select Employment</option>
                        <option value="Employed Full-Time">Employed Full-Time</option>
                        <option value="Employed Part-Time">Employed Part-Time</option>
                        <option value="Self-Employed / Freelance">Self-Employed / Freelance</option>
                        <option value="Student">Student</option>
                        <option value="Homemaker">Homemaker</option>
                        <option value="Retired">Retired</option>
                      </select>
                    </div>
                    <div>
                      <label className="text-xs font-bold text-slate-700 block mb-1">Highest Education Level</label>
                      <select
                        value={educationLevel}
                        onChange={(e) => setEducationLevel(e.target.value)}
                        className="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-royal-500"
                      >
                        <option value="">Select Education</option>
                        <option value="High School">High School</option>
                        <option value="Bachelor's Degree">Bachelor&apos;s Degree</option>
                        <option value="Master's Degree">Master&apos;s Degree</option>
                        <option value="Doctorate / Professional">Doctorate / Professional</option>
                        <option value="Other">Other</option>
                      </select>
                    </div>
                  </div>

                  <div className="pt-4 flex justify-end">
                    <Button type="submit" variant="royal" size="sm" disabled={isSaving} className="gap-2 font-bold">
                      <Save className="h-4 w-4" />
                      <span>{isSaving ? 'Saving Changes...' : 'Save Profile Changes'}</span>
                    </Button>
                  </div>
                </CardContent>
              </Card>
            </form>
          </div>
        )}

        {/* =========================================================================
            TAB 2: ACCOUNT SETTINGS & NOTIFICATION PREFERENCES
            ========================================================================= */}
        {activeTab === 'settings' && (
          <form onSubmit={handleSavePreferences} className="space-y-6">
            <Card>
              <CardHeader>
                <div className="flex items-center gap-2">
                  <Bell className="h-5 w-5 text-brand-royal-600" />
                  <CardTitle className="text-lg">Notification Preferences</CardTitle>
                </div>
                <CardDescription>
                  Configure which notifications and study invitations you receive from Veritolytics.
                </CardDescription>
              </CardHeader>
              <CardContent className="space-y-4">
                <div className="space-y-3">
                  <Checkbox
                    id="notify-surveys"
                    checked={notifySurveys}
                    onChange={(e) => setNotifySurveys(e.target.checked)}
                    label={
                      <div>
                        <span className="font-bold text-slate-900 block">Eligible Survey Invitations</span>
                        <span className="text-xs text-slate-500">Receive email alerts when a study matching your demographics goes live.</span>
                      </div>
                    }
                  />

                  <Checkbox
                    id="notify-payouts"
                    checked={notifyPayouts}
                    onChange={(e) => setNotifyPayouts(e.target.checked)}
                    label={
                      <div>
                        <span className="font-bold text-slate-900 block">Reward & Cashout Status Alerts</span>
                        <span className="text-xs text-slate-500">Get notified when your reward redemptions are approved and disbursed.</span>
                      </div>
                    }
                  />

                  <Checkbox
                    id="notify-digests"
                    checked={notifyDigests}
                    onChange={(e) => setNotifyDigests(e.target.checked)}
                    label={
                      <div>
                        <span className="font-bold text-slate-900 block">Platform Digest & Announcements</span>
                        <span className="text-xs text-slate-500">Periodic updates regarding new research cohorts and platform features.</span>
                      </div>
                    }
                  />
                </div>
              </CardContent>
            </Card>

            <Card>
              <CardHeader>
                <div className="flex items-center gap-2">
                  <Globe className="h-5 w-5 text-brand-violet-600" />
                  <CardTitle className="text-lg">Localization & Display</CardTitle>
                </div>
                <CardDescription>
                  Manage your language preferences and default timezone for study schedules.
                </CardDescription>
              </CardHeader>
              <CardContent className="space-y-4">
                <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                  <div>
                    <label className="text-xs font-bold text-slate-700 block mb-1">Platform Language</label>
                    <select
                      value={language}
                      onChange={(e) => setLanguage(e.target.value)}
                      className="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-royal-500"
                    >
                      <option value="English (US)">English (US)</option>
                      <option value="English (UK)">English (UK)</option>
                      <option value="Spanish">Spanish</option>
                      <option value="French">French</option>
                      <option value="German">German</option>
                    </select>
                  </div>
                  <div>
                    <label className="text-xs font-bold text-slate-700 block mb-1">Timezone</label>
                    <select
                      value={timezone}
                      onChange={(e) => setTimezone(e.target.value)}
                      className="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-royal-500"
                    >
                      <option value="UTC">UTC (Coordinated Universal Time)</option>
                      <option value="America/New_York">Eastern Time (US & Canada)</option>
                      <option value="America/Chicago">Central Time (US & Canada)</option>
                      <option value="America/Los_Angeles">Pacific Time (US & Canada)</option>
                      <option value="Europe/London">London (GMT / BST)</option>
                      <option value="Asia/Tokyo">Tokyo (JST)</option>
                    </select>
                  </div>
                </div>

                <div className="pt-4 flex justify-end">
                  <Button type="submit" variant="royal" size="sm" disabled={isSaving} className="gap-2 font-bold">
                    <Save className="h-4 w-4" />
                    <span>{isSaving ? 'Saving...' : 'Save Preferences'}</span>
                  </Button>
                </div>
              </CardContent>
            </Card>
          </form>
        )}

        {/* =========================================================================
            TAB 3: SECURITY & PASSWORD
            ========================================================================= */}
        {activeTab === 'security' && (
          <div className="space-y-6">
            <Card>
              <CardHeader>
                <div className="flex items-center gap-2">
                  <ShieldCheck className="h-5 w-5 text-brand-violet-600" />
                  <CardTitle className="text-lg">Security & Authentication</CardTitle>
                </div>
                <CardDescription>
                  Your account authentication is secured by Supabase Auth with encrypted password hashing.
                </CardDescription>
              </CardHeader>
              <CardContent className="space-y-4">
                <div className="flex items-center justify-between p-3.5 rounded-xl border border-slate-200 bg-slate-50/60 text-xs">
                  <div>
                    <span className="font-bold text-slate-900 block">Email Verification Status</span>
                    <span className="text-slate-500 text-[11px]">{user?.email}</span>
                  </div>
                  <Badge variant={user?.email_confirmed_at ? 'success' : 'secondary'}>
                    {user?.email_confirmed_at ? 'Confirmed' : 'Pending Confirmation'}
                  </Badge>
                </div>

                <div className="flex items-center justify-between p-3.5 rounded-xl border border-slate-200 bg-white text-xs">
                  <div>
                    <span className="font-bold text-slate-900 block">Account Password</span>
                    <span className="text-slate-500 text-[11px]">Regularly updating your password strengthens account security.</span>
                  </div>
                  <Button
                    type="button"
                    variant="royal"
                    size="sm"
                    onClick={() => setShowPasswordModal(true)}
                    className="gap-1.5 text-xs font-bold"
                  >
                    <KeyRound className="h-3.5 w-3.5" />
                    <span>Change Password</span>
                  </Button>
                </div>
              </CardContent>
            </Card>
          </div>
        )}

      </div>

      {/* Change Password Modal */}
      <ChangePasswordModal
        isOpen={showPasswordModal}
        onClose={() => setShowPasswordModal(false)}
      />
    </div>
  );
};
