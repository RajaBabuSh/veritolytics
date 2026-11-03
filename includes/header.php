<?php
/**
 * Veritolytics - Public Header & Navigation (Dynamic World-Class Edition)
 * Pure Core PHP 8.2+ Architecture
 */

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/auth_check.php';

$pageTitle = $pageTitle ?? APP_NAME . ' | ' . APP_TAGLINE;
$currentUser = current_user();

// Notifications and User Metadata for Logged-In Members
$notifications = [];
$unreadCount = 0;
$initials = 'U';

if ($currentUser) {
    try {
        $db = get_db();
        $notifStmt = $db->prepare("
            SELECT id, title, message, type, link, is_read, created_at
            FROM notifications 
            WHERE user_id = :uid 
            ORDER BY created_at DESC 
            LIMIT 5
        ");
        $notifStmt->execute([':uid' => $currentUser['id']]);
        $notifications = $notifStmt->fetchAll();

        $unreadCountStmt = $db->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = :uid AND is_read = 0");
        $unreadCountStmt->execute([':uid' => $currentUser['id']]);
        $unreadCount = (int)$unreadCountStmt->fetchColumn();
    } catch (Exception $e) {
        $notifications = [];
        $unreadCount = 0;
    }

    $initials = strtoupper(substr($currentUser['first_name'] ?? 'U', 0, 1) . substr($currentUser['last_name'] ?? '', 0, 1));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?></title>
    <meta name="description" content="Veritopinion is an elite survey research rewards platform. Share your insights on consumer tech, media, and finance and earn verified PayPal cash and gift card rewards.">
    <link rel="icon" type="image/png" href="<?= BASE_URL ?>/assets/images/veritopinion-icon.png">

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,400&display=swap" rel="stylesheet">
    <!-- Custom Brand Stylesheet -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>

<!-- Dynamic Top Navigation Bar (Premium Light Theme) -->
<nav class="navbar navbar-expand-lg navbar-light navbar-vy-light sticky-top py-2.5">
    <div class="container">
        <!-- Brand Logo -->
        <a class="navbar-brand d-flex align-items-center text-decoration-none me-2 me-lg-3" href="<?= BASE_URL ?>/" aria-label="Veritopinion">
            <img src="<?= BASE_URL ?>/assets/images/veritopinion-logo.png" alt="Veritopinion" class="brand-logo-img">
        </a>

        <!-- Right Side Header Controls (Dynamic Authentication State) -->
        <div class="d-flex align-items-center gap-2 gap-md-2.5 order-lg-3 ms-auto ms-lg-0">
            <?php if ($currentUser): ?>
                <!-- 1. Current Wallet Balance Pill (Soft Yellow Badge) -->
                <a href="<?= BASE_URL ?>/member/wallet.php" class="header-wallet-badge text-decoration-none" title="View Points Wallet">
                    <i class="bi bi-coin"></i>
                    <span><?= format_points($currentUser['balance_points']) ?></span>
                    <span class="wallet-usd d-none d-sm-inline">≈ <?= format_currency($currentUser['balance_points'] / POINTS_PER_USD) ?></span>
                </a>

                <!-- 2. Notification Bell Dropdown -->
                <div class="dropdown">
                    <button class="header-notif-btn" type="button" id="notifDropdownHome" data-bs-toggle="dropdown" aria-expanded="false" title="Notifications">
                        <i class="bi bi-bell fs-6"></i>
                        <?php if ($unreadCount > 0): ?>
                            <span class="header-notif-badge">
                                <?= $unreadCount ?>
                            </span>
                        <?php endif; ?>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end shadow-lg border-0 rounded-4 p-0 mt-2 dropdown-menu-vy" style="width: 330px;" aria-labelledby="notifDropdownHome">
                        <div class="p-3 border-bottom d-flex align-items-center justify-content-between bg-light rounded-top-4">
                            <h6 class="mb-0 fw-bold fs-6 text-dark">Notifications</h6>
                            <?php if ($unreadCount > 0): ?>
                                <a href="<?= BASE_URL ?>/member/mark_notifications.php" class="text-primary text-decoration-none small fw-semibold">Mark all read</a>
                            <?php endif; ?>
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

                <!-- 3. Circular User Avatar + Name + Dropdown Menu -->
                <div class="dropdown">
                    <button class="header-user-btn" type="button" id="userMenuBtnHome" data-bs-toggle="dropdown" aria-expanded="false">
                        <?= user_avatar_html($currentUser, 32) ?>
                        <span class="fw-semibold small text-dark d-none d-sm-inline"><?= e($currentUser['first_name']) ?></span>
                        <i class="bi bi-chevron-down small text-muted"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0 rounded-4 p-2 mt-2 dropdown-menu-vy" style="min-width: 250px;" aria-labelledby="userMenuBtnHome">
                        <li class="px-3 py-2 border-bottom mb-1 bg-light rounded-top-3">
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <?= user_avatar_html($currentUser, 36) ?>
                                <div class="overflow-hidden">
                                    <div class="fw-bold text-dark small text-truncate"><?= e($currentUser['first_name'] . ' ' . $currentUser['last_name']) ?></div>
                                    <div class="text-muted small text-truncate" style="font-size: 0.75rem;"><?= e($currentUser['email']) ?></div>
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
                        <?php if ($currentUser['role'] === 'admin'): ?>
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
            <?php else: ?>
                <!-- Logged Out Public State (Light Theme Buttons) -->
                <a href="<?= BASE_URL ?>/auth/login.php" class="btn btn-brand-ghost rounded-pill px-3 px-sm-3.5 py-2">
                    Sign In
                </a>
                <a href="<?= BASE_URL ?>/auth/register.php" class="btn btn-brand-primary rounded-pill px-3 px-sm-4 py-2">
                    <span>Join Now</span>
                    <span class="badge bg-warning text-dark fw-bold rounded-pill ms-1" style="font-size: 0.65rem;">+500 pts</span>
                </a>
            <?php endif; ?>

            <!-- Mobile Hamburger Toggle -->
            <button class="navbar-toggler border-0 p-2 ms-1 ms-sm-2" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain" aria-controls="navbarMain" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
        </div>

        <!-- Center Collapsible Navigation Links -->
        <div class="collapse navbar-collapse order-lg-2" id="navbarMain">
            <ul class="navbar-nav mx-auto mb-2 mb-lg-0 fw-medium gap-lg-1">
                <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>/#home">Home</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>/#why-us">Why Veritolytics</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>/#how-it-works">How It Works</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>/#rewards">Rewards</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>/#categories">Categories</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>/#faq">FAQ</a></li>
            </ul>
        </div>
    </div>
</nav>

<?php if ($currentUser): ?>
<!-- Global Logout Confirmation Dialog Modal (for Public Header) -->
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
<?php endif; ?>

<main class="main-content">
    <?php 
    $flashMessages = render_flash();
    if (!empty($flashMessages)): 
    ?>
        <div class="container pt-3">
            <?= $flashMessages ?>
        </div>
    <?php endif; ?>
