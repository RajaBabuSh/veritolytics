import React from 'react';
import { cn } from '@/lib/utils';
import { Check } from 'lucide-react';

export interface CheckboxProps extends React.InputHTMLAttributes<HTMLInputElement> {
  label?: React.ReactNode;
  error?: string;
}

export const Checkbox = React.forwardRef<HTMLInputElement, CheckboxProps>(
  ({ className, label, error, id, checked, ...props }, ref) => {
    const inputId = id || (typeof label === 'string' ? label.toLowerCase().replace(/\s+/g, '-') : undefined);

    return (
      <div className="space-y-1">
        <label htmlFor={inputId} className="inline-flex items-start gap-2.5 cursor-pointer select-none">
          <div className="relative flex items-center justify-center mt-0.5">
            <input
              ref={ref}
              id={inputId}
              type="checkbox"
              checked={checked}
              className={cn(
                'peer h-4 w-4 shrink-0 appearance-none rounded-md border border-slate-300 bg-white transition-all',
                'checked:border-brand-royal-600 checked:bg-brand-royal-600',
                'focus:outline-none focus:ring-2 focus:ring-brand-royal-500/20 focus:ring-offset-1',
                error && 'border-rose-400',
                className
              )}
              {...props}
            />
            <Check className="pointer-events-none absolute h-3 w-3 text-white opacity-0 transition-opacity peer-checked:opacity-100" strokeWidth={3} />
          </div>
          {label && <span className="text-xs text-slate-600 font-medium leading-tight">{label}</span>}
        </label>
        {error && <p className="text-xs text-rose-500 font-medium pl-6">• {error}</p>}
      </div>
    );
  }
);

Checkbox.displayName = 'Checkbox';
