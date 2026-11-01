import React from 'react';
import { Link } from 'react-router-dom';

interface LogoProps {
  variant?: 'light' | 'dark';
  size?: 'sm' | 'md' | 'lg' | 'xl';
  to?: string;
  className?: string;
  showSubtitle?: boolean;
  useImage?: boolean;
}

export const Logo: React.FC<LogoProps> = ({
  variant = 'light',
  size = 'md',
  to = '/',
  className = '',
  showSubtitle = false,
  useImage = false,
}) => {
  const heights = {
    sm: 'h-7',
    md: 'h-9',
    lg: 'h-11',
    xl: 'h-14',
  };

  const badgeDimensions = {
    sm: { w: 26, h: 28, stroke: 3.5 },
    md: { w: 32, h: 36, stroke: 4.2 },
    lg: { w: 40, h: 44, stroke: 5.2 },
    xl: { w: 50, h: 56, stroke: 6.5 },
  };

  const textSizes = {
    sm: 'text-xl',
    md: 'text-2xl',
    lg: 'text-3xl',
    xl: 'text-4xl',
  };

  // Blue color from the brand mark
  const brandBlue = '#007DFE';
  const coralOrange = '#FF4625';
  const silverV = '#D0D8E4';

  const logoGraphic = useImage ? (
    <img
      src="/veritolytics-logo.png"
      alt="Veritolytics"
      className={`${heights[size]} object-contain`}
    />
  ) : (
    <div className="flex items-center gap-1.5 select-none">
      {/* 3D Perspective Badge with Silver 'V' */}
      <svg
        width={badgeDimensions[size].w}
        height={badgeDimensions[size].h}
        viewBox="0 0 100 110"
        fill="none"
        xmlns="http://www.w3.org/2000/svg"
        className="shrink-0 drop-shadow-sm transition-transform hover:scale-105 duration-200"
      >
        <defs>
          <linearGradient id="vBadgeGrad" x1="0%" y1="0%" x2="100%" y2="100%">
            <stop offset="0%" stopColor="#0088FF" />
            <stop offset="100%" stopColor="#0066EE" />
          </linearGradient>
          <linearGradient id="vStrokeGrad" x1="0%" y1="0%" x2="100%" y2="100%">
            <stop offset="0%" stopColor="#E2E8F0" />
            <stop offset="100%" stopColor="#CBD5E1" />
          </linearGradient>
        </defs>

        {/* Angled Perspective Rhombus / Screen */}
        <path
          d="M 12 18 
             C 12 12, 16 9, 24 8 
             L 86 1 
             C 93 0, 98 4, 98 11 
             L 94 92 
             C 94 99, 89 103, 82 104 
             L 20 109 
             C 14 110, 10 106, 10 99 
             Z"
          fill="url(#vBadgeGrad)"
        />

        {/* Silver-White 'V' */}
        <path
          d="M 28 34 L 52 82 L 77 34"
          stroke="url(#vStrokeGrad)"
          strokeWidth="13"
          strokeLinecap="round"
          strokeLinejoin="round"
        />
      </svg>

      {/* Wordmark: "eritolytics" with Two-Tone Donut "o" */}
      <div className="flex items-center tracking-tight font-extrabold font-sans">
        {/* "erit" in vibrant brand blue */}
        <span
          className={`${textSizes[size]} leading-none`}
          style={{ color: brandBlue, letterSpacing: '-0.02em' }}
        >
          erit
        </span>

        {/* Distinctive Two-Tone Analytics Donut "o" */}
        <span className="inline-flex items-center justify-center px-[1px]">
          <svg
            className={
              size === 'sm'
                ? 'w-4 h-4'
                : size === 'md'
                ? 'w-5 h-5'
                : size === 'lg'
                ? 'w-6 h-6'
                : 'w-7 h-7'
            }
            viewBox="0 0 36 36"
            fill="none"
            xmlns="http://www.w3.org/2000/svg"
          >
            {/* Top Half Arc (Vibrant Coral Red / Orange) */}
            <path
              d="M 6 18 A 12 12 0 0 1 30 18"
              stroke={coralOrange}
              strokeWidth="5.5"
              strokeLinecap="round"
            />
            {/* Bottom Half Arc (Vibrant Brand Blue) */}
            <path
              d="M 30 18 A 12 12 0 0 1 6 18"
              stroke={brandBlue}
              strokeWidth="5.5"
              strokeLinecap="round"
            />
          </svg>
        </span>

        {/* "lytics" in vibrant brand blue */}
        <span
          className={`${textSizes[size]} leading-none`}
          style={{ color: brandBlue, letterSpacing: '-0.02em' }}
        >
          lytics
        </span>
      </div>
    </div>
  );

  const fullComponent = (
    <div className={`inline-flex flex-col ${className}`}>
      {logoGraphic}
      {showSubtitle && (
        <span className="text-[9px] tracking-widest uppercase font-semibold text-brand-violet-400 pl-1 -mt-0.5">
          Survey & Analytics Intelligence
        </span>
      )}
    </div>
  );

  if (to) {
    return (
      <Link to={to} className="inline-flex transition-opacity hover:opacity-95 items-center">
        {fullComponent}
      </Link>
    );
  }

  return fullComponent;
};

export default Logo;
