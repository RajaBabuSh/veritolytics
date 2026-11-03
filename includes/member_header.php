<?php
/**
 * Veritolytics - Member Portal Header
 * Pure Core PHP 8.2+ Architecture
 */

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/auth_check.php';

$user = require_login();
$db = get_db();

// Fetch unread notifications count and top 5 recent notifications
$notifStmt = $db->prepare("
    SELECT id, title, message, type, link, is_read, created_at
    FROM notifications 
    WHERE user_id = :uid 
    ORDER BY created_at DESC 
    LIMIT 5
");
$notifStmt->execute([':uid' => $user['id']]);
$notifications = $notifStmt->fetchAll();

$unreadCountStmt = $db->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = :uid AND is_read = 0");
$unreadCountStmt->execute([':uid' => $user['id']]);
$unreadCount = (int)$unreadCountStmt->fetchColumn();

// User Initials
$initials = strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle ?? 'Member Portal') ?> | <?= APP_NAME ?></title>
    <link rel="icon" type="image/png" href="<?= BASE_URL ?>/assets/images/veritolytics-logo.png">

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Custom Brand Stylesheet -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body class="portal-body">

<!-- Top Member Header Bar (Premium Light Theme) -->
<header class="navbar navbar-expand member-portal-header sticky-top px-3 py-2">
    <div class="container-fluid px-2">
        <!-- Brand / Mobile Toggle -->
        <div class="d-flex align-items-center gap-3">
            <button class="btn btn-sm btn-light d-lg-none border" type="button" data-bs-toggle="offcanvas" data-bs-target="#memberSidebarOffcanvas" aria-controls="memberSidebarOffcanvas">
                <i class="bi bi-list fs-5"></i>
            </button>
            <a class="navbar-brand d-flex align-items-center gap-2 m-0" href="<?= BASE_URL ?>/member/dashboard.php">
                <img src="<?= BASE_URL ?>/assets/images/veritolytics-logo.png" alt="Veritolytics" height="30" class="rounded">
                <span class="fw-bold tracking-tight text-dark fs-5 d-none d-sm-inline">VERITOLYTICS</span>
            </a>
            <span class="badge bg-primary-subtle text-primary border border-primary-subtle d-none d-md-inline-block rounded-pill px-2.5 py-1 fw-semibold small">
                Member Portal
            </span>
        </div>

        <!-- Right Side Controls: Balance, Notifications, Profile Dropdown -->
        <div class="d-flex align-items-center gap-3 ms-auto">
            <!-- Points Balance Pill (Soft Yellow Badge) -->
            <a href="<?= BASE_URL ?>/member/wallet.php" class="header-wallet-badge text-decoration-none" title="Points Wallet & Cashouts">
                <i class="bi bi-coin"></i>
                <span><?= format_points($user['balance_points']) ?></span>
                <span class="wallet-usd">≈ <?= format_currency($user['balance_points'] / POINTS_PER_USD) ?></span>
            </a>

            <!-- Notifications Dropdown -->
            <div class="dropdown">
                <button class="header-notif-btn" type="button" id="notifDropdownBtn" data-bs-toggle="dropdown" aria-expanded="false" title="Notifications">
                    <i class="bi bi-bell fs-6"></i>
                    <?php if ($unreadCount > 0): ?>
                        <span class="header-notif-badge">
                            <?= $unreadCount ?>
                        </span>
                    <?php endif; ?>
                </button>
                <div class="dropdown-menu dropdown-menu-end shadow-lg border-0 rounded-4 p-0 mt-2 dropdown-menu-vy" style="width: 330px;" aria-labelledby="notifDropdownBtn">
                    <div class="p-3 border-bottom d-flex align-items-center justify-content-between bg-light rounded-top-4">
                        <h6 class="mb-0 fw-bold fs-6 text-dark">Notifications</h6>
                        <div class="d-flex align-items-center gap-2">
                            <?php if ($unreadCount > 0): ?>
                                <a href="<?= BASE_URL ?>/member/mark_notifications.php" class="text-primary text-decoration-none small fw-semibold">Mark all read</a>
                                <span class="text-muted">&bull;</span>
                            <?php endif; ?>
                            <a href="<?= BASE_URL ?>/member/notifications.php" class="text-secondary text-decoration-none small">View all</a>
                        </div>
                    </div>
                    <div class="list-group list-group-flush" style="max-height: 320px; overflow-y: auto;">
                        <?php if (empty($notifications)): ?>
                            <div class="p-4 text-center text-muted small">
                                <i class="bi bi-check2-circle fs-3 text-success d-block mb-1"></i>
                                You're all caught up! No notifications.
                            </div>
                        <?php else: ?>
                            <?php foreach ($notifications as $n): ?>
                                <a href="<?= e($n['link'] ? (BASE_URL . $n['link']) : '#') ?>" class="list-group-item list-group-item-action p-3 <?= $n['is_read'] ? 'bg-white' : 'bg-primary-subtle bg-opacity-25' ?>">
                                    <div class="d-flex align-items-start gap-2">
                                        <i class="bi <?= $n['is_read'] ? 'bi-bell text-secondary' : 'bi-bell-fill text-primary' ?> mt-1"></i>
                                        <div class="flex-grow-1">
                                            <div class="fw-bold small text-dark"><?= e($n['title']) ?></div>
                                            <div class="text-muted small lh-sm mt-0.5"><?= e($n['message']) ?></div>
                                            <small class="text-secondary" style="font-size: 0.7rem;"><?= time_ago($n['created_at']) ?></small>
                                        </div>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Profile Menu Dropdown -->
            <div class="dropdown">
                <button class="header-user-btn" type="button" id="userMenuBtn" data-bs-toggle="dropdown" aria-expanded="false">
                    <?= user_avatar_html($user, 32) ?>
                    <span class="fw-semibold small d-none d-sm-inline text-dark"><?= e($user['first_name']) ?></span>
                    <i class="bi bi-chevron-down small text-muted"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0 rounded-4 p-2 mt-2 dropdown-menu-vy" style="min-width: 250px;" aria-labelledby="userMenuBtn">
                    <li class="px-3 py-2 border-bottom mb-1 bg-light rounded-top-3">
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <?= user_avatar_html($user, 36) ?>
                            <div class="overflow-hidden">
                                <div class="fw-bold text-dark small text-truncate"><?= e($user['first_name'] . ' ' . $user['last_name']) ?></div>
                                <div class="text-muted small text-truncate" style="font-size: 0.75rem;"><?= e($user['email']) ?></div>
                            </div>
                        </div>
                        <div class="mt-1">
                            <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2 py-0.5" style="font-size: 0.65rem;">
                                <i class="bi bi-patch-check-fill me-1"></i> Active Verified
                            </span>
                        </div>
                    </li>
                    <li><a class="dropdown-item rounded-3 py-2" href="<?= BASE_URL ?>/member/profile.php"><i class="bi bi-person me-2 text-primary"></i> My Profile</a></li>
                    <li><a class="dropdown-item rounded-3 py-2" href="<?= BASE_URL ?>/member/wallet.php"><i class="bi bi-wallet2 me-2 text-success"></i> Wallet & Cashouts</a></li>
                    <li><a class="dropdown-item rounded-3 py-2" href="<?= BASE_URL ?>/member/settings.php"><i class="bi bi-gear me-2 text-secondary"></i> Account Settings</a></li>
                    <?php if ($user['role'] === 'admin'): ?>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item rounded-3 py-2 text-primary fw-semibold" href="<?= BASE_URL ?>/admin/dashboard.php"><i class="bi bi-shield-lock me-2"></i> Admin Panel</a></li>
                    <?php endif; ?>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <button type="button" class="dropdown-item rounded-3 py-2 text-danger fw-semibold" data-bs-toggle="modal" data-bs-target="#logoutConfirmModal">
                            <i class="bi bi-box-arrow-right me-2"></i> Log Out
                        </button>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</header>

<!-- Global Logout Confirmation Dialog Modal -->
<div class="modal fade" id="logoutConfirmModal" tabindex="-1" aria-labelledby="logoutModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-body p-4 text-center">
                <div class="mx-auto rounded-circle bg-danger-subtle text-danger d-flex align-items-center justify-content-center mb-3" style="width: 52px; height: 52px;">
                    <i class="bi bi-box-arrow-right fs-3"></i>
                </div>
                <h5 class="fw-bold text-dark mb-1">Sign Out?</h5>
                <p class="text-muted small mb-4">Are you sure you want to end your Veritolytics session?</p>
                <div class="d-flex gap-2 justify-content-center">
                    <button type="button" class="btn btn-light rounded-3 px-3 w-50" data-bs-dismiss="modal">Cancel</button>
                    <a href="<?= BASE_URL ?>/auth/logout.php" class="btn btn-danger rounded-3 px-3 w-50 fw-semibold">Log Out</a>
                </div>
            </div>
        </div>
    </div>
</div>
