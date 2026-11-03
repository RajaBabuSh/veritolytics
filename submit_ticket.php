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

if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
    flash('danger', 'Security verification failed. Please try submitting again.');
    redirect('/');
}

$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$subject = trim($_POST['subject'] ?? '');
$message = trim($_POST['message'] ?? '');

if (empty($name) || empty($email) || empty($subject) || empty($message)) {
    flash('danger', 'All ticket fields are required.');
    redirect($_SERVER['HTTP_REFERER'] ?? '/');
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    flash('danger', 'Please provide a valid email address.');
    redirect($_SERVER['HTTP_REFERER'] ?? '/');
}

$ticketNumber = 'TCK-' . date('Y') . '-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 6));
$user = current_user();
$userId = $user ? $user['id'] : null;

try {
    $db = get_db();
    $stmt = $db->prepare("
        INSERT INTO support_tickets (ticket_number, user_id, name, email, subject, message, status, priority)
        VALUES (:ticket_number, :user_id, :name, :email, :subject, :message, 'open', 'normal')
    ");
    $stmt->execute([
        ':ticket_number' => $ticketNumber,
        ':user_id' => $userId,
        ':name' => $name,
        ':email' => $email,
        ':subject' => $subject,
        ':message' => $message,
    ]);

    flash('success', "Your support ticket #{$ticketNumber} has been received. Our research team will respond shortly.");
} catch (Exception $e) {
    flash('danger', 'An error occurred while creating your ticket. Please try again later.');
}

redirect($_SERVER['HTTP_REFERER'] ?? '/');
