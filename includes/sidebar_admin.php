<aside class="sidebar">
    <div class="sidebar-logo">
        <img src="<?= SITE_URL ?>/assets/img/usat-logo.jpg" alt="USAT Logo">
        <div style="color: #F6F6F2; opacity: 0.8; text-align: center; font-size: 0.875rem; line-height: 1.3; margin-top: 0.5rem; padding: 0 0.5rem;">
            Institutional Alumni Monitoring<br>and Tracer System
        </div>
    </div>
    <nav class="nav flex-column" style="overflow-y: auto; max-height: calc(100vh - 200px);">
        <?php $current = basename($_SERVER['PHP_SELF']); ?>
        
        <!-- Dashboard -->
        <a class="nav-link <?= $current == 'index.php' ? 'active' : '' ?>" href="index.php">
            <i class="fas fa-tachometer-alt"></i> Dashboard
        </a>
        
        <!-- Alumni Directory -->
        <a class="nav-link <?= $current == 'alumni_directory.php' ? 'active' : '' ?>" href="alumni_directory.php">
            <i class="fas fa-users"></i> Alumni Directory
        </a>
        
        <!-- Tracer Module -->
        <a class="nav-link <?= strpos($current, 'tracer_module') !== false ? 'active' : '' ?>" href="tracer_module.php">
            <i class="fas fa-chart-line"></i> Tracer Module
        </a>
        
        <!-- Job Postings -->
        <a class="nav-link <?= $current == 'job_postings.php' ? 'active' : '' ?>" href="job_postings.php">
            <i class="fas fa-briefcase"></i> Job Postings
        </a>
        
        <!-- Analytics -->
        <a class="nav-link <?= $current == 'analytics.php' ? 'active' : '' ?>" href="analytics.php">
            <i class="fas fa-chart-pie"></i> Analytics
        </a>
        
        <!-- Data Mining -->
        <a class="nav-link <?= $current == 'data_mining.php' ? 'active' : '' ?>" href="data_mining.php">
            <i class="fas fa-database"></i> Data Mining
        </a>
        
        <!-- Reports -->
        <a class="nav-link <?= $current == 'reports.php' ? 'active' : '' ?>" href="reports.php">
            <i class="fas fa-file-alt"></i> Reports
        </a>
        
        <!-- Announcements -->
        <a class="nav-link <?= $current == 'announcements.php' ? 'active' : '' ?>" href="announcements.php">
            <i class="fas fa-bullhorn"></i> Announcements
        </a>
        
        <!-- MESSAGES MODULE - NEW -->
        <a class="nav-link <?= strpos($current, 'send_messages') !== false || strpos($current, 'message_history') !== false || strpos($current, 'message_templates') !== false ? 'active' : '' ?>" href="send_messages.php">
            <i class="fas fa-comment-dots"></i> Messages
        </a>
        
        <!-- Applications -->
        <a class="nav-link <?= $current == 'applications.php' ? 'active' : '' ?>" href="applications.php">
            <i class="fas fa-file-signature"></i> Applications
        </a>
        
        <!-- Settings -->
        <a class="nav-link <?= $current == 'settings.php' ? 'active' : '' ?>" href="settings.php">
            <i class="fas fa-cog"></i> Settings
        </a>
        
        <!-- Logout -->
        <a class="nav-link" href="../logout.php">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </nav>
</aside>