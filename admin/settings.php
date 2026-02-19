<?php
require_once '../includes/admin_header.php';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Save settings (simulated)
    $_SESSION['message'] = "Settings saved (simulated).";
    redirect('settings.php');
}
$message = $_SESSION['message'] ?? '';
unset($_SESSION['message']);
?>
<div class="page-header">
    <h1>System Settings</h1>
</div>

<?php if ($message): ?><div class="alert alert-success"><?= $message ?></div><?php endif; ?>

<div class="card">
    <div class="card-body">
        <form method="post">
            <h5 class="mb-3">Email Configuration</h5>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">SMTP Host</label>
                    <input type="text" class="form-control" name="smtp_host" value="smtp.gmail.com">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">SMTP Port</label>
                    <input type="text" class="form-control" name="smtp_port" value="587">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">SMTP User</label>
                    <input type="text" class="form-control" name="smtp_user" value="your_email@gmail.com">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">SMTP Password</label>
                    <input type="password" class="form-control" name="smtp_pass" value="password">
                </div>
            </div>
            <h5 class="mb-3 mt-4">SMS Configuration</h5>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">API Key</label>
                    <input type="text" class="form-control" name="api_key">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">API Secret</label>
                    <input type="text" class="form-control" name="api_secret">
                </div>
            </div>
            <button type="submit" class="btn btn-primary">Save Settings</button>
        </form>
    </div>
</div>

<?php include '../includes/footer.php'; ?>