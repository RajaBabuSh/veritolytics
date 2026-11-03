import * as React from 'react';
import { cva, type VariantProps } from 'class-variance-authority';
import { cn } from '@/lib/utils';

const buttonVariants = cva(
  'inline-flex items-center justify-center whitespace-nowrap rounded-xl text-sm font-semibold transition-all duration-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-royal-500 focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 active:scale-[0.98]',
  {
    variants: {
      variant: {
        default:
          'bg-brand-royal-600 text-white hover:bg-brand-royal-700 shadow-md shadow-brand-royal-600/20 hover:shadow-lg hover:shadow-brand-royal-600/30',
        royal:
          'bg-gradient-to-r from-brand-royal-600 to-brand-royal-500 text-white hover:from-brand-royal-700 hover:to-brand-royal-600 shadow-glow-royal',
        violet:
          'bg-gradient-to-r from-brand-violet-600 to-brand-violet-500 text-white hover:from-brand-violet-700 hover:to-brand-violet-600 shadow-glow-violet',
        navy:
          'bg-brand-navy-900 text-white hover:bg-brand-navy-800 border border-brand-navy-700/60 shadow-lg',
        brand:
          'bg-gradient-to-r from-brand-navy-900 via-brand-royal-700 to-brand-violet-600 text-white hover:opacity-95 shadow-md',
        outline:
          'border border-slate-200 bg-white hover:bg-slate-50 text-slate-800 hover:border-brand-royal-300',
        outlineDark:
          'border border-white/20 bg-white/5 hover:bg-white/10 text-white hover:border-white/40 backdrop-blur-sm',
        secondary:
          'bg-slate-100 text-slate-900 hover:bg-slate-200',
        ghost:
          'hover:bg-slate-100 text-slate-700 hover:text-slate-900',
        ghostDark:
          'hover:bg-white/10 text-slate-200 hover:text-white',
        link:
          'text-brand-royal-600 underline-offset-4 hover:underline p-0 h-auto font-normal',
      },
      size: {
        default: 'h-11 px-5 py-2.5',
        sm: 'h-9 rounded-lg px-3.5 text-xs',
        lg: 'h-12 rounded-xl px-7 text-base font-bold',
        icon: 'h-10 w-10 p-0',
      },
    },
    defaultVariants: {
      variant: 'default',
      size: 'default',
    },
  }
);

export interface ButtonProps
  extends React.ButtonHTMLAttributes<HTMLButtonElement>,
    VariantProps<typeof buttonVariants> {
  asChild?: boolean;
}

const Button = React.forwardRef<HTMLButtonElement, ButtonProps>(
  ({ className, variant, size, ...props }, ref) => {
    return (
      <button
        className={cn(buttonVariants({ variant, size, className }))}
        ref={ref}
        {...props}
      />
    );
  }
);
Button.displayName = 'Button';

export { Button, buttonVariants };
