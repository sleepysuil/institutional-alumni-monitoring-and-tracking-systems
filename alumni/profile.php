<?php
require_once '../includes/alumni_header.php';
$alumni_id = $_SESSION['alumni_id'];

// Fetch current data
$alumni = $pdo->prepare("SELECT * FROM alumni WHERE id = ?");
$alumni->execute([$alumni_id]);
$alumni = $alumni->fetch();

$employment = $pdo->prepare("SELECT * FROM employment WHERE alumni_id = ?");
$employment->execute([$alumni_id]);
$employment = $employment->fetch();

// Fetch employment history
$history = $pdo->prepare("SELECT * FROM employment_history WHERE alumni_id = ? ORDER BY start_date DESC");
$history->execute([$alumni_id]);
$history = $history->fetchAll();

// Handle personal info update (including photo)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_personal'])) {
    $first = cleanInput($_POST['first_name']);
    $middle = cleanInput($_POST['middle_name']);
    $last = cleanInput($_POST['last_name']);
    $age = (int)$_POST['age'];
    $gender = $_POST['gender'];
    $email = cleanInput($_POST['email']);
    $phone = cleanInput($_POST['phone']);
    
    // Handle file upload
    $profile_pic = $alumni['profile_pic'];
    if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = $_FILES['profile_pic']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        if (in_array($ext, $allowed)) {
            $upload_dir = '../uploads/profiles/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
            $new_filename = uniqid() . '_' . $alumni_id . '.' . $ext;
            $destination = $upload_dir . $new_filename;
            if (move_uploaded_file($_FILES['profile_pic']['tmp_name'], $destination)) {
                $profile_pic = 'uploads/profiles/' . $new_filename;
                // Delete old file
                if ($alumni['profile_pic'] && file_exists('../' . $alumni['profile_pic'])) {
                    unlink('../' . $alumni['profile_pic']);
                }
            }
        } else {
            $error = "Invalid file type. Only JPG, PNG, GIF allowed.";
        }
    }
    
    if (!isset($error)) {
        $stmt = $pdo->prepare("UPDATE alumni SET first_name=?, middle_name=?, last_name=?, age=?, gender=?, email=?, phone=?, profile_pic=? WHERE id=?");
        $stmt->execute([$first, $middle, $last, $age, $gender, $email, $phone, $profile_pic, $alumni_id]);
        $_SESSION['message'] = "Profile updated.";
        redirect('profile.php');
    }
}

// Handle employment update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_employment'])) {
    $status = $_POST['status'];
    $company = cleanInput($_POST['company']);
    $position = cleanInput($_POST['position']);
    $industry = cleanInput($_POST['industry']);
    $salary = cleanInput($_POST['salary_range']);
    $relevance = $_POST['relevance'];
    
    if ($employment) {
        $pdo->prepare("UPDATE employment SET status=?, company=?, position=?, industry=?, salary_range=?, relevance=? WHERE alumni_id=?")
            ->execute([$status, $company, $position, $industry, $salary, $relevance, $alumni_id]);
    } else {
        $pdo->prepare("INSERT INTO employment (alumni_id, status, company, position, industry, salary_range, relevance) VALUES (?,?,?,?,?,?,?)")
            ->execute([$alumni_id, $status, $company, $position, $industry, $salary, $relevance]);
    }
    $_SESSION['message'] = "Employment info updated.";
    redirect('profile.php');
}

$message = $_SESSION['message'] ?? '';
unset($_SESSION['message']);
$error = $error ?? '';
?>
<div class="page-header">
    <h1>My Profile</h1>
</div>

<?php if ($message): ?><div class="alert alert-success"><?= $message ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>

<div class="row g-4">
    <!-- Left column: Profile Summary -->
    <div class="col-md-4">
        <div class="card text-center">
            <div class="card-body">
                <?php if ($alumni['profile_pic'] && file_exists('../' . $alumni['profile_pic'])): ?>
                    <img src="<?= SITE_URL ?>/<?= $alumni['profile_pic'] ?>" alt="Profile" class="rounded-circle mb-3" style="width: 150px; height: 150px; object-fit: cover; border: 3px solid var(--primary);">
                <?php else: ?>
                    <i class="fas fa-user-circle fa-6x mb-3" style="color: var(--primary);"></i>
                <?php endif; ?>
                <h5><?= htmlspecialchars($alumni['first_name'] . ' ' . ($alumni['middle_name'] ? $alumni['middle_name'] . ' ' : '') . $alumni['last_name']) ?></h5>
                <p class="text-muted"><?= $alumni['student_id'] ?></p>
                <hr>
                <p><strong>Program:</strong> <?= htmlspecialchars($alumni['program']) ?></p>
                <p><strong>Graduation:</strong> <?= $alumni['graduation_year'] ?></p>
                <p><strong>Age:</strong> <?= $alumni['age'] ?? 'N/A' ?></p>
                <p><strong>Gender:</strong> <?= $alumni['gender'] ?? 'N/A' ?></p>
                <p><strong>Email:</strong> <?= $alumni['email'] ?></p>
                <p><strong>Phone:</strong> <?= $alumni['phone'] ?></p>
            </div>
        </div>
    </div>
    
    <!-- Right column: Forms and History -->
    <div class="col-md-8">
        <!-- Personal Info Form (with new fields) -->
        <div class="card mb-4">
            <div class="card-header">Personal Information</div>
            <div class="card-body">
                <form method="post" enctype="multipart/form-data">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">First Name</label>
                            <input type="text" name="first_name" class="form-control" value="<?= htmlspecialchars($alumni['first_name']) ?>" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Middle Name</label>
                            <input type="text" name="middle_name" class="form-control" value="<?= htmlspecialchars($alumni['middle_name']) ?>">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Last Name</label>
                            <input type="text" name="last_name" class="form-control" value="<?= htmlspecialchars($alumni['last_name']) ?>" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Age</label>
                            <input type="number" name="age" class="form-control" value="<?= $alumni['age'] ?>">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Gender</label>
                            <select name="gender" class="form-select">
                                <option value="">Select</option>
                                <option value="Male" <?= ($alumni['gender']??'')=='Male'?'selected':'' ?>>Male</option>
                                <option value="Female" <?= ($alumni['gender']??'')=='Female'?'selected':'' ?>>Female</option>
                                <option value="Other" <?= ($alumni['gender']??'')=='Other'?'selected':'' ?>>Other</option>
                                <option value="Prefer not to say" <?= ($alumni['gender']??'')=='Prefer not to say'?'selected':'' ?>>Prefer not to say</option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($alumni['email']) ?>" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Phone</label>
                        <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($alumni['phone']) ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Profile Picture</label>
                        <input type="file" name="profile_pic" class="form-control" accept="image/*">
                        <small class="text-muted">Leave empty to keep current. Max 2MB. JPG, PNG, GIF.</small>
                    </div>
                    <button type="submit" name="update_personal" class="btn btn-primary">Save Changes</button>
                </form>
            </div>
        </div>

        <!-- Employment Form (unchanged) -->
        <div class="card mb-4">
            <div class="card-header">Current Employment</div>
            <div class="card-body">
                <form method="post">
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="Employed" <?= ($employment['status']??'')=='Employed'?'selected':'' ?>>Employed</option>
                            <option value="Self-Employed" <?= ($employment['status']??'')=='Self-Employed'?'selected':'' ?>>Self-Employed</option>
                            <option value="Unemployed" <?= ($employment['status']??'')=='Unemployed'?'selected':'' ?>>Unemployed</option>
                            <option value="Pursuing Higher Education" <?= ($employment['status']??'')=='Pursuing Higher Education'?'selected':'' ?>>Pursuing Higher Education</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Company</label>
                        <input type="text" name="company" class="form-control" value="<?= htmlspecialchars($employment['company'] ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Position</label>
                        <input type="text" name="position" class="form-control" value="<?= htmlspecialchars($employment['position'] ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Industry</label>
                        <input type="text" name="industry" class="form-control" value="<?= htmlspecialchars($employment['industry'] ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Salary Range</label>
                        <input type="text" name="salary_range" class="form-control" value="<?= htmlspecialchars($employment['salary_range'] ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Relevance to Degree</label>
                        <select name="relevance" class="form-select">
                            <option value="Highly Relevant" <?= ($employment['relevance']??'')=='Highly Relevant'?'selected':'' ?>>Highly Relevant</option>
                            <option value="Somewhat Relevant" <?= ($employment['relevance']??'')=='Somewhat Relevant'?'selected':'' ?>>Somewhat Relevant</option>
                            <option value="Not Relevant" <?= ($employment['relevance']??'')=='Not Relevant'?'selected':'' ?>>Not Relevant</option>
                        </select>
                    </div>
                    <button type="submit" name="update_employment" class="btn btn-primary">Update Employment</button>
                </form>
            </div>
        </div>

        <!-- Employment History -->
        <?php if (count($history) > 0): ?>
        <div class="card">
            <div class="card-header">Employment History</div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr><th>Company</th><th>Position</th><th>Start</th><th>End</th> </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($history as $h): ?>
                            <tr>
                                <td><?= htmlspecialchars($h['company']) ?></td>
                                <td><?= htmlspecialchars($h['position']) ?></td>
                                <td><?= $h['start_date'] ?></td>
                                <td><?= $h['end_date'] ?? 'Present' ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php include '../includes/footer.php'; ?>