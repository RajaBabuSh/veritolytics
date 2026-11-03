<?php
/**
 * Veritolytics - Mark Notifications as Read
 * Pure Core PHP 8.2+ Architecture
 */

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_check.php';

$user = require_login();

try {
    $db = get_db();
    if (!empty($_GET['id'])) {
        $stmt = $db->prepare("UPDATE notifications SET is_read = 1 WHERE id = :id AND user_id = :uid");
        $stmt->execute([
            ':id'  => (int)$_GET['id'],
            ':uid' => $user['id']
        ]);
    } else {
        $stmt = $db->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = :uid");
        $stmt->execute([':uid' => $user['id']]);
    }
} catch (Exception $e) {
    // Silent catch
}

if (!empty($_GET['redirect'])) {
    redirect($_GET['redirect']);
}

redirect($_SERVER['HTTP_REFERER'] ?? '/member/dashboard.php');
