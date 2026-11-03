<?php
/**
 * Veritolytics - Admin Sidebar Navigation
 * Pure Core PHP 8.2+ Architecture
 */

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/functions.php';

$currentUri = $_SERVER['REQUEST_URI'] ?? '';
$activePage = basename(parse_url($currentUri, PHP_URL_PATH));

$db = get_db();
$pendingRedeems = (int)$db->query("SELECT COUNT(*) FROM redemptions WHERE status = 'pending'")->fetchColumn();
$openTickets = (int)$db->query("SELECT COUNT(*) FROM support_tickets WHERE status = 'open'")->fetchColumn();
?>

<!-- Desktop Admin Sidebar -->
<aside class="portal-sidebar d-none d-lg-flex">
    <div class="px-2 mb-3">
        <span class="text-uppercase text-muted fw-bold small" style="font-size: 0.7rem; letter-spacing: 0.05em;">Management Hub</span>
    </div>

    <nav class="portal-nav">
        <a href="<?= BASE_URL ?>/admin/dashboard.php" class="portal-nav-link <?= $activePage === 'dashboard.php' ? 'active' : '' ?>">
            <i class="bi bi-speedometer2"></i>
            <span>Overview KPIs</span>
        </a>

        <a href="<?= BASE_URL ?>/admin/users.php" class="portal-nav-link <?= $activePage === 'users.php' ? 'active' : '' ?>">
            <i class="bi bi-people-fill"></i>
            <span>Members & Users</span>
        </a>

        <a href="<?= BASE_URL ?>/admin/surveys.php" class="portal-nav-link <?= $activePage === 'surveys.php' ? 'active' : '' ?>">
            <i class="bi bi-clipboard2-data-fill"></i>
            <span>Surveys Manager</span>
        </a>

        <a href="<?= BASE_URL ?>/admin/rewards.php" class="portal-nav-link <?= $activePage === 'rewards.php' ? 'active' : '' ?>">
            <i class="bi bi-gift-fill"></i>
            <span class="flex-grow-1">Redemptions</span>
            <?php if ($pendingRedeems > 0): ?>
                <span class="badge bg-warning text-dark rounded-pill small"><?= $pendingRedeems ?></span>
            <?php endif; ?>
        </a>

        <a href="<?= BASE_URL ?>/admin/transactions.php" class="portal-nav-link <?= $activePage === 'transactions.php' ? 'active' : '' ?>">
            <i class="bi bi-receipt-cutoff"></i>
            <span>Ledger Audit</span>
        </a>

        <a href="<?= BASE_URL ?>/admin/support.php" class="portal-nav-link <?= $activePage === 'support.php' ? 'active' : '' ?>">
            <i class="bi bi-headset"></i>
            <span class="flex-grow-1">Support Tickets</span>
            <?php if ($openTickets > 0): ?>
                <span class="badge bg-danger rounded-pill small"><?= $openTickets ?></span>
            <?php endif; ?>
        </a>

        <a href="<?= BASE_URL ?>/admin/logout.php" class="portal-nav-link logout-link mt-auto">
            <i class="bi bi-box-arrow-right"></i>
            <span>Admin Sign Out</span>
        </a>
    </nav>
</aside>

<!-- Mobile Admin Sidebar Offcanvas -->
<div class="offcanvas offcanvas-start rounded-end-4" tabindex="-1" id="adminSidebarOffcanvas" aria-labelledby="adminSidebarOffcanvasLabel">
    <div class="offcanvas-header border-bottom">
        <h5 class="offcanvas-title fw-bold text-danger" id="adminSidebarOffcanvasLabel">Admin Portal</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body d-flex flex-column p-3">
        <nav class="portal-nav">
            <a href="<?= BASE_URL ?>/admin/dashboard.php" class="portal-nav-link <?= $activePage === 'dashboard.php' ? 'active' : '' ?>">
                <i class="bi bi-speedometer2"></i>
                <span>Overview KPIs</span>
            </a>
            <a href="<?= BASE_URL ?>/admin/users.php" class="portal-nav-link <?= $activePage === 'users.php' ? 'active' : '' ?>">
                <i class="bi bi-people-fill"></i>
                <span>Members & Users</span>
            </a>
            <a href="<?= BASE_URL ?>/admin/surveys.php" class="portal-nav-link <?= $activePage === 'surveys.php' ? 'active' : '' ?>">
                <i class="bi bi-clipboard2-data-fill"></i>
                <span>Surveys Manager</span>
            </a>
            <a href="<?= BASE_URL ?>/admin/rewards.php" class="portal-nav-link <?= $activePage === 'rewards.php' ? 'active' : '' ?>">
                <i class="bi bi-gift-fill"></i>
                <span>Redemptions</span>
            </a>
            <a href="<?= BASE_URL ?>/admin/transactions.php" class="portal-nav-link <?= $activePage === 'transactions.php' ? 'active' : '' ?>">
                <i class="bi bi-receipt-cutoff"></i>
                <span>Ledger Audit</span>
            </a>
            <a href="<?= BASE_URL ?>/admin/support.php" class="portal-nav-link <?= $activePage === 'support.php' ? 'active' : '' ?>">
                <i class="bi bi-headset"></i>
                <span>Support Tickets</span>
            </a>
            <a href="<?= BASE_URL ?>/admin/logout.php" class="portal-nav-link logout-link mt-auto">
                <i class="bi bi-box-arrow-right"></i>
                <span>Admin Sign Out</span>
            </a>
        </nav>
    </div>
</div>
