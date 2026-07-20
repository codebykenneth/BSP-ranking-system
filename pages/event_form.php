<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$errors = [];
$title = '';
$eventDate = date('Y-m-d');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title     = trim($_POST['title'] ?? '');
    $eventDate = $_POST['event_date'] ?? date('Y-m-d');

    if ($title === '') $errors[] = 'Event name is required.';
    if (empty($eventDate)) $errors[] = 'Date is required.';

    if (empty($errors)) {
        $checkinCode = strtoupper(substr(bin2hex(random_bytes(4)), 0, 6));
        $stmt = $pdo->prepare("INSERT INTO events (title, event_date, checkin_code) VALUES (?, ?, ?)");
        $stmt->execute([$title, $eventDate, $checkinCode]);
        header('Location: ' . base_url('pages/events.php?saved=1'));
        exit;
    }
}

$pageTitle   = 'Add Event';
$currentPage = 'events';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>
<main class="main-content">
    <div class="content-header">
        <h1>Add New Event</h1>
        <p>Create a meeting or event, then take attendance for it from the Events list.</p>
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
            <label for="title">Event Name</label>
            <input type="text" id="title" name="title" value="<?= e($title) ?>" required placeholder="e.g. Weekly Troop Meeting">

            <label for="event_date">Date</label>
            <input type="date" id="event_date" name="event_date" value="<?= e($eventDate) ?>" required>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Add Event</button>
                <a href="<?= base_url('pages/events.php') ?>" class="btn btn-ghost">Cancel</a>
            </div>
        </form>
    </section>
</main>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
