<?php
/**
 * Veritolytics - Database Session Persistence Test Suite
 */

$_SERVER['HTTP_HOST'] = 'localhost:8000';
$_SERVER['REQUEST_URI'] = '/';
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['REMOTE_ADDR'] = '198.51.100.42';
$_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) TestBrowser/1.0';

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/DatabaseSessionHandler.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/auth_check.php';

$passed = 0;
$failed = 0;

function assert_db_session(string $name, bool $condition, string $details = '') {
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
echo "   DATABASE SESSION HANDLER & CSRF TEST SUITE\n";
echo "============================================================\n\n";

$db = Database::getConnection();
echo "Active DB Driver: " . Database::getDriver() . "\n\n";

// -----------------------------------------------------------------------------
// TEST 1: Table Creation & Structure
// -----------------------------------------------------------------------------
echo "--- 1. Testing Sessions Table in Database ---\n";
$handler = new DatabaseSessionHandler($db, 3600);
$handler->open('', '');

if (Database::getDriver() === 'sqlite') {
    $tblCheck = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='sessions'")->fetch();
} else {
    $tblCheck = $db->query("SHOW TABLES LIKE 'sessions'")->fetch();
}
assert_db_session("Sessions table exists in database", !empty($tblCheck));


// -----------------------------------------------------------------------------
// TEST 2: Direct Handler Read / Write / Destroy
// -----------------------------------------------------------------------------
echo "\n--- 2. Testing Direct Handler Operations ---\n";
$testSessionId = 'test_sess_' . bin2hex(random_bytes(16));
$testPayload = 'user_id|i:42;csrf_token|s:64:"abcdef1234567890abcdef1234567890abcdef1234567890abcdef1234567890";';

$writeOk = $handler->write($testSessionId, $testPayload);
assert_db_session("Handler writes session data to database", $writeOk);

$rowStmt = $db->prepare("SELECT * FROM sessions WHERE id = :id");
$rowStmt->execute([':id' => $testSessionId]);
$dbRow = $rowStmt->fetch(PDO::FETCH_ASSOC);

assert_db_session("Session row found in database", !empty($dbRow));
assert_db_session("Session row contains correct payload", $dbRow && $dbRow['data'] === $testPayload);
assert_db_session("Session row contains user_agent snippet", $dbRow && !empty($dbRow['user_agent']));
assert_db_session("Session row contains last_activity timestamp", $dbRow && (int)$dbRow['last_activity'] > (time() - 5));

$readData = $handler->read($testSessionId);
assert_db_session("Handler reads back session payload identically", $readData === $testPayload);

$destroyOk = $handler->destroy($testSessionId);
assert_db_session("Handler destroys session from database", $destroyOk);

$checkDestroy = $handler->read($testSessionId);
assert_db_session("Destroyed session returns empty string", $checkDestroy === '');


// -----------------------------------------------------------------------------
// TEST 3: PHP Native Session Integration with DatabaseSessionHandler
// -----------------------------------------------------------------------------
echo "\n--- 3. Testing PHP Native Session Integration ---\n";

// Set values in native $_SESSION
$_SESSION['veritolytics_user'] = 'test_pilot';
$_SESSION['role'] = 'member';
$csrfToken = generate_csrf_token();
$nativeSessionId = session_id();

assert_db_session("Native session_id() is populated", !empty($nativeSessionId));
assert_db_session("CSRF token is generated and in \$_SESSION", !empty($_SESSION['csrf_token']));

// Commit session to DB
session_write_close();

// Inspect DB to ensure PHP committed to database
$nativeRowStmt = $db->prepare("SELECT * FROM sessions WHERE id = :id");
$nativeRowStmt->execute([':id' => $nativeSessionId]);
$nativeDbRow = $nativeRowStmt->fetch(PDO::FETCH_ASSOC);

assert_db_session("Native session data persisted to DB sessions table", !empty($nativeDbRow));
assert_db_session("DB session data contains 'veritolytics_user'", $nativeDbRow && str_contains($nativeDbRow['data'], 'veritolytics_user'));
assert_db_session("DB session data contains CSRF token", $nativeDbRow && str_contains($nativeDbRow['data'], $csrfToken));


// -----------------------------------------------------------------------------
// TEST 4: Cross-Instance Serverless Request Simulation
// -----------------------------------------------------------------------------
echo "\n--- 4. Testing Cross-Instance Serverless Request Simulation ---\n";

// Spawn Container B as an independent process with cookie PHPSESSID = $nativeSessionId
$phpBin = 'C:\\xampp\\php\\php.exe';
$scriptB = str_replace('/', DIRECTORY_SEPARATOR, __DIR__ . '/simulate_container_b.php');
$outputB = shell_exec("\"{$phpBin}\" \"{$scriptB}\" \"{$nativeSessionId}\" \"{$csrfToken}\"");

assert_db_session("Container B reads session from MySQL / Database", str_contains((string)$outputB, 'USER:test_pilot'));
assert_db_session("Container B retrieves identical CSRF token from DB", str_contains((string)$outputB, 'TOKEN_MATCH:YES'));
assert_db_session("CSRF verification succeeds on Container B", str_contains((string)$outputB, 'VERIFY:VALID'));

Database::closeConnection();
$db = null;

// Verify tampered token fails in Container B
$outputBad = shell_exec("\"{$phpBin}\" \"{$scriptB}\" \"{$nativeSessionId}\" \"tampered_token_xyz\"");
assert_db_session("Tampered token fails on Container B", str_contains((string)$outputBad, 'VERIFY:INVALID'));

// -----------------------------------------------------------------------------
// TEST 5: Clean Up
// -----------------------------------------------------------------------------
$db = Database::getConnection();
$cleanHandler = new DatabaseSessionHandler($db, 3600);
$cleanHandler->destroy($nativeSessionId);
echo "\n============================================================\n";
echo "DATABASE SESSION RESULTS: {$passed} Passed, {$failed} Failed\n";
echo "============================================================\n";

if ($failed > 0) {
    exit(1);
}
exit(0);
