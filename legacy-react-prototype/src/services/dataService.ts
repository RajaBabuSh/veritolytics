import { supabase, isSupabaseConfigured } from '@/lib/supabase';
import { UserProfile, Survey, RewardOption, RedemptionRequest, Transaction, UserRole, AppNotification } from '@/types';

// Standard Genuine Reward Catalog Options (Fixed exchange rate: 100 pts = $1.00 USD)
export const STANDARD_REWARDS: RewardOption[] = [
  {
    id: 'rew-paypal-10',
    title: '$10 PayPal Cash Transfer',
    category: 'Cash & Crypto',
    pointsRequired: 1000,
    cashEquivalentUsd: 10.0,
    deliveryType: 'Digital Transfer to PayPal Email',
    icon: '💵',
    minPoints: 1000,
  },
  {
    id: 'rew-paypal-25',
    title: '$25 PayPal Cash Transfer',
    category: 'Cash & Crypto',
    pointsRequired: 2500,
    cashEquivalentUsd: 25.0,
    deliveryType: 'Digital Transfer to PayPal Email',
    icon: '💵',
    minPoints: 2500,
  },
  {
    id: 'rew-amazon-10',
    title: '$10 Amazon.com eGift Code',
    category: 'Gift Cards',
    pointsRequired: 1000,
    cashEquivalentUsd: 10.0,
    deliveryType: 'Instant Digital Gift Code',
    icon: '🎁',
    minPoints: 1000,
  },
  {
    id: 'rew-amazon-25',
    title: '$25 Amazon.com eGift Code',
    category: 'Gift Cards',
    pointsRequired: 2500,
    cashEquivalentUsd: 25.0,
    deliveryType: 'Instant Digital Gift Code',
    icon: '🎁',
    minPoints: 2500,
  },
  {
    id: 'rew-apple-15',
    title: '$15 Apple Store & iTunes Code',
    category: 'Gift Cards',
    pointsRequired: 1500,
    cashEquivalentUsd: 15.0,
    deliveryType: 'Instant Digital Code',
    icon: '🍎',
    minPoints: 1500,
  },
  {
    id: 'rew-visa-25',
    title: '$25 Virtual Visa Prepaid Card',
    category: 'Cash & Crypto',
    pointsRequired: 2500,
    cashEquivalentUsd: 25.0,
    deliveryType: 'Instant Virtual Prepaid Card',
    icon: '💳',
    minPoints: 2500,
  },
];

// Calculate profile completion percentage based on actual filled fields
export function calculateProfileCompletion(profile: Partial<UserProfile>): number {
  let score = 0;
  if (profile.firstName) score += 15;
  if (profile.lastName) score += 15;
  if (profile.email) score += 10;
  if (profile.country) score += 10;
  if (profile.phone) score += 10;
  if (profile.dateOfBirth) score += 10;
  if (profile.gender) score += 10;
  if (profile.employmentStatus) score += 10;
  if (profile.educationLevel) score += 10;
  return Math.min(score, 100);
}

// In-memory runtime stores synchronized with Supabase
let runtimeSurveys: Survey[] = [];
let runtimeRedemptions: RedemptionRequest[] = [];
let runtimeTransactions: Transaction[] = [];
let runtimeNotifications: AppNotification[] = [];

export const dataService = {
  // 1. User Profile Management
  async getUserProfile(userId: string, userMeta?: Record<string, any>, userEmail?: string): Promise<UserProfile> {
    const defaultProfile: UserProfile = {
      id: userId,
      firstName: userMeta?.first_name || '',
      lastName: userMeta?.last_name || '',
      email: userEmail || '',
      country: userMeta?.country || 'United States',
      phone: userMeta?.phone || '',
      dateOfBirth: userMeta?.date_of_birth || '',
      gender: userMeta?.gender || '',
      employmentStatus: userMeta?.employment_status || '',
      annualIncome: userMeta?.annual_income || '',
      educationLevel: userMeta?.education_level || '',
      role: (userMeta?.role as UserRole) || 'member',
      balancePoints: 0,
      lifetimeEarningsUsd: 0.0,
      completedSurveysCount: 0,
      profileCompletionPct: 0,
      createdAt: new Date().toISOString(),
    };
    defaultProfile.profileCompletionPct = calculateProfileCompletion(defaultProfile);

    if (!isSupabaseConfigured || !supabase) {
      return defaultProfile;
    }

    try {
      const { data, error } = await supabase
        .from('profiles')
        .select('*')
        .eq('id', userId)
        .single();

      if (!error && data) {
        const profile: UserProfile = {
          id: data.id,
          firstName: data.first_name || defaultProfile.firstName,
          lastName: data.last_name || defaultProfile.lastName,
          email: data.email || defaultProfile.email,
          country: data.country || defaultProfile.country,
          phone: data.phone || '',
          dateOfBirth: data.date_of_birth || '',
          gender: data.gender || '',
          employmentStatus: data.employment_status || '',
          annualIncome: data.annual_income || '',
          educationLevel: data.education_level || '',
          role: (data.role as UserRole) || defaultProfile.role,
          balancePoints: Number(data.balance_points || 0),
          lifetimeEarningsUsd: Number(data.lifetime_earnings_usd || 0),
          completedSurveysCount: Number(data.completed_surveys_count || 0),
          profileCompletionPct: 0,
          createdAt: data.created_at || defaultProfile.createdAt,
        };
        profile.profileCompletionPct = calculateProfileCompletion(profile);
        return profile;
      }
    } catch {
      // Table doesn't exist yet, return clean default profile
    }

    return defaultProfile;
  },

  async updateUserProfile(userId: string, updates: Partial<UserProfile>): Promise<UserProfile> {
    const completion = calculateProfileCompletion(updates);

    if (isSupabaseConfigured && supabase) {
      try {
        // Update user metadata in Supabase Auth
        await supabase.auth.updateUser({
          data: {
            first_name: updates.firstName,
            last_name: updates.lastName,
            country: updates.country,
            phone: updates.phone,
            date_of_birth: updates.dateOfBirth,
            gender: updates.gender,
            employment_status: updates.employmentStatus,
            education_level: updates.educationLevel,
            annual_income: updates.annualIncome,
          },
        });

        // Try updating profiles table if it exists
        await supabase.from('profiles').upsert({
          id: userId,
          first_name: updates.firstName,
          last_name: updates.lastName,
          country: updates.country,
          phone: updates.phone,
          date_of_birth: updates.dateOfBirth,
          gender: updates.gender,
          employment_status: updates.employmentStatus,
          education_level: updates.educationLevel,
          annual_income: updates.annualIncome,
          profile_completion_pct: completion,
          updated_at: new Date().toISOString(),
        });
      } catch (err) {
        console.warn('Profile table sync notice:', err);
      }
    }

    return {
      ...(updates as UserProfile),
      profileCompletionPct: completion,
    };
  },

  // 2. Real Survey Management
  async getSurveys(): Promise<Survey[]> {
    if (isSupabaseConfigured && supabase) {
      try {
        const { data, error } = await supabase
          .from('surveys')
          .select('*')
          .eq('status', 'active')
          .order('created_at', { ascending: false });

        if (!error && data) {
          return data.map((d: any) => ({
            id: d.id,
            title: d.title,
            description: d.description || '',
            category: d.category,
            rewardPoints: d.reward_points,
            rewardUsd: Number(d.reward_usd),
            durationMinutes: d.duration_minutes,
            quota: d.quota,
            completedCount: d.completed_count || 0,
            status: d.status,
            provider: d.provider || 'Veritolytics Direct',
            createdAt: d.created_at,
          }));
        }
      } catch {
        // Fall back to runtime
      }
    }
    return runtimeSurveys;
  },

  async getAllSurveysAdmin(): Promise<Survey[]> {
    if (isSupabaseConfigured && supabase) {
      try {
        const { data, error } = await supabase
          .from('surveys')
          .select('*')
          .order('created_at', { ascending: false });

        if (!error && data) {
          return data.map((d: any) => ({
            id: d.id,
            title: d.title,
            description: d.description || '',
            category: d.category,
            rewardPoints: d.reward_points,
            rewardUsd: Number(d.reward_usd),
            durationMinutes: d.duration_minutes,
            quota: d.quota,
            completedCount: d.completed_count || 0,
            status: d.status,
            provider: d.provider || 'Veritolytics Direct',
            createdAt: d.created_at,
          }));
        }
      } catch {
        // Fall back to runtime
      }
    }
    return runtimeSurveys;
  },

  async createSurvey(survey: Omit<Survey, 'id' | 'completedCount' | 'createdAt'>): Promise<Survey> {
    const newSurvey: Survey = {
      ...survey,
      id: 'srv-' + Date.now(),
      completedCount: 0,
      createdAt: new Date().toISOString(),
    };

    if (isSupabaseConfigured && supabase) {
      try {
        const { data, error } = await supabase
          .from('surveys')
          .insert({
            title: survey.title,
            description: survey.description,
            category: survey.category,
            reward_points: survey.rewardPoints,
            reward_usd: survey.rewardUsd,
            duration_minutes: survey.durationMinutes,
            quota: survey.quota,
            status: survey.status,
            provider: survey.provider,
          })
          .select()
          .single();

        if (!error && data) {
          newSurvey.id = data.id;
        }
      } catch (err) {
        console.warn('Database survey insert notice:', err);
      }
    }

    runtimeSurveys = [newSurvey, ...runtimeSurveys];
    return newSurvey;
  },

  // 3. Wallet, Redemptions & Transactions
  async getTransactions(userId: string): Promise<Transaction[]> {
    if (isSupabaseConfigured && supabase) {
      try {
        const { data, error } = await supabase
          .from('transactions')
          .select('*')
          .eq('user_id', userId)
          .order('created_at', { ascending: false });

        if (!error && data) {
          return data.map((d: any) => ({
            id: d.id,
            userId: d.user_id,
            type: d.type,
            title: d.title,
            points: d.points,
            amountUsd: Number(d.amount_usd),
            status: d.status,
            createdAt: d.created_at,
          }));
        }
      } catch {
        // Fall back to runtime
      }
    }
    return runtimeTransactions.filter((t) => t.userId === userId);
  },

  async getRedemptions(userId?: string): Promise<RedemptionRequest[]> {
    if (isSupabaseConfigured && supabase) {
      try {
        let query = supabase.from('redemptions').select('*').order('created_at', { ascending: false });
        if (userId) {
          query = query.eq('user_id', userId);
        }
        const { data, error } = await query;
        if (!error && data) {
          return data.map((d: any) => ({
            id: d.id,
            userId: d.user_id,
            userName: d.user_name || 'Panelist',
            userEmail: d.user_email || '',
            rewardTitle: d.reward_title,
            pointsSpent: d.points_spent,
            amountUsd: Number(d.amount_usd),
            payoutDestination: d.payout_destination,
            status: d.status,
            createdAt: d.created_at,
            adminNotes: d.admin_notes,
          }));
        }
      } catch {
        // Fall back to runtime
      }
    }
    if (userId) {
      return runtimeRedemptions.filter((r) => r.userId === userId);
    }
    return runtimeRedemptions;
  },

  async requestRedemption(
    user: UserProfile,
    reward: RewardOption,
    destination: string
  ): Promise<{ error: string | null; redemption?: RedemptionRequest }> {
    if (user.balancePoints < reward.pointsRequired) {
      return {
        error: `Insufficient balance. You have ${user.balancePoints} points, but this redemption requires ${reward.pointsRequired.toLocaleString()} points.`,
      };
    }

    const newRedemption: RedemptionRequest = {
      id: 'red-' + Date.now(),
      userId: user.id,
      userName: `${user.firstName} ${user.lastName}`.trim() || 'Panelist',
      userEmail: user.email,
      rewardTitle: reward.title,
      pointsSpent: reward.pointsRequired,
      amountUsd: reward.cashEquivalentUsd,
      payoutDestination: destination,
      status: 'pending',
      createdAt: new Date().toISOString(),
    };

    const newTx: Transaction = {
      id: 'tx-' + Date.now(),
      userId: user.id,
      type: 'redemption',
      title: `Redemption: ${reward.title}`,
      points: -reward.pointsRequired,
      amountUsd: -reward.cashEquivalentUsd,
      status: 'pending',
      createdAt: new Date().toISOString(),
    };

    if (isSupabaseConfigured && supabase) {
      try {
        await supabase.from('redemptions').insert({
          user_id: user.id,
          user_name: newRedemption.userName,
          user_email: user.email,
          reward_title: reward.title,
          points_spent: reward.pointsRequired,
          amount_usd: reward.cashEquivalentUsd,
          payout_destination: destination,
          status: 'pending',
        });

        await supabase.from('transactions').insert({
          user_id: user.id,
          type: 'redemption',
          title: `Redemption: ${reward.title}`,
          points: -reward.pointsRequired,
          amount_usd: -reward.cashEquivalentUsd,
          status: 'pending',
        });
      } catch (err) {
        console.warn('Database redemption insert notice:', err);
      }
    }

    runtimeRedemptions = [newRedemption, ...runtimeRedemptions];
    runtimeTransactions = [newTx, ...runtimeTransactions];

    return { error: null, redemption: newRedemption };
  },

  async updateRedemptionStatus(
    redemptionId: string,
    status: 'approved' | 'rejected' | 'settled',
    adminNotes?: string
  ): Promise<void> {
    runtimeRedemptions = runtimeRedemptions.map((r) =>
      r.id === redemptionId ? { ...r, status, adminNotes } : r
    );

    if (isSupabaseConfigured && supabase) {
      try {
        await supabase
          .from('redemptions')
          .update({ status, admin_notes: adminNotes })
          .eq('id', redemptionId);
      } catch (err) {
        console.warn('Database redemption update notice:', err);
      }
    }
  },

  // 4. Real Member Notifications
  async getNotifications(userId: string): Promise<AppNotification[]> {
    if (isSupabaseConfigured && supabase) {
      try {
        const { data, error } = await supabase
          .from('notifications')
          .select('*')
          .eq('user_id', userId)
          .order('created_at', { ascending: false });

        if (!error && data) {
          return data.map((d: any) => ({
            id: d.id,
            userId: d.user_id,
            title: d.title,
            message: d.message,
            type: d.type || 'info',
            link: d.link || '',
            isRead: Boolean(d.is_read),
            createdAt: d.created_at,
          }));
        }
      } catch {
        // Table doesn't exist yet, fallback to runtime
      }
    }
    return runtimeNotifications.filter((n) => n.userId === userId);
  },

  async markNotificationAsRead(notificationId: string): Promise<void> {
    runtimeNotifications = runtimeNotifications.map((n) =>
      n.id === notificationId ? { ...n, isRead: true } : n
    );

    if (isSupabaseConfigured && supabase) {
      try {
        await supabase
          .from('notifications')
          .update({ is_read: true })
          .eq('id', notificationId);
      } catch (err) {
        console.warn('Database notification update notice:', err);
      }
    }
  },

  async markAllNotificationsAsRead(userId: string): Promise<void> {
    runtimeNotifications = runtimeNotifications.map((n) =>
      n.userId === userId ? { ...n, isRead: true } : n
    );

    if (isSupabaseConfigured && supabase) {
      try {
        await supabase
          .from('notifications')
          .update({ is_read: true })
          .eq('user_id', userId);
      } catch (err) {
        console.warn('Database mark all notifications notice:', err);
      }
    }
  },

  async deleteNotification(notificationId: string): Promise<void> {
    runtimeNotifications = runtimeNotifications.filter((n) => n.id !== notificationId);

    if (isSupabaseConfigured && supabase) {
      try {
        await supabase
          .from('notifications')
          .delete()
          .eq('id', notificationId);
      } catch (err) {
        console.warn('Database notification delete notice:', err);
      }
    }
  },

  async clearAllNotifications(userId: string): Promise<void> {
    runtimeNotifications = runtimeNotifications.filter((n) => n.userId !== userId);

    if (isSupabaseConfigured && supabase) {
      try {
        await supabase
          .from('notifications')
          .delete()
          .eq('user_id', userId);
      } catch (err) {
        console.warn('Database notification clear notice:', err);
      }
    }
  },

  async createNotification(notification: Omit<AppNotification, 'id' | 'createdAt'>): Promise<AppNotification> {
    const newNotif: AppNotification = {
      ...notification,
      id: 'notif-' + Date.now(),
      createdAt: new Date().toISOString(),
    };

    if (isSupabaseConfigured && supabase) {
      try {
        const { data, error } = await supabase
          .from('notifications')
          .insert({
            user_id: notification.userId,
            title: notification.title,
            message: notification.message,
            type: notification.type,
            link: notification.link,
            is_read: notification.isRead,
          })
          .select()
          .single();

        if (!error && data) {
          newNotif.id = data.id;
          newNotif.createdAt = data.created_at;
        }
      } catch (err) {
        console.warn('Database notification insert notice:', err);
      }
    }

    runtimeNotifications = [newNotif, ...runtimeNotifications];
    return newNotif;
  },
};
