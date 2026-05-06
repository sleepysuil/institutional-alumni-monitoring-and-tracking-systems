<?php
require_once '../includes/admin_header.php';
$id = $_GET['id'] ?? 0;
$alumni = $pdo->prepare("SELECT * FROM alumni WHERE id = ?");
$alumni->execute([$id]);
$alumni = $alumni->fetch();
if (!$alumni) redirect('alumni_directory.php');

// Get latest tracer survey
$latest_tracer = $pdo->prepare("
    SELECT t.* 
    FROM tracer_responses t
    WHERE t.alumni_id = ?
    ORDER BY t.response_date DESC
    LIMIT 1
");
$latest_tracer->execute([$id]);
$tracer = $latest_tracer->fetch();
$employment_status = $tracer ? ($tracer['is_employed'] == 1 ? 'Employed' : ($tracer['is_employed'] == 0 ? 'Unemployed' : 'Self-Employed')) : 'No Survey';
$survey_data = $tracer ? json_decode($tracer['survey_data'], true) : null;
?>
<div class="page-header">
    <h1>Alumni Details: <?= htmlspecialchars($alumni['first_name'] . ' ' . $alumni['last_name']) ?></h1>
    <a href="alumni_directory.php" class="btn btn-outline"><i class="fas fa-arrow-left me-2"></i>Back</a>
</div>

<div class="row g-4">
    <div class="col-md-4">
        <div class="card">
            <div class="card-body text-center">
                <?php if ($alumni['profile_pic'] && file_exists('../' . $alumni['profile_pic'])): ?>
                    <img src="<?= SITE_URL ?>/<?= $alumni['profile_pic'] ?>" alt="Profile" class="rounded-circle mb-3" style="width: 150px; height: 150px; object-fit: cover;">
                <?php else: ?>
                    <i class="fas fa-user-circle fa-5x mb-3" style="color: var(--primary);"></i>
                <?php endif; ?>
                <h5><?= htmlspecialchars($alumni['first_name'] . ' ' . $alumni['last_name']) ?></h5>
                <p class="text-muted"><?= $alumni['student_id'] ?></p>
                <hr>
                <p><strong>Program:</strong> <?= htmlspecialchars($alumni['program']) ?></p>
                <p><strong>Graduated:</strong> <?= $alumni['graduation_year'] ?></p>
                <p><strong>Email:</strong> <?= $alumni['email'] ?></p>
                <p><strong>Phone:</strong> <?= $alumni['phone'] ?></p>
                <p><strong>Employment Status (from latest survey):</strong> 
                    <span class="badge 
                        <?= $employment_status == 'Employed' ? 'bg-success' : ($employment_status == 'Unemployed' ? 'bg-danger' : ($employment_status == 'Self-Employed' ? 'bg-info' : 'bg-secondary')) ?>">
                        <?= $employment_status ?>
                    </span>
                </p>
            </div>
        </div>
    </div>
    <div class="col-md-8">
        <!-- Current Employment (from `employment` table) – we can keep it but it may be empty now -->
        <div class="card mb-4">
            <div class="card-header">Current Employment (from Employment Table)</div>
            <div class="card-body">
                <?php
                $employment = $pdo->prepare("SELECT * FROM employment WHERE alumni_id = ?");
                $employment->execute([$id]);
                $employment = $employment->fetch();
                ?>
                <?php if ($employment): ?>
                <div class="row">
                    <div class="col-sm-6"><strong>Status:</strong> <span class="badge <?= strtolower(str_replace(' ', '-', $employment['status'])) ?>"><?= $employment['status'] ?></span></div>
                    <div class="col-sm-6"><strong>Company:</strong> <?= htmlspecialchars($employment['company'] ?? 'N/A') ?></div>
                    <div class="col-sm-6 mt-2"><strong>Position:</strong> <?= htmlspecialchars($employment['position'] ?? 'N/A') ?></div>
                    <div class="col-sm-6 mt-2"><strong>Industry:</strong> <?= htmlspecialchars($employment['industry'] ?? 'N/A') ?></div>
                    <div class="col-sm-6 mt-2"><strong>Salary:</strong> <?= htmlspecialchars($employment['salary_range'] ?? 'N/A') ?></div>
                    <div class="col-sm-6 mt-2"><strong>Relevance:</strong> <?= $employment['relevance'] ?? 'N/A' ?></div>
                    <div class="col-sm-6 mt-2"><strong>Start Date:</strong> <?= $employment['start_date'] ?? 'N/A' ?></div>
                </div>
                <?php else: ?>
                <p class="text-muted">No current employment data in employment table.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Latest Tracer Survey Card -->
        <div class="card mb-4">
            <div class="card-header">Latest Tracer Survey</div>
            <div class="card-body">
                <?php if ($tracer): ?>
                    <p><strong>Survey Date:</strong> <?= $tracer['response_date'] ?></p>
                    <p><strong>Employment Status:</strong> 
                        <span class="badge 
                            <?= $tracer['is_employed'] == 1 ? 'bg-success' : ($tracer['is_employed'] == 0 ? 'bg-danger' : 'bg-info') ?>">
                            <?= $tracer['is_employed'] == 1 ? 'Employed' : ($tracer['is_employed'] == 0 ? 'Unemployed' : 'Self-Employed') ?>
                        </span>
                    </p>
                    <?php if ($tracer['is_employed'] == 1): ?>
                        <p><strong>Job Title:</strong> <?= htmlspecialchars($tracer['job_title'] ?? '') ?></p>
                        <p><strong>Company:</strong> <?= htmlspecialchars($tracer['company'] ?? '') ?></p>
                        <p><strong>Industry:</strong> <?= htmlspecialchars($tracer['industry'] ?? '') ?></p>
                        <p><strong>Relevance:</strong> <?= htmlspecialchars($tracer['relevance'] ?? '') ?></p>
                        <?php if ($survey_data): ?>
                            <hr>
                            <h6>Additional Details:</h6>
                            <p><strong>Job Category:</strong> <?= $survey_data['job_category'] ?? 'N/A' ?></p>
                            <p><strong>Job Title/Description:</strong> <?= $survey_data['job_title_desc'] ?? 'N/A' ?></p>
                            <p><strong>Employer Name:</strong> <?= $survey_data['employer_name'] ?? 'N/A' ?></p>
                            <p><strong>Employment Date:</strong> <?= $survey_data['employment_date'] ?? 'N/A' ?></p>
                            <p><strong>Location:</strong> <?= ($survey_data['region'] ?? '') . ($survey_data['city'] ? ', ' . $survey_data['city'] : '') ?></p>
                            <p><strong>Time to First Job:</strong> <?= $survey_data['time_to_first_job'] ?? 'N/A' ?></p>
                            <p><strong>Internship Helpfulness:</strong> <?= $survey_data['internship_helpfulness'] ?? 'N/A' ?></p>
                            <p><strong>Networking Participation:</strong> <?= $survey_data['networking_participation'] ?? 'N/A' ?></p>
                            <p><strong>Critical Skills:</strong> <?= implode(', ', array_filter($survey_data['skills_critical'] ?? [])) ?></p>
                        <?php endif; ?>
                    <?php elseif ($tracer['is_employed'] == 0): ?>
                        <?php if ($survey_data): ?>
                            <p><strong>Seeking Time:</strong> <?= $survey_data['seeking_time'] ?? 'N/A' ?></p>
                            <p><strong>Interviews:</strong> <?= $survey_data['interviews'] ?? 'N/A' ?></p>
                            <p><strong>Employer Feedback:</strong> <?= $survey_data['employer_feedback'] ?? 'N/A' ?></p>
                            <p><strong>Location Factor:</strong> <?= $survey_data['location_factor'] ?? 'N/A' ?></p>
                            <p><strong>Impact Factor:</strong> <?= $survey_data['impact_factor'] ?? 'N/A' ?></p>
                            <p><strong>Other Impact:</strong> <?= $survey_data['other_impact'] ?? 'N/A' ?></p>
                            <p><strong>Skill Development:</strong> <?= $survey_data['skill_development'] ?? 'N/A' ?></p>
                            <p><strong>Job Search Experience:</strong> <?= $survey_data['job_search_experience'] ?? 'N/A' ?></p>
                            <p><strong>Career Resources:</strong> <?= $survey_data['career_resources'] ?? 'N/A' ?></p>
                        <?php endif; ?>
                    <?php elseif ($tracer['is_employed'] == 2): ?>
                        <?php if ($survey_data): ?>
                            <p><strong>Business Name:</strong> <?= $survey_data['business_name'] ?? 'N/A' ?></p>
                            <p><strong>Business Type:</strong> <?= $survey_data['business_type'] ?? 'N/A' ?></p>
                            <p><strong>Industry:</strong> <?= $survey_data['business_industry'] ?? 'N/A' ?></p>
                            <p><strong>Number of Employees:</strong> <?= $survey_data['business_size'] ?? 'N/A' ?></p>
                            <p><strong>Start Date:</strong> <?= $survey_data['business_start_date'] ?? 'N/A' ?></p>
                        <?php endif; ?>
                    <?php endif; ?>
                <?php else: ?>
                    <p class="text-muted">No tracer survey completed yet.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Employment History (unchanged) -->
        <?php
        $history = $pdo->prepare("SELECT * FROM employment_history WHERE alumni_id = ? ORDER BY start_date DESC");
        $history->execute([$id]);
        $history = $history->fetchAll();
        ?>
        <?php if (count($history) > 0): ?>
        <div class="card">
            <div class="card-header">Employment History</div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead> <tr><th>Company</th><th>Position</th><th>Start</th><th>End</th></tr> </thead>
                        <tbody>
                            <?php foreach ($history as $h): ?>
                            <tr>
                                <td><?= htmlspecialchars($h['company']) ?></td>
                                <td><?= htmlspecialchars($h['position']) ?></td>
                                <td><?= $h['start_date'] ?></td>
                                <td><?= $h['end_date'] ?? 'Present' ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php include '../includes/footer.php'; ?>