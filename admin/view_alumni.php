<?php
require_once '../includes/admin_header.php';

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM alumni WHERE id = ?");
$stmt->execute([$id]);
$alumni = $stmt->fetch();
if (!$alumni) redirect('alumni_directory.php');

$e = fn($v) => htmlspecialchars((string)($v ?? ''));
// Survey answers can be strings or arrays; always print something safe
$sv = function ($key, $default = 'N/A') use (&$survey_data, $e) {
    $v = $survey_data[$key] ?? '';
    if (is_array($v)) $v = implode(', ', array_filter($v));
    return $e($v !== '' ? $v : $default);
};
$flagLabel = fn($f) => match((int)$f) { 1 => 'Employed', 2 => 'Self-Employed', default => 'Unemployed' };
$flagBadge = fn($f) => match((int)$f) { 1 => 'bg-success', 2 => 'bg-info', default => 'bg-danger' };

// All tracer responses, newest first (the first one is the "latest")
$stmt = $pdo->prepare("SELECT * FROM tracer_responses WHERE alumni_id = ? ORDER BY response_date DESC, id DESC");
$stmt->execute([$id]);
$responses = $stmt->fetchAll();
$tracer = $responses[0] ?? null;
$employment_status = $tracer ? $flagLabel($tracer['is_employed']) : 'No Survey';
$status_badge = $tracer ? $flagBadge($tracer['is_employed']) : 'bg-secondary';
$survey_data = ($tracer && !empty($tracer['survey_data'])) ? (json_decode($tracer['survey_data'], true) ?: null) : null;

$stmt = $pdo->prepare("SELECT * FROM employment WHERE alumni_id = ?");
$stmt->execute([$id]);
$employment = $stmt->fetch();

$stmt = $pdo->prepare("SELECT * FROM employment_history WHERE alumni_id = ? ORDER BY start_date DESC");
$stmt->execute([$id]);
$history = $stmt->fetchAll();

$stmt = $pdo->prepare("SELECT a.applied_date, a.status, j.title, j.company
                       FROM applications a JOIN job_postings j ON a.job_id = j.id
                       WHERE a.alumni_id = ? ORDER BY a.applied_date DESC");
$stmt->execute([$id]);
$applications = $stmt->fetchAll();
$app_badge = ['pending' => 'warning', 'reviewed' => 'info', 'accepted' => 'success', 'rejected' => 'danger'];

$full_name = trim($alumni['first_name'] . ' ' . (!empty($alumni['middle_name']) ? $alumni['middle_name'] . ' ' : '') . $alumni['last_name']);
$approval = $alumni['approval_status'] ?? 'pending';
?>
<div class="page-header">
    <h1>Alumni Details: <?= $e($alumni['first_name'] . ' ' . $alumni['last_name']) ?></h1>
    <a href="alumni_directory.php" class="btn btn-outline"><i class="fas fa-arrow-left me-2"></i>Back</a>
</div>

<div class="row g-4">
    <div class="col-md-4">
        <div class="card">
            <div class="card-body text-center">
                <?php if (!empty($alumni['profile_pic']) && file_exists('../' . $alumni['profile_pic'])): ?>
                    <img src="<?= SITE_URL ?>/<?= $e($alumni['profile_pic']) ?>" alt="Profile" class="rounded-circle mb-3" style="width: 150px; height: 150px; object-fit: cover;">
                <?php else: ?>
                    <i class="fas fa-user-circle fa-5x mb-3" style="color: var(--primary);"></i>
                <?php endif; ?>
                <h5><?= $e($full_name) ?></h5>
                <p class="text-muted"><?= $e($alumni['student_id']) ?></p>
                <span class="badge bg-<?= $approval === 'approved' ? 'success' : ($approval === 'rejected' ? 'danger' : 'warning text-dark') ?>">Registration: <?= $e(ucfirst($approval)) ?></span>
                <hr>
                <div class="text-start">
                    <p><strong>Program:</strong> <?= $e($alumni['program']) ?></p>
                    <p><strong>Graduated:</strong> <?= (int)$alumni['graduation_year'] ?></p>
                    <?php if (!empty($alumni['age'])): ?><p><strong>Age:</strong> <?= (int)$alumni['age'] ?></p><?php endif; ?>
                    <?php if (!empty($alumni['gender'])): ?><p><strong>Gender:</strong> <?= $e($alumni['gender']) ?></p><?php endif; ?>
                    <p><strong>Email:</strong> <?= $e($alumni['email']) ?></p>
                    <p><strong>Phone:</strong> <?= $e($alumni['phone']) ?></p>
                    <p class="mb-0"><strong>Survey status:</strong>
                        <span class="badge <?= $status_badge ?>"><?= $e($employment_status) ?></span>
                    </p>
                </div>
                <div class="d-flex gap-2 justify-content-center mt-3 flex-wrap">
                    <?php if (!empty($alumni['email'])): ?><a href="mailto:<?= $e($alumni['email']) ?>" class="btn btn-sm btn-outline-primary"><i class="fas fa-envelope"></i> Email</a><?php endif; ?>
                    <?php if (!empty($alumni['phone'])): ?><a href="tel:<?= $e(preg_replace('/[^\d+]/', '', $alumni['phone'])) ?>" class="btn btn-sm btn-outline-primary"><i class="fas fa-phone"></i> Call</a><?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <!-- Current Employment -->
        <div class="card mb-4">
            <div class="card-header">Current Employment (from Alumni Profile)</div>
            <div class="card-body">
                <?php if ($employment): ?>
                <div class="row">
                    <div class="col-sm-6"><strong>Status:</strong> <span class="badge <?= $e(strtolower(str_replace(' ', '-', $employment['status']))) ?>"><?= $e($employment['status']) ?></span></div>
                    <div class="col-sm-6"><strong>Company:</strong> <?= $e($employment['company'] ?? 'N/A') ?></div>
                    <div class="col-sm-6 mt-2"><strong>Position:</strong> <?= $e($employment['position'] ?? 'N/A') ?></div>
                    <div class="col-sm-6 mt-2"><strong>Industry:</strong> <?= $e($employment['industry'] ?? 'N/A') ?></div>
                    <div class="col-sm-6 mt-2"><strong>Salary:</strong> <?= $e($employment['salary_range'] ?? 'N/A') ?></div>
                    <div class="col-sm-6 mt-2"><strong>Relevance:</strong> <?= $e($employment['relevance'] ?? 'N/A') ?></div>
                    <div class="col-sm-6 mt-2"><strong>Start Date:</strong> <?= $employment['start_date'] ? date('M j, Y', strtotime($employment['start_date'])) : 'N/A' ?></div>
                </div>
                <?php else: ?>
                    <p class="text-muted mb-0">The alumnus has not filled in the employment section of their profile.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Latest Tracer Survey -->
        <div class="card mb-4">
            <div class="card-header">Latest Tracer Survey</div>
            <div class="card-body">
                <?php if ($tracer): ?>
                    <p><strong>Survey Date:</strong> <?= $tracer['response_date'] ? date('M j, Y', strtotime($tracer['response_date'])) : 'N/A' ?></p>
                    <p><strong>Employment Status:</strong> <span class="badge <?= $status_badge ?>"><?= $e($employment_status) ?></span></p>

                    <?php if ((int)$tracer['is_employed'] === 1): ?>
                        <p><strong>Job Title:</strong> <?= $e($tracer['job_title']) ?></p>
                        <p><strong>Company:</strong> <?= $e($tracer['company']) ?></p>
                        <p><strong>Industry:</strong> <?= $e($tracer['industry']) ?></p>
                        <p><strong>Relevance:</strong> <?= $e($tracer['relevance']) ?></p>
                        <?php if ($survey_data): ?>
                            <hr><h6>Additional Details</h6>
                            <p><strong>Job Category:</strong> <?= $sv('job_category') ?></p>
                            <p><strong>Job Title/Description:</strong> <?= $sv('job_title_desc') ?></p>
                            <p><strong>Employer Name:</strong> <?= $sv('employer_name') ?></p>
                            <p><strong>Employment Date:</strong> <?= $sv('employment_date') ?></p>
                            <p><strong>Location:</strong> <?= $e(trim(($survey_data['region'] ?? '') . (!empty($survey_data['city']) ? ', ' . $survey_data['city'] : ''), ', ') ?: 'N/A') ?></p>
                            <p><strong>Time to First Job:</strong> <?= $sv('time_to_first_job') ?></p>
                            <p><strong>Internship Helpfulness:</strong> <?= $sv('internship_helpfulness') ?></p>
                            <p><strong>Networking Participation:</strong> <?= $sv('networking_participation') ?></p>
                            <p><strong>Critical Skills:</strong> <?= $sv('skills_critical') ?></p>
                        <?php endif; ?>

                    <?php elseif ((int)$tracer['is_employed'] === 0 && $survey_data): ?>
                        <p><strong>Seeking Time:</strong> <?= $sv('seeking_time') ?></p>
                        <p><strong>Interviews:</strong> <?= $sv('interviews') ?></p>
                        <p><strong>Employer Feedback:</strong> <?= $sv('employer_feedback') ?></p>
                        <p><strong>Location Factor:</strong> <?= $sv('location_factor') ?></p>
                        <p><strong>Impact Factor:</strong> <?= $sv('impact_factor') ?></p>
                        <p><strong>Other Impact:</strong> <?= $sv('other_impact') ?></p>
                        <p><strong>Skill Development:</strong> <?= $sv('skill_development') ?></p>
                        <p><strong>Job Search Experience:</strong> <?= $sv('job_search_experience') ?></p>
                        <p><strong>Career Resources:</strong> <?= $sv('career_resources') ?></p>

                    <?php elseif ((int)$tracer['is_employed'] === 2 && $survey_data): ?>
                        <p><strong>Business Name:</strong> <?= $sv('business_name') ?></p>
                        <p><strong>Business Type:</strong> <?= $sv('business_type') ?></p>
                        <p><strong>Industry:</strong> <?= $sv('business_industry') ?></p>
                        <p><strong>Number of Employees:</strong> <?= $sv('business_size') ?></p>
                        <p><strong>Start Date:</strong> <?= $sv('business_start_date') ?></p>
                    <?php endif; ?>

                    <?php if (!empty($tracer['proof_file'])): ?>
                        <hr>
                        <a href="<?= SITE_URL ?>/<?= $e(ltrim($tracer['proof_file'], '/')) ?>" target="_blank" rel="noopener" class="btn btn-sm btn-outline-secondary"><i class="fas fa-paperclip me-1"></i>View proof of employment</a>
                    <?php endif; ?>
                <?php else: ?>
                    <p class="text-muted mb-0">No tracer survey completed yet.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Survey history -->
        <?php if (count($responses) > 1): ?>
        <div class="card mb-4">
            <div class="card-header">Survey History (<?= count($responses) ?> responses)</div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead><tr><th>Date</th><th>Status</th><th>Job / Company</th></tr></thead>
                        <tbody>
                        <?php foreach ($responses as $r): ?>
                            <tr>
                                <td><?= $r['response_date'] ? date('M j, Y', strtotime($r['response_date'])) : '' ?></td>
                                <td><span class="badge <?= $flagBadge($r['is_employed']) ?>"><?= $flagLabel($r['is_employed']) ?></span></td>
                                <td><?= $e(trim(($r['job_title'] ?? '') . ((!empty($r['job_title']) && !empty($r['company'])) ? ' at ' : '') . ($r['company'] ?? ''))) ?: '<span class="text-muted">-</span>' ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Employment History -->
        <?php if (count($history) > 0): ?>
        <div class="card mb-4">
            <div class="card-header">Employment History</div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead><tr><th>Company</th><th>Position</th><th>Start</th><th>End</th></tr></thead>
                        <tbody>
                            <?php foreach ($history as $h): ?>
                            <tr>
                                <td><?= $e($h['company']) ?></td>
                                <td><?= $e($h['position']) ?></td>
                                <td><?= $h['start_date'] ? date('M Y', strtotime($h['start_date'])) : '' ?></td>
                                <td><?= $h['end_date'] ? date('M Y', strtotime($h['end_date'])) : 'Present' ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Job applications -->
        <?php if ($applications): ?>
        <div class="card">
            <div class="card-header">Job Applications (<?= count($applications) ?>)</div>
            <ul class="list-group list-group-flush">
                <?php foreach ($applications as $ap): ?>
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    <span><?= $e($ap['title']) ?> <small class="text-muted">· <?= $e($ap['company']) ?> · <?= date('M j, Y', strtotime($ap['applied_date'])) ?></small></span>
                    <span class="badge bg-<?= $app_badge[$ap['status']] ?? 'secondary' ?>"><?= $e(ucfirst($ap['status'])) ?></span>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php include '../includes/footer.php'; ?>