<?php
require_once '../includes/admin_header.php';
if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

$categories = ['event' => '🎉 Event', 'survey' => '📊 Survey', 'employment' => '💼 Employment', 'welcome' => '👋 Welcome', 'general' => '📢 General'];
$types = ['sms', 'email', 'both'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        $_SESSION['message'] = "Invalid request. Please try again.";
        $_SESSION['message_type'] = 'danger';
        redirect('message_templates.php');
    }

    if (isset($_POST['save_template']) || isset($_POST['update_template'])) {
        $name = cleanInput($_POST['name'] ?? '');
        $category = array_key_exists($_POST['category'] ?? '', $categories) ? $_POST['category'] : 'general';
        $subject = cleanInput($_POST['subject'] ?? '');
        $content = cleanInput($_POST['content'] ?? '');
        $type = in_array($_POST['message_type'] ?? '', $types, true) ? $_POST['message_type'] : 'both';

        if ($name === '' || $content === '') {
            $_SESSION['message'] = "Template name and content are required.";
            $_SESSION['message_type'] = 'danger';
        } elseif (isset($_POST['update_template'])) {
            $pdo->prepare("UPDATE message_templates SET name=?, category=?, subject=?, content=?, type=? WHERE id=?")
                ->execute([$name, $category, $subject, $content, $type, (int)($_POST['id'] ?? 0)]);
            $_SESSION['message'] = "Template updated.";
        } else {
            $pdo->prepare("INSERT INTO message_templates (name, category, subject, content, type, created_by) VALUES (?, ?, ?, ?, ?, ?)")
                ->execute([$name, $category, $subject, $content, $type, $_SESSION['user_id']]);
            $_SESSION['message'] = "Template saved successfully.";
        }
    } elseif (isset($_POST['delete_template'])) {
        $pdo->prepare("DELETE FROM message_templates WHERE id = ?")->execute([(int)$_POST['delete_template']]);
        $_SESSION['message'] = "Template deleted.";
    }
    redirect('message_templates.php');
}

// LEFT JOIN so templates stay listed if their creator's account was removed
$templates = $pdo->query("SELECT t.*, u.email as created_by_email
                          FROM message_templates t
                          LEFT JOIN users u ON t.created_by = u.id
                          ORDER BY t.created_at DESC")->fetchAll();
$message = $_SESSION['message'] ?? '';
$message_type = $_SESSION['message_type'] ?? 'success';
unset($_SESSION['message'], $_SESSION['message_type']);
$csrf = $_SESSION['csrf_token'];
$e = fn($v) => htmlspecialchars((string)($v ?? ''));
?>
<div class="page-header">
    <h1>Message Templates</h1>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addTemplateModal">
        <i class="fas fa-plus me-2"></i>New Template
    </button>
</div>

<ul class="nav-tabs mb-4">
    <li><a class="nav-link" href="send_messages.php">Compose Message</a></li>
    <li><a class="nav-link" href="message_history.php">Message History</a></li>
    <li><a class="nav-link active" href="message_templates.php">Templates</a></li>
</ul>

<?php if ($message): ?><div class="alert alert-<?= $e($message_type) ?>"><?= $e($message) ?></div><?php endif; ?>

<div class="alert alert-light border small">
    <strong>Tip:</strong> use <code>{first_name}</code>, <code>{last_name}</code>, <code>{program}</code> and <code>{year}</code> in a template and they are replaced with each alumnus's details when the message is sent.
</div>

<div class="card">
    <div class="card-body">
        <?php if (!$templates): ?><p class="text-muted mb-0">No templates yet.</p><?php else: ?>
        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr><th>Name</th><th>Category</th><th>Type</th><th>Subject</th><th>Created By</th><th>Actions</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($templates as $t): $subj = $t['subject'] ?? ''; ?>
                    <tr>
                        <td><?= $e($t['name']) ?></td>
                        <td><span class="badge bg-secondary"><?= $e($categories[$t['category']] ?? $t['category']) ?></span></td>
                        <td>
                            <?php if ($t['type'] == 'sms'): ?><span class="badge bg-info">SMS</span>
                            <?php elseif ($t['type'] == 'email'): ?><span class="badge bg-primary">Email</span>
                            <?php else: ?><span class="badge bg-secondary">Both</span><?php endif; ?>
                        </td>
                        <td><?= $e(mb_substr($subj, 0, 40)) ?><?= mb_strlen($subj) > 40 ? '...' : '' ?></td>
                        <td><?= $e($t['created_by_email'] ?? 'Unknown') ?></td>
                        <td>
                            <a class="btn btn-sm btn-info" title="Use in a new message"
                               href="send_messages.php?<?= http_build_query(['subject' => $subj, 'content' => $t['content'], 'type' => $t['type'], 'category' => $t['category']]) ?>">
                                <i class="fas fa-paper-plane"></i> Use
                            </a>
                            <button type="button" class="btn btn-sm btn-outline-primary edit-template" title="Edit"
                                    data-id="<?= (int)$t['id'] ?>"
                                    data-name="<?= $e($t['name']) ?>"
                                    data-category="<?= $e($t['category']) ?>"
                                    data-type="<?= $e($t['type']) ?>"
                                    data-subject="<?= $e($subj) ?>"
                                    data-content="<?= $e($t['content']) ?>"><i class="fas fa-edit"></i></button>
                            <form method="post" class="d-inline" onsubmit="return confirm('Delete this template?')">
                                <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                                <input type="hidden" name="delete_template" value="<?= (int)$t['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button>
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

<?php
// One form partial used by both the add and edit modals
function templateFields($prefix, $categories) { ?>
    <div class="mb-3">
        <label class="form-label">Template Name</label>
        <input type="text" name="name" id="<?= $prefix ?>_name" class="form-control" maxlength="100" required>
    </div>
    <div class="row">
        <div class="col-md-6 mb-3">
            <label class="form-label">Category</label>
            <select name="category" id="<?= $prefix ?>_category" class="form-select" required>
                <?php foreach ($categories as $v => $l): ?><option value="<?= $v ?>"><?= $l ?></option><?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-6 mb-3">
            <label class="form-label">Message Type</label>
            <select name="message_type" id="<?= $prefix ?>_type" class="form-select" required>
                <option value="sms">SMS Only</option>
                <option value="email">Email Only</option>
                <option value="both">Both</option>
            </select>
        </div>
    </div>
    <div class="mb-3">
        <label class="form-label">Subject (for email)</label>
        <input type="text" name="subject" id="<?= $prefix ?>_subject" class="form-control" maxlength="200" placeholder="Email subject">
    </div>
    <div class="mb-3">
        <label class="form-label">Content</label>
        <textarea name="content" id="<?= $prefix ?>_content" class="form-control" rows="5" required></textarea>
    </div>
<?php } ?>

<!-- Add Template Modal -->
<div class="modal fade" id="addTemplateModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="post">
                <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                <div class="modal-header">
                    <h5 class="modal-title">New Template</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body"><?php templateFields('add', $categories); ?></div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="save_template" class="btn btn-primary">Save Template</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Template Modal -->
<div class="modal fade" id="editTemplateModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="post">
                <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                <input type="hidden" name="id" id="edit_id">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Template</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body"><?php templateFields('edit', $categories); ?></div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="update_template" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.querySelectorAll('.edit-template').forEach(function (btn) {
    btn.addEventListener('click', function () {
        const d = btn.dataset;
        document.getElementById('edit_id').value = d.id;
        document.getElementById('edit_name').value = d.name;
        document.getElementById('edit_category').value = d.category;
        document.getElementById('edit_type').value = d.type;
        document.getElementById('edit_subject').value = d.subject;
        document.getElementById('edit_content').value = d.content;
        new bootstrap.Modal(document.getElementById('editTemplateModal')).show();
    });
});
</script>

<?php include '../includes/footer.php'; ?>