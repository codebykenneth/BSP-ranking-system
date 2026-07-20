<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/config/database.php';

if (is_scout_logged_in()) {
    header('Location: ' . base_url('scout_portal.php'));
    exit;
}

$errors = [];
$name = '';
$troop = '';
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = trim($_POST['name'] ?? '');
    $troop    = trim($_POST['troop'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    if ($name === '') $errors[] = 'Full name is required.';
    if ($troop === '') $errors[] = 'Troop is required.';
    if ($username === '' || strlen($username) < 3) $errors[] = 'Username must be at least 3 characters.';
    if (strlen($password) < 6) $errors[] = 'Password must be at least 6 characters.';
    if ($password !== $confirm) $errors[] = 'Passwords do not match.';

    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT id FROM scouts WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            $errors[] = 'That username is already taken.';
        }
    }

    if (empty($errors)) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        // New self-registered scouts start at the base rank; an admin can
        // promote their rank and attendance later from Scout Management.
        $stmt = $pdo->prepare(
            "INSERT INTO scouts (name, troop, rank_name, rank_level, attendance, username, password)
             VALUES (?, ?, 'Scout', 1, 0, ?, ?)"
        );
        $stmt->execute([$name, $troop, $username, $hash]);

        $_SESSION['scout_login_success'] = 'Account created! You can now log in to your scout portal.';
        header('Location: ' . base_url('scout_login.php'));
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Register as Scout · BSP Ranking System</title>
<?php $cssPath = __DIR__ . '/assets/css/style.css'; $cssVersion = file_exists($cssPath) ? filemtime($cssPath) : time(); ?>
<link rel="stylesheet" href="<?= base_url('assets/css/style.css') ?>?v=<?= $cssVersion ?>">
</head>
<body class="login-body">
    <div class="login-card">
        <div class="login-brand">
            <span class="brand-mark brand-mark-lg">BSP</span>
            <h1>Register as Scout</h1>
            <p>Create your own account to view your rank, points, and standing.</p>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-error">
                <ul>
                    <?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form action="register_scout.php" method="POST" class="login-form">
            <label for="name">Full Name</label>
            <input type="text" id="name" name="name" required autofocus
                   value="<?= e($name) ?>" placeholder="EX: JHON KENIETH Y. AMARILA">

            <label for="troop">Troop</label>
            <input type="text" id="troop" name="troop" required
                   value="<?= e($troop) ?>" placeholder="MOUNTAIN TIGERS">

            <label for="username">Username</label>
            <input type="text" id="username" name="username" required
                   value="<?= e($username) ?>" placeholder="EX: SCOUTER AMARILA">

            <label for="password">Password</label>
            <input type="password" id="password" name="password" required placeholder="At least 6 characters">

            <label for="confirm_password">Confirm Password</label>
            <input type="password" id="confirm_password" name="confirm_password" required placeholder="Re-enter password">

            <button type="submit" class="btn btn-primary btn-block">Create My Account</button>
        </form>

        <p class="login-hint">Already registered? <a href="<?= base_url('scout_login.php') ?>">Log in here</a></p>
        <p class="login-hint"><a href="<?= base_url('index.php') ?>">&larr; Back to admin login</a></p>
        <p class="login-disclaimer">Not officially affiliated with the Boy Scouts of the Philippines. For educational / school use only.</p>
    </div>
</body>
</html>
