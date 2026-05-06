<?php
require_once '../includes/admin_header.php';

// Get filter parameters
$search = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? '';
$type_filter = $_GET['type'] ?? '';
$category_filter = $_GET['category'] ?? '';

$sql = "SELECT m.*, u.email as created_by_email 
        FROM messages m 
        JOIN users u ON m.created_by = u.id 
        WHERE 1=1";
$params = [];

if ($search) {
    $sql .= " AND (m.subject LIKE ? OR m.content LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($status_filter) {
    $sql .= " AND m.status = ?";
    $params[] = $status_filter;
}
if ($type_filter) {
    $sql .= " AND m.type = ?";
    $params[] = $type_filter;
}
if ($category_filter) {
    $sql .= " AND m.category = ?";
    $params[] = $category_filter;
}

$sql .= " ORDER BY m.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$messages = $stmt->fetchAll();
?>
<div class="page-header">
    <h1>Message History</h1>
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
                <input type="text" name="search" class="form-control" placeholder="Search messages..." value="<?= htmlspecialchars($search) ?>">
            </div>
            <div class="col-md-2">
                <select name="status" class="form-select">
                    <option value="">All Status</option>
                    <option value="pending" <?= $status_filter == 'pending' ? 'selected' : '' ?>>Pending</option>
                    <option value="sent" <?= $status_filter == 'sent' ? 'selected' : '' ?>>Sent</option>
                    <option value="failed" <?= $status_filter == 'failed' ? 'selected' : '' ?>>Failed</option>
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
                    <option value="event" <?= $category_filter == 'event' ? 'selected' : '' ?>>Event</option>
                    <option value="survey" <?= $category_filter == 'survey' ? 'selected' : '' ?>>Survey</option>
                    <option value="employment" <?= $category_filter == 'employment' ? 'selected' : '' ?>>Employment</option>
                    <option value="welcome" <?= $category_filter == 'welcome' ? 'selected' : '' ?>>Welcome</option>
                    <option value="general" <?= $category_filter == 'general' ? 'selected' : '' ?>>General</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">Filter</button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Subject</th>
                        <th>Type</th>
                        <th>Category</th>
                        <th>Recipients</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($messages as $m): ?>
                    <tr>
                        <td><?= date('M j, Y H:i', strtotime($m['created_at'])) ?></td>
                        <td><?= htmlspecialchars(substr($m['subject'] ?? '', 0, 50)) ?><?= strlen($m['subject'] ?? '') > 50 ? '...' : '' ?></td>
                        <td>
                            <?php if ($m['type'] == 'sms'): ?>
                                <span class="badge bg-info"><i class="fas fa-sms"></i> SMS</span>
                            <?php elseif ($m['type'] == 'email'): ?>
                                <span class="badge bg-primary"><i class="fas fa-envelope"></i> Email</span>
                            <?php else: ?>
                                <span class="badge bg-secondary"><i class="fas fa-exchange-alt"></i> Both</span>
                            <?php endif; ?>
                        </td>
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
                                echo $category_labels[$m['category']] ?? $m['category'];
                                ?>
                            </span>
                        </td>
                        <td><?= $m['recipient_count'] ?> alumni</td>
                        <td>
                            <?php if ($m['status'] == 'pending'): ?>
                                <span class="badge bg-warning">Pending</span>
                            <?php elseif ($m['status'] == 'sent'): ?>
                                <span class="badge bg-success">Sent</span>
                            <?php elseif ($m['status'] == 'failed'): ?>
                                <span class="badge bg-danger">Failed</span>
                            <?php else: ?>
                                <span class="badge bg-secondary"><?= $m['status'] ?></span>
                            <?php endif; ?>
                         </td>
                        <td>
                            <button class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#viewModal<?= $m['id'] ?>">
                                <i class="fas fa-eye"></i> View
                            </button>
                            <a href="message_details.php?id=<?= $m['id'] ?>" class="btn btn-sm btn-secondary">
                                <i class="fas fa-chart-bar"></i> Stats
                            </a>
                         </td>
                    </tr>
                    
                    <!-- View Modal -->
                    <div class="modal fade" id="viewModal<?= $m['id'] ?>" tabindex="-1">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Message Details</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <p><strong>Subject:</strong> <?= htmlspecialchars($m['subject'] ?? 'N/A') ?></p>
                                    <p><strong>Content:</strong></p>
                                    <div class="border p-2 bg-light rounded"><?= nl2br(htmlspecialchars($m['content'])) ?></div>
                                    <hr>
                                    <p><strong>Type:</strong> <?= $m['type'] ?></p>
                                    <p><strong>Category:</strong> <?= $m['category'] ?></p>
                                    <p><strong>Recipients:</strong> <?= $m['recipient_count'] ?> alumni</p>
                                    <p><strong>Created By:</strong> <?= htmlspecialchars($m['created_by_email']) ?></p>
                                    <p><strong>Created:</strong> <?= date('M j, Y H:i', strtotime($m['created_at'])) ?></p>
                                    <?php if ($m['scheduled_at']): ?>
                                        <p><strong>Scheduled:</strong> <?= date('M j, Y H:i', strtotime($m['scheduled_at'])) ?></p>
                                    <?php endif; ?>
                                    <?php if ($m['sent_at']): ?>
                                        <p><strong>Sent:</strong> <?= date('M j, Y H:i', strtotime($m['sent_at'])) ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>