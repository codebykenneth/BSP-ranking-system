<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$errors = [];
$title = '';
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title   = trim($_POST['title'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if ($title === '') $errors[] = 'Title is required.';
    if ($message === '') $errors[] = 'Message is required.';

    if (empty($errors)) {
        $stmt = $pdo->prepare("INSERT INTO announcements (title, message, posted_by) VALUES (?, ?, ?)");
        $stmt->execute([$title, $message, $_SESSION['admin_username'] ?? 'admin']);
        header('Location: ' . base_url('pages/announcements.php?saved=1'));
        exit;
    }
}

$pageTitle   = 'New Announcement';
$currentPage = 'announcements';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>
<main class="main-content">
    <div class="content-header">
        <h1>New Announcement</h1>
        <p>This will appear on every scout's portal as soon as you post it.</p>
    </div>

    <section class="panel panel-narrow">
        <?php if (!empty($errors)): ?>
            <div class="alert alert-error">
                <ul>
                    <?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST" class="stacked-form">
            <label for="title">Title</label>
            <input type="text" id="title" name="title" value="<?= e($title) ?>" required placeholder="e.g. Camporee Reminder">

            <label for="message">Message</label>
            <textarea id="message" name="message" rows="6" required placeholder="Write your announcement..."><?= e($message) ?></textarea>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Post Announcement</button>
                <a href="<?= base_url('pages/announcements.php') ?>" class="btn btn-ghost">Cancel</a>
            </div>
        </form>
    </section>
</main>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
