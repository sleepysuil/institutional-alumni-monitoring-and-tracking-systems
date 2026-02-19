<?php
require_once '../includes/alumni_header.php';
$alumni_id = $_SESSION['alumni_id'];
$already = $pdo->prepare("SELECT id FROM tracer_responses WHERE alumni_id = ? AND response_date = CURDATE()");
$already->execute([$alumni_id]);
if ($already->fetch()) $already_submitted = true;

if ($_SERVER['REQUEST_METHOD'] == 'POST' && !isset($already_submitted)) {
    $is_employed = $_POST['is_employed'] ?? 0;
    $job_title = cleanInput($_POST['job_title'] ?? '');
    $company = cleanInput($_POST['company'] ?? '');
    $industry = cleanInput($_POST['industry'] ?? '');
    $relevance = cleanInput($_POST['relevance'] ?? '');
    $pdo->prepare("INSERT INTO tracer_responses (alumni_id, response_date, is_employed, job_title, company, industry, relevance) VALUES (?, CURDATE(), ?, ?, ?, ?, ?)")->execute([$alumni_id, $is_employed, $job_title, $company, $industry, $relevance]);
    $success = "Thank you for completing the survey!";
}
?>
<div class="page-header">
    <h1>Tracer Survey</h1>
</div>

<?php if (isset($already_submitted)): ?>
    <div class="alert alert-info">You have already submitted today. Thank you!</div>
<?php elseif (isset($success)): ?>
    <div class="alert alert-success"><?= $success ?></div>
<?php else: ?>
<div class="card">
    <div class="card-body">
        <form method="post">
            <div class="mb-3">
                <label class="form-label">Are you currently employed?</label><br>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="is_employed" id="yes" value="1" required>
                    <label class="form-check-label" for="yes">Yes</label>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="is_employed" id="no" value="0">
                    <label class="form-check-label" for="no">No</label>
                </div>
            </div>
            <div id="empFields" style="display: none;">
                <div class="mb-3"><label class="form-label">Job Title</label><input type="text" name="job_title" class="form-control"></div>
                <div class="mb-3"><label class="form-label">Company</label><input type="text" name="company" class="form-control"></div>
                <div class="mb-3"><label class="form-label">Industry</label><input type="text" name="industry" class="form-control"></div>
                <div class="mb-3"><label class="form-label">Relevance</label>
                    <select name="relevance" class="form-select">
                        <option value="Highly Relevant">Highly Relevant</option>
                        <option value="Somewhat Relevant">Somewhat Relevant</option>
                        <option value="Not Relevant">Not Relevant</option>
                    </select>
                </div>
            </div>
            <button type="submit" class="btn btn-primary">Submit Survey</button>
        </form>
    </div>
</div>
<script>
document.querySelectorAll('input[name="is_employed"]').forEach(r => r.addEventListener('change', function() {
    document.getElementById('empFields').style.display = this.value == '1' ? 'block' : 'none';
}));
</script>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>