<?php
/**
 * Veritolytics - Automated Test Suite
 * Test: Registration, Support Tickets, CSRF, and Serverless Session Persistence
 */

$_SERVER['HTTP_HOST'] = 'localhost:8000';
$_SERVER['REQUEST_URI'] = '/';
$_SERVER['REQUEST_METHOD'] = 'GET';

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/auth_check.php';

$passed = 0;
$failed = 0;

function assert_test(string $name, bool $condition, string $details = '') {
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
echo "   VERITOLYTICS COMPREHENSIVE AUTOMATED TEST SUITE\n";
echo "============================================================\n\n";

$db = get_db();
echo "Active DB Driver: " . Database::getDriver() . "\n\n";

// -----------------------------------------------------------------------------
// TEST 1: CSRF Protection & Multi-Layer Verification
// -----------------------------------------------------------------------------
echo "--- TEST SUITE 1: CSRF Protection System ---\n";

$token = generate_csrf_token();
assert_test("CSRF Token is generated", !empty($token) && strlen($token) > 20);
assert_test("CSRF Token validates with active session", verify_csrf_token($token));

// Test stateless verification (simulate session wiped out / new serverless instance)
$_SESSION['csrf_token'] = null;
assert_test("CSRF Token validates statelessly via cryptographic HMAC when session is empty", verify_csrf_token($token));

// Test invalid / tampered token fails
$tamperedToken = substr($token, 0, -4) . 'ffff';
assert_test("Tampered CSRF token is strictly rejected", !verify_csrf_token($tamperedToken));
assert_test("Empty CSRF token is rejected", !verify_csrf_token(''));
assert_test("Null CSRF token is rejected", !verify_csrf_token(null));

// Test double submit cookie check
$_SESSION['csrf_token'] = null;
$_COOKIE['vy_csrf'] = $token;
assert_test("CSRF Token validates via double-submit cookie", verify_csrf_token($token));
unset($_COOKIE['vy_csrf']);


// -----------------------------------------------------------------------------
// TEST 2: Member Registration & Welcome Bonus Engine
// -----------------------------------------------------------------------------
echo "\n--- TEST SUITE 2: Member Registration Engine ---\n";

$testEmail = 'test_user_' . time() . '_' . rand(1000, 9999) . '@example.com';
$testPassword = 'TestPassword123!';
$testFirstName = 'Alex';
$testLastName = 'Morgan';
$testCountry = 'Canada';

// Test duplicate email check logic
$dupStmt = $db->prepare("SELECT id FROM users WHERE LOWER(email) = LOWER(:email)");
$dupStmt->execute([':email' => 'member@veritopinion.com']);
$existingUser = $dupStmt->fetch();
if (!$existingUser) {
    $dupStmt->execute([':email' => 'member@veritolytics.com']);
    $existingUser = $dupStmt->fetch();
}
assert_test("Pre-existing user email detected by duplicate validator", !empty($existingUser));

// Perform Registration Workflow
$hash = password_hash($testPassword, PASSWORD_DEFAULT);
$welcomeBonus = WELCOME_BONUS_POINTS;
$welcomeUsd = (float)($welcomeBonus / POINTS_PER_USD);

$db->beginTransaction();

$insertStmt = $db->prepare("
    INSERT INTO users (
        first_name, last_name, email, password_hash, country, 
        role, status, balance_points, lifetime_earnings_usd,
        completed_surveys_count, profile_completion_pct
    ) VALUES (
        :first_name, :last_name, :email, :password_hash, :country,
        'member', 'active', :balance_points, :lifetime_earnings_usd,
        0, 35
    )
");

$insertStmt->execute([
    ':first_name' => $testFirstName,
    ':last_name' => $testLastName,
    ':email' => strtolower($testEmail),
    ':password_hash' => $hash,
    ':country' => $testCountry,
    ':balance_points' => $welcomeBonus,
    ':lifetime_earnings_usd' => $welcomeUsd,
]);

$newUserId = (int)$db->lastInsertId();
assert_test("New member row created in database", $newUserId > 0, "User ID: {$newUserId}");

// Add Welcome Bonus Transaction
$txResult = add_transaction($newUserId, 'welcome_bonus', 'Welcome Bonus - Account Registered', $welcomeBonus, $welcomeUsd);
assert_test("Welcome bonus transaction added to ledger", $txResult);

// Add Welcome Notification
$notifResult = add_notification(
    $newUserId,
    'Welcome to Veritolytics! 🎉',
    'Your panel registration is complete. 500 Welcome Points ($5.00) have been deposited into your wallet!',
    'success',
    '/member/wallet.php'
);
assert_test("Welcome notification added to inbox", $notifResult);

$db->commit();

// Verify persisted database records
$userVerifyStmt = $db->prepare("SELECT * FROM users WHERE id = :id");
$userVerifyStmt->execute([':id' => $newUserId]);
$createdUser = $userVerifyStmt->fetch();

assert_test("User verified in users table", $createdUser && $createdUser['email'] === strtolower($testEmail));
assert_test("User balance initialized to 500 points", (int)$createdUser['balance_points'] === 500);
assert_test("User lifetime earnings initialized to $5.00", (float)$createdUser['lifetime_earnings_usd'] === 5.00);
assert_test("Password correctly verified with password_verify()", password_verify($testPassword, $createdUser['password_hash']));

// Verify transaction record in transactions table
$txVerifyStmt = $db->prepare("SELECT * FROM transactions WHERE user_id = :uid ORDER BY id DESC LIMIT 1");
$txVerifyStmt->execute([':uid' => $newUserId]);
$createdTx = $txVerifyStmt->fetch();
assert_test("Transaction recorded with points = 500", $createdTx && (int)$createdTx['points'] === 500);

// Verify notification in notifications table
$notifVerifyStmt = $db->prepare("SELECT * FROM notifications WHERE user_id = :uid ORDER BY id DESC LIMIT 1");
$notifVerifyStmt->execute([':uid' => $newUserId]);
$createdNotif = $notifVerifyStmt->fetch();
assert_test("Notification recorded with welcome title", $createdNotif && str_contains($createdNotif['title'], 'Welcome'));


// -----------------------------------------------------------------------------
// TEST 3: Authentication & Cross-Instance Session Recovery
// -----------------------------------------------------------------------------
echo "\n--- TEST SUITE 3: Authentication & Serverless Container Recovery ---\n";

set_authenticated_session($newUserId, 'member', $hash);
assert_test("User is logged in via session", is_logged_in());
$currentUser = current_user();
assert_test("current_user() returns registered member details", $currentUser && (int)$currentUser['id'] === $newUserId);

// Simulate Serverless Container Instance Switch: Wipe in-memory $_SESSION
$_SESSION['user_id'] = null;
$_SESSION['user_role'] = null;

// The browser would send cookie vy_auth_token:
// Let's generate the cookie payload as set_authenticated_session() does
$hashSnippet = substr((string)$hash, 0, 16);
$expiry = time() + (86400 * 30);
$signature = hash_hmac('sha256', "{$newUserId}:{$expiry}:{$hashSnippet}", get_app_secret());
$_COOKIE['vy_auth_token'] = base64_encode("{$newUserId}:{$expiry}:{$signature}");

assert_test("User session successfully recovered from signed vy_auth_token cookie on new instance", is_logged_in());
assert_test('$_SESSION[\'user_id\'] re-established automatically', (int)$_SESSION['user_id'] === $newUserId);


// -----------------------------------------------------------------------------
// TEST 4: Support Ticket System (Guest and Logged-In Submissions)
// -----------------------------------------------------------------------------
echo "\n--- TEST SUITE 4: Support Ticket System ---\n";

// A. Logged-in member ticket submission
$ticketNumber1 = 'TCK-' . date('Y') . '-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 6));
$ticketStmt1 = $db->prepare("
    INSERT INTO support_tickets (ticket_number, user_id, name, email, subject, message, status, priority)
    VALUES (:ticket_number, :user_id, :name, :email, :subject, :message, 'open', :priority)
");
$ticketStmt1->execute([
    ':ticket_number' => $ticketNumber1,
    ':user_id' => $newUserId,
    ':name' => 'Alex Morgan',
    ':email' => $testEmail,
    ':subject' => 'Survey Credit Inquiry',
    ':message' => 'I completed the Automotive Survey #101 but did not see points updated yet.',
    ':priority' => 'normal',
]);

$checkTicket1 = $db->prepare("SELECT * FROM support_tickets WHERE ticket_number = :tn");
$checkTicket1->execute([':tn' => $ticketNumber1]);
$savedTicket1 = $checkTicket1->fetch();
assert_test("Member support ticket saved with correct user_id", $savedTicket1 && (int)$savedTicket1['user_id'] === $newUserId);
assert_test("Ticket status defaults to 'open'", $savedTicket1 && $savedTicket1['status'] === 'open');

// B. Guest support ticket submission (user_id IS NULL)
$ticketNumber2 = 'TCK-' . date('Y') . '-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 6));
$ticketStmt2 = $db->prepare("
    INSERT INTO support_tickets (ticket_number, user_id, name, email, subject, message, status, priority)
    VALUES (:ticket_number, :user_id, :name, :email, :subject, :message, 'open', :priority)
");
$ticketStmt2->execute([
    ':ticket_number' => $ticketNumber2,
    ':user_id' => null,
    ':name' => 'Public Visitor',
    ':email' => 'visitor@example.org',
    ':subject' => 'General Question',
    ':message' => 'How long does PayPal redemption typically take?',
    ':priority' => 'normal',
]);

$checkTicket2 = $db->prepare("SELECT * FROM support_tickets WHERE ticket_number = :tn");
$checkTicket2->execute([':tn' => $ticketNumber2]);
$savedTicket2 = $checkTicket2->fetch();
assert_test("Guest support ticket saved with NULL user_id", $savedTicket2 && $savedTicket2['user_id'] === null);
assert_test("Guest ticket email matches visitor@example.org", $savedTicket2 && $savedTicket2['email'] === 'visitor@example.org');


// -----------------------------------------------------------------------------
// TEST 5: Flash Messages & Cross-Redirect Persistence
// -----------------------------------------------------------------------------
echo "\n--- TEST SUITE 5: Flash Alerts System ---\n";

flash('success', 'Your ticket has been received!');
assert_test("Flash message stored in session", !empty($_SESSION['flashes']));

$flashes = get_flashes();
assert_test("get_flashes() retrieves messages", count($flashes) === 1 && $flashes[0]['type'] === 'success');
assert_test("get_flashes() clears flashes after retrieval", empty($_SESSION['flashes']));


// -----------------------------------------------------------------------------
// SUMMARY
// -----------------------------------------------------------------------------
echo "\n============================================================\n";
echo "TEST RESULTS: {$passed} Passed, {$failed} Failed\n";
echo "============================================================\n";

if ($failed > 0) {
    exit(1);
}
exit(0);
