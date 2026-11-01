import * as React from 'react';
import { cva, type VariantProps } from 'class-variance-authority';
import { cn } from '@/lib/utils';

const badgeVariants = cva(
  'inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-semibold transition-colors focus:outline-none focus:ring-2 focus:ring-brand-royal-500 focus:ring-offset-2',
  {
    variants: {
      variant: {
        default:
          'border-transparent bg-brand-royal-600 text-white shadow-sm',
        secondary:
          'border-transparent bg-slate-100 text-slate-800',
        royal:
          'border-brand-royal-200 bg-brand-royal-50 text-brand-royal-700 font-medium',
        violet:
          'border-brand-violet-200 bg-brand-violet-50 text-brand-violet-700 font-medium',
        navy:
          'border-brand-navy-800 bg-brand-navy-950 text-brand-royal-300 font-medium',
        success:
          'border-emerald-200 bg-emerald-50 text-emerald-700 font-medium',
        outline:
          'text-slate-700 border-slate-300 bg-transparent',
      },
    },
    defaultVariants: {
      variant: 'default',
    },
  }
);

export interface BadgeProps
  extends React.HTMLAttributes<HTMLDivElement>,
    VariantProps<typeof badgeVariants> {}

function Badge({ className, variant, ...props }: BadgeProps) {
  return (
    <div className={cn(badgeVariants({ variant }), className)} {...props} />
  );
}

export { Badge, badgeVariants };
