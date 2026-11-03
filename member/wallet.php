<?php
/**
 * Veritolytics - Points Wallet & Transaction Ledger
 * Pure Core PHP 8.2+ Architecture
 */

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_check.php';

$user = require_login();
$pageTitle = 'Points Wallet & Financial Ledger';
$db = get_db();

// Filter transactions by type
$filter = trim($_GET['type'] ?? 'all');

$sql = "SELECT * FROM transactions WHERE user_id = :uid";
$params = [':uid' => $user['id']];

if ($filter !== 'all' && !empty($filter)) {
    $sql .= " AND type = :type";
    $params[':type'] = $filter;
}

$sql .= " ORDER BY created_at DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$transactions = $stmt->fetchAll();

// Calculate threshold progress to next $10 cashout (1,000 pts)
$minThreshold = MIN_REDEMPTION_POINTS;
$progressPct = min(100, round(($user['balance_points'] / $minThreshold) * 100));

require_once __DIR__ . '/../includes/member_header.php';
?>

<div class="portal-wrapper">
    <?php require_once __DIR__ . '/../includes/member_sidebar.php'; ?>

    <div class="portal-main">
        <?= render_flash() ?>

        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
            <div>
                <h4 class="fw-bold text-dark mb-1">Points Wallet & Cashouts</h4>
                <p class="text-muted small mb-0">Track all incoming survey credits, bonuses, and reward disbursements.</p>
            </div>
            <a href="<?= BASE_URL ?>/member/rewards.php" class="btn btn-brand-primary rounded-pill px-4 py-2 fw-bold shadow-sm">
                <i class="bi bi-gift-fill me-1"></i> Rewards Store
            </a>
        </div>

        <!-- Wallet Top Cards -->
        <div class="row g-3 mb-4">
            <div class="col-lg-4 col-md-6">
                <div class="card card-vy border-0 shadow-sm p-4 h-100 bg-white" style="background: linear-gradient(135deg, #FFFFFF 0%, #FEF9C3 150%); border: 1px solid #FEF08A !important;">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted small fw-semibold">Available Wallet Balance</span>
                        <div class="rounded-circle icon-box-yellow d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                            <i class="bi bi-coin fs-5"></i>
                        </div>
                    </div>
                    <div class="d-flex align-items-baseline gap-2 mt-1">
                        <span class="fs-1 fw-bold text-dark"><?= number_format($user['balance_points']) ?></span>
                        <span class="text-muted fw-semibold">pts</span>
                    </div>
                    <div class="text-muted small mb-3">
                        Equal to <strong class="text-success"><?= format_currency($user['balance_points'] / POINTS_PER_USD) ?> USD</strong>
                    </div>

                    <div class="mt-auto">
                        <div class="d-flex justify-content-between text-muted small mb-1" style="font-size: 0.725rem;">
                            <span>Progress to $10 Payout:</span>
                            <span class="fw-bold text-dark"><?= $progressPct ?>%</span>
                        </div>
                        <div class="progress bg-light" style="height: 6px;">
                            <div class="progress-bar bg-warning" style="width: <?= $progressPct ?>%;"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4 col-md-6">
                <div class="metric-card h-100">
                    <span class="text-muted small fw-semibold">Lifetime Research Earnings</span>
                    <div class="fs-1 fw-bold text-dark mt-2 mb-1">
                        <?= format_currency($user['lifetime_earnings_usd']) ?>
                    </div>
                    <p class="text-muted small mb-0">Cumulative value generated through verified studies completed to date.</p>
                </div>
            </div>

            <div class="col-lg-4 col-md-12">
                <div class="metric-card h-100 d-flex flex-column justify-content-between">
                    <div>
                        <span class="text-muted small fw-semibold">Redemption Rules</span>
                        <ul class="list-unstyled space-y-1 small text-muted mt-2 mb-0">
                            <li><i class="bi bi-check2 text-success me-1"></i> <strong>100 points = $1.00 USD</strong></li>
                            <li><i class="bi bi-check2 text-success me-1"></i> Minimum redemption: 1,000 points</li>
                            <li><i class="bi bi-check2 text-success me-1"></i> Fast digital processing within 1-2 days</li>
                        </ul>
                    </div>
                    <div class="pt-2 border-top mt-2">
                        <a href="<?= BASE_URL ?>/member/rewards.php" class="btn btn-outline-primary btn-sm rounded-3 w-100 fw-semibold">
                            Browse Rewards Catalog →
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Transaction History Ledger -->
        <div class="card card-vy border-0 shadow-sm p-4">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-2 mb-4">
                <h5 class="fw-bold text-dark mb-0">Transaction Audit Ledger</h5>

                <!-- Ledger Filter Buttons -->
                <div class="btn-group btn-group-sm rounded-pill overflow-hidden border">
                    <a href="<?= BASE_URL ?>/member/wallet.php?type=all" class="btn <?= $filter === 'all' ? 'btn-primary' : 'btn-light' ?> px-3">All</a>
                    <a href="<?= BASE_URL ?>/member/wallet.php?type=survey_reward" class="btn <?= $filter === 'survey_reward' ? 'btn-primary' : 'btn-light' ?> px-3">Surveys</a>
                    <a href="<?= BASE_URL ?>/member/wallet.php?type=redemption" class="btn <?= $filter === 'redemption' ? 'btn-primary' : 'btn-light' ?> px-3">Redemptions</a>
                    <a href="<?= BASE_URL ?>/member/wallet.php?type=welcome_bonus" class="btn <?= $filter === 'welcome_bonus' ? 'btn-primary' : 'btn-light' ?> px-3">Bonuses</a>
                </div>
            </div>

            <?php if (empty($transactions)): ?>
                <div class="text-center py-5 text-muted">
                    <i class="bi bi-receipt-cutoff fs-1 opacity-50 mb-2"></i>
                    <p class="mb-0">No transactions found under this category filter.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light small text-muted">
                            <tr>
                                <th>Date & Time</th>
                                <th>Description / Activity</th>
                                <th>Type</th>
                                <th class="text-end">Points Change</th>
                                <th class="text-end">USD Value</th>
                                <th class="text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($transactions as $t): ?>
                                <tr>
                                    <td class="small text-muted text-nowrap">
                                        <?= date('M j, Y • g:i A', strtotime($t['created_at'])) ?>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-dark small"><?= e($t['title']) ?></div>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-secondary border rounded-pill px-2.5 py-1 small text-capitalize">
                                            <?= str_replace('_', ' ', e($t['type'])) ?>
                                        </span>
                                    </td>
                                    <td class="text-end fw-bold small <?= $t['points'] >= 0 ? 'text-success' : 'text-danger' ?>">
                                        <?= $t['points'] >= 0 ? '+' : '' ?><?= number_format($t['points']) ?> pts
                                    </td>
                                    <td class="text-end small fw-semibold text-dark">
                                        <?= $t['amount_usd'] >= 0 ? '+' : '' ?><?= format_currency($t['amount_usd']) ?>
                                    </td>
                                    <td class="text-center">
                                        <?php
                                        $badgeClass = match($t['status']) {
                                            'completed' => 'bg-success-subtle text-success border-success-subtle',
                                            'cancelled' => 'bg-danger-subtle text-danger border-danger-subtle',
                                            default => 'bg-warning-subtle text-warning-emphasis border-warning-subtle'
                                        };
                                        ?>
                                        <span class="badge <?= $badgeClass ?> border rounded-pill px-2.5 py-1 small fw-semibold text-capitalize">
                                            <?= e($t['status']) ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

<?php require_once __DIR__ . '/../includes/member_footer.php'; ?>
