<?php
require_once '../includes/admin_header.php';

$patterns = [];
$source = 'none';

// First, try to get from employment_history
$history_exists = $pdo->query("SELECT COUNT(*) FROM employment_history")->fetchColumn();

if ($history_exists > 0) {
    // Get all employment history ordered by alumni and start date
    $history = $pdo->query("
        SELECT alumni_id, position, start_date 
        FROM employment_history 
        ORDER BY alumni_id, start_date
    ")->fetchAll();

    // Group by alumni
    $careers = [];
    foreach ($history as $h) {
        $careers[$h['alumni_id']][] = ['pos' => $h['position'], 'date' => $h['start_date']];
    }

    // Find common transitions
    $transitions = [];
    foreach ($careers as $steps) {
        for ($i = 0; $i < count($steps) - 1; $i++) {
            $from = $steps[$i]['pos'];
            $to = $steps[$i+1]['pos'];
            if ($from && $to) {
                $key = $from . '|' . $to;
                if (!isset($transitions[$key])) {
                    $transitions[$key] = ['from' => $from, 'to' => $to, 'count' => 0, 'timeframes' => []];
                }
                $transitions[$key]['count']++;
                // Calculate timeframe in months
                $start = new DateTime($steps[$i]['date']);
                $end = new DateTime($steps[$i+1]['date']);
                $months = $start->diff($end)->m + $start->diff($end)->y * 12;
                $transitions[$key]['timeframes'][] = $months;
            }
        }
    }

    foreach ($transitions as $t) {
        $avg_months = $t['count'] ? round(array_sum($t['timeframes']) / $t['count']) : 0;
        $patterns[] = [
            'from' => $t['from'],
            'to' => $t['to'],
            'timeframe' => $avg_months . ' months',
            'count' => $t['count']
        ];
    }
    $source = 'history';
}

// If no history data, try to get from current employment (common positions)
if (empty($patterns)) {
    $current = $pdo->query("
        SELECT position, COUNT(*) as cnt
        FROM employment
        WHERE position IS NOT NULL AND position != ''
        GROUP BY position
        ORDER BY cnt DESC
        LIMIT 10
    ")->fetchAll();

    if (!empty($current)) {
        foreach ($current as $c) {
            $patterns[] = [
                'from' => 'N/A (current)',
                'to' => $c['position'],
                'timeframe' => 'Current',
                'count' => $c['cnt']
            ];
        }
        $source = 'current';
    }
}

// Sort patterns by count descending
usort($patterns, fn($a, $b) => $b['count'] <=> $a['count']);
?>
<div class="page-header">
    <h1>Career Path Patterns</h1>
    <p class="text-muted">
        <?php if ($source == 'history'): ?>
            Based on alumni employment history
        <?php elseif ($source == 'current'): ?>
            Based on current employment positions (no historical data)
        <?php else: ?>
            No employment data available yet.
        <?php endif; ?>
    </p>
</div>

<?php if (empty($patterns)): ?>
    <div class="alert alert-info">No employment data available. Please update employment records.</div>
<?php else: ?>
    <!-- Chart Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Career Path Transitions Visualization</h5>
                </div>
                <div class="card-body">
                    <canvas id="careerChart" style="max-height: 400px;"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Table Section -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Detailed Career Path Data</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>From Position</th>
                            <th>To Position</th>
                            <th>Average Timeframe</th>
                            <th>Number of Alumni</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($patterns as $p): ?>
                        <tr>
                            <td><?= htmlspecialchars($p['from']) ?></td>
                            <td><?= htmlspecialchars($p['to']) ?></td>
                            <td><?= $p['timeframe'] ?></td>
                            <td><?= $p['count'] ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- Chart.js Script -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    <?php if (!empty($patterns)): ?>
    const ctx = document.getElementById('careerChart').getContext('2d');
    
    // Prepare data for chart
    const labels = [
        <?php foreach ($patterns as $p): ?>
            '<?= addslashes($p['from']) ?> → <?= addslashes($p['to']) ?>',
        <?php endforeach; ?>
    ];
    
    const counts = [
        <?php foreach ($patterns as $p): ?>
            <?= $p['count'] ?>,
        <?php endforeach; ?>
    ];
    
    // Generate colors for bars
    const colors = counts.map((_, index) => {
        const hue = (index * 30) % 360;
        return `hsla(${hue}, 70%, 60%, 0.7)`;
    });
    
    const borderColors = counts.map((_, index) => {
        const hue = (index * 30) % 360;
        return `hsla(${hue}, 70%, 60%, 1)`;
    });
    
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Number of Alumni',
                data: counts,
                backgroundColor: colors,
                borderColor: borderColors,
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                title: {
                    display: true,
                    text: '<?= $source == "history" ? "Career Path Transitions" : "Current Employment Positions" ?>',
                    font: {
                        size: 16
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return `Alumni: ${context.parsed.y}`;
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1,
                        callback: function(value) {
                            return Math.floor(value) === value ? value : '';
                        }
                    },
                    title: {
                        display: true,
                        text: 'Number of Alumni'
                    }
                },
                x: {
                    title: {
                        display: true,
                        text: '<?= $source == "history" ? "Career Transitions" : "Position" ?>'
                    },
                    ticks: {
                        maxRotation: 45,
                        minRotation: 0
                    }
                }
            }
        }
    });
    <?php endif; ?>
});
</script>

<?php include '../includes/footer.php'; ?>