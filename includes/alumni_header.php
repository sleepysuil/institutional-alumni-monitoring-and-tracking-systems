<?php
require_once __DIR__ . '/auth.php';
if (!isAlumni()) { redirect('../index.php'); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>USAT Alumni System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css">
</head>
<body>
    <aside class="sidebar">
        <div class="sidebar-logo">
            <img src="<?= SITE_URL ?>/assets/img/sagax-city-logo.png" alt="USAT">
        </div>
        <nav class="nav flex-column">
            <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : '' ?>" href="dashboard.php">
                <i class="fas fa-tachometer-alt"></i> Dashboard
            </a>
            <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'active' : '' ?>" href="profile.php">
                <i class="fas fa-id-card"></i> My Profile
            </a>
            <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'newsfeed.php' ? 'active' : '' ?>" href="newsfeed.php">
                <i class="fas fa-newspaper"></i> Newsfeed
            </a>
            <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'job_opportunities.php' ? 'active' : '' ?>" href="job_opportunities.php">
                <i class="fas fa-briefcase"></i> Job Opportunities
            </a>
            <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'application_status.php' ? 'active' : '' ?>" href="application_status.php">
                <i class="fas fa-file-signature"></i> My Applications
            </a>
            <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'tracer_survey.php' ? 'active' : '' ?>" href="tracer_survey.php">
                <i class="fas fa-poll"></i> Tracer Survey
            </a>
            <a class="nav-link" href="../logout.php">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </nav>
    </aside>
    <main class="main-content">