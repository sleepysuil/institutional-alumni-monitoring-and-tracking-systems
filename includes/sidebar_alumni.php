<?php
/* Alumni sidebar. Included by alumni_header.php (one copy instead of two). */
$current = basename($_SERVER['PHP_SELF']);

// [link, icon, label, pages that should highlight this item]
$nav_items = [
    ['dashboard.php',         'fa-tachometer-alt', 'Dashboard',         ['dashboard.php']],
    ['profile.php',           'fa-id-card',        'My Profile',        ['profile.php']],
    ['newsfeed.php',          'fa-newspaper',      'Newsfeed',          ['newsfeed.php']],
    ['job_opportunities.php', 'fa-briefcase',      'Job Opportunities', ['job_opportunities.php', 'job_details.php', 'apply.php']],
    ['application_status.php','fa-file-signature', 'My Applications',   ['application_status.php']],
    ['tracer_survey.php',     'fa-poll',           'Tracer Survey',     ['tracer_survey.php']],
];
?>
<aside class="sidebar" id="mainSidebar">
    <div class="sidebar-logo">
        <img src="<?= SITE_URL ?>/assets/img/usat-logo.jpg" alt="USAT Logo">
        <div style="color: #F6F6F2; opacity: 0.8; text-align: center; font-size: 0.8rem; margin-top: 0.5rem;">
            Institutional Alumni Monitoring<br>and Tracer System
        </div>
    </div>
    <nav class="nav flex-column" aria-label="Main navigation">
        <?php foreach ($nav_items as [$href, $icon, $label, $pages]):
            $is_active = in_array($current, $pages, true); ?>
        <a class="nav-link <?= $is_active ? 'active' : '' ?>" href="<?= $href ?>"<?= $is_active ? ' aria-current="page"' : '' ?>>
            <i class="fas <?= $icon ?>"></i> <?= $label ?>
        </a>
        <?php endforeach; ?>
        <a class="nav-link" href="../logout.php">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </nav>
</aside>