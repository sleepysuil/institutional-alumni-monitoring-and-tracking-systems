<?php
require_once '../includes/admin_header.php';

$programs = $pdo->query("SELECT DISTINCT program FROM alumni ORDER BY program")->fetchAll(PDO::FETCH_COLUMN);
$selected_program = $_GET['program'] ?? ($programs[0] ?? '');
$grad_year = $_GET['grad_year'] ?? date('Y');

$avg_time = 'N/A';
$prediction = 0;
$industries = [];

if ($selected_program) {
    // Approximate time to first job using employment start_date and graduation year
    $stmt = $pdo->prepare("
        SELECT e.start_date, a.graduation_year
        FROM alumni a
        JOIN employment e ON a.id = e.alumni_id
        WHERE a.program = ? AND e.start_date IS NOT NULL
    ");
    $stmt->execute([$selected_program]);
    $rows = $stmt->fetchAll();
    
    $months_diff = [];
    foreach ($rows as $row) {
        // Assume graduation on June 1 of graduation_year
        $grad_date = new DateTime($row['graduation_year'] . '-06-01');
        $start_date = new DateTime($row['start_date']);
        $interval = $grad_date->diff($start_date);
        $months = $interval->y * 12 + $interval->m;
        if ($months >= 0) {
            $months_diff[] = $months;
        }
    }
    
    if (!empty($months_diff)) {
        $avg_months = array_sum($months_diff) / count($months_diff);
        $avg_time = round($avg_months) . ' months';
        
        // Prediction: employment rate for recent graduates (last 3 years)
        $recent = $pdo->prepare("
            SELECT COUNT(DISTINCT a.id) as employed
            FROM alumni a
            JOIN employment e ON a.id = e.alumni_id
            WHERE a.program = ? AND a.graduation_year >= ? AND e.status IN ('Employed','Self-Employed')
        ");
        $recent->execute([$selected_program, $grad_year - 3]);
        $employed_recent = $recent->fetchColumn();
        
        $total_recent = $pdo->prepare("
            SELECT COUNT(*) FROM alumni WHERE program = ? AND graduation_year >= ?
        ");
        $total_recent->execute([$selected_program, $grad_year - 3]);
        $total = $total_recent->fetchColumn();
        
        $prediction = $total ? round(($employed_recent / $total) * 100, 1) : 0;
    }
    
    // Top industries for this program
    $ind_stmt = $pdo->prepare("
        SELECT e.industry, COUNT(*) as cnt
        FROM alumni a
        JOIN employment e ON a.id = e.alumni_id
        WHERE a.program = ? AND e.industry IS NOT NULL
        GROUP BY e.industry
        ORDER BY cnt DESC
        LIMIT 3
    ");
    $ind_stmt->execute([$selected_program]);
    $industries = $ind_stmt->fetchAll(PDO::FETCH_COLUMN);
}
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
                    <option value="<?= $p ?>" <?= $selected_program==$p?'selected':'' ?>><?= $p ?></option>
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
                <p><strong>Average Time to Employment:</strong> <?= $avg_time ?></p>
                <p><strong>Recommended Industries:</strong> <?= !empty($industries) ? implode(', ', $industries) : 'No data' ?></p>
                <div class="progress mt-3" style="height: 25px;">
                    <div class="progress-bar" style="width: <?= $prediction ?>%; background: linear-gradient(90deg, #1E3A8A, #8B5CF6);"><?= $prediction ?>% Probability</div>
                </div>
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
    const ctx = document.getElementById('predictionChart');
    if (ctx) {
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['Employment Probability'],
                datasets: [{
                    label: 'Prediction',
                    data: [<?= $prediction ?>],
                    backgroundColor: '#8B5CF6'
                }]
            },
            options: { 
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                scales: { x: { max: 100 } },
                plugins: {
                    datalabels: { display: false } // not needed for bar
                }
            }
        });
    }
});
</script>

<?php include '../includes/footer.php'; ?>