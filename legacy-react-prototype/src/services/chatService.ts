import { supabase, isSupabaseConfigured } from '@/lib/supabase';

export interface ChatMessage {
  id: string;
  sender: 'assistant' | 'user' | 'system';
  text: string;
  timestamp: string;
  quickReplies?: string[];
  isTicketForm?: boolean;
}

export interface SupportTicket {
  id: string;
  ticketNumber: string;
  userId?: string;
  userEmail: string;
  subject: string;
  message: string;
  status: 'open' | 'in_review' | 'resolved';
  createdAt: string;
}

// In-memory fallback tickets list
let localTickets: SupportTicket[] = [];

// Predefined knowledge base response engine
const FAQ_KNOWLEDGE_BASE: Record<string, { answer: string; relatedTopics?: string[] }> = {
  'survey help': {
    answer:
      'Surveys on Veritolytics are matched in real-time based on your demographic profile. If you see no surveys available, researchers may have filled their current quotas or no cohort matches your profile yet. Make sure your demographics in Settings are 100% complete to receive priority invites!',
    relatedTopics: ['Points & Wallet', 'Contact Support'],
  },
  'points & wallet': {
    answer:
      'Every 100 points equals $1.00 USD value. Points credit automatically to your wallet upon completing verified surveys. Your available balance can be redeemed in the Wallet tab once you reach 1,000 points ($10.00 USD).',
    relatedTopics: ['Rewards & Payouts', 'Survey Help'],
  },
  'rewards & payouts': {
    answer:
      'You can redeem points starting at 1,000 points ($10.00 USD) for PayPal cash, Amazon.com eGift codes, Apple cards, or Virtual Visa cards. All redemptions are submitted to our admin ledger for review and released within 1-2 business days.',
    relatedTopics: ['Points & Wallet', 'Contact Support'],
  },
  'account & login': {
    answer:
      'You can update your personal demographic details anytime under Member Settings. If you need to change your password, you can use the Forgot Password link on the Sign In page or update it in Settings.',
    relatedTopics: ['Contact Support', 'Points & Wallet'],
  },
};

export const chatService = {
  // Modular bot response engine (can easily plug into Gemini / external AI API in the future)
  async processUserQuery(query: string): Promise<ChatMessage> {
    const normalized = query.toLowerCase().trim();

    // Check FAQ intents
    if (normalized.includes('survey') || normalized.includes('study') || normalized.includes('cohort')) {
      return {
        id: 'msg-' + Date.now(),
        sender: 'assistant',
        text: FAQ_KNOWLEDGE_BASE['survey help'].answer,
        timestamp: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }),
        quickReplies: ['Points & Wallet', 'Rewards & Payouts', 'Contact Support'],
      };
    }

    if (normalized.includes('point') || normalized.includes('wallet') || normalized.includes('balance') || normalized.includes('earn')) {
      return {
        id: 'msg-' + Date.now(),
        sender: 'assistant',
        text: FAQ_KNOWLEDGE_BASE['points & wallet'].answer,
        timestamp: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }),
        quickReplies: ['Rewards & Payouts', 'Survey Help', 'Contact Support'],
      };
    }

    if (normalized.includes('reward') || normalized.includes('payout') || normalized.includes('paypal') || normalized.includes('cashout') || normalized.includes('redeem')) {
      return {
        id: 'msg-' + Date.now(),
        sender: 'assistant',
        text: FAQ_KNOWLEDGE_BASE['rewards & payouts'].answer,
        timestamp: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }),
        quickReplies: ['Points & Wallet', 'Contact Support'],
      };
    }

    if (normalized.includes('account') || normalized.includes('login') || normalized.includes('password') || normalized.includes('email') || normalized.includes('profile')) {
      return {
        id: 'msg-' + Date.now(),
        sender: 'assistant',
        text: FAQ_KNOWLEDGE_BASE['account & login'].answer,
        timestamp: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }),
        quickReplies: ['Survey Help', 'Contact Support'],
      };
    }

    if (normalized.includes('contact') || normalized.includes('support') || normalized.includes('human') || normalized.includes('ticket') || normalized.includes('agent')) {
      return {
        id: 'msg-' + Date.now(),
        sender: 'assistant',
        text: 'I can connect you directly with our customer support team! Please fill in your subject and issue below to create an official support ticket.',
        timestamp: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }),
        isTicketForm: true,
      };
    }

    // Default intelligent fallback
    return {
      id: 'msg-' + Date.now(),
      sender: 'assistant',
      text: "I'm the Veritolytics Automated Assistant. I can help answer questions about your surveys, point balances, wallet payouts, and account settings. You can also create a support ticket if you need human assistance.",
      timestamp: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }),
      quickReplies: ['Survey Help', 'Points & Wallet', 'Rewards & Payouts', 'Account & Login', 'Contact Support'],
    };
  },

  // Save support ticket securely in Supabase or runtime fallback
  async createTicket(ticketData: {
    userId?: string;
    userEmail: string;
    subject: string;
    message: string;
  }): Promise<{ ticketNumber: string; error: string | null }> {
    const ticketNum = 'VT-' + Math.floor(1000 + Math.random() * 9000);

    const ticket: SupportTicket = {
      id: 'tkt-' + Date.now(),
      ticketNumber: ticketNum,
      userId: ticketData.userId,
      userEmail: ticketData.userEmail,
      subject: ticketData.subject,
      message: ticketData.message,
      status: 'open',
      createdAt: new Date().toISOString(),
    };

    if (isSupabaseConfigured && supabase) {
      try {
        await supabase.from('support_tickets').insert({
          ticket_number: ticketNum,
          user_id: ticketData.userId,
          user_email: ticketData.userEmail,
          subject: ticketData.subject,
          message: ticketData.message,
          status: 'open',
        });
      } catch (err) {
        console.warn('Support ticket saved locally (table not yet created):', err);
      }
    }

    localTickets = [ticket, ...localTickets];
    return { ticketNumber: ticketNum, error: null };
  },
};
