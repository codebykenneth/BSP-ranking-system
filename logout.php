<?php
require_once __DIR__ . '/includes/auth.php';

$_SESSION = [];
session_destroy();

session_start();
$_SESSION['login_error'] = '';
header('Location: ' . base_url('index.php'));
exit;
