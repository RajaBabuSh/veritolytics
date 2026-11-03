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

// Handle User Actions (Toggle status, adjust balance, toggle role, soft-delete, restore)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        flash('danger', 'Security session expired. Please refresh and try again.');
        redirect('/admin/users.php');
    }

    $action = $_POST['action'] ?? '';
    $userId = (int)($_POST['user_id'] ?? 0);

    if ($userId > 0) {
        // Prevent deleting or suspending self
        if ($userId === (int)$admin['id'] && in_array($action, ['toggle_status', 'soft_delete'])) {
            flash('danger', 'You cannot suspend or deactivate your own administrative account.');
            redirect('/admin/users.php');
        }

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
        } elseif ($action === 'soft_delete') {
            // Soft deletion preserves all transaction history, balances and database foreign key integrity
            $up = $db->prepare("UPDATE users SET status = 'suspended', deleted_at = CURRENT_TIMESTAMP WHERE id = :id");
            $up->execute([':id' => $userId]);
            flash('warning', "User #{$userId} has been safely deactivated (soft-deleted). All points, balances, and records are preserved permanently in the database.");
        } elseif ($action === 'restore_user') {
            $up = $db->prepare("UPDATE users SET status = 'active', deleted_at = NULL WHERE id = :id");
            $up->execute([':id' => $userId]);
            flash('success', "User #{$userId} has been restored to active status.");
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
if ($statusFilter === 'active') {
    $sql .= " AND status = 'active' AND (deleted_at IS NULL)";
} elseif ($statusFilter === 'suspended') {
    $sql .= " AND status = 'suspended' AND (deleted_at IS NULL)";
} elseif ($statusFilter === 'pending') {
    $sql .= " AND status = 'pending' AND (deleted_at IS NULL)";
} elseif ($statusFilter === 'deleted') {
    $sql .= " AND deleted_at IS NOT NULL";
}

// Order by id DESC so newly registered users immediately appear at the top
$sql .= " ORDER BY id DESC LIMIT 200";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();

$dbInfo = Database::getStatusInfo();

require_once __DIR__ . '/../includes/admin_header.php';
?>

<div class="portal-wrapper">
    <?php require_once __DIR__ . '/../includes/admin_sidebar.php'; ?>

    <div class="portal-main">
        <?= render_flash() ?>

        <!-- Database Engine Status Indicator -->
        <?php if (!$dbInfo['is_mysql']): ?>
            <div class="alert alert-warning border-warning-subtle bg-warning bg-opacity-10 rounded-3 p-3 mb-4 shadow-xs small d-flex align-items-start gap-3">
                <i class="bi bi-exclamation-triangle-fill text-warning fs-4 mt-0.5"></i>
                <div class="flex-grow-1">
                    <div class="fw-bold text-dark">Database Notice: SQLite Local Fallback Mode Active (<code><?= e($dbInfo['host']) ?></code>)</div>
                    <div class="text-muted mt-0.5">
                        Your application is currently using local SQLite fallback storage. On Vercel serverless containers, local disk files are ephemeral.
                        To ensure registered users and points balances <strong>persist permanently across all Vercel container restarts</strong>, add your external MySQL database credentials (<code>DB_HOST</code>, <code>DB_USER</code>, <code>DB_PASS</code>, <code>DB_NAME</code> or <code>DATABASE_URL</code>) in your Vercel Project Settings &rarr; Environment Variables.
                    </div>
                    <?php if (!empty($dbInfo['error'])): ?>
                        <div class="mt-2 text-danger small font-monospace bg-white p-2 rounded border border-danger-subtle">
                            <strong>Connection Attempt Notice:</strong> <?= e($dbInfo['error']) ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php else: ?>
            <div class="alert alert-success border-success-subtle bg-success bg-opacity-10 rounded-3 p-2.5 mb-4 shadow-xs small d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-database-check text-success fs-5"></i>
                    <div>
                        <strong>Production Database Connected:</strong> MySQL Cloud (<code><?= e($dbInfo['host']) ?></code> / <code><?= e($dbInfo['database']) ?></code>) — All member accounts, points, and sessions are permanently stored.
                    </div>
                </div>
                <span class="badge bg-success rounded-pill px-2.5 py-1">MySQL Cloud Active</span>
            </div>
        <?php endif; ?>

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
                        <option value="active" <?= $statusFilter === 'active' ? 'selected' : '' ?>>Active Only</option>
                        <option value="suspended" <?= $statusFilter === 'suspended' ? 'selected' : '' ?>>Suspended Only</option>
                        <option value="pending" <?= $statusFilter === 'pending' ? 'selected' : '' ?>>Pending Only</option>
                        <option value="deleted" <?= $statusFilter === 'deleted' ? 'selected' : '' ?>>Deactivated / Deleted</option>
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
                            <th>User ID</th>
                            <th>Member</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Points Balance</th>
                            <th>Lifetime USD</th>
                            <th>Surveys</th>
                            <th>Country</th>
                            <th>Registered</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($users)): ?>
                            <tr>
                                <td colspan="10" class="text-center py-4 text-muted small">
                                    No user accounts matched the filter criteria.
                                </td>
                            </tr>
                        <?php endif; ?>

                        <?php foreach ($users as $u): ?>
                            <tr>
                                <td class="small text-muted font-monospace">
                                    #<?= (int)$u['id'] ?>
                                </td>
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
                                    <?php if (!empty($u['deleted_at'])): ?>
                                        <span class="badge bg-secondary-subtle text-secondary border-secondary-subtle border rounded-pill small fw-semibold">
                                            Deactivated
                                        </span>
                                    <?php elseif ($u['status'] === 'active'): ?>
                                        <span class="badge bg-success-subtle text-success border-success-subtle border rounded-pill small fw-semibold text-capitalize">
                                            Active
                                        </span>
                                    <?php elseif ($u['status'] === 'suspended'): ?>
                                        <span class="badge bg-danger-subtle text-danger border-danger-subtle border rounded-pill small fw-semibold text-capitalize">
                                            Suspended
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-warning-subtle text-warning border-warning-subtle border rounded-pill small fw-semibold text-capitalize">
                                            <?= e($u['status']) ?>
                                        </span>
                                    <?php endif; ?>
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
                                    <?= (int)$u['completed_surveys_count'] ?>
                                </td>
                                <td class="small text-muted">
                                    <?= e($u['country']) ?>
                                </td>
                                <td class="small text-muted">
                                    <?= !empty($u['created_at']) ? date('M j, Y', strtotime($u['created_at'])) : '—' ?>
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
                                            
                                            <?php if (empty($u['deleted_at'])): ?>
                                                <li>
                                                    <form method="POST" action="<?= BASE_URL ?>/admin/users.php">
                                                        <?= csrf_field() ?>
                                                        <input type="hidden" name="action" value="toggle_status">
                                                        <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                                        <input type="hidden" name="current_status" value="<?= $u['status'] ?>">
                                                        <button type="submit" class="dropdown-item rounded-2 py-1.5 <?= $u['status'] === 'active' ? 'text-warning' : 'text-success' ?>">
                                                            <i class="bi <?= $u['status'] === 'active' ? 'bi-person-slash' : 'bi-person-check' ?> me-2"></i>
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
                                                <li><hr class="dropdown-divider my-1"></li>
                                                <li>
                                                    <button class="dropdown-item rounded-2 py-1.5 text-danger" data-bs-toggle="modal" data-bs-target="#softDeleteModal_<?= $u['id'] ?>">
                                                        <i class="bi bi-trash3 me-2"></i> Deactivate Account
                                                    </button>
                                                </li>
                                            <?php else: ?>
                                                <li>
                                                    <form method="POST" action="<?= BASE_URL ?>/admin/users.php">
                                                        <?= csrf_field() ?>
                                                        <input type="hidden" name="action" value="restore_user">
                                                        <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                                        <button type="submit" class="dropdown-item rounded-2 py-1.5 text-success">
                                                            <i class="bi bi-arrow-counterclockwise me-2"></i> Restore Account
                                                        </button>
                                                    </form>
                                                </li>
                                            <?php endif; ?>
                                        </ul>
                                    </div>
                                </td>
                            </tr>

                            <!-- Adjust Balance Modal -->
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

                            <!-- Explicit Confirmation Soft-Delete Modal -->
                            <?php if (empty($u['deleted_at'])): ?>
                                <div class="modal fade" id="softDeleteModal_<?= $u['id'] ?>" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <div class="modal-content border-0 shadow-lg rounded-4">
                                            <div class="modal-header border-bottom py-3">
                                                <h6 class="modal-title fw-bold text-danger d-flex align-items-center gap-2">
                                                    <i class="bi bi-exclamation-triangle-fill"></i> Confirm Account Deactivation
                                                </h6>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <form method="POST" action="<?= BASE_URL ?>/admin/users.php">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="action" value="soft_delete">
                                                <input type="hidden" name="user_id" value="<?= $u['id'] ?>">

                                                <div class="modal-body p-4 small">
                                                    <p class="mb-3">
                                                        Are you sure you want to deactivate member account <strong><?= e($u['first_name'] . ' ' . $u['last_name']) ?></strong> (<code><?= e($u['email']) ?></code>)?
                                                    </p>
                                                    <div class="alert alert-light border small text-muted mb-0">
                                                        <i class="bi bi-shield-check text-success me-1"></i>
                                                        <strong>Safe Soft-Delete Guarantee:</strong> The member's points balance (<strong><?= format_points($u['balance_points']) ?></strong>), survey completions, and transaction records will <strong>never be purged from the database</strong>. The user will be blocked from signing in, but their record remains archived and can be restored at any time.
                                                    </div>
                                                </div>
                                                <div class="modal-footer border-top-0 pt-0 px-4 pb-4">
                                                    <button type="button" class="btn btn-light rounded-3" data-bs-dismiss="modal">Cancel</button>
                                                    <button type="submit" class="btn btn-danger rounded-3 px-3">Confirm Deactivation</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
