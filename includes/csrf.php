<?php
/**
 * Veritolytics - Resilient Multi-Layer CSRF Protection System
 * Pure Core PHP 8.2+ Architecture
 * 
 * Supports session-based validation, stateless signed HMAC validation for serverless
 * container deployments (Vercel/FrankenPHP), double-submit cookie validation,
 * and AJAX header tokens (X-CSRF-TOKEN / X-XSRF-TOKEN).
 */

require_once __DIR__ . '/../config/app.php';

/**
 * Retrieve application-wide cryptographic secret key
 */
function get_app_secret(): string {
    static $secret = null;
    if ($secret !== null) {
        return $secret;
    }

    $key = getenv('APP_KEY') ?: ($_ENV['APP_KEY'] ?? '');
    if (!empty($key)) {
        $secret = (string)$key;
        return $secret;
    }

    $dbPass = getenv('DB_PASS') ?: ($_ENV['DB_PASS'] ?? '');
    $dbHost = getenv('DB_HOST') ?: ($_ENV['DB_HOST'] ?? '');
    $dbName = getenv('DB_NAME') ?: ($_ENV['DB_NAME'] ?? '');
    if (!empty($dbPass) || !empty($dbHost)) {
        $secret = hash('sha256', "vy_{$dbPass}_{$dbHost}_{$dbName}_veritolytics_secret_2026");
        return $secret;
    }

    // Fallback deterministic secret constant guaranteed identical across all container replicas
    $secret = hash('sha256', 'VERITOLYTICS_PLATFORM_CORE_SECRET_SALT_2026_EST2017');
    return $secret;
}

/**
 * Verify cryptographic HMAC signature and lifetime of a signed token
 */
function verify_csrf_token_signature(string $token): bool {
    $parts = explode('.', $token);
    if (count($parts) !== 3) {
        return false;
    }
    [$timestamp, $nonce, $sig] = $parts;
    $time = (int)$timestamp;
    // Valid for 24 hours, with 5 minutes allowable future clock skew
    if ($time < (time() - 86400) || $time > (time() + 300)) {
        return false;
    }
    $expected = hash_hmac('sha256', "{$timestamp}.{$nonce}", get_app_secret());
    return hash_equals($expected, $sig);
}

/**
 * Generate cryptographically signed CSRF token
 */
function generate_csrf_token(): string {
    $existing = $_SESSION['csrf_token'] ?? null;
    if (!empty($existing) && is_string($existing) && verify_csrf_token_signature($existing)) {
        return $existing;
    }

    $timestamp = time();
    $nonce = bin2hex(random_bytes(16));
    $sig = hash_hmac('sha256', "{$timestamp}.{$nonce}", get_app_secret());
    $token = "{$timestamp}.{$nonce}.{$sig}";

    $_SESSION['csrf_token'] = $token;

    // Double-submit cookie for resilience across ephemeral serverless container instances
    if (!headers_sent()) {
        $isHttps = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ||
                   (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https');
        setcookie('vy_csrf', $token, [
            'expires' => $timestamp + 86400,
            'path' => '/',
            'domain' => '',
            'secure' => $isHttps,
            'httponly' => false, // accessible to client JS/fetch if needed
            'samesite' => 'Lax'
        ]);
    }

    return $token;
}

function csrf_token(): string {
    return generate_csrf_token();
}

/**
 * Validate incoming CSRF token against session, cryptographic HMAC signature, and cookies
 */
function verify_csrf_token(?string $token): bool {
    // If not in POST, check headers (AJAX/Fetch support)
    if (empty($token)) {
        $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_SERVER['HTTP_X_XSRF_TOKEN'] ?? null;
    }

    if (empty($token) || !is_string($token)) {
        return false;
    }

    // Layer 1: Check active session token
    if (!empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token)) {
        return true;
    }

    // Layer 2: Stateless HMAC signature check (survives container switches/cold starts)
    if (verify_csrf_token_signature($token)) {
        $_SESSION['csrf_token'] = $token;
        return true;
    }

    // Layer 3: Double-submit cookie check
    if (!empty($_COOKIE['vy_csrf']) && hash_equals($_COOKIE['vy_csrf'], $token)) {
        $_SESSION['csrf_token'] = $token;
        return true;
    }

    return false;
}

function csrf_field(): string {
    $token = generate_csrf_token();
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
}
