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

    // Prevent intermediate proxies and browsers from caching dynamic forms and CSRF tokens
    if (!headers_sent() && php_sapi_name() !== 'cli') {
        header('Cache-Control: no-cache, no-store, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        header('Expires: 0');
    }

    // Check for HTTPS directly or via reverse proxy headers (Vercel / Cloudflare / Load Balancers)
    $isHttps = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ||
               (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https') ||
               (isset($_SERVER['HTTP_X_FORWARDED_SSL']) && strtolower($_SERVER['HTTP_X_FORWARDED_SSL']) === 'on') ||
               (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443) ||
               (isset($_SERVER['HTTP_CF_VISITOR']) && strpos($_SERVER['HTTP_CF_VISITOR'], 'https') !== false) ||
               (isset($_SERVER['VERCEL']) || isset($_SERVER['HTTP_X_VERCEL_ID']));

    // Secure Session Settings & Centralized Database Session Storage
    if (session_status() === PHP_SESSION_NONE) {
        // Enforce secure cookie attributes
        $cookieParams = [
            'lifetime' => 86400 * 7, // 7 days
            'path' => '/',
            'domain' => '',
            'secure' => $isHttps,
            'httponly' => true,
            'samesite' => 'Lax'
        ];
        session_set_cookie_params($cookieParams);

        // Security ini flags
        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        ini_set('session.gc_maxlifetime', (string)(86400 * 7));

        // Connect Database-Backed Session Handler (MySQL / SQLite)
        try {
            require_once __DIR__ . '/database.php';
            require_once __DIR__ . '/../includes/DatabaseSessionHandler.php';
            $db = Database::getConnection();
            $sessionHandler = new DatabaseSessionHandler($db, 86400 * 7);
            session_set_save_handler($sessionHandler, true);
        } catch (Throwable $e) {
            error_log("Veritolytics Session Handler Notice: " . $e->getMessage());
            // Fallback to local session storage if database is temporarily unavailable
            $projectSessionDir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'sessions';
            if (!is_dir($projectSessionDir)) {
                @mkdir($projectSessionDir, 0777, true);
            }
            if (is_dir($projectSessionDir) && is_writable($projectSessionDir)) {
                ini_set('session.save_path', $projectSessionDir);
            } else {
                $tempDir = sys_get_temp_dir();
                if (is_dir($tempDir) && is_writable($tempDir)) {
                    ini_set('session.save_path', $tempDir);
                }
            }
        }

        @session_start();
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
