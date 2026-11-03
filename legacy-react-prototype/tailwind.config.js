/** @type {import('tailwindcss').Config} */
export default {
  darkMode: ['class'],
  content: [
    './index.html',
    './src/**/*.{js,ts,jsx,tsx}',
  ],
  theme: {
    container: {
      center: true,
      padding: '1.5rem',
      screens: {
        '2xl': '1360px',
      },
    },
    extend: {
      colors: {
        border: 'hsl(var(--border))',
        input: 'hsl(var(--input))',
        ring: 'hsl(var(--ring))',
        background: 'hsl(var(--background))',
        foreground: 'hsl(var(--foreground))',
        primary: {
          DEFAULT: 'hsl(var(--primary))',
          foreground: 'hsl(var(--primary-foreground))',
        },
        secondary: {
          DEFAULT: 'hsl(var(--secondary))',
          foreground: 'hsl(var(--secondary-foreground))',
        },
        destructive: {
          DEFAULT: 'hsl(var(--destructive))',
          foreground: 'hsl(var(--destructive-foreground))',
        },
        muted: {
          DEFAULT: 'hsl(var(--muted))',
          foreground: 'hsl(var(--muted-foreground))',
        },
        accent: {
          DEFAULT: 'hsl(var(--accent))',
          foreground: 'hsl(var(--accent-foreground))',
        },
        popover: {
          DEFAULT: 'hsl(var(--popover))',
          foreground: 'hsl(var(--popover-foreground))',
        },
        card: {
          DEFAULT: 'hsl(var(--card))',
          foreground: 'hsl(var(--card-foreground))',
        },
        /* Veritolytics Signature Color System */
        brand: {
          navy: {
            950: '#070C1E',
            900: '#0B1536',
            800: '#112255',
            700: '#19347A',
            600: '#234AA3',
            500: '#2E5FCB',
          },
          royal: {
            900: '#172554',
            800: '#1E40AF',
            700: '#1D4ED8',
            600: '#2563EB',
            500: '#3B82F6',
            400: '#60A5FA',
            300: '#93C5FD',
          },
          violet: {
            900: '#4C1D95',
            800: '#5B21B6',
            700: '#6D28D9',
            600: '#7C3AED',
            500: '#8B5CF6',
            400: '#A78BFA',
            300: '#C4B5FD',
            100: '#EDE9FE',
          },
        },
      },
      borderRadius: {
        lg: 'var(--radius)',
        md: 'calc(var(--radius) - 2px)',
        sm: 'calc(var(--radius) - 4px)',
      },
      boxShadow: {
        'glow-royal': '0 0 25px -5px rgba(37, 99, 235, 0.4)',
        'glow-violet': '0 0 25px -5px rgba(124, 58, 237, 0.4)',
        'premium-card': '0 10px 30px -10px rgba(11, 21, 54, 0.08), 0 4px 6px -2px rgba(11, 21, 54, 0.04)',
      },
      fontFamily: {
        sans: ['Plus Jakarta Sans', 'Inter', 'sans-serif'],
      },
    },
  },
  plugins: [],
}
