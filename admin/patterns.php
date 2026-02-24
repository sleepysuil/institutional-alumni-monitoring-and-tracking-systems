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
<?php endif; ?>

<?php include '../includes/footer.php'; ?>