<?php
/**
 * Veritolytics - Survey Submission & Points Credit Engine
 * Pure Core PHP 8.2+ Architecture
 */

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/auth_check.php';

$user = require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/member/surveys.php');
}

if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
    flash('danger', 'Security session expired. Please try taking the survey again.');
    redirect('/member/surveys.php');
}

$surveyId = (int)($_POST['survey_id'] ?? 0);
if ($surveyId <= 0) {
    flash('danger', 'Invalid survey identifier.');
    redirect('/member/surveys.php');
}

$db = get_db();

// 1. Fetch survey details
$stmt = $db->prepare("SELECT * FROM surveys WHERE id = :id AND status = 'active'");
$stmt->execute([':id' => $surveyId]);
$survey = $stmt->fetch();

if (!$survey) {
    flash('warning', 'This survey is no longer available or has closed.');
    redirect('/member/surveys.php');
}

// 2. Guard: Check if user has already completed this survey
$respCheck = $db->prepare("SELECT id FROM survey_responses WHERE survey_id = :sid AND user_id = :uid");
$respCheck->execute([':sid' => $surveyId, ':uid' => $user['id']]);
if ($respCheck->fetch()) {
    flash('warning', 'You have already completed this survey and earned your points reward.');
    redirect('/member/surveys.php');
}

// 3. Process submitted answers
$answers = [];
foreach ($_POST as $key => $val) {
    if (str_starts_with($key, 'q_')) {
        $answers[$key] = is_array($val) ? array_map('trim', $val) : trim($val);
    }
}
$answersJson = json_encode($answers);

$points = (int)$survey['reward_points'];
$usd = (float)$survey['reward_usd'];

try {
    // Insert response record
    $insResp = $db->prepare("
        INSERT INTO survey_responses (survey_id, user_id, status, points_awarded, usd_awarded, answers_json)
        VALUES (:sid, :uid, 'completed', :pts, :usd, :ans)
    ");
    $insResp->execute([
        ':sid' => $surveyId,
        ':uid' => $user['id'],
        ':pts' => $points,
        ':usd' => $usd,
        ':ans' => $answersJson
    ]);

    // Update survey completion count
    $upSurvey = $db->prepare("UPDATE surveys SET completed_count = completed_count + 1 WHERE id = :id");
    $upSurvey->execute([':id' => $surveyId]);

    // Update user balance & completed survey count
    $upUser = $db->prepare("
        UPDATE users SET 
            balance_points = balance_points + :pts,
            lifetime_earnings_usd = lifetime_earnings_usd + :usd,
            completed_surveys_count = completed_surveys_count + 1
        WHERE id = :id
    ");
    $upUser->execute([
        ':pts' => $points,
        ':usd' => $usd,
        ':id' => $user['id']
    ]);

    // Add financial ledger transaction
    add_transaction(
        (int)$user['id'],
        'survey_reward',
        "Completed Survey: " . $survey['title'],
        $points,
        $usd
    );

    // Add notification
    add_notification(
        (int)$user['id'],
        "Survey Reward Credited: +{$points} Points! 🎯",
        "Your responses for '{$survey['title']}' have been verified and {$points} points have been added to your balance.",
        'survey',
        '/member/wallet.php'
    );

    flash('success', "Survey Completed! You earned +{$points} Points (" . format_currency($usd) . " USD) deposited to your wallet.");
} catch (Exception $e) {
    flash('danger', 'An error occurred while crediting your survey reward. Please contact support.');
}

redirect('/member/surveys.php');
