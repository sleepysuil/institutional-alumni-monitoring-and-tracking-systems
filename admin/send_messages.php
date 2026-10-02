<?php
require_once '../includes/admin_header.php';
require_once '../includes/notifications.php';
if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

$categories = ['event' => '🎉 Event Announcement', 'survey' => '📊 Survey Reminder', 'employment' => '💼 Employment Follow-Up', 'welcome' => '👋 Welcome Message', 'general' => '📢 General Notification'];
$type_options = ['sms' => '📱 SMS Only', 'email' => '📧 Email Only', 'both' => '📱📧 Both'];

/** Replace {first_name}, {last_name}, {program}, {year} with the recipient's details. */
function personalize($text, $r) {
    return strtr($text, [
        '{first_name}' => $r['first_name'] ?? '',
        '{last_name}'  => $r['last_name'] ?? '',
        '{program}'    => $r['program'] ?? '',
        '{year}'       => $r['graduation_year'] ?? '',
    ]);
}

/** Uses your email sender from includes/notifications.php if one exists. Returns null when none is configured. */
function sendEmailToRecipient($to, $subject, $body) {
    foreach (['sendEmailMessage', 'sendEmail', 'sendEmailNotification'] as $fn) {
        if (function_exists($fn)) return (bool)$fn($to, $subject, $body);
    }
    return null;
}

// Send immediately; records the real result per channel (the old version marked email-only
// messages as "SMS sent" and never sent email at all)
function sendMessagesNow($message_id, $recipients, $type, $subject, $content) {
    global $pdo;
    $uses_sms = in_array($type, ['sms', 'both'], true);
    $uses_email = in_array($type, ['email', 'both'], true);
    $ok = $fail = 0;
    $now = date('Y-m-d H:i:s');

    foreach ($recipients as $r) {
        $body = personalize($content, $r);
        $subj = personalize($subject, $r);
        $errors = [];
        $sets = []; $vals = [];
        $all_ok = true;

        if ($uses_sms) {
            if (empty($r['phone'])) { $s = false; $errors[] = 'No phone number'; }
            elseif (function_exists('sendSMSViaSemaphore')) {
                // call the sender directly so the real failure reason is recorded
                $res = sendSMSViaSemaphore($r['phone'], plainMessageText($body));
                $s = !empty($res['success']);
                if (!$s) $errors[] = 'SMS failed: ' . ($res['error'] ?? 'unknown error');
            }
            else { $s = (bool)sendSMSMessage($r['phone'], $body); if (!$s) $errors[] = 'SMS sending failed'; }
            $sets[] = "sms_status = ?, sms_sent_at = ?";
            array_push($vals, $s ? 'sent' : 'failed', $s ? $now : null);
            $all_ok = $all_ok && $s;
        }
        if ($uses_email) {
            if (empty($r['email'])) { $m = false; $errors[] = 'No email address'; }
            else {
                $res = sendEmailToRecipient($r['email'], $subj, $body);
                if ($res === null) { $m = false; $errors[] = 'Email sender not configured'; }
                else {
                    $m = $res;
                    if (!$m) $errors[] = (function_exists('getLastEmailError') && getLastEmailError()) ? 'Email failed: ' . getLastEmailError() : 'Email sending failed';
                }
            }
            $sets[] = "email_status = ?, email_sent_at = ?";
            array_push($vals, $m ? 'sent' : 'failed', $m ? $now : null);
            $all_ok = $all_ok && $m;
        }
        $sets[] = "error_message = ?";
        $vals[] = $errors ? implode('; ', $errors) : null;
        array_push($vals, $message_id, $r['id']);
        $pdo->prepare("UPDATE message_recipients SET " . implode(', ', $sets) . " WHERE message_id = ? AND alumni_id = ?")->execute($vals);
        $all_ok ? $ok++ : $fail++;
    }

    // sent = everyone succeeded, failed = nobody did, partial = mixed
    $status = $fail === 0 ? 'sent' : ($ok === 0 ? 'failed' : 'partial');
    $pdo->prepare("UPDATE messages SET status = ?, sent_at = NOW() WHERE id = ?")->execute([$status, $message_id]);
    return [$ok, $fail];
}

// Form values: from a template / "Reuse" link, or what was typed if there was an error
$form = [
    'subject'  => $_GET['subject'] ?? '',
    'content'  => $_GET['content'] ?? '',
    'type'     => array_key_exists($_GET['type'] ?? '', $type_options) ? $_GET['type'] : 'sms',
    'category' => array_key_exists($_GET['category'] ?? '', $categories) ? $_GET['category'] : 'event',
    'schedule_date' => '', 'schedule_time' => '',
];
$preselected = [];
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['send_message'])) {
    $form = [
        'subject'  => trim($_POST['subject'] ?? ''),
        'content'  => trim($_POST['content'] ?? ''),
        'type'     => $_POST['message_type'] ?? '',
        'category' => $_POST['category'] ?? '',
        'schedule_date' => trim($_POST['schedule_date'] ?? ''),
        'schedule_time' => trim($_POST['schedule_time'] ?? ''),
    ];
    $preselected = array_values(array_unique(array_filter(array_map('intval', $_POST['selected_alumni'] ?? []))));

    $scheduled_at = null;
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        $error = "Invalid request. Please try again.";
    } elseif (!array_key_exists($form['type'], $type_options) || !array_key_exists($form['category'], $categories)) {
        $error = "Please choose a valid message type and category.";
    } elseif ($form['content'] === '') {
        $error = "Message content is required.";
    } elseif (empty($preselected)) {
        $error = "Please select at least one recipient.";
    } elseif (in_array($form['type'], ['email', 'both'], true) && $form['subject'] === '') {
        $error = "A subject is required for email messages.";
    } elseif ($form['schedule_date'] !== '' || $form['schedule_time'] !== '') {
        $dt = DateTime::createFromFormat('Y-m-d H:i', $form['schedule_date'] . ' ' . $form['schedule_time']);
        if (!$dt) $error = "Please set both a valid schedule date and time, or leave both empty.";
        elseif ($dt <= new DateTime()) $error = "The scheduled time must be in the future.";
        else $scheduled_at = $dt->format('Y-m-d H:i:s');
    }

    if (!$error) {
        $subject = cleanInput($form['subject']);
        $content = cleanInput($form['content']);

        $placeholders = implode(',', array_fill(0, count($preselected), '?'));
        $stmt = $pdo->prepare("SELECT id, first_name, last_name, program, graduation_year, email, phone FROM alumni WHERE id IN ($placeholders)");
        $stmt->execute($preselected);
        $recipients = $stmt->fetchAll();

        $recipient_list = implode(',', array_column($recipients, 'id'));
        $pdo->prepare("INSERT INTO messages (subject, content, type, category, recipients, recipient_count, scheduled_at, status, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', ?)")
            ->execute([$subject, $content, $form['type'], $form['category'], $recipient_list, count($recipients), $scheduled_at, $_SESSION['user_id']]);
        $message_id = $pdo->lastInsertId();

        $ins = $pdo->prepare("INSERT INTO message_recipients (message_id, alumni_id, recipient_type) VALUES (?, ?, ?)");
        foreach ($recipients as $r) $ins->execute([$message_id, $r['id'], $form['type']]);

        if ($scheduled_at) {
            $_SESSION['message'] = "Message scheduled for " . date('M j, Y g:i A', strtotime($scheduled_at)) . " to " . count($recipients) . " recipients. It stays Pending until it is sent.";
        } else {
            [$ok, $fail] = sendMessagesNow($message_id, $recipients, $form['type'], $subject, $content);
            $_SESSION['message'] = "Message processed: $ok delivered" . ($fail ? ", $fail failed (see Message History for details)" : "") . ".";
            if ($fail) $_SESSION['message_type'] = $ok ? 'warning' : 'danger';
        }
        redirect('send_messages.php');
    }
}

// Alumni checklist
$alumni_list = $pdo->query("
    SELECT a.*, COALESCE(e.status, 'No Data') as employment_status
    FROM alumni a
    LEFT JOIN employment e ON a.id = e.alumni_id
    ORDER BY a.last_name
")->fetchAll();

$courses = $pdo->query("SELECT DISTINCT program FROM alumni ORDER BY program")->fetchAll(PDO::FETCH_COLUMN);
$years = $pdo->query("SELECT DISTINCT graduation_year FROM alumni ORDER BY graduation_year DESC")->fetchAll(PDO::FETCH_COLUMN);
$employment_statuses = ['Employed', 'Self-Employed', 'Unemployed', 'Pursuing Higher Education', 'No Data'];

$message = $_SESSION['message'] ?? '';
$message_type = $_SESSION['message_type'] ?? 'success';
unset($_SESSION['message'], $_SESSION['message_type']);
$e = fn($v) => htmlspecialchars((string)($v ?? ''));
?>
<div class="page-header">
    <h1>Send Messages</h1>
</div>

<ul class="nav-tabs mb-4">
    <li><a class="nav-link active" href="send_messages.php">Compose Message</a></li>
    <li><a class="nav-link" href="message_history.php">Message History</a></li>
    <li><a class="nav-link" href="message_templates.php">Templates</a></li>
</ul>

<?php if ($message): ?><div class="alert alert-<?= $e($message_type) ?>"><?= $e($message) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger"><?= $e($error) ?></div><?php endif; ?>

<div class="row">
    <!-- Compose Message Form -->
    <div class="col-md-5">
        <div class="card">
            <div class="card-header">Compose Message</div>
            <div class="card-body">
                <form method="post" id="messageForm">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

                    <div class="mb-3">
                        <label class="form-label">Message Type</label>
                        <select name="message_type" class="form-select" required>
                            <?php foreach ($type_options as $v => $l): ?>
                                <option value="<?= $v ?>" <?= $form['type'] === $v ? 'selected' : '' ?>><?= $l ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Category</label>
                        <select name="category" class="form-select" required>
                            <?php foreach ($categories as $v => $l): ?>
                                <option value="<?= $v ?>" <?= $form['category'] === $v ? 'selected' : '' ?>><?= $l ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Subject</label>
                        <input type="text" name="subject" class="form-control" maxlength="200" placeholder="Required for email" value="<?= $e($form['subject']) ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Message Content</label>
                        <textarea name="content" id="contentBox" class="form-control" rows="5" required placeholder="Write your message here..."><?= $e($form['content']) ?></textarea>
                        <div class="d-flex justify-content-between">
                            <small class="text-muted">Personalize with {first_name}, {last_name}, {program}, {year}</small>
                            <small class="text-muted" id="charCounter">0 characters</small>
                        </div>
                    </div>

                    <div class="mb-3">
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#templateModal">
                            <i class="fas fa-file-alt"></i> Load Template
                        </button>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Schedule (Optional)</label>
                        <div class="row">
                            <div class="col-md-6"><input type="date" name="schedule_date" class="form-control" min="<?= date('Y-m-d') ?>" value="<?= $e($form['schedule_date']) ?>"></div>
                            <div class="col-md-6"><input type="time" name="schedule_time" class="form-control" value="<?= $e($form['schedule_time']) ?>"></div>
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
                                data-year="<?= (int)$al['graduation_year'] ?>"
                                data-status="<?= htmlspecialchars($al['employment_status']) ?>">
                                <td><input type="checkbox" class="recipient-checkbox" value="<?= (int)$al['id'] ?>" <?= in_array((int)$al['id'], $preselected, true) ? 'checked' : '' ?>></td>
                                <td><?= htmlspecialchars($al['first_name'] . ' ' . $al['last_name']) ?></td>
                                <td><?= htmlspecialchars($al['program']) ?></td>
                                <td><?= (int)$al['graduation_year'] ?></td>
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
                                <td><?= htmlspecialchars($t['category']) ?></td>
                                <td><?= htmlspecialchars($t['type']) ?></td>
                                <td>
                                    <button class="btn btn-sm btn-primary load-template" 
                                            data-subject="<?= htmlspecialchars($t['subject'] ?? '') ?>"
                                            data-category="<?= htmlspecialchars($t['category']) ?>"
                                            data-content="<?= htmlspecialchars($t['content']) ?>"
                                            data-type="<?= htmlspecialchars($t['type']) ?>">
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

    return confirm('Send this message to ' + selected.length + ' alumni?');
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
        
        if (this.dataset.category) document.querySelector('select[name="category"]').value = this.dataset.category;
        updateCharCounter();

        const type = this.dataset.type;
        const typeSelect = document.querySelector('select[name="message_type"]');
        if (type === 'sms') typeSelect.value = 'sms';
        else if (type === 'email') typeSelect.value = 'email';
        else if (type === 'both') typeSelect.value = 'both';
        
        const modal = bootstrap.Modal.getInstance(document.getElementById('templateModal'));
        modal.hide();
    });
});

// Character / SMS segment counter
function updateCharCounter() {
    const n = document.getElementById('contentBox').value.length;
    const segs = Math.max(1, Math.ceil(n / 160));
    document.getElementById('charCounter').textContent = n + ' characters (' + segs + ' SMS segment' + (segs > 1 ? 's' : '') + ')';
}
document.getElementById('contentBox').addEventListener('input', updateCharCounter);
updateCharCounter();

// Initial counts
updateSelectedCount();
updateVisibleCount();
</script>

<?php include '../includes/footer.php'; ?>