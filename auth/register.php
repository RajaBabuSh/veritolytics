<?php
/**
 * Veritolytics - Member Registration Portal
 * Pure Core PHP 8.2+ Architecture
 */

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/auth_check.php';

require_guest();

$pageTitle = 'Join Veritolytics Panel | 500 Welcome Points';
$error = '';
$firstName = '';
$lastName = '';
$email = '';
$country = 'United States';

$countries = [
    'United States', 'United Kingdom', 'Canada', 'Australia', 'Germany', 
    'France', 'India', 'Japan', 'Brazil', 'Singapore', 'Other'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Security session expired. Please refresh and try again.';
    } else {
        $firstName = trim($_POST['first_name'] ?? '');
        $lastName = trim($_POST['last_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $country = trim($_POST['country'] ?? 'United States');
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        $agreeTerms = isset($_POST['agree_terms']);

        if (empty($firstName) || empty($lastName) || empty($email) || empty($password)) {
            $error = 'Please fill in all required fields.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please provide a valid email address.';
        } elseif (strlen($password) < 8) {
            $error = 'Password must be at least 8 characters long.';
        } elseif ($password !== $confirmPassword) {
            $error = 'The passwords you entered do not match.';
        } elseif (!$agreeTerms) {
            $error = 'You must agree to the Terms of Service and Privacy Policy to register.';
        } else {
            $db = get_db();
            // Check if email already exists
            $stmt = $db->prepare("SELECT id FROM users WHERE email = :email");
            $stmt->execute([':email' => $email]);
            if ($stmt->fetch()) {
                $error = 'An account with this email address already exists. Please sign in instead.';
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $welcomeBonus = WELCOME_BONUS_POINTS;
                $welcomeUsd = $welcomeBonus / POINTS_PER_USD;

                $insertStmt = $db->prepare("
                    INSERT INTO users (
                        first_name, last_name, email, password_hash, country, 
                        role, status, balance_points, lifetime_earnings_usd, profile_completion_pct
                    ) VALUES (
                        :first_name, :last_name, :email, :password_hash, :country,
                        'member', 'active', :balance_points, :lifetime_earnings_usd, 35
                    )
                ");

                $success = $insertStmt->execute([
                    ':first_name' => $firstName,
                    ':last_name' => $lastName,
                    ':email' => $email,
                    ':password_hash' => $hash,
                    ':country' => $country,
                    ':balance_points' => $welcomeBonus,
                    ':lifetime_earnings_usd' => $welcomeUsd,
                ]);

                if ($success) {
                    $newUserId = (int)$db->lastInsertId();

                    // Credit Welcome Bonus Transaction
                    add_transaction($newUserId, 'welcome_bonus', 'Welcome Bonus - Account Registered', $welcomeBonus, $welcomeUsd);

                    // Send Welcome Notification
                    add_notification(
                        $newUserId,
                        'Welcome to Veritolytics! 🎉',
                        'Your panel registration is complete. 500 Welcome Points ($5.00) have been deposited into your wallet!',
                        'success',
                        '/member/wallet.php'
                    );

                    // Auto-login
                    session_regenerate_id(true);
                    $_SESSION['user_id'] = $newUserId;
                    $_SESSION['user_role'] = 'member';

                    flash('success', "Welcome to Veritolytics, {$firstName}! 500 Welcome Points have been added to your wallet.");
                    redirect('/member/dashboard.php');
                } else {
                    $error = 'Unable to complete registration. Please try again.';
                }
            }
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-5 my-md-3">
    <div class="row justify-content-center">
        <div class="col-lg-6 col-md-8 col-sm-10">
            <div class="card card-vy border-0 shadow-lg p-4 p-md-5">
                <div class="text-center mb-4">
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-3 py-1 mb-2 fw-semibold">
                        Instant 500 Welcome Points
                    </span>
                    <h3 class="fw-bold text-dark">Create Your Member Account</h3>
                    <p class="text-muted small">Join verified panel respondents worldwide and earn real cash and rewards for your voice.</p>
                </div>

                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger rounded-3 small d-flex align-items-center mb-4 shadow-sm" role="alert">
                        <i class="bi bi-exclamation-triangle-fill me-2 fs-5"></i>
                        <div><?= e($error) ?></div>
                    </div>
                <?php endif; ?>

                <form method="POST" action="<?= BASE_URL ?>/auth/register.php">
                    <?= csrf_field() ?>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small text-muted">First Name</label>
                            <input type="text" name="first_name" class="form-control rounded-3" placeholder="Jane" value="<?= e($firstName) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small text-muted">Last Name</label>
                            <input type="text" name="last_name" class="form-control rounded-3" placeholder="Doe" value="<?= e($lastName) ?>" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold small text-muted">Email Address</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-envelope"></i></span>
                            <input type="email" name="email" class="form-control border-start-0 ps-0 rounded-end-3" placeholder="jane@example.com" value="<?= e($email) ?>" required>
                        </div>
                        <small class="text-muted" style="font-size: 0.75rem;">Used for survey invites and reward deliveries.</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold small text-muted">Country of Residence</label>
                        <select name="country" class="form-select rounded-3" required>
                            <?php foreach ($countries as $c): ?>
                                <option value="<?= e($c) ?>" <?= $country === $c ? 'selected' : '' ?>><?= e($c) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small text-muted">Create Password</label>
                            <div class="input-group">
                                <input type="password" id="regPassword" name="password" class="form-control border-end-0 rounded-start-3" placeholder="Min. 8 chars" required>
                                <button type="button" class="btn btn-light border border-start-0 text-muted btn-toggle-password rounded-end-3" data-target="regPassword">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small text-muted">Confirm Password</label>
                            <div class="input-group">
                                <input type="password" id="regConfirmPassword" name="confirm_password" class="form-control border-end-0 rounded-start-3" placeholder="Repeat password" required>
                                <button type="button" class="btn btn-light border border-start-0 text-muted btn-toggle-password rounded-end-3" data-target="regConfirmPassword">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="mb-4">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="agreeTerms" name="agree_terms" required>
                            <label class="form-check-label small text-muted" for="agreeTerms">
                                I agree to the <a href="<?= BASE_URL ?>/#faq" class="text-primary text-decoration-none">Terms of Service</a> and <a href="<?= BASE_URL ?>/#about" class="text-primary text-decoration-none">Privacy Policy</a>.
                            </label>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-brand-primary w-100 py-2.5 fw-bold shadow-sm">
                        Create Account & Claim 500 Pts
                    </button>
                </form>

                <div class="text-center mt-4 pt-3 border-top small text-muted">
                    Already have a Veritolytics account? 
                    <a href="<?= BASE_URL ?>/auth/login.php" class="text-primary fw-bold text-decoration-none ms-1">Sign In</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
