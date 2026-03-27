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

// Graduated years (1970 to current year)
$current_year = date('Y');
$years = range($current_year, 1970);

// Country codes with sample numbers
$countries = [
    '+63' => ['name' => 'Philippines', 'sample' => '9123456789'],
    '+1'  => ['name' => 'USA/Canada', 'sample' => '2125551234'],
    '+44' => ['name' => 'United Kingdom', 'sample' => '7912345678'],
    '+62' => ['name' => 'Indonesia', 'sample' => '812345678'],
    '+60' => ['name' => 'Malaysia', 'sample' => '123456789'],
    '+65' => ['name' => 'Singapore', 'sample' => '91234567'],
    '+81' => ['name' => 'Japan', 'sample' => '9012345678'],
    '+82' => ['name' => 'South Korea', 'sample' => '1012345678'],
    '+61' => ['name' => 'Australia', 'sample' => '412345678'],
    '+64' => ['name' => 'New Zealand', 'sample' => '211234567'],
    '+86' => ['name' => 'China', 'sample' => '13123456789'],
    '+91' => ['name' => 'India', 'sample' => '9876543210'],
    '+33' => ['name' => 'France', 'sample' => '612345678'],
    '+49' => ['name' => 'Germany', 'sample' => '1512345678'],
    '+39' => ['name' => 'Italy', 'sample' => '3123456789'],
    '+34' => ['name' => 'Spain', 'sample' => '612345678'],
    '+31' => ['name' => 'Netherlands', 'sample' => '612345678'],
    '+46' => ['name' => 'Sweden', 'sample' => '701234567'],
    '+47' => ['name' => 'Norway', 'sample' => '41234567'],
    '+45' => ['name' => 'Denmark', 'sample' => '21234567'],
    '+358' => ['name' => 'Finland', 'sample' => '401234567'],
    '+41' => ['name' => 'Switzerland', 'sample' => '791234567'],
    '+43' => ['name' => 'Austria', 'sample' => '664123456'],
    '+32' => ['name' => 'Belgium', 'sample' => '470123456'],
    '+48' => ['name' => 'Poland', 'sample' => '601234567'],
    '+420' => ['name' => 'Czech Republic', 'sample' => '601234567'],
    '+36' => ['name' => 'Hungary', 'sample' => '20123456'],
    '+30' => ['name' => 'Greece', 'sample' => '6912345678'],
    '+90' => ['name' => 'Turkey', 'sample' => '5312345678'],
    '+966' => ['name' => 'Saudi Arabia', 'sample' => '501234567'],
    '+971' => ['name' => 'UAE', 'sample' => '501234567'],
    '+974' => ['name' => 'Qatar', 'sample' => '33123456'],
    '+20' => ['name' => 'Egypt', 'sample' => '1012345678'],
    '+27' => ['name' => 'South Africa', 'sample' => '712345678'],
    '+234' => ['name' => 'Nigeria', 'sample' => '7012345678'],
    '+254' => ['name' => 'Kenya', 'sample' => '712345678'],
    '+52' => ['name' => 'Mexico', 'sample' => '5512345678'],
    '+55' => ['name' => 'Brazil', 'sample' => '11912345678'],
    '+54' => ['name' => 'Argentina', 'sample' => '1123456789'],
    '+56' => ['name' => 'Chile', 'sample' => '912345678'],
    '+57' => ['name' => 'Colombia', 'sample' => '3001234567'],
    '+58' => ['name' => 'Venezuela', 'sample' => '4121234567'],
    '+51' => ['name' => 'Peru', 'sample' => '987654321'],
    '+593' => ['name' => 'Ecuador', 'sample' => '987654321'],
    '+598' => ['name' => 'Uruguay', 'sample' => '98765432'],
    '+595' => ['name' => 'Paraguay', 'sample' => '981234567'],
];

// Prepare samples array for JavaScript
$samples = [];
foreach ($countries as $code => $data) {
    $samples[$code] = $data['sample'];
}

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
                    <option value="" disabled selected>Graduated Year</option>
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
                        <?php foreach ($countries as $code => $data): ?>
                            <option value="<?= $code ?>" <?= $code == '+63' ? 'selected' : '' ?>><?= $code ?> (<?= $data['name'] ?>)</option>
                        <?php endforeach; ?>
                    </select>
                    <input type="text" class="form-control" id="phone" name="phone" placeholder="9123456789" pattern="\d{7,15}" title="Please enter a valid phone number with 7 to 15 digits" maxlength="15">
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

    <script>
        // Map country sample phone numbers (generated with json_encode)
        const countrySamples = <?= json_encode($samples, JSON_PRETTY_PRINT) ?>;

        function updatePhonePlaceholder() {
            const select = document.getElementById('country_code');
            const phoneInput = document.getElementById('phone');
            const selectedCode = select.value;
            const sample = countrySamples[selectedCode] || "enter your phone number";
            phoneInput.placeholder = sample;
        }

        document.addEventListener('DOMContentLoaded', function() {
            updatePhonePlaceholder();
            document.getElementById('country_code').addEventListener('change', updatePhonePlaceholder);
        });
    </script>
</body>
</html>