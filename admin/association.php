<?php
require_once '../includes/admin_header.php';
$rules = [
    ['program' => 'BS Information Systems', 'industry' => 'Information Technology', 'support' => 33.3, 'confidence' => 100, 'lift' => 1.5, 'strength' => 'Strong'],
    ['program' => 'BS Computer Science', 'industry' => 'Information Technology', 'support' => 33.3, 'confidence' => 100, 'lift' => 16.7, 'strength' => 'Strong'],
    ['program' => 'BS Business Administration', 'industry' => 'Marketing', 'support' => 16.7, 'confidence' => 100, 'lift' => 6, 'strength' => 'Strong'],
    ['program' => 'BS Accountancy', 'industry' => 'Accounting', 'support' => 16.7, 'confidence' => 100, 'lift' => 6, 'strength' => 'Strong'],
];
?>
<div class="page-header">
    <h1>Association Rules</h1>
    <p class="text-muted">Program-Industry Association</p>
</div>

<div class="card">
    <div class="card-body">
        <table class="table">
            <thead>
                <tr><th>Program</th><th>Industry</th><th>Support (%)</th><th>Confidence (%)</th><th>Lift</th><th>Strength</th></tr>
            </thead>
            <tbody>
                <?php foreach ($rules as $r): ?>
                <tr>
                    <td><?= $r['program'] ?></td>
                    <td><?= $r['industry'] ?></td>
                    <td><?= $r['support'] ?></td>
                    <td><?= $r['confidence'] ?></td>
                    <td><?= $r['lift'] ?></td>
                    <td><span class="badge bg-success"><?= $r['strength'] ?></span></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <div class="alert alert-info mt-3">
            <strong>Understanding Metrics:</strong> Support – frequency; Confidence – probability; Lift – how much more likely than random (>1 is good).
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>