<?php
require_once '../includes/admin_header.php';
require_once '../includes/notifications.php';

// Handle sending message
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['send_message'])) {
    $subject = cleanInput($_POST['subject'] ?? '');
    $content = cleanInput($_POST['content']);
    $type = $_POST['message_type'];
    $category = $_POST['category'];
    $schedule_date = $_POST['schedule_date'] ?? '';
    $schedule_time = $_POST['schedule_time'] ?? '';
    
    // Get selected recipients from checklist
    $selected_alumni = $_POST['selected_alumni'] ?? [];
    
    if (empty($selected_alumni)) {
        $error = "Please select at least one recipient.";
    } else {
        // Get alumni details for selected IDs
        $placeholders = implode(',', array_fill(0, count($selected_alumni), '?'));
        $stmt = $pdo->prepare("SELECT id, email, phone FROM alumni WHERE id IN ($placeholders)");
        $stmt->execute($selected_alumni);
        $recipients = $stmt->fetchAll();
        
        // Save message to database
        $recipient_ids = array_column($recipients, 'id');
        $recipient_list = implode(',', $recipient_ids);
        
        $scheduled_at = null;
        if ($schedule_date && $schedule_time) {
            $scheduled_at = $schedule_date . ' ' . $schedule_time . ':00';
        }
        
        $stmt = $pdo->prepare("INSERT INTO messages (subject, content, type, category, recipients, recipient_count, scheduled_at, status, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', ?)");
        $stmt->execute([$subject, $content, $type, $category, $recipient_list, count($recipients), $scheduled_at, $_SESSION['user_id']]);
        $message_id = $pdo->lastInsertId();
        
        // Add recipients to message_recipients table
        foreach ($recipients as $recipient) {
            $stmt = $pdo->prepare("INSERT INTO message_recipients (message_id, alumni_id, recipient_type) VALUES (?, ?, ?)");
            $stmt->execute([$message_id, $recipient['id'], $type]);
        }
        
        // If not scheduled, send immediately
        if (!$scheduled_at) {
            sendMessagesNow($message_id, $recipients, $type, $subject, $content);
        }
        
        $_SESSION['message'] = "Message " . ($scheduled_at ? "scheduled" : "sent") . " successfully to " . count($recipients) . " recipients.";
        redirect('send_messages.php');
    }
}

// Function to send messages immediately
function sendMessagesNow($message_id, $recipients, $type, $subject, $content) {
    global $pdo;
    
    foreach ($recipients as $recipient) {
        $sms_success = true;
        $sms_error = null;
        
        if ($type == 'sms' || $type == 'both') {
            if (!empty($recipient['phone'])) {
                $sms_success = sendSMSMessage($recipient['phone'], $content);
                if (!$sms_success) $sms_error = "SMS sending failed";
            } else {
                $sms_success = false;
                $sms_error = "No phone number";
            }
        }
        
        // Update recipient status
        $stmt = $pdo->prepare("UPDATE message_recipients SET 
            sms_status = ?, 
            sms_sent_at = CASE WHEN ? = 'sent' THEN NOW() ELSE NULL END,
            error_message = ?
            WHERE message_id = ? AND alumni_id = ?");
        $stmt->execute([
            $sms_success ? 'sent' : 'failed',
            $sms_success ? 'sent' : 'failed',
            $sms_error,
            $message_id,
            $recipient['id']
        ]);
    }
    
    // Update main message status
    $pdo->prepare("UPDATE messages SET status = 'sent', sent_at = NOW() WHERE id = ?")->execute([$message_id]);
}

// Get all alumni with employment status for checklist
$alumni_list = $pdo->query("
    SELECT a.*, 
           COALESCE(e.status, 'No Data') as employment_status
    FROM alumni a
    LEFT JOIN employment e ON a.id = e.alumni_id
    ORDER BY a.last_name
")->fetchAll();

// Get filters for quick selection
$courses = $pdo->query("SELECT DISTINCT program FROM alumni ORDER BY program")->fetchAll(PDO::FETCH_COLUMN);
$years = $pdo->query("SELECT DISTINCT graduation_year FROM alumni ORDER BY graduation_year DESC")->fetchAll(PDO::FETCH_COLUMN);
$employment_statuses = ['Employed', 'Self-Employed', 'Unemployed', 'Pursuing Higher Education', 'No Data'];

$error = $error ?? '';
$message = $_SESSION['message'] ?? '';
unset($_SESSION['message']);
?>
<div class="page-header">
    <h1>Send Messages</h1>
</div>

<ul class="nav-tabs mb-4">
    <li><a class="nav-link active" href="send_messages.php">Compose Message</a></li>
    <li><a class="nav-link" href="message_history.php">Message History</a></li>
    <li><a class="nav-link" href="message_templates.php">Templates</a></li>
</ul>

<?php if ($message): ?>
    <div class="alert alert-success"><?= $message ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-danger"><?= $error ?></div>
<?php endif; ?>

<div class="row">
    <!-- Compose Message Form -->
    <div class="col-md-5">
        <div class="card">
            <div class="card-header">Compose Message</div>
            <div class="card-body">
                <form method="post" id="messageForm">
                    <!-- Message Type -->
                    <div class="mb-3">
                        <label class="form-label">Message Type</label>
                        <select name="message_type" class="form-select" required>
                            <option value="sms">📱 SMS Only</option>
                            <option value="email">📧 Email Only</option>
                            <option value="both">📱📧 Both</option>
                        </select>
                    </div>
                    
                    <!-- Category -->
                    <div class="mb-3">
                        <label class="form-label">Category</label>
                        <select name="category" class="form-select" required>
                            <option value="event">🎉 Event Announcement</option>
                            <option value="survey">📊 Survey Reminder</option>
                            <option value="employment">💼 Employment Follow-Up</option>
                            <option value="welcome">👋 Welcome Message</option>
                            <option value="general">📢 General Notification</option>
                        </select>
                    </div>
                    
                    <!-- Subject -->
                    <div class="mb-3">
                        <label class="form-label">Subject</label>
                        <input type="text" name="subject" class="form-control" placeholder="Message subject (for email)">
                    </div>
                    
                    <!-- Content -->
                    <div class="mb-3">
                        <label class="form-label">Message Content</label>
                        <textarea name="content" class="form-control" rows="5" required placeholder="Write your message here..."></textarea>
                        <small class="text-muted">For SMS, messages are limited to 160 characters per segment.</small>
                    </div>
                    
                    <!-- Load Template Button -->
                    <div class="mb-3">
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#templateModal">
                            <i class="fas fa-file-alt"></i> Load Template
                        </button>
                    </div>
                    
                    <!-- Scheduling -->
                    <div class="mb-3">
                        <label class="form-label">Schedule (Optional)</label>
                        <div class="row">
                            <div class="col-md-6">
                                <input type="date" name="schedule_date" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <input type="time" name="schedule_time" class="form-control">
                            </div>
                        </div>
                        <small class="text-muted">Leave empty to send immediately.</small>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Recipient Selection Checklist -->
    <div class="col-md-7">
        <div class="card">
            <div class="card-header">
                Select Recipients
                <div class="float-end">
                    <button type="button" class="btn btn-sm btn-outline-primary" id="selectAllBtn">Select All</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="deselectAllBtn">Deselect All</button>
                </div>
            </div>
            <div class="card-body">
                <!-- Quick Filters -->
                <div class="row mb-3">
                    <div class="col-md-4">
                        <select id="filterCourse" class="form-select form-select-sm">
                            <option value="">All Courses</option>
                            <?php foreach ($courses as $c): ?>
                                <option value="<?= htmlspecialchars($c) ?>"><?= htmlspecialchars($c) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select id="filterYear" class="form-select form-select-sm">
                            <option value="">All Years</option>
                            <?php foreach ($years as $y): ?>
                                <option value="<?= $y ?>"><?= $y ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select id="filterStatus" class="form-select form-select-sm">
                            <option value="">All Status</option>
                            <?php foreach ($employment_statuses as $s): ?>
                                <option value="<?= $s ?>"><?= $s ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="button" id="applyFilters" class="btn btn-primary btn-sm w-100">Filter</button>
                        <button type="button" id="resetFilters" class="btn btn-secondary btn-sm w-100 mt-1">Reset</button>
                    </div>
                </div>
                
                <!-- Recipient Count -->
                <div class="mb-2">
                    <span id="selectedCount" class="badge bg-primary">0 selected</span>
                    <span id="totalCount" class="badge bg-secondary"><?= count($alumni_list) ?> total</span>
                </div>
                
                <!-- Checklist Table -->
                <div class="table-responsive" style="max-height: 450px; overflow-y: auto;">
                    <table class="table table-sm table-hover" id="recipientTable">
                        <thead>
                            <tr>
                                <th style="width: 30px;"><input type="checkbox" id="headerCheckbox"></th>
                                <th>Name</th>
                                <th>Course</th>
                                <th>Year</th>
                                <th>Status</th>
                                <th>Phone</th>
                                <th>Email</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($alumni_list as $al): ?>
                            <tr class="recipient-row" 
                                data-name="<?= htmlspecialchars($al['first_name'] . ' ' . $al['last_name']) ?>"
                                data-course="<?= htmlspecialchars($al['program']) ?>"
                                data-year="<?= $al['graduation_year'] ?>"
                                data-status="<?= htmlspecialchars($al['employment_status']) ?>">
                                <td><input type="checkbox" class="recipient-checkbox" value="<?= $al['id'] ?>"></td>
                                <td><?= htmlspecialchars($al['first_name'] . ' ' . $al['last_name']) ?></td>
                                <td><?= htmlspecialchars($al['program']) ?></td>
                                <td><?= $al['graduation_year'] ?></td>
                                <td>
                                    <?php
                                    if ($al['employment_status'] == 'Employed') {
                                        $badge_class = 'success';
                                    } elseif ($al['employment_status'] == 'Self-Employed') {
                                        $badge_class = 'info';
                                    } elseif ($al['employment_status'] == 'Unemployed') {
                                        $badge_class = 'danger';
                                    } elseif ($al['employment_status'] == 'Pursuing Higher Education') {
                                        $badge_class = 'warning';
                                    } else {
                                        $badge_class = 'secondary';
                                    }
                                    ?>
                                    <span class="badge bg-<?= $badge_class ?>"><?= htmlspecialchars($al['employment_status']) ?></span>
                                 </td>
                                <td><?= htmlspecialchars($al['phone'] ?: 'N/A') ?></td>
                                <td><?= htmlspecialchars($al['email'] ?: 'N/A') ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="mt-3">
                    <button type="submit" form="messageForm" name="send_message" class="btn btn-primary btn-lg w-100" onclick="return syncSelectedRecipients()">
                        <i class="fas fa-paper-plane"></i> Send to Selected Recipients
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Template Modal -->
<div class="modal fade" id="templateModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Load Template</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr><th>Name</th><th>Category</th><th>Type</th><th>Action</th></tr>
                        </thead>
                        <tbody>
                            <?php
                            $templates = $pdo->query("SELECT * FROM message_templates ORDER BY created_at DESC")->fetchAll();
                            foreach ($templates as $t): ?>
                            <tr>
                                <td><?= htmlspecialchars($t['name']) ?></td>
                                <td><?= $t['category'] ?></td>
                                <td><?= $t['type'] ?></td>
                                <td>
                                    <button class="btn btn-sm btn-primary load-template" 
                                            data-subject="<?= htmlspecialchars($t['subject']) ?>"
                                            data-content="<?= htmlspecialchars($t['content']) ?>"
                                            data-type="<?= $t['type'] ?>">
                                        <i class="fas fa-download"></i> Load
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <a href="message_templates.php" class="btn btn-secondary mt-2">Manage Templates</a>
            </div>
        </div>
    </div>
</div>

<script>
// Sync selected recipients to form
function syncSelectedRecipients() {
    const selected = [];
    document.querySelectorAll('.recipient-checkbox:checked').forEach(function(cb) {
        selected.push(cb.value);
    });
    
    // Create hidden inputs for selected recipients
    const form = document.getElementById('messageForm');
    // Remove existing hidden inputs
    const existingInputs = form.querySelectorAll('input[name="selected_alumni[]"]');
    existingInputs.forEach(function(el) {
        el.remove();
    });
    
    selected.forEach(function(id) {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'selected_alumni[]';
        input.value = id;
        form.appendChild(input);
    });
    
    if (selected.length === 0) {
        alert('Please select at least one recipient.');
        return false;
    }
    
    return true;
}

// Update selected count
function updateSelectedCount() {
    const checkboxes = document.querySelectorAll('.recipient-checkbox');
    let count = 0;
    checkboxes.forEach(function(cb) {
        if (cb.checked) count++;
    });
    document.getElementById('selectedCount').innerHTML = count + ' selected';
}

// Update visible count
function updateVisibleCount() {
    const rows = document.querySelectorAll('.recipient-row');
    let visible = 0;
    rows.forEach(function(row) {
        if (row.style.display !== 'none') visible++;
    });
    document.getElementById('totalCount').innerHTML = visible + ' visible';
}

// Select All (only visible rows)
document.getElementById('selectAllBtn').addEventListener('click', function() {
    const rows = document.querySelectorAll('.recipient-row');
    rows.forEach(function(row) {
        if (row.style.display !== 'none') {
            const cb = row.querySelector('.recipient-checkbox');
            if (cb) cb.checked = true;
        }
    });
    updateSelectedCount();
});

// Deselect All
document.getElementById('deselectAllBtn').addEventListener('click', function() {
    const checkboxes = document.querySelectorAll('.recipient-checkbox');
    checkboxes.forEach(function(cb) {
        cb.checked = false;
    });
    updateSelectedCount();
});

// Header checkbox (select/deselect all visible)
document.getElementById('headerCheckbox').addEventListener('change', function(e) {
    const rows = document.querySelectorAll('.recipient-row');
    rows.forEach(function(row) {
        if (row.style.display !== 'none') {
            const cb = row.querySelector('.recipient-checkbox');
            if (cb) cb.checked = e.target.checked;
        }
    });
    updateSelectedCount();
});

// Individual checkbox change
const checkboxes = document.querySelectorAll('.recipient-checkbox');
checkboxes.forEach(function(cb) {
    cb.addEventListener('change', updateSelectedCount);
});

// Filter functionality
function applyFilters() {
    const course = document.getElementById('filterCourse').value;
    const year = document.getElementById('filterYear').value;
    const status = document.getElementById('filterStatus').value;
    
    const rows = document.querySelectorAll('.recipient-row');
    let visibleCount = 0;
    
    rows.forEach(function(row) {
        let show = true;
        if (course && row.dataset.course !== course) show = false;
        if (year && row.dataset.year != year) show = false;
        if (status && row.dataset.status !== status) show = false;
        
        row.style.display = show ? '' : 'none';
        if (show) visibleCount++;
    });
    
    document.getElementById('totalCount').innerHTML = visibleCount + ' visible';
    
    // Uncheck header checkbox
    document.getElementById('headerCheckbox').checked = false;
    updateSelectedCount();
}

function resetFilters() {
    document.getElementById('filterCourse').value = '';
    document.getElementById('filterYear').value = '';
    document.getElementById('filterStatus').value = '';
    
    const rows = document.querySelectorAll('.recipient-row');
    rows.forEach(function(row) {
        row.style.display = '';
    });
    
    document.getElementById('totalCount').innerHTML = document.querySelectorAll('.recipient-row').length + ' visible';
    updateSelectedCount();
}

document.getElementById('applyFilters').addEventListener('click', applyFilters);
document.getElementById('resetFilters').addEventListener('click', resetFilters);

// Load template
const loadButtons = document.querySelectorAll('.load-template');
loadButtons.forEach(function(btn) {
    btn.addEventListener('click', function() {
        document.querySelector('input[name="subject"]').value = this.dataset.subject;
        document.querySelector('textarea[name="content"]').value = this.dataset.content;
        
        const type = this.dataset.type;
        const typeSelect = document.querySelector('select[name="message_type"]');
        if (type === 'sms') typeSelect.value = 'sms';
        else if (type === 'email') typeSelect.value = 'email';
        else if (type === 'both') typeSelect.value = 'both';
        
        const modal = bootstrap.Modal.getInstance(document.getElementById('templateModal'));
        modal.hide();
    });
});

// Initial counts
updateSelectedCount();
updateVisibleCount();
</script>

<?php include '../includes/footer.php'; ?>