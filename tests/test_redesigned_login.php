<?php
/**
 * Test Suite for Clean & Simple Veritolytics Login Page
 */

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/auth_check.php';

echo "============================================================\n";
echo "   VERITOLYTICS SIMPLE LOGIN SUITE VERIFICATION\n";
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

// 1. TEST GET RENDERING & CLEAN CENTERED LAYOUT
echo "--- 1. Testing GET Request & Clean Centered Layout Structure ---\n";
$_SERVER['REQUEST_METHOD'] = 'GET';
ob_start();
include __DIR__ . '/../auth/login.php';
$html = ob_get_clean();

assert_test("HTML contains auth-centered-wrapper", str_contains($html, 'auth-centered-wrapper'));
assert_test("HTML contains auth-card", str_contains($html, 'auth-card'));
assert_test("HTML does NOT contain split-screen layout (auth-split-layout)", !str_contains($html, 'auth-split-layout'));
assert_test("HTML does NOT contain hero section (auth-hero-section)", !str_contains($html, 'auth-hero-section'));
assert_test("HTML does NOT reference auth-hero-bg.jpg", !str_contains($html, 'auth-hero-bg.jpg'));
assert_test("HTML does NOT contain glassmorphism card (auth-glass-card)", !str_contains($html, 'auth-glass-card'));
assert_test("HTML does NOT contain promotional statistics (5,000+, Paid Daily, ISO 20252)", !str_contains($html, '5,000+') && !str_contains($html, 'Paid Daily') && !str_contains($html, 'ISO 20252'));
assert_test("HTML does NOT contain 'SECURE & VERIFIED PAYOUTS'", !str_contains($html, 'SECURE & VERIFIED PAYOUTS') && !str_contains($html, 'Secure &amp; Verified Payouts'));
assert_test("HTML does NOT contain payment badges (Visa, Mastercard, Amazon, PayPal)", !str_contains($html, 'auth-payment-badges') && !str_contains($html, 'Visa') && !str_contains($html, 'Mastercard') && !str_contains($html, 'amazon') && !str_contains($html, 'PayPal'));
assert_test("HTML does NOT contain 'OpinionPays'", !str_contains($html, 'OpinionPays'));
assert_test("HTML does NOT contain 'Insightolytics LLC'", !str_contains($html, 'Insightolytics'));

echo "\n--- 2. Testing Veritolytics Logo & Form Elements ---\n";
assert_test("Contains Veritolytics logo image at top", str_contains($html, 'veritolytics-logo.png'));
assert_test("Contains heading 'Welcome Back'", str_contains($html, 'Welcome Back'));
assert_test("Contains subtitle 'Please enter your details to sign in.'", str_contains($html, 'Please enter your details to sign in.'));
assert_test("Contains Email Address field with placeholder", str_contains($html, 'placeholder="Enter your email address"'));
assert_test("Contains Password field with toggle button", str_contains($html, 'btn-toggle-password'));
assert_test("Contains 'Forgot Password?' link pointing to auth/forgot-password.php", str_contains($html, 'Forgot Password?') && str_contains($html, 'forgot-password.php'));
assert_test("Contains primary 'Sign In' button", str_contains($html, 'Sign In'));
assert_test("Contains 'Don\'t have an account? Sign Up' pointing to auth/register.php", str_contains($html, 'Sign Up') && str_contains($html, 'register.php'));
assert_test("Contains footer copyright '© 2026 Veritolytics. All Rights Reserved.'", str_contains($html, '© 2026 Veritolytics. All Rights Reserved.'));

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
echo "SIMPLE LOGIN RESULTS: $passCount Passed, $failCount Failed\n";
echo "============================================================\n";

if ($failCount > 0) exit(1);
