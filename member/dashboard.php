<?php
/**
 * Veritolytics - Member Dashboard Overview
 * Pure Core PHP 8.2+ Architecture
 */

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_check.php';

$user = require_login();
$pageTitle = 'Dashboard Overview';
$db = get_db();

// 1. Fetch available surveys (not yet completed by this user)
$activeSurveys = [];
try {
    $surveysStmt = $db->prepare("
        SELECT s.*, 
               (SELECT COUNT(*) FROM survey_responses sr WHERE sr.survey_id = s.id AND sr.user_id = :uid) as is_completed
        FROM surveys s
        WHERE s.status = 'active'
        ORDER BY s.reward_points DESC
        LIMIT 4
    ");
    $surveysStmt->execute([':uid' => $user['id']]);
    $activeSurveys = $surveysStmt->fetchAll() ?: [];
} catch (Throwable $e) {
    $activeSurveys = [];
}

// 2. Fetch recent ledger transactions
$recentTransactions = [];
try {
    $txStmt = $db->prepare("
        SELECT id, type, title, points, amount_usd, status, created_at 
        FROM transactions 
        WHERE user_id = :uid 
        ORDER BY created_at DESC 
        LIMIT 5
    ");
    $txStmt->execute([':uid' => $user['id']]);
    $recentTransactions = $txStmt->fetchAll() ?: [];
} catch (Throwable $e) {
    $recentTransactions = [];
}

// 3. Profile completion percentage
$profilePct = $user['profile_completion_pct'] ?? calculate_profile_completion($user);

require_once __DIR__ . '/../includes/member_header.php';
?>

<div class="portal-wrapper">
    <?php require_once __DIR__ . '/../includes/member_sidebar.php'; ?>

    <div class="portal-main">
        <?= render_flash() ?>

        <!-- Welcome Banner (Premium Light Theme) -->
        <div class="card border-0 rounded-4 p-4 p-md-5 mb-4 shadow-sm position-relative overflow-hidden" style="background: linear-gradient(135deg, #FFFFFF 0%, #EFF6FF 50%, #F5F3FF 100%); border: 1px solid var(--vy-border) !important;">
            <div class="row align-items-center position-relative" style="z-index: 2;">
                <div class="col-lg-8">
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-3 py-1 mb-2 fw-semibold" style="font-size: 0.75rem;">
                        <i class="bi bi-patch-check-fill me-1"></i> Verified Research Member
                    </span>
                    <h2 class="fw-bold mb-2 text-dark">Welcome back, <?= e($user['first_name']) ?>!</h2>
                    <p class="text-muted mb-4 mb-lg-0 leading-relaxed" style="max-width: 580px;">
                        You have active research studies matched to your demographic profile. Complete surveys to earn verified points redeemable for PayPal cash and digital gift cards.
                    </p>
                </div>
                <div class="col-lg-4 text-lg-end">
                    <a href="<?= BASE_URL ?>/member/surveys.php" class="btn btn-brand-primary btn-lg rounded-3 px-4 py-2.5 shadow-sm fw-bold">
                        <i class="bi bi-play-circle-fill me-1"></i> Start Surveys
                    </a>
                </div>
            </div>
        </div>

        <!-- 4 Metric KPI Cards (Soft Icon Backgrounds) -->
        <div class="row g-3 mb-4">
            <div class="col-xl-3 col-sm-6">
                <div class="metric-card">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted small fw-semibold">Available Points</span>
                        <div class="metric-icon icon-box-yellow">
                            <i class="bi bi-coin"></i>
                        </div>
                    </div>
                    <div class="metric-value"><?= number_format($user['balance_points']) ?></div>
                    <div class="small text-muted mt-1">
                        ≈ <strong class="text-dark"><?= format_currency($user['balance_points'] / POINTS_PER_USD) ?></strong> USD
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-sm-6">
                <div class="metric-card">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted small fw-semibold">Lifetime Earnings</span>
                        <div class="metric-icon icon-box-green">
                            <i class="bi bi-cash-stack"></i>
                        </div>
                    </div>
                    <div class="metric-value"><?= format_currency($user['lifetime_earnings_usd']) ?></div>
                    <div class="small text-muted mt-1">
                        Total earned to date
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-sm-6">
                <div class="metric-card">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted small fw-semibold">Completed Surveys</span>
                        <div class="metric-icon icon-box-blue">
                            <i class="bi bi-check2-circle"></i>
                        </div>
                    </div>
                    <div class="metric-value"><?= number_format($user['completed_surveys_count']) ?></div>
                    <div class="small text-muted mt-1">
                        Verified studies completed
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-sm-6">
                <div class="metric-card">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted small fw-semibold">Profile Strength</span>
                        <div class="metric-icon icon-box-purple">
                            <i class="bi bi-person-lines-fill"></i>
                        </div>
                    </div>
                    <div class="metric-value"><?= $profilePct ?>%</div>
                    <div class="progress mt-2" style="height: 6px;">
                        <div class="progress-bar bg-gradient-royal" role="progressbar" style="width: <?= $profilePct ?>%;" aria-valuenow="<?= $profilePct ?>" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Surveys and Activity Columns -->
        <div class="row g-4 mb-4">
            <!-- Active Surveys Priority Grid -->
            <div class="col-lg-8">
                <div class="card card-vy border-0 shadow-sm p-4 h-100">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div>
                            <h5 class="fw-bold mb-0 text-dark">Recommended Surveys</h5>
                            <small class="text-muted">High-priority research cohorts awaiting your input</small>
                        </div>
                        <a href="<?= BASE_URL ?>/member/surveys.php" class="text-primary fw-semibold small text-decoration-none">
                            View All (<?= count($activeSurveys) ?>) →
                        </a>
                    </div>

                    <?php if (empty($activeSurveys)): ?>
                        <div class="text-center py-5 text-muted">
                            <i class="bi bi-clipboard-x fs-1 text-secondary opacity-50 mb-2"></i>
                            <p class="mb-0">No open surveys currently match your profile. Check back shortly!</p>
                        </div>
                    <?php else: ?>
                        <div class="row g-3">
                            <?php foreach ($activeSurveys as $s): ?>
                                <div class="col-md-6">
                                    <div class="card h-100 border rounded-4 p-3 d-flex flex-column justify-content-between hover-shadow">
                                        <div>
                                            <div class="d-flex align-items-center justify-content-between mb-2">
                                                <span class="badge bg-light text-secondary border small rounded-pill">
                                                    <?= e($s['category']) ?>
                                                </span>
                                                <span class="fw-bold text-warning small">
                                                    <i class="bi bi-coin"></i> +<?= number_format($s['reward_points']) ?> pts
                                                </span>
                                            </div>
                                            <h6 class="fw-bold text-dark mb-1"><?= e($s['title']) ?></h6>
                                            <p class="text-muted small mb-3" style="font-size: 0.8rem; line-height: 1.4;">
                                                <?= e(mb_strimwidth($s['description'] ?? '', 0, 90, '...')) ?>
                                            </p>
                                        </div>

                                        <div class="d-flex align-items-center justify-content-between pt-2 border-top">
                                            <small class="text-muted">
                                                <i class="bi bi-clock me-1"></i>~<?= $s['duration_minutes'] ?> mins
                                            </small>
                                            <?php if ($s['is_completed']): ?>
                                                <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2.5 py-1">
                                                    <i class="bi bi-check-circle-fill me-1"></i> Completed
                                                </span>
                                            <?php else: ?>
                                                <a href="<?= BASE_URL ?>/member/surveys.php?survey_id=<?= $s['id'] ?>" class="btn btn-sm btn-brand-primary rounded-pill px-3">
                                                    Take Survey
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Profile Completion & Quick Links -->
            <div class="col-lg-4">
                <div class="card card-vy border-0 shadow-sm p-4 mb-4">
                    <h6 class="fw-bold text-dark mb-2">Demographic Profile</h6>
                    <p class="text-muted small mb-3">Complete your demographics to unlock higher-paying surveys.</p>
                    
                    <div class="d-flex align-items-center justify-content-between small text-muted mb-1">
                        <span>Profile Status</span>
                        <strong class="text-dark"><?= $profilePct ?>%</strong>
                    </div>
                    <div class="progress mb-3" style="height: 8px;">
                        <div class="progress-bar bg-gradient-royal" style="width: <?= $profilePct ?>%;"></div>
                    </div>

                    <a href="<?= BASE_URL ?>/member/profile.php" class="btn btn-outline-primary rounded-3 w-100 btn-sm fw-semibold">
                        <i class="bi bi-pencil-square me-1"></i> Update Demographics
                    </a>
                </div>

                <!-- Recent Activity Feed -->
                <div class="card card-vy border-0 shadow-sm p-4">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <h6 class="fw-bold text-dark mb-0">Recent Activity</h6>
                        <a href="<?= BASE_URL ?>/member/wallet.php" class="small text-primary text-decoration-none">Ledger →</a>
                    </div>

                    <?php if (empty($recentTransactions)): ?>
                        <p class="text-muted small text-center my-3">No activity recorded yet.</p>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($recentTransactions as $tx): ?>
                                <div class="list-group-item px-0 py-2 border-bottom-0 d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="fw-semibold small text-dark text-truncate" style="max-width: 170px;">
                                            <?= e($tx['title']) ?>
                                        </div>
                                        <small class="text-muted" style="font-size: 0.725rem;"><?= time_ago($tx['created_at']) ?></small>
                                    </div>
                                    <span class="fw-bold small <?= $tx['points'] >= 0 ? 'text-success' : 'text-danger' ?>">
                                        <?= $tx['points'] >= 0 ? '+' : '' ?><?= number_format($tx['points']) ?> pts
                                    </span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

<?php require_once __DIR__ . '/../includes/member_footer.php'; ?>
