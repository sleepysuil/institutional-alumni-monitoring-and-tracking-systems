<?php
require_once '../includes/admin_header.php';

// Handle save template
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_template'])) {
    $name = cleanInput($_POST['name']);
    $category = $_POST['category'];
    $subject = cleanInput($_POST['subject'] ?? '');
    $content = cleanInput($_POST['content']);
    $type = $_POST['message_type'];
    
    $stmt = $pdo->prepare("INSERT INTO message_templates (name, category, subject, content, type, created_by) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$name, $category, $subject, $content, $type, $_SESSION['user_id']]);
    $_SESSION['message'] = "Template saved successfully.";
    redirect('message_templates.php');
}

// Handle delete template
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $pdo->prepare("DELETE FROM message_templates WHERE id = ?")->execute([$id]);
    $_SESSION['message'] = "Template deleted.";
    redirect('message_templates.php');
}

$templates = $pdo->query("SELECT t.*, u.email as created_by_email 
                          FROM message_templates t 
                          JOIN users u ON t.created_by = u.id 
                          ORDER BY t.created_at DESC")->fetchAll();
$message = $_SESSION['message'] ?? '';
unset($_SESSION['message']);
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

<?php if ($message): ?><div class="alert alert-success"><?= $message ?></div><?php endif; ?>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Category</th>
                        <th>Type</th>
                        <th>Subject</th>
                        <th>Created By</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($templates as $t): ?>
                    <tr>
                        <td><?= htmlspecialchars($t['name']) ?></td>
                        <td>
                            <span class="badge bg-secondary">
                                <?php
                                $category_labels = [
                                    'event' => '🎉 Event',
                                    'survey' => '📊 Survey',
                                    'employment' => '💼 Employment',
                                    'welcome' => '👋 Welcome',
                                    'general' => '📢 General'
                                ];
                                echo $category_labels[$t['category']] ?? $t['category'];
                                ?>
                            </span>
                         </td>
                        <td>
                            <?php if ($t['type'] == 'sms'): ?>
                                <span class="badge bg-info">SMS</span>
                            <?php elseif ($t['type'] == 'email'): ?>
                                <span class="badge bg-primary">Email</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">Both</span>
                            <?php endif; ?>
                         </td>
                        <td><?= htmlspecialchars(substr($t['subject'] ?? '', 0, 40)) ?><?= strlen($t['subject'] ?? '') > 40 ? '...' : '' ?></td>
                        <td><?= htmlspecialchars($t['created_by_email']) ?></td>
                        <td>
                            <button class="btn btn-sm btn-info load-template" 
                                    data-subject="<?= htmlspecialchars($t['subject']) ?>"
                                    data-content="<?= htmlspecialchars($t['content']) ?>"
                                    data-type="<?= $t['type'] ?>">
                                <i class="fas fa-download"></i> Load
                            </button>
                            <a href="?delete=<?= $t['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this template?')">
                                <i class="fas fa-trash"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Template Modal -->
<div class="modal fade" id="addTemplateModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="post">
                <div class="modal-header">
                    <h5 class="modal-title">Save as Template</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Template Name</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Category</label>
                        <select name="category" class="form-select" required>
                            <option value="event">Event Announcement</option>
                            <option value="survey">Survey Reminder</option>
                            <option value="employment">Employment Follow-Up</option>
                            <option value="welcome">Welcome Message</option>
                            <option value="general">General Notification</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Message Type</label>
                        <select name="message_type" class="form-select" required>
                            <option value="sms">SMS Only</option>
                            <option value="email">Email Only</option>
                            <option value="both">Both</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Subject (for email)</label>
                        <input type="text" name="subject" class="form-control" placeholder="Email subject">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Content</label>
                        <textarea name="content" class="form-control" rows="5" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="save_template" class="btn btn-primary">Save Template</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.querySelectorAll('.load-template').forEach(btn => {
    btn.addEventListener('click', function() {
        window.location.href = 'send_messages.php?subject=' + encodeURIComponent(this.dataset.subject) + 
                              '&content=' + encodeURIComponent(this.dataset.content) + 
                              '&type=' + this.dataset.type;
    });
});
</script>

<?php include '../includes/footer.php'; ?>