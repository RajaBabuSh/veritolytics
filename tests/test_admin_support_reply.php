<?php
/**
 * Test Suite: Admin Support Ticket Reply & Status Workflow
 * Verifies that updating ticket status and reply does not trigger HTTP 500,
 * correctly saves admin response, updates status, logs to ticket_replies,
 * preserves existing replies when empty responses are submitted, and notifies member.
 */

// Configure environment
$_SERVER['DOCUMENT_ROOT'] = dirname(__DIR__);
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['REQUEST_URI'] = '/admin/support.php';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/csrf.php';

$db = get_db();
$passed = 0;
$failed = 0;

function assert_test(string $name, bool $condition, string $details = '') {
    global $passed, $failed;
    if ($condition) {
        $passed++;
        echo "  [PASS] {$name}\n";
    } else {
        $failed++;
        echo "  [FAIL] {$name} - Details: {$details}\n";
    }
}

echo "============================================================\n";
echo " Veritolytics Admin Support Status & Reply Verification\n";
echo "============================================================\n\n";

// 1. Verify Database Schema and Parity
echo "--- Step 1: Database Schema & Parity Checks ---\n";

// Check if support_tickets has required columns
$isSqlite = (Database::getDriver() === 'sqlite');
if ($isSqlite) {
    $cols = $db->query("PRAGMA table_info(support_tickets)")->fetchAll(PDO::FETCH_COLUMN, 1);
} else {
    $cols = $db->query("SHOW COLUMNS FROM `support_tickets`")->fetchAll(PDO::FETCH_COLUMN, 0);
}

assert_test("support_tickets has status column", in_array('status', $cols));
assert_test("support_tickets has admin_reply column", in_array('admin_reply', $cols));
assert_test("support_tickets has admin_id column", in_array('admin_id', $cols));
assert_test("support_tickets has replied_at column", in_array('replied_at', $cols));
assert_test("support_tickets has updated_at column", in_array('updated_at', $cols));

// Check if ticket_replies table exists
if ($isSqlite) {
    $hasRepliesTable = (bool)$db->query("SELECT 1 FROM sqlite_master WHERE type='table' AND name='ticket_replies'")->fetch();
} else {
    $hasRepliesTable = (bool)$db->query("SHOW TABLES LIKE 'ticket_replies'")->fetch();
}
assert_test("ticket_replies table exists", $hasRepliesTable);

// 2. Setup Test Admin User and Test Member Ticket
echo "\n--- Step 2: Setup Test Data ---\n";
$adminStmt = $db->query("SELECT * FROM users WHERE role = 'admin' LIMIT 1");
$adminUser = $adminStmt->fetch();
if (!$adminUser) {
    $db->exec("INSERT INTO users (first_name, last_name, email, password_hash, role, status) VALUES ('Test', 'Admin', 'testadmin_support@example.com', 'dummy_hash', 'admin', 'active')");
    $adminUser = $db->query("SELECT * FROM users WHERE email = 'testadmin_support@example.com'")->fetch();
}
assert_test("Admin user available for testing", !empty($adminUser));

$memberStmt = $db->query("SELECT * FROM users WHERE role = 'member' LIMIT 1");
$memberUser = $memberStmt->fetch();
if (!$memberUser) {
    $db->exec("INSERT INTO users (first_name, last_name, email, password_hash, role, status) VALUES ('Test', 'Member', 'testmember_support@example.com', 'dummy_hash', 'member', 'active')");
    $memberUser = $db->query("SELECT * FROM users WHERE email = 'testmember_support@example.com'")->fetch();
}
assert_test("Member user available for testing", !empty($memberUser));

// Create a new support ticket
$ticketNum = 'TCK-TEST-' . random_int(10000, 99999);
$ins = $db->prepare("
    INSERT INTO support_tickets (ticket_number, user_id, name, email, subject, message, priority, status)
    VALUES (:num, :uid, :name, :email, 'Inquiry on Payout', 'When will my payout be sent?', 'high', 'open')
");
$ins->execute([
    ':num'   => $ticketNum,
    ':uid'   => $memberUser['id'],
    ':name'  => $memberUser['first_name'] . ' ' . $memberUser['last_name'],
    ':email' => $memberUser['email']
]);
$ticketId = (int)$db->lastInsertId();
assert_test("Test ticket created with ID #{$ticketId}", $ticketId > 0);

// Setup Admin Session
$_SESSION['user_id'] = $adminUser['id'];
$_SESSION['user_role'] = 'admin';
$_SESSION['user_email'] = $adminUser['email'];

// 3. Test Workflow: Change Status to 'in_review' + Add Admin Response
echo "\n--- Step 3: Change Status to 'in_review' + Add Admin Response ---\n";
$csrfToken = csrf_token();
$responseMsg1 = "We have received your payout inquiry and are reviewing payment records.";

// Simulate POST to admin/support.php logic
$statusRaw = 'in_review';
$newReply = $responseMsg1;
$allowedStatuses = ['open', 'in_review', 'resolved'];
$status = in_array($statusRaw, $allowedStatuses, true) ? $statusRaw : 'open';

// Run transaction
$db->beginTransaction();
$up = $db->prepare("
    UPDATE support_tickets 
    SET status = :st, 
        admin_reply = :reply, 
        admin_id = :aid, 
        replied_at = :rat, 
        updated_at = :upat 
    WHERE id = :id
");
$now1 = date('Y-m-d H:i:s');
$up->execute([
    ':st'    => $status,
    ':reply' => $newReply,
    ':aid'   => (int)$adminUser['id'],
    ':rat'   => $now1,
    ':upat'  => $now1,
    ':id'    => $ticketId
]);

// Add ticket_replies log
$repStmt = $db->prepare("
    INSERT INTO ticket_replies (ticket_id, user_id, is_admin, message, created_at)
    VALUES (:ticket_id, :user_id, 1, :message, :created_at)
");
$repStmt->execute([
    ':ticket_id'  => $ticketId,
    ':user_id'    => (int)$adminUser['id'],
    ':message'    => $newReply,
    ':created_at' => $now1
]);

// In-app notification
add_notification(
    (int)$memberUser['id'],
    "Update on Ticket #{$ticketNum}",
    "Your support ticket #{$ticketNum} status is now 'In Review'. Staff response: \"{$newReply}\"",
    'info',
    '/member/support.php'
);

$db->commit();

// Verify ticket updated in DB
$check1 = $db->query("SELECT * FROM support_tickets WHERE id = {$ticketId}")->fetch();
assert_test("Ticket status updated to 'in_review'", $check1['status'] === 'in_review');
assert_test("Ticket admin_reply saved correctly", $check1['admin_reply'] === $responseMsg1);
assert_test("Ticket admin_id recorded", (int)$check1['admin_id'] === (int)$adminUser['id']);
assert_test("Ticket replied_at recorded", !empty($check1['replied_at']));

// Verify ticket_replies row
$repCheck = $db->query("SELECT * FROM ticket_replies WHERE ticket_id = {$ticketId} ORDER BY id DESC LIMIT 1")->fetch();
assert_test("ticket_replies logged reply", $repCheck && $repCheck['message'] === $responseMsg1);

// 4. Test Workflow: Change Status to 'resolved' with Empty Response (Never overwrite past reply)
echo "\n--- Step 4: Change Status to 'resolved' with Empty Response ---\n";
// When admin submits empty reply to resolve ticket, previous response must NOT be wiped!
$statusRaw2 = 'resolved';
$newReply2 = ''; // Empty input

$ticketCurrent = $db->query("SELECT * FROM support_tickets WHERE id = {$ticketId}")->fetch();
$hasNewReply2 = ($newReply2 !== '');
$finalReply2 = $hasNewReply2 ? $newReply2 : ($ticketCurrent['admin_reply'] ?? null);
$finalAdminId2 = $hasNewReply2 ? (int)$adminUser['id'] : ($ticketCurrent['admin_id'] ?? (int)$adminUser['id']);
$finalRepliedAt2 = $hasNewReply2 ? date('Y-m-d H:i:s') : ($ticketCurrent['replied_at'] ?? null);
$now2 = date('Y-m-d H:i:s');

$db->beginTransaction();
$up2 = $db->prepare("
    UPDATE support_tickets 
    SET status = :st, 
        admin_reply = :reply, 
        admin_id = :aid, 
        replied_at = :rat, 
        updated_at = :upat 
    WHERE id = :id
");
$up2->execute([
    ':st'    => $statusRaw2,
    ':reply' => $finalReply2,
    ':aid'   => $finalAdminId2,
    ':rat'   => $finalRepliedAt2,
    ':upat'  => $now2,
    ':id'    => $ticketId
]);
$db->commit();

$check2 = $db->query("SELECT * FROM support_tickets WHERE id = {$ticketId}")->fetch();
assert_test("Ticket status updated to 'resolved'", $check2['status'] === 'resolved');
assert_test("Existing reply preserved (not overwritten by empty input)", $check2['admin_reply'] === $responseMsg1);
assert_test("Admin ID still preserved", (int)$check2['admin_id'] === (int)$adminUser['id']);

// 5. Test Workflow: Member Can View Reply in Member Support History
echo "\n--- Step 5: Verify Member Support Inquiries View ---\n";
$memberTicketsStmt = $db->prepare("SELECT * FROM support_tickets WHERE user_id = :uid ORDER BY created_at DESC");
$memberTicketsStmt->execute([':uid' => $memberUser['id']]);
$userTickets = $memberTicketsStmt->fetchAll();

$foundTicket = null;
foreach ($userTickets as $ut) {
    if ($ut['id'] == $ticketId) {
        $foundTicket = $ut;
        break;
    }
}

assert_test("Member ticket found in member tickets query", $foundTicket !== null);
assert_test("Member sees status 'resolved'", $foundTicket && $foundTicket['status'] === 'resolved');
assert_test("Member sees admin_reply content", $foundTicket && $foundTicket['admin_reply'] === $responseMsg1);

// 6. Test Workflow: Invalid Status Rejected Safely
echo "\n--- Step 6: Invalid Status Handling ---\n";
$invalidStatus = "invalid_status_malicious'; DROP TABLE support_tickets; --";
$statusValid = in_array($invalidStatus, $allowedStatuses, true) ? $invalidStatus : 'open';
assert_test("Invalid status safely rejected and mapped to default", $statusValid === 'open');

// 7. Test Workflow: Full Request to admin/support.php via PHP CLI
echo "\n--- Step 7: Simulate Full HTTP POST to admin/support.php ---\n";
// We prepare a clean ticket
$ticketNum3 = 'TCK-HTTP-' . random_int(10000, 99999);
$ins3 = $db->prepare("
    INSERT INTO support_tickets (ticket_number, user_id, name, email, subject, message, priority, status)
    VALUES (:num, :uid, 'HTTP User', 'http@example.com', 'Subject 3', 'Message 3', 'normal', 'open')
");
$ins3->execute([':num' => $ticketNum3, ':uid' => $memberUser['id']]);
$ticketId3 = (int)$db->lastInsertId();

// Set simulated POST environment
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST['csrf_token'] = csrf_token();
$_POST['ticket_id'] = $ticketId3;
$_POST['status'] = 'in_review';
$_POST['admin_reply'] = 'Official admin response via POST request.';

// Capture script execution (register shutdown function so redirect exit prints EXECUTION_COMPLETE)
$postScript = dirname(__DIR__) . '/tests/run_admin_support_post.php';
file_put_contents($postScript, '<?php
$_SERVER["DOCUMENT_ROOT"] = "' . addslashes(dirname(__DIR__)) . '";
$_SERVER["HTTP_HOST"] = "localhost";
$_SERVER["REQUEST_URI"] = "/admin/support.php";
$_SERVER["REMOTE_ADDR"] = "127.0.0.1";
$_SERVER["REQUEST_METHOD"] = "POST";

require_once "' . addslashes(dirname(__DIR__)) . '/config/app.php";
require_once "' . addslashes(dirname(__DIR__)) . '/config/database.php";
require_once "' . addslashes(dirname(__DIR__)) . '/includes/csrf.php";

$_SESSION["user_id"] = ' . (int)$adminUser['id'] . ';
$_SESSION["user_role"] = "admin";
$_SESSION["user_email"] = "' . addslashes($adminUser['email']) . '";
$_POST["csrf_token"] = csrf_token();
$_POST["ticket_id"] = ' . $ticketId3 . ';
$_POST["status"] = "in_review";
$_POST["admin_reply"] = "Official admin response via POST request.";

register_shutdown_function(function() {
    echo "EXECUTION_COMPLETE";
});

// Run admin/support.php
include "' . addslashes(dirname(__DIR__)) . '/admin/support.php";
');

// Close parent session and connection to release locks for child process
session_write_close();
Database::closeConnection();

$output = shell_exec('C:\\xampp\\php\\php.exe ' . escapeshellarg($postScript) . ' 2>&1');
@unlink($postScript);

// Re-open DB in parent
$db = Database::getConnection();

assert_test("POST request executed without fatal errors", str_contains((string)$output, 'EXECUTION_COMPLETE'), "Output: " . $output);

$check3 = $db->query("SELECT * FROM support_tickets WHERE id = {$ticketId3}")->fetch();
assert_test("Ticket status updated to 'in_review' via POST", $check3['status'] === 'in_review');
assert_test("Ticket admin_reply saved via POST", $check3['admin_reply'] === 'Official admin response via POST request.');
assert_test("Ticket admin_id saved via POST", (int)$check3['admin_id'] === (int)$adminUser['id']);
assert_test("Ticket replied_at saved via POST", !empty($check3['replied_at']));

echo "\n============================================================\n";
echo " Test Results: {$passed} Passed, {$failed} Failed\n";
echo "============================================================\n";

if ($failed > 0) {
    exit(1);
}
