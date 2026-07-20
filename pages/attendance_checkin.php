<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$eventId = (int) ($_GET['event_id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM events WHERE id = ?");
$stmt->execute([$eventId]);
$event = $stmt->fetch();

if (!$event) {
    header('Location: ' . base_url('pages/events.php'));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $statuses = $_POST['status'] ?? []; // [scout_id => status]
    $stmt = $pdo->prepare(
        "INSERT INTO attendance (event_id, scout_id, status, submission_status, submitted_by_scout, submitted_at)
         VALUES (?, ?, ?, 'confirmed', FALSE, NOW())
         ON CONFLICT (event_id, scout_id) DO UPDATE SET
            status = EXCLUDED.status,
            submission_status = 'confirmed'"
    );
    foreach ($statuses as $scoutId => $status) {
        if (!in_array($status, ['Present', 'Late', 'Absent', 'Excused'], true)) continue;
        $stmt->execute([$eventId, (int) $scoutId, $status]);
    }
    header('Location: ' . base_url('pages/events.php?checked_in=1'));
    exit;
}

$scouts = $pdo->query("SELECT id, name, troop, photo FROM scouts ORDER BY name ASC")->fetchAll();

$existing = [];
$stmt = $pdo->prepare("SELECT scout_id, status, submission_status, excuse_reason FROM attendance WHERE event_id = ?");
$stmt->execute([$eventId]);
foreach ($stmt->fetchAll() as $row) {
    $existing[$row['scout_id']] = $row;
}

$printMode = isset($_GET['print']);

if ($printMode) {
    $pageTitle = 'Attendance Sheet';
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
    <meta charset="UTF-8">
    <title>Attendance Sheet · <?= e($event['title']) ?></title>
    <?php $cssPath = __DIR__ . '/../assets/css/style.css'; $cssVersion = file_exists($cssPath) ? filemtime($cssPath) : time(); ?>
    <link rel="stylesheet" href="<?= base_url('assets/css/style.css') ?>?v=<?= $cssVersion ?>">
    </head>
    <body class="print-body">
        <div class="print-sheet">
            <h1><?= e($event['title']) ?></h1>
            <p class="print-meta"><?= e(date('F j, Y', strtotime($event['event_date']))) ?> &middot; <?= count($scouts) ?> scouts</p>
            <table class="data-table">
                <thead>
                    <tr><th>#</th><th>Name</th><th>Troop</th><th>Present</th><th>Late</th><th>Absent</th><th>Excused</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($scouts as $i => $sc): ?>
                        <tr>
                            <td><?= $i + 1 ?></td>
                            <td><?= e($sc['name']) ?></td>
                            <td><?= e($sc['troop']) ?></td>
                            <td>&#9744;</td><td>&#9744;</td><td>&#9744;</td><td>&#9744;</td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <p class="print-disclaimer">Mark one box per scout, then transfer results into the system via Take Attendance.</p>
        </div>
        <script>window.onload = () => window.print();</script>
    </body>
    </html>
    <?php
    exit;
}

$pageTitle   = 'Take Attendance';
$currentPage = 'events';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>
<main class="main-content">
    <div class="content-header content-header-row">
        <div>
            <h1>Take Attendance</h1>
            <p><?= e($event['title']) ?> &middot; <?= e(date('F j, Y', strtotime($event['event_date']))) ?></p>
        </div>
        <a href="<?= base_url('pages/events.php') ?>" class="btn btn-ghost">&larr; Back to Events</a>
    </div>

    <section class="panel">
        <?php if (empty($scouts)): ?>
            <p class="empty-state">No scouts to mark attendance for yet.</p>
        <?php else: ?>
            <form method="POST">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Scout</th>
                            <th>Troop</th>
                            <th>Present</th>
                            <th>Late</th>
                            <th>Absent</th>
                            <th>Excused</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($scouts as $sc): ?>
                            <?php
                                $existingRow = $existing[$sc['id']] ?? null;
                                $current = $existingRow['status'] ?? 'Present';
                            ?>
                            <tr>
                                <td>
                                    <div class="name-cell">
                                        <?= scout_avatar($sc, 'sm') ?>
                                        <span>
                                            <?= e($sc['name']) ?>
                                            <?php if ($existingRow && $existingRow['submission_status'] === 'pending'): ?>
                                                <span class="status-pill status-pending" style="margin-left:6px;">Self check-in &mdash; pending</span>
                                            <?php endif; ?>
                                            <?php if ($existingRow && !empty($existingRow['excuse_reason'])): ?>
                                                <div style="font-size:12px;color:var(--ink-soft);">Reason: <?= e($existingRow['excuse_reason']) ?></div>
                                            <?php endif; ?>
                                        </span>
                                    </div>
                                </td>
                                <td><?= e($sc['troop']) ?></td>
                                <?php foreach (['Present', 'Late', 'Absent', 'Excused'] as $status): ?>
                                    <td class="status-cell">
                                        <input type="radio" name="status[<?= $sc['id'] ?>]" value="<?= $status ?>"
                                               <?= $current === $status ? 'checked' : '' ?>>
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Save Attendance</button>
                    <a href="<?= base_url('pages/events.php') ?>" class="btn btn-ghost">Cancel</a>
                </div>
            </form>
        <?php endif; ?>
    </section>
</main>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
