<?php
/**
 * Veritolytics - Support Ticket Processor
 * Pure Core PHP 8.2+ Architecture
 */

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/auth_check.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/');
}

$user = current_user();
$userId = $user ? (int)$user['id'] : null;

// Determine safe redirection destination
$returnUrl = !empty($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : ($userId ? '/member/support.php' : '/');

if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
    flash('danger', 'Security verification failed. Please try submitting again.');
    redirect($returnUrl);
}

$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$subject = trim($_POST['subject'] ?? '');
$message = trim($_POST['message'] ?? '');
$priority = in_array($_POST['priority'] ?? '', ['normal', 'high', 'urgent']) ? $_POST['priority'] : 'normal';

// Auto-fill from authenticated user profile if fields were left blank
if ($user) {
    if (empty($name)) {
        $name = trim($user['first_name'] . ' ' . $user['last_name']);
    }
    if (empty($email)) {
        $email = trim($user['email']);
    }
}

if (empty($name) || empty($email) || empty($subject) || empty($message)) {
    flash('danger', 'All ticket fields (full name, email, subject, and message) are required.');
    redirect($returnUrl);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    flash('danger', 'Please provide a valid email address.');
    redirect($returnUrl);
}

$ticketNumber = 'TCK-' . date('Y') . '-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 6));

try {
    $db = get_db();
    $stmt = $db->prepare("
        INSERT INTO support_tickets (ticket_number, user_id, name, email, subject, message, status, priority)
        VALUES (:ticket_number, :user_id, :name, :email, :subject, :message, 'open', :priority)
    ");
    $stmt->execute([
        ':ticket_number' => $ticketNumber,
        ':user_id' => $userId,
        ':name' => $name,
        ':email' => $email,
        ':subject' => $subject,
        ':message' => $message,
        ':priority' => $priority,
    ]);

    if ($userId) {
        add_notification(
            $userId,
            'Support Ticket Submitted',
            "Your ticket #{$ticketNumber} has been received. Our panel operations team will review it shortly.",
            'info',
            '/member/support.php'
        );
    }

    flash('success', "Your support ticket #{$ticketNumber} has been received. Our research team will respond shortly.");
} catch (Throwable $e) {
    error_log("Veritolytics Support Ticket Submission Error: " . $e->getMessage());
    flash('danger', 'An error occurred while creating your ticket. Please try again later.');
}

redirect($returnUrl);
