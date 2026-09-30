<?php
require_once '../includes/admin_header.php';
require_once '../includes/mining_helpers.php';

$profiles = getAlumniProfiles($pdo);

$programs = array_values(array_unique(array_column($profiles, 'program')));
sort($programs);

$selected_program = $_GET['program'] ?? ($programs[0] ?? '');
if (!in_array($selected_program, $programs, true)) $selected_program = $programs[0] ?? '';
$grad_year = (int)($_GET['grad_year'] ?? date('Y'));   // cast: also stops XSS via the URL
if ($grad_year < 1970 || $grad_year > 2100) $grad_year = (int)date('Y');

$avg_time = 'N/A';
$prediction = null;   // percent
$ci = [0, 0];
$sample = 0;
$note = '';
$industries = [];

if ($selected_program) {
    $rows = array_values(array_filter($profiles, fn($p) => $p['program'] === $selected_program));

    // ---- Average time to first employment (months) ----
    $months = [];
    foreach ($rows as $p) {
        if (!$p['working']) continue;
        $m = null;
        if ($p['start_date'] && $p['year']) {
            try {
                $grad  = new DateTime($p['year'] . '-06-01');   // assume graduation June 1
                $start = new DateTime($p['start_date']);
                if ($start >= $grad) {
                    $d = $grad->diff($start);
                    $m = $d->y * 12 + $d->m;
                }
            } catch (Exception $e) {}
        }
        if ($m === null) $m = timeLabelToMonths($p['time_to_first_job']);   // fall back to survey answer
        if ($m !== null && $m <= 120) $months[] = $m;
    }
    if ($months) $avg_time = round(array_sum($months) / count($months), 1) . ' months';

    // ---- Employment probability from recent cohorts ----
    $cohort = array_filter($rows, fn($p) => $p['status'] !== null && $p['year'] >= $grad_year - 3 && $p['year'] <= $grad_year);
    if (!$cohort) {
        $cohort = array_filter($rows, fn($p) => $p['status'] !== null);
        if ($cohort) $note = 'No recent-cohort responses, so all graduates of this program were used.';
    }
    $sample = count($cohort);
    if ($sample > 0) {
        $employed = count(array_filter($cohort, fn($p) => $p['working']));
        $prediction = round($employed / $sample * 100, 1);
        $ci = array_map(fn($v) => round($v * 100, 1), wilsonInterval($employed, $sample));
        if ($sample < 10) $note = trim($note . ' Small sample (' . $sample . ' responses): treat the result as indicative.');
    }

    // ---- Top industries ----
    $counts = [];
    foreach ($rows as $p) if ($p['working'] && $p['industry']) $counts[$p['industry']] = ($counts[$p['industry']] ?? 0) + 1;
    arsort($counts);
    $industries = array_slice(array_keys($counts), 0, 3);
}
$pred_display = $prediction ?? 0;
?>
<div class="page-header">
    <h1>Employment Outcome Prediction</h1>
</div>

<div class="card mb-4">
    <div class="card-body">
        <form method="get" class="row g-3">
            <div class="col-md-5">
                <label class="form-label">Program</label>
                <select name="program" class="form-select">
                    <?php foreach ($programs as $p): ?>
                    <option value="<?= htmlspecialchars($p) ?>" <?= $selected_program == $p ? 'selected' : '' ?>><?= htmlspecialchars($p) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-5">
                <label class="form-label">Graduation Year</label>
                <input type="number" name="grad_year" class="form-control" value="<?= $grad_year ?>">
            </div>
            <div class="col-md-2 align-self-end">
                <button type="submit" class="btn btn-primary w-100">Predict</button>
            </div>
        </form>
    </div>
</div>

<div class="row g-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">Prediction Result</div>
            <div class="card-body">
                <p><strong>Program:</strong> <?= htmlspecialchars($selected_program) ?></p>
                <p><strong>Graduation Year:</strong> <?= $grad_year ?></p>
                <p><strong>Average Time to Employment:</strong> <?= htmlspecialchars($avg_time) ?></p>
                <p><strong>Recommended Industries:</strong> <?= $industries ? htmlspecialchars(implode(', ', $industries)) : 'No data' ?></p>
                <?php if ($prediction === null): ?>
                    <div class="alert alert-info mb-0">No survey or employment responses for this program yet.</div>
                <?php else: ?>
                    <div class="progress mt-3" style="height: 25px;">
                        <div class="progress-bar" style="width: <?= $pred_display ?>%; background: linear-gradient(90deg, #388087, #6FB3B3);"><?= $pred_display ?>% Probability</div>
                    </div>
                    <p class="small text-muted mt-2 mb-0">95% interval: <?= $ci[0] ?>% – <?= $ci[1] ?>% (based on <?= $sample ?> responses)</p>
                    <?php if ($note): ?><p class="small text-warning mt-1 mb-0"><?= htmlspecialchars($note) ?></p><?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">Confidence Interval</div>
            <div class="card-body">
                <div class="chart-container">
                    <canvas id="predictionChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    new Chart(document.getElementById('predictionChart'), {
        type: 'bar',
        data: {
            labels: ['Lower bound', 'Estimate', 'Upper bound'],
            datasets: [{
                data: [<?= $ci[0] ?>, <?= $pred_display ?>, <?= $ci[1] ?>],
                backgroundColor: ['#BADFE7', '#388087', '#BADFE7']
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            scales: { x: { min: 0, max: 100, ticks: { callback: v => v + '%' } } },
            plugins: { legend: { display: false }, datalabels: { display: false } }
        }
    });
});
</script>

<?php include '../includes/footer.php'; ?>