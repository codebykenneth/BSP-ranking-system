<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/config/database.php';

if (is_logged_in()) {
    header('Location: ' . base_url('pages/dashboard.php'));
    exit;
}

$errors = [];
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    if ($username === '') {
        $errors[] = 'Username is required.';
    } elseif (strlen($username) < 3) {
        $errors[] = 'Username must be at least 3 characters.';
    }

    if ($password === '') {
        $errors[] = 'Password is required.';
    } elseif (strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters.';
    }

    if ($password !== $confirm) {
        $errors[] = 'Passwords do not match.';
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            $errors[] = 'That username is already taken.';
        }
    }

    if (empty($errors)) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
        $stmt->execute([$username, $hash]);

        $_SESSION['login_success'] = 'Account created! You can now log in.';
        header('Location: ' . base_url('index.php'));
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Register · BSP Ranking System</title>
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
            <h1>Create Account</h1>
            <p>Register a new admin account for the Ranking System.</p>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-error">
                <ul>
                    <?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form action="register.php" method="POST" class="login-form">
            <label for="username">Username</label>
            <input type="text" id="username" name="username" required autofocus
                   value="<?= e($username) ?>" placeholder="Choose a username">

            <label for="password">Password</label>
            <input type="password" id="password" name="password" required
                   placeholder="At least 6 characters">

            <label for="confirm_password">Confirm Password</label>
            <input type="password" id="confirm_password" name="confirm_password" required
                   placeholder="Re-enter password">

            <button type="submit" class="btn btn-primary btn-block">Register</button>
        </form>

        <p class="login-hint">Already have an account? <a href="<?= base_url('index.php') ?>">Log in</a></p>
        <p class="login-disclaimer">Not officially affiliated with the Boy Scouts of the Philippines. For educational / school use only.</p>
    </div>
</body>
</html>
