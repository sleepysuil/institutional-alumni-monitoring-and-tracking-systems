<?php
require_once '../includes/admin_header.php';
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_announcement'])) {
    $title = cleanInput($_POST['title']);
    $content = cleanInput($_POST['content']);
    $pdo->prepare("INSERT INTO announcements (title, content, posted_by, posted_date) VALUES (?, ?, ?, NOW())")->execute([$title, $content, $_SESSION['user_id']]);
    $_SESSION['message'] = "Announcement posted.";
    redirect('announcements.php');
}
if (isset($_GET['delete'])) {
    $pdo->prepare("DELETE FROM announcements WHERE id = ?")->execute([$_GET['delete']]);
    $_SESSION['message'] = "Announcement deleted.";
    redirect('announcements.php');
}
$announcements = $pdo->query("SELECT * FROM announcements ORDER BY posted_date DESC")->fetchAll();
$message = $_SESSION['message'] ?? '';
unset($_SESSION['message']);
?>
<div class="page-header">
    <h1>Announcements</h1>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal"><i class="fas fa-plus me-2"></i>New Announcement</button>
</div>

<?php if ($message): ?><div class="alert alert-success"><?= $message ?></div><?php endif; ?>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table">
                <thead><tr><th>Title</th><th>Content</th><th>Posted</th><th>Actions</th></tr></thead>
                <tbody>
                    <?php foreach ($announcements as $a): ?>
                    <tr>
                        <td><?= htmlspecialchars($a['title']) ?></td>
                        <td><?= htmlspecialchars(substr($a['content'],0,100)) ?>...</td>
                        <td><?= date('M j, Y', strtotime($a['posted_date'])) ?></td>
                        <td>
                            <a href="?delete=<?= $a['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete?')"><i class="fas fa-trash"></i></a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Modal -->
<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post">
                <div class="modal-header">
                    <h5 class="modal-title">Post Announcement</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Title</label>
                        <input type="text" name="title" class="form-control" required>
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

<?php include '../includes/footer.php'; ?>