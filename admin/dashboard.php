<?php
/**
 * Veritolytics - Admin Control Center Overview
 * Pure Core PHP 8.2+ Architecture
 */

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/auth_check.php';

$admin = require_admin();
$pageTitle = 'Admin Console Overview';
$db = get_db();

// Handle quick actions from dashboard (Approve / Reject redemption)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        flash('danger', 'Security session expired.');
        redirect('/admin/dashboard.php');
    }

    $action = $_POST['action'] ?? '';
    $redemptionId = (int)($_POST['redemption_id'] ?? 0);

    if ($redemptionId > 0 && in_array($action, ['approve', 'reject'])) {
        $redStmt = $db->prepare("SELECT * FROM redemptions WHERE id = :id");
        $redStmt->execute([':id' => $redemptionId]);
        $red = $redStmt->fetch();

        if ($red && $red['status'] === 'pending') {
            if ($action === 'approve') {
                $up = $db->prepare("UPDATE redemptions SET status = 'approved', reviewed_by = :aid, reviewed_at = NOW() WHERE id = :id");
                $up->execute([':aid' => $admin['id'], ':id' => $redemptionId]);

                add_notification(
                    (int)$red['user_id'],
                    "Reward Approved: {$red['reward_title']}! 🚀",
                    "Your payout for {$red['reward_title']} has been processed and sent to {$red['payout_destination']}.",
                    'reward',
                    '/member/rewards.php'
                );
                flash('success', "Redemption #{$redemptionId} approved and marked settled.");
            } elseif ($action === 'reject') {
                $notes = trim($_POST['notes'] ?? 'Information mismatch');
                $up = $db->prepare("UPDATE redemptions SET status = 'rejected', admin_notes = :notes, reviewed_by = :aid, reviewed_at = NOW() WHERE id = :id");
                $up->execute([':notes' => $notes, ':aid' => $admin['id'], ':id' => $redemptionId]);

                // Refund points to user
                update_user_balance((int)$red['user_id'], (int)$red['points_spent']);
                add_transaction(
                    (int)$red['user_id'],
                    'adjustment',
                    "Refunded: {$red['reward_title']} ({$notes})",
                    (int)$red['points_spent'],
                    (float)$red['amount_usd']
                );

                add_notification(
                    (int)$red['user_id'],
                    "Reward Request Cancelled & Points Refunded",
                    "Your redemption request for {$red['reward_title']} was not approved ({$notes}). Your {$red['points_spent']} points were refunded.",
                    'warning',
                    '/member/rewards.php'
                );
                flash('warning', "Redemption #{$redemptionId} was rejected and points were refunded to member.");
            }
        }
    }
    redirect('/admin/dashboard.php');
}

// System KPIs
$totalMembers = (int)$db->query("SELECT COUNT(*) FROM users WHERE role = 'member'")->fetchColumn();
$activeSurveysCount = (int)$db->query("SELECT COUNT(*) FROM surveys WHERE status = 'active'")->fetchColumn();
$totalPointsCirculation = (int)$db->query("SELECT SUM(balance_points) FROM users")->fetchColumn();
$lifetimeEarningsTotal = (float)$db->query("SELECT SUM(lifetime_earnings_usd) FROM users")->fetchColumn();

// Pending Redemptions Queue
$pendingRedemptions = $db->query("
    SELECT r.*, u.first_name, u.last_name, u.email
    FROM redemptions r
    JOIN users u ON u.id = r.user_id
    WHERE r.status = 'pending'
    ORDER BY r.created_at ASC
    LIMIT 6
")->fetchAll();

// Recent Members (Order by id DESC to immediately surface newest registrations)
$recentUsers = $db->query("
    SELECT id, first_name, last_name, email, country, balance_points, created_at
    FROM users 
    WHERE role = 'member'
    ORDER BY id DESC 
    LIMIT 5
")->fetchAll();

$dbInfo = Database::getStatusInfo();

require_once __DIR__ . '/../includes/admin_header.php';
?>

<div class="portal-wrapper">
    <?php require_once __DIR__ . '/../includes/admin_sidebar.php'; ?>

    <div class="portal-main">
        <?= render_flash() ?>

        <?php if (!$dbInfo['is_mysql']): ?>
            <div class="alert alert-warning border-warning-subtle bg-warning bg-opacity-10 rounded-3 p-3 mb-4 shadow-xs small d-flex align-items-start gap-3">
                <i class="bi bi-exclamation-triangle-fill text-warning fs-4 mt-0.5"></i>
                <div class="flex-grow-1">
                    <div class="fw-bold text-dark">Database Storage Notice: SQLite Local Fallback Active</div>
                    <div class="text-muted mt-0.5">
                        Your application is running on ephemeral SQLite storage. In serverless hosting (Vercel), local container disks reset periodically.
                        To permanently retain all new member registrations, configure your MySQL database credentials (<code>DB_HOST</code>, <code>DB_USER</code>, <code>DB_PASS</code>, <code>DB_NAME</code> or <code>DATABASE_URL</code>) in Vercel Environment Variables.
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
            <div>
                <h4 class="fw-bold text-dark mb-1">Administrative Overview</h4>
                <p class="text-muted small mb-0">Platform health, financial ledger, and member redemption moderation.</p>
            </div>
            <div class="d-flex align-items-center gap-2">
                <span class="badge <?= $dbInfo['is_mysql'] ? 'bg-success-subtle text-success border-success-subtle' : 'bg-warning-subtle text-warning border-warning-subtle' ?> border rounded-pill px-3 py-1.5 small fw-semibold">
                    <i class="bi bi-database me-1"></i> Database: <?= $dbInfo['is_mysql'] ? 'MySQL Cloud' : 'SQLite Local' ?>
                </span>
                <a href="<?= BASE_URL ?>/admin/surveys.php" class="btn btn-sm btn-danger rounded-pill px-3 py-1.5 fw-bold shadow-sm">
                    <i class="bi bi-plus-circle me-1"></i> New Survey
                </a>
            </div>
        </div>

        <!-- 4 Metric KPI Cards -->
        <div class="row g-3 mb-4">
            <div class="col-xl-3 col-sm-6">
                <div class="metric-card">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted small fw-semibold">Registered Members</span>
                        <div class="metric-icon bg-primary bg-opacity-10 text-primary">
                            <i class="bi bi-people-fill"></i>
                        </div>
                    </div>
                    <div class="metric-value"><?= number_format($totalMembers) ?></div>
                    <div class="small text-muted mt-1">Verified panel participants</div>
                </div>
            </div>

            <div class="col-xl-3 col-sm-6">
                <div class="metric-card">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted small fw-semibold">Active Studies</span>
                        <div class="metric-icon bg-success bg-opacity-10 text-success">
                            <i class="bi bi-clipboard-check-fill"></i>
                        </div>
                    </div>
                    <div class="metric-value"><?= number_format($activeSurveysCount) ?></div>
                    <div class="small text-muted mt-1">Currently open for responses</div>
                </div>
            </div>

            <div class="col-xl-3 col-sm-6">
                <div class="metric-card">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted small fw-semibold">Points in System</span>
                        <div class="metric-icon bg-warning bg-opacity-10 text-warning">
                            <i class="bi bi-coin"></i>
                        </div>
                    </div>
                    <div class="metric-value"><?= number_format($totalPointsCirculation) ?></div>
                    <div class="small text-muted mt-1">≈ <?= format_currency($totalPointsCirculation / POINTS_PER_USD) ?> USD liability</div>
                </div>
            </div>

            <div class="col-xl-3 col-sm-6">
                <div class="metric-card">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted small fw-semibold">Lifetime Member Payouts</span>
                        <div class="metric-icon bg-purple bg-opacity-10 text-purple" style="color: #7C3AED;">
                            <i class="bi bi-cash-coin"></i>
                        </div>
                    </div>
                    <div class="metric-value"><?= format_currency($lifetimeEarningsTotal) ?></div>
                    <div class="small text-muted mt-1">Cumulative researcher value</div>
                </div>
            </div>
        </div>

        <!-- Pending Redemptions Action Queue -->
        <div class="card card-vy border-0 shadow-sm p-4 mb-4">
            <div class="d-flex align-items-center justify-content-between mb-3 border-bottom pb-2">
                <div>
                    <h5 class="fw-bold text-dark mb-0">Pending Redemptions Queue</h5>
                    <small class="text-muted">Review member payout requests before digital disbarment</small>
                </div>
                <a href="<?= BASE_URL ?>/admin/rewards.php" class="small text-danger fw-semibold text-decoration-none">
                    All Redemptions →
                </a>
            </div>

            <?php if (empty($pendingRedemptions)): ?>
                <div class="text-center py-4 text-muted small">
                    <i class="bi bi-check-circle fs-3 text-success d-block mb-1"></i>
                    All redemptions are currently settled. Zero pending requests!
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light small text-muted">
                            <tr>
                                <th>Member</th>
                                <th>Reward Requested</th>
                                <th>Points Spent</th>
                                <th>Value</th>
                                <th>Payout Destination</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pendingRedemptions as $pr): ?>
                                <tr>
                                    <td>
                                        <div class="fw-bold text-dark small"><?= e($pr['first_name'] . ' ' . $pr['last_name']) ?></div>
                                        <small class="text-muted"><?= e($pr['email']) ?></small>
                                    </td>
                                    <td>
                                        <span class="fw-semibold small text-dark"><?= e($pr['reward_title']) ?></span>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark border small fw-semibold">
                                            <?= number_format($pr['points_spent']) ?> pts
                                        </span>
                                    </td>
                                    <td>
                                        <span class="fw-bold text-success small"><?= format_currency($pr['amount_usd']) ?></span>
                                    </td>
                                    <td>
                                        <code class="small text-muted"><?= e($pr['payout_destination']) ?></code>
                                    </td>
                                    <td class="text-end">
                                        <div class="d-flex justify-content-end gap-1">
                                            <form method="POST" action="<?= BASE_URL ?>/admin/dashboard.php" class="d-inline">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="action" value="approve">
                                                <input type="hidden" name="redemption_id" value="<?= $pr['id'] ?>">
                                                <button type="submit" class="btn btn-sm btn-success rounded-pill px-2.5 py-1 small fw-semibold" onclick="return confirm('Approve this redemption and issue reward?');">
                                                    <i class="bi bi-check-lg me-1"></i> Approve
                                                </button>
                                            </form>
                                            <form method="POST" action="<?= BASE_URL ?>/admin/dashboard.php" class="d-inline">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="action" value="reject">
                                                <input type="hidden" name="redemption_id" value="<?= $pr['id'] ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger rounded-pill px-2.5 py-1 small fw-semibold" onclick="return confirm('Reject this redemption and refund points back to member?');">
                                                    Reject
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <!-- Recent Registrations -->
        <div class="card card-vy border-0 shadow-sm p-4">
            <h5 class="fw-bold text-dark mb-3">Recently Registered Members</h5>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light small text-muted">
                        <tr>
                            <th>User ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Country</th>
                            <th>Balance</th>
                            <th>Joined Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentUsers as $ru): ?>
                            <tr>
                                <td><code class="small text-muted">#VY-<?= str_pad((string)$ru['id'], 5, '0', STR_PAD_LEFT) ?></code></td>
                                <td class="fw-bold small text-dark"><?= e($ru['first_name'] . ' ' . $ru['last_name']) ?></td>
                                <td class="small text-muted"><?= e($ru['email']) ?></td>
                                <td class="small text-muted"><?= e($ru['country']) ?></td>
                                <td>
                                    <span class="badge bg-warning-subtle text-dark border border-warning-subtle rounded-pill small">
                                        <?= format_points($ru['balance_points']) ?>
                                    </span>
                                </td>
                                <td class="small text-muted"><?= date('M j, Y', strtotime($ru['created_at'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
