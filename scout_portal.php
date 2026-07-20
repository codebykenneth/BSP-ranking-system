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
    // Their record was deleted by an admin — end the session.
    session_destroy();
    header('Location: ' . base_url('scout_login.php'));
    exit;
}

$ranked = get_ranked_scouts($pdo);
$myPosition = null;
$myData = null;
foreach ($ranked as $i => $s) {
    if ((int) $s['id'] === $scoutId) {
        $myPosition = $i + 1;
        $myData = $s;
        break;
    }
}

$history = get_scout_attendance_history($pdo, $scoutId);
$announcements = get_recent_announcements($pdo, 5);

$pageTitle = 'My Portal';
$cssPath = __DIR__ . '/assets/css/style.css';
$cssVersion = file_exists($cssPath) ? filemtime($cssPath) : time();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Portal · BSP Ranking System</title>
<link rel="stylesheet" href="<?= base_url('assets/css/style.css') ?>?v=<?= $cssVersion ?>">
</head>
<body>
<div class="scout-portal-shell">
    <header class="scout-portal-header">
        <div class="scout-portal-brand">
            <span class="brand-mark">BSP</span>
            <div>
                <strong>My Scout Portal</strong>
                <small>View only &mdash; see an admin to update your info</small>
            </div>
        </div>
        <a href="<?= base_url('logout.php') ?>" class="logout-link">Log out</a>
    </header>

    <main class="scout-portal-main">
        <section class="scout-profile-card">
            <?= scout_avatar($scout, 'lg') ?>
            <div>
                <h1><?= e($scout['name']) ?></h1>
                <p><?= e($scout['troop']) ?> &middot; <span class="badge"><?= e($scout['rank_name']) ?></span></p>
                <a href="<?= base_url('scout_profile.php') ?>" class="link" style="font-size:13px;">Change my photo &rarr;</a>
            </div>
        </section>

        <section class="stats-grid">
            <div class="stat-card">
                <span class="stat-icon">&#127942;</span>
                <div>
                    <span class="stat-value"><?= (int) ($myData['activity_points'] ?? 0) ?></span>
                    <span class="stat-label">Activity Points</span>
                </div>
            </div>
            <div class="stat-card">
                <span class="stat-icon">&#128197;</span>
                <div>
                    <span class="stat-value"><?= number_format($myData['attendance'] ?? 0, 1) ?>%</span>
                    <span class="stat-label">Attendance</span>
                </div>
            </div>
            <div class="stat-card">
                <span class="stat-icon">&#11088;</span>
                <div>
                    <span class="stat-value"><?= number_format($myData['score'] ?? 0, 2) ?></span>
                    <span class="stat-label">Total Score</span>
                </div>
            </div>
            <div class="stat-card">
                <span class="stat-icon">&#127942;</span>
                <div>
                    <span class="stat-value">#<?= $myPosition ?? '&mdash;' ?></span>
                    <span class="stat-label">Troop Rank</span>
                </div>
            </div>
        </section>

        <section class="panel">
            <div class="panel-header">
                <h2>Announcements</h2>
            </div>
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
                            </div>
                            <p class="announcement-message"><?= nl2br(e($a['message'])) ?></p>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <section class="panel">
            <div class="panel-header">
                <h2>My Attendance History</h2>
            </div>
            <?php if (empty($history)): ?>
                <p class="empty-state">No attendance has been recorded for you yet.</p>
            <?php else: ?>
                <table class="data-table">
                    <thead>
                        <tr><th>Date</th><th>Event</th><th>Status</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($history as $h): ?>
                            <tr>
                                <td><?= e(date('M j, Y', strtotime($h['event_date']))) ?></td>
                                <td><?= e($h['title']) ?></td>
                                <td><span class="status-pill status-<?= strtolower($h['status']) ?>"><?= e($h['status']) ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </section>
    </main>

    <footer class="app-footer">
        <p>BSP Ranking System &mdash; built for educational / school use only. Not officially affiliated with the Boy Scouts of the Philippines.</p>
    </footer>
</div>
</body>
</html>
