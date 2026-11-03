<?php
/**
 * Veritolytics - Member Sidebar Navigation (Organized & Clean)
 * Pure Core PHP 8.2+ Architecture
 */

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/functions.php';

$currentUri = $_SERVER['REQUEST_URI'] ?? '';
$activePage = basename(parse_url($currentUri, PHP_URL_PATH));

$db = get_db();
$surveyCountStmt = $db->query("SELECT COUNT(*) FROM surveys WHERE status = 'active'");
$activeSurveysCount = (int)$surveyCountStmt->fetchColumn();

$notifUnreadStmt = $db->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = :uid AND is_read = 0");
$notifUnreadStmt->execute([':uid' => $user['id']]);
$sidebarUnreadCount = (int)$notifUnreadStmt->fetchColumn();
?>

<!-- Desktop Sidebar -->
<aside class="portal-sidebar d-none d-lg-flex">
    <!-- User Quick Demographics & Balance Card -->
    <div class="sidebar-user-card p-3 mb-4 text-center">
        <?= user_avatar_html($user, 54, 'mx-auto mb-2') ?>
        <h6 class="fw-bold mb-0 text-dark"><?= e($user['first_name'] . ' ' . $user['last_name']) ?></h6>
        <small class="text-muted mb-2 d-block"><?= e(!empty($user['city']) ? ($user['city'] . ', ' . $user['country']) : $user['country']) ?></small>
        
        <div class="sidebar-balance-box p-2 mt-1">
            <span class="text-muted d-block small" style="font-size: 0.725rem;">Available Points</span>
            <span class="fw-bold fs-5 text-primary"><?= format_points($user['balance_points']) ?></span>
            <span class="d-block text-muted small" style="font-size: 0.725rem;">≈ <?= format_currency($user['balance_points'] / POINTS_PER_USD) ?></span>
        </div>
    </div>

    <!-- Navigation Menu (8 Clean Items + Log Out) -->
    <nav class="portal-nav">
        <!-- 1. Overview -->
        <a href="<?= BASE_URL ?>/member/dashboard.php" class="portal-nav-link <?= $activePage === 'dashboard.php' ? 'active' : '' ?>">
            <i class="bi bi-grid-fill"></i>
            <span>Overview</span>
        </a>

        <!-- 2. Surveys -->
        <a href="<?= BASE_URL ?>/member/surveys.php" class="portal-nav-link <?= $activePage === 'surveys.php' || $activePage === 'take_survey.php' ? 'active' : '' ?>">
            <i class="bi bi-clipboard-data-fill"></i>
            <span class="flex-grow-1">Surveys</span>
            <?php if ($activeSurveysCount > 0): ?>
                <span class="badge bg-primary rounded-pill small"><?= $activeSurveysCount ?></span>
            <?php endif; ?>
        </a>

        <!-- 3. My Rewards -->
        <a href="<?= BASE_URL ?>/member/rewards.php" class="portal-nav-link <?= $activePage === 'rewards.php' || $activePage === 'redeem_reward.php' ? 'active' : '' ?>">
            <i class="bi bi-gift-fill"></i>
            <span>My Rewards</span>
        </a>

        <!-- 4. Wallet & Cashouts -->
        <a href="<?= BASE_URL ?>/member/wallet.php" class="portal-nav-link <?= $activePage === 'wallet.php' ? 'active' : '' ?>">
            <i class="bi bi-wallet2"></i>
            <span>Wallet & Cashouts</span>
        </a>

        <!-- 5. My Profile -->
        <a href="<?= BASE_URL ?>/member/profile.php" class="portal-nav-link <?= $activePage === 'profile.php' ? 'active' : '' ?>">
            <i class="bi bi-person-fill"></i>
            <span>My Profile</span>
        </a>

        <!-- 6. Account Settings -->
        <a href="<?= BASE_URL ?>/member/settings.php" class="portal-nav-link <?= $activePage === 'settings.php' || $activePage === 'change-password.php' || $activePage === 'change_password.php' ? 'active' : '' ?>">
            <i class="bi bi-gear-fill"></i>
            <span>Account Settings</span>
        </a>

        <!-- 7. Notifications -->
        <a href="<?= BASE_URL ?>/member/notifications.php" class="portal-nav-link <?= $activePage === 'notifications.php' ? 'active' : '' ?>">
            <i class="bi bi-bell-fill"></i>
            <span class="flex-grow-1">Notifications</span>
            <?php if ($sidebarUnreadCount > 0): ?>
                <span class="badge bg-danger rounded-pill small"><?= $sidebarUnreadCount ?></span>
            <?php endif; ?>
        </a>

        <!-- 8. Help & Support -->
        <a href="<?= BASE_URL ?>/member/support.php" class="portal-nav-link <?= $activePage === 'support.php' ? 'active' : '' ?>">
            <i class="bi bi-question-circle-fill"></i>
            <span>Help & Support</span>
        </a>

        <!-- 9. Log Out -->
        <button type="button" class="portal-nav-link logout-link text-start w-100 bg-transparent border-0 mt-auto text-danger" data-bs-toggle="modal" data-bs-target="#logoutConfirmModal">
            <i class="bi bi-box-arrow-right text-danger"></i>
            <span>Log Out</span>
        </button>
    </nav>
</aside>

<!-- Mobile Offcanvas Sidebar -->
<div class="offcanvas offcanvas-start rounded-end-4" tabindex="-1" id="memberSidebarOffcanvas" aria-labelledby="memberSidebarOffcanvasLabel">
    <div class="offcanvas-header border-bottom">
        <div class="d-flex align-items-center">
            <img src="<?= BASE_URL ?>/assets/images/veritolytics-logo.png" alt="Veritolytics" height="32" class="brand-logo-img">
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body d-flex flex-column p-3">
        <div class="sidebar-user-card p-3 text-center mb-3">
            <?= user_avatar_html($user, 48, 'mx-auto mb-2') ?>
            <h6 class="fw-bold mb-0 text-dark"><?= e($user['first_name'] . ' ' . $user['last_name']) ?></h6>
            <div class="header-wallet-badge mt-2">
                <i class="bi bi-coin"></i>
                <span><?= format_points($user['balance_points']) ?></span>
            </div>
        </div>

        <nav class="portal-nav">
            <a href="<?= BASE_URL ?>/member/dashboard.php" class="portal-nav-link <?= $activePage === 'dashboard.php' ? 'active' : '' ?>">
                <i class="bi bi-grid-fill"></i>
                <span>Overview</span>
            </a>
            <a href="<?= BASE_URL ?>/member/surveys.php" class="portal-nav-link <?= $activePage === 'surveys.php' || $activePage === 'take_survey.php' ? 'active' : '' ?>">
                <i class="bi bi-clipboard-data-fill"></i>
                <span class="flex-grow-1">Surveys</span>
                <?php if ($activeSurveysCount > 0): ?>
                    <span class="badge bg-primary rounded-pill small"><?= $activeSurveysCount ?></span>
                <?php endif; ?>
            </a>
            <a href="<?= BASE_URL ?>/member/rewards.php" class="portal-nav-link <?= $activePage === 'rewards.php' || $activePage === 'redeem_reward.php' ? 'active' : '' ?>">
                <i class="bi bi-gift-fill"></i>
                <span>My Rewards</span>
            </a>
            <a href="<?= BASE_URL ?>/member/wallet.php" class="portal-nav-link <?= $activePage === 'wallet.php' ? 'active' : '' ?>">
                <i class="bi bi-wallet2"></i>
                <span>Wallet & Cashouts</span>
            </a>
            <a href="<?= BASE_URL ?>/member/profile.php" class="portal-nav-link <?= $activePage === 'profile.php' ? 'active' : '' ?>">
                <i class="bi bi-person-fill"></i>
                <span>My Profile</span>
            </a>
            <a href="<?= BASE_URL ?>/member/settings.php" class="portal-nav-link <?= $activePage === 'settings.php' || $activePage === 'change-password.php' || $activePage === 'change_password.php' ? 'active' : '' ?>">
                <i class="bi bi-gear-fill"></i>
                <span>Account Settings</span>
            </a>
            <a href="<?= BASE_URL ?>/member/notifications.php" class="portal-nav-link <?= $activePage === 'notifications.php' ? 'active' : '' ?>">
                <i class="bi bi-bell-fill"></i>
                <span class="flex-grow-1">Notifications</span>
                <?php if ($sidebarUnreadCount > 0): ?>
                    <span class="badge bg-danger rounded-pill small"><?= $sidebarUnreadCount ?></span>
                <?php endif; ?>
            </a>
            <a href="<?= BASE_URL ?>/member/support.php" class="portal-nav-link <?= $activePage === 'support.php' ? 'active' : '' ?>">
                <i class="bi bi-question-circle-fill"></i>
                <span>Help & Support</span>
            </a>
            <button type="button" class="portal-nav-link logout-link text-start w-100 bg-transparent border-0 mt-auto text-danger" data-bs-toggle="modal" data-bs-target="#logoutConfirmModal">
                <i class="bi bi-box-arrow-right"></i>
                <span>Log Out</span>
            </button>
        </nav>
    </div>
</div>
