<?php
/**
 * Veritolytics - Surveys Management Console
 * Pure Core PHP 8.2+ Architecture
 */

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/auth_check.php';

$admin = require_admin();
$pageTitle = 'Surveys Management';
$db = get_db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        flash('danger', 'Security session expired.');
        redirect('/admin/surveys.php');
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'create_survey') {
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $category = trim($_POST['category'] ?? 'Consumer Insights');
        $rewardPoints = (int)($_POST['reward_points'] ?? 100);
        $rewardUsd = (float)($_POST['reward_usd'] ?? 1.00);
        $duration = (int)($_POST['duration_minutes'] ?? 5);
        $quota = (int)($_POST['quota'] ?? 100);
        $provider = trim($_POST['provider'] ?? 'Veritolytics Direct');

        if (empty($title)) {
            flash('danger', 'Survey Title is required.');
        } else {
            $defaultQuestions = [
                ['q' => 'How satisfied are you with digital services in this field?', 'type' => 'radio', 'options' => ['Very Satisfied', 'Satisfied', 'Neutral', 'Dissatisfied']],
                ['q' => 'Would you recommend this brand/solution to family or peers?', 'type' => 'radio', 'options' => ['Definitely', 'Likely', 'Unsure', 'No']]
            ];

            $ins = $db->prepare("
                INSERT INTO surveys (title, description, category, reward_points, reward_usd, duration_minutes, quota, status, provider, sample_questions_json)
                VALUES (:title, :description, :category, :reward_points, :reward_usd, :duration, :quota, 'active', :provider, :questions)
            ");
            $ins->execute([
                ':title' => $title,
                ':description' => $description,
                ':category' => $category,
                ':reward_points' => $rewardPoints,
                ':reward_usd' => $rewardUsd,
                ':duration' => $duration,
                ':quota' => $quota,
                ':provider' => $provider,
                ':questions' => json_encode($defaultQuestions)
            ]);

            flash('success', "New survey '{$title}' created and published successfully.");
        }
    } elseif ($action === 'toggle_status') {
        $sid = (int)($_POST['survey_id'] ?? 0);
        $newStatus = ($_POST['current_status'] === 'active') ? 'paused' : 'active';
        $up = $db->prepare("UPDATE surveys SET status = :st WHERE id = :id");
        $up->execute([':st' => $newStatus, ':id' => $sid]);
        flash('success', "Survey #{$sid} status updated to {$newStatus}.");
    } elseif ($action === 'delete_survey') {
        $sid = (int)($_POST['survey_id'] ?? 0);
        $del = $db->prepare("DELETE FROM surveys WHERE id = :id");
        $del->execute([':id' => $sid]);
        flash('warning', "Survey #{$sid} deleted permanently.");
    }

    redirect('/admin/surveys.php');
}

$surveys = $db->query("SELECT * FROM surveys ORDER BY created_at DESC")->fetchAll();

require_once __DIR__ . '/../includes/admin_header.php';
?>

<div class="portal-wrapper">
    <?php require_once __DIR__ . '/../includes/admin_sidebar.php'; ?>

    <div class="portal-main">
        <?= render_flash() ?>

        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
            <div>
                <h4 class="fw-bold text-dark mb-1">Surveys & Studies Catalog</h4>
                <p class="text-muted small mb-0">Manage research cohorts, reward payouts, and response quota metrics.</p>
            </div>
            <button type="button" class="btn btn-danger rounded-pill px-3.5 py-2 fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#newSurveyModal">
                <i class="bi bi-plus-circle me-1"></i> Create New Study
            </button>
        </div>

        <!-- Surveys Table -->
        <div class="card card-vy border-0 shadow-sm p-4">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light small text-muted">
                        <tr>
                            <th>Survey Title</th>
                            <th>Category</th>
                            <th>Reward</th>
                            <th>Est. Time</th>
                            <th>Quota Progress</th>
                            <th>Status</th>
                            <th>Provider</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($surveys as $s): ?>
                            <tr>
                                <td>
                                    <div class="fw-bold text-dark small"><?= e($s['title']) ?></div>
                                    <small class="text-muted text-truncate d-block" style="max-width: 260px;">
                                        <?= e($s['description']) ?>
                                    </small>
                                </td>
                                <td>
                                    <span class="badge bg-light text-secondary border rounded-pill small">
                                        <?= e($s['category']) ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-warning-subtle text-dark border border-warning-subtle rounded-pill small fw-bold">
                                        +<?= number_format($s['reward_points']) ?> pts (<?= format_currency($s['reward_usd']) ?>)
                                    </span>
                                </td>
                                <td class="small text-muted text-nowrap">
                                    <?= $s['duration_minutes'] ?> mins
                                </td>
                                <td>
                                    <div class="small fw-semibold text-dark">
                                        <?= $s['completed_count'] ?> / <?= $s['quota'] ?>
                                    </div>
                                    <div class="progress mt-1" style="height: 5px; width: 100px;">
                                        <?php $pct = min(100, round(($s['completed_count'] / max(1, $s['quota'])) * 100)); ?>
                                        <div class="progress-bar bg-primary" style="width: <?= $pct ?>%;"></div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge <?= $s['status'] === 'active' ? 'bg-success-subtle text-success border-success-subtle' : 'bg-secondary-subtle text-secondary border-secondary-subtle' ?> border rounded-pill small fw-semibold text-capitalize">
                                        <?= e($s['status']) ?>
                                    </span>
                                </td>
                                <td class="small text-muted"><?= e($s['provider']) ?></td>
                                <td class="text-end">
                                    <div class="d-flex justify-content-end gap-1">
                                        <form method="POST" action="<?= BASE_URL ?>/admin/surveys.php" class="d-inline">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="toggle_status">
                                            <input type="hidden" name="survey_id" value="<?= $s['id'] ?>">
                                            <input type="hidden" name="current_status" value="<?= $s['status'] ?>">
                                            <button type="submit" class="btn btn-sm btn-light border rounded-pill px-2.5 py-1 small" title="Toggle Active/Paused">
                                                <i class="bi <?= $s['status'] === 'active' ? 'bi-pause-fill text-warning' : 'bi-play-fill text-success' ?>"></i>
                                            </button>
                                        </form>
                                        <form method="POST" action="<?= BASE_URL ?>/admin/surveys.php" class="d-inline" onsubmit="return confirm('Permanently delete this survey?');">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="delete_survey">
                                            <input type="hidden" name="survey_id" value="<?= $s['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger border-0 rounded-pill px-2.5 py-1 small">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Create New Survey Modal -->
        <div class="modal fade" id="newSurveyModal" tabindex="-1" aria-labelledby="newSurveyModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                    <div class="modal-header bg-light border-bottom py-3">
                        <h5 class="modal-title fw-bold text-dark mb-0" id="newSurveyModalLabel">Create New Research Survey</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <form method="POST" action="<?= BASE_URL ?>/admin/surveys.php">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="create_survey">

                        <div class="modal-body p-4 p-md-5">
                            <div class="mb-3">
                                <label class="form-label fw-semibold small text-muted">Study Title *</label>
                                <input type="text" name="title" class="form-control rounded-3" placeholder="e.g. Electric Vehicle Consumer Sentiments 2026" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold small text-muted">Research Description *</label>
                                <textarea name="description" class="form-control rounded-3" rows="3" placeholder="Brief outline of the study objectives and target demographic..." required></textarea>
                            </div>

                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold small text-muted">Category</label>
                                    <select name="category" class="form-select rounded-3">
                                        <option value="Technology">Technology</option>
                                        <option value="Consumer Insights" selected>Consumer Insights</option>
                                        <option value="Finance">Finance</option>
                                        <option value="Media & Entertainment">Media & Entertainment</option>
                                        <option value="Workplace">Workplace</option>
                                        <option value="Health & Wellness">Health & Wellness</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold small text-muted">Research Sponsor / Provider</label>
                                    <input type="text" name="provider" class="form-control rounded-3" value="Veritolytics Direct" required>
                                </div>
                            </div>

                            <div class="row g-3">
                                <div class="col-md-3">
                                    <label class="form-label fw-semibold small text-muted">Reward Points</label>
                                    <input type="number" name="reward_points" class="form-control rounded-3" value="250" step="10" required>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-semibold small text-muted">USD Value ($)</label>
                                    <input type="number" name="reward_usd" class="form-control rounded-3" value="2.50" step="0.05" required>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-semibold small text-muted">Est. Duration (Mins)</label>
                                    <input type="number" name="duration_minutes" class="form-control rounded-3" value="7" required>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-semibold small text-muted">Target Quota</label>
                                    <input type="number" name="quota" class="form-control rounded-3" value="200" required>
                                </div>
                            </div>
                        </div>

                        <div class="modal-footer border-top-0 pt-0 px-4 pb-4 d-flex justify-content-between">
                            <button type="button" class="btn btn-light rounded-3 px-3" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-brand-primary rounded-3 px-4 py-2 fw-bold shadow-sm">
                                Save & Publish Study
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
