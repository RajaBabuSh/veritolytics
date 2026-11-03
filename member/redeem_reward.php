<?php
/**
 * Veritolytics - Reward Redemption Handler
 * Pure Core PHP 8.2+ Architecture
 */

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/auth_check.php';

$user = require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/member/rewards.php');
}

if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
    flash('danger', 'Security session expired. Please try submitting again.');
    redirect('/member/rewards.php');
}

$rewardId = (int)($_POST['reward_id'] ?? 0);
$payoutDestination = trim($_POST['payout_destination'] ?? '');

if ($rewardId <= 0 || empty($payoutDestination)) {
    flash('danger', 'Please provide a valid payout destination (e.g., your PayPal email or delivery email).');
    redirect('/member/rewards.php');
}

$db = get_db();
$stmt = $db->prepare("SELECT * FROM rewards WHERE id = :id AND is_active = 1");
$stmt->execute([':id' => $rewardId]);
$reward = $stmt->fetch();

if (!$reward) {
    flash('danger', 'Selected reward is currently unavailable.');
    redirect('/member/rewards.php');
}

$ptsRequired = (int)$reward['points_required'];
$amountUsd = (float)$reward['cash_equivalent_usd'];

// Check user points balance
if ($user['balance_points'] < $ptsRequired) {
    $shortage = $ptsRequired - $user['balance_points'];
    flash('warning', "Insufficient points balance. You need {$shortage} more points to redeem {$reward['title']}.");
    redirect('/member/rewards.php');
}

try {
    // 1. Deduct points from user balance
    $deductStmt = $db->prepare("
        UPDATE users 
        SET balance_points = balance_points - :pts 
        WHERE id = :id AND balance_points >= :pts
    ");
    $deducted = $deductStmt->execute([
        ':pts' => $ptsRequired,
        ':id' => $user['id']
    ]);

    if (!$deducted || $deductStmt->rowCount() === 0) {
        flash('danger', 'Failed to process points deduction. Please verify your balance.');
        redirect('/member/rewards.php');
    }

    // 2. Insert into redemptions table
    $insRedeem = $db->prepare("
        INSERT INTO redemptions (user_id, reward_id, reward_title, points_spent, amount_usd, payout_destination, status)
        VALUES (:uid, :rid, :title, :pts, :usd, :dest, 'pending')
    ");
    $insRedeem->execute([
        ':uid' => $user['id'],
        ':rid' => $reward['id'],
        ':title' => $reward['title'],
        ':pts' => $ptsRequired,
        ':usd' => $amountUsd,
        ':dest' => $payoutDestination
    ]);

    // 3. Insert transaction ledger entry
    add_transaction(
        (int)$user['id'],
        'redemption',
        "Redeemed: {$reward['title']} (Pending Review)",
        -$ptsRequired,
        -$amountUsd,
        'pending'
    );

    // 4. Send notification
    add_notification(
        (int)$user['id'],
        "Reward Request Submitted: {$reward['title']}",
        "Your redemption request for {$reward['title']} has been submitted to admin queue for processing. Payout sent to {$payoutDestination}.",
        'reward',
        '/member/rewards.php'
    );

    flash('success', "Your redemption request for {$reward['title']} was submitted successfully! It is currently Pending Review and will be processed within 1-2 business days.");
} catch (Exception $e) {
    flash('danger', 'An unexpected error occurred while submitting your reward request.');
}

redirect('/member/rewards.php');
