<?php
require_once '../includes/admin_header.php';
require_once '../includes/mining_helpers.php';   // getAlumniProfiles(), cleanText()

$tab = $_GET['tab'] ?? '';
if (!in_array($tab, ['employed', 'unemployed'], true)) $tab = '';

/* ------------------------------------------------------------------
 * Survey analysis definitions (the answers alumni give in tracer_survey.php)
 * ------------------------------------------------------------------ */
$analyses = [
    'unemployed' => [
        'title'       => 'Unemployed Alumni Analysis',
        'is_employed' => 0,
        'questions'   => [
            'seeking_time' => ['label' => 'Time Actively Seeking Employment', 'options' => ['Less than 3 months', '3 to 6 months', '6 to 12 months', 'Over 1 year', "Haven't actively started job searching"]],
            'interviews' => ['label' => 'Job Interviews Secured', 'options' => ['Yes, multiple interviews', 'Yes, a few interviews', 'No, despite sending out many applications', "No, I haven't applied for jobs yet", 'Prefer not to say']],
            'employer_feedback' => ['label' => 'Employer Feedback Received', 'options' => ['Yes, constructive feedback', 'Yes, but no specific feedback', "No, I haven't received any feedback", "I haven't had any interviews or job applications", 'Prefer not to say']],
            'location_factor' => ['label' => 'Location as a Factor', 'options' => ['Yes, location is a major factor', 'Location is a minor factor', 'No, location is not a factor', "I'm willing to relocate", 'Prefer not to say']],
            'impact_factor' => ['label' => 'Biggest Impact on Unemployment', 'options' => ['Lack of relevant work experience', 'Insufficient qualifications or education', 'Economic downturn', 'Inadequate networking opportunities', 'Other']],
            'skill_development' => ['label' => 'Skill Development Activities', 'options' => ["Yes, I'm continually improving my skills", "I've taken some courses or certifications", "No, I haven't focused on skill development", 'Prefer not to say']],
            'job_search_experience' => ['label' => 'Job Search Experience', 'options' => ['Frustrating and discouraging', 'Challenging, but I remain optimistic', 'Smooth and successful', 'Not applicable (e.g., not actively job searching)']],
            'career_resources' => ['label' => 'Use of Career Services / Alumni Resources', 'options' => ['Yes, regularly', 'Occasionally', "No, I haven't used these resources", "My institution doesn't offer such services", 'Prefer not to say']],
        ],
    ],
    'employed' => [
        'title'       => 'Employed Alumni Analysis',
        'is_employed' => 1,
        'questions'   => [
            'time_to_first_job' => ['label' => 'Time to Secure First Job', 'options' => ['Less than 3 months', '3 to 6 months', '6 to 12 months', 'Over 1 year']],
            'internship_helpfulness' => ['label' => 'Helpfulness of Internships', 'options' => ['Very helpful', 'Somewhat helpful', 'Neutral', 'Not very helpful', 'Not helpful at all']],
            'networking_participation' => ['label' => 'Networking / Job Fair Participation', 'options' => ['Yes, regularly', 'Occasionally', "No, I didn't participate in networking events", 'Prefer not to say']],
            'job_category' => ['label' => 'Top Job Categories', 'options' => [], 'top' => 8],
            'skills_critical' => ['label' => 'Skills Most Critical in Getting the Job', 'type' => 'skills', 'options' => [], 'top' => 10],
        ],
    ],
];

/** Latest survey answer per alumni (not every historical submission), tallied per question. */
function tallyQuestions(PDO $pdo, int $isEmployed, array $questions): array {
    $stmt = $pdo->prepare("
        SELECT t.survey_data
        FROM tracer_responses t
        JOIN (SELECT alumni_id, MAX(id) AS mid FROM tracer_responses GROUP BY alumni_id) l ON l.mid = t.id
        WHERE t.is_employed = ? AND t.survey_data IS NOT NULL");
    $stmt->execute([$isEmployed]);

    $result = [];
    $seen = [];
    foreach ($questions as $q => $def) {
        $result[$q] = [];
        foreach ($def['options'] as $o) $result[$q][$o] = 0;
    }

    $n = 0;
    foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $json) {
        $data = json_decode($json, true);
        if (!is_array($data)) continue;
        $n++;
        foreach ($questions as $q => $def) {
            if (($def['type'] ?? 'single') === 'skills') {
                foreach (($data['skills_critical'] ?? []) as $s) {
                    $s = cleanText($s);
                    if (!$s) continue;
                    $k = strtolower($s);
                    $seen[$q][$k] ??= $s;
                    $result[$q][$seen[$q][$k]] = ($result[$q][$seen[$q][$k]] ?? 0) + 1;
                }
                continue;
            }
            $ans = $data[$q] ?? '';
            if (is_array($ans)) continue;
            $ans = trim((string)$ans);
            if ($ans === '') continue;                      // blank answers are skipped, not counted as "Other"
            if (isset($result[$q][$ans])) {
                $result[$q][$ans]++;
            } elseif ($def['options']) {
                $result[$q]['Other'] = ($result[$q]['Other'] ?? 0) + 1;
            } else {
                $ans = cleanText($ans) ?? $ans;
                $result[$q][$ans] = ($result[$q][$ans] ?? 0) + 1;
            }
        }
    }

    // dynamic questions: keep the biggest N only
    foreach ($questions as $q => $def) {
        if (!$def['options']) {
            arsort($result[$q]);
            $result[$q] = array_slice($result[$q], 0, $def['top'] ?? 8, true);
        }
    }
    return [$result, $n];
}

function wrapLabel(string $s, int $w = 28): array {
    return explode("\n", wordwrap($s, $w, "\n", true));
}

/* ------------------------------------------------------------------
 * Analysis tabs
 * ------------------------------------------------------------------ */
$chartData = [];
$tally = [];
$responseCount = 0;
if ($tab !== '') {
    $def = $analyses[$tab];
    [$tally, $responseCount] = tallyQuestions($pdo, $def['is_employed'], $def['questions']);
    foreach ($tally as $q => $counts) {
        if (array_sum($counts) === 0) continue;
        $chartData['chart_' . $q] = [
            'labels' => array_map('wrapLabel', array_keys($counts)),
            'data'   => array_values($counts),
        ];
    }
}

/* ------------------------------------------------------------------
 * Overview data (survey + profile merged, same source as Data Mining)
 * ------------------------------------------------------------------ */
$statuses = ['Employed' => 0, 'Self-Employed' => 0, 'Unemployed' => 0, 'Pursuing Higher Education' => 0];
$byYear = [];
$total_alumni = $respondents = $working = $rated = $relevant = 0;

if ($tab === '') {
    foreach (getAlumniProfiles($pdo) as $p) {
        $total_alumni++;
        $byYear[$p['year']] ??= ['total' => 0, 'resp' => 0];
        $byYear[$p['year']]['total']++;
        if ($p['status'] === null) continue;

        $respondents++;
        $byYear[$p['year']]['resp']++;
        if (isset($statuses[$p['status']])) $statuses[$p['status']]++;
        if ($p['working']) {
            $working++;
            if ($p['relevance']) {
                $rated++;
                if ($p['relevance'] === 'Highly Relevant') $relevant++;
            }
        }
    }
    krsort($byYear);
}
$employment_rate = $respondents ? round($working / $respondents * 100, 1) : 0;
$relevance_rate  = $rated ? round($relevant / $rated * 100, 1) : null;
$response_rate   = $total_alumni ? round($respondents / $total_alumni * 100, 1) : 0;
$unemployed      = $statuses['Unemployed'];

// Alumni with a phone number who have never answered the tracer survey
$pending_count = (int)$pdo->query("
    SELECT COUNT(*) FROM alumni a
    WHERE a.phone IS NOT NULL AND a.phone != ''
      AND NOT EXISTS (SELECT 1 FROM tracer_responses t WHERE t.alumni_id = a.id)")->fetchColumn();

// Latest survey submissions
$latest_responses = $pdo->query("
    SELECT t.response_date, t.is_employed, a.id, a.first_name, a.last_name, a.program
    FROM tracer_responses t JOIN alumni a ON t.alumni_id = a.id
    ORDER BY t.response_date DESC, t.id DESC LIMIT 5")->fetchAll();
?>
<div class="page-header">
    <h1>Tracer Module</h1>
</div>

<ul class="nav-tabs">
    <li><a class="nav-link <?= $tab === '' ? 'active' : '' ?>" href="tracer_module.php">Overview</a></li>
    <li><a class="nav-link" href="employment_details.php">Employment Details</a></li>
    <li><a class="nav-link" href="job_relevance.php">Job Relevance</a></li>
    <li><a class="nav-link <?= $tab === 'employed' ? 'active' : '' ?>" href="?tab=employed">Employed Analysis</a></li>
    <li><a class="nav-link <?= $tab === 'unemployed' ? 'active' : '' ?>" href="?tab=unemployed">Unemployed Analysis</a></li>
</ul>

<?php if ($tab !== ''): ?>
    <!-- ================= ANALYSIS TABS ================= -->
    <div class="card mt-4">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <span><?= $analyses[$tab]['title'] ?></span>
            <span class="badge bg-secondary"><?= $responseCount ?> respondent<?= $responseCount == 1 ? '' : 's' ?> (latest survey per alumni)</span>
        </div>
        <div class="card-body">
            <?php if ($responseCount === 0): ?>
                <div class="alert alert-info mb-0">No survey data yet for this group.</div>
            <?php else: ?>
            <div class="row">
                <?php foreach ($analyses[$tab]['questions'] as $q => $qdef):
                    $has = isset($chartData['chart_' . $q]);
                    $height = $has ? max(240, count($chartData['chart_' . $q]['data']) * 42 + 70) : 0;
                ?>
                <div class="col-lg-6 mb-4">
                    <div class="card h-100">
                        <div class="card-header"><?= htmlspecialchars($qdef['label']) ?></div>
                        <div class="card-body">
                            <?php if (!$has): ?>
                                <div class="alert alert-info mb-0">No answers yet.</div>
                            <?php else: ?>
                                <div style="position: relative; height: <?= $height ?>px;">
                                    <canvas id="chart_<?= $q ?>"></canvas>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($chartData): ?>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const charts = <?= json_encode($chartData) ?>;
        Object.keys(charts).forEach(function(id) {
            const canvas = document.getElementById(id);
            if (!canvas) return;
            new Chart(canvas, {
                type: 'bar',
                data: {
                    labels: charts[id].labels,            // arrays = multi-line labels
                    datasets: [{ label: 'Number of Alumni', data: charts[id].data, backgroundColor: '#388087', borderRadius: 4 }]
                },
                options: {
                    indexAxis: 'y',                       // horizontal bars: long answers stay readable on phones
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        datalabels: { display: false },
                        tooltip: { callbacks: { label: c => `${c.raw} alumni` } }
                    },
                    scales: {
                        x: { beginAtZero: true, ticks: { precision: 0, stepSize: 1 }, title: { display: true, text: 'Alumni' } },
                        y: { ticks: { autoSkip: false, font: { size: 11 } } }
                    }
                }
            });
        });
    });
    </script>
    <?php endif; ?>

<?php else: ?>
    <!-- ================= OVERVIEW ================= -->
    <div class="row g-4 mt-1">
        <div class="col-6 col-lg-3">
            <div class="stat-card primary">
                <div class="stat-title">Total Alumni</div>
                <div class="stat-value"><?= $total_alumni ?></div>
                <div class="stat-label"><?= $respondents ?> responded (<?= $response_rate ?>%)</div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="stat-card teal">
                <div class="stat-title">Employment Rate</div>
                <div class="stat-value"><?= $employment_rate ?>%</div>
                <div class="stat-label"><?= $working ?> of <?= $respondents ?> respondents</div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="stat-card purple">
                <div class="stat-title">Job Relevance</div>
                <div class="stat-value"><?= $relevance_rate === null ? 'N/A' : $relevance_rate . '%' ?></div>
                <div class="stat-label"><?= $rated ? 'Highly relevant, of ' . $rated . ' rated jobs' : 'No relevance data yet' ?></div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="stat-card emerald">
                <div class="stat-title">Unemployment</div>
                <div class="stat-value"><?= $unemployed ?></div>
                <div class="stat-label">Seeking employment</div>
            </div>
        </div>
    </div>

    <div class="row mt-4 g-4">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">Employment Status Breakdown</div>
                <div class="card-body">
                    <?php if ($respondents === 0): ?>
                        <div class="alert alert-info mb-0">No responses yet. Send the survey to alumni to get started.</div>
                    <?php else: ?>
                        <div class="chart-container"><canvas id="statusChart"></canvas></div>
                    <?php endif; ?>
                    <div class="list-group mt-3">
                        <?php foreach ($statuses as $label => $count): ?>
                        <div class="list-group-item d-flex justify-content-between align-items-center">
                            <?= $label ?>
                            <span class="badge bg-primary rounded-pill"><?= $count ?></span>
                        </div>
                        <?php endforeach; ?>
                        <div class="list-group-item d-flex justify-content-between align-items-center text-muted">
                            No response yet
                            <span class="badge bg-secondary rounded-pill"><?= $total_alumni - $respondents ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">Response Rate by Year</div>
                <div class="card-body">
                    <?php if (empty($byYear)): ?>
                        <p class="text-muted mb-0">No alumni records yet.</p>
                    <?php endif; ?>
                    <div class="list-group">
                        <?php foreach ($byYear as $year => $y):
                            $rate = $y['total'] ? round($y['resp'] / $y['total'] * 100, 1) : 0; ?>
                        <div class="list-group-item">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span>Class of <?= $year ?></span>
                                <span class="badge bg-<?= $rate >= 70 ? 'success' : ($rate >= 40 ? 'warning' : 'danger') ?>"><?= $y['resp'] ?>/<?= $y['total'] ?> (<?= $rate ?>%)</span>
                            </div>
                            <div class="progress" style="height: 6px;"><div class="progress-bar" style="width: <?= $rate ?>%; background:#388087;"></div></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card mt-4">
        <div class="card-header">Latest Survey Responses</div>
        <ul class="list-group list-group-flush">
            <?php if (!$latest_responses): ?><li class="list-group-item text-muted">No responses yet.</li><?php endif; ?>
            <?php foreach ($latest_responses as $lr):
                $lbl = ['1' => ['Employed', 'success'], '0' => ['Unemployed', 'danger'], '2' => ['Self-Employed', 'info']][(string)(int)$lr['is_employed']] ?? ['Unknown', 'secondary']; ?>
            <li class="list-group-item d-flex justify-content-between align-items-center">
                <span><a href="view_alumni.php?id=<?= (int)$lr['id'] ?>" class="text-decoration-none"><?= htmlspecialchars($lr['first_name'] . ' ' . $lr['last_name']) ?></a>
                    <small class="text-muted">· <?= htmlspecialchars($lr['program']) ?> · <?= $lr['response_date'] ? date('M j, Y', strtotime($lr['response_date'])) : '' ?></small></span>
                <span class="badge bg-<?= $lbl[1] ?>"><?= $lbl[0] ?></span>
            </li>
            <?php endforeach; ?>
        </ul>
    </div>

    <div class="mt-4">
        <button class="btn btn-primary" id="sendSurveyBtn" <?= $pending_count ? '' : 'disabled' ?>>
            <i class="fas fa-paper-plane me-2"></i>Send Survey Reminder (<?= $pending_count ?> pending)
        </button>
        <small class="text-muted d-block mt-2">Sends an SMS to alumni who have a phone number but have not answered the tracer survey yet.</small>
        <div id="sendResult" class="mt-3"></div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const ctx = document.getElementById('statusChart');
        if (ctx) {
            new Chart(ctx, {
                type: 'pie',
                data: {
                    labels: <?= json_encode(array_keys($statuses), JSON_HEX_TAG | JSON_HEX_AMP) ?>,
                    datasets: [{ data: <?= json_encode(array_values($statuses)) ?>, backgroundColor: ['#388087', '#6FB3B3', '#BADFE7', '#C2EDCE'] }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'bottom' },
                        datalabels: {
                            display: c => c.dataset.data[c.dataIndex] > 0,   // no "0.0%" bubbles on empty slices
                            color: '#fff',
                            backgroundColor: 'rgba(0,0,0,0.6)',
                            borderRadius: 3,
                            padding: { top: 2, bottom: 2, left: 4, right: 4 },
                            font: { weight: 'bold', size: 11 },
                            formatter: (value, c) => {
                                const total = c.dataset.data.reduce((a, b) => a + b, 0);
                                return total > 0 ? ((value / total) * 100).toFixed(1) + '%' : '';
                            }
                        }
                    }
                }
            });
        }

        const btn = document.getElementById('sendSurveyBtn');
        const out = document.getElementById('sendResult');
        btn.addEventListener('click', function() {
            if (!confirm('Send an SMS reminder to <?= $pending_count ?> alumni who have not answered the survey?\nThis uses your Semaphore credits.')) return;
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Sending...';
            fetch('<?= SITE_URL ?>/api/send_notification.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'type=tracer&audience=pending'
            })
            .then(r => r.json())
            .then(d => {
                if (!d.success) btn.disabled = false;   // allow a retry after a failure
                out.innerHTML = '<div class="alert alert-' + (d.success ? 'success' : 'danger') + '">' +
                    (d.message || d.error || 'Unknown response') + '</div>';
            })
            .catch(() => { btn.disabled = false; out.innerHTML = '<div class="alert alert-danger">Request failed. Please try again.</div>'; })
            .finally(() => {
                btn.innerHTML = '<i class="fas fa-paper-plane me-2"></i>Send Survey Reminder';
            });
        });
    });
    </script>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>