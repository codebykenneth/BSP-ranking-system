<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$id = isset($_GET['id']) ? (int) $_GET['id'] : (isset($_POST['id']) ? (int) $_POST['id'] : 0);
$isEdit = $id > 0;

$scout = [
    'name' => '', 'troop' => '', 'rank_name' => 'Scout', 'attendance' => 0, 'photo' => null,
];
$errors = [];

if ($isEdit) {
    $stmt = $pdo->prepare("SELECT * FROM scouts WHERE id = ?");
    $stmt->execute([$id]);
    $found = $stmt->fetch();
    if (!$found) {
        header('Location: ' . base_url('pages/scouts.php'));
        exit;
    }
    $scout = $found;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $scout['name']       = trim($_POST['name'] ?? '');
    $scout['troop']      = trim($_POST['troop'] ?? '');
    $scout['rank_name']  = trim($_POST['rank_name'] ?? 'Scout');
    $scout['attendance'] = (float) ($_POST['attendance'] ?? 0);

    if ($scout['name'] === '') $errors[] = 'Name is required.';
    if ($scout['troop'] === '') $errors[] = 'Troop is required.';
    if (!array_key_exists($scout['rank_name'], RANK_LEVELS)) $errors[] = 'Invalid rank selected.';
    if ($scout['attendance'] < 0 || $scout['attendance'] > 100) $errors[] = 'Attendance must be between 0 and 100.';

    // Optional profile picture upload
    $photoPath = $scout['photo'] ?? null;
    if (!empty($_FILES['photo']['name'])) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed, true)) {
            $errors[] = 'Photo must be a JPG, PNG, GIF, or WEBP file.';
        } elseif ($_FILES['photo']['size'] > 2 * 1024 * 1024) {
            $errors[] = 'Photo must be smaller than 2MB.';
        } else {
            $uploadDir = __DIR__ . '/../assets/uploads/';
            $newName = 'scout_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
            if (move_uploaded_file($_FILES['photo']['tmp_name'], $uploadDir . $newName)) {
                $photoPath = 'assets/uploads/' . $newName;
            } else {
                $errors[] = 'Failed to upload photo. Please try again.';
            }
        }
    }

    if (empty($errors)) {
        $rankLevel = RANK_LEVELS[$scout['rank_name']];

        if ($isEdit) {
            $stmt = $pdo->prepare(
                "UPDATE scouts SET name = ?, troop = ?, rank_name = ?, rank_level = ?, attendance = ?, photo = ? WHERE id = ?"
            );
            $stmt->execute([$scout['name'], $scout['troop'], $scout['rank_name'], $rankLevel, $scout['attendance'], $photoPath, $id]);
        } else {
            $stmt = $pdo->prepare(
                "INSERT INTO scouts (name, troop, rank_name, rank_level, attendance, photo) VALUES (?, ?, ?, ?, ?, ?)"
            );
            $stmt->execute([$scout['name'], $scout['troop'], $scout['rank_name'], $rankLevel, $scout['attendance'], $photoPath]);
        }
        header('Location: ' . base_url('pages/scouts.php?saved=1'));
        exit;
    }
}

$pageTitle   = $isEdit ? 'Edit Scout' : 'Add Scout';
$currentPage = 'scouts';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>
<main class="main-content">
    <div class="content-header">
        <h1><?= $isEdit ? 'Edit Scout' : 'Add New Scout' ?></h1>
        <p>Fields marked with the rank level directly affect the scoring formula.</p>
    </div>

    <section class="panel panel-narrow">
        <?php if (!empty($errors)): ?>
            <div class="alert alert-error">
                <ul>
                    <?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data" class="stacked-form">
            <input type="hidden" name="id" value="<?= (int) $id ?>">

            <label for="name">Full Name</label>
            <input type="text" id="name" name="name" value="<?= e($scout['name']) ?>" required>

            <label for="troop">Troop</label>
            <input type="text" id="troop" name="troop" value="<?= e($scout['troop']) ?>" required placeholder="e.g. Mountain Tigers.">

            <label for="rank_name">Rank</label>
            <select id="rank_name" name="rank_name" required>
                <?php foreach (RANK_LEVELS as $rankName => $level): ?>
                    <option value="<?= e($rankName) ?>" <?= $scout['rank_name'] === $rankName ? 'selected' : '' ?>>
                        <?= e($rankName) ?> (Level <?= $level ?>)
                    </option>
                <?php endforeach; ?>
            </select>

            <label for="attendance">Starting Attendance (%)</label>
            <input type="number" id="attendance" name="attendance" min="0" max="100" step="0.01"
                   value="<?= e((string) $scout['attendance']) ?>" required>
            <p class="field-hint">Used only until this scout has real attendance records from Events. Once they've been marked at an event, their attendance % is calculated automatically.</p>

            <label for="photo">Profile Picture (optional)</label>
            <input type="file" id="photo" name="photo" accept="image/*">
            <?php if (!empty($scout['photo'])): ?>
                <div class="current-photo">
                    <img src="<?= base_url($scout['photo']) ?>" alt="Current photo">
                    <span>Current photo</span>
                </div>
            <?php endif; ?>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary"><?= $isEdit ? 'Save Changes' : 'Add Scout' ?></button>
                <a href="<?= base_url('pages/scouts.php') ?>" class="btn btn-ghost">Cancel</a>
            </div>
        </form>
    </section>
</main>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
