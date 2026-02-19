<?php
require_once __DIR__ . '/auth.php';
if (!isAdmin()) { redirect('../index.php'); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>USAT Alumni System - Admin</title>
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
            <?php $current = basename($_SERVER['PHP_SELF']); ?>
            <a class="nav-link <?= $current == 'index.php' ? 'active' : '' ?>" href="index.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            <a class="nav-link <?= $current == 'alumni_directory.php' ? 'active' : '' ?>" href="alumni_directory.php"><i class="fas fa-users"></i> Alumni Directory</a>
            <a class="nav-link <?= strpos($current, 'tracer_module') !== false ? 'active' : '' ?>" href="tracer_module.php"><i class="fas fa-chart-line"></i> Tracer Module</a>
            <a class="nav-link <?= $current == 'job_postings.php' ? 'active' : '' ?>" href="job_postings.php"><i class="fas fa-briefcase"></i> Job Postings</a>
            <a class="nav-link <?= $current == 'analytics.php' ? 'active' : '' ?>" href="analytics.php"><i class="fas fa-chart-pie"></i> Analytics</a>
            <a class="nav-link <?= $current == 'data_mining.php' ? 'active' : '' ?>" href="data_mining.php"><i class="fas fa-database"></i> Data Mining</a>
            <a class="nav-link <?= $current == 'reports.php' ? 'active' : '' ?>" href="reports.php"><i class="fas fa-file-alt"></i> Reports</a>
            <a class="nav-link <?= $current == 'announcements.php' ? 'active' : '' ?>" href="announcements.php"><i class="fas fa-bullhorn"></i> Announcements</a>
            <a class="nav-link <?= $current == 'applications.php' ? 'active' : '' ?>" href="applications.php"><i class="fas fa-file-signature"></i> Applications</a>
            <a class="nav-link <?= $current == 'settings.php' ? 'active' : '' ?>" href="settings.php"><i class="fas fa-cog"></i> Settings</a>
            <a class="nav-link" href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </nav>
    </aside>
    <main class="main-content">