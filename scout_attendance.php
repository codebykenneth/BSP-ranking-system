<?php
require_once __DIR__ . '/includes/auth.php';
require_scout_login();
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$scoutId = (int) $_SESSION['scout_id'];

$stmt = $pdo->prepare("SELECT * FROM scouts WHERE id = ?");
$stmt->execute([$scoutId]);
$scout = $stmt->fetch();

if (!$scout) {
    session_destroy();
    header('Location: ' . base_url('scout_login.php'));
    exit;
}

$todaysEvents = get_todays_events($pdo);
foreach ($todaysEvents as &$ev) {
    $ev['my_attendance'] = get_scout_attendance_for_event($pdo, (int) $ev['id'], $scoutId);
}
unset($ev);

$lateEventId = (int) ($_GET['late'] ?? 0);

$flashError = $_SESSION['attendance_error'] ?? '';
unset($_SESSION['attendance_error']);
$flashSuccess = $_SESSION['attendance_success'] ?? '';
unset($_SESSION['attendance_success']);

$pageTitle = 'Take Attendance';
$cssPath = __DIR__ . '/assets/css/style.css';
$cssVersion = file_exists($cssPath) ? filemtime($cssPath) : time();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($pageTitle) ?> · BSP Ranking System</title>
<link rel="stylesheet" href="<?= base_url('assets/css/style.css') ?>?v=<?= $cssVersion ?>">
<style>
.event-checkin-card {
    background: var(--white);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    padding: 20px;
    margin-bottom: 16px;
    box-shadow: var(--shadow);
}
.event-checkin-card h3 { margin: 0 0 4px; }
.event-checkin-meta { color: var(--ink-soft); font-size: 13px; margin-bottom: 14px; }
.checkin-actions { display: flex; gap: 10px; flex-wrap: wrap; }
.excuse-box { margin-top: 14px; display: none; }
.excuse-box.active { display: block; }
.excuse-box textarea {
    width: 100%; min-height: 70px; font-family: inherit;
    padding: 10px; border: 1px solid var(--border); border-radius: 8px; margin-bottom: 10px;
}
.late-notice {
    background: #FDF3E3; border: 1px solid var(--brass-light);
    border-radius: 8px; padding: 10px 14px; margin-bottom: 12px; font-size: 13px; color: var(--brass);
}
.live-clock { font-family: 'IBM Plex Mono', monospace; font-size: 13px; color: var(--ink-soft); margin-bottom: 16px; }
</style>
</head>
<body>
<div class="scout-portal-shell">
    <header class="scout-portal-header">
        <div class="scout-portal-brand">
            <span class="brand-mark">BSP</span>
            <div>
                <strong>Take Attendance</strong>
                <small>Check in for today's activities</small>
            </div>
        </div>
        <a href="<?= base_url('scout_portal.php') ?>" class="btn btn-ghost">&larr; My Portal</a>
    </header>

    <main class="scout-portal-main">
        <?php if ($flashError): ?>
            <div class="alert alert-error"><?= e($flashError) ?></div>
        <?php endif; ?>
        <?php if ($flashSuccess): ?>
            <div class="alert alert-success"><?= e($flashSuccess) ?></div>
        <?php endif; ?>

        <p class="live-clock">Current time: <?= e(date('g:i A')) ?> &middot; <?= e(date('F j, Y')) ?></p>

        <?php if (empty($todaysEvents)): ?>
            <section class="panel">
                <p class="empty-state">No activities scheduled for today.</p>
            </section>
        <?php else: ?>
            <?php foreach ($todaysEvents as $ev): ?>
                <?php
                    $my = $ev['my_attendance'];
                    $callTimeLabel = !empty($ev['call_time']) ? date('g:i A', strtotime($ev['call_time'])) : null;
                    $showLateForm = !$my && $lateEventId === (int) $ev['id'];
                ?>
                <div class="event-checkin-card">
                    <h3><?= e($ev['title']) ?></h3>
                    <p class="event-checkin-meta">
                        <?= e(date('F j, Y', strtotime($ev['event_date']))) ?>
                        <?php if ($callTimeLabel): ?> &middot; Call time: <strong><?= e($callTimeLabel) ?></strong><?php endif; ?>
                    </p>

                    <?php if ($my): ?>
                        <p>
                            <span class="status-pill status-<?= strtolower($my['status']) ?>"><?= e($my['status']) ?></span>
                            <?php if ($my['submission_status'] === 'pending'): ?>
                                <span class="status-pill status-pending">Pending Approval</span>
                            <?php else: ?>
                                <span class="badge">&#10003; Confirmed by admin</span>
                            <?php endif; ?>
                        </p>
                        <?php if (!empty($my['submitted_at'])): ?>
                            <p class="event-checkin-meta">Submitted <?= e(date('g:i A', strtotime($my['submitted_at']))) ?></p>
                        <?php endif; ?>
                        <?php if (!empty($my['excuse_reason'])): ?>
                            <p class="event-checkin-meta">Reason: <?= e($my['excuse_reason']) ?></p>
                        <?php endif; ?>

                    <?php elseif ($showLateForm): ?>
                        <div class="late-notice">
                            You're checking in after call time (<?= e($callTimeLabel) ?>). Please give a reason &mdash; your Troop Leader will review it.
                        </div>
                        <form method="POST" action="<?= base_url('scout_attendance_action.php') ?>">
                            <input type="hidden" name="event_id" value="<?= (int) $ev['id'] ?>">
                            <input type="hidden" name="action" value="late_confirm">
                            <textarea name="reason" placeholder="Reason for being late..." required></textarea>
                            <button type="submit" class="btn btn-primary">Submit Late Attendance</button>
                        </form>

                    <?php else: ?>
                        <div class="checkin-actions">
                            <form method="POST" action="<?= base_url('scout_attendance_action.php') ?>">
                                <input type="hidden" name="event_id" value="<?= (int) $ev['id'] ?>">
                                <input type="hidden" name="action" value="checkin">
                                <button type="submit" class="btn btn-primary">I'm Here</button>
                            </form>
                            <button type="button" class="btn btn-ghost" onclick="document.getElementById('excuse-<?= (int) $ev['id'] ?>').classList.toggle('active')">Can't make it</button>
                        </div>
                        <div class="excuse-box" id="excuse-<?= (int) $ev['id'] ?>">
                            <form method="POST" action="<?= base_url('scout_attendance_action.php') ?>">
                                <input type="hidden" name="event_id" value="<?= (int) $ev['id'] ?>">
                                <input type="hidden" name="action" value="excuse">
                                <textarea name="reason" placeholder="Reason (e.g. sick, family emergency)..." required></textarea>
                                <button type="submit" class="btn btn-secondary">Submit Excuse</button>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </main>

    <footer class="app-footer">
        <p>BSP Ranking System &mdash; built for educational / school use only. Not officially affiliated with the Boy Scouts of the Philippines.</p>
    </footer>
</div>
</body>
</html>
