<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$totalScouts     = (int) $pdo->query("SELECT COUNT(*) FROM scouts")->fetchColumn();
$totalActivities = (int) $pdo->query("SELECT COUNT(*) FROM activities")->fetchColumn();
$totalTroops     = (int) $pdo->query("SELECT COUNT(DISTINCT troop) FROM scouts")->fetchColumn();

$ranked = get_ranked_scouts($pdo);
$top5   = array_slice($ranked, 0, 5);
$avgAttendance = count($ranked) ? array_sum(array_column($ranked, 'attendance')) / count($ranked) : 0;
$lowAttendance = get_low_attendance_scouts($pdo, 75);
$pendingAttendance = get_pending_attendance($pdo);

$pageTitle   = 'Dashboard';
$currentPage = 'dashboard';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>
<main class="main-content">
    <div class="content-header">
        <h1>Dashboard</h1>
        <p>Overview of scouts, activities, and current standings.</p>
    </div>

    <section class="stats-grid">
        <div class="stat-card">
            <span class="stat-icon">&#9878;</span>
            <div>
                <span class="stat-value"><?= $totalScouts ?></span>
                <span class="stat-label">Total Scouts</span>
            </div>
        </div>
        <div class="stat-card">
            <span class="stat-icon">&#127942;</span>
            <div>
                <span class="stat-value"><?= $totalActivities ?></span>
                <span class="stat-label">Total Activities</span>
            </div>
        </div>
        <div class="stat-card">
            <span class="stat-icon">&#9971;</span>
            <div>
                <span class="stat-value"><?= $totalTroops ?></span>
                <span class="stat-label">Troops</span>
            </div>
        </div>
        <div class="stat-card">
            <span class="stat-icon">&#128197;</span>
            <div>
                <span class="stat-value"><?= number_format($avgAttendance, 1) ?>%</span>
                <span class="stat-label">Avg. Attendance</span>
            </div>
        </div>
    </section>

    <section class="panel">
        <div class="panel-header">
            <h2>Top 5 Scouts</h2>
            <a href="<?= base_url('pages/leaderboard.php') ?>" class="link">View full leaderboard &rarr;</a>
        </div>

        <?php if (empty($top5)): ?>
            <p class="empty-state">No scouts yet. Add scouts to see the leaderboard.</p>
        <?php else: ?>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Troop</th>
                        <th>Rank</th>
                        <th>Score</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($top5 as $i => $s): ?>
                        <tr class="<?= $i < 3 ? 'rank-top rank-' . ($i + 1) : '' ?>">
                            <td><?= $i + 1 ?></td>
                            <td><?= e($s['name']) ?></td>
                            <td><?= e($s['troop']) ?></td>
                            <td><?= e($s['rank_name']) ?></td>
                            <td><strong><?= number_format($s['score'], 2) ?></strong></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </section>

    <?php if (!empty($pendingAttendance)): ?>
    <section class="panel">
        <div class="panel-header">
            <h2>Pending Attendance Approvals (<?= count($pendingAttendance) ?>)</h2>
            <a href="<?= base_url('pages/attendance_approvals.php') ?>" class="link">Review all &rarr;</a>
        </div>
        <table class="data-table">
            <thead>
                <tr><th>Scout</th><th>Activity</th><th>Submitted</th><th>Reported Status</th></tr>
            </thead>
            <tbody>
                <?php foreach (array_slice($pendingAttendance, 0, 5) as $p): ?>
                    <tr>
                        <td>
                            <div class="name-cell">
                                <?= scout_avatar(['name' => $p['scout_name'], 'photo' => $p['photo']], 'sm') ?>
                                <span><?= e($p['scout_name']) ?></span>
                            </div>
                        </td>
                        <td><?= e($p['event_title']) ?></td>
                        <td><?= e(date('M j, g:i A', strtotime($p['submitted_at']))) ?></td>
                        <td><span class="status-pill status-<?= strtolower($p['status']) ?>"><?= e($p['status']) ?></span></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </section>
    <?php endif; ?>

    <?php if (!empty($lowAttendance)): ?>
    <section class="panel">
        <div class="panel-header">
            <h2>Needs Attention &mdash; Attendance Below 75%</h2>
            <a href="<?= base_url('pages/events.php') ?>" class="link">Manage events &rarr;</a>
        </div>
        <table class="data-table">
            <thead>
                <tr><th>Name</th><th>Troop</th><th>Attendance</th></tr>
            </thead>
            <tbody>
                <?php foreach ($lowAttendance as $s): ?>
                    <tr>
                        <td>
                            <div class="name-cell">
                                <?= scout_avatar($s, 'sm') ?>
                                <span><?= e($s['name']) ?></span>
                            </div>
                        </td>
                        <td><?= e($s['troop']) ?></td>
                        <td><span class="status-pill status-absent"><?= number_format($s['attendance'], 1) ?>%</span></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </section>
    <?php endif; ?>
</main>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
