<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

if (is_logged_in()) {
    header('Location: ' . base_url('pages/dashboard.php'));
    exit;
}

$error = $_SESSION['login_error'] ?? '';
unset($_SESSION['login_error']);

$success = $_SESSION['login_success'] ?? '';
unset($_SESSION['login_success']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Log in · BSP Ranking System</title>
<?php
$cssPath = __DIR__ . '/assets/css/style.css';
$cssVersion = file_exists($cssPath) ? filemtime($cssPath) : time();
?>
<link rel="stylesheet" href="<?= base_url('assets/css/style.css') ?>?v=<?= $cssVersion ?>">
</head>
<body class="login-body">
    <div class="login-card">
        <div class="login-brand">
            <span class="brand-mark brand-mark-lg">BSP</span>
            <h1>BSP RANKING SYSTEM</h1>
            <p>Sign in to manage scouts, activities, and rankings.</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= e($error) ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success"><?= e($success) ?></div>
        <?php endif; ?>

        <form action="login_process.php" method="POST" class="login-form">
            <label for="username">Username</label>
            <input type="text" id="username" name="username" required autofocus placeholder="ENTER HERE">

            <label for="password">Password</label>
            <input type="password" id="password" name="password" required placeholder="&bull;&bull;&bull;&bull;&bull;&bull;">

            <button type="submit" class="btn btn-primary btn-block">Log In</button>
        </form>

        <p class="login-hint">THIS WEB APP IS FOR TIS MOUNTAIN TIGERS ONLY BSP IN TAPTAP INTEGRATED SCHOOL.</p>
        <p class="login-hint">Don't have an account? Click the Rigester as Scout Button Bellow.</p>

        <div class="login-divider"><span>Want to be a scout?</span></div>

        <div class="scout-portal-actions">
            <a href="<?= base_url('scout_login.php') ?>" class="btn btn-secondary btn-block">Log In as Scout</a>
            <a href="<?= base_url('register_scout.php') ?>" class="btn btn-ghost btn-block">Register as Scout</a>
        </div>

        <p class="login-disclaimer">Not officially affiliated with the Boy Scouts of the Philippines. For educational / school use only.</p>
    </div>
</body>
</html>
