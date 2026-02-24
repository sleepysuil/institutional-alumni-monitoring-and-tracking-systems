<aside class="sidebar">
    <div class="sidebar-logo">
        <img src="<?= SITE_URL ?>/assets/img/usat-logo.jpg" alt="USAT Logo">
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