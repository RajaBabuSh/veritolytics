<?php
/**
 * Veritolytics - Application Configuration Example
 * Pure Core PHP 8.2+ Architecture
 */

// Prevent multiple inclusions
if (!defined('VERITOLYTICS_INIT')) {
    define('VERITOLYTICS_INIT', true);

    // Application Metadata
    define('APP_NAME', 'VERITOPINION');
    define('APP_TAGLINE', 'Your Voice. Your Opinions. Your Impact.');
    define('APP_VERSION', '1.0.0');

    // Economy & Points Rules (100 pts = $1.00 USD)
    define('POINTS_PER_USD', 100);
    define('MIN_REDEMPTION_POINTS', 1000); // $10.00 Minimum Payout
    define('WELCOME_BONUS_POINTS', 500);  // $5.00 Welcome Bonus

    // Error Reporting
    ini_set('display_errors', '0');
    error_reporting(E_ALL);

    // Ensure server superglobal defaults for CLI / automated testing
    if (!isset($_SERVER['REQUEST_METHOD'])) {
        $_SERVER['REQUEST_METHOD'] = 'GET';
    }

    // Secure Session Settings
    if (session_status() === PHP_SESSION_NONE) {
        $cookieParams = [
            'lifetime' => 86400 * 7,
            'path' => '/',
            'domain' => '',
            'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
            'httponly' => true,
            'samesite' => 'Lax'
        ];
        session_set_cookie_params($cookieParams);
        session_start();
    }

    // Auto-detect Base URL for dynamic link generation
    if (!defined('BASE_URL')) {
        $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https://' : 'http://';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        
        $scriptPath = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
        $parts = explode('/', trim($scriptPath, '/'));
        $subfolder = '';
        if (!empty($parts[0]) && in_array(strtolower($parts[0]), ['veritolytics-php', 'veritolytics'])) {
            $subfolder = '/' . $parts[0];
        }
        define('BASE_URL', rtrim($protocol . $host . $subfolder, '/'));
    }
}
