import React, { useState } from 'react';
import { Database, AlertTriangle, ExternalLink, ChevronDown, ChevronUp, Copy, Check, CheckCircle2, XCircle } from 'lucide-react';
import { useAuth } from '@/context/AuthContext';

export const ConfigNotice: React.FC = () => {
  const { isConfigured, diagnostics } = useAuth();
  const [expanded, setExpanded] = useState(false);
  const [copied, setCopied] = useState(false);

  // If Supabase is properly configured and connected, do not show any notice
  if (isConfigured) {
    return null;
  }

  const sampleEnvText = `VITE_SUPABASE_URL=https://grfpbwmcnzobditnmdjo.supabase.co\nVITE_SUPABASE_PUBLISHABLE_KEY=your-publishable-key-here`;

  const copyToClipboard = () => {
    navigator.clipboard.writeText(sampleEnvText);
    setCopied(true);
    setTimeout(() => setCopied(false), 2500);
  };

  const missingList = diagnostics.missingVars.length > 0
    ? diagnostics.missingVars.join(' and ')
    : 'Supabase credentials';

  return (
    <div className="rounded-2xl border border-amber-200 bg-gradient-to-br from-amber-50/80 via-white to-amber-50/40 p-4 text-xs text-slate-700 shadow-sm transition-all mb-6">
      <div className="flex items-start justify-between gap-3">
        <div className="flex items-start gap-3">
          <div className="h-8 w-8 rounded-xl bg-amber-500 text-white flex items-center justify-center shrink-0 shadow-sm mt-0.5">
            <AlertTriangle className="h-4 w-4" />
          </div>
          <div className="space-y-1">
            <div className="flex items-center gap-2 flex-wrap">
              <h4 className="font-bold text-slate-900 text-sm">
                Supabase Credentials Required for Live Auth
              </h4>
              <span className="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-amber-100 text-amber-800 border border-amber-300">
                Action Needed in Vercel
              </span>
            </div>
            <p className="text-[11px] text-slate-600 leading-relaxed">
              Missing build-time variables: <strong className="text-slate-900">{missingList}</strong>. 
              Forms validate inputs locally, but live registration, login, and database persistence require these keys in Vercel.
            </p>

            {/* Diagnostic Badges */}
            <div className="flex flex-wrap items-center gap-2 pt-1 text-[11px]">
              <div className={`inline-flex items-center gap-1 px-2.5 py-1 rounded-lg border font-medium ${
                diagnostics.hasUrl
                  ? 'bg-emerald-50 text-emerald-700 border-emerald-200'
                  : 'bg-rose-50 text-rose-700 border-rose-200'
              }`}>
                {diagnostics.hasUrl ? <CheckCircle2 className="h-3 w-3" /> : <XCircle className="h-3 w-3" />}
                <span>URL: {diagnostics.hasUrl ? (diagnostics.detectedHost || 'Detected') : 'Missing VITE_SUPABASE_URL'}</span>
              </div>

              <div className={`inline-flex items-center gap-1 px-2.5 py-1 rounded-lg border font-medium ${
                diagnostics.hasKey
                  ? 'bg-emerald-50 text-emerald-700 border-emerald-200'
                  : 'bg-rose-50 text-rose-700 border-rose-200'
              }`}>
                {diagnostics.hasKey ? <CheckCircle2 className="h-3 w-3" /> : <XCircle className="h-3 w-3" />}
                <span>Key: {diagnostics.hasKey ? `Detected (${diagnostics.keyType})` : 'Missing VITE_SUPABASE_PUBLISHABLE_KEY'}</span>
              </div>
            </div>
          </div>
        </div>

        <button
          type="button"
          onClick={() => setExpanded(!expanded)}
          className="rounded-lg p-1.5 text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition-colors shrink-0"
          aria-label="Toggle instructions"
        >
          {expanded ? <ChevronUp className="h-4 w-4" /> : <ChevronDown className="h-4 w-4" />}
        </button>
      </div>

      {expanded && (
        <div className="mt-3 pt-3 border-t border-amber-200/80 space-y-3 animate-fadeIn">
          <div className="bg-amber-100/60 rounded-xl p-3 border border-amber-200/90 text-[11px] text-amber-950 space-y-1">
            <p className="font-semibold text-amber-900">
              Why this happens on Vercel:
            </p>
            <p className="leading-relaxed">
              Vite embeds environment variables into JavaScript at <strong>build time</strong>. 
              Adding variables in the Vercel dashboard does not affect already deployed builds until you trigger a <strong>Redeploy</strong>.
            </p>
          </div>

          <p className="text-[11px] text-slate-700 font-semibold">
            How to resolve this in 2 steps:
          </p>

          <ol className="list-decimal list-inside space-y-1.5 text-[11px] text-slate-700 font-medium">
            <li>
              Go to your Vercel Dashboard → <strong>Project Settings → Environment Variables</strong>.
            </li>
            <li>
              Add the two variables below (check <strong>Production</strong>, <strong>Preview</strong>, and <strong>Development</strong>):
            </li>
          </ol>

          <div className="relative rounded-xl bg-brand-navy-950 p-3 text-slate-200 font-mono text-[11px]">
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

          <div className="rounded-xl bg-blue-50/80 border border-blue-200 p-2.5 text-[11px] text-blue-900">
            <strong>Critical Final Step:</strong> After saving the variables in Vercel, go to the <strong>Deployments</strong> tab, click the three dots (<strong>···</strong>) next to your latest deployment, and click <strong>Redeploy</strong> (uncheck "Use existing Build Cache").
          </div>
        </div>
      )}
    </div>
  );
};
