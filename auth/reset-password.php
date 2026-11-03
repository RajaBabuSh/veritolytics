<?php
/**
 * Veritolytics - Password Reset Form
 * Pure Core PHP 8.2+ Architecture
 */

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/auth_check.php';

require_guest();

$token = trim($_GET['token'] ?? $_POST['token'] ?? '');
$pageTitle = 'Set New Password | ' . APP_NAME;
$error = '';
$user = null;

if (empty($token)) {
    flash('danger', 'Password reset token is missing or malformed.');
    redirect('/auth/login.php');
}

$db = get_db();
$stmt = $db->prepare("
    SELECT id, first_name, email, reset_expires_at 
    FROM users 
    WHERE reset_token = :token
");
$stmt->execute([':token' => $token]);
$user = $stmt->fetch();

if (!$user || (strtotime($user['reset_expires_at']) < time())) {
    flash('danger', 'This password reset token has expired or is invalid. Please request a new one.');
    redirect('/auth/forgot-password.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Security session expired. Please refresh and try again.';
    } else {
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if (strlen($newPassword) < 8) {
            $error = 'New password must be at least 8 characters long.';
        } elseif ($newPassword !== $confirmPassword) {
            $error = 'The passwords entered do not match.';
        } else {
            $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
            $upStmt = $db->prepare("
                UPDATE users 
                SET password_hash = :hash, reset_token = NULL, reset_expires_at = NULL 
                WHERE id = :id
            ");
            $upStmt->execute([
                ':hash' => $newHash,
                ':id' => $user['id']
            ]);

            // Add security notification
            add_notification(
                (int)$user['id'],
                'Password Successfully Changed',
                'Your account password was updated. If you did not make this change, contact support immediately.',
                'warning',
                '/member/settings.php'
            );

            flash('success', 'Your password has been reset successfully. Please sign in with your new password.');
            redirect('/auth/login.php');
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
                    <div class="d-inline-flex p-3 rounded-circle bg-success bg-opacity-10 text-success mb-3">
                        <i class="bi bi-shield-lock-fill fs-2"></i>
                    </div>
                    <h3 class="fw-bold text-dark">Create New Password</h3>
                    <p class="text-muted small">Hello <?= e($user['first_name']) ?>, enter your secure new password below.</p>
                </div>

                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger rounded-3 small d-flex align-items-center mb-4 shadow-sm" role="alert">
                        <i class="bi bi-exclamation-triangle-fill me-2 fs-5"></i>
                        <div><?= e($error) ?></div>
                    </div>
                <?php endif; ?>

                <form method="POST" action="<?= BASE_URL ?>/auth/reset-password.php">
                    <?= csrf_field() ?>
                    <input type="hidden" name="token" value="<?= e($token) ?>">

                    <div class="mb-3">
                        <label class="form-label fw-semibold small text-muted">New Password</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-lock"></i></span>
                            <input type="password" id="resetNewPassword" name="new_password" class="form-control border-start-0 border-end-0 ps-0" placeholder="Minimum 8 characters" required autofocus>
                            <button type="button" class="btn btn-light border border-start-0 text-muted btn-toggle-password" data-target="resetNewPassword">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold small text-muted">Confirm New Password</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-lock"></i></span>
                            <input type="password" id="resetConfirmPassword" name="confirm_password" class="form-control border-start-0 border-end-0 ps-0" placeholder="Repeat new password" required>
                            <button type="button" class="btn btn-light border border-start-0 text-muted btn-toggle-password" data-target="resetConfirmPassword">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-brand-primary w-100 py-2.5 fw-bold shadow-sm">
                        Update Password
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
