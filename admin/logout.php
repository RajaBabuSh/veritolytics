<?php
/**
 * Veritolytics - Admin Sign Out Handler
 * Pure Core PHP 8.2+ Architecture
 */

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_check.php';

// Safely clear session and remove persistent auth cookies
clear_authenticated_session();

// Start fresh session solely to carry logout flash message
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
flash('info', 'Administrator signed out safely.');

redirect('/admin/login.php');
