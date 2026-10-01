<?php
require_once '../includes/alumni_header.php';
$alumni_id = $_SESSION['alumni_id'];
if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

function loadAlumni($pdo, $id) {
    $s = $pdo->prepare("SELECT * FROM alumni WHERE id = ?");
    $s->execute([$id]);
    return $s->fetch();
}

$alumni = loadAlumni($pdo, $alumni_id);

$employment = $pdo->prepare("SELECT * FROM employment WHERE alumni_id = ?");
$employment->execute([$alumni_id]);
$employment = $employment->fetch();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] == 'POST' && !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
    $_SESSION['message'] = "Invalid request. Please try again.";
    redirect('profile.php');
}

// ---------- Personal info (including photo) ----------
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_personal'])) {
    $first  = cleanInput($_POST['first_name'] ?? '');
    $middle = cleanInput($_POST['middle_name'] ?? '');
    $last   = cleanInput($_POST['last_name'] ?? '');
    $age    = ($_POST['age'] ?? '') !== '' ? (int)$_POST['age'] : null;
    $gender = $_POST['gender'] ?? '';
    $email  = trim($_POST['email'] ?? '');
    $phone  = cleanInput($_POST['phone'] ?? '');

    if ($first === '' || $last === '') $errors[] = "First and last name are required.";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Please enter a valid email address.";
    if ($age !== null && ($age < 15 || $age > 100)) $errors[] = "Please enter a valid age.";
    if (!in_array($gender, ['Male', 'Female', 'Other', 'Prefer not to say'], true)) $gender = null;

    // Email must not belong to someone else (alumni record or login account)
    if (!$errors) {
        $dup = $pdo->prepare("SELECT (SELECT COUNT(*) FROM alumni WHERE email = ? AND id <> ?)
                                   + (SELECT COUNT(*) FROM users WHERE email = ? AND (alumni_id IS NULL OR alumni_id <> ?))");
        $dup->execute([$email, $alumni_id, $email, $alumni_id]);
        if ($dup->fetchColumn() > 0) $errors[] = "That email address is already in use.";
    }

    // Photo upload
    $profile_pic = $alumni['profile_pic'];
    $new_file = null;
    if (!$errors && isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] !== UPLOAD_ERR_NO_FILE) {
        $f = $_FILES['profile_pic'];
        $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
        if ($f['error'] !== UPLOAD_ERR_OK) {
            $errors[] = "Upload failed. Please try again with a smaller image.";
        } elseif ($f['size'] > 2 * 1024 * 1024) {
            $errors[] = "Image is too large. Maximum size is 2MB.";
        } elseif (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif'], true) || @getimagesize($f['tmp_name']) === false) {
            $errors[] = "Invalid file. Only real JPG, PNG or GIF images are allowed.";
        } else {
            $upload_dir = '../uploads/profiles/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
            $new_name = uniqid() . '_' . $alumni_id . '.' . $ext;
            if (move_uploaded_file($f['tmp_name'], $upload_dir . $new_name)) {
                $new_file = 'uploads/profiles/' . $new_name;
            } else {
                $errors[] = "Could not save the uploaded image.";
            }
        }
    }

    if (!$errors) {
        if ($new_file) {
            if (!empty($alumni['profile_pic']) && file_exists('../' . $alumni['profile_pic'])) {
                @unlink('../' . $alumni['profile_pic']);
            }
            $profile_pic = $new_file;
        }
        $pdo->beginTransaction();
        $pdo->prepare("UPDATE alumni SET first_name=?, middle_name=?, last_name=?, age=?, gender=?, email=?, phone=?, profile_pic=? WHERE id=?")
            ->execute([$first, $middle, $last, $age, $gender, $email, $phone, $profile_pic, $alumni_id]);
        // Keep the login email in sync with the profile email
        $pdo->prepare("UPDATE users SET email = ? WHERE alumni_id = ?")->execute([$email, $alumni_id]);
        $pdo->commit();
        $_SESSION['message'] = "Profile updated.";
        redirect('profile.php');
    }
    // On error, show what the user typed rather than the old DB values
    $alumni = array_merge($alumni, ['first_name' => $first, 'middle_name' => $middle, 'last_name' => $last,
        'age' => $age, 'gender' => $gender, 'email' => $email, 'phone' => $phone]);
}

// ---------- Employment ----------
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_employment'])) {
    $statuses = ['Employed', 'Self-Employed', 'Unemployed', 'Pursuing Higher Education'];
    $status = in_array($_POST['status'] ?? '', $statuses, true) ? $_POST['status'] : null;
    if (!$status) $errors[] = "Please choose a valid employment status.";

    $working = in_array($status, ['Employed', 'Self-Employed'], true);
    $company  = $working ? cleanInput($_POST['company'] ?? '') : null;
    $position = $working ? cleanInput($_POST['position'] ?? '') : null;
    $industry = $working ? cleanInput($_POST['industry'] ?? '') : null;
    $salary   = $working ? cleanInput($_POST['salary_range'] ?? '') : null;
    $relevance = $working && in_array($_POST['relevance'] ?? '', ['Highly Relevant', 'Somewhat Relevant', 'Not Relevant'], true)
        ? $_POST['relevance'] : null;
    $start = $working && !empty($_POST['start_date']) ? $_POST['start_date'] : null;

    if (!$errors) {
        if ($employment) {
            $pdo->prepare("UPDATE employment SET status=?, company=?, position=?, industry=?, salary_range=?, relevance=?, start_date=? WHERE alumni_id=?")
                ->execute([$status, $company, $position, $industry, $salary, $relevance, $start, $alumni_id]);
        } else {
            $pdo->prepare("INSERT INTO employment (alumni_id, status, company, position, industry, salary_range, relevance, start_date) VALUES (?,?,?,?,?,?,?,?)")
                ->execute([$alumni_id, $status, $company, $position, $industry, $salary, $relevance, $start]);
        }
        $_SESSION['message'] = "Employment info updated.";
        redirect('profile.php');
    }
}

// ---------- Employment history: add / delete ----------
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_history'])) {
    $h_company  = cleanInput($_POST['h_company'] ?? '');
    $h_position = cleanInput($_POST['h_position'] ?? '');
    $h_industry = cleanInput($_POST['h_industry'] ?? '');
    $h_start = $_POST['h_start'] ?? '';
    $h_end   = $_POST['h_end'] ?? '';
    if ($h_company === '' || $h_position === '' || $h_start === '') {
        $errors[] = "Company, position and start date are required for a past job.";
    } elseif ($h_end !== '' && $h_end < $h_start) {
        $errors[] = "End date cannot be before the start date.";
    } else {
        $pdo->prepare("INSERT INTO employment_history (alumni_id, company, position, industry, start_date, end_date) VALUES (?,?,?,?,?,?)")
            ->execute([$alumni_id, $h_company, $h_position, $h_industry ?: null, $h_start, $h_end ?: null]);
        $_SESSION['message'] = "Past job added.";
        redirect('profile.php');
    }
}
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_history'])) {
    $pdo->prepare("DELETE FROM employment_history WHERE id = ? AND alumni_id = ?")->execute([(int)$_POST['delete_history'], $alumni_id]);
    $_SESSION['message'] = "History entry removed.";
    redirect('profile.php');
}

// Fetch employment history (after any changes)
$history = $pdo->prepare("SELECT * FROM employment_history WHERE alumni_id = ? ORDER BY start_date DESC");
$history->execute([$alumni_id]);
$history = $history->fetchAll();

$message = $_SESSION['message'] ?? '';
unset($_SESSION['message']);
$csrf = $_SESSION['csrf_token'];
$e = fn($v) => htmlspecialchars((string)($v ?? ''));
?>
<div class="page-header">
    <h1>My Profile</h1>
</div>

<?php if ($message): ?><div class="alert alert-success"><?= $e($message) ?></div><?php endif; ?>
<?php foreach ($errors as $err): ?><div class="alert alert-danger"><?= $e($err) ?></div><?php endforeach; ?>

<div class="row g-4">
    <!-- Left column: Profile Summary -->
    <div class="col-md-4">
        <div class="card text-center">
            <div class="card-body">
                <?php if (!empty($alumni['profile_pic']) && file_exists('../' . $alumni['profile_pic'])): ?>
                    <img src="<?= SITE_URL ?>/<?= $e($alumni['profile_pic']) ?>" alt="Profile" class="rounded-circle mb-3" style="width: 150px; height: 150px; object-fit: cover; border: 3px solid var(--primary);">
                <?php else: ?>
                    <i class="fas fa-user-circle fa-6x mb-3" style="color: var(--primary);"></i>
                <?php endif; ?>
                <h5><?= $e($alumni['first_name'] . ' ' . (!empty($alumni['middle_name']) ? $alumni['middle_name'] . ' ' : '') . $alumni['last_name']) ?></h5>
                <p class="text-muted"><?= $e($alumni['student_id']) ?></p>
                <hr>
                <p><strong>Program:</strong> <?= $e($alumni['program']) ?></p>
                <p><strong>Graduation:</strong> <?= (int)$alumni['graduation_year'] ?></p>
                <p><strong>Age:</strong> <?= $e($alumni['age'] ?? 'N/A') ?></p>
                <p><strong>Gender:</strong> <?= $e($alumni['gender'] ?? 'N/A') ?></p>
                <p><strong>Email:</strong> <?= $e($alumni['email']) ?></p>
                <p><strong>Phone:</strong> <?= $e($alumni['phone']) ?></p>
            </div>
        </div>
    </div>

    <!-- Right column -->
    <div class="col-md-8">
        <!-- Personal Info -->
        <div class="card mb-4">
            <div class="card-header">Personal Information</div>
            <div class="card-body">
                <form method="post" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">First Name</label>
                            <input type="text" name="first_name" class="form-control" value="<?= $e($alumni['first_name']) ?>" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Middle Name</label>
                            <input type="text" name="middle_name" class="form-control" value="<?= $e($alumni['middle_name']) ?>">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Last Name</label>
                            <input type="text" name="last_name" class="form-control" value="<?= $e($alumni['last_name']) ?>" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Age</label>
                            <input type="number" name="age" min="15" max="100" class="form-control" value="<?= $e($alumni['age']) ?>">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Gender</label>
                            <select name="gender" class="form-select">
                                <option value="">Select</option>
                                <?php foreach (['Male', 'Female', 'Other', 'Prefer not to say'] as $g): ?>
                                    <option value="<?= $g ?>" <?= ($alumni['gender'] ?? '') == $g ? 'selected' : '' ?>><?= $g ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" value="<?= $e($alumni['email']) ?>" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Phone</label>
                        <input type="text" name="phone" class="form-control" value="<?= $e($alumni['phone']) ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Profile Picture</label>
                        <input type="file" name="profile_pic" class="form-control" accept="image/jpeg,image/png,image/gif">
                        <small class="text-muted">Leave empty to keep current. Max 2MB. JPG, PNG, GIF.</small>
                    </div>
                    <button type="submit" name="update_personal" class="btn btn-primary">Save Changes</button>
                </form>
            </div>
        </div>

        <!-- Current Employment -->
        <div class="card mb-4">
            <div class="card-header">Current Employment</div>
            <div class="card-body">
                <form method="post">
                    <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select name="status" id="emp_status" class="form-select">
                            <?php foreach (['Employed', 'Self-Employed', 'Unemployed', 'Pursuing Higher Education'] as $s): ?>
                                <option value="<?= $s ?>" <?= ($employment['status'] ?? '') == $s ? 'selected' : '' ?>><?= $s ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div id="emp_fields">
                        <div class="mb-3">
                            <label class="form-label">Company / Business</label>
                            <input type="text" name="company" class="form-control" value="<?= $e($employment['company'] ?? '') ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Position</label>
                            <input type="text" name="position" class="form-control" value="<?= $e($employment['position'] ?? '') ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Industry</label>
                            <input type="text" name="industry" class="form-control" value="<?= $e($employment['industry'] ?? '') ?>">
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Salary Range</label>
                                <input type="text" name="salary_range" class="form-control" value="<?= $e($employment['salary_range'] ?? '') ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Start Date</label>
                                <input type="date" name="start_date" class="form-control" value="<?= $e($employment['start_date'] ?? '') ?>">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Relevance to Degree</label>
                            <select name="relevance" class="form-select">
                                <?php foreach (['Highly Relevant', 'Somewhat Relevant', 'Not Relevant'] as $r): ?>
                                    <option value="<?= $r ?>" <?= ($employment['relevance'] ?? '') == $r ? 'selected' : '' ?>><?= $r ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <button type="submit" name="update_employment" class="btn btn-primary">Update Employment</button>
                </form>
            </div>
        </div>

        <!-- Employment History -->
        <div class="card">
            <div class="card-header">Employment History</div>
            <div class="card-body">
                <?php if (count($history) > 0): ?>
                <div class="table-responsive mb-4">
                    <table class="table table-sm align-middle">
                        <thead>
                            <tr><th>Company</th><th>Position</th><th>Start</th><th>End</th><th></th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($history as $h): ?>
                            <tr>
                                <td><?= $e($h['company']) ?></td>
                                <td><?= $e($h['position']) ?></td>
                                <td><?= $h['start_date'] ? date('M Y', strtotime($h['start_date'])) : '' ?></td>
                                <td><?= $h['end_date'] ? date('M Y', strtotime($h['end_date'])) : 'Present' ?></td>
                                <td class="text-end">
                                    <form method="post" class="d-inline" onsubmit="return confirm('Remove this entry?')">
                                        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                                        <input type="hidden" name="delete_history" value="<?= $h['id'] ?>">
                                        <button class="btn btn-sm btn-outline-danger" title="Remove"><i class="fas fa-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                    <p class="text-muted">No past jobs recorded yet.</p>
                <?php endif; ?>

                <h6>Add a past job</h6>
                <form method="post">
                    <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                    <div class="row g-2">
                        <div class="col-md-4"><input type="text" name="h_company" class="form-control" placeholder="Company" required></div>
                        <div class="col-md-4"><input type="text" name="h_position" class="form-control" placeholder="Position" required></div>
                        <div class="col-md-4"><input type="text" name="h_industry" class="form-control" placeholder="Industry (optional)"></div>
                        <div class="col-md-4"><label class="form-label small mb-0">Start</label><input type="date" name="h_start" class="form-control" required></div>
                        <div class="col-md-4"><label class="form-label small mb-0">End (blank if ongoing)</label><input type="date" name="h_end" class="form-control"></div>
                        <div class="col-md-4 d-flex align-items-end"><button type="submit" name="add_history" class="btn btn-outline-primary w-100">Add</button></div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Hide job fields when the person isn't working
(function () {
    var sel = document.getElementById('emp_status');
    var box = document.getElementById('emp_fields');
    function toggle() {
        box.style.display = (sel.value === 'Employed' || sel.value === 'Self-Employed') ? '' : 'none';
    }
    sel.addEventListener('change', toggle);
    toggle();
})();
</script>

<?php include '../includes/footer.php'; ?>