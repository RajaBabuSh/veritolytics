import React from 'react';
import { Link } from 'react-router-dom';
import { Button } from '@/components/ui/Button';
import { ArrowLeft, Compass } from 'lucide-react';

export const NotFoundPage: React.FC = () => {
  return (
    <div className="min-h-[70vh] flex items-center justify-center p-6 text-center">
      <div className="max-w-md space-y-6">
        <div className="h-16 w-16 mx-auto rounded-2xl bg-brand-royal-50 flex items-center justify-center text-brand-royal-600 shadow-inner">
          <Compass className="h-8 w-8" />
        </div>
        <div className="space-y-2">
          <h1 className="text-4xl font-extrabold text-slate-900 tracking-tight">404</h1>
          <h2 className="text-xl font-bold text-slate-800">Page Not Found</h2>
          <p className="text-sm text-slate-500">
            The page or survey resource you are looking for has been moved or is no longer accessible.
          </p>
        </div>
        <div>
          <Link to="/">
            <Button variant="default" className="gap-2">
              <ArrowLeft className="h-4 w-4" />
              <span>Return Home</span>
            </Button>
          </Link>
        </div>
      </div>
    </div>
  );
};
