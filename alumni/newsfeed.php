<?php
require_once '../includes/alumni_header.php';
require_once '../includes/pagination.php';
$alumni_id = $_SESSION['alumni_id'];

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_post'])) {
    $content = trim(cleanInput($_POST['content'] ?? ''));
    if ($content === '') {
        $_SESSION['message'] = "Post cannot be empty.";
    } else {
        $pdo->prepare("INSERT INTO alumni_posts (alumni_id, content, posted_date) VALUES (?, ?, NOW())")->execute([$alumni_id, $content]);
        $_SESSION['message'] = "Your update was posted.";
    }
    redirect('newsfeed.php' . (isset($_GET['filter']) ? '?filter=' . $_GET['filter'] : ''));
}
if (isset($_GET['delete_post'])) {
    $pdo->prepare("DELETE FROM alumni_posts WHERE id = ? AND alumni_id = ?")->execute([$_GET['delete_post'], $alumni_id]);
    $_SESSION['message'] = "Post deleted.";
    redirect('newsfeed.php' . (isset($_GET['filter']) ? '?filter=' . $_GET['filter'] : ''));
}

$filter = $_GET['filter'] ?? 'all';
$page = getCurrentPage();
$limit = 8;

$announcements = $pdo->query("SELECT 'announcement' as type, id, title, content as description, posted_date, NULL as company, NULL as alumni_id, NULL as profile_pic FROM announcements ORDER BY posted_date DESC LIMIT 100")->fetchAll();
$jobs = $pdo->query("SELECT 'job' as type, id, title, description, posted_date, company, NULL as alumni_id, NULL as profile_pic FROM job_postings WHERE status='active' ORDER BY posted_date DESC LIMIT 100")->fetchAll();
$posts_raw = $pdo->query("
    SELECT p.id, p.content, p.posted_date, p.alumni_id, a.first_name, a.last_name, a.profile_pic
    FROM alumni_posts p JOIN alumni a ON p.alumni_id = a.id
    ORDER BY p.posted_date DESC LIMIT 100
")->fetchAll();
$posts = array_map(fn($p) => [
    'type' => 'post', 'id' => $p['id'],
    'title' => trim($p['first_name'] . ' ' . $p['last_name']),
    'description' => $p['content'], 'posted_date' => $p['posted_date'],
    'company' => null, 'alumni_id' => $p['alumni_id'], 'profile_pic' => $p['profile_pic']
], $posts_raw);

$feed_full = match($filter) {
    'jobs' => $jobs,
    'announcements' => $announcements,
    'posts' => $posts,
    default => array_merge($announcements, $jobs, $posts)
};
usort($feed_full, fn($a, $b) => strtotime($b['posted_date']) - strtotime($a['posted_date']));

$pager = new Paginator(count($feed_full), $page, $limit);
$feed = array_slice($feed_full, $pager->offset(), $pager->limit());

$message = $_SESSION['message'] ?? '';
unset($_SESSION['message']);

$me = $pdo->prepare("SELECT first_name, last_name, profile_pic FROM alumni WHERE id = ?");
$me->execute([$alumni_id]);
$me = $me->fetch();
?>
<div class="page-header">
    <h1>Newsfeed</h1>
</div>

<?php if ($message): ?><div class="alert alert-success"><?= htmlspecialchars($message) ?></div><?php endif; ?>

<div class="row">
    <div class="col-lg-8">
        <div class="card mb-3">
            <div class="card-body">
                <form method="post">
                    <div class="d-flex align-items-start gap-3">
                        <?php if (!empty($me['profile_pic']) && file_exists('../' . $me['profile_pic'])): ?>
                            <img src="<?= SITE_URL ?>/<?= $me['profile_pic'] ?>" alt="You" class="rounded-circle" style="width: 45px; height: 45px; object-fit: cover;">
                        <?php else: ?>
                            <i class="fas fa-user-circle fa-2x" style="color: var(--primary);"></i>
                        <?php endif; ?>
                        <div class="flex-grow-1">
                            <textarea name="content" class="form-control" rows="2" placeholder="Share an update with fellow alumni..." required></textarea>
                        </div>
                    </div>
                    <div class="text-end mt-2">
                        <button type="submit" name="add_post" class="btn btn-primary btn-sm"><i class="fas fa-paper-plane me-1"></i> Post</button>
                    </div>
                </form>
            </div>
        </div>

        <?php if (empty($feed)): ?>
            <div class="card"><div class="empty-state"><i class="fas fa-newspaper fa-2x mb-3"></i><p class="mb-0">No updates found.</p></div></div>
        <?php endif; ?>

        <?php foreach ($feed as $item): ?>
        <div class="card mb-3 newsfeed-item <?= $item['type'] ?>">
            <div class="card-body">
                <div class="d-flex flex-column flex-sm-row">
                    <div class="flex-shrink-0 mb-2 mb-sm-0">
                        <?php if ($item['type'] == 'job'): ?>
                            <i class="fas fa-briefcase fa-2x text-primary me-3"></i>
                        <?php elseif ($item['type'] == 'announcement'): ?>
                            <i class="fas fa-bullhorn fa-2x text-warning me-3"></i>
                        <?php else: ?>
                            <?php if (!empty($item['profile_pic']) && file_exists('../' . $item['profile_pic'])): ?>
                                <img src="<?= SITE_URL ?>/<?= $item['profile_pic'] ?>" alt="" class="rounded-circle me-3" style="width: 40px; height: 40px; object-fit: cover;">
                            <?php else: ?>
                                <i class="fas fa-user-circle fa-2x text-secondary me-3"></i>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                    <div class="flex-grow-1">
                        <h5 class="card-title">
                            <?php if ($item['type'] == 'job'): ?>
                                <a href="job_details.php?id=<?= $item['id'] ?>" class="text-decoration-none"><?= htmlspecialchars($item['title']) ?> at <?= htmlspecialchars($item['company']) ?></a>
                            <?php elseif ($item['type'] == 'announcement'): ?>
                                <?= htmlspecialchars($item['title']) ?>
                            <?php else: ?>
                                <?= htmlspecialchars($item['title']) ?> <span class="badge bg-secondary fw-normal">Alumni Post</span>
                            <?php endif; ?>
                        </h5>
                        <p class="card-text">
                            <?php if ($item['type'] == 'post'): ?>
                                <?= nl2br(htmlspecialchars($item['description'])) ?>
                            <?php else: ?>
                                <?= nl2br(htmlspecialchars(substr($item['description'], 0, 200))) ?><?= strlen($item['description']) > 200 ? '...' : '' ?>
                            <?php endif; ?>
                        </p>
                        <p class="small text-muted mb-2"><i class="far fa-clock me-1"></i> <?= timeAgo($item['posted_date']) ?></p>
                        <?php if ($item['type'] == 'job'): ?>
                            <a href="job_details.php?id=<?= $item['id'] ?>" class="btn btn-sm btn-primary">View Job</a>
                        <?php elseif ($item['type'] == 'post' && $item['alumni_id'] == $alumni_id): ?>
                            <a href="?delete_post=<?= $item['id'] ?><?= $filter != 'all' ? '&filter=' . $filter : '' ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this post?')"><i class="fas fa-trash"></i> Delete</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>

        <?= $pager->render() ?>
    </div>

    <div class="col-lg-4">
        <div class="card mb-3">
            <div class="card-header">Filter</div>
            <div class="list-group list-group-flush">
                <a class="list-group-item list-group-item-action <?= $filter == 'all' ? 'active' : '' ?>" href="?filter=all">All Updates</a>
                <a class="list-group-item list-group-item-action <?= $filter == 'posts' ? 'active' : '' ?>" href="?filter=posts">Alumni Posts</a>
                <a class="list-group-item list-group-item-action <?= $filter == 'jobs' ? 'active' : '' ?>" href="?filter=jobs">Job Postings</a>
                <a class="list-group-item list-group-item-action <?= $filter == 'announcements' ? 'active' : '' ?>" href="?filter=announcements">Announcements</a>
            </div>
        </div>
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