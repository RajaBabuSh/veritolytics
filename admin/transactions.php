<?php
/**
 * Veritolytics - Master Ledger Audit Trail
 * Pure Core PHP 8.2+ Architecture
 */

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_check.php';

$admin = require_admin();
$pageTitle = 'Master Ledger Audit Trail';
$db = get_db();

$typeFilter = trim($_GET['type'] ?? 'all');
$search = trim($_GET['search'] ?? '');

$sql = "
    SELECT t.*, u.first_name, u.last_name, u.email
    FROM transactions t
    JOIN users u ON u.id = t.user_id
    WHERE 1=1
";
$params = [];

if ($typeFilter !== 'all' && !empty($typeFilter)) {
    $sql .= " AND t.type = :type";
    $params[':type'] = $typeFilter;
}

if (!empty($search)) {
    $sql .= " AND (u.first_name LIKE :s1 OR u.last_name LIKE :s2 OR u.email LIKE :s3 OR t.title LIKE :s4)";
    $params[':s1'] = "%{$search}%";
    $params[':s2'] = "%{$search}%";
    $params[':s3'] = "%{$search}%";
    $params[':s4'] = "%{$search}%";
}

$sql .= " ORDER BY t.created_at DESC LIMIT 150";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$transactions = $stmt->fetchAll();

require_once __DIR__ . '/../includes/admin_header.php';
?>

<div class="portal-wrapper">
    <?php require_once __DIR__ . '/../includes/admin_sidebar.php'; ?>

    <div class="portal-main">
        <?= render_flash() ?>

        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
            <div>
                <h4 class="fw-bold text-dark mb-1">Financial & Points Ledger Audit</h4>
                <p class="text-muted small mb-0">Immutable records of all survey payouts, welcome bonuses, and redemption debits.</p>
            </div>
            <span class="badge bg-light text-dark border rounded-pill px-3 py-1.5 small fw-semibold">
                Records: <?= count($transactions) ?>
            </span>
        </div>

        <!-- Filter Bar -->
        <div class="card card-vy border-0 shadow-sm p-3 mb-4">
            <form method="GET" action="<?= BASE_URL ?>/admin/transactions.php" class="row g-2 align-items-center">
                <div class="col-md-6">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-search"></i></span>
                        <input type="text" name="search" class="form-control border-start-0 ps-0" placeholder="Search user or transaction title..." value="<?= e($search) ?>">
                    </div>
                </div>
                <div class="col-md-4">
                    <select name="type" class="form-select form-select-sm">
                        <option value="all">All Transaction Types</option>
                        <option value="survey_reward" <?= $typeFilter === 'survey_reward' ? 'selected' : '' ?>>Survey Rewards</option>
                        <option value="welcome_bonus" <?= $typeFilter === 'welcome_bonus' ? 'selected' : '' ?>>Welcome Bonuses</option>
                        <option value="redemption" <?= $typeFilter === 'redemption' ? 'selected' : '' ?>>Redemptions</option>
                        <option value="bonus" <?= $typeFilter === 'bonus' ? 'selected' : '' ?>>Profile Bonuses</option>
                        <option value="adjustment" <?= $typeFilter === 'adjustment' ? 'selected' : '' ?>>Admin Adjustments</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex gap-1">
                    <button type="submit" class="btn btn-sm btn-primary w-100 rounded-3">Filter</button>
                    <a href="<?= BASE_URL ?>/admin/transactions.php" class="btn btn-sm btn-light border rounded-3">Reset</a>
                </div>
            </form>
        </div>

        <!-- Transactions Ledger Table -->
        <div class="card card-vy border-0 shadow-sm p-4">
            <?php if (empty($transactions)): ?>
                <div class="text-center py-4 text-muted small">No transactions matched your search criteria.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light small text-muted">
                            <tr>
                                <th>Date & Time</th>
                                <th>Member</th>
                                <th>Activity / Title</th>
                                <th>Type</th>
                                <th class="text-end">Points Delta</th>
                                <th class="text-end">USD Value</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($transactions as $t): ?>
                                <tr>
                                    <td class="small text-muted text-nowrap">
                                        <?= date('M j, Y • g:i A', strtotime($t['created_at'])) ?>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-dark small"><?= e($t['first_name'] . ' ' . $t['last_name']) ?></div>
                                        <small class="text-muted"><?= e($t['email']) ?></small>
                                    </td>
                                    <td>
                                        <div class="fw-semibold small text-dark"><?= e($t['title']) ?></div>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-secondary border rounded-pill small text-capitalize">
                                            <?= str_replace('_', ' ', e($t['type'])) ?>
                                        </span>
                                    </td>
                                    <td class="text-end fw-bold small <?= $t['points'] >= 0 ? 'text-success' : 'text-danger' ?>">
                                        <?= $t['points'] >= 0 ? '+' : '' ?><?= number_format($t['points']) ?> pts
                                    </td>
                                    <td class="text-end small fw-semibold text-dark">
                                        <?= $t['amount_usd'] >= 0 ? '+' : '' ?><?= format_currency($t['amount_usd']) ?>
                                    </td>
                                    <td>
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

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
