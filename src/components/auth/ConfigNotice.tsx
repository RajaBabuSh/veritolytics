import React, { useState } from 'react';
import { Database, Key, ExternalLink, ChevronDown, ChevronUp, Copy, Check } from 'lucide-react';
import { useAuth } from '@/context/AuthContext';

export const ConfigNotice: React.FC = () => {
  const { isConfigured } = useAuth();
  const [expanded, setExpanded] = useState(false);
  const [copied, setCopied] = useState(false);

  if (isConfigured) {
    return null;
  }

  const sampleEnvText = `VITE_SUPABASE_URL=https://grfpbwmcnzobditnmdjo.supabase.co\nVITE_SUPABASE_PUBLISHABLE_KEY=your-publishable-key-here`;

  const copyToClipboard = () => {
    navigator.clipboard.writeText(sampleEnvText);
    setCopied(true);
    setTimeout(() => setCopied(false), 2500);
  };

  return (
    <div className="rounded-2xl border border-brand-royal-200/90 bg-gradient-to-br from-brand-royal-50/90 via-white to-brand-violet-50/70 p-4 text-xs text-slate-700 shadow-sm transition-all mb-6">
      <div className="flex items-start justify-between gap-3">
        <div className="flex items-center gap-2.5">
          <div className="h-8 w-8 rounded-xl bg-brand-royal-600 text-white flex items-center justify-center shrink-0 shadow-sm">
            <Database className="h-4 w-4" />
          </div>
          <div>
            <h4 className="font-bold text-slate-900 text-sm">
              Live Authentication Setup Ready
            </h4>
            <p className="text-[11px] text-slate-500">
              Supabase project keys are not configured yet. Forms validate locally and will connect immediately once credentials are set.
            </p>
          </div>
        </div>

        <button
          type="button"
          onClick={() => setExpanded(!expanded)}
          className="rounded-lg p-1 text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition-colors"
          aria-label="Toggle instructions"
        >
          {expanded ? <ChevronUp className="h-4 w-4" /> : <ChevronDown className="h-4 w-4" />}
        </button>
      </div>

      {expanded && (
        <div className="mt-3 pt-3 border-t border-slate-200/80 space-y-2.5 animate-fadeIn">
          <p className="text-[11px] text-slate-600 leading-relaxed">
            Follow these 3 quick steps to enable live registration, password hashing, email verification, and login:
          </p>

          <ol className="list-decimal list-inside space-y-1 text-[11px] text-slate-600 font-medium">
            <li>
              Create a free project at{' '}
              <a
                href="https://supabase.com"
                target="_blank"
                rel="noreferrer"
                className="text-brand-royal-600 underline font-semibold inline-flex items-center gap-0.5"
              >
                supabase.com <ExternalLink className="h-3 w-3" />
              </a>
            </li>
            <li>Go to <strong>Project Settings → API</strong> in your Supabase dashboard.</li>
            <li>Copy your <strong>Project URL</strong> and <strong>publishable API key</strong> into the <code className="bg-slate-100 px-1 py-0.5 rounded text-slate-800">.env</code> file in your project root.</li>
          </ol>

          <div className="relative mt-2 rounded-xl bg-brand-navy-950 p-3 text-slate-200 font-mono text-[11px]">
            <button
              type="button"
              onClick={copyToClipboard}
              className="absolute right-2 top-2 rounded-lg bg-white/10 px-2 py-1 text-[10px] text-white hover:bg-white/20 transition-colors flex items-center gap-1"
            >
              {copied ? <Check className="h-3 w-3 text-emerald-400" /> : <Copy className="h-3 w-3" />}
              <span>{copied ? 'Copied' : 'Copy'}</span>
            </button>
            <pre className="overflow-x-auto pr-16">{sampleEnvText}</pre>
          </div>
        </div>
      )}
    </div>
  );
};
