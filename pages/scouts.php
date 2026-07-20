<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Handle delete
if (isset($_GET['delete'])) {
    $deleteId = (int) $_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM scouts WHERE id = ?");
    $stmt->execute([$deleteId]);
    header('Location: ' . base_url('pages/scouts.php?deleted=1'));
    exit;
}

// Search / filter
$search = trim($_GET['search'] ?? '');
$troopFilter = trim($_GET['troop'] ?? '');

$sql = "SELECT * FROM scouts WHERE 1=1";
$params = [];

if ($search !== '') {
    $sql .= " AND name LIKE ?";
    $params[] = "%$search%";
}
if ($troopFilter !== '') {
    $sql .= " AND troop = ?";
    $params[] = $troopFilter;
}
$sql .= " ORDER BY name ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$scouts = $stmt->fetchAll();

$troops = $pdo->query("SELECT DISTINCT troop FROM scouts ORDER BY troop ASC")->fetchAll(PDO::FETCH_COLUMN);

$pageTitle   = 'Scouts';
$currentPage = 'scouts';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>
<main class="main-content">
    <div class="content-header content-header-row">
        <div>
            <h1>Scout Management</h1>
            <p>Add, edit, or remove scouts from the roster.</p>
        </div>
        <a href="<?= base_url('pages/scout_form.php') ?>" class="btn btn-primary">+ Add Scout</a>
    </div>

    <?php if (isset($_GET['saved'])): ?>
        <div class="alert alert-success">Scout saved successfully.</div>
    <?php elseif (isset($_GET['deleted'])): ?>
        <div class="alert alert-success">Scout deleted.</div>
    <?php endif; ?>

    <section class="panel">
        <form method="GET" class="filter-bar">
            <input type="text" name="search" placeholder="Search by name..." value="<?= e($search) ?>">
            <select name="troop">
                <option value="">All Troops</option>
                <?php foreach ($troops as $t): ?>
                    <option value="<?= e($t) ?>" <?= $troopFilter === $t ? 'selected' : '' ?>><?= e($t) ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-secondary">Filter</button>
            <a href="<?= base_url('pages/scouts.php') ?>" class="btn btn-ghost">Reset</a>
        </form>

        <?php if (empty($scouts)): ?>
            <p class="empty-state">No scouts found.</p>
        <?php else: ?>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Troop</th>
                        <th>Rank</th>
                        <th>Attendance</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($scouts as $s): ?>
                        <tr>
                            <td>#<?= (int) $s['id'] ?></td>
                            <td><?= e($s['name']) ?></td>
                            <td><?= e($s['troop']) ?></td>
                            <td><span class="badge"><?= e($s['rank_name']) ?></span></td>
                            <td><?= number_format((float) $s['attendance'], 1) ?>%</td>
                            <td class="actions">
                                <a href="<?= base_url('pages/scout_form.php?id=' . $s['id']) ?>" class="btn btn-small btn-secondary">Edit</a>
                                <a href="<?= base_url('pages/scouts.php?delete=' . $s['id']) ?>"
                                   class="btn btn-small btn-danger"
                                   onclick="return confirm('Delete this scout? Their activities will also be removed.');">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </section>
</main>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
