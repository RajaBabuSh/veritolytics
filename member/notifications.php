<?php
/**
 * Veritolytics - Member Notifications Center (Premium Light Theme)
 * Pure Core PHP 8.2+ Architecture
 */

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/auth_check.php';

$user = require_login();
$pageTitle = 'Notifications Center';
$db = get_db();

// Handle Delete/Clear action
if (isset($_GET['action']) && $_GET['action'] === 'delete' && !empty($_GET['id'])) {
    if (verify_csrf_token($_GET['csrf_token'] ?? '')) {
        $delStmt = $db->prepare("DELETE FROM notifications WHERE id = :id AND user_id = :uid");
        $delStmt->execute([':id' => (int)$_GET['id'], ':uid' => $user['id']]);
        flash('success', 'Notification removed.');
    }
    redirect('/member/notifications.php');
}

$filter = $_GET['filter'] ?? 'all';

if ($filter === 'unread') {
    $stmt = $db->prepare("SELECT * FROM notifications WHERE user_id = :uid AND is_read = 0 ORDER BY created_at DESC");
} else {
    $stmt = $db->prepare("SELECT * FROM notifications WHERE user_id = :uid ORDER BY created_at DESC");
}
$stmt->execute([':uid' => $user['id']]);
$notificationsList = $stmt->fetchAll();

$unreadCountStmt = $db->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = :uid AND is_read = 0");
$unreadCountStmt->execute([':uid' => $user['id']]);
$totalUnread = (int)$unreadCountStmt->fetchColumn();

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
                        <li class="breadcrumb-item active" aria-current="page">Notifications</li>
                    </ol>
                </nav>
                <div class="d-flex align-items-center gap-2">
                    <h4 class="fw-bold text-dark mb-0">Notifications Center</h4>
                    <?php if ($totalUnread > 0): ?>
                        <span class="badge bg-danger rounded-pill"><?= $totalUnread ?> Unread</span>
                    <?php endif; ?>
                </div>
                <p class="text-muted small mb-0">Stay informed about survey matching invitations, reward approvals, and points earnings.</p>
            </div>
            <div class="d-flex align-items-center gap-2">
                <?php if ($totalUnread > 0): ?>
                    <a href="<?= BASE_URL ?>/member/mark_notifications.php" class="btn btn-outline-primary btn-sm rounded-pill px-3 py-1.5 fw-semibold">
                        <i class="bi bi-check2-all me-1"></i> Mark All as Read
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Filter Pills & List Container -->
        <div class="card card-vy border-0 shadow-sm rounded-4 bg-white overflow-hidden">
            <div class="bg-light border-bottom p-3 d-flex justify-content-between align-items-center">
                <div class="btn-group btn-group-sm" role="group">
                    <a href="<?= BASE_URL ?>/member/notifications.php?filter=all" class="btn <?= $filter === 'all' ? 'btn-primary fw-bold' : 'btn-light border text-muted' ?>">
                        All Notifications
                    </a>
                    <a href="<?= BASE_URL ?>/member/notifications.php?filter=unread" class="btn <?= $filter === 'unread' ? 'btn-primary fw-bold' : 'btn-light border text-muted' ?>">
                        Unread (<?= $totalUnread ?>)
                    </a>
                </div>
            </div>

            <div class="list-group list-group-flush">
                <?php if (empty($notificationsList)): ?>
                    <div class="p-5 text-center text-muted">
                        <div class="mx-auto rounded-circle bg-success-subtle text-success d-flex align-items-center justify-content-center mb-3" style="width: 64px; height: 64px;">
                            <i class="bi bi-check2-all fs-2"></i>
                        </div>
                        <h6 class="fw-bold text-dark mb-1">You're All Caught Up!</h6>
                        <p class="small text-muted mb-0">There are no <?= $filter === 'unread' ? 'unread' : '' ?> notifications right now. Keep your profile complete for priority alerts.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($notificationsList as $item): ?>
                        <?php 
                            $icon = match($item['type']) {
                                'survey'  => 'bi-clipboard-check-fill text-primary',
                                'reward'  => 'bi-gift-fill text-purple',
                                'success' => 'bi-check-circle-fill text-success',
                                'warning' => 'bi-exclamation-triangle-fill text-warning',
                                'system'  => 'bi-shield-check-fill text-secondary',
                                default   => 'bi-bell-fill text-primary'
                            };
                        ?>
                        <div class="list-group-item p-3 p-md-4 d-flex align-items-start gap-3 <?= $item['is_read'] ? 'bg-white' : 'bg-primary-subtle bg-opacity-10 border-start border-primary border-4' ?>">
                            <div class="rounded-circle bg-light d-flex align-items-center justify-content-center flex-shrink-0" style="width: 44px; height: 44px; font-size: 1.25rem;">
                                <i class="bi <?= $icon ?>"></i>
                            </div>
                            <div class="flex-grow-1">
                                <div class="d-flex align-items-baseline justify-content-between gap-2 mb-1">
                                    <h6 class="fw-bold text-dark mb-0 small fs-6">
                                        <?= e($item['title']) ?>
                                        <?php if (!$item['is_read']): ?>
                                            <span class="badge bg-primary rounded-pill ms-1.5" style="font-size: 0.65rem;">New</span>
                                        <?php endif; ?>
                                    </h6>
                                    <span class="text-muted small" style="font-size: 0.75rem;"><?= time_ago($item['created_at']) ?></span>
                                </div>
                                <p class="text-muted small mb-2 lh-sm"><?= e($item['message']) ?></p>
                                <div class="d-flex align-items-center gap-3">
                                    <?php if (!empty($item['link'])): ?>
                                        <a href="<?= e(BASE_URL . $item['link']) ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3 py-1 fw-semibold" style="font-size: 0.775rem;">
                                            View Details &rarr;
                                        </a>
                                    <?php endif; ?>
                                    <?php if (!$item['is_read']): ?>
                                        <a href="<?= BASE_URL ?>/member/mark_notifications.php?id=<?= $item['id'] ?>" class="text-muted small text-decoration-none">
                                            <i class="bi bi-check2"></i> Mark Read
                                        </a>
                                    <?php endif; ?>
                                    <a href="<?= BASE_URL ?>/member/notifications.php?action=delete&id=<?= $item['id'] ?>&csrf_token=<?= csrf_token() ?>" class="text-danger small text-decoration-none ms-auto" onclick="return confirm('Remove this notification?');">
                                        <i class="bi bi-trash"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

<?php require_once __DIR__ . '/../includes/member_footer.php'; ?>
