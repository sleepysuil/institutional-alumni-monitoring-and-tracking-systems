<?php
require_once '../includes/admin_header.php';

// Get program-industry combinations from employment data
$data = $pdo->query("
    SELECT a.program, e.industry, COUNT(*) as cnt
    FROM alumni a
    JOIN employment e ON a.id = e.alumni_id
    WHERE e.industry IS NOT NULL AND e.industry != ''
    GROUP BY a.program, e.industry
")->fetchAll();

// Calculate total alumni per program and per industry for support/confidence
$total_alumni = $pdo->query("SELECT COUNT(*) FROM alumni")->fetchColumn();
$program_counts = $pdo->query("SELECT program, COUNT(*) as cnt FROM alumni GROUP BY program")->fetchAll(PDO::FETCH_KEY_PAIR);
$industry_counts = $pdo->query("SELECT industry, COUNT(*) as cnt FROM employment WHERE industry IS NOT NULL GROUP BY industry")->fetchAll(PDO::FETCH_KEY_PAIR);

$rules = [];
foreach ($data as $row) {
    $program = $row['program'];
    $industry = $row['industry'];
    $count = $row['cnt'];
    
    $support = $total_alumni ? round(($count / $total_alumni) * 100, 1) : 0;
    $confidence = isset($program_counts[$program]) ? round(($count / $program_counts[$program]) * 100, 1) : 0;
    $expected = isset($industry_counts[$industry]) ? $industry_counts[$industry] / $total_alumni : 0;
    $lift = $expected ? round($confidence / 100 / $expected, 2) : 0;
    $strength = $lift > 1.2 ? 'Strong' : ($lift > 0.8 ? 'Moderate' : 'Weak');
    
    $rules[] = [
        'program' => $program,
        'industry' => $industry,
        'support' => $support,
        'confidence' => $confidence,
        'lift' => $lift,
        'strength' => $strength
    ];
}

// Sort by lift descending
usort($rules, fn($a, $b) => $b['lift'] <=> $a['lift']);
?>
<div class="page-header">
    <h1>Association Rules</h1>
    <p class="text-muted">Program-Industry Association (based on employment data)</p>
</div>

<?php if (empty($rules)): ?>
    <div class="alert alert-warning">No association data available. Please ensure employment records include industry.</div>
<?php endif; ?>

<div class="card">
    <div class="card-body">
        <table class="table">
            <thead>
                <tr><th>Program</th><th>Industry</th><th>Support (%)</th><th>Confidence (%)</th><th>Lift</th><th>Strength</th></tr>
            </thead>
            <tbody>
                <?php foreach ($rules as $r): ?>
                <tr>
                    <td><?= htmlspecialchars($r['program']) ?></td>
                    <td><?= htmlspecialchars($r['industry']) ?></td>
                    <td><?= $r['support'] ?></td>
                    <td><?= $r['confidence'] ?></td>
                    <td><?= $r['lift'] ?></td>
                    <td><span class="badge bg-<?= $r['strength']=='Strong'?'success':($r['strength']=='Moderate'?'warning':'secondary') ?>"><?= $r['strength'] ?></span></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <div class="alert alert-info mt-3">
            <strong>Understanding Metrics:</strong> Support – frequency of combination; Confidence – probability that a graduate from the program works in that industry; Lift – how much more likely than random (>1 is positive association).
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>