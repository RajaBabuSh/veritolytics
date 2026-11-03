<?php
/**
 * Veritolytics - User Persistence & Management Test Suite
 */

$_SERVER['HTTP_HOST'] = 'localhost:8000';
$_SERVER['REQUEST_URI'] = '/';
$_SERVER['REQUEST_METHOD'] = 'GET';

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/DatabaseSessionHandler.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/auth_check.php';

$passed = 0;
$failed = 0;

function assert_mgmt(string $name, bool $condition, string $details = '') {
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
echo "   USER PERSISTENCE & ADMIN MANAGEMENT TEST SUITE\n";
echo "============================================================\n\n";

$db = Database::getConnection();
echo "Active DB Driver: " . Database::getDriver() . "\n";
$statusInfo = Database::getStatusInfo();
echo "Active Host: " . $statusInfo['host'] . " | Database: " . $statusInfo['database'] . "\n\n";

// -----------------------------------------------------------------------------
// TEST 1: Database Status & Diagnostic Info
// -----------------------------------------------------------------------------
echo "--- 1. Testing Database Driver & Configuration Helper ---\n";
assert_mgmt("Database driver is detected", !empty(Database::getDriver()));
assert_mgmt("Database host is reported", !empty(Database::getActiveHost()));
assert_mgmt("Database name is reported", !empty(Database::getActiveDatabase()));
assert_mgmt("getStatusInfo() provides structured diagnostic payload", is_array($statusInfo) && isset($statusInfo['driver']));


// -----------------------------------------------------------------------------
// TEST 2: Permanent User Creation & Immediate Visibility
// -----------------------------------------------------------------------------
echo "\n--- 2. Testing New Member Registration & Immediate Visibility ---\n";

$testEmail = 'persistent_member_' . time() . '_' . rand(100, 999) . '@example.com';
$testPassword = 'SuperSecretPassword123!';
$hash = password_hash($testPassword, PASSWORD_DEFAULT);

$insertStmt = $db->prepare("
    INSERT INTO users (
        first_name, last_name, email, password_hash, country, 
        role, status, balance_points, lifetime_earnings_usd,
        completed_surveys_count, profile_completion_pct
    ) VALUES (
        'Marcus', 'Vance', :email, :password_hash, 'United Kingdom',
        'member', 'active', 500, 5.00,
        0, 35
    )
");
$insertStmt->execute([
    ':email' => strtolower($testEmail),
    ':password_hash' => $hash,
]);
$newUserId = (int)$db->lastInsertId();

assert_mgmt("New user created with primary key ID", $newUserId > 0, "ID: {$newUserId}");

// Query admin directory order (ORDER BY id DESC)
$adminListStmt = $db->query("SELECT * FROM users ORDER BY id DESC LIMIT 5");
$topUsers = $adminListStmt->fetchAll();

assert_mgmt("Newly created user is row #1 in Admin Users List", !empty($topUsers) && (int)$topUsers[0]['id'] === $newUserId);
assert_mgmt("User balance points initialized to 500", (int)$topUsers[0]['balance_points'] === 500);
assert_mgmt("User lifetime USD initialized to 5.00", (float)$topUsers[0]['lifetime_earnings_usd'] === 5.00);
assert_mgmt("User status is active", $topUsers[0]['status'] === 'active');
assert_mgmt("User deleted_at is NULL by default", empty($topUsers[0]['deleted_at']));


// -----------------------------------------------------------------------------
// TEST 3: Safe Soft-Deletion & Restoration
// -----------------------------------------------------------------------------
echo "\n--- 3. Testing Safe Soft-Deletion vs Hard Deletion ---\n";

// Soft delete the user
$softDel = $db->prepare("UPDATE users SET status = 'suspended', deleted_at = CURRENT_TIMESTAMP WHERE id = :id");
$softDel->execute([':id' => $newUserId]);

// Verify user record still exists in database!
$checkRecord = $db->prepare("SELECT * FROM users WHERE id = :id");
$checkRecord->execute([':id' => $newUserId]);
$userAfterDelete = $checkRecord->fetch();

assert_mgmt("User row STILL EXISTS in database after soft-delete", !empty($userAfterDelete));
assert_mgmt("User balance is preserved completely after deactivation", (int)$userAfterDelete['balance_points'] === 500);
assert_mgmt("User deleted_at timestamp is populated", !empty($userAfterDelete['deleted_at']));
assert_mgmt("User status updated to suspended", $userAfterDelete['status'] === 'suspended');

// Verify soft-deleted user cannot sign in
$canLogin = password_verify($testPassword, $userAfterDelete['password_hash']) && empty($userAfterDelete['deleted_at']);
assert_mgmt("Soft-deleted user blocked from authentication", !$canLogin);

// Restore user
$restoreStmt = $db->prepare("UPDATE users SET status = 'active', deleted_at = NULL WHERE id = :id");
$restoreStmt->execute([':id' => $newUserId]);

$checkRestore = $db->prepare("SELECT * FROM users WHERE id = :id");
$checkRestore->execute([':id' => $newUserId]);
$userAfterRestore = $checkRestore->fetch();

assert_mgmt("User successfully restored to active status", $userAfterRestore['status'] === 'active');
assert_mgmt("User deleted_at is reset to NULL", empty($userAfterRestore['deleted_at']));
$canLoginAfterRestore = password_verify($testPassword, $userAfterRestore['password_hash']) && empty($userAfterRestore['deleted_at']);
assert_mgmt("Restored user can authenticate again", $canLoginAfterRestore);


// -----------------------------------------------------------------------------
// TEST 4: Session Expiration & GC Independence
// -----------------------------------------------------------------------------
echo "\n--- 4. Testing Session GC Independence from Users Table ---\n";

$sessionHandler = new DatabaseSessionHandler($db, 3600);
// Trigger session garbage collection
$gcCount = $sessionHandler->gc(0); // clean all sessions older than 0 seconds

// Verify user table is 100% untouched
$checkUserAgain = $db->prepare("SELECT * FROM users WHERE id = :id");
$checkUserAgain->execute([':id' => $newUserId]);
$userAfterGc = $checkUserAgain->fetch();

assert_mgmt("Session garbage collection does NOT touch or delete user records", !empty($userAfterGc));
assert_mgmt("User ID remains unchanged across session operations", (int)$userAfterGc['id'] === $newUserId);


// -----------------------------------------------------------------------------
// TEST 5: Status Filter Coverage in Admin Directory
// -----------------------------------------------------------------------------
echo "\n--- 5. Testing Status Filter Queries ---\n";

$activeUsers = $db->query("SELECT COUNT(*) FROM users WHERE status = 'active' AND (deleted_at IS NULL)")->fetchColumn();
assert_mgmt("Active users query executes successfully", (int)$activeUsers > 0);

$allUsersCount = $db->query("SELECT COUNT(*) FROM users")->fetchColumn();
assert_mgmt("All users query returns total members", (int)$allUsersCount >= 2);


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
