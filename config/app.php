<?php
/**
 * Veritolytics - Application Configuration & Session Bootstrap
 * Pure Core PHP 8.2+ Architecture
 */

// Prevent multiple inclusions
if (!defined('VERITOLYTICS_INIT')) {
    define('VERITOLYTICS_INIT', true);

    // Application Metadata
    define('APP_NAME', 'VERITOLYTICS');
    define('APP_TAGLINE', 'Your Voice. Your Insights. Your Impact.');
    define('APP_VERSION', '1.0.0');

    // Economy & Points Rules (100 pts = $1.00 USD)
    define('POINTS_PER_USD', 100);
    define('MIN_REDEMPTION_POINTS', 1000); // $10.00 Minimum Payout
    define('WELCOME_BONUS_POINTS', 500);  // $5.00 Welcome Bonus

    // Error Reporting in Development (can be turned off in production)
    ini_set('display_errors', '0');
    error_reporting(E_ALL);

    // Ensure server superglobal defaults for CLI / automated testing
    if (!isset($_SERVER['REQUEST_METHOD'])) {
        $_SERVER['REQUEST_METHOD'] = 'GET';
    }

    // Dedicated session storage to ensure seamless operation across all environments
    $projectSessionDir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'sessions';
    if (!is_dir($projectSessionDir)) {
        @mkdir($projectSessionDir, 0777, true);
    }
    if (is_dir($projectSessionDir)) {
        ini_set('session.save_path', $projectSessionDir);
    } else {
        ini_set('session.save_path', sys_get_temp_dir());
    }

    // Check for HTTPS directly or via reverse proxy headers (Vercel / Cloudflare / Load Balancers)
    $isHttps = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ||
               (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https') ||
               (isset($_SERVER['HTTP_CF_VISITOR']) && strpos($_SERVER['HTTP_CF_VISITOR'], 'https') !== false);

    // Secure Session Settings
    if (session_status() === PHP_SESSION_NONE) {
        $cookieParams = [
            'lifetime' => 86400 * 7, // 7 days
            'path' => '/',
            'domain' => '',
            'secure' => $isHttps,
            'httponly' => true,
            'samesite' => 'Lax'
        ];
        session_set_cookie_params($cookieParams);
        session_start();
    }

    // Auto-detect Base URL for dynamic link generation
    if (!defined('BASE_URL')) {
        $protocol = $isHttps ? 'https://' : 'http://';
        $host = $_SERVER['HTTP_X_FORWARDED_HOST'] ?? $_SERVER['HTTP_HOST'] ?? 'localhost:8000';
        
        // Check if running inside subfolder (e.g. /Veritolytics-PHP)
        $scriptPath = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
        // Find project root if in subfolder
        $parts = explode('/', trim($scriptPath, '/'));
        $subfolder = '';
        if (!empty($parts[0]) && in_array(strtolower($parts[0]), ['veritolytics-php', 'veritolytics'])) {
            $subfolder = '/' . $parts[0];
        }
        define('BASE_URL', rtrim($protocol . $host . $subfolder, '/'));
    }
}
