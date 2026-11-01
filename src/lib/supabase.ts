import { createClient, SupabaseClient } from '@supabase/supabase-js';

// Safe string cleaner to strip surrounding quotes, accidental whitespace or linebreaks from Vercel env fields
function cleanEnvValue(val: unknown): string {
  if (typeof val !== 'string') return '';
  return val.trim().replace(/^["']|["']$/g, '').trim();
}

// 1. Resolve Supabase URL from all common client variable names
const rawUrl =
  import.meta.env.VITE_SUPABASE_URL ||
  import.meta.env.SUPABASE_URL ||
  import.meta.env.NEXT_PUBLIC_SUPABASE_URL ||
  import.meta.env.VITE_PUBLIC_SUPABASE_URL ||
  '';

const supabaseUrl = cleanEnvValue(rawUrl);

// 2. Resolve Supabase Key from modern publishable key and anon key formats
const rawKey =
  import.meta.env.VITE_SUPABASE_PUBLISHABLE_KEY ||
  import.meta.env.VITE_SUPABASE_ANON_KEY ||
  import.meta.env.VITE_SUPABASE_KEY ||
  import.meta.env.SUPABASE_PUBLISHABLE_KEY ||
  import.meta.env.SUPABASE_ANON_KEY ||
  import.meta.env.SUPABASE_KEY ||
  import.meta.env.NEXT_PUBLIC_SUPABASE_PUBLISHABLE_KEY ||
  import.meta.env.NEXT_PUBLIC_SUPABASE_ANON_KEY ||
  '';

const supabaseKey = cleanEnvValue(rawKey);

// Check if values are dummy or placeholders
function isPlaceholder(value: string): boolean {
  if (!value) return true;
  const lower = value.toLowerCase();
  return (
    lower.includes('your-project-id') ||
    lower.includes('your-publishable-key') ||
    lower.includes('your-anon-public-key') ||
    lower.includes('example') ||
    lower === 'https://your-project-id.supabase.co'
  );
}

const isValidUrl = Boolean(
  supabaseUrl &&
  !isPlaceholder(supabaseUrl) &&
  (supabaseUrl.startsWith('https://') || supabaseUrl.startsWith('http://'))
);

const isValidKey = Boolean(
  supabaseKey &&
  !isPlaceholder(supabaseKey) &&
  supabaseKey.length > 10
);

// Extract safe hostname for diagnostics (e.g., "grfpbwmcnzobditnmdjo.supabase.co") without revealing credentials
let detectedHost: string | null = null;
if (isValidUrl) {
  try {
    detectedHost = new URL(supabaseUrl).host;
  } catch {
    detectedHost = null;
  }
}

// Detect key type safely
let keyType: 'publishable' | 'jwt_anon' | 'custom' | 'none' = 'none';
if (isValidKey) {
  if (supabaseKey.startsWith('sb_publishable_')) {
    keyType = 'publishable';
  } else if (supabaseKey.startsWith('eyJ')) {
    keyType = 'jwt_anon';
  } else {
    keyType = 'custom';
  }
}

let supabaseInstance: SupabaseClient | null = null;
let initError: string | null = null;

if (isValidUrl && isValidKey) {
  try {
    supabaseInstance = createClient(supabaseUrl, supabaseKey, {
      auth: {
        autoRefreshToken: true,
        persistSession: true,
        detectSessionInUrl: true,
      },
    });
  } catch (err: any) {
    initError = err instanceof Error ? err.message : 'Failed to instantiate Supabase client';
    console.error('[Veritolytics] Supabase client initialization error:', err);
    supabaseInstance = null;
  }
}

// Safe Diagnostic Interface
export interface SupabaseConfigDiagnostics {
  isConfigured: boolean;
  hasUrl: boolean;
  hasKey: boolean;
  detectedHost: string | null;
  keyType: 'publishable' | 'jwt_anon' | 'custom' | 'none';
  missingVars: string[];
  initializationError: string | null;
}

export function getSupabaseDiagnostics(): SupabaseConfigDiagnostics {
  const missing: string[] = [];
  if (!isValidUrl) missing.push('VITE_SUPABASE_URL');
  if (!isValidKey) missing.push('VITE_SUPABASE_PUBLISHABLE_KEY');

  return {
    isConfigured: Boolean(supabaseInstance !== null),
    hasUrl: isValidUrl,
    hasKey: isValidKey,
    detectedHost,
    keyType,
    missingVars: missing,
    initializationError: initError,
  };
}

// Global flag: true only if client was successfully created
export const isSupabaseConfigured = Boolean(supabaseInstance !== null);

// Diagnostic console notification (safe, no secret exposure)
if (typeof window !== 'undefined') {
  if (!isSupabaseConfigured) {
    const diag = getSupabaseDiagnostics();
    console.warn('[Veritolytics] Supabase environment configuration status:', {
      configured: false,
      missingVariables: diag.missingVars,
      detectedHost: diag.detectedHost,
      keyType: diag.keyType,
      tip: 'In Vercel: Project Settings > Environment Variables > Add missing variables > Trigger a Redeploy.',
    });
  } else {
    console.info('[Veritolytics] Supabase client successfully initialized for:', detectedHost);
  }
}

export const supabase = supabaseInstance;
