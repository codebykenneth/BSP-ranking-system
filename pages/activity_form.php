<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$id = isset($_GET['id']) ? (int) $_GET['id'] : (isset($_POST['id']) ? (int) $_POST['id'] : 0);
$isEdit = $id > 0;

$activity = ['scout_id' => '', 'activity_name' => '', 'points' => '', 'activity_date' => date('Y-m-d')];
$errors = [];

if ($isEdit) {
    $stmt = $pdo->prepare("SELECT * FROM activities WHERE id = ?");
    $stmt->execute([$id]);
    $found = $stmt->fetch();
    if (!$found) {
        header('Location: ' . base_url('pages/activities.php'));
        exit;
    }
    $activity = $found;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $activity['scout_id']       = (int) ($_POST['scout_id'] ?? 0);
    $activity['activity_name']  = trim($_POST['activity_name'] ?? '');
    $activity['points']         = (int) ($_POST['points'] ?? 0);
    $activity['activity_date']  = $_POST['activity_date'] ?? date('Y-m-d');

    if ($activity['scout_id'] <= 0) $errors[] = 'Please select a scout.';
    if ($activity['activity_name'] === '') $errors[] = 'Activity name is required.';
    if ($activity['points'] < 0) $errors[] = 'Points cannot be negative.';
    if (empty($activity['activity_date'])) $errors[] = 'Date is required.';

    if (empty($errors)) {
        if ($isEdit) {
            $stmt = $pdo->prepare(
                "UPDATE activities SET scout_id = ?, activity_name = ?, points = ?, activity_date = ? WHERE id = ?"
            );
            $stmt->execute([$activity['scout_id'], $activity['activity_name'], $activity['points'], $activity['activity_date'], $id]);
        } else {
            $stmt = $pdo->prepare(
                "INSERT INTO activities (scout_id, activity_name, points, activity_date) VALUES (?, ?, ?, ?)"
            );
            $stmt->execute([$activity['scout_id'], $activity['activity_name'], $activity['points'], $activity['activity_date']]);
        }
        header('Location: ' . base_url('pages/activities.php?saved=1'));
        exit;
    }
}

$scoutsList = $pdo->query("SELECT id, name, troop FROM scouts ORDER BY name ASC")->fetchAll();

$pageTitle   = $isEdit ? 'Edit Activity' : 'Add Activity';
$currentPage = 'activities';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>
<main class="main-content">
    <div class="content-header">
        <h1><?= $isEdit ? 'Edit Activity' : 'Add New Activity' ?></h1>
        <p>Points here feed directly into each scout's total score.</p>
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
            <input type="hidden" name="id" value="<?= (int) $id ?>">

            <label for="scout_id">Scout</label>
            <select id="scout_id" name="scout_id" required>
                <option value="">Select a scout</option>
                <?php foreach ($scoutsList as $sc): ?>
                    <option value="<?= $sc['id'] ?>" <?= (int) $activity['scout_id'] === (int) $sc['id'] ? 'selected' : '' ?>>
                        <?= e($sc['name']) ?> &mdash; <?= e($sc['troop']) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label for="activity_name">Activity Name</label>
            <input type="text" id="activity_name" name="activity_name" value="<?= e($activity['activity_name']) ?>" required placeholder="e.g. Camporee 2026">

            <label for="points">Points</label>
            <input type="number" id="points" name="points" min="0" value="<?= e((string) $activity['points']) ?>" required>

            <label for="activity_date">Date</label>
            <input type="date" id="activity_date" name="activity_date" value="<?= e($activity['activity_date']) ?>" required>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary"><?= $isEdit ? 'Save Changes' : 'Add Activity' ?></button>
                <a href="<?= base_url('pages/activities.php') ?>" class="btn btn-ghost">Cancel</a>
            </div>
        </form>
    </section>
</main>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
