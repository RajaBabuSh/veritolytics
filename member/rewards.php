<?php
/**
 * Veritolytics - My Rewards & Redemption Store
 * Pure Core PHP 8.2+ Architecture
 */

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/auth_check.php';

$user = require_login();
$pageTitle = 'My Rewards & Redemptions';
$db = get_db();

// 1. Fetch catalog rewards
$rewardsStmt = $db->query("SELECT * FROM rewards WHERE is_active = 1 ORDER BY points_required ASC");
$rewardsCatalog = $rewardsStmt->fetchAll();

// 2. Fetch user's genuine redemptions
$redemptionsStmt = $db->prepare("
    SELECT id, reward_title, points_spent, amount_usd, payout_destination, status, admin_notes, created_at
    FROM redemptions
    WHERE user_id = :uid
    ORDER BY created_at DESC
");
$redemptionsStmt->execute([':uid' => $user['id']]);
$userRedemptions = $redemptionsStmt->fetchAll();

// 3. Compute Pending Rewards vs Settled/Redeemed
$pendingPoints = 0;
$pendingUsd = 0.00;
$redeemedPoints = 0;
$redeemedUsd = 0.00;

foreach ($userRedemptions as $r) {
    if ($r['status'] === 'pending') {
        $pendingPoints += (int)$r['points_spent'];
        $pendingUsd += (float)$r['amount_usd'];
    } elseif ($r['status'] === 'approved' || $r['status'] === 'settled') {
        $redeemedPoints += (int)$r['points_spent'];
        $redeemedUsd += (float)$r['amount_usd'];
    }
}

require_once __DIR__ . '/../includes/member_header.php';
?>

<div class="portal-wrapper">
    <?php require_once __DIR__ . '/../includes/member_sidebar.php'; ?>

    <div class="portal-main">
        <?= render_flash() ?>

        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
            <div>
                <h4 class="fw-bold text-dark mb-1">My Rewards & Redemptions</h4>
                <p class="text-muted small mb-0">Exchange your verified research points for PayPal cash transfers and digital gift cards.</p>
            </div>
            <a href="<?= BASE_URL ?>/member/surveys.php" class="btn btn-sm btn-outline-primary rounded-pill px-3 py-1.5 fw-semibold">
                <i class="bi bi-clipboard-data me-1"></i> Earn More Points
            </a>
        </div>

        <!-- Balances Breakdown Cards -->
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="metric-card bg-white border border-primary-subtle shadow-sm">
                    <span class="text-muted small fw-semibold">Available Reward Balance</span>
                    <div class="d-flex align-items-baseline gap-2 mt-2">
                        <span class="fs-2 fw-bold text-dark"><?= number_format($user['balance_points']) ?></span>
                        <span class="text-muted small">pts</span>
                    </div>
                    <div class="small text-muted mt-1">
                        Equivalent to <strong class="text-success"><?= format_currency($user['balance_points'] / POINTS_PER_USD) ?> USD</strong>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="metric-card">
                    <div class="d-flex align-items-center justify-content-between">
                        <span class="text-muted small fw-semibold">Pending Review Rewards</span>
                        <span class="badge bg-warning text-dark rounded-pill small">Under Review</span>
                    </div>
                    <div class="d-flex align-items-baseline gap-2 mt-2">
                        <span class="fs-2 fw-bold text-dark"><?= number_format($pendingPoints) ?></span>
                        <span class="text-muted small">pts</span>
                    </div>
                    <div class="small text-muted mt-1">
                        Locked: <strong class="text-dark"><?= format_currency($pendingUsd) ?> USD</strong>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="metric-card">
                    <div class="d-flex align-items-center justify-content-between">
                        <span class="text-muted small fw-semibold">Lifetime Redeemed Rewards</span>
                        <span class="badge bg-success-subtle text-success rounded-pill small">Disbursed</span>
                    </div>
                    <div class="d-flex align-items-baseline gap-2 mt-2">
                        <span class="fs-2 fw-bold text-dark"><?= number_format($redeemedPoints) ?></span>
                        <span class="text-muted small">pts</span>
                    </div>
                    <div class="small text-muted mt-1">
                        Total Payout: <strong class="text-success"><?= format_currency($redeemedUsd) ?> USD</strong>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section 1: Rewards Catalog -->
        <div class="card card-vy border-0 shadow-sm p-4 mb-4">
            <div class="d-flex align-items-center justify-content-between mb-3 border-bottom pb-2">
                <div>
                    <h5 class="fw-bold text-dark mb-0">Redeem Points</h5>
                    <small class="text-muted">Fixed exchange: 100 points = $1.00 USD. Minimum cashout: 1,000 points.</small>
                </div>
                <span class="badge bg-light text-dark border rounded-pill px-3 py-1.5 small fw-normal">
                    <i class="bi bi-shield-check text-success me-1"></i> 100% Guaranteed Delivery
                </span>
            </div>

            <div class="row g-3">
                <?php foreach ($rewardsCatalog as $rew): ?>
                    <?php 
                        $canRedeem = ($user['balance_points'] >= $rew['points_required']);
                        $shortage = max(0, $rew['points_required'] - $user['balance_points']);
                    ?>
                    <div class="col-xl-4 col-md-6">
                        <div class="card border rounded-4 p-3 h-100 d-flex flex-column justify-content-between hover-shadow">
                            <div>
                                <div class="d-flex align-items-start justify-content-between mb-2">
                                    <div class="p-2 rounded-3 icon-box-blue fs-4">
                                        <i class="bi <?= e($rew['icon']) ?>"></i>
                                    </div>
                                    <span class="badge bg-warning-subtle text-dark border border-warning-subtle rounded-pill px-2.5 py-1 fw-bold">
                                        <?= number_format($rew['points_required']) ?> pts
                                    </span>
                                </div>
                                <h6 class="fw-bold text-dark mb-1"><?= e($rew['title']) ?></h6>
                                <p class="text-muted small mb-3" style="font-size: 0.775rem;">
                                    <?= e($rew['delivery_type']) ?>
                                </p>
                            </div>

                            <div class="pt-3 border-top d-flex align-items-center justify-content-between">
                                <span class="fw-bold text-success fs-5">
                                    <?= format_currency($rew['cash_equivalent_usd']) ?>
                                </span>
                                <?php if ($canRedeem): ?>
                                    <button type="button" class="btn btn-sm btn-brand-primary rounded-pill px-3.5 py-1.5 fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#redeemModal_<?= $rew['id'] ?>">
                                        Redeem Now
                                    </button>
                                <?php else: ?>
                                    <button class="btn btn-sm btn-light border text-muted rounded-pill px-3 py-1.5 small" disabled title="Need <?= $shortage ?> more points">
                                        Need <?= number_format($shortage) ?> pts
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Redemption Form Modal -->
                    <?php if ($canRedeem): ?>
                        <div class="modal fade" id="redeemModal_<?= $rew['id'] ?>" tabindex="-1" aria-labelledby="redeemModalLabel_<?= $rew['id'] ?>" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered">
                                <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                                    <div class="modal-header bg-light border-bottom py-3">
                                        <div>
                                            <h5 class="modal-title fw-bold text-dark mb-0" id="redeemModalLabel_<?= $rew['id'] ?>">
                                                Confirm Redemption
                                            </h5>
                                            <small class="text-muted"><?= e($rew['title']) ?> (<?= format_currency($rew['cash_equivalent_usd']) ?>)</small>
                                        </div>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>

                                    <form method="POST" action="<?= BASE_URL ?>/member/redeem_reward.php">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="reward_id" value="<?= $rew['id'] ?>">

                                        <div class="modal-body p-4">
                                            <div class="alert alert-info bg-info-subtle border-0 rounded-3 small p-3 mb-3">
                                                <div class="d-flex justify-content-between mb-1">
                                                    <span>Cost:</span>
                                                    <strong><?= number_format($rew['points_required']) ?> points</strong>
                                                </div>
                                                <div class="d-flex justify-content-between">
                                                    <span>Your Balance After:</span>
                                                    <strong><?= number_format($user['balance_points'] - $rew['points_required']) ?> points</strong>
                                                </div>
                                            </div>

                                            <div class="mb-3">
                                                <label class="form-label fw-semibold small text-muted">
                                                    <?= str_contains($rew['title'], 'PayPal') ? 'PayPal Account Email Address *' : 'Delivery Email Address *' ?>
                                                </label>
                                                <input type="email" name="payout_destination" class="form-control rounded-3" placeholder="your-email@example.com" value="<?= e($user['email']) ?>" required>
                                                <small class="text-muted" style="font-size: 0.725rem;">
                                                    Please verify carefully. Rewards are issued digitally to this destination.
                                                </small>
                                            </div>
                                        </div>

                                        <div class="modal-footer border-top-0 pt-0 px-4 pb-4 d-flex justify-content-between">
                                            <button type="button" class="btn btn-light rounded-3 px-3" data-bs-dismiss="modal">Cancel</button>
                                            <button type="submit" class="btn btn-success rounded-3 px-4 py-2 fw-bold shadow-sm">
                                                Confirm & Redeem
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Section 2: My Redemption History (Genuine Member Redemptions) -->
        <div class="card card-vy border-0 shadow-sm p-4">
            <h5 class="fw-bold text-dark mb-3">My Redemption History</h5>

            <?php if (empty($userRedemptions)): ?>
                <!-- Beautiful Empty State as Required -->
                <div class="text-center py-5 my-2">
                    <div class="d-inline-flex p-3 rounded-circle bg-light text-muted mb-3">
                        <i class="bi bi-gift fs-1 opacity-50"></i>
                    </div>
                    <h5 class="fw-bold text-dark mb-1">You haven't earned any rewards yet</h5>
                    <p class="text-muted small mb-4" style="max-width: 440px; margin: 0 auto;">
                        Complete eligible surveys to start earning reward points. Once you reach 1,000 points ($10.00 USD), you can cash out here!
                    </p>
                    <a href="<?= BASE_URL ?>/member/surveys.php" class="btn btn-brand-primary rounded-pill px-4 py-2 fw-bold shadow-sm">
                        <i class="bi bi-play-circle-fill me-1"></i> View Available Surveys
                    </a>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light small text-muted">
                            <tr>
                                <th>Reward Item</th>
                                <th>Points Spent</th>
                                <th>USD Equivalent</th>
                                <th>Payout Destination</th>
                                <th>Status</th>
                                <th>Date Requested</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($userRedemptions as $r): ?>
                                <tr>
                                    <td>
                                        <div class="fw-bold text-dark small"><?= e($r['reward_title']) ?></div>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark border small fw-semibold">
                                            <?= number_format($r['points_spent']) ?> pts
                                        </span>
                                    </td>
                                    <td>
                                        <span class="fw-bold text-dark small">
                                            <?= format_currency($r['amount_usd']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <code class="small text-muted"><?= e($r['payout_destination']) ?></code>
                                    </td>
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
                                    <td class="small text-muted">
                                        <?= date('M j, Y • g:i A', strtotime($r['created_at'])) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

<?php require_once __DIR__ . '/../includes/member_footer.php'; ?>
