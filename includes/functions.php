<?php
/**
 * Veritolytics - Helper Functions & Business Logic Engine
 * Pure Core PHP 8.2+ Architecture
 */

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/csrf.php';

/**
 * Safe HTML Escaping shorthand
 */
function e(?string $string): string {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Safe HTTP Redirect with application base URL resolution
 */
function redirect(string $path): void {
    if (!str_starts_with($path, 'http://') && !str_starts_with($path, 'https://')) {
        $path = BASE_URL . '/' . ltrim($path, '/');
    }
    // Commit session to storage before redirecting to prevent race conditions
    if (session_status() === PHP_SESSION_ACTIVE) {
        @session_write_close();
    }
    header("Location: " . $path);
    exit();
}

/**
 * Set flash alert in session and synced cookie (for cross-instance Vercel resilience)
 */
function flash(string $type, string $message): void {
    if (!isset($_SESSION['flashes'])) {
        $_SESSION['flashes'] = [];
    }
    $_SESSION['flashes'][] = [
        'type' => $type, // 'success', 'danger', 'warning', 'info'
        'message' => $message
    ];

    if (!headers_sent()) {
        $isHttps = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ||
                   (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https');
        $payload = base64_encode((string)json_encode($_SESSION['flashes']));
        setcookie('vy_flash', $payload, [
            'expires' => time() + 300,
            'path' => '/',
            'domain' => '',
            'secure' => $isHttps,
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
    }
}

/**
 * Retrieve and clear flash alerts (checks session + cookie fallback)
 */
function get_flashes(): array {
    $flashes = $_SESSION['flashes'] ?? [];
    unset($_SESSION['flashes']);

    if (empty($flashes) && !empty($_COOKIE['vy_flash'])) {
        $raw = base64_decode($_COOKIE['vy_flash'], true);
        if ($raw) {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                $flashes = $decoded;
            }
        }
    }

    if (!empty($_COOKIE['vy_flash']) && !headers_sent()) {
        setcookie('vy_flash', '', [
            'expires' => time() - 3600,
            'path' => '/',
            'domain' => '',
            'secure' => false,
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
        unset($_COOKIE['vy_flash']);
    }

    return $flashes;
}

/**
 * Render Bootstrap 5 Flash Alerts HTML
 */
function render_flash(): string {
    $flashes = get_flashes();
    if (empty($flashes)) {
        return '';
    }

    $html = '<div class="flash-messages-container mb-4">';
    foreach ($flashes as $flash) {
        $type = e($flash['type']);
        $msg = e($flash['message']);
        $icon = match($type) {
            'success' => 'bi-check-circle-fill',
            'danger' => 'bi-exclamation-triangle-fill',
            'warning' => 'bi-exclamation-circle-fill',
            default => 'bi-info-circle-fill',
        };
        $html .= "
        <div class=\"alert alert-{$type} alert-dismissible fade show d-flex align-items-center shadow-sm rounded-3\" role=\"alert\">
            <i class=\"bi {$icon} me-2 fs-5\"></i>
            <div class=\"flex-grow-1\">{$msg}</div>
            <button type=\"button\" class=\"btn-close\" data-bs-dismiss=\"alert\" aria-label=\"Close\"></button>
        </div>";
    }
    $html .= '</div>';
    return $html;
}

/**
 * Format Points with comma separators
 */
function format_points(int $points): string {
    return number_format($points) . ' pts';
}

/**
 * Format USD Currency
 */
function format_currency(float|int $amount): string {
    return '$' . number_format($amount, 2);
}

/**
 * Human-friendly time ago
 */
function time_ago(string $datetime): string {
    $timestamp = strtotime($datetime);
    if (!$timestamp) return 'recently';
    $difference = time() - $timestamp;

    if ($difference < 60) return 'Just now';
    if ($difference < 3600) return round($difference / 60) . ' mins ago';
    if ($difference < 86400) return round($difference / 3600) . ' hours ago';
    if ($difference < 2592000) return round($difference / 86400) . ' days ago';
    return date('M j, Y', $timestamp);
}

/**
 * Calculate user profile completion percentage
 */
function calculate_profile_completion(array $user): int {
    $score = 0;
    if (!empty($user['first_name'])) $score += 15;
    if (!empty($user['last_name'])) $score += 15;
    if (!empty($user['email'])) $score += 10;
    if (!empty($user['country'])) $score += 10;
    if (!empty($user['phone'])) $score += 10;
    if (!empty($user['date_of_birth'])) $score += 10;
    if (!empty($user['gender'])) $score += 10;
    if (!empty($user['employment_status'])) $score += 10;
    if (!empty($user['education_level'])) $score += 10;
    return min($score, 100);
}

/**
 * Add an entry to the financial/points ledger
 */
function add_transaction(int $userId, string $type, string $title, int $points, float $amountUsd, string $status = 'completed'): bool {
    try {
        $db = get_db();
        $stmt = $db->prepare("
            INSERT INTO transactions (user_id, type, title, points, amount_usd, status)
            VALUES (:user_id, :type, :title, :points, :amount_usd, :status)
        ");
        return $stmt->execute([
            ':user_id' => $userId,
            ':type' => $type,
            ':title' => $title,
            ':points' => $points,
            ':amount_usd' => $amountUsd,
            ':status' => $status
        ]);
    } catch (Throwable $e) {
        // Fallback for MySQL environments with strict enum differences (e.g. 'bonus' fallback)
        try {
            $fallbackType = ($type === 'welcome_bonus') ? 'bonus' : 'adjustment';
            $stmt = $db->prepare("
                INSERT INTO transactions (user_id, type, title, points, amount_usd, status)
                VALUES (:user_id, :type, :title, :points, :amount_usd, :status)
            ");
            return $stmt->execute([
                ':user_id' => $userId,
                ':type' => $fallbackType,
                ':title' => $title,
                ':points' => $points,
                ':amount_usd' => $amountUsd,
                ':status' => $status
            ]);
        } catch (Throwable $e2) {
            error_log("Veritolytics add_transaction Error: " . $e2->getMessage());
            return false;
        }
    }
}

/**
 * Add in-app notification
 */
function add_notification(int $userId, string $title, string $message, string $type = 'info', ?string $link = null): bool {
    try {
        $db = get_db();
        $stmt = $db->prepare("
            INSERT INTO notifications (user_id, title, message, type, link, is_read)
            VALUES (:user_id, :title, :message, :type, :link, 0)
        ");
        return $stmt->execute([
            ':user_id' => $userId,
            ':title' => $title,
            ':message' => $message,
            ':type' => $type,
            ':link' => $link
        ]);
    } catch (Throwable $e) {
        error_log("Veritolytics add_notification Error: " . $e->getMessage());
        return false;
    }
}

/**
 * Safely update user points balance and recalculate lifetime earnings
 */
function update_user_balance(int $userId, int $pointDelta): bool {
    $db = get_db();
    $stmt = $db->prepare("SELECT balance_points, lifetime_earnings_usd FROM users WHERE id = :id");
    $stmt->execute([':id' => $userId]);
    $user = $stmt->fetch();
    if (!$user) return false;

    $newBalance = max(0, (int)$user['balance_points'] + $pointDelta);
    $lifetime = (float)$user['lifetime_earnings_usd'];
    if ($pointDelta > 0) {
        $lifetime += ($pointDelta / POINTS_PER_USD);
    }

    $updateStmt = $db->prepare("
        UPDATE users 
        SET balance_points = :balance, lifetime_earnings_usd = :lifetime 
        WHERE id = :id
    ");
    return $updateStmt->execute([
        ':balance' => $newBalance,
        ':lifetime' => $lifetime,
        ':id' => $userId
    ]);
}

/**
 * Render user circular avatar HTML (custom uploaded avatar or stylized initials)
 */
function user_avatar_html(array $user, int $size = 40, string $extraClass = ''): string {
    $initials = strtoupper(substr($user['first_name'] ?? 'U', 0, 1) . substr($user['last_name'] ?? '', 0, 1));
    $avatar = $user['avatar'] ?? '';

    if (!empty($avatar)) {
        $avatarPath = __DIR__ . '/../' . ltrim($avatar, '/');
        if (file_exists($avatarPath)) {
            $src = BASE_URL . '/' . ltrim($avatar, '/');
            return '<img src="' . e($src) . '" alt="' . e($user['first_name']) . '" class="rounded-circle object-fit-cover shadow-xs ' . $extraClass . '" style="width: ' . $size . 'px; height: ' . $size . 'px;">';
        }
    }

    $fontSize = max(11, round($size * 0.38));
    return '<div class="rounded-circle bg-gradient-royal text-white fw-bold d-flex align-items-center justify-content-center shadow-xs ' . $extraClass . '" style="width: ' . $size . 'px; height: ' . $size . 'px; font-size: ' . $fontSize . 'px; flex-shrink: 0;">' . e($initials) . '</div>';
}
