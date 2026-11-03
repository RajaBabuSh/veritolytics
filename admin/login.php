<?php
/**
 * Veritolytics - Dedicated Admin Authentication Portal
 * Pure Core PHP 8.2+ Architecture
 */

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/auth_check.php';

// If already admin, redirect to dashboard
if (is_logged_in() && is_admin()) {
    redirect('/admin/dashboard.php');
}

$pageTitle = 'Administrator Access Portal | ' . APP_NAME;
$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Security session expired. Please refresh and try again.';
    } else {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($email) || empty($password)) {
            $error = 'Please enter both your administrative email and password.';
        } else {
            $db = get_db();
            $stmt = $db->prepare("SELECT id, first_name, last_name, email, password_hash, role, status FROM users WHERE email = :email");
            $stmt->execute([':email' => $email]);
            $user = $stmt->fetch();

            // Seamless alias matching between @veritopinion.com and legacy @veritolytics.com
            if (!$user) {
                $altEmail = str_ends_with($email, '@veritolytics.com') 
                    ? str_replace('@veritolytics.com', '@veritopinion.com', $email)
                    : (str_ends_with($email, '@veritopinion.com') ? str_replace('@veritopinion.com', '@veritolytics.com', $email) : null);
                if ($altEmail) {
                    $stmt->execute([':email' => $altEmail]);
                    $user = $stmt->fetch();
                }
            }

            if ($user && password_verify($password, $user['password_hash'])) {
                if ($user['role'] !== 'admin') {
                    $error = 'Access restricted: This account does not possess administrator credentials. Please sign in via the Member Portal.';
                } elseif ($user['status'] === 'suspended') {
                    $error = 'This administrative account has been suspended.';
                } else {
                    set_authenticated_session((int)$user['id'], 'admin', (string)$user['password_hash']);

                    flash('success', "Welcome, Administrator {$user['first_name']}.");
                    redirect('/admin/dashboard.php');
                }
            } else {
                $error = 'Invalid administrator credentials. Access attempt logged.';
            }
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-5 my-md-4">
    <div class="row justify-content-center">
        <div class="col-lg-5 col-md-7 col-sm-10">
            <div class="card card-vy border-0 shadow-lg p-4 p-md-5">
                <div class="text-center mb-4">
                    <div class="d-inline-flex p-3 rounded-circle bg-danger bg-opacity-10 text-danger mb-3">
                        <i class="bi bi-shield-lock-fill fs-2"></i>
                    </div>
                    <h3 class="fw-bold text-dark">Staff & Admin Access</h3>
                    <p class="text-muted small">Restricted portal for research platform operators and panel moderators.</p>
                </div>

                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger rounded-3 small d-flex align-items-center mb-4 shadow-sm" role="alert">
                        <i class="bi bi-exclamation-triangle-fill me-2 fs-5"></i>
                        <div><?= e($error) ?></div>
                    </div>
                <?php endif; ?>

                <div class="alert alert-warning border-warning-subtle bg-warning-subtle rounded-3 p-3 mb-4 small">
                    <div class="d-flex align-items-center gap-1.5 fw-bold text-dark mb-1">
                        <i class="bi bi-key-fill text-warning"></i> Demo Administrator Account:
                    </div>
                    <div class="d-flex justify-content-between align-items-center mt-1">
                        <span><strong>Email:</strong> <code>admin@veritopinion.com</code></span>
                        <span><code>Admin@12345</code></span>
                    </div>
                </div>

                <form method="POST" action="<?= BASE_URL ?>/admin/login.php">
                    <?= csrf_field() ?>

                    <div class="mb-3">
                        <label class="form-label fw-semibold small text-muted">Admin Email</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-envelope"></i></span>
                            <input type="email" name="email" class="form-control border-start-0 ps-0" placeholder="admin@veritopinion.com" value="<?= e($email) ?>" required autofocus>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold small text-muted">Master Password</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-lock"></i></span>
                            <input type="password" id="adminPwd" name="password" class="form-control border-start-0 border-end-0 ps-0" placeholder="••••••••" required>
                            <button type="button" class="btn btn-light border border-start-0 text-muted btn-toggle-password" data-target="adminPwd">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-danger w-100 py-2.5 fw-bold shadow-sm">
                        Authenticate as Administrator
                    </button>
                </form>

                <div class="text-center mt-4 pt-3 border-top small text-muted">
                    Looking for the member dashboard? 
                    <a href="<?= BASE_URL ?>/auth/login.php" class="text-primary fw-bold text-decoration-none ms-1">Member Sign In</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
