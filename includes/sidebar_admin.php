<?php
/*
 * Admin sidebar. Included by admin_header.php (one copy instead of two).
 * - the right menu item now stays highlighted on sub-pages (e.g. Employment Details -> Tracer Module)
 * - small badges show how many registrations / applications are waiting
 */
$current = basename($_SERVER['PHP_SELF']);

$nav_badges = ['settings.php' => 0, 'applications.php' => 0];
if (isset($pdo)) {
    try {
        $nav_badges['settings.php'] = (int)$pdo->query("SELECT COUNT(*) FROM alumni WHERE is_approved = 0 AND approval_status = 'pending'")->fetchColumn();
        $nav_badges['applications.php'] = (int)$pdo->query("SELECT COUNT(*) FROM applications WHERE status = 'pending'")->fetchColumn();
    } catch (Throwable $e) { /* columns/tables may not exist yet; no badges then */ }
}

// [link, icon, label, pages that should highlight this item]
$nav_items = [
    ['index.php',            'fa-tachometer-alt', 'Dashboard',        ['index.php']],
    ['alumni_directory.php', 'fa-users',          'Alumni Directory', ['alumni_directory.php', 'view_alumni.php']],
    ['tracer_module.php',    'fa-chart-line',     'Tracer Module',    ['tracer_module.php', 'employment_details.php', 'job_relevance.php']],
    ['job_postings.php',     'fa-briefcase',      'Job Postings',     ['job_postings.php', 'edit_job.php']],
    ['analytics.php',        'fa-chart-pie',      'Analytics',        ['analytics.php']],
    ['data_mining.php',      'fa-database',       'Data Mining',      ['data_mining.php', 'clustering.php', 'association.php', 'prediction.php', 'patterns.php', 'anomalies.php', 'trends.php', 'skills_gap.php']],
    ['reports.php',          'fa-file-alt',       'Reports',          ['reports.php']],
    ['announcements.php',    'fa-bullhorn',       'Announcements',    ['announcements.php']],
    ['send_messages.php',    'fa-comment-dots',   'Messages',         ['send_messages.php', 'message_history.php', 'message_templates.php', 'message_details.php']],
    ['applications.php',     'fa-file-signature', 'Applications',     ['applications.php']],
    ['settings.php',         'fa-cog',            'Settings',         ['settings.php']],
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
            $is_active = in_array($current, $pages, true);
            $badge = $nav_badges[$href] ?? 0; ?>
        <a class="nav-link <?= $is_active ? 'active' : '' ?>" href="<?= $href ?>"<?= $is_active ? ' aria-current="page"' : '' ?>>
            <i class="fas <?= $icon ?>"></i> <?= $label ?>
            <?php if ($badge > 0): ?><span class="nav-badge" title="<?= $badge ?> waiting"><?= $badge > 99 ? '99+' : $badge ?></span><?php endif; ?>
        </a>
        <?php endforeach; ?>
        <a class="nav-link" href="../logout.php">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </nav>
</aside>