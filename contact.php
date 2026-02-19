<?php
$success = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Here you would send email or save to database
    $success = "Thank you for your message. We'll get back to you soon.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>USAT Alumni System - Contact Us</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body style="background: #F3F4F6;">
    <div class="container py-5">
        <div class="text-center mb-5">
            <h1 class="display-5 fw-bold" style="color: #1E3A8A;">Get in Touch</h1>
            <p class="lead text-muted">We're here to help and answer any questions you might have.</p>
        </div>

        <div class="row g-4">
            <!-- Contact Form -->
            <div class="col-md-6">
                <div class="card p-4">
                    <h3 class="h4 mb-3" style="color: #1E3A8A;">Send us a Message</h3>
                    <?php if ($success): ?>
                        <div class="alert alert-success"><?= $success ?></div>
                    <?php endif; ?>
                    <form method="post">
                        <div class="mb-3">
                            <label for="name" class="form-label">Full Name</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label for="subject" class="form-label">Subject</label>
                            <input type="text" class="form-control" id="subject" name="subject" required>
                        </div>
                        <div class="mb-3">
                            <label for="message" class="form-label">Message</label>
                            <textarea class="form-control" id="message" name="message" rows="4" required></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">Send Message</button>
                    </form>
                </div>
            </div>

            <!-- Contact Info & Quick Help -->
            <div class="col-md-6">
                <div class="card p-4 mb-4">
                    <h3 class="h4 mb-3" style="color: #1E3A8A;">USAT College Sagay, Inc.</h3>
                    <div class="d-flex align-items-center mb-3">
                        <div class="contact-icon"><i class="fas fa-map-marker-alt fa-fw"></i></div>
                        <div>National Highway, Sagay City, Negros Occidental</div>
                    </div>
                    <div class="d-flex align-items-center mb-3">
                        <div class="contact-icon"><i class="fas fa-phone-alt fa-fw"></i></div>
                        <div>+63 (34) 123 4567</div>
                    </div>
                    <div class="d-flex align-items-center mb-3">
                        <div class="contact-icon"><i class="fas fa-envelope fa-fw"></i></div>
                        <div>alumni@usat.edu</div>
                    </div>
                    <div class="d-flex align-items-center">
                        <div class="contact-icon"><i class="fas fa-clock fa-fw"></i></div>
                        <div>Mon-Fri: 8:00 AM – 5:00 PM</div>
                    </div>
                </div>

                <div class="card p-4">
                    <h3 class="h4 mb-3" style="color: #1E3A8A;">Quick Help</h3>
                    <div class="quick-help-item">
                        <i class="fas fa-question-circle me-2" style="color: #8B5CF6;"></i>
                        <strong>How to update my profile?</strong><br>
                        <small class="text-muted">Login and go to "My Profile" in the alumni dashboard.</small>
                    </div>
                    <div class="quick-help-item">
                        <i class="fas fa-question-circle me-2" style="color: #8B5CF6;"></i>
                        <strong>Forgot password?</strong><br>
                        <small class="text-muted">Contact the admin to reset your password.</small>
                    </div>
                    <div class="quick-help-item">
                        <i class="fas fa-question-circle me-2" style="color: #8B5CF6;"></i>
                        <strong>Job posting inquiries?</strong><br>
                        <small class="text-muted">Email us at careers@usat.edu</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Meet Our Team -->
        <div class="mt-5">
            <h2 class="text-center mb-4" style="color: #1E3A8A;">Meet Our Team</h2>
            <p class="text-center text-muted mb-5">Dedicated professionals committed to making education accessible for everyone.</p>
            <div class="row g-4">
                <!-- Team Member 1 -->
                <div class="col-md-3">
                    <div class="card team-card text-center p-3">
                        <img src="assets/img/team/erwin-palomaria.jpg" alt="Erwin Palomaria" class="team-img rounded-circle mx-auto mb-3" onerror="this.onerror=null; this.src='https://via.placeholder.com/150';">
                        <h5>Erwin Palomaria</h5>
                        <p class="text-muted small">Project Manager</p>
                    </div>
                </div>
                <!-- Team Member 2 -->
                <div class="col-md-3">
                    <div class="card team-card text-center p-3">
                        <img src="assets/img/team/francisluis-paclibon.jpg" alt="Francis Luis Paclibon" class="team-img rounded-circle mx-auto mb-3" onerror="this.onerror=null; this.src='https://via.placeholder.com/150';">
                        <h5>Francis Luis Paclibon</h5>
                        <p class="text-muted small">Programmer/ UI/UX Designer</p>
                    </div>
                </div>
                <!-- Team Member 3 -->
                <div class="col-md-3">
                    <div class="card team-card text-center p-3">
                        <img src="assets/img/team/ericstine-gamao.jpg" alt="Ericstine Gamao" class="team-img rounded-circle mx-auto mb-3" onerror="this.onerror=null; this.src='https://via.placeholder.com/150';">
                        <h5>Ericstine Gamao</h5>
                        <p class="text-muted small">Analyst</p>
                    </div>
                </div>
                <!-- Team Member 4 -->
                <div class="col-md-3">
                    <div class="card team-card text-center p-3">
                        <img src="assets/img/team/christianjacob-sabete.jpg" alt="Christian Jacob Sabete" class="team-img rounded-circle mx-auto mb-3" onerror="this.onerror=null; this.src='https://via.placeholder.com/150';">
                        <h5>Christian Sabete</h5>
                        <p class="text-muted small">Tester</p>
                    </div>
                </div>
                <!-- Team Member 5 (New) -->
                <div class="col-md-3">
                    <div class="card team-card text-center p-3">
                        <img src="assets/img/team/yulbryan-varca.jpg" alt="Yul Bryan Varca" class="team-img rounded-circle mx-auto mb-3" onerror="this.onerror=null; this.src='https://via.placeholder.com/150';">
                        <h5>Yul Bryan Varca</h5>
                        <p class="text-muted small">Adviser, MIT</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="text-center mt-5">
            <a href="index.php" class="btn btn-outline-primary"><i class="fas fa-arrow-left me-2"></i>Back to Login</a>
        </div>
    </div>
</body>
</html>