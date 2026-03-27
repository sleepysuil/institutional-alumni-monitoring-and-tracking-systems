<?php
require_once '../includes/alumni_header.php';

$filter = $_GET['filter'] ?? 'all';

$announcements = $pdo->query("SELECT 'announcement' as type, id, title, content as description, posted_date, NULL as company, posted_by FROM announcements ORDER BY posted_date DESC")->fetchAll();
$jobs = $pdo->query("SELECT 'job' as type, id, title, description, posted_date, company, NULL as posted_by FROM job_postings WHERE status='active' ORDER BY posted_date DESC")->fetchAll();

$feed = [];
if ($filter == 'all') {
    $feed = array_merge($announcements, $jobs);
} elseif ($filter == 'jobs') {
    $feed = $jobs;
} elseif ($filter == 'announcements') {
    $feed = $announcements;
}

usort($feed, fn($a, $b) => strtotime($b['posted_date']) - strtotime($a['posted_date']));
$feed = array_slice($feed, 0, 20);
?>
<div class="page-header">
    <h1>Newsfeed</h1>
</div>

<ul class="nav nav-tabs flex-wrap">
    <li class="nav-item">
        <a class="nav-link <?= $filter == 'all' ? 'active' : '' ?>" href="?filter=all">All Updates</a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= $filter == 'jobs' ? 'active' : '' ?>" href="?filter=jobs">Job Postings</a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= $filter == 'announcements' ? 'active' : '' ?>" href="?filter=announcements">Announcements</a>
    </li>
</ul>

<div class="row">
    <div class="col-lg-8">
        <?php if (empty($feed)): ?>
            <div class="alert alert-info">No updates found.</div>
        <?php endif; ?>

        <?php foreach ($feed as $item): ?>
        <div class="card mb-3 newsfeed-item <?= $item['type'] ?>">
            <div class="card-body">
                <div class="d-flex flex-column flex-sm-row">
                    <div class="flex-shrink-0 mb-2 mb-sm-0">
                        <?php if ($item['type'] == 'job'): ?>
                            <i class="fas fa-briefcase fa-2x text-primary me-3"></i>
                        <?php else: ?>
                            <i class="fas fa-bullhorn fa-2x text-warning me-3"></i>
                        <?php endif; ?>
                    </div>
                    <div class="flex-grow-1">
                        <h5 class="card-title">
                            <?php if ($item['type'] == 'job'): ?>
                                <a href="job_details.php?id=<?= $item['id'] ?>" class="text-decoration-none">
                                    <?= htmlspecialchars($item['title']) ?> at <?= htmlspecialchars($item['company']) ?>
                                </a>
                            <?php else: ?>
                                <?= htmlspecialchars($item['title']) ?>
                            <?php endif; ?>
                        </h5>
                        <p class="card-text"><?= nl2br(htmlspecialchars(substr($item['description'], 0, 200))) ?>...</p>
                        <p class="small text-muted">
                            <i class="far fa-clock me-1"></i> <?= timeAgo($item['posted_date']) ?>
                        </p>
                        <?php if ($item['type'] == 'job'): ?>
                            <a href="job_details.php?id=<?= $item['id'] ?>" class="btn btn-sm btn-primary">View Job</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">Quick Links</div>
            <ul class="list-group list-group-flush">
                <li class="list-group-item"><a href="job_opportunities.php">Browse All Jobs</a></li>
                <li class="list-group-item"><a href="profile.php">Update Profile</a></li>
                <li class="list-group-item"><a href="tracer_survey.php">Take Tracer Survey</a></li>
            </ul>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>