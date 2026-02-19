<?php
require_once '../includes/admin_header.php';
$skills = [
    ['industry' => 'Information Technology', 'match' => 65, 'gap' => 35, 'skills' => ['Cloud Computing','AI/ML','Cybersecurity','DevOps'], 'rec' => ['Offer cloud certification','Introduce AI/ML courses']],
    ['industry' => 'Accounting', 'match' => 75, 'gap' => 25, 'skills' => ['Data Analytics','ERP Systems','Financial Modeling'], 'rec' => ['Include data analytics','ERP training']],
    ['industry' => 'Marketing', 'match' => 60, 'gap' => 40, 'skills' => ['Social Media','Analytics','Content Creation'], 'rec' => ['Digital marketing focus','Google Analytics certification']],
];
?>
<div class="page-header">
    <h1>Skills Gap Analysis</h1>
</div>

<?php foreach ($skills as $s): ?>
<div class="card mb-4">
    <div class="card-header"><?= $s['industry'] ?></div>
    <div class="card-body">
        <div class="row align-items-center">
            <div class="col-md-6">
                <p><strong>Current Match:</strong> <?= $s['match'] ?>% <span class="text-danger">Gap: <?= $s['gap'] ?>%</span></p>
                <div class="progress mb-3" style="height: 30px;">
                    <div class="progress-bar bg-success" style="width: <?= $s['match'] ?>%;">Match <?= $s['match'] ?>%</div>
                    <div class="progress-bar bg-danger" style="width: <?= $s['gap'] ?>%;">Gap <?= $s['gap'] ?>%</div>
                </div>
            </div>
            <div class="col-md-6">
                <h6>Required Skills:</h6>
                <ul>
                    <?php foreach ($s['skills'] as $skill): ?><li><?= $skill ?></li><?php endforeach; ?>
                </ul>
                <h6>Recommendations:</h6>
                <ul>
                    <?php foreach ($s['rec'] as $r): ?><li><?= $r ?></li><?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>
</div>
<?php endforeach; ?>

<?php include '../includes/footer.php'; ?>