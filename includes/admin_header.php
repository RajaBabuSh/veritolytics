<?php
/**
 * Veritolytics - Admin Portal Header
 * Pure Core PHP 8.2+ Architecture
 */

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/auth_check.php';

$admin = require_admin();
$db = get_db();

// Fetch pending counts
$pendingRedeemCount = (int)$db->query("SELECT COUNT(*) FROM redemptions WHERE status = 'pending'")->fetchColumn();
$openTicketsCount = (int)$db->query("SELECT COUNT(*) FROM support_tickets WHERE status = 'open'")->fetchColumn();

$initials = strtoupper(substr($admin['first_name'], 0, 1) . substr($admin['last_name'], 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle ?? 'Admin Portal') ?> | <?= APP_NAME ?></title>
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
<body class="bg-light">

<header class="navbar navbar-expand bg-white border-bottom border-light-subtle sticky-top px-3 py-2 shadow-sm">
    <div class="container-fluid px-2">
        <div class="d-flex align-items-center gap-3">
            <button class="btn btn-sm btn-light d-lg-none border" type="button" data-bs-toggle="offcanvas" data-bs-target="#adminSidebarOffcanvas" aria-controls="adminSidebarOffcanvas">
                <i class="bi bi-list fs-5"></i>
            </button>
            <a class="navbar-brand d-flex align-items-center gap-2 m-0" href="<?= BASE_URL ?>/admin/dashboard.php">
                <img src="<?= BASE_URL ?>/assets/images/veritolytics-logo.png" alt="Veritolytics" height="30" class="rounded">
                <span class="fw-bold tracking-tight text-dark fs-5">VERITOLYTICS</span>
            </a>
            <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill px-2.5 py-1 fw-bold small">
                Admin Console
            </span>
        </div>

        <div class="d-flex align-items-center gap-3 ms-auto">
            <!-- Switch to Member Portal Link -->
            <a href="<?= BASE_URL ?>/member/dashboard.php" class="btn btn-sm btn-outline-secondary rounded-pill px-3 py-1.5 small fw-semibold d-none d-md-inline-flex align-items-center gap-1">
                <i class="bi bi-person me-1"></i> Member View
            </a>

            <!-- Admin Profile Menu -->
            <div class="dropdown">
                <button class="btn btn-light rounded-pill p-1 pe-2.5 d-flex align-items-center gap-2 border" type="button" id="adminMenuBtn" data-bs-toggle="dropdown" aria-expanded="false">
                    <div class="rounded-circle bg-danger text-white fw-bold d-flex align-items-center justify-content-center shadow-sm" style="width: 32px; height: 32px; font-size: 0.8rem;">
                        <?= $initials ?>
                    </div>
                    <span class="fw-semibold small d-none d-sm-inline"><?= e($admin['first_name']) ?> (Admin)</span>
                    <i class="bi bi-chevron-down small text-muted"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0 rounded-4 p-2 mt-2" style="min-width: 200px;" aria-labelledby="adminMenuBtn">
                    <li class="px-3 py-2 border-bottom mb-1">
                        <div class="fw-bold text-dark small"><?= e($admin['first_name'] . ' ' . $admin['last_name']) ?></div>
                        <div class="text-muted small text-truncate" style="font-size: 0.75rem;"><?= e($admin['email']) ?></div>
                    </li>
                    <li><a class="dropdown-item rounded-3 py-2 text-danger fw-semibold" href="<?= BASE_URL ?>/admin/logout.php"><i class="bi bi-box-arrow-right me-2"></i> Log Out</a></li>
                </ul>
            </div>
        </div>
    </div>
</header>
