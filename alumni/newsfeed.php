<?php
require_once '../includes/alumni_header.php';
$announcements = $pdo->query("SELECT 'announcement' as type, id, title, content as description, posted_date, NULL as company FROM announcements ORDER BY posted_date DESC LIMIT 5")->fetchAll();
$jobs = $pdo->query("SELECT 'job' as type, id, title, description, posted_date, company FROM job_postings WHERE status='active' ORDER BY posted_date DESC LIMIT 5")->fetchAll();
$feed = array_merge($announcements, $jobs);
usort($feed, fn($a,$b) => strtotime($b['posted_date']) - strtotime($a['posted_date']));
?>
<div class="page-header">
    <h1>Newsfeed</h1>
</div>

<div class="row g-4">
    <div class="col-md-8">
        <?php foreach ($feed as $item): ?>
        <div class="card mb-3 newsfeed-item <?= $item['type'] ?>">
            <div class="card-body">
                <h5 class="card-title">
                    <?php if ($item['type'] == 'job'): ?>
                        <i class="fas fa-briefcase text-primary me-2"></i> Job: <?= htmlspecialchars($item['title']) ?> at <?= htmlspecialchars($item['company']) ?>
                    <?php else: ?>
                        <i class="fas fa-bullhorn text-warning me-2"></i> Announcement: <?= htmlspecialchars($item['title']) ?>
                    <?php endif; ?>
                </h5>
                <p class="card-text"><?= htmlspecialchars(substr($item['description'],0,200)) ?>...</p>
                <p class="small text-muted">Posted <?= timeAgo($item['posted_date']) ?></p>
                <?php if ($item['type'] == 'job'): ?>
                    <a href="job_details.php?id=<?= $item['id'] ?>" class="btn btn-sm btn-primary">View Job</a>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">Filters</div>
            <div class="list-group list-group-flush">
                <a href="?type=all" class="list-group-item list-group-item-action">All Updates</a>
                <a href="?type=jobs" class="list-group-item list-group-item-action">Job Postings</a>
                <a href="?type=announcements" class="list-group-item list-group-item-action">Announcements</a>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>