<?php
/**
 * Veritolytics - Surveys Listing & Participation
 * Pure Core PHP 8.2+ Architecture
 */

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/auth_check.php';

$user = require_login();
$pageTitle = 'Available Surveys';
$db = get_db();

// Filter by category if requested
$category = trim($_GET['category'] ?? 'all');

$sql = "
    SELECT s.*, 
           (SELECT COUNT(*) FROM survey_responses sr WHERE sr.survey_id = s.id AND sr.user_id = :uid) as is_completed
    FROM surveys s
    WHERE s.status = 'active'
";
$params = [':uid' => $user['id']];

if ($category !== 'all' && !empty($category)) {
    $sql .= " AND s.category = :cat";
    $params[':cat'] = $category;
}

$sql .= " ORDER BY s.reward_points DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$surveys = $stmt->fetchAll();

// Check if a specific survey modal was triggered via ?survey_id=...
$triggerSurveyId = (int)($_GET['survey_id'] ?? 0);

require_once __DIR__ . '/../includes/member_header.php';
?>

<div class="portal-wrapper">
    <?php require_once __DIR__ . '/../includes/member_sidebar.php'; ?>

    <div class="portal-main">
        <?= render_flash() ?>

        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
            <div>
                <h4 class="fw-bold text-dark mb-1">Available Surveys & Studies</h4>
                <p class="text-muted small mb-0">Select an active research study to share your feedback and earn verified points.</p>
            </div>
            <div class="d-flex align-items-center gap-2">
                <span class="text-muted small">Available Balance:</span>
                <div class="header-wallet-badge">
                    <i class="bi bi-coin"></i>
                    <span><?= format_points($user['balance_points']) ?></span>
                </div>
            </div>
        </div>

        <!-- Category Filters -->
        <div class="d-flex gap-2 overflow-x-auto pb-2 mb-4">
            <?php
            $cats = [
                'all' => 'All Categories',
                'Technology' => 'Technology',
                'Consumer Insights' => 'Consumer Insights',
                'Finance' => 'Finance',
                'Media & Entertainment' => 'Media & Entertainment',
                'Workplace' => 'Workplace'
            ];
            ?>
            <?php foreach ($cats as $key => $label): ?>
                <a href="<?= BASE_URL ?>/member/surveys.php?category=<?= urlencode($key) ?>" class="btn btn-sm <?= $category === $key ? 'btn-brand-primary' : 'btn-outline-secondary bg-white' ?> rounded-pill px-3 py-1.5 fw-semibold text-nowrap">
                    <?= e($label) ?>
                </a>
            <?php endforeach; ?>
        </div>

        <!-- Survey Grid -->
        <?php if (empty($surveys)): ?>
            <div class="card card-vy border-0 shadow-sm p-5 text-center my-4">
                <i class="bi bi-search fs-1 text-muted mb-3"></i>
                <h5 class="fw-bold text-dark">No Surveys Found in this Category</h5>
                <p class="text-muted small mb-3">Try selecting 'All Categories' or check back later as new research cohorts open frequently.</p>
                <div>
                    <a href="<?= BASE_URL ?>/member/surveys.php" class="btn btn-brand-primary rounded-pill px-4">View All Surveys</a>
                </div>
            </div>
        <?php else: ?>
            <div class="row g-4">
                <?php foreach ($surveys as $s): ?>
                    <div class="col-lg-6">
                        <div class="card card-vy border-0 shadow-sm p-4 h-100 d-flex flex-column justify-content-between">
                            <div>
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill small fw-semibold px-2.5 py-1">
                                        <?= e($s['category']) ?>
                                    </span>
                                    <span class="badge bg-warning-subtle text-dark border border-warning-subtle rounded-pill px-2.5 py-1 fw-bold">
                                        <i class="bi bi-coin text-warning me-1"></i>+<?= number_format($s['reward_points']) ?> pts (<?= format_currency($s['reward_usd']) ?>)
                                    </span>
                                </div>

                                <h5 class="fw-bold text-dark mb-2"><?= e($s['title']) ?></h5>
                                <p class="text-muted small mb-3 leading-relaxed">
                                    <?= e($s['description']) ?>
                                </p>
                            </div>

                            <div>
                                <div class="row g-2 py-2 border-top border-bottom small text-muted mb-3">
                                    <div class="col-6">
                                        <i class="bi bi-clock me-1 text-primary"></i> Est. <?= $s['duration_minutes'] ?> Minutes
                                    </div>
                                    <div class="col-6">
                                        <i class="bi bi-building me-1 text-secondary"></i> <?= e($s['provider']) ?>
                                    </div>
                                </div>

                                <div class="d-flex align-items-center justify-content-between">
                                    <div class="small text-muted">
                                        Quota: <?= $s['completed_count'] ?> / <?= $s['quota'] ?> filled
                                    </div>
                                    <?php if ($s['is_completed']): ?>
                                        <button class="btn btn-sm btn-success rounded-pill px-3 py-1.5 fw-semibold" disabled>
                                            <i class="bi bi-check-circle-fill me-1"></i> Completed
                                        </button>
                                    <?php else: ?>
                                        <button type="button" class="btn btn-sm btn-brand-primary rounded-pill px-4 py-1.5 fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#surveyModal_<?= $s['id'] ?>">
                                            Take Survey →
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Survey Questions Modal for each survey -->
                    <?php if (!$s['is_completed']): ?>
                        <?php 
                            $questions = json_decode($s['sample_questions_json'] ?? '[]', true) ?: [
                                ['q' => 'How would you rate your overall satisfaction with current offerings in this industry?', 'type' => 'radio', 'options' => ['Very Satisfied', 'Somewhat Satisfied', 'Neutral', 'Dissatisfied']],
                                ['q' => 'How likely are you to recommend innovative solutions in this domain to colleagues?', 'type' => 'radio', 'options' => ['Extremely Likely', 'Likely', 'Neutral', 'Unlikely']]
                            ];
                        ?>
                        <div class="modal fade" id="surveyModal_<?= $s['id'] ?>" tabindex="-1" aria-labelledby="surveyModalLabel_<?= $s['id'] ?>" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered modal-lg">
                                <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                                    <div class="modal-header bg-light border-bottom py-3">
                                        <div>
                                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill small mb-1"><?= e($s['category']) ?></span>
                                            <h5 class="modal-title fw-bold text-dark mb-0" id="surveyModalLabel_<?= $s['id'] ?>"><?= e($s['title']) ?></h5>
                                        </div>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    
                                    <form method="POST" action="<?= BASE_URL ?>/member/take_survey.php">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="survey_id" value="<?= $s['id'] ?>">

                                        <div class="modal-body p-4 p-md-5">
                                            <div class="alert alert-primary bg-primary-subtle border-0 rounded-3 p-3 mb-4 d-flex align-items-center justify-content-between">
                                                <div class="small text-primary-emphasis">
                                                    <i class="bi bi-info-circle-fill me-1"></i> Answer all questions thoughtfully to ensure research validation.
                                                </div>
                                                <span class="badge bg-warning text-dark fw-bold rounded-pill">
                                                    +<?= number_format($s['reward_points']) ?> Points Reward
                                                </span>
                                            </div>

                                            <div class="space-y-4">
                                                <?php foreach ($questions as $qIndex => $q): ?>
                                                    <div class="card border rounded-3 p-3 mb-3 bg-white">
                                                        <label class="form-label fw-bold text-dark mb-3">
                                                            <span class="badge bg-primary rounded-circle me-1"><?= $qIndex + 1 ?></span>
                                                            <?= e($q['q']) ?>
                                                        </label>
                                                        <div class="space-y-2">
                                                            <?php foreach ($q['options'] as $oIndex => $option): ?>
                                                                <div class="form-check mb-2">
                                                                    <input class="form-check-input" type="radio" name="q_<?= $qIndex ?>" id="q_<?= $s['id'] ?>_<?= $qIndex ?>_<?= $oIndex ?>" value="<?= e($option) ?>" required>
                                                                    <label class="form-check-label text-dark small" for="q_<?= $s['id'] ?>_<?= $qIndex ?>_<?= $oIndex ?>">
                                                                        <?= e($option) ?>
                                                                    </label>
                                                                </div>
                                                            <?php endforeach; ?>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>

                                        <div class="modal-footer border-top-0 pt-0 px-4 pb-4 d-flex justify-content-between">
                                            <button type="button" class="btn btn-light rounded-3 px-3" data-bs-dismiss="modal">Cancel</button>
                                            <button type="submit" class="btn btn-brand-primary rounded-3 px-4 py-2 fw-bold shadow-sm">
                                                Submit Responses & Earn <?= number_format($s['reward_points']) ?> Pts
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if ($triggerSurveyId > 0): ?>
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    const el = document.getElementById('surveyModal_<?= $triggerSurveyId ?>');
                    if (el && window.bootstrap) {
                        const m = new bootstrap.Modal(el);
                        m.show();
                    }
                });
            </script>
        <?php endif; ?>

<?php require_once __DIR__ . '/../includes/member_footer.php'; ?>
