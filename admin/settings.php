<?php
require_once '../includes/admin_header.php';
if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

// Is the logged-in admin a super admin?
$check = $pdo->prepare("SELECT is_super_admin FROM users WHERE id = ?");
$check->execute([$_SESSION['user_id']]);
$is_super = (bool)$check->fetchColumn();

$flash = function ($text, $type = 'success') {
    $_SESSION['message'] = $text;
    $_SESSION['message_type'] = $type;
};

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        $flash("Invalid request. Please try again.", 'danger');
        redirect('settings.php');
    }

    // ---- Super admin management ----
    if (isset($_POST['make_super_admin'])) {
        if (!$is_super) {
            $flash("Only a Super Admin can perform this action.", 'danger');
        } else {
            // only real admin accounts can be promoted
            $u = $pdo->prepare("UPDATE users SET is_super_admin = 1 WHERE id = ? AND role = 'admin'");
            $u->execute([(int)$_POST['user_id']]);
            $flash($u->rowCount() ? "User promoted to Super Admin." : "That account could not be promoted.", $u->rowCount() ? 'success' : 'danger');
        }
        redirect('settings.php');
    }

    if (isset($_POST['remove_super_admin'])) {
        $target = (int)$_POST['user_id'];
        $supers = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin' AND is_super_admin = 1")->fetchColumn();
        if (!$is_super) {
            $flash("Only a Super Admin can perform this action.", 'danger');
        } elseif ($target === (int)$_SESSION['user_id']) {
            $flash("You cannot remove your own Super Admin status.", 'danger');
        } elseif ($supers <= 1) {
            $flash("At least one Super Admin must remain.", 'danger');
        } else {
            $pdo->prepare("UPDATE users SET is_super_admin = 0 WHERE id = ?")->execute([$target]);
            $flash("Super Admin privileges removed.");
        }
        redirect('settings.php');
    }

    // ---- Approval setting ----
    if (isset($_POST['update_approval_settings'])) {
        $val = isset($_POST['require_admin_approval']) ? 1 : 0;
        $pdo->prepare("INSERT INTO system_settings (setting_key, setting_value) VALUES ('require_admin_approval', ?) ON DUPLICATE KEY UPDATE setting_value = ?")
            ->execute([$val, $val]);
        $flash("Approval settings updated.");
        redirect('settings.php');
    }

    // ---- Alumni approvals ----
    if (isset($_POST['approve_alumni'])) {
        $pdo->prepare("UPDATE alumni SET is_approved = 1, approval_status = 'approved' WHERE id = ?")->execute([(int)$_POST['alumni_id']]);
        $flash("Alumni approved successfully.");
        redirect('settings.php');
    }
    if (isset($_POST['reject_alumni'])) {
        $pdo->prepare("UPDATE alumni SET is_approved = 0, approval_status = 'rejected' WHERE id = ?")->execute([(int)$_POST['alumni_id']]);
        $flash("Alumni application rejected.");
        redirect('settings.php');
    }
    if (isset($_POST['reconsider_alumni'])) {
        $pdo->prepare("UPDATE alumni SET is_approved = 0, approval_status = 'pending' WHERE id = ? AND approval_status = 'rejected'")->execute([(int)$_POST['alumni_id']]);
        $flash("Moved back to pending.");
        redirect('settings.php');
    }
    if (isset($_POST['approve_all'])) {
        $n = $pdo->exec("UPDATE alumni SET is_approved = 1, approval_status = 'approved' WHERE is_approved = 0 AND approval_status = 'pending'");
        $flash($n . " alumni approved.");
        redirect('settings.php');
    }
}

// Current approval setting (default: approval required)
$require_approval = $pdo->query("SELECT setting_value FROM system_settings WHERE setting_key = 'require_admin_approval'")->fetchColumn();
$require_approval = $require_approval === false ? 1 : (int)$require_approval;

// LEFT JOIN so alumni without a login account still show up
$pending_alumni = $pdo->query("
    SELECT a.*, u.email AS user_email
    FROM alumni a
    LEFT JOIN users u ON a.id = u.alumni_id
    WHERE a.is_approved = 0 AND a.approval_status = 'pending'
    ORDER BY a.created_at DESC
")->fetchAll();

$rejected_alumni = $pdo->query("
    SELECT id, first_name, last_name, student_id, program FROM alumni
    WHERE approval_status = 'rejected' ORDER BY created_at DESC LIMIT 10
")->fetchAll();

$counts = $pdo->query("SELECT approval_status, COUNT(*) FROM alumni GROUP BY approval_status")->fetchAll(PDO::FETCH_KEY_PAIR);

$admins = $pdo->query("
    SELECT u.* FROM users u
    WHERE u.role = 'admin'
    ORDER BY u.is_super_admin DESC, u.created_at ASC
")->fetchAll();

$message = $_SESSION['message'] ?? '';
$message_type = $_SESSION['message_type'] ?? 'success';
unset($_SESSION['message'], $_SESSION['message_type']);
$csrf = $_SESSION['csrf_token'];
$e = fn($v) => htmlspecialchars((string)($v ?? ''));
?>
<div class="page-header">
    <h1>System Settings</h1>
</div>

<?php if ($message): ?><div class="alert alert-<?= $e($message_type) ?>"><?= $e($message) ?></div><?php endif; ?>

<div class="row">
    <div class="col-md-6">
        <div class="card mb-4">
            <div class="card-header">Registration Approval Settings</div>
            <div class="card-body">
                <form method="post">
                    <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                    <div class="form-check mb-3">
                        <input type="checkbox" class="form-check-input" id="require_admin_approval" name="require_admin_approval" value="1" <?= $require_approval ? 'checked' : '' ?>>
                        <label class="form-check-label" for="require_admin_approval">Require admin approval for new alumni registrations</label>
                    </div>
                    <button type="submit" name="update_approval_settings" class="btn btn-primary">Save Settings</button>
                </form>
                <hr>
                <div class="d-flex gap-2 flex-wrap small">
                    <span class="badge bg-warning text-dark">Pending: <?= (int)($counts['pending'] ?? 0) ?></span>
                    <span class="badge bg-success">Approved: <?= (int)($counts['approved'] ?? 0) ?></span>
                    <span class="badge bg-danger">Rejected: <?= (int)($counts['rejected'] ?? 0) ?></span>
                </div>
            </div>
        </div>

        <!-- Pending Approvals -->
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Pending Alumni Approvals
                    <?php if (count($pending_alumni) > 0): ?><span class="badge bg-danger ms-2"><?= count($pending_alumni) ?> pending</span><?php endif; ?>
                </span>
                <?php if (count($pending_alumni) > 1): ?>
                <form method="post" class="d-inline" onsubmit="return confirm('Approve ALL <?= count($pending_alumni) ?> pending alumni?')">
                    <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                    <button type="submit" name="approve_all" class="btn btn-sm btn-outline-success">Approve all</button>
                </form>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <?php if (empty($pending_alumni)): ?>
                    <p class="text-muted mb-0">No pending alumni approvals.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle">
                            <thead><tr><th>Name</th><th>Student ID</th><th>Program</th><th>Year</th><th>Registered</th><th>Actions</th></tr></thead>
                            <tbody>
                                <?php foreach ($pending_alumni as $al): ?>
                                <tr>
                                    <td><a href="view_alumni.php?id=<?= (int)$al['id'] ?>" class="text-decoration-none"><?= $e($al['first_name'] . ' ' . $al['last_name']) ?></a></td>
                                    <td><?= $e($al['student_id']) ?></td>
                                    <td><?= $e($al['program']) ?></td>
                                    <td><?= (int)$al['graduation_year'] ?></td>
                                    <td><?= date('M j, Y', strtotime($al['created_at'])) ?></td>
                                    <td>
                                        <form method="post" class="d-inline-flex gap-1">
                                            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                                            <input type="hidden" name="alumni_id" value="<?= (int)$al['id'] ?>">
                                            <button type="submit" name="approve_alumni" class="btn btn-sm btn-success" onclick="return confirm('Approve this alumni?')"><i class="fas fa-check"></i></button>
                                            <button type="submit" name="reject_alumni" class="btn btn-sm btn-danger" onclick="return confirm('Reject this alumni?')"><i class="fas fa-times"></i></button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($rejected_alumni): ?>
        <div class="card">
            <div class="card-header">Recently Rejected</div>
            <ul class="list-group list-group-flush">
                <?php foreach ($rejected_alumni as $ra): ?>
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    <span><?= $e($ra['first_name'] . ' ' . $ra['last_name']) ?> <small class="text-muted">· <?= $e($ra['student_id']) ?> · <?= $e($ra['program']) ?></small></span>
                    <form method="post" class="d-inline">
                        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                        <input type="hidden" name="alumni_id" value="<?= (int)$ra['id'] ?>">
                        <button type="submit" name="reconsider_alumni" class="btn btn-sm btn-outline-secondary">Reconsider</button>
                    </form>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>
    </div>

    <!-- Admin Management -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">Admin Management</div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm align-middle">
                        <thead><tr><th>Email</th><th>Role</th><th>Created</th><?php if ($is_super): ?><th>Actions</th><?php endif; ?></tr></thead>
                        <tbody>
                            <?php foreach ($admins as $admin): ?>
                            <tr>
                                <td><?= $e($admin['email']) ?><?= (int)$admin['id'] === (int)$_SESSION['user_id'] ? ' <span class="badge bg-light text-dark">you</span>' : '' ?></td>
                                <td>
                                    <?php if ($admin['is_super_admin']): ?><span class="badge bg-warning text-dark">Super Admin</span>
                                    <?php else: ?><span class="badge bg-secondary">Admin</span><?php endif; ?>
                                </td>
                                <td><?= date('M j, Y', strtotime($admin['created_at'])) ?></td>
                                <?php if ($is_super): ?>
                                <td>
                                    <form method="post" class="d-inline">
                                        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                                        <input type="hidden" name="user_id" value="<?= (int)$admin['id'] ?>">
                                        <?php if ($admin['is_super_admin']): ?>
                                            <?php if ((int)$admin['id'] !== (int)$_SESSION['user_id']): ?>
                                            <button type="submit" name="remove_super_admin" class="btn btn-sm btn-warning" onclick="return confirm('Remove Super Admin status?')"><i class="fas fa-star"></i> Remove Super</button>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <button type="submit" name="make_super_admin" class="btn btn-sm btn-primary" onclick="return confirm('Make this user a Super Admin?')"><i class="fas fa-crown"></i> Make Super</button>
                                        <?php endif; ?>
                                    </form>
                                </td>
                                <?php endif; ?>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <small class="text-muted">Super Admins have full system access and can manage other admins. <?= $is_super ? '' : 'Only a Super Admin can change these roles.' ?></small>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>