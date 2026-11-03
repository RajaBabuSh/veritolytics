<?php
/**
 * Veritolytics - Member & User Management Console
 * Pure Core PHP 8.2+ Architecture
 */

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/auth_check.php';

$admin = require_admin();
$pageTitle = 'Members & User Management';
$db = get_db();

// Handle User Actions (Toggle status, adjust balance, toggle role)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        flash('danger', 'Security session expired.');
        redirect('/admin/users.php');
    }

    $action = $_POST['action'] ?? '';
    $userId = (int)($_POST['user_id'] ?? 0);

    if ($userId > 0) {
        if ($action === 'toggle_status') {
            $newStatus = ($_POST['current_status'] === 'active') ? 'suspended' : 'active';
            $up = $db->prepare("UPDATE users SET status = :status WHERE id = :id");
            $up->execute([':status' => $newStatus, ':id' => $userId]);
            flash('success', "User #{$userId} status changed to {$newStatus}.");
        } elseif ($action === 'toggle_role') {
            $newRole = ($_POST['current_role'] === 'admin') ? 'member' : 'admin';
            $up = $db->prepare("UPDATE users SET role = :role WHERE id = :id");
            $up->execute([':role' => $newRole, ':id' => $userId]);
            flash('success', "User #{$userId} role changed to {$newRole}.");
        } elseif ($action === 'adjust_points') {
            $delta = (int)($_POST['point_delta'] ?? 0);
            $reason = trim($_POST['reason'] ?? 'Administrative Adjustment');
            
            if ($delta !== 0) {
                update_user_balance($userId, $delta);
                add_transaction(
                    $userId,
                    'adjustment',
                    "Admin Balance Adjustment: {$reason}",
                    $delta,
                    $delta / POINTS_PER_USD
                );
                add_notification(
                    $userId,
                    'Points Balance Adjusted',
                    "An administrator updated your wallet by " . ($delta >= 0 ? "+{$delta}" : "{$delta}") . " points ({$reason}).",
                    'system',
                    '/member/wallet.php'
                );
                flash('success', "Successfully adjusted User #{$userId} balance by {$delta} points.");
            }
        }
    }
    redirect('/admin/users.php');
}

// Search & Filter parameters
$search = trim($_GET['search'] ?? '');
$roleFilter = trim($_GET['role'] ?? '');
$statusFilter = trim($_GET['status'] ?? '');

$sql = "SELECT * FROM users WHERE 1=1";
$params = [];

if (!empty($search)) {
    $sql .= " AND (first_name LIKE :s1 OR last_name LIKE :s2 OR email LIKE :s3)";
    $params[':s1'] = "%{$search}%";
    $params[':s2'] = "%{$search}%";
    $params[':s3'] = "%{$search}%";
}
if (!empty($roleFilter)) {
    $sql .= " AND role = :role";
    $params[':role'] = $roleFilter;
}
if (!empty($statusFilter)) {
    $sql .= " AND status = :st";
    $params[':st'] = $statusFilter;
}

$sql .= " ORDER BY created_at DESC LIMIT 100";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();

require_once __DIR__ . '/../includes/admin_header.php';
?>

<div class="portal-wrapper">
    <?php require_once __DIR__ . '/../includes/admin_sidebar.php'; ?>

    <div class="portal-main">
        <?= render_flash() ?>

        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
            <div>
                <h4 class="fw-bold text-dark mb-1">Members & Users Directory</h4>
                <p class="text-muted small mb-0">Search, review demographics, adjust balances, and manage account statuses.</p>
            </div>
            <span class="badge bg-light text-dark border rounded-pill px-3 py-1.5 small fw-semibold">
                Total Shown: <?= count($users) ?>
            </span>
        </div>

        <!-- Filter Bar -->
        <div class="card card-vy border-0 shadow-sm p-3 mb-4">
            <form method="GET" action="<?= BASE_URL ?>/admin/users.php" class="row g-2 align-items-center">
                <div class="col-md-5">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-search"></i></span>
                        <input type="text" name="search" class="form-control border-start-0 ps-0" placeholder="Search by name or email..." value="<?= e($search) ?>">
                    </div>
                </div>
                <div class="col-md-3">
                    <select name="role" class="form-select form-select-sm">
                        <option value="">All Roles</option>
                        <option value="member" <?= $roleFilter === 'member' ? 'selected' : '' ?>>Members Only</option>
                        <option value="admin" <?= $roleFilter === 'admin' ? 'selected' : '' ?>>Admins Only</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="status" class="form-select form-select-sm">
                        <option value="">All Statuses</option>
                        <option value="active" <?= $statusFilter === 'active' ? 'selected' : '' ?>>Active</option>
                        <option value="suspended" <?= $statusFilter === 'suspended' ? 'selected' : '' ?>>Suspended</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex gap-1">
                    <button type="submit" class="btn btn-sm btn-primary w-100 rounded-3">Filter</button>
                    <a href="<?= BASE_URL ?>/admin/users.php" class="btn btn-sm btn-light border rounded-3">Reset</a>
                </div>
            </form>
        </div>

        <!-- Users Table -->
        <div class="card card-vy border-0 shadow-sm p-4">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light small text-muted">
                        <tr>
                            <th>User</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Points Balance</th>
                            <th>Lifetime USD</th>
                            <th>Surveys</th>
                            <th>Country</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $u): ?>
                            <tr>
                                <td>
                                    <div class="fw-bold text-dark small"><?= e($u['first_name'] . ' ' . $u['last_name']) ?></div>
                                    <small class="text-muted"><?= e($u['email']) ?></small>
                                </td>
                                <td>
                                    <span class="badge <?= $u['role'] === 'admin' ? 'bg-danger-subtle text-danger border-danger-subtle' : 'bg-primary-subtle text-primary border-primary-subtle' ?> border rounded-pill small fw-semibold text-uppercase" style="font-size: 0.7rem;">
                                        <?= e($u['role']) ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge <?= $u['status'] === 'active' ? 'bg-success-subtle text-success border-success-subtle' : 'bg-danger-subtle text-danger border-danger-subtle' ?> border rounded-pill small fw-semibold text-capitalize">
                                        <?= e($u['status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="fw-bold text-dark small">
                                        <?= format_points($u['balance_points']) ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="small fw-semibold text-success">
                                        <?= format_currency($u['lifetime_earnings_usd']) ?>
                                    </span>
                                </td>
                                <td class="small text-muted text-center">
                                    <?= $u['completed_surveys_count'] ?>
                                </td>
                                <td class="small text-muted">
                                    <?= e($u['country']) ?>
                                </td>
                                <td class="text-end">
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-light border rounded-circle p-1" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                            <i class="bi bi-three-dots-vertical"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 rounded-3 p-2 small">
                                            <li>
                                                <button class="dropdown-item rounded-2 py-1.5" data-bs-toggle="modal" data-bs-target="#adjustModal_<?= $u['id'] ?>">
                                                    <i class="bi bi-coin text-warning me-2"></i> Adjust Points
                                                </button>
                                            </li>
                                            <li>
                                                <form method="POST" action="<?= BASE_URL ?>/admin/users.php">
                                                    <?= csrf_field() ?>
                                                    <input type="hidden" name="action" value="toggle_status">
                                                    <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                                    <input type="hidden" name="current_status" value="<?= $u['status'] ?>">
                                                    <button type="submit" class="dropdown-item rounded-2 py-1.5 <?= $u['status'] === 'active' ? 'text-danger' : 'text-success' ?>">
                                                        <i class="bi <?= $u['status'] === 'active' ? 'bi-person-x' : 'bi-person-check' ?> me-2"></i>
                                                        <?= $u['status'] === 'active' ? 'Suspend Account' : 'Activate Account' ?>
                                                    </button>
                                                </form>
                                            </li>
                                            <li>
                                                <form method="POST" action="<?= BASE_URL ?>/admin/users.php">
                                                    <?= csrf_field() ?>
                                                    <input type="hidden" name="action" value="toggle_role">
                                                    <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                                    <input type="hidden" name="current_role" value="<?= $u['role'] ?>">
                                                    <button type="submit" class="dropdown-item rounded-2 py-1.5 text-secondary" onclick="return confirm('Toggle this user role?');">
                                                        <i class="bi bi-shield-shaded me-2"></i> Toggle Admin Role
                                                    </button>
                                                </form>
                                            </li>
                                        </ul>
                                    </div>
                                </td>
                            </tr>

                            <!-- Adjust Balance Modal for User -->
                            <div class="modal fade" id="adjustModal_<?= $u['id'] ?>" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered modal-sm">
                                    <div class="modal-content border-0 shadow-lg rounded-4">
                                        <div class="modal-header border-0 pb-0">
                                            <h6 class="modal-title fw-bold">Adjust Points Balance</h6>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <form method="POST" action="<?= BASE_URL ?>/admin/users.php">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="adjust_points">
                                            <input type="hidden" name="user_id" value="<?= $u['id'] ?>">

                                            <div class="modal-body p-3">
                                                <div class="small text-muted mb-2">
                                                    User: <strong><?= e($u['first_name'] . ' ' . $u['last_name']) ?></strong><br>
                                                    Current Balance: <strong><?= format_points($u['balance_points']) ?></strong>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label small fw-semibold text-muted">Point Delta (+ or -)</label>
                                                    <input type="number" name="point_delta" class="form-control form-control-sm rounded-3" placeholder="+500 or -200" required>
                                                </div>
                                                <div class="mb-2">
                                                    <label class="form-label small fw-semibold text-muted">Audit Reason</label>
                                                    <input type="text" name="reason" class="form-control form-control-sm rounded-3" placeholder="Bonus, Correction, Promotion..." required>
                                                </div>
                                            </div>
                                            <div class="modal-footer border-0 pt-0">
                                                <button type="button" class="btn btn-sm btn-light rounded-3" data-bs-dismiss="modal">Cancel</button>
                                                <button type="submit" class="btn btn-sm btn-primary rounded-3 px-3">Apply Delta</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
