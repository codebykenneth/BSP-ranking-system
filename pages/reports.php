<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$ranked = get_ranked_scouts($pdo);
$printMode = isset($_GET['print']);

$pageTitle   = 'Reports';
$currentPage = 'reports';

if ($printMode) {
    // Minimal standalone print view (no sidebar/nav)
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
    <meta charset="UTF-8">
    <title>Scout Rankings Report</title>
    <?php $cssPath = __DIR__ . '/../assets/css/style.css'; $cssVersion = file_exists($cssPath) ? filemtime($cssPath) : time(); ?>
    <link rel="stylesheet" href="<?= base_url('assets/css/style.css') ?>?v=<?= $cssVersion ?>">
    </head>
    <body class="print-body">
        <div class="print-sheet">
            <h1>BSP Ranking System &mdash; Full Report</h1>
            <p class="print-meta">Generated on <?= date('F j, Y, g:i A') ?> &middot; <?= count($ranked) ?> scouts</p>
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
                    <?php foreach ($ranked as $i => $s): ?>
                        <tr>
                            <td><?= $i + 1 ?></td>
                            <td><?= e($s['name']) ?></td>
                            <td><?= e($s['troop']) ?></td>
                            <td><?= e($s['rank_name']) ?></td>
                            <td><?= (int) $s['activity_points'] ?></td>
                            <td><?= number_format((float) $s['attendance'], 1) ?>%</td>
                            <td><?= number_format($s['score'], 2) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <p class="print-disclaimer">Not officially affiliated with the Boy Scouts of the Philippines. For educational / school use only.</p>
        </div>
        <script>window.onload = () => window.print();</script>
    </body>
    </html>
    <?php
    exit;
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>
<main class="main-content">
    <div class="content-header content-header-row">
        <div>
            <h1>Reports</h1>
            <p>Complete roster with computed scores, sorted highest to lowest.</p>
        </div>
        <a href="<?= base_url('pages/reports.php?print=1') ?>" target="_blank" class="btn btn-secondary">&#128424; Print / Export</a>
    </div>

    <section class="panel">
        <?php if (empty($ranked)): ?>
            <p class="empty-state">No scouts yet.</p>
        <?php else: ?>
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
                    <?php foreach ($ranked as $i => $s): ?>
                        <tr class="<?= $i < 3 ? 'rank-top rank-' . ($i + 1) : '' ?>">
                            <td><?= $i + 1 ?></td>
                            <td><?= e($s['name']) ?></td>
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
