<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$eventId = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM events WHERE id = ?");
$stmt->execute([$eventId]);
$event = $stmt->fetch();

if (!$event) {
    header('Location: ' . base_url('pages/events.php'));
    exit;
}

$errors = [];
$title     = $event['title'];
$eventDate = $event['event_date'];
$callTime  = $event['call_time'] ?? '';
$points    = (int) $event['points_value'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title     = trim($_POST['title'] ?? '');
    $eventDate = $_POST['event_date'] ?? $eventDate;
    $callTime  = trim($_POST['call_time'] ?? '');
    $points    = (int) ($_POST['points'] ?? 0);

    if ($title === '') $errors[] = 'Event name is required.';
    if (empty($eventDate)) $errors[] = 'Date is required.';
    if ($points < 0) $errors[] = 'Points can\'t be negative.';

    if (empty($errors)) {
        $stmt = $pdo->prepare(
            "UPDATE events SET title = ?, event_date = ?, call_time = ?, points_value = ? WHERE id = ?"
        );
        $stmt->execute([$title, $eventDate, $callTime !== '' ? $callTime : null, $points, $eventId]);
        header('Location: ' . base_url('pages/events.php?updated=1'));
        exit;
    }
}

$pageTitle   = 'Edit Event';
$currentPage = 'events';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>
<main class="main-content">
    <div class="content-header">
        <h1>Edit Event</h1>
        <p>Update the name, date, call time, or points for this event.</p>
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
            <input type="hidden" name="id" value="<?= (int) $eventId ?>">

            <label for="title">Event Name</label>
            <input type="text" id="title" name="title" value="<?= e($title) ?>">

            <label for="event_date">Date</label>
            <input type="date" id="event_date" name="event_date" value="<?= e($eventDate) ?>">

            <label for="call_time">Call Time <span style="font-weight:400;color:var(--ink-soft);">(optional &mdash; time scouts are expected to be present by, used for scout self check-in)</span></label>
            <input type="time" id="call_time" name="call_time" value="<?= e($callTime) ?>">

            <label for="points">Points for Attendance</label>
            <input type="number" id="points" name="points" min="0" step="1" value="<?= e((string) $points) ?>">
            <p style="font-size:12px;color:var(--ink-soft);margin-top:-6px;">
                Note: changing this only affects attendance confirmed <em>after</em> you save &mdash; scouts already awarded points for this event won't be retroactively adjusted.
            </p>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Save Changes</button>
                <a href="<?= base_url('pages/events.php') ?>" class="btn btn-ghost">Cancel</a>
            </div>
        </form>
    </section>
</main>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
