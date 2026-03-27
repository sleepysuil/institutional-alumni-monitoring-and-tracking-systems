<aside class="sidebar">
    <div class="sidebar-logo">
        <img src="<?= SITE_URL ?>/assets/img/usat-logo.jpg" alt="USAT Logo">
    </div>
    <nav class="nav flex-column">
        <?php $current = basename($_SERVER['PHP_SELF']); ?>
        <a class="nav-link <?= $current == 'index.php' ? 'active' : '' ?>" href="index.php">
            <i class="fas fa-tachometer-alt"></i> Dashboard
        </a>
        <a class="nav-link <?= $current == 'alumni_directory.php' ? 'active' : '' ?>" href="alumni_directory.php">
            <i class="fas fa-users"></i> Alumni Directory
        </a>
        <a class="nav-link <?= strpos($current, 'tracer_module') !== false ? 'active' : '' ?>" href="tracer_module.php">
            <i class="fas fa-chart-line"></i> Tracer Module
        </a>
        <a class="nav-link <?= $current == 'job_postings.php' ? 'active' : '' ?>" href="job_postings.php">
            <i class="fas fa-briefcase"></i> Job Postings
        </a>
        <a class="nav-link <?= $current == 'analytics.php' ? 'active' : '' ?>" href="analytics.php">
            <i class="fas fa-chart-pie"></i> Analytics
        </a>
        <a class="nav-link <?= $current == 'data_mining.php' ? 'active' : '' ?>" href="data_mining.php">
            <i class="fas fa-database"></i> Data Mining
        </a>
        <a class="nav-link <?= $current == 'reports.php' ? 'active' : '' ?>" href="reports.php">
            <i class="fas fa-file-alt"></i> Reports
        </a>
        <a class="nav-link <?= $current == 'announcements.php' ? 'active' : '' ?>" href="announcements.php">
            <i class="fas fa-bullhorn"></i> Announcements
        </a>
        <a class="nav-link <?= $current == 'applications.php' ? 'active' : '' ?>" href="applications.php">
            <i class="fas fa-file-signature"></i> Applications
        </a>
        <a class="nav-link <?= $current == 'settings.php' ? 'active' : '' ?>" href="settings.php">
            <i class="fas fa-cog"></i> Settings
        </a>
        <a class="nav-link" href="../logout.php">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </nav>
</aside>