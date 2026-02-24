<?php
require_once 'config/db.php';
require_once 'includes/functions.php';

// Fetch distinct programs
$programs = $pdo->query("SELECT DISTINCT program FROM alumni ORDER BY program")->fetchAll(PDO::FETCH_COLUMN);
if (empty($programs)) {
    $programs = [
        'BS Information Systems',
        'BS Computer Science',
        'BS Business Administration',
        'BS Accountancy',
        'BS Hospitality Management',
        'BS Education'
    ];
}

// Graduation years
$current_year = date('Y');
$years = range($current_year, 1970);

// Country codes
$countries = [
    '+63' => 'Philippines',
    '+1'  => 'USA/Canada',
    '+44' => 'United Kingdom',
    '+62' => 'Indonesia',
    '+60' => 'Malaysia',
    '+65' => 'Singapore',
    '+81' => 'Japan',
    '+82' => 'South Korea',
    '+61' => 'Australia',
    '+64' => 'New Zealand',
    '+86' => 'China',
    '+91' => 'India',
    '+33' => 'France',
    '+49' => 'Germany',
    '+39' => 'Italy',
    '+34' => 'Spain',
    '+31' => 'Netherlands',
    '+46' => 'Sweden',
    '+47' => 'Norway',
    '+45' => 'Denmark',
    '+358' => 'Finland',
    '+41' => 'Switzerland',
    '+43' => 'Austria',
    '+32' => 'Belgium',
    '+48' => 'Poland',
    '+420' => 'Czech Republic',
    '+36' => 'Hungary',
    '+30' => 'Greece',
    '+90' => 'Turkey',
    '+966' => 'Saudi Arabia',
    '+971' => 'UAE',
    '+974' => 'Qatar',
    '+20' => 'Egypt',
    '+27' => 'South Africa',
    '+234' => 'Nigeria',
    '+254' => 'Kenya',
    '+52' => 'Mexico',
    '+55' => 'Brazil',
    '+54' => 'Argentina',
    '+56' => 'Chile',
    '+57' => 'Colombia',
    '+58' => 'Venezuela',
    '+51' => 'Peru',
    '+593' => 'Ecuador',
    '+598' => 'Uruguay',
    '+595' => 'Paraguay',
];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!isset($_POST['terms'])) {
        $error = "You must agree to the Terms & Conditions and Privacy Policy.";
    } else {
        $full_name = cleanInput($_POST['full_name']);
        $name_parts = explode(' ', $full_name, 2);
        $first_name = $name_parts[0];
        $last_name = $name_parts[1] ?? '';
        
        $student_id = cleanInput($_POST['student_id']);
        $program = cleanInput($_POST['program']);
        $grad_year = (int)$_POST['grad_year'];
        $email = cleanInput($_POST['email']);
        
        $country_code = $_POST['country_code'] ?? '+63';
        $phone_number = cleanInput($_POST['phone'] ?? '');
        $phone = $country_code . ' ' . $phone_number;
        
        $password = $_POST['password'];
        $confirm = $_POST['confirm_password'];
        
        if ($password !== $confirm) {
            $error = "Passwords do not match.";
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            
            try {
                $pdo->beginTransaction();
                $stmt = $pdo->prepare("INSERT INTO alumni (student_id, first_name, last_name, program, graduation_year, email, phone) VALUES (?,?,?,?,?,?,?)");
                $stmt->execute([$student_id, $first_name, $last_name, $program, $grad_year, $email, $phone]);
                $alumni_id = $pdo->lastInsertId();
                
                $stmt2 = $pdo->prepare("INSERT INTO users (email, password, role, alumni_id) VALUES (?,?, 'alumni', ?)");
                $stmt2->execute([$email, $hashed, $alumni_id]);
                
                $pdo->commit();
                $success = "Registration successful! You can now login.";
            } catch (Exception $e) {
                $pdo->rollBack();
                $error = "Registration failed: " . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>USAT Alumni System - Register</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css">
</head>
<body class="auth-wrapper">
    <div class="auth-card" style="max-width: 550px;">
        <div class="text-center mb-4">
            <img src="<?= SITE_URL ?>/assets/img/usat-logo.jpg" alt="USAT Logo" style="max-width: 120px;">
        </div>
        <h2 class="text-center">Create Account</h2>
        <p class="text-center text-muted mb-4">Join the USAT alumni community</p>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?= $error ?></div>
        <?php endif; ?>
        <?php if (isset($success)): ?>
            <div class="alert alert-success"><?= $success ?></div>
        <?php endif; ?>
        
        <form method="post">
            <!-- Full Name -->
            <div class="mb-3">
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                    <input type="text" class="form-control" id="full_name" name="full_name" placeholder="Full Name" required>
                </div>
            </div>
            
            <!-- Student ID -->
            <div class="mb-3">
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-id-card"></i></span>
                    <input type="text" class="form-control" id="student_id" name="student_id" placeholder="Student ID (e.g., USAT-2026-0001)" required>
                </div>
            </div>
            
            <!-- Program Dropdown -->
            <div class="mb-3">
                <select class="form-select" id="program" name="program" required>
                    <option value="" disabled selected>Select your program</option>
                    <?php foreach ($programs as $p): ?>
                        <option value="<?= htmlspecialchars($p) ?>"><?= htmlspecialchars($p) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <!-- Graduation Year Dropdown -->
            <div class="mb-3">
                <select class="form-select" id="grad_year" name="grad_year" required>
                    <option value="" disabled selected>Graduation Year</option>
                    <?php foreach ($years as $year): ?>
                        <option value="<?= $year ?>"><?= $year ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <!-- Email -->
            <div class="mb-3">
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                    <input type="email" class="form-control" id="email" name="email" placeholder="Email address" required>
                </div>
            </div>
            
            <!-- Phone with Country Code -->
            <div class="mb-3">
                <label class="form-label">Phone (optional)</label>
                <div class="input-group">
                    <select class="form-select" name="country_code" id="country_code" style="max-width: 180px;">
                        <?php foreach ($countries as $code => $name): ?>
                            <option value="<?= $code ?>" <?= $code == '+63' ? 'selected' : '' ?>><?= $code ?> (<?= $name ?>)</option>
                        <?php endforeach; ?>
                    </select>
                    <input type="text" class="form-control" id="phone" name="phone" placeholder="9123456789">
                </div>
                <small class="text-muted">Enter local number without leading zero</small>
            </div>
            
            <!-- Password -->
            <div class="mb-3">
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                    <input type="password" class="form-control" id="password" name="password" placeholder="Password" required>
                </div>
            </div>
            
            <!-- Confirm Password -->
            <div class="mb-3">
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="Confirm Password" required>
                </div>
            </div>
            
            <!-- Terms -->
            <div class="mb-3 form-check">
                <input type="checkbox" class="form-check-input" id="terms" name="terms" required>
                <label class="form-check-label" for="terms">
                    I agree to the <a href="terms.php" target="_blank">Terms & Conditions</a> and 
                    <a href="privacy.php" target="_blank">Privacy Policy</a>.
                </label>
            </div>
            
            <button type="submit" class="btn btn-primary w-100 py-2">Register</button>
        </form>
        
        <p class="mt-3 text-center">
            Already have an account? <a href="index.php">Login here</a>.
        </p>
        
        <div class="footer-links">
            <a href="about.php">About</a> | 
            <a href="contact.php">Contact</a> | 
            <a href="privacy.php">Privacy</a> | 
            <a href="terms.php">Terms</a>
        </div>
    </div>
</body>
</html>