<?php
$success = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Simulate email sending –it can replace with actual mail function or PHPMailer
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
<body style="background: var(--light);">
    <div class="container py-5">
        <!-- Logo -->
        <div class="text-center mb-4">
            <img src="assets/img/usat-logo.jpg" alt="USAT Logo" style="max-width: 150px;">
        </div>
        <div class="text-center mb-5">
            <h1 class="display-5 fw-bold" style="color: var(--primary);">Get in Touch</h1>
            <p class="lead text-muted">We're here to help and answer any questions you might have.</p>
        </div>

        <div class="row g-4">
            <!-- Contact Form -->
            <div class="col-md-6">
                <div class="card p-4 shadow-sm">
                    <h3 class="h4 mb-3" style="color: var(--primary);">Send us a Message</h3>
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
                <div class="card p-4 mb-4 shadow-sm">
                    <h3 class="h4 mb-3" style="color: var(--primary);">USAT College Sagay, Inc.</h3>
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

                <!-- Google Map Embed -->
                <div class="card p-4 mb-4 shadow-sm">
                    <h3 class="h4 mb-3" style="color: var(--primary);">Our Location</h3>
                    <div class="ratio ratio-16x9">
                        <iframe src="https://www.google.com/maps/embed?pb=!1m17!1m12!1m3!1d3870.123456789!2d123.418095!3d10.898405!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m2!1m1!2zMTDCsDUzJzU0LjMiTiAxMjPCsDI1JzA1LjEiRQ!5e0!3m2!1sen!2sph!4v1645567890123!5m2!1sen!2sph" 
                                width="100%" height="250" style="border:0; border-radius: 0.5rem;" allowfullscreen="" loading="lazy"></iframe>
                    </div>
                    <p class="mt-2 small text-muted text-center">
                        <a href="https://maps.app.goo.gl/NaKRLqXm8TjLXid47" target="_blank" class="text-decoration-none">View on Google Maps</a>
                    </p>
                </div>

                <div class="card p-4 shadow-sm">
                    <h3 class="h4 mb-3" style="color: var(--primary);">Quick Help</h3>
                    <div class="quick-help-item">
                        <i class="fas fa-question-circle me-2" style="color: var(--primary-light);"></i>
                        <strong>How to update my profile?</strong><br>
                        <small class="text-muted">Login and go to "My Profile" in the alumni dashboard.</small>
                    </div>
                    <div class="quick-help-item">
                        <i class="fas fa-question-circle me-2" style="color: var(--primary-light);"></i>
                        <strong>Forgot password?</strong><br>
                        <small class="text-muted">Contact the admin to reset your password.</small>
                    </div>
                    <div class="quick-help-item">
                        <i class="fas fa-question-circle me-2" style="color: var(--primary-light);"></i>
                        <strong>Job posting inquiries?</strong><br>
                        <small class="text-muted">Email us at careers@usat.edu</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Meet Our Team - Enhanced Section -->
        <div class="mt-5">
            <h2 class="text-center mb-4" style="color: var(--primary);">Meet Our Team</h2>
            <p class="text-center text-muted mb-5">Dedicated professionals committed to making education accessible for everyone.</p>
            <div class="row g-4 justify-content-center">
                <!-- Team Member 1 -->
                <div class="col-lg-2 col-md-4 col-6">
                    <div class="card team-card text-center p-3 h-100 shadow-sm border-0">
                        <div class="position-relative mx-auto" style="width: 120px; height: 120px;">
                            <img src="assets/img/team/erwin-palomaria.jpg" alt="Erwin Palomaria" class="team-img rounded-circle w-100 h-100 object-fit-cover border border-3" style="border-color: var(--primary-light) !important;" onerror="this.onerror=null; this.src='https://via.placeholder.com/150';">
                        </div>
                        <h5 class="mt-3 mb-1">Erwin Palomaria</h5>
                        <p class="text-muted small">Project Manager</p>
                        <div class="mt-2">
                            <a href="https://www.facebook.com/erwin.palomaria.9" target="_blank" rel="noopener" class="text-secondary me-2"><i class="fab fa-facebook"></i></a>
                            <a href="mailto:erwin.palomaria@usat.edu" class="text-secondary"><i class="fas fa-envelope"></i></a>
                        </div>
                    </div>
                </div>
                <!-- Team Member 2 -->
                <div class="col-lg-2 col-md-4 col-6">
                    <div class="card team-card text-center p-3 h-100 shadow-sm border-0">
                        <div class="position-relative mx-auto" style="width: 120px; height: 120px;">
                            <img src="assets/img/team/francisluis-paclibon.jpg" alt="Francis Luis Paclibon" class="team-img rounded-circle w-100 h-100 object-fit-cover border border-3" style="border-color: var(--primary-light) !important;" onerror="this.onerror=null; this.src='https://via.placeholder.com/150';">
                        </div>
                        <h5 class="mt-3 mb-1">Francis Luis Paclibon</h5>
                        <p class="text-muted small">Programmer/UI/UX</p>
                        <div class="mt-2">
                            <a href="https://www.facebook.com/franc1sssssssss" target="_blank" rel="noopener" class="text-secondary me-2"><i class="fab fa-facebook"></i></a>
                            <a href="mailto:francisluis129@gmail.com" class="text-secondary"><i class="fas fa-envelope"></i></a>
                        </div>
                    </div>
                </div>
                <!-- Team Member 3 -->
                <div class="col-lg-2 col-md-4 col-6">
                    <div class="card team-card text-center p-3 h-100 shadow-sm border-0">
                        <div class="position-relative mx-auto" style="width: 120px; height: 120px;">
                            <img src="assets/img/team/ericstine-gamao.jpg" alt="Ericstine Gamao" class="team-img rounded-circle w-100 h-100 object-fit-cover border border-3" style="border-color: var(--primary-light) !important;" onerror="this.onerror=null; this.src='https://via.placeholder.com/150';">
                        </div>
                        <h5 class="mt-3 mb-1">Ericstine Gamao</h5>
                        <p class="text-muted small">Analyst</p>
                        <div class="mt-2">
                            <a href="https://www.facebook.com/wreckstain" target="_blank" rel="noopener" class="text-secondary me-2"><i class="fab fa-facebook"></i></a>
                            <a href="mailto:ericstine.gamao@usat.edu" class="text-secondary"><i class="fas fa-envelope"></i></a>
                        </div>
                    </div>
                </div>
                <!-- Team Member 4 -->
                <div class="col-lg-2 col-md-4 col-6">
                    <div class="card team-card text-center p-3 h-100 shadow-sm border-0">
                        <div class="position-relative mx-auto" style="width: 120px; height: 120px;">
                            <img src="assets/img/team/christianjacob-sabete.jpg" alt="Christian Jacob Sabete" class="team-img rounded-circle w-100 h-100 object-fit-cover border border-3" style="border-color: var(--primary-light) !important;" onerror="this.onerror=null; this.src='https://via.placeholder.com/150';">
                        </div>
                        <h5 class="mt-3 mb-1">Christian Sabete</h5>
                        <p class="text-muted small">Tester</p>
                        <div class="mt-2">
                            <a href="https://www.facebook.com/christianjacob.sabete.902" target="_blank" rel="noopener" class="text-secondary me-2"><i class="fab fa-facebook"></i></a>
                            <a href="mailto:christian.sabete@usat.edu" class="text-secondary"><i class="fas fa-envelope"></i></a>
                        </div>
                    </div>
                </div>
                <!-- Team Member 5 (Adviser) -->
                <div class="col-lg-2 col-md-4 col-6">
                    <div class="card team-card text-center p-3 h-100 shadow-sm border-0">
                        <div class="position-relative mx-auto" style="width: 120px; height: 120px;">
                            <img src="assets/img/team/yulbryan-varca.jpg" alt="Yul Bryan Varca" class="team-img rounded-circle w-100 h-100 object-fit-cover border border-3" style="border-color: var(--primary) !important;" onerror="this.onerror=null; this.src='https://via.placeholder.com/150';">
                        </div>
                        <h5 class="mt-3 mb-1">Yul Bryan Varca</h5>
                        <p class="text-muted small">Adviser, MIT</p>
                        <div class="mt-2">
                            <a href="https://facebook.com/yulbryan.varca" target="_blank" rel="noopener" class="text-secondary me-2"><i class="fab fa-facebook"></i></a>
                            <a href="mailto:yulbryan.varca@usat.edu" class="text-secondary"><i class="fas fa-envelope"></i></a>
                        </div>
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