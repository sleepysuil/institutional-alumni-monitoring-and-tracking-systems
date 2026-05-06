<?php
require_once '../includes/admin_header.php';

// Handle Super Admin actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['make_super_admin'])) {
        $user_id = $_POST['user_id'];
        // Check if current user is super admin
        $check = $pdo->prepare("SELECT is_super_admin FROM users WHERE id = ?");
        $check->execute([$_SESSION['user_id']]);
        $is_super = $check->fetchColumn();
        
        if ($is_super) {
            $pdo->prepare("UPDATE users SET is_super_admin = 1 WHERE id = ?")->execute([$user_id]);
            $_SESSION['message'] = "User promoted to Super Admin.";
        } else {
            $_SESSION['message'] = "Only Super Admin can perform this action.";
        }
        redirect('settings.php');
    }
    
    if (isset($_POST['remove_super_admin'])) {
        $user_id = $_POST['user_id'];
        $check = $pdo->prepare("SELECT is_super_admin FROM users WHERE id = ?");
        $check->execute([$_SESSION['user_id']]);
        $is_super = $check->fetchColumn();
        
        if ($is_super && $user_id != $_SESSION['user_id']) {
            $pdo->prepare("UPDATE users SET is_super_admin = 0 WHERE id = ?")->execute([$user_id]);
            $_SESSION['message'] = "Super Admin privileges removed.";
        } else {
            $_SESSION['message'] = "Cannot remove your own super admin status.";
        }
        redirect('settings.php');
    }
    
    if (isset($_POST['update_approval_settings'])) {
        $require_approval = isset($_POST['require_admin_approval']) ? 1 : 0;
        // Save to a settings table (create if not exists)
        $pdo->prepare("INSERT INTO system_settings (setting_key, setting_value) VALUES ('require_admin_approval', ?) ON DUPLICATE KEY UPDATE setting_value = ?")
            ->execute([$require_approval, $require_approval]);
        $_SESSION['message'] = "Approval settings updated.";
        redirect('settings.php');
    }
    
    if (isset($_POST['approve_alumni'])) {
        $alumni_id = $_POST['alumni_id'];
        $pdo->prepare("UPDATE alumni SET is_approved = 1, approval_status = 'approved' WHERE id = ?")->execute([$alumni_id]);
        $_SESSION['message'] = "Alumni approved successfully.";
        redirect('settings.php');
    }
    
    if (isset($_POST['reject_alumni'])) {
        $alumni_id = $_POST['alumni_id'];
        $pdo->prepare("UPDATE alumni SET is_approved = 0, approval_status = 'rejected' WHERE id = ?")->execute([$alumni_id]);
        $_SESSION['message'] = "Alumni application rejected.";
        redirect('settings.php');
    }
}

// Get current approval setting
$require_approval = $pdo->query("SELECT setting_value FROM system_settings WHERE setting_key = 'require_admin_approval'")->fetchColumn();
$require_approval = $require_approval === false ? 1 : $require_approval; // Default to requiring approval

// Get pending alumni for approval
$pending_alumni = $pdo->query("
    SELECT a.*, u.email as user_email 
    FROM alumni a 
    JOIN users u ON a.id = u.alumni_id 
    WHERE a.is_approved = 0 AND a.approval_status = 'pending'
    ORDER BY a.created_at DESC
")->fetchAll();

// Get all admin users (including super admins)
$admins = $pdo->query("
    SELECT u.*, 
           CASE WHEN u.is_super_admin = 1 THEN 'Super Admin' ELSE 'Admin' END as admin_type
    FROM users u 
    WHERE u.role = 'admin'
    ORDER BY u.is_super_admin DESC, u.created_at ASC
")->fetchAll();

$message = $_SESSION['message'] ?? '';
unset($_SESSION['message']);
?>
<div class="page-header">
    <h1>System Settings</h1>
</div>

<?php if ($message): ?>
    <div class="alert alert-success"><?= $message ?></div>
<?php endif; ?>

<div class="row">
    <!-- Alumni Approval Settings -->
    <div class="col-md-6">
        <div class="card mb-4">
            <div class="card-header">Registration Approval Settings</div>
            <div class="card-body">
                <form method="post">
                    <div class="form-check mb-3">
                        <input type="checkbox" class="form-check-input" id="require_admin_approval" name="require_admin_approval" value="1" <?= $require_approval ? 'checked' : '' ?>>
                        <label class="form-check-label" for="require_admin_approval">
                            Require admin approval for new alumni registrations
                        </label>
                    </div>
                    <button type="submit" name="update_approval_settings" class="btn btn-primary">Save Settings</button>
                </form>
            </div>
        </div>
        
        <!-- Pending Approvals -->
        <div class="card">
            <div class="card-header">
                Pending Alumni Approvals
                <?php if (count($pending_alumni) > 0): ?>
                    <span class="badge bg-danger float-end"><?= count($pending_alumni) ?> pending</span>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <?php if (empty($pending_alumni)): ?>
                    <p class="text-muted">No pending alumni approvals.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Student ID</th>
                                    <th>Program</th>
                                    <th>Year</th>
                                    <th>Registered</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pending_alumni as $al): ?>
                                <tr>
                                    <td><?= htmlspecialchars($al['first_name'] . ' ' . $al['last_name']) ?></td>
                                    <td><?= htmlspecialchars($al['student_id']) ?></td>
                                    <td><?= htmlspecialchars($al['program']) ?></td>
                                    <td><?= $al['graduation_year'] ?></td>
                                    <td><?= date('M j, Y', strtotime($al['created_at'])) ?></td>
                                    <td>
                                        <form method="post" style="display: inline-block;">
                                            <input type="hidden" name="alumni_id" value="<?= $al['id'] ?>">
                                            <button type="submit" name="approve_alumni" class="btn btn-sm btn-success" onclick="return confirm('Approve this alumni?')">
                                                <i class="fas fa-check"></i> Approve
                                            </button>
                                            <button type="submit" name="reject_alumni" class="btn btn-sm btn-danger" onclick="return confirm('Reject this alumni?')">
                                                <i class="fas fa-times"></i> Reject
                                            </button>
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
    </div>
    
    <!-- Admin Management -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">Admin Management</div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($admins as $admin): ?>
                            <tr>
                                <td><?= htmlspecialchars($admin['email']) ?></td>
                                <td>
                                    <?php if ($admin['is_super_admin']): ?>
                                        <span class="badge bg-warning">Super Admin</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Admin</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= date('M j, Y', strtotime($admin['created_at'])) ?></td>
                                <td>
                                    <?php if ($admin['is_super_admin']): ?>
                                        <form method="post" style="display: inline-block;">
                                            <input type="hidden" name="user_id" value="<?= $admin['id'] ?>">
                                            <button type="submit" name="remove_super_admin" class="btn btn-sm btn-warning" onclick="return confirm('Remove Super Admin status?')">
                                                <i class="fas fa-star"></i> Remove Super
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <form method="post" style="display: inline-block;">
                                            <input type="hidden" name="user_id" value="<?= $admin['id'] ?>">
                                            <button type="submit" name="make_super_admin" class="btn btn-sm btn-primary" onclick="return confirm('Make this user a Super Admin?')">
                                                <i class="fas fa-crown"></i> Make Super
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <small class="text-muted">Super Admins have full system access and can manage other admins.</small>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>