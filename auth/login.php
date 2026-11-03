<?php
/**
 * Veritolytics - Simple & Clean Member Login Portal
 * Pure Core PHP 8.2+ Architecture
 */

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/auth_check.php';

require_guest();

$pageTitle = 'Sign In | Veritolytics';
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
            $stmt = $db->prepare("SELECT id, first_name, last_name, email, password_hash, role, status, deleted_at FROM users WHERE email = :email");
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
                if (!empty($user['deleted_at'])) {
                    $error = 'This account has been deactivated. Please contact support@veritopinion.com.';
                } elseif ($user['status'] === 'suspended') {
                    $error = 'Your account has been suspended. Please contact support@veritopinion.com.';
                } else {
                    set_authenticated_session((int)$user['id'], (string)$user['role'], (string)$user['password_hash']);

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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?></title>
    <meta name="description" content="Sign in to your Veritolytics panel account to access active research studies and earn cash rewards.">
    <link rel="icon" type="image/png" href="<?= BASE_URL ?>/assets/images/veritolytics-icon.png">

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Custom Brand Stylesheet -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">

    <style>
        body, html {
            min-height: 100%;
            margin: 0;
            padding: 0;
            font-family: 'Plus Jakarta Sans', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background-color: #ffffff;
            color: #0f172a;
            -webkit-font-smoothing: antialiased;
        }

        .auth-centered-wrapper {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 40px 20px;
            box-sizing: border-box;
            background-color: #ffffff;
        }

        .auth-card {
            width: 100%;
            max-width: 440px;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05);
            padding: 38px 34px;
            box-sizing: border-box;
        }

        .auth-logo {
            height: 42px;
            width: auto;
            object-fit: contain;
            display: block;
            margin: 0 auto 20px auto;
            transition: opacity 0.2s ease;
        }

        .auth-logo:hover {
            opacity: 0.9;
        }

        .auth-title {
            font-size: 1.65rem;
            font-weight: 800;
            letter-spacing: -0.5px;
            color: #0f172a;
            margin-bottom: 6px;
            text-align: center;
        }

        .auth-subtitle {
            font-size: 0.92rem;
            color: #64748b;
            margin-bottom: 24px;
            text-align: center;
            line-height: 1.45;
        }

        .auth-label {
            font-size: 0.84rem;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 6px;
            display: block;
        }

        .auth-form-control {
            height: 46px;
            width: 100%;
            border-radius: 10px;
            border: 1.5px solid #e2e8f0;
            background-color: #f8fafc;
            padding: 0 14px;
            font-size: 0.95rem;
            color: #0f172a;
            box-sizing: border-box;
            transition: all 0.2s ease;
        }

        .auth-form-control:focus {
            outline: none;
            background-color: #ffffff;
            border-color: #0066f0;
            box-shadow: 0 0 0 3px rgba(0, 102, 240, 0.12);
        }

        .auth-form-control::placeholder {
            color: #94a3b8;
            font-size: 0.9rem;
        }

        .auth-toggle-btn {
            position: absolute;
            top: 50%;
            right: 10px;
            transform: translateY(-50%);
            background: transparent;
            border: none;
            color: #94a3b8;
            padding: 6px 8px;
            font-size: 1rem;
            cursor: pointer;
            border-radius: 6px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: color 0.2s ease;
        }

        .auth-toggle-btn:hover {
            color: #0066f0;
        }

        .auth-forgot-link {
            font-size: 0.82rem;
            font-weight: 600;
            color: #0066f0;
            text-decoration: none;
            transition: color 0.15s ease;
        }

        .auth-forgot-link:hover {
            color: #0052cc;
            text-decoration: underline;
        }

        .auth-checkbox-wrapper {
            display: flex;
            align-items: center;
            margin: 16px 0 22px 0;
        }

        .auth-checkbox {
            width: 16px;
            height: 16px;
            border-radius: 4px;
            border: 1.5px solid #cbd5e1;
            margin-right: 8px;
            cursor: pointer;
            accent-color: #0066f0;
        }

        .auth-checkbox-label {
            font-size: 0.85rem;
            color: #64748b;
            cursor: pointer;
            user-select: none;
        }

        .auth-submit-btn {
            width: 100%;
            height: 46px;
            background-color: #0066f0;
            border: 1px solid #0066f0;
            border-radius: 10px;
            color: #ffffff;
            font-weight: 700;
            font-size: 0.96rem;
            letter-spacing: 0.2px;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(0, 102, 240, 0.24);
            transition: all 0.2s ease;
        }

        .auth-submit-btn:hover {
            background-color: #0052cc;
            border-color: #0052cc;
            box-shadow: 0 6px 16px rgba(0, 102, 240, 0.32);
            transform: translateY(-1px);
        }

        .auth-submit-btn:active {
            transform: translateY(0);
        }

        .auth-signup-text {
            text-align: center;
            margin-top: 20px;
            font-size: 0.88rem;
            color: #64748b;
        }

        .auth-signup-link {
            color: #0066f0;
            font-weight: 700;
            text-decoration: none;
            margin-left: 4px;
            transition: color 0.15s ease;
        }

        .auth-signup-link:hover {
            color: #0052cc;
            text-decoration: underline;
        }

        .auth-demo-hint {
            background-color: #f0f7ff;
            border: 1px solid #bae0fd;
            border-radius: 10px;
            padding: 10px 14px;
            margin-bottom: 20px;
            font-size: 0.8rem;
            color: #0369a1;
        }

        .auth-demo-hint code {
            background: rgba(3, 105, 161, 0.08);
            color: #0c4a6e;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 0.78rem;
            font-family: monospace;
            font-weight: 600;
        }

        .auth-footer {
            margin-top: 24px;
            text-align: center;
            font-size: 0.78rem;
            color: #94a3b8;
        }

        @media (max-width: 576px) {
            .auth-centered-wrapper {
                padding: 24px 16px;
                justify-content: flex-start;
            }
            .auth-card {
                border: none;
                box-shadow: none;
                padding: 16px 4px;
            }
        }
    </style>
</head>
<body>

<div class="auth-centered-wrapper">
    <div class="auth-card">
        <!-- Veritolytics Logo at the Top -->
        <a href="<?= BASE_URL ?>/" title="Veritolytics Home" class="text-decoration-none">
            <img src="<?= BASE_URL ?>/assets/images/veritolytics-logo.png" alt="Veritolytics" class="auth-logo">
        </a>

        <!-- Welcome Back Heading & Subtitle -->
        <h1 class="auth-title">Welcome Back</h1>
        <p class="auth-subtitle">Please enter your details to sign in.</p>

        <!-- Error Notification -->
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger rounded-3 small d-flex align-items-center mb-3.5 py-2.5 px-3 border-danger-subtle" role="alert">
                <i class="bi bi-exclamation-circle-fill me-2 fs-5 flex-shrink-0 text-danger"></i>
                <div><?= e($error) ?></div>
            </div>
        <?php endif; ?>

        <!-- Flash Notifications -->
        <?php foreach (get_flashes() as $flash): ?>
            <div class="alert alert-<?= $flash['type'] === 'error' ? 'danger' : e($flash['type']) ?> rounded-3 small d-flex align-items-center mb-3.5 py-2.5 px-3" role="alert">
                <i class="bi bi-info-circle-fill me-2 fs-5 flex-shrink-0"></i>
                <div><?= e($flash['message']) ?></div>
            </div>
        <?php endforeach; ?>

        <!-- Demo Account Hint -->
        <div class="auth-demo-hint d-flex align-items-center justify-content-between">
            <div>
                <i class="bi bi-key-fill me-1 text-primary"></i>
                <strong>Demo:</strong> <code>member@veritopinion.com</code>
            </div>
            <div>
                <code>Member@12345</code>
            </div>
        </div>

        <!-- Login Form -->
        <form method="POST" action="<?= BASE_URL ?>/auth/login.php" autocomplete="on">
            <?= csrf_field() ?>

            <!-- Email Address Field -->
            <div class="mb-3">
                <label class="auth-label" for="loginEmail">Email Address</label>
                <input type="email" id="loginEmail" name="email" class="auth-form-control" placeholder="Enter your email address" value="<?= e($email) ?>" required autofocus autocomplete="email">
            </div>

            <!-- Password Field with Show/Hide Toggle & Forgot Password Link -->
            <div class="mb-3">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <label class="auth-label mb-0" for="loginPassword">Password</label>
                    <a href="<?= BASE_URL ?>/auth/forgot-password.php" class="auth-forgot-link">Forgot Password?</a>
                </div>
                <div class="position-relative">
                    <input type="password" id="loginPassword" name="password" class="auth-form-control pe-5" placeholder="••••••••" required autocomplete="current-password">
                    <button type="button" class="auth-toggle-btn btn-toggle-password" data-target="loginPassword" aria-label="Toggle password visibility">
                        <i class="bi bi-eye"></i>
                    </button>
                </div>
            </div>

            <!-- Remember Me Checkbox -->
            <div class="auth-checkbox-wrapper">
                <input type="checkbox" id="rememberMe" name="remember_me" class="auth-checkbox">
                <label for="rememberMe" class="auth-checkbox-label">Remember me</label>
            </div>

            <!-- Blue Sign In Button -->
            <button type="submit" class="auth-submit-btn">
                Sign In
            </button>
        </form>

        <!-- Sign Up Link Below Button -->
        <div class="auth-signup-text">
            Don't have an account? 
            <a href="<?= BASE_URL ?>/auth/register.php" class="auth-signup-link">Sign Up</a>
        </div>
    </div>

    <!-- Minimal Copyright Footer -->
    <footer class="auth-footer">
        <div>© 2026 Veritolytics. All Rights Reserved.</div>
    </footer>
</div>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
<script src="<?= BASE_URL ?>/assets/js/main.js"></script>

<script>
    // Fail-safe inline password toggle handler
    document.querySelectorAll('.btn-toggle-password').forEach(function(button) {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const targetId = this.getAttribute('data-target');
            const input = document.getElementById(targetId);
            const icon = this.querySelector('i');
            if (!input) return;

            if (input.type === 'password') {
                input.type = 'text';
                if (icon) {
                    icon.classList.remove('bi-eye');
                    icon.classList.add('bi-eye-slash');
                }
            } else {
                input.type = 'password';
                if (icon) {
                    icon.classList.remove('bi-eye-slash');
                    icon.classList.add('bi-eye');
                }
            }
        });
    });
</script>

</body>
</html>
