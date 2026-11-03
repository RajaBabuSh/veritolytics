/**
 * Veritolytics - Main JavaScript Engine
 * Pure Core PHP 8.2+ Architecture
 */

document.addEventListener('DOMContentLoaded', function () {
  // 1. Password Visibility Toggler
  document.querySelectorAll('.btn-toggle-password').forEach(button => {
    button.addEventListener('click', function () {
      const targetId = this.getAttribute('data-target');
      const input = document.getElementById(targetId);
      const icon = this.querySelector('i');
      if (input) {
        if (input.type === 'password') {
          input.type = 'text';
          if (icon) {
            icon.classList.remove('bi-eye');
            icon.classList.add('bi-eye-slash');
          }
        } else {
          input.type = 'password';
          if (icon) {
            icon.classList.remove('bi-eye-slash');
            icon.classList.add('bi-eye');
          }
        }
      }
    });
  });

  // 2. Chatbot Interaction Engine
  const chatBtn = document.getElementById('chatFloatingBtn');
  const chatWindow = document.getElementById('chatWindow');
  const chatCloseBtn = document.getElementById('chatCloseBtn');
  const chatForm = document.getElementById('chatForm');
  const chatInput = document.getElementById('chatInput');
  const chatBody = document.getElementById('chatBody');

  const faqData = {
    'survey help': {
      answer: 'Surveys on Veritolytics are matched in real-time based on your profile demographics. If no surveys appear right now, researcher quotas may be full. Keep your profile 100% complete for priority invites!',
      pills: ['Points & Wallet', 'Rewards & Payouts', 'Contact Support']
    },
    'points & wallet': {
      answer: 'Every 100 points equals $1.00 USD. Points credit instantly upon survey completion. You can redeem once your balance reaches 1,000 points ($10.00 USD).',
      pills: ['Rewards & Payouts', 'Survey Help', 'Contact Support']
    },
    'rewards & payouts': {
      answer: 'You can redeem points starting at 1,000 pts ($10.00 USD) for PayPal Cash, Amazon eGift codes, Apple cards, or Virtual Visa. Payouts are verified and issued within 1-2 business days.',
      pills: ['Points & Wallet', 'Contact Support']
    },
    'account & login': {
      answer: 'You can update your personal demographic details anytime under Member Settings. Use Forgot Password on the login page if you need to reset your password.',
      pills: ['Contact Support', 'Points & Wallet']
    },
    'contact support': {
      answer: 'Would you like to open an official support ticket? Click the button below to submit your inquiry directly to our research operations team.',
      pills: ['Open Support Ticket', 'Back to FAQs']
    }
  };

  if (chatBtn && chatWindow) {
    chatBtn.addEventListener('click', () => {
      const isVisible = chatWindow.style.display === 'flex';
      chatWindow.style.display = isVisible ? 'none' : 'flex';
      if (!isVisible && chatInput) chatInput.focus();
    });

    if (chatCloseBtn) {
      chatCloseBtn.addEventListener('click', () => {
        chatWindow.style.display = 'none';
      });
    }

    // Handle Quick Reply Pill clicks
    document.addEventListener('click', (e) => {
      if (e.target.classList.contains('chat-pill')) {
        const query = e.target.getAttribute('data-query') || e.target.innerText.trim();
        if (query === 'Open Support Ticket') {
          const ticketModalEl = document.getElementById('supportTicketModal');
          if (ticketModalEl && window.bootstrap) {
            const modal = new bootstrap.Modal(ticketModalEl);
            modal.show();
          } else {
            window.location.href = (window.BASE_URL || '') + '/#contact';
          }
          return;
        }
        handleUserMessage(query);
      }
    });

    // Handle User Chat Form Submit
    if (chatForm && chatInput) {
      chatForm.addEventListener('submit', (e) => {
        e.preventDefault();
        const text = chatInput.value.trim();
        if (!text) return;
        chatInput.value = '';
        handleUserMessage(text);
      });
    }

    function handleUserMessage(text) {
      appendChatMessage(text, 'user');
      
      // Simulate Bot Thinking
      setTimeout(() => {
        const lower = text.toLowerCase();
        let matched = null;

        if (lower.includes('survey') || lower.includes('study')) matched = faqData['survey help'];
        else if (lower.includes('point') || lower.includes('wallet') || lower.includes('balance') || lower.includes('earn')) matched = faqData['points & wallet'];
        else if (lower.includes('reward') || lower.includes('payout') || lower.includes('paypal') || lower.includes('cash') || lower.includes('gift')) matched = faqData['rewards & payouts'];
        else if (lower.includes('account') || lower.includes('login') || lower.includes('password') || lower.includes('profile')) matched = faqData['account & login'];
        else if (lower.includes('contact') || lower.includes('support') || lower.includes('ticket') || lower.includes('human')) matched = faqData['contact support'];
        else {
          matched = {
            answer: 'Thank you for reaching out! I can assist with surveys, wallet points, rewards payouts, or connecting you to human support.',
            pills: ['Survey Help', 'Points & Wallet', 'Rewards & Payouts', 'Contact Support']
          };
        }

        appendChatMessage(matched.answer, 'assistant', matched.pills);
      }, 400);
    }

    function appendChatMessage(text, sender, pills = []) {
      const bubble = document.createElement('div');
      bubble.className = `chat-bubble ${sender}`;
      bubble.innerText = text;
      chatBody.appendChild(bubble);

      if (pills && pills.length > 0) {
        const pillContainer = document.createElement('div');
        pillContainer.className = 'chat-quick-replies';
        pills.forEach(p => {
          const btn = document.createElement('button');
          btn.type = 'button';
          btn.className = 'chat-pill';
          btn.innerText = p;
          btn.setAttribute('data-query', p);
          pillContainer.appendChild(btn);
        });
        chatBody.appendChild(pillContainer);
      }

      chatBody.scrollTop = chatBody.scrollHeight;
    }
  }

  // 3. Real-Time Demographics Profile Percentage Recalculator
  const profileForm = document.getElementById('profileForm');
  if (profileForm) {
    const fieldsToCount = [
      'first_name', 'last_name', 'email', 'country', 'phone', 
      'date_of_birth', 'gender', 'employment_status', 'education_level'
    ];

    function updateProfileScore() {
      let score = 0;
      fieldsToCount.forEach(name => {
        const el = profileForm.elements[name];
        if (el && el.value.trim() !== '') {
          if (name === 'first_name' || name === 'last_name') score += 15;
          else score += 10;
        }
      });
      score = Math.min(score, 100);

      const bar = document.getElementById('profileProgressBar');
      const text = document.getElementById('profileProgressText');
      if (bar) {
        bar.style.width = score + '%';
        bar.setAttribute('aria-valuenow', score);
      }
      if (text) text.innerText = score + '% Complete';
    }

    profileForm.querySelectorAll('input, select, textarea').forEach(el => {
      el.addEventListener('input', updateProfileScore);
      el.addEventListener('change', updateProfileScore);
    });
  }

  // 4. Portal Sidebar Interactive Click Animation & Ripple Engine
  const portalNavLinks = document.querySelectorAll('.portal-nav-link');
  if (portalNavLinks.length > 0) {
    portalNavLinks.forEach(link => {
      // Touch and pointer down: immediate visual press feedback (0.97 scale)
      link.addEventListener('pointerdown', function (e) {
        if (e.button !== undefined && e.button !== 0) return;

        this.classList.add('nav-link-pressed');

        // Spawn dynamic ripple at exact pointer coordinates
        const rect = this.getBoundingClientRect();
        const size = Math.max(rect.width, rect.height) * 1.5;
        const x = e.clientX - rect.left - size / 2;
        const y = e.clientY - rect.top - size / 2;

        const ripple = document.createElement('span');
        ripple.className = 'portal-nav-ripple';
        ripple.style.width = `${size}px`;
        ripple.style.height = `${size}px`;
        ripple.style.left = `${x}px`;
        ripple.style.top = `${y}px`;

        this.appendChild(ripple);

        setTimeout(() => {
          if (ripple.parentNode) {
            ripple.parentNode.removeChild(ripple);
          }
        }, 500);
      });

      // Release press state
      const releasePress = function () {
        setTimeout(() => {
          link.classList.remove('nav-link-pressed');
        }, 80);
      };

      link.addEventListener('pointerup', releasePress);
      link.addEventListener('pointercancel', releasePress);
      link.addEventListener('mouseleave', releasePress);
    });
  }
});
