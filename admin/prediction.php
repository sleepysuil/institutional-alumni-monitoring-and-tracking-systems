<?php
require_once '../includes/admin_header.php';
$programs = $pdo->query("SELECT DISTINCT program FROM alumni")->fetchAll(PDO::FETCH_COLUMN);
$selected_program = $_GET['program'] ?? 'BS Information Systems';
$grad_year = $_GET['grad_year'] ?? 2024;
$prediction = 75; // dummy
$avg_time = '3-6 months';
$industries = ['Information Technology', 'Finance', 'Healthcare'];
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
                <p><strong>Program:</strong> <?= $selected_program ?></p>
                <p><strong>Graduation Year:</strong> <?= $grad_year ?></p>
                <p><strong>Average Time to Employment:</strong> <?= $avg_time ?></p>
                <p><strong>Recommended Industries:</strong> <?= implode(', ', $industries) ?></p>
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
                scales: { x: { max: 100 } }
            }
        });
    }
});
</script>

<?php include '../includes/footer.php'; ?>