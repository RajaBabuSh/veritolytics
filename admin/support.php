<?php
/**
 * Veritolytics - Customer Support & Helpdesk Manager
 * Pure Core PHP 8.2+ Architecture
 */

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/auth_check.php';

$admin = require_admin();
$pageTitle = 'Support Tickets Helpdesk';
$db = get_db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        flash('danger', 'Security session expired. Please refresh and try again.');
        redirect('/admin/support.php');
    }

    $ticketId = (int)($_POST['ticket_id'] ?? 0);
    $statusRaw = trim((string)($_POST['status'] ?? ''));
    $allowedStatuses = ['open', 'in_review', 'resolved'];
    $newReply = trim((string)($_POST['admin_reply'] ?? ''));

    if ($ticketId <= 0) {
        flash('danger', 'Invalid support ticket ID.');
        redirect('/admin/support.php');
    }

    try {
        // Fetch existing ticket to verify existence and check current state
        $stmtFind = $db->prepare("SELECT * FROM support_tickets WHERE id = :id LIMIT 1");
        $stmtFind->execute([':id' => $ticketId]);
        $ticket = $stmtFind->fetch();

        if (!$ticket) {
            flash('danger', 'Support ticket not found or already deleted.');
            redirect('/admin/support.php');
        }

        // Validate status: If invalid, keep existing status or default to open
        $status = in_array($statusRaw, $allowedStatuses, true) ? $statusRaw : ($ticket['status'] ?? 'open');

        // Non-destructive reply handling:
        // If admin wrote a response, use it. If submitted empty, preserve previous reply!
        $hasNewReply = ($newReply !== '');
        $finalReply = $hasNewReply ? $newReply : ($ticket['admin_reply'] ?? null);
        $finalAdminId = $hasNewReply ? (int)$admin['id'] : ($ticket['admin_id'] ?? (int)$admin['id']);
        $finalRepliedAt = $hasNewReply ? date('Y-m-d H:i:s') : ($ticket['replied_at'] ?? null);
        $now = date('Y-m-d H:i:s');

        // Start transaction
        $db->beginTransaction();

        $up = $db->prepare("
            UPDATE support_tickets 
            SET status = :st, 
                admin_reply = :reply, 
                admin_id = :aid, 
                replied_at = :rat, 
                updated_at = :upat 
            WHERE id = :id
        ");
        $up->execute([
            ':st'    => $status,
            ':reply' => $finalReply,
            ':aid'   => $finalAdminId,
            ':rat'   => $finalRepliedAt,
            ':upat'  => $now,
            ':id'    => $ticketId
        ]);

        // If a new non-empty response was entered and differs from previous, log into ticket_replies
        if ($hasNewReply && $newReply !== ($ticket['admin_reply'] ?? '')) {
            try {
                $replyStmt = $db->prepare("
                    INSERT INTO ticket_replies (ticket_id, user_id, is_admin, message, created_at)
                    VALUES (:ticket_id, :user_id, 1, :message, :created_at)
                ");
                $replyStmt->execute([
                    ':ticket_id'  => $ticketId,
                    ':user_id'    => (int)$admin['id'],
                    ':message'    => $newReply,
                    ':created_at' => $now
                ]);
            } catch (Throwable $repEx) {
                error_log("Veritolytics ticket_replies logging notice: " . $repEx->getMessage());
            }
        }

        // In-app notification to member if ticket belongs to a registered user
        if (!empty($ticket['user_id'])) {
            $statusLabel = ucwords(str_replace('_', ' ', $status));
            $notifMsg = "Your support ticket #{$ticket['ticket_number']} status has been updated to '{$statusLabel}'.";
            if ($hasNewReply) {
                $preview = mb_strimwidth($newReply, 0, 90, '...');
                $notifMsg .= " Staff response: \"{$preview}\"";
            }
            add_notification(
                (int)$ticket['user_id'],
                "Update on Ticket #{$ticket['ticket_number']}",
                $notifMsg,
                'info',
                '/member/support.php'
            );
        }

        // Commit transaction only if all operations succeed
        $db->commit();
        flash('success', "Ticket #{$ticket['ticket_number']} updated successfully.");

    } catch (Throwable $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        error_log("Veritolytics Admin Support Error: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine());
        flash('danger', 'Unable to save ticket status and reply due to a database error. Please try again.');
    }

    redirect('/admin/support.php');
}

$statusFilter = trim($_GET['status'] ?? 'all');
$sql = "SELECT * FROM support_tickets";
if ($statusFilter !== 'all' && !empty($statusFilter)) {
    $sql .= " WHERE status = :st";
    $stmt = $db->prepare($sql . " ORDER BY created_at DESC");
    $stmt->execute([':st' => $statusFilter]);
} else {
    $stmt = $db->query($sql . " ORDER BY created_at DESC");
}
$tickets = $stmt->fetchAll();

require_once __DIR__ . '/../includes/admin_header.php';
?>

<div class="portal-wrapper">
    <?php require_once __DIR__ . '/../includes/admin_sidebar.php'; ?>

    <div class="portal-main">
        <?= render_flash() ?>

        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
            <div>
                <h4 class="fw-bold text-dark mb-1">Customer Support & Helpdesk</h4>
                <p class="text-muted small mb-0">Respond to user inquiries regarding survey rewards, cashout status, and account issues.</p>
            </div>
            <div class="btn-group btn-group-sm rounded-pill border overflow-hidden">
                <a href="<?= BASE_URL ?>/admin/support.php?status=all" class="btn <?= $statusFilter === 'all' ? 'btn-primary' : 'btn-light' ?> px-3">All</a>
                <a href="<?= BASE_URL ?>/admin/support.php?status=open" class="btn <?= $statusFilter === 'open' ? 'btn-primary' : 'btn-light' ?> px-3">Open</a>
                <a href="<?= BASE_URL ?>/admin/support.php?status=in_review" class="btn <?= $statusFilter === 'in_review' ? 'btn-primary' : 'btn-light' ?> px-3">In Review</a>
                <a href="<?= BASE_URL ?>/admin/support.php?status=resolved" class="btn <?= $statusFilter === 'resolved' ? 'btn-primary' : 'btn-light' ?> px-3">Resolved</a>
            </div>
        </div>

        <!-- Tickets Table -->
        <div class="card card-vy border-0 shadow-sm p-4">
            <?php if (empty($tickets)): ?>
                <div class="text-center py-4 text-muted small">No support tickets found under this filter.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light small text-muted">
                            <tr>
                                <th>Ticket #</th>
                                <th>Name / Email</th>
                                <th>Subject</th>
                                <th>Status</th>
                                <th>Priority</th>
                                <th>Date Submitted</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($tickets as $t): ?>
                                <tr>
                                    <td><code class="fw-bold small text-dark"><?= e($t['ticket_number']) ?></code></td>
                                    <td>
                                        <div class="fw-bold text-dark small"><?= e($t['name']) ?></div>
                                        <small class="text-muted"><?= e($t['email']) ?></small>
                                    </td>
                                    <td>
                                        <span class="small fw-semibold text-dark"><?= e($t['subject']) ?></span>
                                    </td>
                                    <td>
                                        <?php
                                        $badge = match($t['status']) {
                                            'resolved' => 'bg-success-subtle text-success border-success-subtle',
                                            'in_review' => 'bg-info-subtle text-info border-info-subtle',
                                            default => 'bg-danger-subtle text-danger border-danger-subtle'
                                        };
                                        ?>
                                        <span class="badge <?= $badge ?> border rounded-pill px-2.5 py-1 small fw-semibold text-capitalize">
                                            <?= str_replace('_', ' ', e($t['status'])) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-secondary border small text-capitalize"><?= e($t['priority']) ?></span>
                                    </td>
                                    <td class="small text-muted text-nowrap"><?= date('M j, Y • g:i A', strtotime($t['created_at'])) ?></td>
                                    <td class="text-end">
                                        <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-3 py-1 small fw-semibold" data-bs-toggle="modal" data-bs-target="#ticketModal_<?= $t['id'] ?>">
                                            View & Reply
                                        </button>
                                    </td>
                                </tr>

                                <!-- View & Reply Ticket Modal -->
                                <div class="modal fade" id="ticketModal_<?= $t['id'] ?>" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered modal-lg">
                                        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                                            <div class="modal-header bg-light border-bottom py-3">
                                                <div>
                                                    <h5 class="modal-title fw-bold text-dark mb-0">Ticket <?= e($t['ticket_number']) ?></h5>
                                                    <small class="text-muted"><?= e($t['subject']) ?></small>
                                                </div>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>

                                            <form method="POST" action="<?= BASE_URL ?>/admin/support.php">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="ticket_id" value="<?= $t['id'] ?>">

                                                <div class="modal-body p-4 space-y-3">
                                                    <div class="p-3 bg-light rounded-3 mb-3 border">
                                                        <div class="d-flex justify-content-between mb-2">
                                                            <div>
                                                                <strong>From:</strong> <?= e($t['name']) ?> (<code><?= e($t['email']) ?></code>)
                                                            </div>
                                                            <div class="small text-muted"><?= date('M j, Y • g:i A', strtotime($t['created_at'])) ?></div>
                                                        </div>
                                                        <div class="text-dark small leading-relaxed pt-2 border-top">
                                                            <?= nl2br(e($t['message'])) ?>
                                                        </div>
                                                    </div>

                                                    <div class="row g-3 mb-3">
                                                        <div class="col-md-6">
                                                            <label class="form-label small fw-semibold text-muted">Ticket Status</label>
                                                            <select name="status" class="form-select rounded-3">
                                                                <option value="open" <?= $t['status'] === 'open' ? 'selected' : '' ?>>Open</option>
                                                                <option value="in_review" <?= $t['status'] === 'in_review' ? 'selected' : '' ?>>In Review</option>
                                                                <option value="resolved" <?= $t['status'] === 'resolved' ? 'selected' : '' ?>>Resolved</option>
                                                            </select>
                                                        </div>
                                                    </div>

                                                    <div class="mb-3">
                                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                                            <label class="form-label small fw-semibold text-muted mb-0">Administrator Response / Resolution Note</label>
                                                            <?php if (!empty($t['replied_at'])): ?>
                                                                <small class="text-muted" style="font-size: 0.75rem;">
                                                                    <i class="bi bi-clock-history me-1"></i> Last replied: <?= date('M j, Y • g:i A', strtotime($t['replied_at'])) ?>
                                                                </small>
                                                            <?php endif; ?>
                                                        </div>
                                                        <textarea name="admin_reply" class="form-control rounded-3" rows="4" placeholder="Type official response to the user..."><?= e($t['admin_reply'] ?? '') ?></textarea>
                                                    </div>
                                                </div>

                                                <div class="modal-footer border-0 pt-0 px-4 pb-4 d-flex justify-content-between">
                                                    <button type="button" class="btn btn-light rounded-3" data-bs-dismiss="modal">Close</button>
                                                    <button type="submit" class="btn btn-primary rounded-3 px-4 fw-semibold">Save Status & Reply</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
