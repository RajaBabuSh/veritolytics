<?php
/**
 * Veritolytics - Session Authentication & RBAC Engine
 * Pure Core PHP 8.2+ Architecture
 */

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/csrf.php';

/**
 * Persistently establish an authenticated user session across requests and containers
 */
function set_authenticated_session(int $userId, string $role = 'member', ?string $passwordHash = null): void {
    if (session_status() === PHP_SESSION_ACTIVE) {
        @session_regenerate_id(true);
    }
    $_SESSION['user_id'] = $userId;
    $_SESSION['user_role'] = $role;

    if (!headers_sent()) {
        try {
            if (!$passwordHash) {
                $db = get_db();
                $stmt = $db->prepare("SELECT password_hash FROM users WHERE id = :id");
                $stmt->execute([':id' => $userId]);
                $passwordHash = (string)$stmt->fetchColumn();
            }
            $hashSnippet = substr((string)$passwordHash, 0, 16);
            $expiry = time() + (86400 * 30); // 30-day token
            $signature = hash_hmac('sha256', "{$userId}:{$expiry}:{$hashSnippet}", get_app_secret());
            $tokenPayload = base64_encode("{$userId}:{$expiry}:{$signature}");

            $isHttps = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ||
                       (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https');

            setcookie('vy_auth_token', $tokenPayload, [
                'expires' => $expiry,
                'path' => '/',
                'domain' => '',
                'secure' => $isHttps,
                'httponly' => true,
                'samesite' => 'Lax'
            ]);
        } catch (Throwable $e) {
            // Silently allow session-only login if cookie fails
        }
    }
}

/**
 * Safely tear down user session and remove persistent auth cookies
 */
function clear_authenticated_session(): void {
    $_SESSION = [];
    if (ini_get("session.use_cookies") && !headers_sent()) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }
    if (!headers_sent()) {
        setcookie('vy_auth_token', '', [
            'expires' => time() - 42000,
            'path' => '/',
            'domain' => '',
            'secure' => false,
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
    }
    unset($_COOKIE['vy_auth_token']);
    if (session_status() === PHP_SESSION_ACTIVE) {
        @session_destroy();
    }
}

function is_logged_in(): bool {
    if (!empty($_SESSION['user_id'])) {
        return true;
    }

    // Serverless container cross-instance recovery via signed auth cookie
    if (!empty($_COOKIE['vy_auth_token'])) {
        $raw = base64_decode($_COOKIE['vy_auth_token'], true);
        if ($raw && str_contains($raw, ':')) {
            $parts = explode(':', $raw);
            if (count($parts) === 3) {
                [$uId, $expiry, $sig] = $parts;
                $uId = (int)$uId;
                $exp = (int)$expiry;
                if ($uId > 0 && $exp > time()) {
                    try {
                        $db = get_db();
                        $stmt = $db->prepare("SELECT id, role, status, password_hash FROM users WHERE id = :id");
                        $stmt->execute([':id' => $uId]);
                        $u = $stmt->fetch();
                        if ($u && $u['status'] !== 'suspended') {
                            $hashSnippet = substr((string)$u['password_hash'], 0, 16);
                            $expectedSig = hash_hmac('sha256', "{$uId}:{$exp}:{$hashSnippet}", get_app_secret());
                            if (hash_equals($expectedSig, $sig)) {
                                $_SESSION['user_id'] = (int)$u['id'];
                                $_SESSION['user_role'] = $u['role'];
                                return true;
                            }
                        }
                    } catch (Throwable $e) {
                        // DB error or table not ready
                    }
                }
            }
        }
    }

    return false;
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
    try {
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
    } catch (Throwable $e) {
        // Fallback for minimal user schema or during migrations
        try {
            $stmt = $db->prepare("
                SELECT id, first_name, last_name, email,
                       country, phone, date_of_birth, gender,
                       employment_status, annual_income, education_level, role, status,
                       balance_points, lifetime_earnings_usd, completed_surveys_count,
                       profile_completion_pct, created_at, updated_at
                FROM users 
                WHERE id = :id
            ");
            $stmt->execute([':id' => $_SESSION['user_id']]);
            $user = $stmt->fetch();
            if ($user) {
                $user['pending_email'] = null;
                $user['email_verification_token'] = null;
                $user['email_verification_expires_at'] = null;
                $user['state'] = null;
                $user['city'] = null;
                $user['avatar'] = null;
                $user['notification_preferences'] = null;
            }
        } catch (Throwable $e2) {
            $user = null;
        }
    }

    if (!$user || $user['status'] === 'suspended' || !empty($user['deleted_at'])) {
        // Invalidate session if user was soft-deleted or suspended
        clear_authenticated_session();
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
