import React, { useState } from 'react';
import { Outlet } from 'react-router-dom';
import { AdminSidebar } from '@/components/navigation/AdminSidebar';
import { X } from 'lucide-react';

export const AdminLayout: React.FC = () => {
  const [mobileSidebarOpen, setMobileSidebarOpen] = useState(false);

  return (
    <div className="flex min-h-screen bg-slate-100 antialiased">
      {/* Desktop Persistent Admin Sidebar */}
      <div className="hidden lg:block shrink-0">
        <AdminSidebar />
      </div>

      {/* Mobile Drawer */}
      {mobileSidebarOpen && (
        <div className="fixed inset-0 z-50 flex lg:hidden">
          <div
            className="fixed inset-0 bg-brand-navy-950/80 backdrop-blur-sm"
            onClick={() => setMobileSidebarOpen(false)}
          />
          <div className="relative z-50 flex w-72 flex-col bg-brand-navy-950">
            <button
              onClick={() => setMobileSidebarOpen(false)}
              className="absolute right-3 top-4 rounded-lg p-1.5 text-slate-400 hover:bg-brand-navy-900"
              aria-label="Close sidebar"
            >
              <X className="h-5 w-5" />
            </button>
            <AdminSidebar onCloseMobile={() => setMobileSidebarOpen(false)} />
          </div>
        </div>
      )}

      {/* Main Admin Section */}
      <div className="flex flex-1 flex-col overflow-y-auto">
        <Outlet context={{ openMobileSidebar: () => setMobileSidebarOpen(true) }} />
      </div>
    </div>
  );
};
