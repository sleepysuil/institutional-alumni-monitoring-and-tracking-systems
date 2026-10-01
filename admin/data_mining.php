<?php
require_once '../includes/admin_header.php';
require_once '../includes/mining_helpers.php';

// How much data the mining tools have to work with
$profiles = getAlumniProfiles($pdo);
$total = count($profiles);
$with_salary = count(array_filter($profiles, fn($p) => $p['salary'] !== null));
$with_industry = count(array_filter($profiles, fn($p) => $p['working'] && $p['industry']));

$tools = [
    ['clustering.php',  'fa-project-diagram',       'Clustering',  'K-Means alumni segmentation',        "$with_salary with salary data",   2],
    ['association.php', 'fa-link',                  'Association', 'Program-industry rules',              "$with_industry with industry",    20],
    ['prediction.php',  'fa-chart-line',            'Prediction',  'Employment outcome forecast',        "$total profiles",                 20],
    ['patterns.php',    'fa-sitemap',               'Patterns',    'Career path discovery',              "$total profiles",                 10],
    ['anomalies.php',   'fa-exclamation-triangle',  'Anomalies',   'Outlier detection',                  "$with_salary with salary data",   3],
    ['trends.php',      'fa-chart-bar',             'Trends',      'Employment rate trends',             "$total profiles",                 10],
    ['skills_gap.php',  'fa-tools',                 'Skills Gap',  'Curriculum improvement insights',    "$total profiles",                 10],
];
$counts_for = [
    'clustering.php' => $with_salary, 'association.php' => $with_industry, 'anomalies.php' => $with_salary,
];
?>
<div class="page-header">
    <h1>Data Mining & Analytics</h1>
    <p class="text-muted">Advanced pattern discovery and predictive analytics</p>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-4"><div class="card text-center"><div class="card-body py-3">
        <div class="fs-3 fw-bold text-primary"><?= $total ?></div><div class="small text-muted">Alumni profiles analysed</div>
    </div></div></div>
    <div class="col-md-4"><div class="card text-center"><div class="card-body py-3">
        <div class="fs-3 fw-bold text-success"><?= $with_salary ?></div><div class="small text-muted">With salary data (clustering, anomalies)</div>
    </div></div></div>
    <div class="col-md-4"><div class="card text-center"><div class="card-body py-3">
        <div class="fs-3 fw-bold text-info"><?= $with_industry ?></div><div class="small text-muted">Working with a known industry (association)</div>
    </div></div></div>
</div>
<?php if ($total < 20): ?>
    <div class="alert alert-info">Only <?= $total ?> alumni profiles so far. Mining results become more reliable as more alumni complete the Tracer Survey and update their employment details.</div>
<?php endif; ?>

<div class="row g-4">
    <?php foreach ($tools as [$href, $icon, $name, $desc, $data_note, $min]):
        $n = $counts_for[$href] ?? $total;
        $ready = $n >= min($min, 3);
    ?>
    <div class="col-md-3">
        <a href="<?= $href ?>" class="card h-100 text-decoration-none">
            <div class="card-body text-center">
                <i class="fas <?= $icon ?> fa-3x mb-3" style="color: #8B5CF6;"></i>
                <h5><?= $name ?></h5>
                <p class="small text-muted mb-2"><?= $desc ?></p>
                <span class="badge bg-<?= $ready ? 'success' : 'secondary' ?>"><?= $data_note ?></span>
            </div>
        </a>
    </div>
    <?php endforeach; ?>
</div>

<?php include '../includes/footer.php'; ?>