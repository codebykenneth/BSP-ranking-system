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

$activities = get_scout_activities($pdo, $scoutId);
$attendanceData = get_scout_attendance_with_points($pdo, $scoutId);
$attendancePoints = $attendanceData['total'];
$leaderboard = get_troop_leaderboard($pdo);

$activityPoints = (float) ($myData['activity_points'] ?? 0);
$progressScore = calculate_progress_score($activityPoints, $attendancePoints, (int) $scout['rank_level']);
$progressPercent = min(100, round(($progressScore / 500) * 100, 1));
$hasPatch = $progressScore >= 500;

$pageTitle = 'My Portal';
$cssPath = __DIR__ . '/assets/css/style.css';
$cssVersion = file_exists($cssPath) ? filemtime($cssPath) : time();

$bsp_quotes = [
    "A scout is prepared not because he expects the worst, but because he expects to do his best.",
    "The best way to make a boy good is to make him happy — that's the heart of scouting.",
    "Every good turn, however small, is a step toward becoming the leader your troop needs.",
    "Rank isn't given, it's earned one honest effort at a time.",
];
$daily_quote = $bsp_quotes[array_rand($bsp_quotes)];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Portal · BSP Ranking System</title>
<link rel="stylesheet" href="<?= base_url('assets/css/style.css') ?>?v=<?= $cssVersion ?>">
<style>
.stat-card { cursor: pointer; transition: transform .15s ease, box-shadow .15s ease; }
.stat-card:hover { transform: translateY(-2px); box-shadow: 0 6px 16px rgba(30,52,40,0.15); }

.modal-overlay {
    display: none;
    position: fixed; inset: 0;
    background: rgba(30,52,40,0.55);
    z-index: 1000;
    align-items: center; justify-content: center;
    padding: 20px;
}
.modal-overlay.active { display: flex; }
.modal-box {
    background: var(--parchment, #F6F2E4);
    border-radius: var(--radius, 10px);
    max-width: 520px; width: 100%;
    max-height: 85vh; overflow-y: auto;
    padding: 28px;
    box-shadow: 0 10px 40px rgba(0,0,0,0.3);
    position: relative;
}
.modal-close {
    position: absolute; top: 14px; right: 18px;
    background: none; border: none; font-size: 22px;
    cursor: pointer; color: var(--ink, #23271F);
}
.modal-box h2 { margin-top: 0; }
.modal-table { width: 100%; border-collapse: collapse; margin-top: 12px; }
.modal-table th, .modal-table td { text-align: left; padding: 8px 6px; border-bottom: 1px solid var(--border, #DDD4B8); font-size: 14px; }
.progress-track {
    background: var(--khaki-light, #EFE9D6);
    border-radius: 20px; height: 22px; overflow: hidden; margin: 16px 0;
}
.progress-fill {
    background: var(--brass, #B4842A);
    height: 100%; transition: width .4s ease;
    display: flex; align-items: center; justify-content: center;
    color: white; font-size: 12px; font-weight: 600;
}
.patch-claim {
    text-align: center; margin-top: 20px; padding: 20px;
    border: 2px dashed var(--brass, #B4842A); border-radius: 10px;
}
.patch-badge {
    font-size: 48px;
    animation: patchPop 1s ease infinite alternate;
}
@keyframes patchPop {
    from { transform: scale(1) rotate(-3deg); }
    to { transform: scale(1.1) rotate(3deg); }
}
.leaderboard-row.me { background: var(--khaki-light, #EFE9D6); font-weight: 700; }
.rank-badge { display: inline-block; width: 28px; }
.site-quote {
    text-align: center; font-style: italic; max-width: 600px;
    margin: 30px auto 10px; color: var(--ink-soft, #5B5F52);
    padding: 0 20px;
}
.made-by { text-align: center; font-size: 13px; margin-top: 4px; color: var(--ink-soft, #5B5F52); }
</style>
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
        <div style="display:flex;align-items:center;gap:14px;">
            <a href="<?= base_url('scout_attendance.php') ?>" class="btn btn-primary btn-small">Take Attendance</a>
            <a href="<?= base_url('logout.php') ?>" class="logout-link">Log out</a>
        </div>
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
            <div class="stat-card" onclick="openModal('modal-activity')">
                <span class="stat-icon">&#127942;</span>
                <div>
                    <span class="stat-value"><?= (int) $activityPoints ?></span>
                    <span class="stat-label">Activity Points</span>
                </div>
            </div>
            <div class="stat-card" onclick="openModal('modal-attendance')">
                <span class="stat-icon">&#128197;</span>
                <div>
                    <span class="stat-value"><?= (int) $attendancePoints ?> pts</span>
                    <span class="stat-label">Attendance</span>
                </div>
            </div>
            <div class="stat-card" onclick="openModal('modal-score')">
                <span class="stat-icon">&#11088;</span>
                <div>
                    <span class="stat-value"><?= number_format($progressScore, 0) ?>/500</span>
                    <span class="stat-label">Total Score</span>
                </div>
            </div>
            <div class="stat-card" onclick="openModal('modal-rank')">
                <span class="stat-icon">&#127942;</span>
                <div>
                    <span class="stat-value">#<?= $myPosition ?? '&mdash;' ?></span>
                    <span class="stat-label">Troop Rank</span>
                </div>
            </div>
        </section>

        <section class="panel">
            <div class="panel-header"><h2>Announcements</h2></div>
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
            <div class="panel-header"><h2>My Attendance History</h2></div>
            <?php if (empty($history)): ?>
                <p class="empty-state">No attendance has been recorded for you yet.</p>
            <?php else: ?>
                <table class="data-table">
                    <thead><tr><th>Date</th><th>Event</th><th>Status</th></tr></thead>
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

    <p class="site-quote">&ldquo;<?= e($daily_quote) ?>&rdquo;</p>

    <footer class="app-footer">
        <p>BSP Ranking System &mdash; built for educational / school use only. Not officially affiliated with the Boy Scouts of the Philippines.</p>
        <p class="made-by">Made by Jhon Kenieth Y. Amarila</p>
    </footer>
</div>

<!-- Activity Points Modal -->
<div class="modal-overlay" id="modal-activity">
    <div class="modal-box">
        <button class="modal-close" onclick="closeModal('modal-activity')">&times;</button>
        <h2>How I Earned My Activity Points</h2>
        <?php if (empty($activities)): ?>
            <p class="empty-state">No activities logged yet. Points will appear here once your Troop Leader records one.</p>
        <?php else: ?>
            <table class="modal-table">
                <thead><tr><th>Activity</th><th>Date</th><th>Points</th></tr></thead>
                <tbody>
                    <?php foreach ($activities as $act): ?>
                        <tr>
                            <td><?= e($act['title'] ?? $act['description'] ?? 'Activity') ?></td>
                            <td><?= e(date('M j, Y', strtotime($act['created_at'] ?? 'now'))) ?></td>
                            <td><strong>+<?= (int) ($act['points'] ?? 0) ?></strong></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<!-- Attendance Modal -->
<div class="modal-overlay" id="modal-attendance">
    <div class="modal-box">
        <button class="modal-close" onclick="closeModal('modal-attendance')">&times;</button>
        <h2>Attendance Points</h2>
        <p style="font-size:13px;color:var(--ink-soft,#5B5F52);">Present = 10 pts &middot; Late = 8 pts &middot; Excused = 0 pts &middot; Absent = 0 pts</p>
        <?php if (empty($attendanceData['records'])): ?>
            <p class="empty-state">No attendance recorded yet.</p>
        <?php else: ?>
            <table class="modal-table">
                <thead><tr><th>Event</th><th>Date</th><th>Status</th><th>Points</th></tr></thead>
                <tbody>
                    <?php foreach ($attendanceData['records'] as $r): ?>
                        <tr>
                            <td><?= e($r['title']) ?></td>
                            <td><?= e(date('M j, Y', strtotime($r['event_date']))) ?></td>
                            <td><span class="status-pill status-<?= strtolower($r['status']) ?>"><?= e($r['status']) ?></span></td>
                            <td><strong>+<?= (int) $r['points'] ?></strong></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <p style="text-align:right;margin-top:10px;"><strong>Total: <?= (int) $attendanceData['total'] ?> pts</strong></p>
        <?php endif; ?>
    </div>
</div>

<!-- Total Score / Patch Modal -->
<div class="modal-overlay" id="modal-score">
    <div class="modal-box">
        <button class="modal-close" onclick="closeModal('modal-score')">&times;</button>
        <h2>My Progress to 500</h2>
        <p>Activity Points (<?= (int) $activityPoints ?>) + Attendance Points (<?= (int) $attendancePoints ?>) + Rank Bonus (<?= (int) $scout['rank_level'] * 10 ?>)</p>
        <div class="progress-track">
            <div class="progress-fill" style="width: <?= $progressPercent ?>%;"><?= $progressPercent ?>%</div>
        </div>
        <p style="text-align:center;"><?= number_format($progressScore, 0) ?> / 500 points</p>

        <?php if ($hasPatch): ?>
            <div class="patch-claim">
                <div class="patch-badge">&#127894;</div>
                <h3>You earned your Patch!</h3>
                <p>Congratulations! Message me on Facebook to claim your patch.</p>
                <a href="https://web.facebook.com/yourkenneth.29" target="_blank" rel="noopener" class="btn btn-primary" style="display:inline-block;text-decoration:none;margin-top:10px;">
                    Claim My Patch on Facebook
                </a>
            </div>
        <?php else: ?>
            <p class="empty-state">Keep earning points from activities and attendance to reach 500 and unlock your patch!</p>
        <?php endif; ?>
    </div>
</div>

<!-- Troop Rank Modal -->
<div class="modal-overlay" id="modal-rank">
    <div class="modal-box">
        <button class="modal-close" onclick="closeModal('modal-rank')">&times;</button>
        <h2>Troop Leaderboard</h2>
        <table class="modal-table">
            <thead><tr><th>#</th><th>Name</th><th>Points</th></tr></thead>
            <tbody>
                <?php foreach ($leaderboard as $i => $entry): ?>
                    <tr class="leaderboard-row <?= $entry['id'] === $scoutId ? 'me' : '' ?>">
                        <td class="rank-badge">#<?= $i + 1 ?></td>
                        <td><?= e($entry['name']) ?><?= $entry['id'] === $scoutId ? ' (You)' : '' ?></td>
                        <td><?= number_format($entry['points'], 0) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function openModal(id) {
    document.getElementById(id).classList.add('active');
}
function closeModal(id) {
    document.getElementById(id).classList.remove('active');
}
document.querySelectorAll('.modal-overlay').forEach(function(overlay) {
    overlay.addEventListener('click', function(e) {
        if (e.target === overlay) overlay.classList.remove('active');
    });
});
</script>
</body>
</html>
