<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . base_url('scout_login.php'));
    exit;
}

$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

if ($username === '' || $password === '') {
    $_SESSION['scout_login_error'] = 'Please enter both username and password.';
    header('Location: ' . base_url('scout_login.php'));
    exit;
}

$stmt = $pdo->prepare("SELECT id, name, username, password FROM scouts WHERE username = ? LIMIT 1");
$stmt->execute([$username]);
$scout = $stmt->fetch();

if ($scout && $scout['password'] && password_verify($password, $scout['password'])) {
    session_regenerate_id(true);
    $_SESSION['scout_id']   = $scout['id'];
    $_SESSION['scout_name'] = $scout['name'];
    header('Location: ' . base_url('scout_portal.php'));
    exit;
}

$_SESSION['scout_login_error'] = 'Invalid username or password.';
header('Location: ' . base_url('scout_login.php'));
exit;
