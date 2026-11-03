<?php
/**
 * Veritolytics - Member Profile Management (Premium Light Theme)
 * Pure Core PHP 8.2+ Architecture
 */

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/auth_check.php';

$user = require_login();
$pageTitle = 'My Profile';
$db = get_db();

$countries = [
    'United States', 'United Kingdom', 'Canada', 'Australia', 'Germany', 
    'France', 'India', 'Japan', 'Brazil', 'Singapore', 'Netherlands', 
    'Spain', 'Italy', 'Mexico', 'South Africa', 'Other'
];

$genderOptions = ['Male', 'Female', 'Non-Binary / Third Gender', 'Prefer not to disclose'];

$employmentOptions = [
    'Employed Full-Time', 'Employed Part-Time', 'Self-Employed / Freelancer',
    'Executive / Senior Manager', 'Homemaker', 'Student', 'Retired', 'Currently Seeking Opportunities'
];

$educationOptions = [
    'High School Diploma / GED', 'Some College / Vocational', 'Associate Degree',
    'Bachelor\'s Degree', 'Master\'s Degree', 'Doctorate / Professional (MD, JD, PhD)'
];

$incomeOptions = [
    'Under $25,000', '$25,000 - $49,999', '$50,000 - $74,999',
    '$75,000 - $99,999', '$100,000 - $149,999', '$150,000+'
];

// Handle Profile Updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        flash('danger', 'Security validation token expired. Please try again.');
        redirect('/member/profile.php');
    }

    $action = $_POST['action'] ?? 'update_profile';

    if ($action === 'update_profile') {
        $firstName = trim((string)($_POST['first_name'] ?? ''));
        $lastName  = trim((string)($_POST['last_name'] ?? ''));
        $phone     = trim((string)($_POST['phone'] ?? ''));
        $country   = trim((string)($_POST['country'] ?? ''));
        $state     = trim((string)($_POST['state'] ?? ''));
        $city      = trim((string)($_POST['city'] ?? ''));
        $dob       = trim((string)($_POST['date_of_birth'] ?? ''));
        $gender    = trim((string)($_POST['gender'] ?? ''));
        $employment = trim((string)($_POST['employment_status'] ?? ''));
        $education  = trim((string)($_POST['education_level'] ?? ''));
        $income     = trim((string)($_POST['annual_income'] ?? ''));

        if (empty($firstName) || empty($lastName)) {
            flash('danger', 'First Name and Last Name are required fields.');
            redirect('/member/profile.php');
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

            if (!in_array($mimeType, $allowedTypes)) {
                flash('danger', 'Please upload a valid image file (JPG, PNG, or WebP).');
                redirect('/member/profile.php');
            }

            if ($file['size'] > 2 * 1024 * 1024) {
                flash('danger', 'Avatar image file must be smaller than 2MB.');
                redirect('/member/profile.php');
            }

            $ext = match($mimeType) {
                'image/jpeg' => 'jpg',
                'image/png'  => 'png',
                'image/webp' => 'webp',
                default      => 'jpg'
            };

            $uploadDir = __DIR__ . '/../uploads/avatars';
            if (!is_dir($uploadDir)) {
                @mkdir($uploadDir, 0755, true);
            }

            $fileName = 'avatar_' . $user['id'] . '_' . time() . '.' . $ext;
            $destination = $uploadDir . '/' . $fileName;

            if (move_uploaded_file($file['tmp_name'], $destination)) {
                if ($currentAvatar && file_exists(__DIR__ . '/../' . ltrim($currentAvatar, '/'))) {
                    @unlink(__DIR__ . '/../' . ltrim($currentAvatar, '/'));
                }
                $currentAvatar = 'uploads/avatars/' . $fileName;
            }
        }

        // Recalculate profile completeness
        $checkData = [
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $user['email'],
            'country' => $country,
            'phone' => $phone,
            'date_of_birth' => $dob,
            'gender' => $gender,
            'employment_status' => $employment,
            'education_level' => $education
        ];
        $newPct = calculate_profile_completion($checkData);

        // Save to Database
        $updateStmt = $db->prepare("
            UPDATE users SET
                first_name = :first_name,
                last_name = :last_name,
                phone = :phone,
                country = :country,
                state = :state,
                city = :city,
                date_of_birth = :date_of_birth,
                gender = :gender,
                avatar = :avatar,
                employment_status = :employment_status,
                education_level = :education_level,
                annual_income = :annual_income,
                profile_completion_pct = :profile_completion_pct,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = :id
        ");

        $updateStmt->execute([
            ':first_name' => $firstName,
            ':last_name'  => $lastName,
            ':phone'      => $phone ?: null,
            ':country'    => $country ?: 'United States',
            ':state'      => $state ?: null,
            ':city'       => $city ?: null,
            ':date_of_birth' => $dob ?: null,
            ':gender'     => $gender ?: null,
            ':avatar'     => $currentAvatar,
            ':employment_status' => $employment ?: null,
            ':education_level'   => $education ?: null,
            ':annual_income'     => $income ?: null,
            ':profile_completion_pct' => $newPct,
            ':id'         => $user['id']
        ]);

        // Award bonus if user hits 100% completion for the first time
        if ($newPct === 100 && (($user['profile_completion_pct'] ?? 0) < 100)) {
            $bonus = 200;
            update_user_balance((int)$user['id'], $bonus);
            add_transaction((int)$user['id'], 'bonus', '100% Profile Demographics Bonus', $bonus, $bonus / POINTS_PER_USD);
            add_notification(
                (int)$user['id'],
                '100% Profile Completion Bonus (+200 pts)! 🌟',
                'Congratulations! You earned 200 points for completing 100% of your research profile.',
                'success',
                '/member/wallet.php'
            );
            flash('success', 'Profile updated! You earned 200 bonus points for reaching 100% profile completeness.');
        } else {
            flash('success', 'Your personal profile has been saved successfully.');
        }

        redirect('/member/profile.php');
    }
}

// Re-fetch updated user record
$stmt = $db->prepare("SELECT * FROM users WHERE id = :id");
$stmt->execute([':id' => $user['id']]);
$user = $stmt->fetch();

$currentPct = $user['profile_completion_pct'] ?? calculate_profile_completion($user);

require_once __DIR__ . '/../includes/member_header.php';
?>

<div class="portal-wrapper">
    <?php require_once __DIR__ . '/../includes/member_sidebar.php'; ?>

    <div class="portal-main">
        <?= render_flash() ?>

        <!-- Page Header -->
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
            <div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-1 small">
                        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/member/dashboard.php" class="text-decoration-none">Dashboard</a></li>
                        <li class="breadcrumb-item active" aria-current="page">My Profile</li>
                    </ol>
                </nav>
                <h4 class="fw-bold text-dark mb-0">My Member Profile</h4>
                <p class="text-muted small mb-0">Manage your identity, demographic criteria, and research qualifications.</p>
            </div>
            <div class="d-flex align-items-center gap-2">
                <a href="<?= BASE_URL ?>/member/settings.php" class="btn btn-outline-secondary btn-sm rounded-pill px-3 py-1.5 fw-semibold">
                    <i class="bi bi-gear me-1"></i> Account Settings
                </a>
                <button type="button" class="btn btn-brand-primary btn-sm rounded-pill px-3.5 py-1.5 fw-bold shadow-xs" onclick="document.getElementById('first_name').focus();">
                    <i class="bi bi-pencil-square me-1"></i> Edit Profile
                </button>
            </div>
        </div>

        <!-- 1. Profile Hero & Read-Only Information Card (Clean White Light Theme) -->
        <div class="card card-vy border-0 shadow-sm p-4 mb-4 bg-white">
            <div class="row align-items-center g-4">
                <div class="col-md-auto text-center text-md-start">
                    <div class="position-relative d-inline-block">
                        <?= user_avatar_html($user, 90, 'border border-3 border-white shadow-sm') ?>
                        <span class="position-absolute bottom-0 end-0 badge rounded-circle bg-primary p-2 border border-2 border-white shadow-xs" title="Profile Avatar Active">
                            <i class="bi bi-camera-fill" style="font-size: 0.75rem;"></i>
                        </span>
                    </div>
                </div>

                <div class="col-md">
                    <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                        <h4 class="fw-bold text-dark mb-0"><?= e($user['first_name'] . ' ' . $user['last_name']) ?></h4>
                        <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2.5 py-1 fw-semibold small">
                            <i class="bi bi-patch-check-fill me-1"></i> Active Verified
                        </span>
                    </div>

                    <div class="text-muted small mb-2 d-flex flex-wrap align-items-center gap-3">
                        <span><i class="bi bi-envelope text-primary me-1"></i> <?= e($user['email']) ?></span>
                        <span>&bull;</span>
                        <span><i class="bi bi-geo-alt text-danger me-1"></i> <?= e(!empty($user['city']) ? ($user['city'] . ', ' . $user['country']) : $user['country']) ?></span>
                    </div>

                    <div class="d-flex flex-wrap align-items-center gap-2 pt-1">
                        <span class="badge bg-light text-dark border px-2.5 py-1.5 fw-medium small">
                            Member ID: <code class="text-primary fw-bold">#VY-<?= str_pad((string)$user['id'], 6, '0', STR_PAD_LEFT) ?></code>
                        </span>
                        <span class="badge bg-light text-dark border px-2.5 py-1.5 fw-medium small">
                            Joined: <strong><?= date('M j, Y', strtotime($user['created_at'])) ?></strong>
                        </span>
                        <div class="header-wallet-badge">
                            <i class="bi bi-coin"></i>
                            <span><?= format_points($user['balance_points']) ?></span>
                        </div>
                    </div>
                </div>

                <!-- Profile Completion Score Widget -->
                <div class="col-lg-3 col-md-4 border-start-md ps-md-4">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="small fw-semibold text-dark">Profile Completion</span>
                        <span class="fw-bold text-primary small" id="profileCompletionBadge"><?= $currentPct ?>%</span>
                    </div>
                    <div class="progress mb-2" style="height: 8px;">
                        <div class="progress-bar bg-gradient-royal" role="progressbar" style="width: <?= $currentPct ?>%;" aria-valuenow="<?= $currentPct ?>" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                    <span class="text-muted d-block small" style="font-size: 0.75rem;">
                        <?= $currentPct === 100 ? '<i class="bi bi-check-circle-fill text-success me-1"></i> High priority survey matching enabled' : 'Complete 100% to qualify for exclusive research studies' ?>
                    </span>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <!-- 2. Main Editable Form (Left Column) -->
            <div class="col-lg-8">
                <div class="card card-vy border-0 shadow-sm p-4 p-md-5 bg-white">
                    <form method="POST" action="<?= BASE_URL ?>/member/profile.php" enctype="multipart/form-data" id="profileForm">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="update_profile">

                        <!-- Section A: Identity & Contact -->
                        <div class="d-flex align-items-center gap-2 border-bottom pb-2 mb-4">
                            <div class="rounded-circle bg-primary-subtle text-primary d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                                <i class="bi bi-person-badge"></i>
                            </div>
                            <h6 class="fw-bold text-dark mb-0">1. Personal Identity & Contact</h6>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold small text-dark" for="first_name">
                                    First Name <span class="text-danger">*</span>
                                </label>
                                <input type="text" id="first_name" name="first_name" class="form-control rounded-3" value="<?= e($user['first_name']) ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold small text-dark" for="last_name">
                                    Last Name <span class="text-danger">*</span>
                                </label>
                                <input type="text" id="last_name" name="last_name" class="form-control rounded-3" value="<?= e($user['last_name']) ?>" required>
                            </div>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold small text-dark">
                                    Registered Email Address
                                </label>
                                <div class="input-group">
                                    <input type="email" class="form-control rounded-start-3 bg-light text-muted border-end-0" value="<?= e($user['email']) ?>" readonly>
                                    <a href="<?= BASE_URL ?>/member/settings.php?tab=email" class="btn btn-outline-primary border-start-0 px-3 fw-semibold small d-flex align-items-center" title="Securely change and verify your email">
                                        Change
                                    </a>
                                </div>
                                <small class="text-muted d-block mt-1" style="font-size: 0.725rem;">
                                    <i class="bi bi-shield-check text-success me-1"></i> Verified primary email for PayPal payouts & study notifications.
                                </small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold small text-dark" for="phone">
                                    Phone Number
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light text-muted border-end-0"><i class="bi bi-telephone"></i></span>
                                    <input type="tel" id="phone" name="phone" class="form-control border-start-0 rounded-end-3" placeholder="+1-555-0100" value="<?= e($user['phone'] ?? '') ?>">
                                </div>
                            </div>
                        </div>

                        <!-- Section B: Location -->
                        <div class="d-flex align-items-center gap-2 border-bottom pb-2 mb-4 pt-2">
                            <div class="rounded-circle bg-success-subtle text-success d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                                <i class="bi bi-geo-alt"></i>
                            </div>
                            <h6 class="fw-bold text-dark mb-0">2. Geographic Location</h6>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-md-4">
                                <label class="form-label fw-semibold small text-dark" for="country">Country *</label>
                                <select id="country" name="country" class="form-select rounded-3">
                                    <?php foreach ($countries as $c): ?>
                                        <option value="<?= e($c) ?>" <?= ($user['country'] ?? '') === $c ? 'selected' : '' ?>><?= e($c) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold small text-dark" for="state">State / Province</label>
                                <input type="text" id="state" name="state" class="form-control rounded-3" placeholder="e.g. New York / CA" value="<?= e($user['state'] ?? '') ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold small text-dark" for="city">City</label>
                                <input type="text" id="city" name="city" class="form-control rounded-3" placeholder="e.g. Manhattan" value="<?= e($user['city'] ?? '') ?>">
                            </div>
                        </div>

                        <!-- Section C: Demographics -->
                        <div class="d-flex align-items-center gap-2 border-bottom pb-2 mb-4 pt-2">
                            <div class="rounded-circle bg-warning-subtle text-warning-emphasis d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                                <i class="bi bi-bar-chart-steps"></i>
                            </div>
                            <h6 class="fw-bold text-dark mb-0">3. Research Demographics & Survey Matching</h6>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold small text-dark" for="date_of_birth">Date of Birth</label>
                                <input type="date" id="date_of_birth" name="date_of_birth" class="form-control rounded-3" value="<?= e($user['date_of_birth'] ?? '') ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold small text-dark" for="gender">Gender Identity</label>
                                <select id="gender" name="gender" class="form-select rounded-3">
                                    <option value="">Select Gender...</option>
                                    <?php foreach ($genderOptions as $g): ?>
                                        <option value="<?= e($g) ?>" <?= ($user['gender'] ?? '') === $g ? 'selected' : '' ?>><?= e($g) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-md-4">
                                <label class="form-label fw-semibold small text-dark">Employment Status</label>
                                <select name="employment_status" class="form-select rounded-3">
                                    <option value="">Select Status...</option>
                                    <?php foreach ($employmentOptions as $emp): ?>
                                        <option value="<?= e($emp) ?>" <?= ($user['employment_status'] ?? '') === $emp ? 'selected' : '' ?>><?= e($emp) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold small text-dark">Education Level</label>
                                <select name="education_level" class="form-select rounded-3">
                                    <option value="">Select Education...</option>
                                    <?php foreach ($educationOptions as $edu): ?>
                                        <option value="<?= e($edu) ?>" <?= ($user['education_level'] ?? '') === $edu ? 'selected' : '' ?>><?= e($edu) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold small text-dark">Annual Income</label>
                                <select name="annual_income" class="form-select rounded-3">
                                    <option value="">Select Range...</option>
                                    <?php foreach ($incomeOptions as $inc): ?>
                                        <option value="<?= e($inc) ?>" <?= ($user['annual_income'] ?? '') === $inc ? 'selected' : '' ?>><?= e($inc) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <!-- Section D: Profile Picture Management -->
                        <div class="d-flex align-items-center gap-2 border-bottom pb-2 mb-4 pt-2">
                            <div class="rounded-circle bg-purple-subtle text-purple d-flex align-items-center justify-content-center" style="width: 32px; height: 32px; background: rgba(124, 58, 237, 0.1); color: #7C3AED;">
                                <i class="bi bi-image"></i>
                            </div>
                            <h6 class="fw-bold text-dark mb-0">4. Profile Picture / Avatar</h6>
                        </div>

                        <div class="row align-items-center g-3 mb-4">
                            <div class="col-auto">
                                <?= user_avatar_html($user, 64, 'border shadow-xs') ?>
                            </div>
                            <div class="col">
                                <label class="form-label fw-semibold small text-dark mb-1" for="avatar_file">
                                    Upload New Avatar Photo
                                </label>
                                <input type="file" id="avatar_file" name="avatar_file" class="form-control rounded-3" accept="image/png, image/jpeg, image/webp">
                                <small class="text-muted d-block mt-1" style="font-size: 0.725rem;">
                                    Supported: JPG, PNG, WebP (Max: 2MB). If no photo is chosen, your stylized initials avatar is displayed.
                                </small>
                            </div>
                            <?php if (!empty($user['avatar'])): ?>
                                <div class="col-auto">
                                    <div class="form-check mt-3">
                                        <input class="form-check-input" type="checkbox" name="remove_avatar" value="1" id="removeAvatarCheck">
                                        <label class="form-check-label text-danger small fw-semibold" for="removeAvatarCheck">
                                            Remove photo
                                        </label>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Action Buttons -->
                        <div class="d-flex align-items-center justify-content-between pt-3 border-top mt-4">
                            <a href="<?= BASE_URL ?>/member/profile.php" class="btn btn-light border rounded-3 px-4 py-2.5 fw-semibold text-muted">
                                Cancel
                            </a>
                            <div class="d-flex align-items-center gap-2">
                                <button type="submit" class="btn btn-brand-primary rounded-3 px-4 py-2.5 fw-bold shadow-sm d-inline-flex align-items-center gap-2">
                                    <i class="bi bi-check-lg fs-5"></i>
                                    <span>Save Profile Changes</span>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- 3. Right Column: Verification, Demographics Checklist & Security Guarantee -->
            <div class="col-lg-4">
                <!-- Read-Only Account Identity Card -->
                <div class="card card-vy border-0 shadow-sm p-4 mb-4 bg-white">
                    <h6 class="fw-bold text-dark mb-3 d-flex align-items-center gap-2">
                        <i class="bi bi-shield-check text-primary"></i>
                        <span>Account Verification Info</span>
                    </h6>

                    <ul class="list-group list-group-flush small">
                        <li class="list-group-item px-0 py-2.5 d-flex justify-content-between align-items-center bg-transparent">
                            <span class="text-muted">Member Identifier:</span>
                            <code class="text-dark fw-bold">#VY-<?= str_pad((string)$user['id'], 6, '0', STR_PAD_LEFT) ?></code>
                        </li>
                        <li class="list-group-item px-0 py-2.5 d-flex justify-content-between align-items-center bg-transparent">
                            <span class="text-muted">Registration Date:</span>
                            <span class="text-dark fw-semibold"><?= date('F j, Y', strtotime($user['created_at'])) ?></span>
                        </li>
                        <li class="list-group-item px-0 py-2.5 d-flex justify-content-between align-items-center bg-transparent">
                            <span class="text-muted">Panelist Status:</span>
                            <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2.5 py-1">
                                <i class="bi bi-patch-check-fill me-1"></i> Active Verified
                            </span>
                        </li>
                        <li class="list-group-item px-0 py-2.5 d-flex justify-content-between align-items-center bg-transparent">
                            <span class="text-muted">Surveys Completed:</span>
                            <span class="badge bg-primary-subtle text-primary rounded-pill px-2.5 py-1 fw-bold">
                                <?= (int)$user['completed_surveys_count'] ?> studies
                            </span>
                        </li>
                    </ul>

                    <div class="pt-3 border-top mt-2">
                        <a href="<?= BASE_URL ?>/member/settings.php?tab=email" class="btn btn-outline-primary w-100 rounded-3 py-2 small fw-semibold">
                            <i class="bi bi-envelope me-1"></i> Manage Email & Security
                        </a>
                    </div>
                </div>

                <!-- Demographics Checklist Guidance Card -->
                <div class="card card-vy border-0 shadow-sm p-4 mb-4 bg-white">
                    <h6 class="fw-bold text-dark mb-2">Survey Qualification Checklist</h6>
                    <p class="text-muted small mb-3">Complete all demographic milestones to unlock the highest compensation tiers.</p>

                    <ul class="list-unstyled space-y-2.5 small mb-0">
                        <li class="d-flex align-items-center gap-2 mb-2">
                            <i class="bi <?= !empty($user['first_name']) && !empty($user['last_name']) ? 'bi-check-circle-fill text-success' : 'bi-circle text-muted' ?>"></i>
                            <span class="<?= !empty($user['first_name']) ? 'text-dark fw-semibold' : 'text-muted' ?>">Legal Identity & Name</span>
                        </li>
                        <li class="d-flex align-items-center gap-2 mb-2">
                            <i class="bi <?= !empty($user['phone']) ? 'bi-check-circle-fill text-success' : 'bi-circle text-muted' ?>"></i>
                            <span class="<?= !empty($user['phone']) ? 'text-dark fw-semibold' : 'text-muted' ?>">Mobile Phone Verification</span>
                        </li>
                        <li class="d-flex align-items-center gap-2 mb-2">
                            <i class="bi <?= !empty($user['city']) || !empty($user['state']) ? 'bi-check-circle-fill text-success' : 'bi-circle text-muted' ?>"></i>
                            <span class="<?= !empty($user['city']) ? 'text-dark fw-semibold' : 'text-muted' ?>">City & State Geolocation</span>
                        </li>
                        <li class="d-flex align-items-center gap-2 mb-2">
                            <i class="bi <?= !empty($user['date_of_birth']) ? 'bi-check-circle-fill text-success' : 'bi-circle text-muted' ?>"></i>
                            <span class="<?= !empty($user['date_of_birth']) ? 'text-dark fw-semibold' : 'text-muted' ?>">Date of Birth & Age Bracket</span>
                        </li>
                        <li class="d-flex align-items-center gap-2 mb-2">
                            <i class="bi <?= !empty($user['employment_status']) ? 'bi-check-circle-fill text-success' : 'bi-circle text-muted' ?>"></i>
                            <span class="<?= !empty($user['employment_status']) ? 'text-dark fw-semibold' : 'text-muted' ?>">Employment & Occupation</span>
                        </li>
                        <li class="d-flex align-items-center gap-2">
                            <i class="bi <?= !empty($user['annual_income']) ? 'bi-check-circle-fill text-success' : 'bi-circle text-muted' ?>"></i>
                            <span class="<?= !empty($user['annual_income']) ? 'text-dark fw-semibold' : 'text-muted' ?>">Annual Household Income</span>
                        </li>
                    </ul>
                </div>

                <!-- Data Privacy Guarantee -->
                <div class="card card-vy border-0 shadow-sm p-4 bg-light border-light-subtle">
                    <h6 class="fw-bold text-dark mb-1">
                        <i class="bi bi-lock-fill text-success me-1"></i> Data Protection
                    </h6>
                    <p class="text-muted small mb-0">
                        Veritolytics never sells your individual identity. Your demographic attributes are anonymized and aggregated into statistical survey samples in compliance with ESOMAR guidelines.
                    </p>
                </div>
            </div>
        </div>

<?php require_once __DIR__ . '/../includes/member_footer.php'; ?>
