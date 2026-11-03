-- ==============================================================================
-- VERITOLYTICS PRODUCTION SCHEMA & ROW LEVEL SECURITY (RLS) POLICIES
-- ==============================================================================
-- Run this script in the Supabase SQL Editor:
-- https://supabase.com/dashboard/project/grfpbwmcnzobditnmdjo/sql/new
-- ==============================================================================

-- 1. PROFILES TABLE (One-to-one with auth.users)
CREATE TABLE IF NOT EXISTS public.profiles (
  id uuid PRIMARY KEY REFERENCES auth.users(id) ON DELETE CASCADE,
  first_name text NOT NULL DEFAULT '',
  last_name text NOT NULL DEFAULT '',
  email text NOT NULL DEFAULT '',
  country text NOT NULL DEFAULT 'United States',
  phone text DEFAULT '',
  date_of_birth date,
  gender text DEFAULT '',
  employment_status text DEFAULT '',
  annual_income text DEFAULT '',
  education_level text DEFAULT '',
  role text NOT NULL DEFAULT 'member' CHECK (role IN ('member', 'admin')),
  balance_points integer NOT NULL DEFAULT 0 CHECK (balance_points >= 0),
  lifetime_earnings_usd numeric(10, 2) NOT NULL DEFAULT 0.00 CHECK (lifetime_earnings_usd >= 0.00),
  completed_surveys_count integer NOT NULL DEFAULT 0 CHECK (completed_surveys_count >= 0),
  profile_completion_pct integer NOT NULL DEFAULT 30 CHECK (profile_completion_pct BETWEEN 0 AND 100),
  created_at timestamptz NOT NULL DEFAULT now(),
  updated_at timestamptz NOT NULL DEFAULT now()
);

-- 2. SURVEYS TABLE
CREATE TABLE IF NOT EXISTS public.surveys (
  id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
  title text NOT NULL,
  description text DEFAULT '',
  category text NOT NULL DEFAULT 'Consumer Insights',
  reward_points integer NOT NULL DEFAULT 100 CHECK (reward_points > 0),
  reward_usd numeric(10, 2) NOT NULL DEFAULT 1.00 CHECK (reward_usd > 0),
  duration_minutes integer NOT NULL DEFAULT 5 CHECK (duration_minutes > 0),
  quota integer NOT NULL DEFAULT 100 CHECK (quota > 0),
  completed_count integer NOT NULL DEFAULT 0 CHECK (completed_count >= 0),
  status text NOT NULL DEFAULT 'active' CHECK (status IN ('active', 'paused', 'completed', 'draft')),
  provider text NOT NULL DEFAULT 'Veritolytics Direct',
  created_by uuid REFERENCES auth.users(id),
  created_at timestamptz NOT NULL DEFAULT now(),
  updated_at timestamptz NOT NULL DEFAULT now()
);

-- 3. SURVEY RESPONSES TABLE
CREATE TABLE IF NOT EXISTS public.survey_responses (
  id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
  survey_id uuid NOT NULL REFERENCES public.surveys(id) ON DELETE CASCADE,
  user_id uuid NOT NULL REFERENCES auth.users(id) ON DELETE CASCADE,
  status text NOT NULL DEFAULT 'completed' CHECK (status IN ('in_progress', 'completed', 'screened_out')),
  points_awarded integer NOT NULL DEFAULT 0,
  usd_awarded numeric(10, 2) NOT NULL DEFAULT 0.00,
  completed_at timestamptz NOT NULL DEFAULT now(),
  UNIQUE(survey_id, user_id)
);

-- 4. REWARDS CATALOG TABLE
CREATE TABLE IF NOT EXISTS public.rewards (
  id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
  title text NOT NULL,
  category text NOT NULL CHECK (category IN ('Cash & Crypto', 'Gift Cards', 'Gaming & Media')),
  points_required integer NOT NULL CHECK (points_required > 0),
  cash_equivalent_usd numeric(10, 2) NOT NULL CHECK (cash_equivalent_usd > 0),
  delivery_type text NOT NULL DEFAULT 'Instant Digital',
  is_active boolean NOT NULL DEFAULT true,
  created_at timestamptz NOT NULL DEFAULT now()
);

-- 5. REDEMPTIONS TABLE (Member Cashout Requests)
CREATE TABLE IF NOT EXISTS public.redemptions (
  id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
  user_id uuid NOT NULL REFERENCES auth.users(id) ON DELETE CASCADE,
  user_name text NOT NULL DEFAULT '',
  user_email text NOT NULL DEFAULT '',
  reward_title text NOT NULL,
  points_spent integer NOT NULL CHECK (points_spent > 0),
  amount_usd numeric(10, 2) NOT NULL CHECK (amount_usd > 0),
  payout_destination text NOT NULL,
  status text NOT NULL DEFAULT 'pending' CHECK (status IN ('pending', 'approved', 'rejected', 'settled')),
  admin_notes text DEFAULT '',
  reviewed_by uuid REFERENCES auth.users(id),
  reviewed_at timestamptz,
  created_at timestamptz NOT NULL DEFAULT now()
);

-- 6. TRANSACTIONS LEDGER TABLE
CREATE TABLE IF NOT EXISTS public.transactions (
  id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
  user_id uuid NOT NULL REFERENCES auth.users(id) ON DELETE CASCADE,
  type text NOT NULL CHECK (type IN ('survey_reward', 'redemption', 'bonus', 'adjustment')),
  title text NOT NULL,
  points integer NOT NULL,
  amount_usd numeric(10, 2) NOT NULL,
  status text NOT NULL DEFAULT 'completed' CHECK (status IN ('pending', 'completed', 'cancelled')),
  created_at timestamptz NOT NULL DEFAULT now()
);

-- 7. NOTIFICATIONS TABLE
CREATE TABLE IF NOT EXISTS public.notifications (
  id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
  user_id uuid NOT NULL REFERENCES auth.users(id) ON DELETE CASCADE,
  title text NOT NULL,
  message text NOT NULL,
  type text NOT NULL DEFAULT 'info' CHECK (type IN ('info', 'success', 'warning', 'survey', 'reward', 'system')),
  link text DEFAULT '',
  is_read boolean NOT NULL DEFAULT false,
  created_at timestamptz NOT NULL DEFAULT now()
);

-- ==============================================================================
-- ENABLE ROW LEVEL SECURITY (RLS)
-- ==============================================================================
ALTER TABLE public.profiles ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.surveys ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.survey_responses ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.rewards ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.redemptions ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.transactions ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.notifications ENABLE ROW LEVEL SECURITY;

-- Helper function: Is current user an admin?
CREATE OR REPLACE FUNCTION public.is_admin()
RETURNS boolean AS $$
BEGIN
  RETURN EXISTS (
    SELECT 1 FROM public.profiles
    WHERE id = auth.uid() AND role = 'admin'
  );
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

-- PROFILES POLICIES
DROP POLICY IF EXISTS "Users can view own profile" ON public.profiles;
CREATE POLICY "Users can view own profile"
  ON public.profiles FOR SELECT
  USING (auth.uid() = id OR public.is_admin());

DROP POLICY IF EXISTS "Users can update own demographic profile" ON public.profiles;
CREATE POLICY "Users can update own demographic profile"
  ON public.profiles FOR UPDATE
  USING (auth.uid() = id)
  WITH CHECK (auth.uid() = id);

-- SURVEYS POLICIES
DROP POLICY IF EXISTS "Public can view active surveys" ON public.surveys;
CREATE POLICY "Public can view active surveys"
  ON public.surveys FOR SELECT
  USING (status = 'active' OR public.is_admin());

DROP POLICY IF EXISTS "Admins can manage surveys" ON public.surveys;
CREATE POLICY "Admins can manage surveys"
  ON public.surveys FOR ALL
  USING (public.is_admin());

-- SURVEY RESPONSES POLICIES
DROP POLICY IF EXISTS "Users can view own responses" ON public.survey_responses;
CREATE POLICY "Users can view own responses"
  ON public.survey_responses FOR SELECT
  USING (auth.uid() = user_id OR public.is_admin());

DROP POLICY IF EXISTS "Users can insert own responses" ON public.survey_responses;
CREATE POLICY "Users can insert own responses"
  ON public.survey_responses FOR INSERT
  WITH CHECK (auth.uid() = user_id);

-- REDEMPTIONS POLICIES
DROP POLICY IF EXISTS "Users can view own redemptions" ON public.redemptions;
CREATE POLICY "Users can view own redemptions"
  ON public.redemptions FOR SELECT
  USING (auth.uid() = user_id OR public.is_admin());

DROP POLICY IF EXISTS "Users can submit redemption request" ON public.redemptions;
CREATE POLICY "Users can submit redemption request"
  ON public.redemptions FOR INSERT
  WITH CHECK (auth.uid() = user_id);

DROP POLICY IF EXISTS "Admins can manage redemptions" ON public.redemptions;
CREATE POLICY "Admins can manage redemptions"
  ON public.redemptions FOR UPDATE
  USING (public.is_admin());

-- TRANSACTIONS POLICIES
DROP POLICY IF EXISTS "Users can view own transactions" ON public.transactions;
CREATE POLICY "Users can view own transactions"
  ON public.transactions FOR SELECT
  USING (auth.uid() = user_id OR public.is_admin());

-- NOTIFICATIONS POLICIES
DROP POLICY IF EXISTS "Users can view own notifications" ON public.notifications;
CREATE POLICY "Users can view own notifications"
  ON public.notifications FOR SELECT
  USING (auth.uid() = user_id OR public.is_admin());

DROP POLICY IF EXISTS "Users can update own notifications" ON public.notifications;
CREATE POLICY "Users can update own notifications"
  ON public.notifications FOR UPDATE
  USING (auth.uid() = user_id)
  WITH CHECK (auth.uid() = user_id);

DROP POLICY IF EXISTS "Users can delete own notifications" ON public.notifications;
CREATE POLICY "Users can delete own notifications"
  ON public.notifications FOR DELETE
  USING (auth.uid() = user_id);

DROP POLICY IF EXISTS "Users or admins can insert notifications" ON public.notifications;
CREATE POLICY "Users or admins can insert notifications"
  ON public.notifications FOR INSERT
  WITH CHECK (auth.uid() = user_id OR public.is_admin());

-- ==============================================================================
-- AUTOMATIC NEW USER PROFILE TRIGGER
-- ==============================================================================
-- Automatically creates a clean profile with 0 points for every newly registered user
CREATE OR REPLACE FUNCTION public.handle_new_user()
RETURNS trigger AS $$
BEGIN
  INSERT INTO public.profiles (
    id,
    first_name,
    last_name,
    email,
    country,
    balance_points,
    lifetime_earnings_usd,
    completed_surveys_count,
    role,
    profile_completion_pct
  ) VALUES (
    NEW.id,
    COALESCE(NEW.raw_user_meta_data->>'first_name', ''),
    COALESCE(NEW.raw_user_meta_data->>'last_name', ''),
    COALESCE(NEW.email, ''),
    COALESCE(NEW.raw_user_meta_data->>'country', 'United States'),
    0,
    0.00,
    0,
    'member',
    40
  );
  RETURN NEW;
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

DROP TRIGGER IF EXISTS on_auth_user_created ON auth.users;
CREATE TRIGGER on_auth_user_created
  AFTER INSERT ON auth.users
  FOR EACH ROW EXECUTE FUNCTION public.handle_new_user();
