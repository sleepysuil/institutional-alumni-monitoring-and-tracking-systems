<?php
require_once '../includes/admin_header.php';
require_once '../includes/mining_helpers.php';

/**
 * Real K-Means (the old page just split graduation years into 3 equal ranges).
 * Deterministic: farthest-point initialisation, so results don't change on refresh.
 * $points = list of [x, y] already normalised to 0..1
 */
function kmeans(array $points, int $k, int $maxIter = 100): array {
    $n = count($points);
    $k = max(1, min($k, $n));

    $dist = fn($a, $b) => ($a[0] - $b[0]) ** 2 + ($a[1] - $b[1]) ** 2;

    // init: start with the point nearest the mean, then keep adding the farthest point
    $mean = [array_sum(array_column($points, 0)) / $n, array_sum(array_column($points, 1)) / $n];
    $first = 0; $best = INF;
    foreach ($points as $i => $p) { $d = $dist($p, $mean); if ($d < $best) { $best = $d; $first = $i; } }
    $centroids = [$points[$first]];
    while (count($centroids) < $k) {
        $far = 0; $farD = -1;
        foreach ($points as $i => $p) {
            $d = min(array_map(fn($c) => $dist($p, $c), $centroids));
            if ($d > $farD) { $farD = $d; $far = $i; }
        }
        $centroids[] = $points[$far];
    }

    $assign = array_fill(0, $n, -1);
    for ($iter = 0; $iter < $maxIter; $iter++) {
        $changed = false;
        foreach ($points as $i => $p) {
            $bestC = 0; $bestD = INF;
            foreach ($centroids as $ci => $c) {
                $d = $dist($p, $c);
                if ($d < $bestD) { $bestD = $d; $bestC = $ci; }
            }
            if ($assign[$i] !== $bestC) { $assign[$i] = $bestC; $changed = true; }
        }
        if (!$changed) break;
        foreach ($centroids as $ci => $_) {
            $members = array_keys(array_filter($assign, fn($a) => $a === $ci));
            if (!$members) continue;
            $centroids[$ci] = [
                array_sum(array_map(fn($i) => $points[$i][0], $members)) / count($members),
                array_sum(array_map(fn($i) => $points[$i][1], $members)) / count($members),
            ];
        }
    }
    return $assign;
}

$k = (int)($_GET['k'] ?? 3);
$k = max(2, min(5, $k));

$rows = array_values(array_filter(getAlumniProfiles($pdo), fn($p) => $p['salary'] !== null && $p['year'] > 0));
$n = count($rows);

$clusters = [];
$summary  = [];

if ($n >= 2) {
    $k = min($k, $n);
    $xs = array_column($rows, 'year');
    $ys = array_column($rows, 'salary');
    $minX = min($xs); $rngX = max($xs) - $minX;
    $minY = min($ys); $rngY = max($ys) - $minY;

    $norm = array_map(fn($r) => [
        $rngX ? ($r['year']   - $minX) / $rngX : 0,
        $rngY ? ($r['salary'] - $minY) / $rngY : 0,
    ], $rows);

    $assign = kmeans($norm, $k);

    $groups = [];
    foreach ($rows as $i => $r) $groups[$assign[$i]][] = $r;

    // order groups from lowest to highest average salary, then name them
    uasort($groups, fn($a, $b) => array_sum(array_column($a, 'salary')) / count($a) <=> array_sum(array_column($b, 'salary')) / count($b));
    $groups = array_values($groups);

    $names  = $k == 3 ? ['Entry-Level Earners', 'Mid-Level Earners', 'High Earners'] : [];
    $colors = ['#EF4444', '#10B981', '#8B5CF6', '#F59E0B', '#3B82F6'];

    foreach ($groups as $gi => $g) {
        $name = $names[$gi] ?? ('Cluster ' . ($gi + 1) . ($gi == 0 ? ' (lowest pay)' : ($gi == count($groups) - 1 ? ' (highest pay)' : '')));
        $clusters[] = [
            'name'   => $name,
            'color'  => $colors[$gi % count($colors)],
            'points' => array_map(fn($r) => ['x' => $r['year'], 'y' => round($r['salary']), 'label' => $r['name']], $g),
        ];
        $sal = array_column($g, 'salary');
        $summary[] = [
            'name'   => $name, 'color' => $colors[$gi % count($colors)],
            'count'  => count($g),
            'year'   => round(array_sum(array_column($g, 'year')) / count($g)),
            'salary' => round(array_sum($sal) / count($sal)),
            'min'    => min($sal), 'max' => max($sal),
            'members' => array_map(fn($r) => ['id' => $r['id'], 'name' => $r['name']], $g),
        ];
    }
}
$has_data = $n >= 2;
?>
<div class="page-header">
    <h1>Clustering Analysis</h1>
    <form method="get" class="d-flex align-items-center gap-2">
        <label class="form-label mb-0">Clusters</label>
        <select name="k" class="form-select form-select-sm" style="width:auto" onchange="this.form.submit()">
            <?php for ($i = 2; $i <= 5; $i++): ?>
                <option value="<?= $i ?>" <?= $i == $k ? 'selected' : '' ?>><?= $i ?></option>
            <?php endfor; ?>
        </select>
    </form>
</div>
<p class="text-muted">K-Means segmentation of alumni by graduation year and salary (salary ranges use their midpoint).</p>

<?php if (!$has_data): ?>
    <div class="alert alert-warning">At least 2 alumni with salary data are needed. Salary comes from the alumni's "Current Employment" profile section.</div>
<?php else: ?>
<div class="row g-4">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">Cluster Visualization (<?= $n ?> alumni)</div>
            <div class="card-body">
                <div class="chart-container" style="height: 380px;">
                    <canvas id="clusterChart"></canvas>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <?php foreach ($summary as $c): ?>
        <div class="card mb-3" style="border-left: 5px solid <?= $c['color'] ?>;">
            <div class="card-body">
                <h5><?= htmlspecialchars($c['name']) ?></h5>
                <ul class="list-unstyled small mb-0">
                    <li><strong>Alumni:</strong> <?= $c['count'] ?></li>
                    <li><strong>Avg Grad Year:</strong> <?= $c['year'] ?></li>
                    <li><strong>Avg Salary:</strong> ₱<?= number_format($c['salary']) ?></li>
                    <li><strong>Range:</strong> ₱<?= number_format($c['min']) ?> – ₱<?= number_format($c['max']) ?></li>
                </ul>
                <details class="mt-2 small">
                    <summary>View alumni in this cluster</summary>
                    <ul class="mb-0 mt-1">
                        <?php foreach ($c['members'] as $m): ?>
                            <li><a href="view_alumni.php?id=<?= (int)$m['id'] ?>"><?= htmlspecialchars($m['name']) ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                </details>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const datasets = <?= json_encode(array_map(fn($c) => [
        'label' => $c['name'], 'data' => $c['points'],
        'backgroundColor' => $c['color'], 'pointRadius' => 7, 'pointHoverRadius' => 9,
    ], $clusters), JSON_HEX_TAG | JSON_HEX_AMP) ?>;
    new Chart(document.getElementById('clusterChart'), {
        type: 'scatter',
        data: { datasets: datasets },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                datalabels: { display: false },   // stops "x: 2022, y: 30000" text on every dot
                legend: { position: 'bottom' },
                tooltip: { callbacks: { label: c => `${c.raw.label}: ${c.raw.x}, ₱${c.raw.y.toLocaleString()}` } }
            },
            scales: {
                x: { title: { display: true, text: 'Graduation Year' }, ticks: { stepSize: 1, precision: 0 } },
                y: { title: { display: true, text: 'Salary (₱)' }, ticks: { callback: v => '₱' + v.toLocaleString() } }
            }
        }
    });
});
</script>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>