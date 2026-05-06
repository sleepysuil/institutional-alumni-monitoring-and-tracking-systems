<?php
require_once '../includes/admin_header.php';

$total_alumni = $pdo->query("SELECT COUNT(*) FROM alumni")->fetchColumn();
$employed = $pdo->query("SELECT COUNT(*) FROM employment WHERE status IN ('Employed','Self-Employed')")->fetchColumn();
$employment_rate = $total_alumni ? round(($employed/$total_alumni)*100,1) : 0;
$unemployed = $pdo->query("SELECT COUNT(*) FROM employment WHERE status='Unemployed'")->fetchColumn();
$job_relevance = $pdo->query("SELECT COUNT(*) FROM employment WHERE relevance='Highly Relevant'")->fetchColumn();
$relevance_rate = $employed ? round(($job_relevance/$employed)*100,1) : 0;
$years = $pdo->query("SELECT graduation_year, COUNT(*) as cnt FROM alumni GROUP BY graduation_year ORDER BY graduation_year DESC")->fetchAll();

$statuses = [
    'Employed' => $pdo->query("SELECT COUNT(*) FROM employment WHERE status='Employed'")->fetchColumn(),
    'Self-Employed' => $pdo->query("SELECT COUNT(*) FROM employment WHERE status='Self-Employed'")->fetchColumn(),
    'Unemployed' => $unemployed,
    'Pursuing Higher Education' => $pdo->query("SELECT COUNT(*) FROM employment WHERE status='Pursuing Higher Education'")->fetchColumn(),
];

// ========== Unemployed Analysis ==========
$responses = $pdo->query("
    SELECT survey_data
    FROM tracer_responses
    WHERE is_employed = 0 AND survey_data IS NOT NULL
")->fetchAll();

$questions = [
    'seeking_time' => [],
    'interviews' => [],
    'employer_feedback' => [],
    'location_factor' => [],
    'impact_factor' => [],
    'skill_development' => [],
    'job_search_experience' => [],
    'career_resources' => []
];

$possible_options = [
    'seeking_time' => ['Less than 3 months','3 to 6 months','6 to 12 months','Over 1 year',"Haven't actively started job searching"],
    'interviews' => ['Yes, multiple interviews','Yes, a few interviews','No, despite sending out many applications',"No, I haven't applied for jobs yet",'Prefer not to say'],
    'employer_feedback' => ['Yes, constructive feedback','Yes, but no specific feedback','No, I haven\'t received any feedback',"I haven't had any interviews or job applications",'Prefer not to say'],
    'location_factor' => ['Yes, location is a major factor','Location is a minor factor','No, location is not a factor',"I'm willing to relocate",'Prefer not to say'],
    'impact_factor' => ['Lack of relevant work experience','Insufficient qualifications or education','Economic downturn','Inadequate networking opportunities','Other'],
    'skill_development' => ['Yes, I\'m continually improving my skills',"I've taken some courses or certifications","No, I haven't focused on skill development",'Prefer not to say'],
    'job_search_experience' => ['Frustrating and discouraging','Challenging, but I remain optimistic','Smooth and successful','Not applicable (e.g., not actively job searching)'],
    'career_resources' => ['Yes, regularly','Occasionally','No, I haven\'t used these resources',"My institution doesn't offer such services",'Prefer not to say']
];

foreach ($questions as $q => &$counts) {
    foreach ($possible_options[$q] as $opt) {
        $counts[$opt] = 0;
    }
}

foreach ($responses as $r) {
    $data = json_decode($r['survey_data'], true);
    if (!$data) continue;
    foreach ($questions as $q => &$counts) {
        if (isset($data[$q])) {
            $ans = $data[$q];
            if (array_key_exists($ans, $counts)) {
                $counts[$ans]++;
            } else {
                $counts['Other'] = ($counts['Other'] ?? 0) + 1;
            }
        }
    }
}
$has_unemployed_data = !empty($responses);
?>
<div class="page-header">
    <h1>Tracer Module</h1>
</div>

<ul class="nav-tabs">
    <li><a class="nav-link <?= !isset($_GET['tab']) || $_GET['tab'] != 'unemployed' ? 'active' : '' ?>" href="tracer_module.php">Overview</a></li>
    <li><a class="nav-link" href="employment_details.php">Employment Details</a></li>
    <li><a class="nav-link" href="job_relevance.php">Job Relevance</a></li>
    <li><a class="nav-link <?= isset($_GET['tab']) && $_GET['tab'] == 'unemployed' ? 'active' : '' ?>" href="?tab=unemployed">Unemployed Analysis</a></li>
</ul>

<?php
$tab = $_GET['tab'] ?? '';
if ($tab == 'unemployed'):
?>
    <div class="card mt-4">
        <div class="card-header">Unemployed Alumni Analysis</div>
        <div class="card-body">
            <div class="row">
                <?php foreach ($questions as $q => $counts): ?>
                <div class="col-md-6 mb-4">
                    <div class="card h-100">
                        <div class="card-header">
                            <?= ucwords(str_replace('_', ' ', $q)) ?>
                        </div>
                        <div class="card-body">
                            <?php if (!$has_unemployed_data): ?>
                                <div class="alert alert-info">No survey data yet.</div>
                            <?php else: ?>
                                <div style="position: relative; height: 300px;">
                                    <canvas id="chart_<?= $q ?>" style="width: 100%; height: 100%;"></canvas>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
<?php else: ?>
    <div class="row g-4 mt-3">
        <div class="col-md-3">
            <div class="stat-card primary">
                <div class="stat-title">Total Alumni</div>
                <div class="stat-value"><?= $total_alumni ?></div>
                <div class="stat-label">All graduates</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card teal">
                <div class="stat-title">Employment Rate</div>
                <div class="stat-value"><?= $employment_rate ?>%</div>
                <div class="stat-label"><?= $employed ?> employed</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card purple">
                <div class="stat-title">Job Relevance</div>
                <div class="stat-value"><?= $relevance_rate ?>%</div>
                <div class="stat-label">Jobs aligned with degree</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card emerald">
                <div class="stat-title">Unemployment</div>
                <div class="stat-value"><?= $unemployed ?></div>
                <div class="stat-label">Seeking employment</div>
            </div>
        </div>
    </div>

    <div class="row mt-4 g-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">Employment Status Breakdown</div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="statusChart"></canvas>
                    </div>
                    <div class="list-group mt-3">
                        <?php foreach ($statuses as $label => $count): ?>
                        <div class="list-group-item d-flex justify-content-between align-items-center">
                            <?= $label ?>
                            <span class="badge bg-primary rounded-pill"><?= $count ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">Response Rate by Year</div>
                <div class="card-body">
                    <div class="list-group">
                        <?php foreach ($years as $y): ?>
                        <div class="list-group-item d-flex justify-content-between align-items-center">
                            Class of <?= $y['graduation_year'] ?>
                            <span class="badge bg-success"><?= $y['cnt'] ?> alumni (100%)</span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="mt-4">
        <button class="btn btn-primary" onclick="sendTracerSurvey()"><i class="fas fa-paper-plane me-2"></i>Send Tracer Survey</button>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const ctx = document.getElementById('statusChart');
        if (ctx) {
            new Chart(ctx, {
                type: 'pie',
                data: {
                    labels: <?= json_encode(array_keys($statuses)) ?>,
                    datasets: [{
                        data: <?= json_encode(array_values($statuses)) ?>,
                        backgroundColor: ['#388087', '#6FB3B3', '#BADFE7', '#C2EDCE']
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        datalabels: {
                            color: '#fff',
                            backgroundColor: 'rgba(0,0,0,0.6)',
                            borderRadius: 3,
                            padding: { top: 2, bottom: 2, left: 4, right: 4 },
                            font: { weight: 'bold', size: 11 },
                            formatter: (value, context) => {
                                let total = context.dataset.data.reduce((a,b) => a + b, 0);
                                return total > 0 ? ((value / total) * 100).toFixed(1) + '%' : '0%';
                            }
                        }
                    }
                }
            });
        }
    });

    function sendTracerSurvey() {
        if(confirm('Send tracer survey to all alumni?')) {
            alert('Survey notifications sent!');
        }
    }
    </script>
<?php endif; ?>

<?php if ($tab == 'unemployed' && $has_unemployed_data): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Small delay to ensure canvases are fully sized
    setTimeout(function() {
        <?php foreach ($questions as $q => $counts): ?>
        const canvas_<?= $q ?> = document.getElementById('chart_<?= $q ?>');
        if (canvas_<?= $q ?> && typeof Chart !== 'undefined') {
            try {
                new Chart(canvas_<?= $q ?>, {
                    type: 'bar',
                    data: {
                        labels: <?= json_encode(array_keys($counts)) ?>,
                        datasets: [{
                            label: 'Number of Alumni',
                            data: <?= json_encode(array_values($counts)) ?>,
                            backgroundColor: '#388087'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        plugins: {
                            legend: { display: false },
                            tooltip: { callbacks: { label: (ctx) => `${ctx.raw} alumni` } }
                        },
                        scales: {
                            y: { beginAtZero: true, title: { display: true, text: 'Count' } },
                            x: { ticks: { maxRotation: 45, minRotation: 45 } }
                        }
                    }
                });
            } catch(e) {
                console.error('Chart error for <?= $q ?>:', e);
            }
        }
        <?php endforeach; ?>
    }, 200);
});
</script>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>