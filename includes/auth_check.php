<?php
/**
 * Veritolytics - Session Authentication & RBAC Engine
 * Pure Core PHP 8.2+ Architecture
 */

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';

function is_logged_in(): bool {
    return !empty($_SESSION['user_id']);
}

function current_user(): ?array {
    static $cachedUser = null;
    if ($cachedUser !== null) {
        return $cachedUser;
    }

    if (!is_logged_in()) {
        return null;
    }

    $db = get_db();
    $stmt = $db->prepare("
        SELECT id, first_name, last_name, email, pending_email, email_verification_token,
               country, state, city, phone, date_of_birth, gender, avatar,
               employment_status, annual_income, education_level, role, status,
               balance_points, lifetime_earnings_usd, completed_surveys_count,
               profile_completion_pct, notification_preferences, created_at, updated_at
        FROM users 
        WHERE id = :id
    ");
    $stmt->execute([':id' => $_SESSION['user_id']]);
    $user = $stmt->fetch();

    if (!$user || $user['status'] === 'suspended') {
        // Invalidate session if user was deleted or suspended
        unset($_SESSION['user_id'], $_SESSION['user_role']);
        session_destroy();
        return null;
    }

    $cachedUser = $user;
    return $cachedUser;
}

function is_admin(): bool {
    $user = current_user();
    return $user !== null && $user['role'] === 'admin';
}

function require_login(): array {
    if (!is_logged_in() || current_user() === null) {
        $_SESSION['return_to'] = $_SERVER['REQUEST_URI'];
        flash('warning', 'Please sign in to access your Veritolytics member portal.');
        redirect('/auth/login.php');
    }
    return current_user();
}

function require_admin(): array {
    if (!is_logged_in()) {
        $_SESSION['return_to'] = $_SERVER['REQUEST_URI'];
        flash('warning', 'Administrator authentication required.');
        redirect('/admin/login.php');
    }

    $user = current_user();
    if ($user['role'] !== 'admin') {
        flash('danger', 'Access denied. You do not have administrator permissions.');
        redirect('/member/dashboard.php');
    }

    return $user;
}

function require_guest(): void {
    if (is_logged_in()) {
        $user = current_user();
        if ($user && $user['role'] === 'admin') {
            redirect('/admin/dashboard.php');
        } else {
            redirect('/member/dashboard.php');
        }
    }
}
