<?php
require_once '../includes/admin_header.php';
require_once '../includes/mining_helpers.php';

$patterns = [];
$source = 'none';

// ---- 1. Career transitions from employment_history ----
$history = $pdo->query("
    SELECT alumni_id, position, start_date
    FROM employment_history
    WHERE position IS NOT NULL AND position != ''
    ORDER BY alumni_id, start_date
")->fetchAll();

if ($history) {
    $careers = [];
    foreach ($history as $h) {
        $careers[$h['alumni_id']][] = ['pos' => cleanText($h['position']), 'date' => $h['start_date']];
    }

    $transitions = [];
    foreach ($careers as $steps) {
        for ($i = 0; $i < count($steps) - 1; $i++) {
            $from = $steps[$i]['pos'];
            $to   = $steps[$i + 1]['pos'];
            if (!$from || !$to || strcasecmp($from, $to) === 0) continue;

            $key = strtolower($from . '|' . $to);
            if (!isset($transitions[$key])) {
                $transitions[$key] = ['from' => $from, 'to' => $to, 'count' => 0, 'months' => []];
            }
            $transitions[$key]['count']++;
            // Skip missing dates (new DateTime(null) would silently mean "today")
            if ($steps[$i]['date'] && $steps[$i + 1]['date']) {
                try {
                    $d = (new DateTime($steps[$i]['date']))->diff(new DateTime($steps[$i + 1]['date']));
                    $transitions[$key]['months'][] = $d->y * 12 + $d->m;
                } catch (Exception $e) {}
            }
        }
    }
    foreach ($transitions as $t) {
        $patterns[] = [
            'from' => $t['from'], 'to' => $t['to'], 'count' => $t['count'],
            'timeframe' => $t['months'] ? round(array_sum($t['months']) / count($t['months'])) . ' months' : 'N/A',
        ];
    }
    if ($patterns) $source = 'history';
}

// ---- 2. Fallback: most common current positions (survey + profile) ----
if (empty($patterns)) {
    $counts = [];
    foreach (getAlumniProfiles($pdo) as $p) {
        if ($p['working'] && $p['position']) {
            $k = strtolower($p['position']);
            if (!isset($counts[$k])) $counts[$k] = ['label' => $p['position'], 'n' => 0];
            $counts[$k]['n']++;
        }
    }
    uasort($counts, fn($a, $b) => $b['n'] <=> $a['n']);
    foreach (array_slice($counts, 0, 10) as $c) {
        $patterns[] = ['from' => 'N/A (current)', 'to' => $c['label'], 'timeframe' => 'Current', 'count' => $c['n']];
    }
    if ($patterns) $source = 'current';
}

usort($patterns, fn($a, $b) => $b['count'] <=> $a['count']);

// The chart shows the 10 most common paths so labels stay readable; the table lists all
$top = array_slice($patterns, 0, 10);
$chart_labels = array_map(fn($p) => $source == 'history' ? $p['from'] . ' → ' . $p['to'] : $p['to'], $top);
$chart_counts = array_column($top, 'count');
$total_alumni_paths = array_sum(array_column($patterns, 'count'));
?>
<div class="page-header">
    <h1>Career Path Patterns</h1>
    <p class="text-muted mb-0">
        <?php if ($source == 'history'): ?>Based on alumni employment history
        <?php elseif ($source == 'current'): ?>Based on current positions (no employment history recorded yet)
        <?php else: ?>No employment data available yet.<?php endif; ?>
    </p>
</div>

<?php if (empty($patterns)): ?>
    <div class="alert alert-info">No position data yet. Positions come from the Tracer Survey ("Job Title/Description") or the alumni profile.</div>
<?php else: ?>
    <div class="card mb-4">
        <div class="card-header">Career Path Visualization <small class="text-muted">· top <?= count($top) ?> of <?= count($patterns) ?></small></div>
        <div class="card-body">
            <div class="chart-container" style="height: 380px;">
                <canvas id="careerChart"></canvas>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">Detailed Career Path Data</div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead class="table-light">
                        <tr><th>From Position</th><th>To Position</th><th>Average Timeframe</th><th>Number of Alumni</th></tr>
                    </thead>
                    <tbody>
                    <?php foreach ($patterns as $p): ?>
                        <tr>
                            <td><?= htmlspecialchars($p['from']) ?></td>
                            <td><?= htmlspecialchars($p['to']) ?></td>
                            <td><?= htmlspecialchars($p['timeframe']) ?></td>
                            <td><?= (int)$p['count'] ?> <small class="text-muted">(<?= $total_alumni_paths ? round($p['count'] / $total_alumni_paths * 100) : 0 ?>%)</small></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const labels = <?= json_encode($chart_labels, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?>;   // json_encode = safe, no broken quotes / XSS
    const counts = <?= json_encode($chart_counts) ?>;
    new Chart(document.getElementById('careerChart'), {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Number of Alumni',
                data: counts,
                backgroundColor: counts.map((_, i) => `hsla(${(i * 30) % 360}, 60%, 55%, 0.75)`)
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                datalabels: { display: false },
                title: { display: true, text: <?= json_encode($source == 'history' ? 'Career Path Transitions' : 'Current Positions') ?> }
            },
            scales: {
                y: { beginAtZero: true, ticks: { stepSize: 1, precision: 0 }, title: { display: true, text: 'Number of Alumni' } },
                x: { ticks: { maxRotation: 45, minRotation: 0 } }
            }
        }
    });
});
</script>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>