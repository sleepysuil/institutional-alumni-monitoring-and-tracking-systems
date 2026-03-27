<?php
require_once '../includes/admin_header.php';
$relevance_counts = $pdo->query("SELECT relevance, COUNT(*) as cnt FROM employment WHERE relevance IS NOT NULL GROUP BY relevance")->fetchAll();
$total_employed = $pdo->query("SELECT COUNT(*) FROM employment WHERE status IN ('Employed','Self-Employed')")->fetchColumn();
?>
<div class="page-header">
    <h1>Job Relevance Analysis</h1>
</div>

<ul class="nav-tabs">
    <li><a class="nav-link" href="tracer_module.php">Overview</a></li>
    <li><a class="nav-link" href="employment_details.php">Employment Details</a></li>
    <li><a class="nav-link active" href="job_relevance.php">Job Relevance</a></li>
</ul>

<div class="row g-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">Relevance Distribution</div>
            <div class="card-body">
                <div class="chart-container">
                    <canvas id="relevanceChart"></canvas>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">Summary</div>
            <div class="card-body">
                <table class="table">
                    <thead><tr><th>Relevance</th><th>Count</th><th>Percentage</th></tr></thead>
                    <tbody>
                        <?php 
                        $total = $total_employed ?: 1;
                        foreach ($relevance_counts as $r): 
                            $percent = round(($r['cnt'] / $total) * 100, 1);
                        ?>
                        <tr>
                            <td><?= $r['relevance'] ?></td>
                            <td><?= $r['cnt'] ?></td>
                            <td><?= $percent ?>%</td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('relevanceChart');
    if (ctx) {
        new Chart(ctx, {
            type: 'pie',
            data: {
                labels: <?= json_encode(array_column($relevance_counts, 'relevance')) ?>,
                datasets: [{
                    data: <?= json_encode(array_column($relevance_counts, 'cnt')) ?>,
                    backgroundColor: ['#388087', '#6FB3B3', '#BADFE7']
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
</script>

<?php include '../includes/footer.php'; ?>