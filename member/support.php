<?php
/**
 * Veritolytics - Member Help & Support Desk (Premium Light Theme)
 * Pure Core PHP 8.2+ Architecture
 */

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/auth_check.php';

$user = require_login();
$pageTitle = 'Help & Support';
$db = get_db();

// Handle Support Ticket Creation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        flash('danger', 'Security validation token expired. Please try again.');
        redirect('/member/support.php');
    }

    $subject  = trim((string)($_POST['subject'] ?? ''));
    $priority = in_array($_POST['priority'] ?? '', ['normal', 'high', 'urgent']) ? $_POST['priority'] : 'normal';
    $message  = trim((string)($_POST['message'] ?? ''));

    if (empty($subject) || empty($message)) {
        flash('danger', 'Please provide both a ticket subject and message description.');
    } else {
        $ticketNumber = 'TCK-' . date('Y') . '-' . str_pad((string)random_int(100, 9999), 4, '0', STR_PAD_LEFT);
        try {
            $stmt = $db->prepare("
                INSERT INTO support_tickets (ticket_number, user_id, name, email, subject, message, priority, status)
                VALUES (:ticket_number, :user_id, :name, :email, :subject, :message, :priority, 'open')
            ");
            $stmt->execute([
                ':ticket_number' => $ticketNumber,
                ':user_id'       => $user['id'],
                ':name'          => $user['first_name'] . ' ' . $user['last_name'],
                ':email'         => $user['email'],
                ':subject'       => $subject,
                ':message'       => $message,
                ':priority'      => $priority
            ]);

            add_notification(
                (int)$user['id'],
                'Support Ticket Submitted',
                'Your ticket #' . $ticketNumber . ' has been logged. Our panel support desk will respond shortly.',
                'info',
                '/member/support.php'
            );

            flash('success', "Your support inquiry ({$ticketNumber}) was submitted successfully. Our operations desk will review it shortly.");
        } catch (Throwable $e) {
            error_log("Veritolytics member/support Error: " . $e->getMessage());
            flash('danger', 'An error occurred while creating your ticket. Please try again later.');
        }
        redirect('/member/support.php');
    }
}

// Fetch member's past tickets
$ticketsStmt = $db->prepare("SELECT * FROM support_tickets WHERE user_id = :uid ORDER BY created_at DESC");
$ticketsStmt->execute([':uid' => $user['id']]);
$userTickets = $ticketsStmt->fetchAll();

require_once __DIR__ . '/../includes/member_header.php';
?>

<div class="portal-wrapper">
    <?php require_once __DIR__ . '/../includes/member_sidebar.php'; ?>

    <div class="portal-main">
        <?= render_flash() ?>

        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
            <div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-1 small">
                        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/member/dashboard.php" class="text-decoration-none">Dashboard</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Help & Support</li>
                    </ol>
                </nav>
                <h4 class="fw-bold text-dark mb-0">Member Help & Support</h4>
                <p class="text-muted small mb-0">Have an inquiry regarding reward disbursements, survey quotas, or technical issues?</p>
            </div>
            <div>
                <button type="button" class="btn btn-brand-primary btn-sm rounded-pill px-3.5 py-1.5 fw-bold shadow-xs" onclick="document.getElementById('ticket_subject').focus();">
                    <i class="bi bi-plus-circle me-1"></i> Open Support Ticket
                </button>
            </div>
        </div>

        <div class="row g-4 mb-4">
            <!-- Left: Open Support Ticket Form -->
            <div class="col-lg-7">
                <div class="card card-vy border-0 shadow-sm p-4 p-md-5 bg-white">
                    <h5 class="fw-bold text-dark mb-1">Open a Support Ticket</h5>
                    <p class="text-muted small mb-4">Submit your question directly to our panel support and verification team.</p>

                    <form method="POST" action="<?= BASE_URL ?>/member/support.php">
                        <?= csrf_field() ?>

                        <div class="mb-3">
                            <label class="form-label fw-semibold small text-dark" for="ticket_subject">Subject *</label>
                            <input type="text" id="ticket_subject" name="subject" class="form-control rounded-3" placeholder="e.g. Question regarding PayPal redemption timing" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold small text-dark" for="ticket_priority">Inquiry Priority</label>
                            <select id="ticket_priority" name="priority" class="form-select rounded-3">
                                <option value="normal" selected>Normal Inquiry (General Questions)</option>
                                <option value="high">High Priority (Payment or Redemption Issue)</option>
                                <option value="urgent">Urgent (Account Access Issue)</option>
                            </select>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-semibold small text-dark" for="ticket_message">Message Description *</label>
                            <textarea id="ticket_message" name="message" rows="5" class="form-control rounded-3" placeholder="Please describe the issue or question with relevant details..." required></textarea>
                        </div>

                        <div class="d-flex justify-content-end pt-2 border-top">
                            <button type="submit" class="btn btn-brand-primary rounded-3 px-4 py-2.5 fw-bold shadow-sm d-inline-flex align-items-center gap-2">
                                <i class="bi bi-send"></i>
                                <span>Submit Ticket</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Right: Operations Desk Hours & Quick Help -->
            <div class="col-lg-5">
                <div class="card card-vy border-0 shadow-sm p-4 mb-4 bg-white">
                    <h6 class="fw-bold text-dark mb-2 d-flex align-items-center gap-2">
                        <i class="bi bi-clock-history text-primary"></i>
                        <span>Panel Operations Desk</span>
                    </h6>
                    <p class="text-muted small mb-3">Our dedicated researcher support desk is staffed globally to assist respondents.</p>

                    <div class="p-3 bg-light rounded-3 border small mb-3">
                        <div class="d-flex justify-content-between mb-1.5">
                            <span class="text-muted">Direct Email:</span>
                            <span class="fw-bold text-dark">support@veritopinion.com</span>
                        </div>
                        <div class="d-flex justify-content-between mb-1.5">
                            <span class="text-muted">Response Time:</span>
                            <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill">Within 24 Hours</span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Desk Hours:</span>
                            <span class="fw-semibold text-dark">Mon - Fri • 9am - 6pm EST</span>
                        </div>
                    </div>

                    <h6 class="fw-bold text-dark mb-2 small text-uppercase tracking-wider">Fast Answers</h6>
                    <ul class="list-unstyled space-y-2 small text-muted mb-0">
                        <li class="mb-1.5"><i class="bi bi-check-circle-fill text-success me-1.5"></i> <strong>Point Value:</strong> 100 points = $1.00 USD fixed.</li>
                        <li class="mb-1.5"><i class="bi bi-check-circle-fill text-success me-1.5"></i> <strong>Cashout Minimum:</strong> 1,000 points ($10.00 USD).</li>
                        <li><i class="bi bi-check-circle-fill text-success me-1.5"></i> <strong>Disbursements:</strong> Processed within 1 to 2 business days.</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Past Submitted Support Tickets -->
        <div class="card card-vy border-0 shadow-sm rounded-4 bg-white overflow-hidden">
            <div class="bg-light border-bottom p-3">
                <h6 class="fw-bold text-dark mb-0">My Support Inquiries (<?= count($userTickets) ?>)</h6>
            </div>

            <?php if (empty($userTickets)): ?>
                <div class="p-5 text-center text-muted">
                    <i class="bi bi-inbox fs-2 text-muted d-block mb-2"></i>
                    <h6 class="fw-bold text-dark mb-1">No Support Tickets Yet</h6>
                    <p class="small text-muted mb-0">When you open a support inquiry, its live review status and admin response will appear here.</p>
                </div>
            <?php else: ?>
                <div class="list-group list-group-flush">
                    <?php foreach ($userTickets as $t): ?>
                        <div class="list-group-item p-4">
                            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
                                <div>
                                    <code class="fw-bold text-dark me-2">#<?= e($t['ticket_number']) ?></code>
                                    <span class="fw-bold text-dark"><?= e($t['subject']) ?></span>
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge <?= $t['status'] === 'resolved' ? 'bg-success-subtle text-success border border-success-subtle' : ($t['status'] === 'open' ? 'bg-warning-subtle text-warning-emphasis border border-warning-subtle' : 'bg-secondary-subtle text-secondary') ?> rounded-pill px-2.5 py-1 small">
                                        <?= ucfirst(e($t['status'])) ?>
                                    </span>
                                    <small class="text-muted" style="font-size: 0.75rem;"><?= time_ago($t['created_at']) ?></small>
                                </div>
                            </div>
                            <p class="small text-muted mb-2 bg-light p-3 rounded-3 border"><?= nl2br(e($t['message'])) ?></p>

                            <?php if (!empty($t['admin_reply'])): ?>
                                <div class="p-3 rounded-3 bg-primary-subtle bg-opacity-25 border-start border-primary border-3 mt-2">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <span class="small fw-bold text-primary">
                                            <i class="bi bi-reply-fill me-1"></i> Response from Veritopinion Operations Desk:
                                        </span>
                                        <?php if (!empty($t['replied_at'])): ?>
                                            <small class="text-muted" style="font-size: 0.75rem;">
                                                <?= time_ago($t['replied_at']) ?>
                                            </small>
                                        <?php endif; ?>
                                    </div>
                                    <div class="small text-dark"><?= nl2br(e($t['admin_reply'])) ?></div>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

<?php require_once __DIR__ . '/../includes/member_footer.php'; ?>
