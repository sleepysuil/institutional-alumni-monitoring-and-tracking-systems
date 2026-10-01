<?php
require_once '../includes/admin_header.php';
if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        $_SESSION['message'] = "Invalid request. Please try again.";
        $_SESSION['message_type'] = 'danger';
        redirect('announcements.php');
    }

    if (isset($_POST['add_announcement']) || isset($_POST['edit_announcement'])) {
        $title = cleanInput($_POST['title'] ?? '');
        $content = cleanInput($_POST['content'] ?? '');
        if ($title === '' || $content === '') {
            $_SESSION['message'] = "Title and content are required.";
            $_SESSION['message_type'] = 'danger';
        } elseif (isset($_POST['add_announcement'])) {
            $pdo->prepare("INSERT INTO announcements (title, content, posted_by, posted_date) VALUES (?, ?, ?, NOW())")
                ->execute([$title, $content, $_SESSION['user_id']]);
            $_SESSION['message'] = "Announcement posted.";
        } else {
            $pdo->prepare("UPDATE announcements SET title = ?, content = ? WHERE id = ?")
                ->execute([$title, $content, (int)($_POST['id'] ?? 0)]);
            $_SESSION['message'] = "Announcement updated.";
        }
    } elseif (isset($_POST['delete_announcement'])) {
        $pdo->prepare("DELETE FROM announcements WHERE id = ?")->execute([(int)$_POST['delete_announcement']]);
        $_SESSION['message'] = "Announcement deleted.";
    }
    redirect('announcements.php');
}

$announcements = $pdo->query("SELECT * FROM announcements ORDER BY posted_date DESC")->fetchAll();
$message = $_SESSION['message'] ?? '';
$message_type = $_SESSION['message_type'] ?? 'success';
unset($_SESSION['message'], $_SESSION['message_type']);
$csrf = $_SESSION['csrf_token'];
$e = fn($v) => htmlspecialchars((string)($v ?? ''));
?>
<div class="page-header">
    <h1>Announcements</h1>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal"><i class="fas fa-plus me-2"></i>New Announcement</button>
</div>

<?php if ($message): ?><div class="alert alert-<?= $e($message_type) ?>"><?= $e($message) ?></div><?php endif; ?>

<div class="card">
    <div class="card-body">
        <?php if (empty($announcements)): ?>
            <p class="text-muted mb-0">No announcements yet.</p>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table align-middle">
                <thead><tr><th>Title</th><th>Content</th><th>Posted</th><th class="text-end">Actions</th></tr></thead>
                <tbody>
                    <?php foreach ($announcements as $a): $c = $a['content'] ?? ''; ?>
                    <tr>
                        <td><?= $e($a['title']) ?></td>
                        <td title="<?= $e($c) ?>"><?= $e(mb_substr($c, 0, 100)) ?><?= mb_strlen($c) > 100 ? '...' : '' ?></td>
                        <td><?= date('M j, Y', strtotime($a['posted_date'])) ?></td>
                        <td class="text-end">
                            <button type="button" class="btn btn-sm btn-outline-primary btn-edit"
                                    data-id="<?= (int)$a['id'] ?>"
                                    data-title="<?= $e($a['title']) ?>"
                                    data-content="<?= $e($c) ?>"><i class="fas fa-edit"></i></button>
                            <form method="post" class="d-inline" onsubmit="return confirm('Delete this announcement?')">
                                <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                                <input type="hidden" name="delete_announcement" value="<?= (int)$a['id'] ?>">
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

<!-- Add Modal -->
<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post">
                <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                <div class="modal-header">
                    <h5 class="modal-title">Post Announcement</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Title</label>
                        <input type="text" name="title" class="form-control" maxlength="200" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Content</label>
                        <textarea name="content" class="form-control" rows="4" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_announcement" class="btn btn-primary">Post</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post">
                <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                <input type="hidden" name="id" id="edit_id">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Announcement</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Title</label>
                        <input type="text" name="title" id="edit_title" class="form-control" maxlength="200" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Content</label>
                        <textarea name="content" id="edit_content" class="form-control" rows="4" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="edit_announcement" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.querySelectorAll('.btn-edit').forEach(function (btn) {
    btn.addEventListener('click', function () {
        document.getElementById('edit_id').value = btn.dataset.id;
        document.getElementById('edit_title').value = btn.dataset.title;
        document.getElementById('edit_content').value = btn.dataset.content;
        new bootstrap.Modal(document.getElementById('editModal')).show();
    });
});
</script>

<?php include '../includes/footer.php'; ?>