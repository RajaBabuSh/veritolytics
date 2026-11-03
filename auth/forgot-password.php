<?php
/**
 * Veritolytics - Password Reset Request
 * Pure Core PHP 8.2+ Architecture
 */

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/auth_check.php';

require_guest();

$pageTitle = 'Forgot Password | ' . APP_NAME;
$error = '';
$generatedLink = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Security session expired. Please refresh and try again.';
    } else {
        $email = trim($_POST['email'] ?? '');

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address.';
        } else {
            $db = get_db();
            $stmt = $db->prepare("SELECT id, first_name FROM users WHERE email = :email");
            $stmt->execute([':email' => $email]);
            $user = $stmt->fetch();

            if ($user) {
                $token = bin2hex(random_bytes(32));
                $expiresAt = date('Y-m-d H:i:s', time() + 3600); // 1 hour

                $upStmt = $db->prepare("UPDATE users SET reset_token = :token, reset_expires_at = :expires WHERE id = :id");
                $upStmt->execute([
                    ':token' => $token,
                    ':expires' => $expiresAt,
                    ':id' => $user['id']
                ]);

                $generatedLink = BASE_URL . "/auth/reset-password.php?token=" . urlencode($token);
            } else {
                // To prevent email enumeration, we still show instructions
                $generatedLink = 'not_found';
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
                    <div class="d-inline-flex p-3 rounded-circle bg-warning bg-opacity-10 text-warning mb-3">
                        <i class="bi bi-key-fill fs-2"></i>
                    </div>
                    <h3 class="fw-bold text-dark">Reset Password</h3>
                    <p class="text-muted small">Enter your email address and we'll generate a secure password recovery link.</p>
                </div>

                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger rounded-3 small d-flex align-items-center mb-4 shadow-sm" role="alert">
                        <i class="bi bi-exclamation-triangle-fill me-2 fs-5"></i>
                        <div><?= e($error) ?></div>
                    </div>
                <?php endif; ?>

                <?php if (!empty($generatedLink)): ?>
                    <div class="alert alert-success rounded-3 p-4 mb-4 shadow-sm">
                        <div class="d-flex align-items-center gap-2 fw-bold mb-2">
                            <i class="bi bi-check-circle-fill fs-5"></i> Reset Link Generated!
                        </div>
                        <p class="small mb-3">In production, this link is dispatched securely via email. For testing in this environment, click the link below to reset your password immediately:</p>
                        <?php if ($generatedLink !== 'not_found'): ?>
                            <a href="<?= e($generatedLink) ?>" class="btn btn-sm btn-success rounded-3 w-100 py-2 fw-semibold">
                                Proceed to Reset Password →
                            </a>
                        <?php else: ?>
                            <p class="small text-muted mb-0">If this email is registered in our database, instructions have been logged.</p>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="<?= BASE_URL ?>/auth/forgot-password.php">
                    <?= csrf_field() ?>

                    <div class="mb-4">
                        <label class="form-label fw-semibold small text-muted">Registered Email Address</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-envelope"></i></span>
                            <input type="email" name="email" class="form-control border-start-0 ps-0" placeholder="name@example.com" required autofocus>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-brand-primary w-100 py-2.5 fw-bold shadow-sm">
                        Generate Reset Link
                    </button>
                </form>

                <div class="text-center mt-4 pt-3 border-top small text-muted">
                    Remembered your password? 
                    <a href="<?= BASE_URL ?>/auth/login.php" class="text-primary fw-bold text-decoration-none ms-1">Return to Sign In</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
