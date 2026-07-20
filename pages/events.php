<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

if (isset($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM events WHERE id = ?");
    $stmt->execute([(int) $_GET['delete']]);
    header('Location: ' . base_url('pages/events.php?deleted=1'));
    exit;
}

$events = $pdo->query(
    "SELECT e.*,
        (SELECT COUNT(*) FROM attendance a WHERE a.event_id = e.id AND a.status = 'Present') AS present_count,
        (SELECT COUNT(*) FROM attendance a WHERE a.event_id = e.id) AS marked_count,
        (SELECT COUNT(*) FROM scouts) AS total_scouts
     FROM events e
     ORDER BY e.event_date DESC"
)->fetchAll();

$pageTitle   = 'Events & Attendance';
$currentPage = 'events';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>
<main class="main-content">
    <div class="content-header content-header-row">
        <div>
            <h1>Events & Attendance</h1>
            <p>Create meetings and events, then take attendance for each one.</p>
        </div>
        <a href="<?= base_url('pages/event_form.php') ?>" class="btn btn-primary">+ Add Event</a>
    </div>

    <?php if (isset($_GET['saved'])): ?>
        <div class="alert alert-success">Event saved successfully.</div>
    <?php elseif (isset($_GET['deleted'])): ?>
        <div class="alert alert-success">Event deleted.</div>
    <?php elseif (isset($_GET['checked_in'])): ?>
        <div class="alert alert-success">Attendance saved for this event.</div>
    <?php endif; ?>

    <section class="panel">
        <?php if (empty($events)): ?>
            <p class="empty-state">No events yet. Add one to start taking attendance.</p>
        <?php else: ?>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Event</th>
                        <th>Attendance Taken</th>
                        <th>Present</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($events as $ev): ?>
                        <tr>
                            <td><?= e(date('M j, Y', strtotime($ev['event_date']))) ?></td>
                            <td><?= e($ev['title']) ?></td>
                            <td>
                                <?php if ((int) $ev['marked_count'] > 0): ?>
                                    <span class="badge">&#10003; <?= (int) $ev['marked_count'] ?> / <?= (int) $ev['total_scouts'] ?> marked</span>
                                <?php else: ?>
                                    <span class="badge" style="opacity:.6;">Not started</span>
                                <?php endif; ?>
                            </td>
                            <td><?= (int) $ev['present_count'] ?></td>
                            <td class="actions">
                                <a href="<?= base_url('pages/attendance_checkin.php?event_id=' . $ev['id']) ?>" class="btn btn-small btn-secondary">Check Attendance</a>
                                <a href="<?= base_url('pages/attendance_checkin.php?event_id=' . $ev['id'] . '&print=1') ?>" target="_blank" class="btn btn-small btn-secondary">Print Sheet</a>
                                <a href="<?= base_url('pages/events.php?delete=' . $ev['id']) ?>"
                                   class="btn btn-small btn-danger"
                                   onclick="return confirm('Delete this event? Its attendance records will also be removed.');">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </section>
</main>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
