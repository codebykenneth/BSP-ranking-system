<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$ranked = get_ranked_scouts($pdo);
$top10  = array_slice($ranked, 0, 10);

$pageTitle   = 'Leaderboard';
$currentPage = 'leaderboard';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>
<main class="main-content">
    <div class="content-header">
        <h1>Leaderboard</h1>
        <p>Top 10 scouts ranked by total score. Recomputed live from activities, rank, and attendance.</p>
    </div>

    <section class="panel">
        <?php if (empty($top10)): ?>
            <p class="empty-state">No scouts yet.</p>
        <?php else: ?>
            <div class="podium">
                <?php foreach (array_slice($top10, 0, 3) as $i => $s): ?>
                    <div class="podium-item podium-<?= $i + 1 ?>">
                        <span class="podium-medal"><?= ['&#129351;', '&#129352;', '&#129353;'][$i] ?></span>
                        <?= scout_avatar($s, 'lg') ?>
                        <strong><?= e($s['name']) ?></strong>
                        <span><?= e($s['troop']) ?></span>
                        <span class="podium-score"><?= number_format($s['score'], 2) ?> pts</span>
                    </div>
                <?php endforeach; ?>
            </div>

            <table class="data-table">
                <thead>
                    <tr>
                        <th>Rank</th>
                        <th>Name</th>
                        <th>Troop</th>
                        <th>BSP Rank</th>
                        <th>Activity Pts</th>
                        <th>Attendance</th>
                        <th>Total Score</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($top10 as $i => $s): ?>
                        <tr class="<?= $i < 3 ? 'rank-top rank-' . ($i + 1) : '' ?>">
                            <td>
                                <?php if ($i < 3): ?>
                                    <span class="rank-medal"><?= ['&#129351;', '&#129352;', '&#129353;'][$i] ?></span>
                                <?php else: ?>
                                    <?= $i + 1 ?>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="name-cell">
                                    <?= scout_avatar($s, 'sm') ?>
                                    <span><?= e($s['name']) ?></span>
                                </div>
                            </td>
                            <td><?= e($s['troop']) ?></td>
                            <td><span class="badge"><?= e($s['rank_name']) ?></span></td>
                            <td><?= (int) $s['activity_points'] ?></td>
                            <td><?= number_format((float) $s['attendance'], 1) ?>%</td>
                            <td><strong><?= number_format($s['score'], 2) ?></strong></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </section>
</main>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
