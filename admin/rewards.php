<?php
/**
 * Veritolytics - Admin Rewards & Redemptions Console
 * Pure Core PHP 8.2+ Architecture
 */

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/auth_check.php';

$admin = require_admin();
$pageTitle = 'Redemptions & Rewards Store Manager';
$db = get_db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        flash('danger', 'Security session expired.');
        redirect('/admin/rewards.php');
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'approve_redemption') {
        $rid = (int)($_POST['redemption_id'] ?? 0);
        $red = $db->query("SELECT * FROM redemptions WHERE id = {$rid}")->fetch();
        if ($red && $red['status'] === 'pending') {
            $up = $db->prepare("UPDATE redemptions SET status = 'approved', reviewed_by = :aid, reviewed_at = NOW() WHERE id = :id");
            $up->execute([':aid' => $admin['id'], ':id' => $rid]);

            add_notification(
                (int)$red['user_id'],
                "Payout Approved: {$red['reward_title']}! 🚀",
                "Your reward has been disbursed to {$red['payout_destination']}.",
                'reward',
                '/member/rewards.php'
            );
            flash('success', "Redemption #{$rid} approved and marked settled.");
        }
    } elseif ($action === 'reject_redemption') {
        $rid = (int)($_POST['redemption_id'] ?? 0);
        $notes = trim($_POST['rejection_notes'] ?? 'Information mismatch');
        $red = $db->query("SELECT * FROM redemptions WHERE id = {$rid}")->fetch();

        if ($red && $red['status'] === 'pending') {
            $up = $db->prepare("UPDATE redemptions SET status = 'rejected', admin_notes = :notes, reviewed_by = :aid, reviewed_at = NOW() WHERE id = :id");
            $up->execute([':notes' => $notes, ':aid' => $admin['id'], ':id' => $rid]);

            // Refund points
            update_user_balance((int)$red['user_id'], (int)$red['points_spent']);
            add_transaction(
                (int)$red['user_id'],
                'adjustment',
                "Refund: {$red['reward_title']} ({$notes})",
                (int)$red['points_spent'],
                (float)$red['amount_usd']
            );
            add_notification(
                (int)$red['user_id'],
                "Reward Request Cancelled",
                "Your redemption request for {$red['reward_title']} was not approved ({$notes}). {$red['points_spent']} points refunded to wallet.",
                'warning',
                '/member/rewards.php'
            );
            flash('warning', "Redemption #{$rid} rejected. {$red['points_spent']} points were refunded to member.");
        }
    } elseif ($action === 'add_reward_catalog') {
        $title = trim($_POST['title'] ?? '');
        $category = trim($_POST['category'] ?? 'Cash & Crypto');
        $points = (int)($_POST['points_required'] ?? 1000);
        $usd = (float)($_POST['cash_equivalent_usd'] ?? 10.00);
        $delivery = trim($_POST['delivery_type'] ?? 'Instant Digital');
        $icon = trim($_POST['icon'] ?? 'bi-gift');

        if (!empty($title) && $points > 0) {
            $ins = $db->prepare("
                INSERT INTO rewards (title, category, points_required, cash_equivalent_usd, delivery_type, icon, is_active)
                VALUES (:title, :category, :pts, :usd, :del, :icon, 1)
            ");
            $ins->execute([
                ':title' => $title,
                ':category' => $category,
                ':pts' => $points,
                ':usd' => $usd,
                ':del' => $delivery,
                ':icon' => $icon
            ]);
            flash('success', "New reward option '{$title}' added to store.");
        }
    }

    redirect('/admin/rewards.php');
}

$statusFilter = trim($_GET['status'] ?? 'all');
$sql = "
    SELECT r.*, u.first_name, u.last_name, u.email as user_email
    FROM redemptions r
    JOIN users u ON u.id = r.user_id
";
if ($statusFilter !== 'all' && !empty($statusFilter)) {
    $sql .= " WHERE r.status = :st";
    $stmt = $db->prepare($sql . " ORDER BY r.created_at DESC");
    $stmt->execute([':st' => $statusFilter]);
} else {
    $stmt = $db->query($sql . " ORDER BY r.created_at DESC");
}
$redemptions = $stmt->fetchAll();

$catalog = $db->query("SELECT * FROM rewards ORDER BY points_required ASC")->fetchAll();

require_once __DIR__ . '/../includes/admin_header.php';
?>

<div class="portal-wrapper">
    <?php require_once __DIR__ . '/../includes/admin_sidebar.php'; ?>

    <div class="portal-main">
        <?= render_flash() ?>

        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
            <div>
                <h4 class="fw-bold text-dark mb-1">Redemptions & Rewards Store</h4>
                <p class="text-muted small mb-0">Approve cash payouts, inspect digital gift card orders, and manage catalog items.</p>
            </div>
            <button type="button" class="btn btn-primary rounded-pill px-3.5 py-2 fw-semibold" data-bs-toggle="modal" data-bs-target="#newRewardCatalogModal">
                <i class="bi bi-plus-circle me-1"></i> Add Store Item
            </button>
        </div>

        <!-- Section 1: Redemptions Queue -->
        <div class="card card-vy border-0 shadow-sm p-4 mb-4">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-2 mb-3">
                <h5 class="fw-bold text-dark mb-0">Member Payout Requests</h5>
                <div class="btn-group btn-group-sm rounded-pill border overflow-hidden">
                    <a href="<?= BASE_URL ?>/admin/rewards.php?status=all" class="btn <?= $statusFilter === 'all' ? 'btn-primary' : 'btn-light' ?> px-3">All</a>
                    <a href="<?= BASE_URL ?>/admin/rewards.php?status=pending" class="btn <?= $statusFilter === 'pending' ? 'btn-primary' : 'btn-light' ?> px-3">Pending</a>
                    <a href="<?= BASE_URL ?>/admin/rewards.php?status=approved" class="btn <?= $statusFilter === 'approved' ? 'btn-primary' : 'btn-light' ?> px-3">Approved</a>
                    <a href="<?= BASE_URL ?>/admin/rewards.php?status=rejected" class="btn <?= $statusFilter === 'rejected' ? 'btn-primary' : 'btn-light' ?> px-3">Rejected</a>
                </div>
            </div>

            <?php if (empty($redemptions)): ?>
                <div class="text-center py-4 text-muted small">No redemptions found for this filter.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light small text-muted">
                            <tr>
                                <th>ID</th>
                                <th>Member</th>
                                <th>Reward</th>
                                <th>Cost</th>
                                <th>USD</th>
                                <th>Payout Destination</th>
                                <th>Status</th>
                                <th>Requested</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($redemptions as $r): ?>
                                <tr>
                                    <td><code class="small text-muted">#<?= $r['id'] ?></code></td>
                                    <td>
                                        <div class="fw-bold text-dark small"><?= e($r['first_name'] . ' ' . $r['last_name']) ?></div>
                                        <small class="text-muted"><?= e($r['user_email']) ?></small>
                                    </td>
                                    <td class="fw-semibold small text-dark"><?= e($r['reward_title']) ?></td>
                                    <td>
                                        <span class="badge bg-light text-dark border small fw-semibold">
                                            <?= number_format($r['points_spent']) ?> pts
                                        </span>
                                    </td>
                                    <td class="fw-bold text-success small"><?= format_currency($r['amount_usd']) ?></td>
                                    <td><code class="small text-muted"><?= e($r['payout_destination']) ?></code></td>
                                    <td>
                                        <?php
                                        $badgeClass = match($r['status']) {
                                            'approved', 'settled' => 'bg-success-subtle text-success border-success-subtle',
                                            'rejected' => 'bg-danger-subtle text-danger border-danger-subtle',
                                            default => 'bg-warning-subtle text-warning-emphasis border-warning-subtle'
                                        };
                                        ?>
                                        <span class="badge <?= $badgeClass ?> border rounded-pill px-2.5 py-1 small fw-semibold text-capitalize">
                                            <?= e($r['status']) ?>
                                        </span>
                                    </td>
                                    <td class="small text-muted text-nowrap"><?= date('M j, Y', strtotime($r['created_at'])) ?></td>
                                    <td class="text-end">
                                        <?php if ($r['status'] === 'pending'): ?>
                                            <div class="d-flex justify-content-end gap-1">
                                                <form method="POST" action="<?= BASE_URL ?>/admin/rewards.php" class="d-inline">
                                                    <?= csrf_field() ?>
                                                    <input type="hidden" name="action" value="approve_redemption">
                                                    <input type="hidden" name="redemption_id" value="<?= $r['id'] ?>">
                                                    <button type="submit" class="btn btn-sm btn-success rounded-pill px-2.5 py-1 small" onclick="return confirm('Approve this reward disbarment?');">
                                                        Approve
                                                    </button>
                                                </form>
                                                <button type="button" class="btn btn-sm btn-outline-danger rounded-pill px-2.5 py-1 small" data-bs-toggle="modal" data-bs-target="#rejectModal_<?= $r['id'] ?>">
                                                    Reject
                                                </button>
                                            </div>

                                            <!-- Rejection Modal -->
                                            <div class="modal fade" id="rejectModal_<?= $r['id'] ?>" tabindex="-1" aria-hidden="true">
                                                <div class="modal-dialog modal-dialog-centered modal-sm">
                                                    <div class="modal-content border-0 shadow-lg rounded-4">
                                                        <div class="modal-header border-0 pb-0">
                                                            <h6 class="modal-title fw-bold text-danger">Reject & Refund Redemption</h6>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                        </div>
                                                        <form method="POST" action="<?= BASE_URL ?>/admin/rewards.php">
                                                            <?= csrf_field() ?>
                                                            <input type="hidden" name="action" value="reject_redemption">
                                                            <input type="hidden" name="redemption_id" value="<?= $r['id'] ?>">

                                                            <div class="modal-body p-3">
                                                                <p class="small text-muted mb-2">
                                                                    Rejecting will refund <strong><?= number_format($r['points_spent']) ?> points</strong> back to <?= e($r['first_name']) ?>'s wallet.
                                                                </p>
                                                                <label class="form-label small fw-semibold text-muted">Rejection Reason</label>
                                                                <input type="text" name="rejection_notes" class="form-control form-control-sm rounded-3" placeholder="e.g. Invalid PayPal email" required>
                                                            </div>
                                                            <div class="modal-footer border-0 pt-0">
                                                                <button type="button" class="btn btn-sm btn-light rounded-3" data-bs-dismiss="modal">Cancel</button>
                                                                <button type="submit" class="btn btn-sm btn-danger rounded-3 px-3">Confirm Reject</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-muted small">—</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <!-- Section 2: Store Catalog -->
        <div class="card card-vy border-0 shadow-sm p-4">
            <h5 class="fw-bold text-dark mb-3">Rewards Catalog Items</h5>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light small text-muted">
                        <tr>
                            <th>Item Name</th>
                            <th>Category</th>
                            <th>Points Required</th>
                            <th>USD Value</th>
                            <th>Delivery Format</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($catalog as $item): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <i class="bi <?= e($item['icon']) ?> fs-5 text-primary"></i>
                                        <span class="fw-bold small text-dark"><?= e($item['title']) ?></span>
                                    </div>
                                </td>
                                <td><span class="badge bg-light text-secondary border rounded-pill small"><?= e($item['category']) ?></span></td>
                                <td class="fw-bold text-dark small"><?= number_format($item['points_required']) ?> pts</td>
                                <td class="fw-bold text-success small"><?= format_currency($item['cash_equivalent_usd']) ?></td>
                                <td class="small text-muted"><?= e($item['delivery_type']) ?></td>
                                <td>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill small">Active</span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Add Reward Catalog Modal -->
        <div class="modal fade" id="newRewardCatalogModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                    <div class="modal-header bg-light border-bottom py-3">
                        <h5 class="modal-title fw-bold text-dark mb-0">Add New Reward Item</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form method="POST" action="<?= BASE_URL ?>/admin/rewards.php">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="add_reward_catalog">

                        <div class="modal-body p-4 space-y-3">
                            <div class="mb-3">
                                <label class="form-label small fw-semibold text-muted">Reward Title *</label>
                                <input type="text" name="title" class="form-control rounded-3" placeholder="e.g. $50 Amazon Gift Code" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-semibold text-muted">Category</label>
                                <select name="category" class="form-select rounded-3">
                                    <option value="Cash & Crypto">Cash & Crypto</option>
                                    <option value="Gift Cards" selected>Gift Cards</option>
                                    <option value="Gaming & Media">Gaming & Media</option>
                                </select>
                            </div>
                            <div class="row g-2 mb-3">
                                <div class="col-6">
                                    <label class="form-label small fw-semibold text-muted">Points Required</label>
                                    <input type="number" name="points_required" class="form-control rounded-3" value="5000" step="100" required>
                                </div>
                                <div class="col-6">
                                    <label class="form-label small fw-semibold text-muted">USD Value ($)</label>
                                    <input type="number" name="cash_equivalent_usd" class="form-control rounded-3" value="50.00" step="1.00" required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-semibold text-muted">Delivery Details</label>
                                <input type="text" name="delivery_type" class="form-control rounded-3" value="Instant Digital Gift Code" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-semibold text-muted">Bootstrap Icon Class</label>
                                <input type="text" name="icon" class="form-control rounded-3" value="bi-gift" required>
                            </div>
                        </div>
                        <div class="modal-footer border-0 pt-0 px-4 pb-4">
                            <button type="button" class="btn btn-light rounded-3" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-brand-primary rounded-3 px-4">Add to Store</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
