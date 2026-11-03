import React, { useState } from 'react';
import { Outlet } from 'react-router-dom';
import { MemberSidebar } from '@/components/navigation/MemberSidebar';
import { SupportChatbot } from '@/components/chat/SupportChatbot';
import { X } from 'lucide-react';

export const MemberLayout: React.FC = () => {
  const [mobileSidebarOpen, setMobileSidebarOpen] = useState(false);

  return (
    <div className="flex min-h-screen bg-slate-50/60 antialiased relative">
      {/* Desktop Persistent Sidebar - Fixed to Viewport Height so bottom controls are always visible */}
      <div className="hidden lg:block shrink-0 sticky top-0 h-screen z-20">
        <MemberSidebar />
      </div>

      {/* Mobile Sidebar Overlay Drawer */}
      {mobileSidebarOpen && (
        <div className="fixed inset-0 z-50 flex lg:hidden">
          <div
            className="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity"
            onClick={() => setMobileSidebarOpen(false)}
          />
          <div className="relative z-50 flex w-72 h-full max-h-screen flex-col bg-white shadow-2xl">
            <button
              onClick={() => setMobileSidebarOpen(false)}
              className="absolute right-3 top-3.5 z-20 rounded-lg p-1.5 text-slate-500 hover:bg-slate-100 transition-colors"
              aria-label="Close sidebar"
            >
              <X className="h-5 w-5" />
            </button>
            <MemberSidebar onCloseMobile={() => setMobileSidebarOpen(false)} />
          </div>
        </div>
      )}

      {/* Main Content Area */}
      <div className="flex flex-1 flex-col overflow-y-auto min-w-0">
        <Outlet context={{ openMobileSidebar: () => setMobileSidebarOpen(true) }} />
      </div>

      {/* Floating Customer Support Chatbot (Fixed at bottom-right corner of viewport across all member pages) */}
      <SupportChatbot />
    </div>
  );
};
