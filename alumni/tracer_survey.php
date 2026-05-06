<?php
require_once '../includes/alumni_header.php';
$alumni_id = $_SESSION['alumni_id'];

// Get current employment status
$emp = $pdo->prepare("SELECT status FROM employment WHERE alumni_id = ?");
$emp->execute([$alumni_id]);
$current_status = $emp->fetchColumn();

// Check if already submitted today
$already = $pdo->prepare("SELECT id FROM tracer_responses WHERE alumni_id = ? AND response_date = CURDATE()");
$already->execute([$alumni_id]);
if ($already->fetch()) $already_submitted = true;

if ($_SERVER['REQUEST_METHOD'] == 'POST' && !isset($already_submitted)) {
    $is_employed = $_POST['is_employed'] ?? 0;
    $job_title = cleanInput($_POST['job_title'] ?? '');
    $company = cleanInput($_POST['company'] ?? '');
    $industry = cleanInput($_POST['industry'] ?? '');
    $relevance = cleanInput($_POST['relevance'] ?? '');
    
    $proof_file = null;
    
    // Handle file upload - only for employed and self-employed
    if (isset($_FILES['proof_file']) && $_FILES['proof_file']['error'] == 0 && ($is_employed == 1 || $is_employed == 2)) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'pdf'];
        $filename = $_FILES['proof_file']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        if (in_array($ext, $allowed)) {
            $upload_dir = '../uploads/tracer_proofs/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
            $new_filename = uniqid() . '_' . $alumni_id . '_proof.' . $ext;
            $destination = $upload_dir . $new_filename;
            if (move_uploaded_file($_FILES['proof_file']['tmp_name'], $destination)) {
                $proof_file = 'uploads/tracer_proofs/' . $new_filename;
            }
        } else {
            $error = "Invalid file type. Allowed: JPG, PNG, GIF, PDF";
        }
    }
    
    // Survey data array
    $survey_data = [];

    // Unemployed section
    if ($is_employed == 0) {
        $survey_data['seeking_time'] = $_POST['seeking_time'] ?? '';
        $survey_data['interviews'] = $_POST['interviews'] ?? '';
        $survey_data['employer_feedback'] = $_POST['employer_feedback'] ?? '';
        $survey_data['location_factor'] = $_POST['location_factor'] ?? '';
        $survey_data['impact_factor'] = $_POST['impact_factor'] ?? '';
        $survey_data['other_impact'] = cleanInput($_POST['other_impact'] ?? '');
        $survey_data['skill_development'] = $_POST['skill_development'] ?? '';
        $survey_data['job_search_experience'] = $_POST['job_search_experience'] ?? '';
        $survey_data['career_resources'] = $_POST['career_resources'] ?? '';
    }
    // Employed section
    elseif ($is_employed == 1) {
        $survey_data['job_category'] = $_POST['job_category'] ?? '';
        $survey_data['job_title_desc'] = cleanInput($_POST['job_title_desc'] ?? '');
        $survey_data['employer_name'] = cleanInput($_POST['employer_name'] ?? '');
        $survey_data['employment_date'] = $_POST['employment_date'] ?? '';
        $survey_data['region'] = $_POST['region'] ?? '';
        $survey_data['city'] = $_POST['city'] ?? '';
        $survey_data['time_to_first_job'] = $_POST['time_to_first_job'] ?? '';
        $survey_data['internship_helpfulness'] = $_POST['internship_helpfulness'] ?? '';
        $survey_data['networking_participation'] = $_POST['networking_participation'] ?? '';
        $survey_data['skills_critical'] = [
            'skill1' => cleanInput($_POST['skill1'] ?? ''),
            'skill2' => cleanInput($_POST['skill2'] ?? ''),
            'skill3' => cleanInput($_POST['skill3'] ?? '')
        ];
    }
    // Self-employed section
    elseif ($is_employed == 2) {
        $survey_data['business_name'] = cleanInput($_POST['business_name'] ?? '');
        $survey_data['business_type'] = cleanInput($_POST['business_type'] ?? '');
        $survey_data['business_industry'] = cleanInput($_POST['business_industry'] ?? '');
        $survey_data['business_size'] = $_POST['business_size'] ?? '';
        $survey_data['business_start_date'] = $_POST['business_start_date'] ?? '';
    }

    // Save to database
    $stmt = $pdo->prepare("INSERT INTO tracer_responses (alumni_id, response_date, is_employed, job_title, company, industry, relevance, survey_data, proof_file)
                           VALUES (?, CURDATE(), ?, ?, ?, ?, ?, ?, ?)
                           ON DUPLICATE KEY UPDATE
                               is_employed = VALUES(is_employed),
                               job_title = VALUES(job_title),
                               company = VALUES(company),
                               industry = VALUES(industry),
                               relevance = VALUES(relevance),n n
                               survey_data = VALUES(survey_data),
                               proof_file = VALUES(proof_file)");
    if ($stmt->execute([$alumni_id, $is_employed, $job_title, $company, $industry, $relevance, json_encode($survey_data), $proof_file])) {
        $success = "Thank you for completing the survey!";
    } else {
        $error = "Failed to save survey. Please try again.";
    }
}

// Get existing proof file if any
$existing = $pdo->prepare("SELECT proof_file FROM tracer_responses WHERE alumni_id = ? ORDER BY response_date DESC LIMIT 1");
$existing->execute([$alumni_id]);
$existing_proof = $existing->fetchColumn();
?>
<div class="page-header">
    <h1>Tracer Survey</h1>
</div>

<?php if (isset($already_submitted)): ?>
    <div class="alert alert-info">You have already submitted today. Thank you!</div>
<?php elseif (isset($success)): ?>
    <div class="alert alert-success"><?= $success ?></div>
<?php elseif (isset($error)): ?>
    <div class="alert alert-danger"><?= $error ?></div>
<?php else: ?>
<div class="card">
    <div class="card-body">
        <form method="post" enctype="multipart/form-data">
            <!-- Employment Status Selection -->
            <div class="mb-3">
                <label class="form-label">Are you currently employed?</label><br>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="is_employed" id="emp_yes" value="1" <?= $current_status == 'Employed' ? 'checked' : '' ?> required>
                    <label class="form-check-label" for="emp_yes">Yes (Employed)</label>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="is_employed" id="emp_no" value="0" <?= $current_status == 'Unemployed' ? 'checked' : '' ?>>
                    <label class="form-check-label" for="emp_no">No (Unemployed)</label>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="is_employed" id="emp_self" value="2" <?= $current_status == 'Self-Employed' ? 'checked' : '' ?>>
                    <label class="form-check-label" for="emp_self">Self-Employed</label>
                </div>
            </div>

            <!-- ========== PROOF OF EMPLOYMENT UPLOAD SECTION ========== -->
            <div id="proofSection" style="display: none;">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Proof of Employment / Business:</strong><br>
                    <small>Please upload a valid proof (Employment ID, Certificate of Employment, Business Permit, DTI Registration, etc.)<br>
                    Accepted formats: JPG, PNG, GIF, PDF. Max file size: 5MB.</small>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Upload Proof Document</label>
                    <input type="file" name="proof_file" class="form-control" accept=".jpg,.jpeg,.png,.gif,.pdf">
                    <?php if ($existing_proof): ?>
                        <div class="mt-2">
                            <span class="badge bg-success">Previously uploaded: </span>
                            <a href="<?= SITE_URL ?>/<?= $existing_proof ?>" target="_blank" class="small">View existing file</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Section for Unemployed -->
            <div id="unemployedSection" style="display: none;">
                <hr>
                <h5>Unemployment Details</h5>
                <div class="mb-3">
                    <label class="form-label">1. How long have you been actively seeking employment since graduation?</label>
                    <select name="seeking_time" class="form-select">
                        <option value="">Select</option>
                        <option value="Less than 3 months">Less than 3 months</option>
                        <option value="3 to 6 months">3 to 6 months</option>
                        <option value="6 to 12 months">6 to 12 months</option>
                        <option value="Over 1 year">Over 1 year</option>
                        <option value="Haven't actively started job searching">Haven't actively started job searching</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">2. Did you secure any job interviews during your job search?</label>
                    <select name="interviews" class="form-select">
                        <option value="">Select</option>
                        <option value="Yes, multiple interviews">Yes, multiple interviews</option>
                        <option value="Yes, a few interviews">Yes, a few interviews</option>
                        <option value="No, despite sending out many applications">No, despite sending out many applications</option>
                        <option value="No, I haven't applied for jobs yet">No, I haven't applied for jobs yet</option>
                        <option value="Prefer not to say">Prefer not to say</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">3. Have you received feedback from employers after interviews or job applications?</label>
                    <select name="employer_feedback" class="form-select">
                        <option value="">Select</option>
                        <option value="Yes, constructive feedback">Yes, constructive feedback</option>
                        <option value="Yes, but no specific feedback">Yes, but no specific feedback</option>
                        <option value="No, I haven't received any feedback">No, I haven't received any feedback</option>
                        <option value="I haven't had any interviews or job applications">I haven't had any interviews or job applications</option>
                        <option value="Prefer not to say">Prefer not to say</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">4. Do you believe your geographic location plays a significant role in your unemployability?</label>
                    <select name="location_factor" class="form-select">
                        <option value="">Select</option>
                        <option value="Yes, location is a major factor">Yes, location is a major factor</option>
                        <option value="Location is a minor factor">Location is a minor factor</option>
                        <option value="No, location is not a factor">No, location is not a factor</option>
                        <option value="I'm willing to relocate">I'm willing to relocate</option>
                        <option value="Prefer not to say">Prefer not to say</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">5. Which of the following do you believe has had the most significant impact on your unemployability?</label>
                    <select name="impact_factor" class="form-select">
                        <option value="">Select</option>
                        <option value="Lack of relevant work experience">Lack of relevant work experience</option>
                        <option value="Insufficient qualifications or education">Insufficient qualifications or education</option>
                        <option value="Economic downturn">Economic downturn</option>
                        <option value="Inadequate networking opportunities">Inadequate networking opportunities</option>
                        <option value="Other">Other (please specify)</option>
                    </select>
                </div>
                <div class="mb-3" id="other_impact_div" style="display: none;">
                    <label class="form-label">Other Impact (please specify)</label>
                    <input type="text" name="other_impact" class="form-control">
                </div>
                <div class="mb-3">
                    <label class="form-label">6. Are you actively participating in professional development or skill-building activities during your job search?</label>
                    <select name="skill_development" class="form-select">
                        <option value="">Select</option>
                        <option value="Yes, I'm continually improving my skills">Yes, I'm continually improving my skills</option>
                        <option value="I've taken some courses or certifications">I've taken some courses or certifications</option>
                        <option value="No, I haven't focused on skill development">No, I haven't focused on skill development</option>
                        <option value="Prefer not to say">Prefer not to say</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">7. How would you describe your overall job search experience?</label>
                    <select name="job_search_experience" class="form-select">
                        <option value="">Select</option>
                        <option value="Frustrating and discouraging">Frustrating and discouraging</option>
                        <option value="Challenging, but I remain optimistic">Challenging, but I remain optimistic</option>
                        <option value="Smooth and successful">Smooth and successful</option>
                        <option value="Not applicable (e.g., not actively job searching)">Not applicable (e.g., not actively job searching)</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">8. Are you utilizing the career services or alumni resources provided by your educational institution during your job search?</label>
                    <select name="career_resources" class="form-select">
                        <option value="">Select</option>
                        <option value="Yes, regularly">Yes, regularly</option>
                        <option value="Occasionally">Occasionally</option>
                        <option value="No, I haven't used these resources">No, I haven't used these resources</option>
                        <option value="My institution doesn't offer such services">My institution doesn't offer such services</option>
                        <option value="Prefer not to say">Prefer not to say</option>
                    </select>
                </div>
            </div>

            <!-- Section for Employed -->
            <div id="employedSection" style="display: none;">
                <hr>
                <h5>Employment Details</h5>
                <div class="mb-3">
                    <label class="form-label">Job Category</label>
                    <select name="job_category" class="form-select">
                        <option value="">Select</option>
                        <option value="Administrative and Office Support">Administrative and Office Support</option>
                        <option value="Agriculture and Farming">Agriculture and Farming</option>
                        <option value="Arts, Entertainment, and Media">Arts, Entertainment, and Media</option>
                        <option value="Banking and Financial Services">Banking and Financial Services</option>
                        <option value="Consulting">Consulting</option>
                        <option value="Customer Service and Call Center">Customer Service and Call Center</option>
                        <option value="Education and Training">Education and Training</option>
                        <option value="Engineering and Architecture">Engineering and Architecture</option>
                        <option value="Environmental and Sustainability">Environmental and Sustainability</option>
                        <option value="Fitness and Recreation">Fitness and Recreation</option>
                        <option value="Food and Beverage">Food and Beverage</option>
                        <option value="Government and Public Administration">Government and Public Administration</option>
                        <option value="Healthcare and Medical">Healthcare and Medical</option>
                        <option value="Hospitality and Tourism">Hospitality and Tourism</option>
                        <option value="Human Resources">Human Resources</option>
                        <option value="Information Technology">Information Technology</option>
                        <option value="Legal">Legal</option>
                        <option value="Manufacturing and Production">Manufacturing and Production</option>
                        <option value="Marketing, Advertising, and PR">Marketing, Advertising, and PR</option>
                        <option value="Non-profit and Social Services">Non-profit and Social Services</option>
                        <option value="Real Estate and Property Management">Real Estate and Property Management</option>
                        <option value="Retail and Sales">Retail and Sales</option>
                        <option value="Science and Research">Science and Research</option>
                        <option value="Skilled Trades and Blue-Collar">Skilled Trades and Blue-Collar</option>
                        <option value="Transportation and Logistics">Transportation and Logistics</option>
                        <option value="Other">Other (please specify)</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Job Title/Description</label>
                    <input type="text" name="job_title_desc" class="form-control">
                </div>
                <div class="mb-3">
                    <label class="form-label">Employer Name</label>
                    <input type="text" name="employer_name" class="form-control">
                </div>
                <div class="mb-3">
                    <label class="form-label">Employment ID (Optional)</label>
                    <input type="text" name="employment_id" class="form-control" placeholder="Your employee/ID number">
                </div>
                <div class="mb-3">
                    <label class="form-label">Date of Employment</label>
                    <input type="date" name="employment_date" class="form-control">
                </div>
                <div class="mb-3">
                    <label class="form-label">Region</label>
                    <input type="text" name="region" class="form-control" placeholder="e.g., Western Visayas">
                </div>
                <div class="mb-3">
                    <label class="form-label">City/Municipality</label>
                    <input type="text" name="city" class="form-control" placeholder="e.g., Sagay City">
                </div>
                <div class="mb-3">
                    <label class="form-label">How long did it take you to secure your first job after graduation?</label>
                    <select name="time_to_first_job" class="form-select">
                        <option value="">Select</option>
                        <option value="Less than 3 months">Less than 3 months</option>
                        <option value="3 to 6 months">3 to 6 months</option>
                        <option value="6 to 12 months">6 to 12 months</option>
                        <option value="Over 1 year">Over 1 year</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">How helpful were internships in developing your practical skills and enhancing your employability?</label>
                    <select name="internship_helpfulness" class="form-select">
                        <option value="">Select</option>
                        <option value="Very helpful">Very helpful</option>
                        <option value="Somewhat helpful">Somewhat helpful</option>
                        <option value="Neutral">Neutral</option>
                        <option value="Not very helpful">Not very helpful</option>
                        <option value="Not helpful at all">Not helpful at all</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Did you participate in any networking events, job fairs, or alumni associations to enhance your job search efforts?</label>
                    <select name="networking_participation" class="form-select">
                        <option value="">Select</option>
                        <option value="Yes, regularly">Yes, regularly</option>
                        <option value="Occasionally">Occasionally</option>
                        <option value="No, I didn't participate in networking events">No, I didn't participate in networking events</option>
                        <option value="Prefer not to say">Prefer not to say</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">In your opinion, what skills or qualifications were most critical in securing your current job?</label>
                    <input type="text" name="skill1" class="form-control mb-2" placeholder="Skill 1">
                    <input type="text" name="skill2" class="form-control mb-2" placeholder="Skill 2">
                    <input type="text" name="skill3" class="form-control mb-2" placeholder="Skill 3">
                </div>
            </div>

            <!-- Section for Self-Employed -->
            <div id="selfEmployedSection" style="display: none;">
                <hr>
                <h5>Self-Employment Details</h5>
                <div class="mb-3">
                    <label class="form-label">Business Name</label>
                    <input type="text" name="business_name" class="form-control">
                </div>
                <div class="mb-3">
                    <label class="form-label">Business Type</label>
                    <input type="text" name="business_type" class="form-control">
                </div>
                <div class="mb-3">
                    <label class="form-label">Industry</label>
                    <input type="text" name="business_industry" class="form-control">
                </div>
                <div class="mb-3">
                    <label class="form-label">Number of Employees</label>
                    <select name="business_size" class="form-select">
                        <option value="">Select</option>
                        <option value="Solo (1)">Solo (1)</option>
                        <option value="Micro (2-10)">Micro (2-10)</option>
                        <option value="Small (11-50)">Small (11-50)</option>
                        <option value="Medium (51-250)">Medium (51-250)</option>
                        <option value="Large (250+)">Large (250+)</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Start Date</label>
                    <input type="date" name="business_start_date" class="form-control">
                </div>
            </div>

            <button type="submit" class="btn btn-primary mt-3">Submit Survey</button>
        </form>
    </div>
</div>

<script>
    function toggleSections() {
        const empRadios = document.querySelectorAll('input[name="is_employed"]');
        let selected = null;
        for (let r of empRadios) if (r.checked) selected = r.value;

        const unemployedDiv = document.getElementById('unemployedSection');
        const employedDiv = document.getElementById('employedSection');
        const selfDiv = document.getElementById('selfEmployedSection');
        const proofDiv = document.getElementById('proofSection');

        if (selected === '0') {
            unemployedDiv.style.display = 'block';
            employedDiv.style.display = 'none';
            selfDiv.style.display = 'none';
            proofDiv.style.display = 'none'; // Hide proof section for unemployed
        } else if (selected === '1') {
            unemployedDiv.style.display = 'none';
            employedDiv.style.display = 'block';
            selfDiv.style.display = 'none';
            proofDiv.style.display = 'block'; // Show proof section for employed
        } else if (selected === '2') {
            unemployedDiv.style.display = 'none';
            employedDiv.style.display = 'none';
            selfDiv.style.display = 'block';
            proofDiv.style.display = 'block'; // Show proof section for self-employed
        } else {
            unemployedDiv.style.display = 'none';
            employedDiv.style.display = 'none';
            selfDiv.style.display = 'none';
            proofDiv.style.display = 'none';
        }
    }

    // Show other impact textbox when "Other" is selected
    document.querySelector('select[name="impact_factor"]').addEventListener('change', function() {
        const otherDiv = document.getElementById('other_impact_div');
        otherDiv.style.display = this.value === 'Other' ? 'block' : 'none';
    });

    document.querySelectorAll('input[name="is_employed"]').forEach(r => r.addEventListener('change', toggleSections));
    toggleSections();
</script>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>