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

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_FILES['photo']['name'])) {
        $errors[] = 'Please choose an image to upload.';
    } else {
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));

        if (!in_array($ext, $allowed, true)) {
            $errors[] = 'Photo must be a JPG, PNG, GIF, or WEBP file.';
        } elseif ($_FILES['photo']['size'] > 2 * 1024 * 1024) {
            $errors[] = 'Photo must be smaller than 2MB.';
        } elseif ($_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'Upload failed. Please try again.';
        } else {
            $uploadDir = __DIR__ . '/assets/uploads/';
            $newName = 'scout_' . $scoutId . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;

            if (move_uploaded_file($_FILES['photo']['tmp_name'], $uploadDir . $newName)) {
                // Clean up their old photo file, if any, before saving the new path.
                if (!empty($scout['photo'])) {
                    $oldPath = __DIR__ . '/' . $scout['photo'];
                    if (is_file($oldPath)) {
                        @unlink($oldPath);
                    }
                }

                $newPhotoPath = 'assets/uploads/' . $newName;
                $stmt = $pdo->prepare("UPDATE scouts SET photo = ? WHERE id = ?");
                $stmt->execute([$newPhotoPath, $scoutId]);
                $scout['photo'] = $newPhotoPath;
                $success = true;
            } else {
                $errors[] = 'Failed to save the uploaded photo. Please try again.';
            }
        }
    }
}

$pageTitle = 'My Profile Picture';
$cssPath = __DIR__ . '/assets/css/style.css';
$cssVersion = file_exists($cssPath) ? filemtime($cssPath) : time();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Profile Picture · BSP Ranking System</title>
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
        <p><a href="<?= base_url('scout_portal.php') ?>" class="link">&larr; Back to my portal</a></p>

        <section class="panel panel-narrow">
            <div class="panel-header">
                <h2>My Profile Picture</h2>
            </div>

            <?php if ($success): ?>
                <div class="alert alert-success">Profile picture updated!</div>
            <?php endif; ?>
            <?php if (!empty($errors)): ?>
                <div class="alert alert-error">
                    <ul>
                        <?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <div class="current-photo" style="margin-bottom: 20px;">
                <?= scout_avatar($scout, 'lg') ?>
                <span>This is how you currently appear on the leaderboard.</span>
            </div>

            <form method="POST" enctype="multipart/form-data" class="stacked-form">
                <label for="photo">Choose a new photo</label>
                <input type="file" id="photo" name="photo" accept="image/*" required>
                <p class="field-hint">JPG, PNG, GIF, or WEBP. Max 2MB.</p>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Upload Photo</button>
                </div>
            </form>
        </section>
    </main>

    <footer class="app-footer">
        <p>BSP Ranking System &mdash; built for educational / school use only. Not officially affiliated with the Boy Scouts of the Philippines.</p>
    </footer>
</div>
</body>
</html>
