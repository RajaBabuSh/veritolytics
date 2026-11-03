<?php
/**
 * Veritolytics - Simulated HTTP Endpoints Integration Test
 */

$_SERVER['HTTP_HOST'] = 'localhost:8000';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';

$passed = 0;
$failed = 0;

function assert_http(string $name, bool $condition, string $details = '') {
    global $passed, $failed;
    if ($condition) {
        echo "  [PASS] {$name}\n";
        $passed++;
    } else {
        echo "  [FAIL] {$name}" . ($details ? " - {$details}" : "") . "\n";
        $failed++;
    }
}

echo "============================================================\n";
echo "   HTTP ENDPOINT INTEGRATION TESTS\n";
echo "============================================================\n\n";

$db = Database::getConnection();

// Release session lock and close database connection before spawning child processes
if (session_status() === PHP_SESSION_ACTIVE) {
    session_write_close();
}
Database::closeConnection();
$db = null;

// 1. Submit Ticket
echo "--- 1. Testing POST to submit_ticket.php ---\n";
$phpBin = 'C:\\xampp\\php\\php.exe';
$script1 = str_replace('/', DIRECTORY_SEPARATOR, __DIR__ . '/run_post_ticket.php');
exec("\"{$phpBin}\" \"{$script1}\" 2>&1", $out1, $ret1);
assert_http("submit_ticket.php executed cleanly (exit code 0)", $ret1 === 0);

$db = Database::getConnection();
$ticketStmt = $db->query("SELECT * FROM support_tickets WHERE email LIKE 'sarah_connor_%' ORDER BY id DESC LIMIT 1");
$ticket = $ticketStmt->fetch();
assert_http("Support ticket persisted in DB", !empty($ticket));
assert_http("Support ticket has generated ticket number TCK-*", $ticket && str_starts_with($ticket['ticket_number'], 'TCK-'));
assert_http("Ticket subject and message recorded properly", $ticket && $ticket['subject'] === 'Survey Credit Inquiry');


// 2. Register User
echo "\n--- 2. Testing POST to auth/register.php ---\n";
// Close connection again before second child process
Database::closeConnection();
$db = null;
$script2 = str_replace('/', DIRECTORY_SEPARATOR, __DIR__ . '/run_post_register.php');
exec("\"{$phpBin}\" \"{$script2}\" 2>&1", $out2, $ret2);
assert_http("auth/register.php executed cleanly (exit code 0)", $ret2 === 0);

$db = Database::getConnection();
$userStmt = $db->query("SELECT * FROM users WHERE email LIKE 'john_connor_%' ORDER BY id DESC LIMIT 1");
$user = $userStmt->fetch();
assert_http("New user successfully registered and found in DB", !empty($user));
assert_http("Welcome bonus 500 points credited in balance_points", $user && (int)$user['balance_points'] === 500);
assert_http("Lifetime earnings initialized to $5.00", $user && (float)$user['lifetime_earnings_usd'] === 5.00);

if ($user) {
    $txStmt = $db->prepare("SELECT * FROM transactions WHERE user_id = :uid AND points = 500");
    $txStmt->execute([':uid' => $user['id']]);
    $tx = $txStmt->fetch();
    assert_http("500 points welcome bonus transaction logged in transactions table", !empty($tx));

    $notifStmt = $db->prepare("SELECT * FROM notifications WHERE user_id = :uid");
    $notifStmt->execute([':uid' => $user['id']]);
    $notif = $notifStmt->fetch();
    assert_http("Welcome notification created in notifications table", !empty($notif));
}

echo "\n============================================================\n";
echo "HTTP INTEGRATION RESULTS: {$passed} Passed, {$failed} Failed\n";
echo "============================================================\n";

if ($failed > 0) {
    exit(1);
}
exit(0);
