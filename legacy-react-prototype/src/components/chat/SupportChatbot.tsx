import React, { useState, useRef, useEffect } from 'react';
import {
  MessageCircle,
  X,
  Send,
  Sparkles,
  Shield,
  HelpCircle,
  CheckCircle2,
  Ticket,
  ChevronRight,
  Bot,
  User,
  Loader2,
} from 'lucide-react';
import { Button } from '@/components/ui/Button';
import { useAuth } from '@/context/AuthContext';
import { chatService, ChatMessage } from '@/services/chatService';

export const SupportChatbot: React.FC = () => {
  const { user } = useAuth();

  const [isOpen, setIsOpen] = useState(false);
  const [hasUnread, setHasUnread] = useState(true);
  const [messages, setMessages] = useState<ChatMessage[]>([
    {
      id: 'welcome-1',
      sender: 'assistant',
      text: 'Hello! Welcome to Veritolytics. How can I help you today?',
      timestamp: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }),
      quickReplies: [
        'Survey Help',
        'Points & Wallet',
        'Rewards & Payouts',
        'Account & Login',
        'Contact Support',
      ],
    },
  ]);
  const [inputQuery, setInputQuery] = useState('');
  const [isTyping, setIsTyping] = useState(false);

  // Support Ticket Form State
  const [ticketSubject, setTicketSubject] = useState('');
  const [ticketMessage, setTicketMessage] = useState('');
  const [ticketSubmitted, setTicketSubmitted] = useState<string | null>(null);
  const [isSubmittingTicket, setIsSubmittingTicket] = useState(false);

  const messagesEndRef = useRef<HTMLDivElement>(null);

  const scrollToBottom = () => {
    messagesEndRef.current?.scrollIntoView({ behavior: 'smooth' });
  };

  useEffect(() => {
    if (isOpen) {
      scrollToBottom();
    }
  }, [messages, isOpen]);

  const handleToggleChat = () => {
    setIsOpen(!isOpen);
    if (!isOpen) {
      setHasUnread(false);
    }
  };

  const handleSendMessage = async (text: string) => {
    if (!text.trim()) return;

    const userMsg: ChatMessage = {
      id: 'user-' + Date.now(),
      sender: 'user',
      text: text.trim(),
      timestamp: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }),
    };

    setMessages((prev) => [...prev, userMsg]);
    setInputQuery('');
    setIsTyping(true);

    // Process via chat service
    setTimeout(async () => {
      const response = await chatService.processUserQuery(text);
      setMessages((prev) => [...prev, response]);
      setIsTyping(false);
    }, 450);
  };

  const handleQuickReply = (topic: string) => {
    if (topic === 'Contact Support') {
      const promptMsg: ChatMessage = {
        id: 'user-' + Date.now(),
        sender: 'user',
        text: 'I would like to contact human customer support.',
        timestamp: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }),
      };
      const assistantMsg: ChatMessage = {
        id: 'assistant-' + Date.now(),
        sender: 'assistant',
        text: 'Please describe your request below to submit a support ticket directly to our review team.',
        timestamp: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }),
        isTicketForm: true,
      };
      setMessages((prev) => [...prev, promptMsg, assistantMsg]);
    } else {
      handleSendMessage(topic);
    }
  };

  const handleSubmitTicket = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!ticketSubject.trim() || !ticketMessage.trim()) return;

    setIsSubmittingTicket(true);
    const result = await chatService.createTicket({
      userId: user?.id,
      userEmail: user?.email || 'panelist@veritolytics.com',
      subject: ticketSubject.trim(),
      message: ticketMessage.trim(),
    });

    setIsSubmittingTicket(false);
    setTicketSubmitted(result.ticketNumber);

    const confirmationMsg: ChatMessage = {
      id: 'assistant-ticket-' + Date.now(),
      sender: 'assistant',
      text: `Your support ticket #${result.ticketNumber} has been received! Our support specialists will review your account and follow up directly at ${user?.email || 'your registered email'}.`,
      timestamp: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }),
      quickReplies: ['Points & Wallet', 'Survey Help'],
    };

    setMessages((prev) => [...prev, confirmationMsg]);
    setTicketSubject('');
    setTicketMessage('');
  };

  return (
    <>
      {/* 1. FLOATING CIRCULAR CHATBOT BUTTON (Fixed at Bottom-Right: 24px / 24px) */}
      <div className="fixed bottom-6 right-6 z-50 select-none">
        <button
          type="button"
          onClick={handleToggleChat}
          aria-label={isOpen ? 'Close support chat' : 'Open customer support chat'}
          className="relative flex h-14 w-14 items-center justify-center rounded-full bg-gradient-to-tr from-brand-royal-700 via-brand-royal-600 to-brand-royal-500 text-white shadow-glow-royal hover:scale-105 active:scale-95 transition-all duration-200 focus:outline-none focus:ring-4 focus:ring-brand-royal-500/30 ring-2 ring-white"
        >
          {isOpen ? (
            <X className="h-6 w-6 transition-transform duration-200 rotate-90" />
          ) : (
            <MessageCircle className="h-6 w-6" />
          )}

          {/* Unread Notification Indicator */}
          {!isOpen && hasUnread && (
            <span className="absolute -top-1 -right-1 flex h-4 w-4">
              <span className="absolute inline-flex h-full w-full animate-ping rounded-full bg-rose-400 opacity-75" />
              <span className="relative inline-flex h-4 w-4 items-center justify-center rounded-full bg-rose-500 text-[10px] font-bold text-white shadow-sm ring-2 ring-white">
                1
              </span>
            </span>
          )}
        </button>
      </div>

      {/* 2. MODERN CHAT WINDOW (Floats Above Button) */}
      {isOpen && (
        <div className="fixed bottom-24 right-4 sm:right-6 z-50 flex w-[calc(100vw-32px)] max-w-[380px] sm:w-[390px] h-[520px] max-h-[calc(100vh-120px)] flex-col overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-2xl animate-fadeIn">
          {/* Header */}
          <div className="bg-gradient-to-r from-brand-navy-950 via-brand-navy-900 to-brand-royal-900 p-4 text-white flex items-center justify-between shrink-0 shadow-sm">
            <div className="flex items-center gap-3">
              <div className="relative flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-to-tr from-brand-royal-600 to-brand-violet-600 font-bold text-white shadow-glow-royal text-xs">
                <Bot className="h-5 w-5" />
                <span className="absolute -bottom-0.5 -right-0.5 h-2.5 w-2.5 rounded-full border-2 border-brand-navy-900 bg-emerald-400" />
              </div>
              <div>
                <h3 className="text-sm font-bold tracking-tight">Veritolytics Assistant</h3>
                <div className="flex items-center gap-1.5 text-[11px] text-slate-300">
                  <span className="h-1.5 w-1.5 rounded-full bg-emerald-400 animate-pulse" />
                  <span>Automated Support • Online</span>
                </div>
              </div>
            </div>

            <button
              type="button"
              onClick={() => setIsOpen(false)}
              className="rounded-lg p-1 text-slate-300 hover:bg-white/10 hover:text-white transition-colors"
              aria-label="Close chat window"
            >
              <X className="h-5 w-5" />
            </button>
          </div>

          {/* Messages Container */}
          <div className="flex-1 overflow-y-auto p-4 space-y-3.5 bg-slate-50/60 text-xs">
            {messages.map((msg) => (
              <div
                key={msg.id}
                className={`flex flex-col ${
                  msg.sender === 'user' ? 'items-end' : 'items-start'
                }`}
              >
                <div className="flex items-end gap-1.5 max-w-[85%]">
                  {msg.sender === 'assistant' && (
                    <div className="h-6 w-6 rounded-full bg-brand-royal-100 flex items-center justify-center text-brand-royal-700 shrink-0 text-[10px] font-bold">
                      VA
                    </div>
                  )}

                  <div
                    className={`rounded-2xl px-3.5 py-2.5 text-xs shadow-sm ${
                      msg.sender === 'user'
                        ? 'bg-brand-royal-600 text-white rounded-br-xs'
                        : 'bg-white text-slate-800 border border-slate-200/80 rounded-bl-xs'
                    }`}
                  >
                    <p className="leading-relaxed whitespace-pre-wrap">{msg.text}</p>

                    {/* Inline Support Ticket Form */}
                    {msg.isTicketForm && (
                      <form
                        onSubmit={handleSubmitTicket}
                        className="mt-3 pt-3 border-t border-slate-100 space-y-2.5"
                      >
                        <div>
                          <label className="block text-[11px] font-bold text-slate-700 mb-1">
                            Issue Category / Subject *
                          </label>
                          <input
                            type="text"
                            required
                            placeholder="e.g. Survey #104 qualification issue"
                            value={ticketSubject}
                            onChange={(e) => setTicketSubject(e.target.value)}
                            className="w-full rounded-lg border border-slate-200 px-2.5 py-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-brand-royal-500"
                          />
                        </div>
                        <div>
                          <label className="block text-[11px] font-bold text-slate-700 mb-1">
                            Detailed Description *
                          </label>
                          <textarea
                            required
                            rows={3}
                            placeholder="Provide details about your query or transaction..."
                            value={ticketMessage}
                            onChange={(e) => setTicketMessage(e.target.value)}
                            className="w-full rounded-lg border border-slate-200 px-2.5 py-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-brand-royal-500"
                          />
                        </div>
                        <Button
                          type="submit"
                          variant="royal"
                          size="sm"
                          disabled={isSubmittingTicket}
                          className="w-full text-xs font-bold gap-1.5"
                        >
                          {isSubmittingTicket ? (
                            <>
                              <Loader2 className="h-3 w-3 animate-spin" />
                              <span>Submitting Ticket...</span>
                            </>
                          ) : (
                            <>
                              <Ticket className="h-3.5 w-3.5" />
                              <span>Submit Support Ticket</span>
                            </>
                          )}
                        </Button>
                      </form>
                    )}
                  </div>
                </div>

                <span className="text-[10px] text-slate-400 mt-1 px-1">
                  {msg.timestamp}
                </span>

                {/* Quick Reply Chips */}
                {msg.quickReplies && (
                  <div className="flex flex-wrap gap-1.5 mt-2 pl-7">
                    {msg.quickReplies.map((reply) => (
                      <button
                        key={reply}
                        type="button"
                        onClick={() => handleQuickReply(reply)}
                        className="rounded-full border border-brand-royal-200 bg-white px-2.5 py-1 text-[11px] font-semibold text-brand-royal-700 hover:bg-brand-royal-50 hover:border-brand-royal-400 transition-colors shadow-2xs"
                      >
                        {reply}
                      </button>
                    ))}
                  </div>
                )}
              </div>
            ))}

            {isTyping && (
              <div className="flex items-center gap-1.5 pl-7 text-slate-400 text-xs">
                <div className="flex gap-1 bg-white border border-slate-200 rounded-full px-3 py-1.5 shadow-2xs">
                  <span className="h-1.5 w-1.5 rounded-full bg-slate-400 animate-bounce" />
                  <span className="h-1.5 w-1.5 rounded-full bg-slate-400 animate-bounce [animation-delay:0.2s]" />
                  <span className="h-1.5 w-1.5 rounded-full bg-slate-400 animate-bounce [animation-delay:0.4s]" />
                </div>
              </div>
            )}

            <div ref={messagesEndRef} />
          </div>

          {/* Chat Input Footer */}
          <div className="p-3 bg-white border-t border-slate-200 shrink-0">
            <form
              onSubmit={(e) => {
                e.preventDefault();
                handleSendMessage(inputQuery);
              }}
              className="flex items-center gap-2"
            >
              <input
                type="text"
                placeholder="Ask about surveys, points, or cashouts..."
                value={inputQuery}
                onChange={(e) => setInputQuery(e.target.value)}
                className="flex-1 rounded-xl border border-slate-200 px-3 py-2 text-xs text-slate-800 placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-brand-royal-500/20 focus:border-brand-royal-500"
              />
              <button
                type="submit"
                disabled={!inputQuery.trim()}
                className="h-8 w-8 rounded-xl bg-brand-royal-600 text-white flex items-center justify-center hover:bg-brand-royal-700 disabled:opacity-40 transition-all shrink-0 shadow-sm"
                aria-label="Send message"
              >
                <Send className="h-4 w-4" />
              </button>
            </form>
            <p className="text-[10px] text-slate-400 text-center mt-1.5">
              Veritolytics automated help system • Select &quot;Contact Support&quot; for human review.
            </p>
          </div>
        </div>
      )}
    </>
  );
};
