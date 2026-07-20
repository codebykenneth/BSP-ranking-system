<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

if (is_scout_logged_in()) {
    header('Location: ' . base_url('scout_portal.php'));
    exit;
}

$error = $_SESSION['scout_login_error'] ?? '';
unset($_SESSION['scout_login_error']);

$success = $_SESSION['scout_login_success'] ?? '';
unset($_SESSION['scout_login_success']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Scout Log In · BSP Ranking System</title>
<?php $cssPath = __DIR__ . '/assets/css/style.css'; $cssVersion = file_exists($cssPath) ? filemtime($cssPath) : time(); ?>
<link rel="stylesheet" href="<?= base_url('assets/css/style.css') ?>?v=<?= $cssVersion ?>">
</head>
<body class="login-body">
    <div class="login-card">
        <div class="login-brand">
            <span class="brand-mark brand-mark-lg">BSP</span>
            <h1>Scout Log In</h1>
            <p>View your rank, points, attendance, and standing.</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= e($error) ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success"><?= e($success) ?></div>
        <?php endif; ?>

        <form action="scout_login_process.php" method="POST" class="login-form">
            <label for="username">Username</label>
            <input type="text" id="username" name="username" required autofocus placeholder="Your username">

            <label for="password">Password</label>
            <input type="password" id="password" name="password" required placeholder="&bull;&bull;&bull;&bull;&bull;&bull;">

            <button type="submit" class="btn btn-primary btn-block">Log In</button>
        </form>

        <p class="login-hint">Don't have an account? <a href="<?= base_url('register_scout.php') ?>">Register as Scout</a></p>
        <p class="login-hint"><a href="<?= base_url('index.php') ?>">&larr; Back to admin login</a></p>
        <p class="login-disclaimer">Not officially affiliated with the Boy Scouts of the Philippines. For educational / school use only.</p>
    </div>
</body>
</html>
