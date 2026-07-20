<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

if (isset($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM activities WHERE id = ?");
    $stmt->execute([(int) $_GET['delete']]);
    header('Location: ' . base_url('pages/activities.php?deleted=1'));
    exit;
}

$scoutFilter = isset($_GET['scout_id']) ? (int) $_GET['scout_id'] : 0;

$sql = "SELECT a.*, s.name AS scout_name, s.troop
        FROM activities a
        JOIN scouts s ON s.id = a.scout_id";
$params = [];
if ($scoutFilter > 0) {
    $sql .= " WHERE a.scout_id = ?";
    $params[] = $scoutFilter;
}
$sql .= " ORDER BY a.activity_date DESC, a.id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$activities = $stmt->fetchAll();

$scoutsList = $pdo->query("SELECT id, name FROM scouts ORDER BY name ASC")->fetchAll();

$pageTitle   = 'Activities';
$currentPage = 'activities';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>
<main class="main-content">
    <div class="content-header content-header-row">
        <div>
            <h1>Activity Management</h1>
            <p>Log activities and points earned per scout.</p>
        </div>
        <a href="<?= base_url('pages/activity_form.php') ?>" class="btn btn-primary">+ Add Activity</a>
    </div>

    <?php if (isset($_GET['saved'])): ?>
        <div class="alert alert-success">Activity saved successfully.</div>
    <?php elseif (isset($_GET['deleted'])): ?>
        <div class="alert alert-success">Activity deleted.</div>
    <?php endif; ?>

    <section class="panel">
        <form method="GET" class="filter-bar">
            <select name="scout_id">
                <option value="">All Scouts</option>
                <?php foreach ($scoutsList as $sc): ?>
                    <option value="<?= $sc['id'] ?>" <?= $scoutFilter === (int) $sc['id'] ? 'selected' : '' ?>>
                        <?= e($sc['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-secondary">Filter</button>
            <a href="<?= base_url('pages/activities.php') ?>" class="btn btn-ghost">Reset</a>
        </form>

        <?php if (empty($activities)): ?>
            <p class="empty-state">No activities logged yet.</p>
        <?php else: ?>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Scout</th>
                        <th>Troop</th>
                        <th>Activity</th>
                        <th>Points</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($activities as $a): ?>
                        <tr>
                            <td><?= e(date('M j, Y', strtotime($a['activity_date']))) ?></td>
                            <td><?= e($a['scout_name']) ?></td>
                            <td><?= e($a['troop']) ?></td>
                            <td><?= e($a['activity_name']) ?></td>
                            <td><strong>+<?= (int) $a['points'] ?></strong></td>
                            <td class="actions">
                                <a href="<?= base_url('pages/activity_form.php?id=' . $a['id']) ?>" class="btn btn-small btn-secondary">Edit</a>
                                <a href="<?= base_url('pages/activities.php?delete=' . $a['id']) ?>"
                                   class="btn btn-small btn-danger"
                                   onclick="return confirm('Delete this activity?');">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </section>
</main>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
