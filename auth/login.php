<?php
/**
 * Veritolytics - Member Login Portal
 * Pure Core PHP 8.2+ Architecture
 */

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/auth_check.php';

require_guest();

$pageTitle = 'Member Sign In | ' . APP_NAME;
$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Security session expired. Please refresh and try again.';
    } else {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($email) || empty($password)) {
            $error = 'Please enter both your email address and password.';
        } else {
            $db = get_db();
            $stmt = $db->prepare("SELECT id, first_name, last_name, email, password_hash, role, status FROM users WHERE email = :email");
            $stmt->execute([':email' => $email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password_hash'])) {
                if ($user['status'] === 'suspended') {
                    $error = 'Your account has been suspended. Please contact support@veritolytics.com.';
                } else {
                    // Regenerate session ID to prevent fixation
                    session_regenerate_id(true);
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_role'] = $user['role'];

                    flash('success', "Welcome back, {$user['first_name']}!");
                    
                    $destination = $_SESSION['return_to'] ?? ($user['role'] === 'admin' ? '/admin/dashboard.php' : '/member/dashboard.php');
                    unset($_SESSION['return_to']);
                    redirect($destination);
                }
            } else {
                $error = 'Invalid email address or password. Please check your credentials.';
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
                    <div class="d-inline-flex p-3 rounded-circle bg-primary bg-opacity-10 text-primary mb-3">
                        <i class="bi bi-person-circle fs-2"></i>
                    </div>
                    <h3 class="fw-bold text-dark">Welcome Back</h3>
                    <p class="text-muted small">Sign in to your panel dashboard to access active surveys and earnings.</p>
                </div>

                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger rounded-3 small d-flex align-items-center mb-4 shadow-sm" role="alert">
                        <i class="bi bi-exclamation-triangle-fill me-2 fs-5"></i>
                        <div><?= e($error) ?></div>
                    </div>
                <?php endif; ?>

                <!-- Demo Account Helper Card -->
                <div class="alert alert-info border-info-subtle bg-info-subtle rounded-3 p-3 mb-4 small">
                    <div class="d-flex align-items-center gap-1.5 fw-bold text-info-emphasis mb-1">
                        <i class="bi bi-info-circle-fill"></i> Pre-Configured Demo Credentials:
                    </div>
                    <div class="d-flex justify-content-between align-items-center mt-1">
                        <span><strong>Member:</strong> <code>member@veritolytics.com</code></span>
                        <span><code>Member@12345</code></span>
                    </div>
                </div>

                <form method="POST" action="<?= BASE_URL ?>/auth/login.php" class="space-y-3">
                    <?= csrf_field() ?>

                    <div class="mb-3">
                        <label class="form-label fw-semibold small text-muted">Email Address</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-envelope"></i></span>
                            <input type="email" name="email" class="form-control border-start-0 ps-0" placeholder="name@example.com" value="<?= e($email) ?>" required autofocus>
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="form-label fw-semibold small text-muted mb-0">Password</label>
                            <a href="<?= BASE_URL ?>/auth/forgot-password.php" class="text-primary text-decoration-none small fw-semibold">Forgot?</a>
                        </div>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-lock"></i></span>
                            <input type="password" id="loginPassword" name="password" class="form-control border-start-0 border-end-0 ps-0" placeholder="••••••••" required>
                            <button type="button" class="btn btn-light border border-start-0 text-muted btn-toggle-password" data-target="loginPassword">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div class="d-flex align-items-center justify-content-between mb-4">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="rememberMe" name="remember_me">
                            <label class="form-check-label small text-muted" for="rememberMe">Remember me</label>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-brand-primary w-100 py-2.5 fw-bold shadow-sm">
                        Sign In to Portal
                    </button>
                </form>

                <div class="text-center mt-4 pt-3 border-top small text-muted">
                    Don't have an account yet? 
                    <a href="<?= BASE_URL ?>/auth/register.php" class="text-primary fw-bold text-decoration-none ms-1">Create Account (+500 pts)</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
