<?php
/**
 * Veritolytics - Member Account Settings & Security (Complete Edition)
 * Pure Core PHP 8.2+ Architecture
 */

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/auth_check.php';

$user = require_login();
$pageTitle = 'Account Settings';
$db = get_db();

// Default or selected tab
$activeTab = $_GET['tab'] ?? 'personal';
if (!in_array($activeTab, ['personal', 'email', 'security', 'notifications', 'privacy'])) {
    $activeTab = 'personal';
}

$countries = [
    'United States', 'United Kingdom', 'Canada', 'Australia', 'Germany', 
    'France', 'India', 'Japan', 'Brazil', 'Singapore', 'Netherlands', 
    'Spain', 'Italy', 'Mexico', 'South Africa', 'Other'
];

// Handle JSON Data Export (Direct Download)
if (isset($_GET['action']) && $_GET['action'] === 'export_data') {
    if (!verify_csrf_token($_GET['csrf_token'] ?? '')) {
        flash('danger', 'Security validation token expired. Please try again.');
        redirect('/member/settings.php?tab=privacy');
    }

    // Fetch user surveys
    $surveysStmt = $db->prepare("
        SELECT s.title, s.category, sr.points_awarded, sr.usd_awarded, sr.completed_at
        FROM survey_responses sr
        JOIN surveys s ON sr.survey_id = s.id
        WHERE sr.user_id = :uid
        ORDER BY sr.completed_at DESC
    ");
    $surveysStmt->execute([':uid' => $user['id']]);
    $userSurveys = $surveysStmt->fetchAll();

    // Fetch user transactions
    $txStmt = $db->prepare("SELECT type, title, points, amount_usd, status, created_at FROM transactions WHERE user_id = :uid ORDER BY created_at DESC");
    $txStmt->execute([':uid' => $user['id']]);
    $userTx = $txStmt->fetchAll();

    $exportData = [
        'account_id' => 'VY-' . str_pad((string)$user['id'], 6, '0', STR_PAD_LEFT),
        'personal_information' => [
            'first_name' => $user['first_name'],
            'last_name' => $user['last_name'],
            'email' => $user['email'],
            'phone' => $user['phone'],
            'country' => $user['country'],
            'state' => $user['state'],
            'city' => $user['city'],
            'date_of_birth' => $user['date_of_birth'],
            'gender' => $user['gender'],
            'created_at' => $user['created_at'],
        ],
        'wallet_summary' => [
            'balance_points' => (int)$user['balance_points'],
            'balance_usd' => $user['balance_points'] / POINTS_PER_USD,
            'lifetime_earnings_usd' => (float)$user['lifetime_earnings_usd'],
            'completed_surveys_count' => (int)$user['completed_surveys_count']
        ],
        'completed_surveys' => $userSurveys,
        'ledger_transactions' => $userTx,
        'exported_at' => date('Y-m-d H:i:s T')
    ];

    header('Content-Type: application/json; charset=utf-8');
    header('Content-Disposition: attachment; filename="veritopinion_data_user_' . $user['id'] . '.json"');
    echo json_encode($exportData, JSON_PRETTY_PRINT);
    exit();
}

// Handle Form Submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        flash('danger', 'Security validation token expired. Please try again.');
        redirect('/member/settings.php?tab=' . $activeTab);
    }

    $action = $_POST['action'] ?? '';

    // =========================================================================
    // SECTION A: UPDATE PERSONAL INFORMATION
    // =========================================================================
    if ($action === 'update_personal') {
        $firstName = trim((string)($_POST['first_name'] ?? ''));
        $lastName  = trim((string)($_POST['last_name'] ?? ''));
        $phone     = trim((string)($_POST['phone'] ?? ''));
        $country   = trim((string)($_POST['country'] ?? ''));
        $state     = trim((string)($_POST['state'] ?? ''));
        $city      = trim((string)($_POST['city'] ?? ''));
        $dob       = trim((string)($_POST['date_of_birth'] ?? ''));

        if (empty($firstName) || empty($lastName)) {
            flash('danger', 'First Name and Last Name are required.');
            redirect('/member/settings.php?tab=personal');
        }

        // Handle Avatar Upload or Removal
        $currentAvatar = $user['avatar'] ?? null;
        if (!empty($_POST['remove_avatar'])) {
            if ($currentAvatar && file_exists(__DIR__ . '/../' . ltrim($currentAvatar, '/'))) {
                @unlink(__DIR__ . '/../' . ltrim($currentAvatar, '/'));
            }
            $currentAvatar = null;
        }

        if (isset($_FILES['avatar_file']) && $_FILES['avatar_file']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['avatar_file'];
            $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
            $fileInfo = @finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = $fileInfo ? finfo_file($fileInfo, $file['tmp_name']) : $file['type'];
            if ($fileInfo) finfo_close($fileInfo);

            if (in_array($mimeType, $allowedTypes) && $file['size'] <= 2 * 1024 * 1024) {
                $ext = match($mimeType) {
                    'image/jpeg' => 'jpg',
                    'image/png'  => 'png',
                    'image/webp' => 'webp',
                    default      => 'jpg'
                };
                $uploadDir = __DIR__ . '/../uploads/avatars';
                if (!is_dir($uploadDir)) @mkdir($uploadDir, 0755, true);
                $fileName = 'avatar_' . $user['id'] . '_' . time() . '.' . $ext;
                $destination = $uploadDir . '/' . $fileName;

                if (move_uploaded_file($file['tmp_name'], $destination)) {
                    if ($currentAvatar && file_exists(__DIR__ . '/../' . ltrim($currentAvatar, '/'))) {
                        @unlink(__DIR__ . '/../' . ltrim($currentAvatar, '/'));
                    }
                    $currentAvatar = 'uploads/avatars/' . $fileName;
                }
            }
        }

        $stmt = $db->prepare("
            UPDATE users SET
                first_name = :first_name,
                last_name = :last_name,
                phone = :phone,
                country = :country,
                state = :state,
                city = :city,
                date_of_birth = :dob,
                avatar = :avatar,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = :id
        ");
        $stmt->execute([
            ':first_name' => $firstName,
            ':last_name'  => $lastName,
            ':phone'      => $phone ?: null,
            ':country'    => $country ?: 'United States',
            ':state'      => $state ?: null,
            ':city'       => $city ?: null,
            ':dob'        => $dob ?: null,
            ':avatar'     => $currentAvatar,
            ':id'         => $user['id']
        ]);

        flash('success', 'Personal information updated successfully.');
        redirect('/member/settings.php?tab=personal');
    }

    // =========================================================================
    // SECTION B: EMAIL ADDRESS MANAGEMENT (SECURE WITH OTP VERIFICATION)
    // =========================================================================
    elseif ($action === 'request_email_change') {
        $newEmail = strtolower(trim((string)($_POST['new_email'] ?? '')));
        $accountPassword = (string)($_POST['confirm_account_password'] ?? '');

        // Fetch current password hash
        $pwdStmt = $db->prepare("SELECT password_hash FROM users WHERE id = :id");
        $pwdStmt->execute([':id' => $user['id']]);
        $currentHash = $pwdStmt->fetchColumn();

        if (empty($newEmail) || !filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
            flash('danger', 'Please enter a valid new email address.');
            redirect('/member/settings.php?tab=email');
        } elseif (empty($accountPassword) || !password_verify($accountPassword, $currentHash)) {
            flash('danger', 'Your account password was entered incorrectly. Password verification is required to change your registered email.');
            redirect('/member/settings.php?tab=email');
        } elseif ($newEmail === strtolower($user['email'])) {
            flash('warning', 'The new email address cannot be identical to your current email address.');
            redirect('/member/settings.php?tab=email');
        } else {
            // Check for duplicate registrations in users table
            $dupStmt = $db->prepare("SELECT id FROM users WHERE email = :email AND id != :id");
            $dupStmt->execute([':email' => $newEmail, ':id' => $user['id']]);
            if ($dupStmt->fetch()) {
                flash('danger', 'The email address "' . e($newEmail) . '" is already registered to another Veritopinion account.');
                redirect('/member/settings.php?tab=email');
            }

            // Generate secure 6-digit OTP code and expiration (15 minutes)
            $otp = (string)random_int(100000, 999999);
            $expiresAt = date('Y-m-d H:i:s', time() + 15 * 60);

            $saveStmt = $db->prepare("
                UPDATE users SET
                    pending_email = :pending_email,
                    email_verification_token = :token,
                    email_verification_expires_at = :expires,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = :id
            ");
            $saveStmt->execute([
                ':pending_email' => $newEmail,
                ':token' => $otp,
                ':expires' => $expiresAt,
                ':id' => $user['id']
            ]);

            // Flash instruction with test OTP displayed for zero-friction local verification
            flash('info', "A 6-digit verification code has been dispatched for {$newEmail}. For testing on your local server, your verification OTP is: <strong>{$otp}</strong> (valid for 15 minutes).");
            redirect('/member/settings.php?tab=email');
        }
    }

    // Verify Email OTP Submission
    elseif ($action === 'verify_email_otp') {
        $submittedOtp = trim((string)($_POST['verification_otp'] ?? ''));

        $checkStmt = $db->prepare("SELECT pending_email, email_verification_token, email_verification_expires_at FROM users WHERE id = :id");
        $checkStmt->execute([':id' => $user['id']]);
        $row = $checkStmt->fetch();

        $pendingEmail = $row['pending_email'] ?? '';
        $storedOtp    = $row['email_verification_token'] ?? '';
        $expiresAt    = $row['email_verification_expires_at'] ?? '';

        if (empty($pendingEmail) || empty($storedOtp)) {
            flash('warning', 'There is no pending email verification request. Please submit a new email change request.');
            redirect('/member/settings.php?tab=email');
        } elseif (empty($submittedOtp) || $submittedOtp !== $storedOtp) {
            flash('danger', 'The verification code entered is incorrect. Please check and try again.');
            redirect('/member/settings.php?tab=email');
        } elseif (strtotime($expiresAt) < time()) {
            flash('danger', 'The verification code has expired (valid for 15 minutes). Please request a new code.');
            redirect('/member/settings.php?tab=email');
        } else {
            // Re-check duplicate before final confirmation
            $dupStmt = $db->prepare("SELECT id FROM users WHERE email = :email AND id != :id");
            $dupStmt->execute([':email' => $pendingEmail, ':id' => $user['id']]);
            if ($dupStmt->fetch()) {
                flash('danger', 'This email address was claimed by another account while verification was pending.');
                redirect('/member/settings.php?tab=email');
            }

            // Update registered email & clear pending columns
            $updateStmt = $db->prepare("
                UPDATE users SET
                    email = :email,
                    pending_email = NULL,
                    email_verification_token = NULL,
                    email_verification_expires_at = NULL,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = :id
            ");
            $updateStmt->execute([
                ':email' => $pendingEmail,
                ':id'    => $user['id']
            ]);

            // Add notification
            add_notification(
                (int)$user['id'],
                'Email Address Updated',
                'Your primary account email was successfully updated to ' . e($pendingEmail) . '.',
                'system',
                '/member/settings.php?tab=email'
            );

            flash('success', 'Your registered email address has been successfully verified and updated to: ' . e($pendingEmail));
            redirect('/member/settings.php?tab=email');
        }
    }

    // Cancel Email Change Request
    elseif ($action === 'cancel_email_change') {
        $cancelStmt = $db->prepare("
            UPDATE users SET
                pending_email = NULL,
                email_verification_token = NULL,
                email_verification_expires_at = NULL,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = :id
        ");
        $cancelStmt->execute([':id' => $user['id']]);

        flash('info', 'Email change request has been cancelled.');
        redirect('/member/settings.php?tab=email');
    }

    // =========================================================================
    // SECTION C: PASSWORD & SECURITY
    // =========================================================================
    elseif ($action === 'change_password') {
        $currentPassword = (string)($_POST['current_password'] ?? '');
        $newPassword     = (string)($_POST['new_password'] ?? '');
        $confirmPassword = (string)($_POST['confirm_password'] ?? '');

        // Fetch current hash
        $pwdStmt = $db->prepare("SELECT password_hash FROM users WHERE id = :id");
        $pwdStmt->execute([':id' => $user['id']]);
        $currentHash = $pwdStmt->fetchColumn();

        if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
            flash('danger', 'Please complete all password fields.');
            redirect('/member/settings.php?tab=security');
        } elseif (!password_verify($currentPassword, $currentHash)) {
            flash('danger', 'Your current password was entered incorrectly.');
            redirect('/member/settings.php?tab=security');
        } elseif (strlen($newPassword) < 8) {
            flash('danger', 'Your new password must be at least 8 characters long.');
            redirect('/member/settings.php?tab=security');
        } elseif ($newPassword !== $confirmPassword) {
            flash('danger', 'The new password and confirmation password do not match.');
            redirect('/member/settings.php?tab=security');
        } elseif ($currentPassword === $newPassword) {
            flash('warning', 'Your new password cannot be the same as your current password.');
            redirect('/member/settings.php?tab=security');
        } else {
            $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
            $upStmt = $db->prepare("UPDATE users SET password_hash = :hash, updated_at = CURRENT_TIMESTAMP WHERE id = :id");
            $upStmt->execute([':hash' => $newHash, ':id' => $user['id']]);

            add_notification(
                (int)$user['id'],
                'Password Updated Securely',
                'Your account password was updated from Account Settings on ' . date('M j, Y \a\t g:i A') . '.',
                'system',
                '/member/settings.php?tab=security'
            );

            flash('success', 'Your password has been changed successfully!');
            redirect('/member/settings.php?tab=security');
        }
    }

    // =========================================================================
    // SECTION D: NOTIFICATION PREFERENCES
    // =========================================================================
    elseif ($action === 'update_notifications') {
        $prefs = [
            'surveys'    => !empty($_POST['notif_surveys']) ? 1 : 0,
            'rewards'    => !empty($_POST['notif_rewards']) ? 1 : 0,
            'payments'   => !empty($_POST['notif_payments']) ? 1 : 0,
            'promotions' => !empty($_POST['notif_promotions']) ? 1 : 0,
            'security'   => !empty($_POST['notif_security']) ? 1 : 0,
        ];

        $prefsJson = json_encode($prefs);
        $notifStmt = $db->prepare("UPDATE users SET notification_preferences = :prefs, updated_at = CURRENT_TIMESTAMP WHERE id = :id");
        $notifStmt->execute([':prefs' => $prefsJson, ':id' => $user['id']]);

        flash('success', 'Your notification preferences have been saved successfully.');
        redirect('/member/settings.php?tab=notifications');
    }

    // =========================================================================
    // SECTION E: ACCOUNT DELETION / CLOSURE REQUEST
    // =========================================================================
    elseif ($action === 'request_account_deletion') {
        $confirmPassword = (string)($_POST['deletion_password'] ?? '');
        $reason = trim((string)($_POST['deletion_reason'] ?? 'Unspecified'));

        $pwdStmt = $db->prepare("SELECT password_hash FROM users WHERE id = :id");
        $pwdStmt->execute([':id' => $user['id']]);
        $currentHash = $pwdStmt->fetchColumn();

        if (empty($confirmPassword) || !password_verify($confirmPassword, $currentHash)) {
            flash('danger', 'Password confirmation is required to submit an account deletion request.');
            redirect('/member/settings.php?tab=privacy');
        } else {
            // Create an official support ticket for administrative review
            $ticketNumber = 'DEL-' . date('Ymd') . '-' . str_pad((string)$user['id'], 4, '0', STR_PAD_LEFT);
            $ticketStmt = $db->prepare("
                INSERT INTO support_tickets (ticket_number, user_id, name, email, subject, message, status, priority)
                VALUES (:ticket_number, :user_id, :name, :email, :subject, :message, 'open', 'urgent')
            ");
            $ticketStmt->execute([
                ':ticket_number' => $ticketNumber,
                ':user_id'       => $user['id'],
                ':name'          => $user['first_name'] . ' ' . $user['last_name'],
                ':email'         => $user['email'],
                ':subject'       => 'Account Deletion & Data Removal Request: #' . $user['id'],
                ':message'       => "Member requested account deletion. Reason: " . $reason . ". Current balance: " . $user['balance_points'] . " points."
            ]);

            flash('warning', 'Your account deletion request has been logged (' . $ticketNumber . '). A research administrator will process your request within 2 business days.');
            redirect('/member/settings.php?tab=privacy');
        }
    }
}

// Re-fetch fresh user row
$stmt = $db->prepare("SELECT * FROM users WHERE id = :id");
$stmt->execute([':id' => $user['id']]);
$user = $stmt->fetch();

// Parse notification preferences
$notifPrefs = [
    'surveys' => 1,
    'rewards' => 1,
    'payments' => 1,
    'promotions' => 1,
    'security' => 1
];
if (!empty($user['notification_preferences'])) {
    $decoded = json_decode($user['notification_preferences'], true);
    if (is_array($decoded)) {
        $notifPrefs = array_merge($notifPrefs, $decoded);
    }
}

require_once __DIR__ . '/../includes/member_header.php';
?>

<div class="portal-wrapper">
    <?php require_once __DIR__ . '/../includes/member_sidebar.php'; ?>

    <div class="portal-main">
        <?= render_flash() ?>

        <!-- Page Header -->
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-2">
            <div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-1 small">
                        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/member/dashboard.php" class="text-decoration-none">Dashboard</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Account Settings</li>
                    </ol>
                </nav>
                <h4 class="fw-bold text-dark mb-0">Account Settings & Security</h4>
                <p class="text-muted small mb-0">Manage your credentials, verified email, authentication security, and privacy preferences.</p>
            </div>
            <div>
                <a href="<?= BASE_URL ?>/member/profile.php" class="btn btn-outline-primary btn-sm rounded-pill px-3 py-1.5 fw-semibold">
                    <i class="bi bi-person me-1"></i> View My Profile
                </a>
            </div>
        </div>

        <!-- Settings Clean White Card with Nav Tabs (Light Theme) -->
        <div class="card card-vy border-0 shadow-sm rounded-4 bg-white overflow-hidden mb-4">
            <!-- Navigation Tabs Header -->
            <div class="bg-light border-bottom px-3 px-md-4 pt-3">
                <ul class="nav nav-tabs nav-tabs-vy border-0" id="settingsTab" role="tablist">
                    <li class="nav-item" role="presentation">
                        <a class="nav-link <?= $activeTab === 'personal' ? 'active' : '' ?>" href="<?= BASE_URL ?>/member/settings.php?tab=personal">
                            <i class="bi bi-person me-2"></i>
                            <span>Personal Info</span>
                        </a>
                    </li>
                    <li class="nav-item" role="presentation">
                        <a class="nav-link <?= $activeTab === 'email' ? 'active' : '' ?>" href="<?= BASE_URL ?>/member/settings.php?tab=email">
                            <i class="bi bi-envelope me-2"></i>
                            <span>Email Address</span>
                            <?php if (!empty($user['pending_email'])): ?>
                                <span class="badge bg-warning text-dark rounded-pill ms-1.5" style="font-size: 0.65rem;">Pending</span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <li class="nav-item" role="presentation">
                        <a class="nav-link <?= $activeTab === 'security' ? 'active' : '' ?>" href="<?= BASE_URL ?>/member/settings.php?tab=security">
                            <i class="bi bi-shield-lock me-2"></i>
                            <span>Password & Security</span>
                        </a>
                    </li>
                    <li class="nav-item" role="presentation">
                        <a class="nav-link <?= $activeTab === 'notifications' ? 'active' : '' ?>" href="<?= BASE_URL ?>/member/settings.php?tab=notifications">
                            <i class="bi bi-bell me-2"></i>
                            <span>Notifications</span>
                        </a>
                    </li>
                    <li class="nav-item" role="presentation">
                        <a class="nav-link <?= $activeTab === 'privacy' ? 'active' : '' ?>" href="<?= BASE_URL ?>/member/settings.php?tab=privacy">
                            <i class="bi bi-shield-check me-2"></i>
                            <span>Privacy & Data</span>
                        </a>
                    </li>
                </ul>
            </div>

            <div class="p-4 p-md-5">
                <!-- ================================================================= -->
                <!-- TAB A: PERSONAL INFORMATION -->
                <!-- ================================================================= -->
                <?php if ($activeTab === 'personal'): ?>
                    <div class="tab-pane-content">
                        <div class="d-flex align-items-center justify-content-between mb-4">
                            <div>
                                <h5 class="fw-bold text-dark mb-1">Personal Information</h5>
                                <p class="text-muted small mb-0">Update your basic legal identity, phone number, and primary location.</p>
                            </div>
                            <div>
                                <a href="<?= BASE_URL ?>/member/profile.php" class="btn btn-sm btn-outline-secondary rounded-pill px-3 py-1">
                                    Full Demographics Form &rarr;
                                </a>
                            </div>
                        </div>

                        <form method="POST" action="<?= BASE_URL ?>/member/settings.php?tab=personal" enctype="multipart/form-data">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="update_personal">

                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold small text-dark" for="set_first_name">First Name *</label>
                                    <input type="text" id="set_first_name" name="first_name" class="form-control rounded-3" value="<?= e($user['first_name']) ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold small text-dark" for="set_last_name">Last Name *</label>
                                    <input type="text" id="set_last_name" name="last_name" class="form-control rounded-3" value="<?= e($user['last_name']) ?>" required>
                                </div>
                            </div>

                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold small text-dark" for="set_phone">Phone Number</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light text-muted border-end-0"><i class="bi bi-telephone"></i></span>
                                        <input type="tel" id="set_phone" name="phone" class="form-control border-start-0 rounded-end-3" placeholder="+1-555-0100" value="<?= e($user['phone'] ?? '') ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold small text-dark" for="set_dob">Date of Birth</label>
                                    <input type="date" id="set_dob" name="date_of_birth" class="form-control rounded-3" value="<?= e($user['date_of_birth'] ?? '') ?>">
                                </div>
                            </div>

                            <div class="row g-3 mb-4">
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold small text-dark" for="set_country">Country</label>
                                    <select id="set_country" name="country" class="form-select rounded-3">
                                        <?php foreach ($countries as $c): ?>
                                            <option value="<?= e($c) ?>" <?= ($user['country'] ?? '') === $c ? 'selected' : '' ?>><?= e($c) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold small text-dark" for="set_state">State / Province</label>
                                    <input type="text" id="set_state" name="state" class="form-control rounded-3" placeholder="e.g. New York" value="<?= e($user['state'] ?? '') ?>">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold small text-dark" for="set_city">City</label>
                                    <input type="text" id="set_city" name="city" class="form-control rounded-3" placeholder="e.g. Brooklyn" value="<?= e($user['city'] ?? '') ?>">
                                </div>
                            </div>

                            <!-- Avatar Upload -->
                            <div class="p-3 bg-light rounded-4 border mb-4">
                                <div class="d-flex align-items-center gap-3">
                                    <?= user_avatar_html($user, 54, 'border shadow-xs') ?>
                                    <div class="flex-grow-1">
                                        <label class="form-label fw-semibold small text-dark mb-1" for="set_avatar_file">Profile Picture / Avatar</label>
                                        <input type="file" id="set_avatar_file" name="avatar_file" class="form-control form-control-sm rounded-3" accept="image/png, image/jpeg, image/webp">
                                        <span class="text-muted small" style="font-size: 0.725rem;">Upload a personal avatar (PNG, JPG, WebP max 2MB).</span>
                                    </div>
                                    <?php if (!empty($user['avatar'])): ?>
                                        <div class="form-check ms-2">
                                            <input class="form-check-input" type="checkbox" name="remove_avatar" value="1" id="removeAvatarCheck2">
                                            <label class="form-check-label text-danger small fw-semibold" for="removeAvatarCheck2">
                                                Remove photo
                                            </label>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="d-flex justify-content-end pt-2 border-top">
                                <button type="submit" class="btn btn-brand-primary rounded-3 px-4 py-2.5 fw-bold shadow-sm">
                                    <i class="bi bi-check-lg me-1"></i> Save Personal Information
                                </button>
                            </div>
                        </form>
                    </div>

                <!-- ================================================================= -->
                <!-- TAB B: EMAIL ADDRESS MANAGEMENT (WITH SECURE OTP VERIFICATION) -->
                <!-- ================================================================= -->
                <?php elseif ($activeTab === 'email'): ?>
                    <div class="tab-pane-content" style="max-width: 680px;">
                        <h5 class="fw-bold text-dark mb-1">Email Address Management</h5>
                        <p class="text-muted small mb-4">Your registered email address is used for authentication, research study dispatches, and PayPal reward transfers.</p>

                        <!-- Current Email Card -->
                        <div class="p-4 bg-light rounded-4 border mb-4">
                            <span class="text-muted small fw-semibold d-block mb-1">Current Registered Email</span>
                            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="fs-5 fw-bold text-dark"><?= e($user['email']) ?></span>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2.5 py-1 small">
                                        <i class="bi bi-patch-check-fill me-1"></i> Verified & Active
                                    </span>
                                </div>
                                <span class="badge bg-white text-muted border px-2.5 py-1 small">
                                    Primary Login
                                </span>
                            </div>
                        </div>

                        <!-- Active Pending Verification Alert / Form (If Request Exists) -->
                        <?php if (!empty($user['pending_email']) && !empty($user['email_verification_token'])): ?>
                            <div class="card border-warning border-opacity-50 bg-warning bg-opacity-10 rounded-4 p-4 mb-4 shadow-sm">
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <i class="bi bi-hourglass-split text-warning-emphasis fs-4"></i>
                                    <h6 class="fw-bold text-dark mb-0">Pending Email Verification</h6>
                                </div>
                                <p class="small text-muted mb-3">
                                    A 6-digit confirmation code was dispatched for: <strong class="text-dark"><?= e($user['pending_email']) ?></strong>.
                                    The code will expire on <strong class="text-dark"><?= date('g:i A', strtotime($user['email_verification_expires_at'])) ?></strong>.
                                </p>

                                <form method="POST" action="<?= BASE_URL ?>/member/settings.php?tab=email" class="mb-3">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="verify_email_otp">

                                    <div class="row g-2 align-items-center">
                                        <div class="col-sm-7">
                                            <label class="form-label small fw-semibold text-dark mb-1" for="otp_input">Enter 6-Digit Verification Code</label>
                                            <input type="text" id="otp_input" name="verification_otp" class="form-control rounded-3 text-center fw-bold fs-5 tracking-wider" placeholder="••••••" maxlength="6" required autocomplete="one-time-code" autofocus>
                                        </div>
                                        <div class="col-sm-5 pt-sm-4">
                                            <button type="submit" class="btn btn-success rounded-3 w-100 py-2.5 fw-bold shadow-sm">
                                                <i class="bi bi-check2-circle me-1"></i> Verify & Update
                                            </button>
                                        </div>
                                    </div>
                                </form>

                                <form method="POST" action="<?= BASE_URL ?>/member/settings.php?tab=email">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="cancel_email_change">
                                    <button type="submit" class="btn btn-link text-danger text-decoration-none small p-0 fw-semibold">
                                        <i class="bi bi-x-circle me-1"></i> Cancel this email change request
                                    </button>
                                </form>
                            </div>
                        <?php endif; ?>

                        <!-- Request New Email Change Form -->
                        <div class="card card-vy border-0 shadow-sm p-4 p-md-4 bg-white border border-light-subtle">
                            <h6 class="fw-bold text-dark mb-1">Request a New Email Address</h6>
                            <p class="text-muted small mb-3">To safeguard your survey rewards, changing your email requires your current account password.</p>

                            <form method="POST" action="<?= BASE_URL ?>/member/settings.php?tab=email" autocomplete="off">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="request_email_change">

                                <div class="mb-3">
                                    <label class="form-label fw-semibold small text-dark" for="new_email">
                                        New Email Address <span class="text-danger">*</span>
                                    </label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light text-muted border-end-0"><i class="bi bi-envelope"></i></span>
                                        <input type="email" id="new_email" name="new_email" class="form-control border-start-0 rounded-end-3" placeholder="name@example.com" required>
                                    </div>
                                    <small class="text-muted" style="font-size: 0.725rem;">Must be a valid email address not already registered on Veritopinion.</small>
                                </div>

                                <div class="mb-4">
                                    <label class="form-label fw-semibold small text-dark" for="confirm_account_password">
                                        Confirm Current Password <span class="text-danger">*</span>
                                    </label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light text-muted border-end-0"><i class="bi bi-shield-lock"></i></span>
                                        <input type="password" id="confirm_account_password" name="confirm_account_password" class="form-control border-start-0 border-end-0 ps-0" placeholder="••••••••" required>
                                        <button type="button" class="btn btn-light border border-start-0 text-muted btn-toggle-password" data-target="confirm_account_password">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                    </div>
                                </div>

                                <div class="d-flex justify-content-end pt-2 border-top">
                                    <button type="submit" class="btn btn-brand-primary rounded-3 px-4 py-2.5 fw-bold shadow-sm">
                                        <i class="bi bi-send me-1"></i> Send Verification Code
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                <!-- ================================================================= -->
                <!-- TAB C: PASSWORD & SECURITY -->
                <!-- ================================================================= -->
                <?php elseif ($activeTab === 'security'): ?>
                    <div class="tab-pane-content" style="max-width: 680px;">
                        <div class="d-flex align-items-center gap-3 mb-4">
                            <div class="rounded-circle bg-primary-subtle text-primary d-flex align-items-center justify-content-center" style="width: 48px; height: 48px; font-size: 1.25rem;">
                                <i class="bi bi-shield-lock-fill"></i>
                            </div>
                            <div>
                                <h5 class="fw-bold text-dark mb-0">Change Account Password</h5>
                                <p class="text-muted small mb-0">Protect your survey rewards using a strong, unique password.</p>
                            </div>
                        </div>

                        <form method="POST" action="<?= BASE_URL ?>/member/settings.php?tab=security" autocomplete="off" id="changePasswordForm">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="change_password">

                            <!-- Current Password -->
                            <div class="mb-3">
                                <label class="form-label fw-semibold small text-dark" for="curPwd">Current Password *</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-shield-lock"></i></span>
                                    <input type="password" id="curPwd" name="current_password" class="form-control border-start-0 border-end-0 ps-0" placeholder="Enter current password" required autocomplete="current-password">
                                    <button type="button" class="btn btn-light border border-start-0 text-muted btn-toggle-password" data-target="curPwd">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                                <div class="text-end mt-1">
                                    <a href="<?= BASE_URL ?>/auth/forgot-password.php" class="text-primary small text-decoration-none" style="font-size: 0.75rem;">Forgot password?</a>
                                </div>
                            </div>

                            <!-- New Password -->
                            <div class="mb-3">
                                <label class="form-label fw-semibold small text-dark" for="newPwd">New Password *</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-key"></i></span>
                                    <input type="password" id="newPwd" name="new_password" class="form-control border-start-0 border-end-0 ps-0" placeholder="Minimum 8 characters" required autocomplete="new-password">
                                    <button type="button" class="btn btn-light border border-start-0 text-muted btn-toggle-password" data-target="newPwd">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>

                                <!-- Real-Time Password Strength Meter -->
                                <div class="password-strength-container mt-2">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <span class="small text-muted" style="font-size: 0.75rem;">Strength:</span>
                                        <span id="strengthText" class="badge bg-secondary-subtle text-secondary rounded-pill px-2" style="font-size: 0.7rem;">Enter password</span>
                                    </div>
                                    <div class="password-strength-bar">
                                        <div id="strengthProgress" class="password-strength-progress"></div>
                                    </div>
                                </div>

                                <!-- Password Checklist -->
                                <div class="row g-2 mt-2 pt-1">
                                    <div class="col-sm-6">
                                        <div class="pwd-rule-item" id="ruleLength"><i class="bi bi-circle"></i> <span>At least 8 characters</span></div>
                                    </div>
                                    <div class="col-sm-6">
                                        <div class="pwd-rule-item" id="ruleLetter"><i class="bi bi-circle"></i> <span>Contains letters</span></div>
                                    </div>
                                    <div class="col-sm-6">
                                        <div class="pwd-rule-item" id="ruleNumber"><i class="bi bi-circle"></i> <span>Contains a number</span></div>
                                    </div>
                                    <div class="col-sm-6">
                                        <div class="pwd-rule-item" id="ruleSpecial"><i class="bi bi-circle"></i> <span>Symbol recommended</span></div>
                                    </div>
                                </div>
                            </div>

                            <!-- Confirm New Password -->
                            <div class="mb-4">
                                <label class="form-label fw-semibold small text-dark" for="confPwd">Confirm New Password *</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-check2-circle"></i></span>
                                    <input type="password" id="confPwd" name="confirm_password" class="form-control border-start-0 border-end-0 ps-0" placeholder="Repeat your new password" required autocomplete="new-password">
                                    <button type="button" class="btn btn-light border border-start-0 text-muted btn-toggle-password" data-target="confPwd">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                                <div id="matchFeedback" class="small mt-1 text-muted" style="font-size: 0.75rem;"></div>
                            </div>

                            <div class="d-flex justify-content-end pt-2 border-top">
                                <button type="submit" class="btn btn-brand-primary rounded-3 px-4 py-2.5 fw-bold shadow-sm d-inline-flex align-items-center gap-2">
                                    <i class="bi bi-shield-check"></i>
                                    <span>Save New Password</span>
                                </button>
                            </div>
                        </form>
                    </div>

                <!-- ================================================================= -->
                <!-- TAB D: NOTIFICATION PREFERENCES -->
                <!-- ================================================================= -->
                <?php elseif ($activeTab === 'notifications'): ?>
                    <div class="tab-pane-content" style="max-width: 680px;">
                        <h5 class="fw-bold text-dark mb-1">Notification Preferences</h5>
                        <p class="text-muted small mb-4">Choose which notifications you wish to receive via your registered email and panel notifications feed.</p>

                        <form method="POST" action="<?= BASE_URL ?>/member/settings.php?tab=notifications">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="update_notifications">

                            <div class="list-group list-group-flush mb-4">
                                <!-- 1. Survey Invitations -->
                                <div class="list-group-item px-0 py-3 d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="fw-bold text-dark mb-0">Survey Invitations & Quota Alerts</h6>
                                        <p class="text-muted small mb-0">Instant alerts when newly matched, high-reward research studies open for your profile.</p>
                                    </div>
                                    <div class="form-check form-switch fs-5">
                                        <input class="form-check-input" type="checkbox" name="notif_surveys" value="1" id="swSurveys" <?= !empty($notifPrefs['surveys']) ? 'checked' : '' ?>>
                                    </div>
                                </div>

                                <!-- 2. Reward Notifications -->
                                <div class="list-group-item px-0 py-3 d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="fw-bold text-dark mb-0">Reward Redemption Status</h6>
                                        <p class="text-muted small mb-0">Notifications when your PayPal cash or digital gift card redemptions are approved or delivered.</p>
                                    </div>
                                    <div class="form-check form-switch fs-5">
                                        <input class="form-check-input" type="checkbox" name="notif_rewards" value="1" id="swRewards" <?= !empty($notifPrefs['rewards']) ? 'checked' : '' ?>>
                                    </div>
                                </div>

                                <!-- 3. Payment Updates -->
                                <div class="list-group-item px-0 py-3 d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="fw-bold text-dark mb-0">Wallet Ledger & Points Balance</h6>
                                        <p class="text-muted small mb-0">Credits issued for survey completions, bonuses, and account balance milestone achievements.</p>
                                    </div>
                                    <div class="form-check form-switch fs-5">
                                        <input class="form-check-input" type="checkbox" name="notif_payments" value="1" id="swPayments" <?= !empty($notifPrefs['payments']) ? 'checked' : '' ?>>
                                    </div>
                                </div>

                                <!-- 4. Promotional Emails -->
                                <div class="list-group-item px-0 py-3 d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="fw-bold text-dark mb-0">Promotional & Special Events</h6>
                                        <p class="text-muted small mb-0">Occasional announcements regarding double-point weekends and community bonus programs.</p>
                                    </div>
                                    <div class="form-check form-switch fs-5">
                                        <input class="form-check-input" type="checkbox" name="notif_promotions" value="1" id="swPromotions" <?= !empty($notifPrefs['promotions']) ? 'checked' : '' ?>>
                                    </div>
                                </div>

                                <!-- 5. Security Alerts -->
                                <div class="list-group-item px-0 py-3 d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="fw-bold text-dark mb-0">Account Security & Credential Notices</h6>
                                        <p class="text-muted small mb-0">Critical alerts on password updates, new logins, or session security events (recommended).</p>
                                    </div>
                                    <div class="form-check form-switch fs-5">
                                        <input class="form-check-input" type="checkbox" name="notif_security" value="1" id="swSecurity" <?= !empty($notifPrefs['security']) ? 'checked' : '' ?>>
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex justify-content-end pt-2 border-top">
                                <button type="submit" class="btn btn-brand-primary rounded-3 px-4 py-2.5 fw-bold shadow-sm">
                                    <i class="bi bi-check2 me-1"></i> Save Preferences
                                </button>
                            </div>
                        </form>
                    </div>

                <!-- ================================================================= -->
                <!-- TAB E: ACCOUNT PRIVACY & DATA MANAGEMENT -->
                <!-- ================================================================= -->
                <?php elseif ($activeTab === 'privacy'): ?>
                    <div class="tab-pane-content" style="max-width: 720px;">
                        <h5 class="fw-bold text-dark mb-1">Account Privacy & Data Governance</h5>
                        <p class="text-muted small mb-4">You maintain complete ownership of your personal information under GDPR and CCPA privacy standards.</p>

                        <!-- Data Export Card -->
                        <div class="card border border-light-subtle rounded-4 p-4 mb-4 bg-light">
                            <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3">
                                <div>
                                    <h6 class="fw-bold text-dark mb-1">
                                        <i class="bi bi-file-earmark-arrow-down text-primary me-1"></i> Export Account Data (JSON)
                                    </h6>
                                    <p class="text-muted small mb-0">Download a complete, machine-readable JSON copy of your profile, survey history, and ledger records.</p>
                                </div>
                                <a href="<?= BASE_URL ?>/member/settings.php?action=export_data&csrf_token=<?= csrf_token() ?>" class="btn btn-outline-primary rounded-3 px-3 py-2 fw-semibold text-nowrap">
                                    <i class="bi bi-download me-1"></i> Download Data
                                </a>
                            </div>
                        </div>

                        <!-- Data Protection Standards -->
                        <div class="p-3 bg-white rounded-3 border mb-4 small text-muted">
                            <div class="d-flex align-items-center gap-2 mb-1 text-dark fw-bold">
                                <i class="bi bi-shield-check text-success"></i>
                                <span>Veritopinion Privacy Shield</span>
                            </div>
                            <span>Your survey answers are encrypted, detached from your personal contact details, and combined into statistical datasets before corporate research clients access them.</span>
                        </div>

                        <!-- Danger Zone: Account Deletion -->
                        <div class="card border-danger border-opacity-25 rounded-4 p-4">
                            <h6 class="fw-bold text-danger mb-1 d-flex align-items-center gap-2">
                                <i class="bi bi-exclamation-triangle-fill"></i>
                                <span>Account Deletion & Data Removal</span>
                            </h6>
                            <p class="text-muted small mb-3">
                                Closing your account will permanently cancel your participation, surrender remaining points, and anonymize your historical logs.
                            </p>

                            <button type="button" class="btn btn-outline-danger btn-sm rounded-3 px-3 py-2 fw-bold" data-bs-toggle="modal" data-bs-target="#deleteAccountModal">
                                Request Account Deletion...
                            </button>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

<!-- Global Account Deletion Confirmation Modal -->
<div class="modal fade" id="deleteAccountModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header bg-danger text-white border-0 py-3">
                <h6 class="modal-title fw-bold" id="deleteModalLabel">
                    <i class="bi bi-exclamation-octagon-fill me-1"></i> Request Account Deletion
                </h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="<?= BASE_URL ?>/member/settings.php?tab=privacy">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="request_account_deletion">

                <div class="modal-body p-4">
                    <p class="small text-muted mb-3">
                        Are you sure you wish to close your account? This action creates an administrative ticket. Any unredeemed points balance (<strong class="text-dark"><?= format_points($user['balance_points']) ?></strong>) will be forfeited.
                    </p>

                    <div class="mb-3">
                        <label class="form-label fw-semibold small text-dark" for="del_reason">Reason for leaving (Optional)</label>
                        <select id="del_reason" name="deletion_reason" class="form-select rounded-3">
                            <option value="Not enough surveys matched">Not enough surveys matched</option>
                            <option value="Privacy concerns">Privacy concerns</option>
                            <option value="Reached savings goal">Reached savings goal</option>
                            <option value="Other">Other reason</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold small text-dark" for="del_pwd">Confirm Account Password *</label>
                        <input type="password" id="del_pwd" name="deletion_password" class="form-control rounded-3" placeholder="Enter password to confirm" required>
                    </div>
                </div>

                <div class="modal-footer bg-light border-top p-3 d-flex justify-content-between">
                    <button type="button" class="btn btn-light border rounded-3 px-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger rounded-3 px-3 fw-bold">Confirm Account Deletion</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Password Strength Meter & Matching Logic for Security Tab
    const newPwdInput = document.getElementById('newPwd');
    const confPwdInput = document.getElementById('confPwd');
    const progressBar = document.getElementById('strengthProgress');
    const strengthText = document.getElementById('strengthText');
    const matchFeedback = document.getElementById('matchFeedback');

    const ruleLength = document.getElementById('ruleLength');
    const ruleLetter = document.getElementById('ruleLetter');
    const ruleNumber = document.getElementById('ruleNumber');
    const ruleSpecial = document.getElementById('ruleSpecial');

    function checkStrength(pwd) {
        if (!progressBar || !strengthText) return;
        if (!pwd) {
            progressBar.className = 'password-strength-progress';
            progressBar.style.width = '0%';
            strengthText.className = 'badge bg-secondary-subtle text-secondary rounded-pill px-2';
            strengthText.innerText = 'Enter password';
            return;
        }

        const hasLength = pwd.length >= 8;
        const hasLetter = /[a-zA-Z]/.test(pwd);
        const hasNumber = /[0-9]/.test(pwd);
        const hasSpecial = /[^a-zA-Z0-9]/.test(pwd);

        updateRule(ruleLength, hasLength);
        updateRule(ruleLetter, hasLetter);
        updateRule(ruleNumber, hasNumber);
        updateRule(ruleSpecial, hasSpecial);

        let score = 0;
        if (hasLength) score++;
        if (hasLetter) score++;
        if (hasNumber) score++;
        if (hasSpecial) score++;
        if (pwd.length >= 12) score++;

        if (score <= 2) {
            progressBar.className = 'password-strength-progress strength-weak';
            strengthText.className = 'badge bg-danger-subtle text-danger rounded-pill px-2';
            strengthText.innerText = 'Weak';
        } else if (score <= 3) {
            progressBar.className = 'password-strength-progress strength-medium';
            strengthText.className = 'badge bg-warning-subtle text-warning-emphasis rounded-pill px-2';
            strengthText.innerText = 'Moderate';
        } else {
            progressBar.className = 'password-strength-progress strength-strong';
            strengthText.className = 'badge bg-success-subtle text-success rounded-pill px-2';
            strengthText.innerText = 'Strong & Secure';
        }
    }

    function updateRule(element, isValid) {
        if (!element) return;
        const icon = element.querySelector('i');
        if (isValid) {
            element.classList.add('valid');
            if (icon) icon.className = 'bi bi-check-circle-fill text-success';
        } else {
            element.classList.remove('valid');
            if (icon) icon.className = 'bi bi-circle text-muted';
        }
    }

    function checkMatch() {
        if (!matchFeedback) return;
        const val1 = newPwdInput ? newPwdInput.value : '';
        const val2 = confPwdInput ? confPwdInput.value : '';

        if (!val2) {
            matchFeedback.innerText = '';
            return;
        }

        if (val1 === val2) {
            matchFeedback.innerHTML = '<span class="text-success"><i class="bi bi-check-circle me-1"></i> Passwords match!</span>';
        } else {
            matchFeedback.innerHTML = '<span class="text-danger"><i class="bi bi-x-circle me-1"></i> Passwords do not match.</span>';
        }
    }

    if (newPwdInput) {
        newPwdInput.addEventListener('input', function () {
            checkStrength(this.value);
            checkMatch();
        });
    }

    if (confPwdInput) {
        confPwdInput.addEventListener('input', checkMatch);
    }
});
</script>

<?php require_once __DIR__ . '/../includes/member_footer.php'; ?>
