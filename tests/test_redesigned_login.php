<?php
/**
 * Test Suite for Redesigned Veritolytics Split-Screen Login Page
 */

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/auth_check.php';

echo "============================================================\n";
echo "   VERITOLYTICS REDESIGNED LOGIN SUITE VERIFICATION\n";
echo "============================================================\n\n";

$passCount = 0;
$failCount = 0;

function assert_test($description, $condition) {
    global $passCount, $failCount;
    if ($condition) {
        echo "  [PASS] $description\n";
        $passCount++;
    } else {
        echo "  [FAIL] $description\n";
        $failCount++;
    }
}

// Ensure clean session
if (session_status() === PHP_SESSION_ACTIVE) {
    session_destroy();
}
$_SESSION = [];

// 1. TEST GET RENDERING
echo "--- 1. Testing GET Request & Layout Structure ---\n";
$_SERVER['REQUEST_METHOD'] = 'GET';
ob_start();
include __DIR__ . '/../auth/login.php';
$html = ob_get_clean();

assert_test("HTML contains auth-split-layout", str_contains($html, 'auth-split-layout'));
assert_test("HTML contains auth-hero-section (69% width)", str_contains($html, 'auth-hero-section'));
assert_test("HTML contains auth-form-section (31% width)", str_contains($html, 'auth-form-section'));
assert_test("HTML references auth-hero-bg.jpg", str_contains($html, 'auth-hero-bg.jpg'));
assert_test("HTML contains Glassmorphism card (auth-glass-card)", str_contains($html, 'auth-glass-card'));
assert_test("Card contains 'Empowering Your Voice, Rewarding Your Time.'", str_contains($html, 'Empowering Your Voice, Rewarding Your Time.'));
assert_test("Card contains 'Join thousands of professionals worldwide'", str_contains($html, 'Join thousands of professionals worldwide'));
assert_test("Benefit 1: Instant Payouts via PayPal & Gift Cards", str_contains($html, 'Instant Payouts via PayPal &amp; Gift Cards'));
assert_test("Benefit 2: Bank-Level Data Encryption & Privacy", str_contains($html, 'Bank-Level Data Encryption &amp; Privacy'));
assert_test("Benefit 3: Exclusive Global B2B Research Studies", str_contains($html, 'Exclusive Global B2B Research Studies'));
assert_test("Statistic 1: 5,000+ Early Members", str_contains($html, '5,000+') && str_contains($html, 'Early Members'));
assert_test("Statistic 2: \$250+ Paid Daily", str_contains($html, '$250+') && str_contains($html, 'Paid Daily'));
assert_test("Statistic 3: ISO 20252 Ready", str_contains($html, 'ISO 20252') && str_contains($html, 'Ready'));

echo "\n--- 2. Testing Right Login Section & Form Elements ---\n";
assert_test("Contains brand logo image", str_contains($html, 'veritopinion-logo.png') || str_contains($html, 'veritolytics-logo.png'));
assert_test("Contains heading 'Welcome back'", str_contains($html, 'Welcome back'));
assert_test("Contains subtitle 'Please enter your details to sign in.'", str_contains($html, 'Please enter your details to sign in.'));
assert_test("Contains Email Address field with placeholder", str_contains($html, 'placeholder="Enter your email address"'));
assert_test("Contains Password field with toggle button", str_contains($html, 'btn-toggle-password'));
assert_test("Contains 'Forgot password?' link pointing to auth/forgot-password.php", str_contains($html, 'Forgot password?') && str_contains($html, 'forgot-password.php'));
assert_test("Contains primary 'Sign in' button", str_contains($html, 'Sign in'));
assert_test("Contains 'Don\'t have an account? Sign up' pointing to auth/register.php", str_contains($html, 'Sign up') && str_contains($html, 'register.php'));
assert_test("Contains 'SECURE & VERIFIED PAYOUTS'", str_contains($html, 'Secure &amp; Verified Payouts'));
assert_test("Contains PayPal payment badge", str_contains($html, 'PayPal'));
assert_test("Contains Visa payment badge", str_contains($html, 'Visa'));
assert_test("Contains Mastercard payment badge", str_contains($html, 'Mastercard'));
assert_test("Contains Amazon payment badge", str_contains($html, 'amazon'));
assert_test("Contains footer copyright '© 2026 Veritolytics. Powered by Insightolytics LLC.'", str_contains($html, '© 2026 Veritolytics. Powered by Insightolytics LLC.'));
assert_test("Contains Privacy Policy & Terms of Service links", str_contains($html, 'Privacy Policy') && str_contains($html, 'Terms of Service'));

echo "\n--- 3. Testing Authentication Handlers ---\n";

// Test 3.1: Invalid CSRF
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST = [
    'csrf_token' => 'invalid_tampered_token',
    'email' => 'member@veritopinion.com',
    'password' => 'Member@12345'
];
ob_start();
include __DIR__ . '/../auth/login.php';
$html_csrf = ob_get_clean();
assert_test("Invalid CSRF rejected with security session expired", str_contains($html_csrf, 'Security session expired'));

// Test 3.2: Empty Fields with valid CSRF
$valid_token = generate_csrf_token();
$_POST = [
    'csrf_token' => $valid_token,
    'email' => '',
    'password' => ''
];
ob_start();
include __DIR__ . '/../auth/login.php';
$html_empty = ob_get_clean();
assert_test("Empty fields rejected with validation message", str_contains($html_empty, 'Please enter both your email address and password'));

// Test 3.3: Invalid Password
$valid_token = generate_csrf_token();
$_POST = [
    'csrf_token' => $valid_token,
    'email' => 'member@veritopinion.com',
    'password' => 'WrongPassword!999'
];
ob_start();
include __DIR__ . '/../auth/login.php';
$html_invalid = ob_get_clean();
assert_test("Invalid password rejected safely", str_contains($html_invalid, 'Invalid email address or password'));

// Test 3.4: Valid Member Authentication
$valid_token = generate_csrf_token();
$_POST = [
    'csrf_token' => $valid_token,
    'email' => 'member@veritopinion.com',
    'password' => 'Member@12345'
];

// Seed member if needed
$db = get_db();
$check = $db->prepare("SELECT id FROM users WHERE email = :email");
$check->execute([':email' => 'member@veritopinion.com']);
if (!$check->fetch()) {
    $db->prepare("INSERT INTO users (first_name, last_name, email, password_hash, role, status, balance_points, created_at, updated_at) VALUES ('Alex', 'Mercer', 'member@veritopinion.com', :hash, 'member', 'active', 500, datetime('now'), datetime('now'))")
       ->execute([':hash' => password_hash('Member@12345', PASSWORD_DEFAULT)]);
}

$tmp_sub = sys_get_temp_dir() . '/test_login_sub.php';
$repo_dir = str_replace('\\', '/', dirname(__DIR__));
$login_script = "
require_once '{$repo_dir}/config/app.php';
require_once '{$repo_dir}/config/database.php';
require_once '{$repo_dir}/includes/functions.php';
require_once '{$repo_dir}/includes/csrf.php';
require_once '{$repo_dir}/includes/auth_check.php';

register_shutdown_function(function() {
    if (isset(\$_SESSION['user_id'])) {
        echo 'AUTH_SUCCESS_USER_ID:' . \$_SESSION['user_id'];
    }
});

\$_SERVER['REQUEST_METHOD'] = 'POST';
\$_POST['csrf_token'] = generate_csrf_token();
\$_POST['email'] = 'member@veritopinion.com';
\$_POST['password'] = 'Member@12345';

include '{$repo_dir}/auth/login.php';
";

file_put_contents($tmp_sub, "<?php\n" . $login_script);
$out = shell_exec("C:\\xampp\\php\\php.exe -d extension=gd \"{$tmp_sub}\" 2>&1");
@unlink($tmp_sub);

assert_test("Valid member authenticates and initializes session", str_contains((string)$out, 'AUTH_SUCCESS_USER_ID:'));

echo "\n============================================================\n";
echo "REDESIGNED LOGIN RESULTS: $passCount Passed, $failCount Failed\n";
echo "============================================================\n";

if ($failCount > 0) exit(1);
