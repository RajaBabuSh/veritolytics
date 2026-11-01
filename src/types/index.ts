export type UserRole = 'member' | 'admin';

export interface UserProfile {
  id: string;
  firstName: string;
  lastName: string;
  email: string;
  country: string;
  phone?: string;
  dateOfBirth?: string;
  gender?: string;
  employmentStatus?: string;
  annualIncome?: string;
  educationLevel?: string;
  role: UserRole;
  balancePoints: number;
  lifetimeEarningsUsd: number;
  completedSurveysCount: number;
  profileCompletionPct: number;
  createdAt: string;
}

export interface Survey {
  id: string;
  title: string;
  description: string;
  category: string;
  rewardPoints: number;
  rewardUsd: number;
  durationMinutes: number;
  quota: number;
  completedCount: number;
  status: 'active' | 'paused' | 'completed' | 'draft';
  provider: string;
  createdAt: string;
}

export interface RewardOption {
  id: string;
  title: string;
  category: 'Cash & Crypto' | 'Gift Cards' | 'Gaming & Media';
  pointsRequired: number;
  cashEquivalentUsd: number;
  deliveryType: string;
  icon: string;
  minPoints: number;
}

export interface RedemptionRequest {
  id: string;
  userId: string;
  userName: string;
  userEmail: string;
  rewardTitle: string;
  pointsSpent: number;
  amountUsd: number;
  payoutDestination: string;
  status: 'pending' | 'approved' | 'rejected' | 'settled';
  createdAt: string;
  adminNotes?: string;
}

export interface Transaction {
  id: string;
  userId: string;
  type: 'survey_reward' | 'redemption' | 'bonus' | 'adjustment';
  title: string;
  points: number; // positive or negative
  amountUsd: number; // positive or negative
  status: 'completed' | 'pending' | 'cancelled';
  createdAt: string;
}

export interface AppNotification {
  id: string;
  userId: string;
  title: string;
  message: string;
  type: 'info' | 'success' | 'warning' | 'survey' | 'reward' | 'system';
  link?: string;
  isRead: boolean;
  createdAt: string;
}
