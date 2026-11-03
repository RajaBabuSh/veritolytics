<?php
/**
 * Veritolytics - Member Logout Handler
 * Pure Core PHP 8.2+ Architecture
 */

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_check.php';

// Clear session and persistent authentication cookies
clear_authenticated_session();

// Start fresh session solely to carry logout flash message
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
flash('info', 'You have been safely logged out. See you next time!');

redirect('/auth/login.php');
