<?php
require_once '../includes/admin_header.php';

$id = $_GET['id'] ?? 0;

$message = $pdo->prepare("SELECT * FROM messages WHERE id = ?");
$message->execute([$id]);
$message = $message->fetch();
if (!$message) redirect('message_history.php');

// Get recipient details with delivery status
$recipients = $pdo->prepare("
    SELECT mr.*, a.first_name, a.last_name, a.email, a.phone
    FROM message_recipients mr
    JOIN alumni a ON mr.alumni_id = a.id
    WHERE mr.message_id = ?
");
$recipients->execute([$id]);
$recipients = $recipients->fetchAll();

$sent_count = count(array_filter($recipients, function($r) use ($message) {
    if ($message['type'] == 'email') return $r['email_status'] == 'sent';
    if ($message['type'] == 'sms') return $r['sms_status'] == 'sent';
    return $r['email_status'] == 'sent' || $r['sms_status'] == 'sent';
}));

$failed_count = count(array_filter($recipients, function($r) use ($message) {
    if ($message['type'] == 'email') return $r['email_status'] == 'failed';
    if ($message['type'] == 'sms') return $r['sms_status'] == 'failed';
    return $r['email_status'] == 'failed' || $r['sms_status'] == 'failed';
}));
?>
<div class="page-header">
    <h1>Message Delivery Report</h1>
    <a href="message_history.php" class="btn btn-outline"><i class="fas fa-arrow-left me-2"></i>Back</a>
</div>

<!-- Summary Cards -->
<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="stat-card primary">
            <div class="stat-title">Total Recipients</div>
            <div class="stat-value"><?= count($recipients) ?></div>
            <div class="stat-label">Alumni targeted</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card teal">
            <div class="stat-title">Sent Successfully</div>
            <div class="stat-value"><?= $sent_count ?></div>
            <div class="stat-label">Messages delivered</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card purple">
            <div class="stat-title">Failed</div>
            <div class="stat-value"><?= $failed_count ?></div>
            <div class="stat-label">Delivery failed</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card emerald">
            <div class="stat-title">Success Rate</div>
            <div class="stat-value"><?= count($recipients) > 0 ? round(($sent_count / count($recipients)) * 100, 1) : 0 ?>%</div>
            <div class="stat-label">Delivery rate</div>
        </div>
    </div>
</div>

<!-- Message Details -->
<div class="card mb-4">
    <div class="card-header">Message Information</div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <p><strong>Subject:</strong> <?= htmlspecialchars($message['subject'] ?? 'N/A') ?></p>
                <p><strong>Type:</strong> <?= $message['type'] ?></p>
                <p><strong>Category:</strong> <?= $message['category'] ?></p>
            </div>
            <div class="col-md-6">
                <p><strong>Created:</strong> <?= date('M j, Y H:i', strtotime($message['created_at'])) ?></p>
                <?php if ($message['scheduled_at']): ?>
                    <p><strong>Scheduled:</strong> <?= date('M j, Y H:i', strtotime($message['scheduled_at'])) ?></p>
                <?php endif; ?>
                <?php if ($message['sent_at']): ?>
                    <p><strong>Sent:</strong> <?= date('M j, Y H:i', strtotime($message['sent_at'])) ?></p>
                <?php endif; ?>
            </div>
        </div>
        <div class="mt-3">
            <strong>Content:</strong>
            <div class="border p-2 bg-light rounded mt-1"><?= nl2br(htmlspecialchars($message['content'])) ?></div>
        </div>
    </div>
</div>

<!-- Recipient List -->
<div class="card">
    <div class="card-header">Recipient Delivery Status</div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Email Status</th>
                        <th>SMS Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recipients as $r): ?>
                    <tr>
                        <td><?= htmlspecialchars($r['first_name'] . ' ' . $r['last_name']) ?></td>
                        <td><?= htmlspecialchars($r['email']) ?></td>
                        <td><?= htmlspecialchars($r['phone']) ?></td>
                        <td>
                            <?php if ($message['type'] == 'email' || $message['type'] == 'both'): ?>
                                <span class="badge bg-<?= $r['email_status'] == 'sent' ? 'success' : ($r['email_status'] == 'failed' ? 'danger' : 'secondary') ?>">
                                    <?= $r['email_status'] ?>
                                </span>
                            <?php else: ?>
                                <span class="badge bg-secondary">N/A</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($message['type'] == 'sms' || $message['type'] == 'both'): ?>
                                <span class="badge bg-<?= $r['sms_status'] == 'sent' ? 'success' : ($r['sms_status'] == 'failed' ? 'danger' : 'secondary') ?>">
                                    <?= $r['sms_status'] ?>
                                </span>
                            <?php else: ?>
                                <span class="badge bg-secondary">N/A</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>