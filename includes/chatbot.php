<?php
/**
 * Veritolytics - Floating Support Chatbot & Ticket System
 * Intercom / Modern SaaS Style
 */
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/csrf.php';
?>

<!-- Floating Chatbot Launcher Button -->
<div id="chatFloatingBtn" class="chat-floating-btn" title="Veritolytics Assistant">
    <i class="bi bi-chat-dots-fill fs-4"></i>
    <span class="chat-pulse"></span>
</div>

<!-- Floating Chat Window -->
<div id="chatWindow" class="chat-window">
    <div class="chat-header d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center gap-2">
            <div class="rounded-circle bg-white text-primary p-2 d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
                <i class="bi bi-robot fs-5"></i>
            </div>
            <div>
                <h6 class="mb-0 fw-bold text-white fs-6">Veritolytics Assistant</h6>
                <small class="text-white-50" style="font-size: 0.725rem;">
                    <i class="bi bi-circle-fill text-success" style="font-size: 0.5rem;"></i> Online • Instant Help
                </small>
            </div>
        </div>
        <button type="button" id="chatCloseBtn" class="btn btn-sm btn-link text-white-50 text-decoration-none p-1">
            <i class="bi bi-x-lg fs-5"></i>
        </button>
    </div>

    <div id="chatBody" class="chat-body">
        <div class="chat-bubble assistant">
            Hello! 👋 Welcome to <strong>Veritolytics</strong>. How can I help you today?
        </div>
        <div class="chat-quick-replies">
            <button type="button" class="chat-pill" data-query="Survey Help">Survey Help</button>
            <button type="button" class="chat-pill" data-query="Points & Wallet">Points & Wallet</button>
            <button type="button" class="chat-pill" data-query="Rewards & Payouts">Rewards & Payouts</button>
            <button type="button" class="chat-pill" data-query="Account & Login">Account & Login</button>
            <button type="button" class="chat-pill" data-query="Contact Support">Contact Support</button>
        </div>
    </div>

    <div class="chat-footer">
        <form id="chatForm" class="d-flex gap-2">
            <input type="text" id="chatInput" class="form-control form-control-sm rounded-pill" placeholder="Ask a question..." autocomplete="off">
            <button type="submit" class="btn btn-sm btn-brand-primary rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 34px; height: 34px;">
                <i class="bi bi-send-fill" style="font-size: 0.8rem;"></i>
            </button>
        </form>
    </div>
</div>

<!-- Support Ticket Creation Modal -->
<div class="modal fade" id="supportTicketModal" tabindex="-1" aria-labelledby="supportTicketModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header bg-light border-bottom py-3">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-headset fs-4 text-primary"></i>
                    <div>
                        <h5 class="modal-title fw-bold mb-0 text-dark" id="supportTicketModalLabel">Submit a Support Ticket</h5>
                        <small class="text-muted">Our team responds within 24 hours.</small>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?= BASE_URL ?>/submit_ticket.php" method="POST">
                <?= csrf_field() ?>
                <div class="modal-body p-4 space-y-3">
                    <?php $u = function_exists('current_user') ? current_user() : null; ?>
                    <div class="mb-3">
                        <label class="form-label fw-semibold small text-muted">Your Full Name</label>
                        <input type="text" name="name" class="form-control rounded-3" value="<?= e($u ? ($u['first_name'] . ' ' . $u['last_name']) : '') ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold small text-muted">Email Address</label>
                        <input type="email" name="email" class="form-control rounded-3" value="<?= e($u ? $u['email'] : '') ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold small text-muted">Subject / Department</label>
                        <select name="subject" class="form-select rounded-3" required>
                            <option value="Survey Credit Inquiry">Survey Credit Inquiry</option>
                            <option value="Reward Redemption Status">Reward Redemption Status</option>
                            <option value="Account & Login Assistance">Account & Login Assistance</option>
                            <option value="Research Partner Collaboration">Research Partner Collaboration</option>
                            <option value="General Question">General Question</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold small text-muted">Message Details</label>
                        <textarea name="message" class="form-control rounded-3" rows="4" placeholder="Describe the issue or question in detail..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer border-top-0 pt-0 px-4 pb-4">
                    <button type="button" class="btn btn-light rounded-3 px-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-brand-primary rounded-3 px-4">Submit Ticket</button>
                </div>
            </form>
        </div>
    </div>
</div>
