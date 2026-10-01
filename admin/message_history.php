<?php
require_once '../includes/admin_header.php';

$search = trim($_GET['search'] ?? '');
$status_filter = in_array($_GET['status'] ?? '', ['pending', 'sent', 'partial', 'failed'], true) ? $_GET['status'] : '';
$type_filter = in_array($_GET['type'] ?? '', ['sms', 'email', 'both'], true) ? $_GET['type'] : '';
$category_filter = in_array($_GET['category'] ?? '', ['event', 'survey', 'employment', 'welcome', 'general'], true) ? $_GET['category'] : '';
$limit = 15;
$page = max(1, (int)($_GET['page'] ?? 1));

// LEFT JOIN so messages stay visible even if the sender's account was removed
$from = "FROM messages m LEFT JOIN users u ON m.created_by = u.id WHERE 1=1";
$params = [];
if ($search !== '') {
    $from .= " AND (m.subject LIKE ? OR m.content LIKE ?)";
    $params[] = "%$search%"; $params[] = "%$search%";
}
if ($status_filter)   { $from .= " AND m.status = ?";   $params[] = $status_filter; }
if ($type_filter)     { $from .= " AND m.type = ?";     $params[] = $type_filter; }
if ($category_filter) { $from .= " AND m.category = ?"; $params[] = $category_filter; }

$c = $pdo->prepare("SELECT COUNT(*) $from");
$c->execute($params);
$total = (int)$c->fetchColumn();
$pages = max(1, (int)ceil($total / $limit));
$page = min($page, $pages);

$stmt = $pdo->prepare("SELECT m.*, u.email AS created_by_email $from ORDER BY m.created_at DESC LIMIT " . (int)$limit . " OFFSET " . (int)(($page - 1) * $limit));
$stmt->execute($params);
$messages = $stmt->fetchAll();

$category_labels = ['event' => '🎉 Event', 'survey' => '📊 Survey', 'employment' => '💼 Employment', 'welcome' => '👋 Welcome', 'general' => '📢 General'];
$status_badges = ['pending' => 'warning', 'sent' => 'success', 'partial' => 'info', 'failed' => 'danger'];
$e = fn($v) => htmlspecialchars((string)($v ?? ''));
$has_filters = ($search !== '' || $status_filter || $type_filter || $category_filter);
?>
<div class="page-header">
    <h1>Message History</h1>
    <span class="text-muted"><?= $total ?> message<?= $total == 1 ? '' : 's' ?></span>
</div>

<ul class="nav-tabs mb-4">
    <li><a class="nav-link" href="send_messages.php">Compose Message</a></li>
    <li><a class="nav-link active" href="message_history.php">Message History</a></li>
    <li><a class="nav-link" href="message_templates.php">Templates</a></li>
</ul>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body">
        <form method="get" class="row g-3">
            <div class="col-md-3">
                <input type="text" name="search" class="form-control" placeholder="Search messages..." value="<?= $e($search) ?>">
            </div>
            <div class="col-md-2">
                <select name="status" class="form-select">
                    <option value="">All Status</option>
                    <?php foreach (['pending' => 'Pending', 'sent' => 'Sent', 'partial' => 'Partial', 'failed' => 'Failed'] as $v => $l): ?>
                        <option value="<?= $v ?>" <?= $status_filter == $v ? 'selected' : '' ?>><?= $l ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <select name="type" class="form-select">
                    <option value="">All Types</option>
                    <option value="sms" <?= $type_filter == 'sms' ? 'selected' : '' ?>>SMS</option>
                    <option value="email" <?= $type_filter == 'email' ? 'selected' : '' ?>>Email</option>
                    <option value="both" <?= $type_filter == 'both' ? 'selected' : '' ?>>Both</option>
                </select>
            </div>
            <div class="col-md-3">
                <select name="category" class="form-select">
                    <option value="">All Categories</option>
                    <?php foreach ($category_labels as $v => $l): ?>
                        <option value="<?= $v ?>" <?= $category_filter == $v ? 'selected' : '' ?>><?= $l ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-primary flex-grow-1">Filter</button>
                <?php if ($has_filters): ?><a href="message_history.php" class="btn btn-outline-secondary" title="Clear"><i class="fas fa-times"></i></a><?php endif; ?>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr><th>Date</th><th>Subject</th><th>Type</th><th>Category</th><th>Recipients</th><th>Status</th><th>Actions</th></tr>
                </thead>
                <tbody>
                    <?php if (!$messages): ?><tr><td colspan="7" class="text-center text-muted py-4">No messages found.</td></tr><?php endif; ?>
                    <?php foreach ($messages as $m): $subj = $m['subject'] ?? ''; ?>
                    <tr>
                        <td><?= date('M j, Y H:i', strtotime($m['created_at'])) ?></td>
                        <td><?= $e(mb_substr($subj, 0, 50)) ?><?= mb_strlen($subj) > 50 ? '...' : '' ?><?= $subj === '' ? '<span class="text-muted">(no subject)</span>' : '' ?></td>
                        <td>
                            <?php if ($m['type'] == 'sms'): ?>
                                <span class="badge bg-info"><i class="fas fa-sms"></i> SMS</span>
                            <?php elseif ($m['type'] == 'email'): ?>
                                <span class="badge bg-primary"><i class="fas fa-envelope"></i> Email</span>
                            <?php else: ?>
                                <span class="badge bg-secondary"><i class="fas fa-exchange-alt"></i> Both</span>
                            <?php endif; ?>
                        </td>
                        <td><span class="badge bg-secondary"><?= $e($category_labels[$m['category']] ?? $m['category']) ?></span></td>
                        <td><?= (int)$m['recipient_count'] ?> alumni</td>
                        <td>
                            <span class="badge bg-<?= $status_badges[$m['status']] ?? 'secondary' ?>"><?= $e(ucfirst($m['status'])) ?></span>
                            <?php if ($m['status'] == 'pending' && $m['scheduled_at']): ?>
                                <br><small class="text-muted"><i class="far fa-clock"></i> <?= date('M j, H:i', strtotime($m['scheduled_at'])) ?></small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <button class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#viewModal<?= (int)$m['id'] ?>"><i class="fas fa-eye"></i> View</button>
                            <a href="message_details.php?id=<?= (int)$m['id'] ?>" class="btn btn-sm btn-secondary"><i class="fas fa-chart-bar"></i> Stats</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if ($pages > 1):
            $link = fn($p) => '?' . http_build_query(array_merge($_GET, ['page' => $p])); ?>
        <nav class="mt-3">
            <ul class="pagination justify-content-center mb-0">
                <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>"><a class="page-link" href="<?= $link($page - 1) ?>">&laquo;</a></li>
                <?php for ($i = max(1, $page - 2); $i <= min($pages, $page + 2); $i++): ?>
                    <li class="page-item <?= $i == $page ? 'active' : '' ?>"><a class="page-link" href="<?= $link($i) ?>"><?= $i ?></a></li>
                <?php endfor; ?>
                <li class="page-item <?= $page >= $pages ? 'disabled' : '' ?>"><a class="page-link" href="<?= $link($page + 1) ?>">&raquo;</a></li>
            </ul>
        </nav>
        <?php endif; ?>
    </div>
</div>

<!-- View modals live outside the table (a <div> inside <tbody> is invalid HTML) -->
<?php foreach ($messages as $m): ?>
<div class="modal fade" id="viewModal<?= (int)$m['id'] ?>" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Message Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p><strong>Subject:</strong> <?= $e($m['subject'] ?: 'N/A') ?></p>
                <p><strong>Content:</strong></p>
                <div class="border p-2 bg-light rounded"><?= nl2br($e($m['content'])) ?></div>
                <hr>
                <p><strong>Type:</strong> <?= $e($m['type']) ?></p>
                <p><strong>Category:</strong> <?= $e($m['category']) ?></p>
                <p><strong>Recipients:</strong> <?= (int)$m['recipient_count'] ?> alumni</p>
                <p><strong>Created By:</strong> <?= $e($m['created_by_email'] ?? 'Unknown') ?></p>
                <p><strong>Created:</strong> <?= date('M j, Y H:i', strtotime($m['created_at'])) ?></p>
                <?php if ($m['scheduled_at']): ?><p><strong>Scheduled:</strong> <?= date('M j, Y H:i', strtotime($m['scheduled_at'])) ?></p><?php endif; ?>
                <?php if ($m['sent_at']): ?><p><strong>Sent:</strong> <?= date('M j, Y H:i', strtotime($m['sent_at'])) ?></p><?php endif; ?>
            </div>
            <div class="modal-footer">
                <a class="btn btn-outline-primary btn-sm" href="send_messages.php?<?= http_build_query(['subject' => $m['subject'] ?? '', 'content' => $m['content'], 'type' => $m['type'], 'category' => $m['category']]) ?>"><i class="fas fa-redo me-1"></i>Reuse this message</a>
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
<?php endforeach; ?>

<?php include '../includes/footer.php'; ?>