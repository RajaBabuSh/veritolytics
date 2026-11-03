<?php
/**
 * Veritolytics - Premium Split-Screen Member Login Portal
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
    <link rel="icon" type="image/png" href="<?= BASE_URL ?>/assets/images/veritopinion-icon.png">

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
        :root {
            --auth-primary: #0066f0;
            --auth-primary-hover: #0052cc;
            --auth-navy-deep: #07192f;
            --auth-border: #e2e8f0;
            --auth-text-dark: #0f172a;
            --auth-text-muted: #64748b;
        }

        body, html {
            height: 100%;
            margin: 0;
            padding: 0;
            font-family: 'Plus Jakarta Sans', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background-color: #ffffff;
            color: var(--auth-text-dark);
            -webkit-font-smoothing: antialiased;
        }

        .auth-split-layout {
            display: flex;
            min-height: 100vh;
            width: 100%;
            overflow-x: hidden;
        }

        /* 1. LEFT HERO SECTION (69% on Desktop) */
        .auth-hero-section {
            flex: 0 0 69%;
            max-width: 69%;
            min-height: 100vh;
            position: relative;
            background-image: 
                linear-gradient(135deg, rgba(7, 25, 47, 0.82) 0%, rgba(0, 99, 236, 0.68) 55%, rgba(6, 18, 38, 0.92) 100%),
                url('<?= BASE_URL ?>/assets/images/auth-hero-bg.jpg');
            background-size: cover;
            background-position: center center;
            background-repeat: no-repeat;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 48px 56px;
            box-sizing: border-box;
            overflow: hidden;
        }

        /* Ambient glowing background orb */
        .auth-hero-section::before {
            content: '';
            position: absolute;
            top: -10%;
            left: -10%;
            width: 500px;
            height: 500px;
            background: radial-gradient(circle, rgba(0, 210, 255, 0.25) 0%, rgba(0, 102, 240, 0) 70%);
            border-radius: 50%;
            pointer-events: none;
            z-index: 1;
        }

        /* Top Brand Indicator in Hero */
        .auth-hero-top {
            position: relative;
            z-index: 2;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .auth-hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(255, 255, 255, 0.14);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.25);
            padding: 8px 16px;
            border-radius: 9999px;
            color: #ffffff;
            font-size: 0.8rem;
            font-weight: 600;
            letter-spacing: 0.3px;
        }

        .auth-hero-badge i {
            color: #38bdf8;
            font-size: 0.85rem;
        }

        /* Glassmorphism Information Card (Bottom-Left) */
        .auth-glass-card {
            position: relative;
            z-index: 2;
            background: rgba(255, 255, 255, 0.12);
            backdrop-filter: blur(20px) saturate(180%);
            -webkit-backdrop-filter: blur(20px) saturate(180%);
            border: 1px solid rgba(255, 255, 255, 0.22);
            border-radius: 24px;
            padding: 36px 40px;
            box-shadow: 0 24px 60px rgba(0, 10, 30, 0.35);
            max-width: 660px;
            margin-top: auto;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .auth-glass-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 30px 70px rgba(0, 10, 30, 0.42);
        }

        .auth-card-title {
            color: #ffffff;
            font-size: 1.68rem;
            font-weight: 800;
            line-height: 1.3;
            letter-spacing: -0.4px;
            margin-bottom: 12px;
        }

        .auth-card-subtitle {
            color: rgba(255, 255, 255, 0.88);
            font-size: 0.96rem;
            line-height: 1.55;
            margin-bottom: 24px;
            font-weight: 400;
        }

        .auth-benefits-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
            margin-bottom: 28px;
        }

        .auth-benefit-item {
            display: flex;
            align-items: center;
            gap: 12px;
            color: #ffffff;
            font-size: 0.93rem;
            font-weight: 500;
        }

        .auth-check-icon {
            width: 22px;
            height: 22px;
            border-radius: 50%;
            background: rgba(56, 189, 248, 0.2);
            border: 1px solid rgba(56, 189, 248, 0.45);
            color: #38bdf8;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            flex-shrink: 0;
        }

        .auth-card-divider {
            height: 1px;
            background: rgba(255, 255, 255, 0.16);
            margin-bottom: 24px;
        }

        .auth-stat-value {
            color: #ffffff;
            font-size: 1.45rem;
            font-weight: 800;
            line-height: 1;
            letter-spacing: -0.5px;
        }

        .auth-stat-label {
            color: rgba(255, 255, 255, 0.72);
            font-size: 0.68rem;
            font-weight: 700;
            letter-spacing: 1.2px;
            text-transform: uppercase;
            margin-top: 6px;
        }

        /* 2. RIGHT LOGIN SECTION (31% on Desktop) */
        .auth-form-section {
            flex: 0 0 31%;
            max-width: 31%;
            min-height: 100vh;
            background-color: #ffffff;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 44px 42px;
            box-sizing: border-box;
            overflow-y: auto;
        }

        .auth-form-wrapper {
            width: 100%;
            max-width: 360px;
            margin: auto 0;
            padding: 16px 0;
        }

        .auth-brand-logo {
            height: 38px;
            width: auto;
            object-fit: contain;
            display: block;
            margin-bottom: 28px;
            transition: opacity 0.2s ease;
        }

        .auth-brand-logo:hover {
            opacity: 0.92;
        }

        .auth-title {
            font-size: 1.8rem;
            font-weight: 800;
            letter-spacing: -0.6px;
            color: var(--auth-text-dark);
            margin-bottom: 6px;
        }

        .auth-subtitle {
            font-size: 0.925rem;
            color: var(--auth-text-muted);
            margin-bottom: 24px;
            line-height: 1.5;
        }

        /* Form Inputs */
        .auth-label {
            font-size: 0.84rem;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 6px;
            display: block;
        }

        .auth-input-group {
            position: relative;
            margin-bottom: 18px;
        }

        .auth-form-control {
            height: 48px;
            width: 100%;
            border-radius: 10px;
            border: 1.5px solid var(--auth-border);
            background-color: #f8fafc;
            padding: 0 16px;
            font-size: 0.95rem;
            color: var(--auth-text-dark);
            font-family: inherit;
            box-sizing: border-box;
            transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .auth-form-control:focus {
            outline: none;
            background-color: #ffffff;
            border-color: var(--auth-primary);
            box-shadow: 0 0 0 4px rgba(0, 102, 240, 0.12);
        }

        .auth-form-control::placeholder {
            color: #94a3b8;
            font-size: 0.9rem;
        }

        .auth-toggle-btn {
            position: absolute;
            top: 50%;
            right: 12px;
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
            color: var(--auth-primary);
        }

        .auth-forgot-link {
            font-size: 0.82rem;
            font-weight: 600;
            color: var(--auth-primary);
            text-decoration: none;
            transition: color 0.15s ease;
        }

        .auth-forgot-link:hover {
            color: var(--auth-primary-hover);
            text-decoration: underline;
        }

        /* Checkbox */
        .auth-checkbox-wrapper {
            display: flex;
            align-items: center;
            margin-bottom: 22px;
        }

        .auth-checkbox {
            width: 17px;
            height: 17px;
            border-radius: 4px;
            border: 1.5px solid #cbd5e1;
            margin-right: 8px;
            cursor: pointer;
            accent-color: var(--auth-primary);
        }

        .auth-checkbox-label {
            font-size: 0.85rem;
            color: var(--auth-text-muted);
            cursor: pointer;
            user-select: none;
        }

        /* Submit Button */
        .auth-submit-btn {
            width: 100%;
            height: 48px;
            background: linear-gradient(135deg, var(--auth-primary) 0%, var(--auth-primary-hover) 100%);
            border: none;
            border-radius: 10px;
            color: #ffffff;
            font-weight: 700;
            font-size: 0.96rem;
            letter-spacing: 0.2px;
            cursor: pointer;
            box-shadow: 0 4px 14px rgba(0, 102, 240, 0.32);
            transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .auth-submit-btn:hover {
            transform: translateY(-1.5px);
            box-shadow: 0 6px 20px rgba(0, 102, 240, 0.42);
            filter: brightness(1.04);
        }

        .auth-submit-btn:active {
            transform: translateY(0);
            box-shadow: 0 2px 8px rgba(0, 102, 240, 0.32);
        }

        /* Sign Up Callout */
        .auth-signup-text {
            text-align: center;
            margin-top: 18px;
            font-size: 0.88rem;
            color: var(--auth-text-muted);
        }

        .auth-signup-link {
            color: var(--auth-primary);
            font-weight: 700;
            text-decoration: none;
            margin-left: 4px;
            transition: color 0.15s ease;
        }

        .auth-signup-link:hover {
            color: var(--auth-primary-hover);
            text-decoration: underline;
        }

        /* Pre-Configured Demo Credentials Pill */
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

        /* 3. RIGHT FOOTER & PAYMENT METHOD BADGES */
        .auth-right-footer {
            margin-top: 24px;
            padding-top: 20px;
            border-top: 1px solid #f1f5f9;
        }

        .auth-legal-text {
            font-size: 0.76rem;
            color: #94a3b8;
            text-align: center;
            line-height: 1.5;
        }

        /* RESPONSIVE BREAKPOINTS */
        @media (max-width: 1100px) {
            .auth-hero-section {
                flex: 0 0 54%;
                max-width: 54%;
                padding: 36px 36px;
            }
            .auth-form-section {
                flex: 0 0 46%;
                max-width: 46%;
                padding: 36px 32px;
            }
            .auth-glass-card {
                padding: 28px 30px;
            }
            .auth-card-title {
                font-size: 1.4rem;
            }
        }

        @media (max-width: 768px) {
            .auth-split-layout {
                flex-direction: column;
                height: auto;
                min-height: 100vh;
            }
            .auth-hero-section {
                flex: 0 0 auto;
                width: 100%;
                max-width: 100%;
                min-height: auto;
                padding: 32px 24px 28px;
            }
            .auth-glass-card {
                position: static;
                margin-top: 24px;
                padding: 24px;
                border-radius: 18px;
            }
            .auth-card-title {
                font-size: 1.3rem;
            }
            .auth-form-section {
                flex: 1 0 auto;
                width: 100%;
                max-width: 100%;
                min-height: auto;
                padding: 36px 24px 28px;
            }
            .auth-form-wrapper {
                max-width: 100%;
                padding: 0;
            }
        }
    </style>
</head>
<body>

<div class="auth-split-layout">
    <!-- 1. LEFT HERO SECTION (69% Desktop) -->
    <section class="auth-hero-section">
        <!-- Top Corporate Credential Badge -->
        <div class="auth-hero-top">
            <div class="auth-hero-badge">
                <i class="bi bi-shield-check"></i>
                <span>Enterprise Research Panel · Global Insights Network</span>
            </div>
        </div>

        <!-- Glassmorphism Information Card (Bottom-Left) -->
        <div class="auth-glass-card">
            <h2 class="auth-card-title">Empowering Your Voice, Rewarding Your Time.</h2>
            <p class="auth-card-subtitle">
                Join thousands of professionals worldwide who share their insights to shape tomorrow's innovations.
            </p>

            <!-- Three Benefit Points -->
            <div class="auth-benefits-list">
                <div class="auth-benefit-item">
                    <span class="auth-check-icon"><i class="bi bi-check2"></i></span>
                    <span>Instant Payouts via PayPal &amp; Gift Cards</span>
                </div>
                <div class="auth-benefit-item">
                    <span class="auth-check-icon"><i class="bi bi-check2"></i></span>
                    <span>Bank-Level Data Encryption &amp; Privacy</span>
                </div>
                <div class="auth-benefit-item">
                    <span class="auth-check-icon"><i class="bi bi-check2"></i></span>
                    <span>Exclusive Global B2B Research Studies</span>
                </div>
            </div>

            <!-- Divider -->
            <div class="auth-card-divider"></div>

            <!-- Three Key Statistics -->
            <div class="row g-2 g-sm-3 text-start">
                <div class="col-4">
                    <div class="auth-stat-value">5,000+</div>
                    <div class="auth-stat-label">Early Members</div>
                </div>
                <div class="col-4">
                    <div class="auth-stat-value">$250+</div>
                    <div class="auth-stat-label">Paid Daily</div>
                </div>
                <div class="col-4">
                    <div class="auth-stat-value">ISO 20252</div>
                    <div class="auth-stat-label">Ready</div>
                </div>
            </div>
        </div>
    </section>

    <!-- 2. RIGHT LOGIN SECTION (31% Desktop) -->
    <section class="auth-form-section">
        <div class="auth-form-wrapper">
            <!-- Brand Logo -->
            <a href="<?= BASE_URL ?>/" title="Veritolytics Home" class="d-inline-block text-decoration-none">
                <img src="<?= BASE_URL ?>/assets/images/veritolytics-logo.png" alt="Veritolytics" class="auth-brand-logo">
            </a>

            <!-- Heading & Subtitle -->
            <h1 class="auth-title">Welcome back</h1>
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

            <!-- Pre-Configured Demo Credentials -->
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

                <!-- Email Address -->
                <div class="auth-input-group">
                    <label class="auth-label" for="loginEmail">Email Address</label>
                    <input type="email" id="loginEmail" name="email" class="auth-form-control" placeholder="Enter your email address" value="<?= e($email) ?>" required autofocus autocomplete="email">
                </div>

                <!-- Password with Show/Hide Toggle & Forgot Link -->
                <div class="auth-input-group">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <label class="auth-label mb-0" for="loginPassword">Password</label>
                        <a href="<?= BASE_URL ?>/auth/forgot-password.php" class="auth-forgot-link">Forgot password?</a>
                    </div>
                    <div class="position-relative">
                        <input type="password" id="loginPassword" name="password" class="auth-form-control pe-5" placeholder="••••••••" required autocomplete="current-password">
                        <button type="button" class="auth-toggle-btn btn-toggle-password" data-target="loginPassword" aria-label="Toggle password visibility">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                </div>

                <!-- Remember Me -->
                <div class="auth-checkbox-wrapper">
                    <input type="checkbox" id="rememberMe" name="remember_me" class="auth-checkbox">
                    <label for="rememberMe" class="auth-checkbox-label">Remember for 30 days</label>
                </div>

                <!-- Primary Submit Button -->
                <button type="submit" class="auth-submit-btn">
                    <span>Sign in</span>
                    <i class="bi bi-arrow-right"></i>
                </button>
            </form>

            <!-- Sign Up Callout -->
            <div class="auth-signup-text">
                Don't have an account? 
                <a href="<?= BASE_URL ?>/auth/register.php" class="auth-signup-link">Sign up</a>
            </div>
        </div>

        <!-- 3. FOOTER: COPYRIGHT -->
        <footer class="auth-right-footer">
            <div class="auth-legal-text">
                <div>© 2026 Veritolytics. All Rights Reserved.</div>
            </div>
        </footer>
    </section>
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
