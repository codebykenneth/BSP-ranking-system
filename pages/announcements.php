<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

if (isset($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM announcements WHERE id = ?");
    $stmt->execute([(int) $_GET['delete']]);
    header('Location: ' . base_url('pages/announcements.php?deleted=1'));
    exit;
}

$announcements = $pdo->query("SELECT * FROM announcements ORDER BY created_at DESC")->fetchAll();

$pageTitle   = 'Announcements';
$currentPage = 'announcements';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>
<main class="main-content">
    <div class="content-header content-header-row">
        <div>
            <h1>Announcements</h1>
            <p>Post updates for scouts to see on their portal.</p>
        </div>
        <a href="<?= base_url('pages/announcement_form.php') ?>" class="btn btn-primary">+ New Announcement</a>
    </div>

    <?php if (isset($_GET['saved'])): ?>
        <div class="alert alert-success">Announcement posted.</div>
    <?php elseif (isset($_GET['deleted'])): ?>
        <div class="alert alert-success">Announcement deleted.</div>
    <?php endif; ?>

    <section class="panel">
        <?php if (empty($announcements)): ?>
            <p class="empty-state">No announcements yet.</p>
        <?php else: ?>
            <div class="announcement-list">
                <?php foreach ($announcements as $a): ?>
                    <article class="announcement-item">
                        <div class="announcement-item-header">
                            <div>
                                <h3><?= e($a['title']) ?></h3>
                                <span class="announcement-meta">
                                    Posted by <?= e($a['posted_by']) ?> &middot; <?= e(date('M j, Y \a\t g:i A', strtotime($a['created_at']))) ?>
                                </span>
                            </div>
                            <a href="<?= base_url('pages/announcements.php?delete=' . $a['id']) ?>"
                               class="btn btn-small btn-danger"
                               onclick="return confirm('Delete this announcement?');">Delete</a>
                        </div>
                        <p class="announcement-message"><?= nl2br(e($a['message'])) ?></p>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</main>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
