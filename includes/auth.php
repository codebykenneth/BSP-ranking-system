
<?php
date_default_timezone_set('Asia/Manila');
/**
 * Session bootstrap + login guard.
 * Include this at the very top of every protected page.
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function is_logged_in(): bool
{
    return isset($_SESSION['admin_id']);
}

function require_login(): void
{
    if (!is_logged_in()) {
        header('Location: ' . base_url('index.php'));
        exit;
    }
}

function is_scout_logged_in(): bool
{
    return isset($_SESSION['scout_id']);
}

function require_scout_login(): void
{
    if (!is_scout_logged_in()) {
        header('Location: ' . base_url('scout_login.php'));
        exit;
    }
}

/**
 * Builds a link back to the project root regardless of which
 * sub-folder (pages/) the current script lives in.
 */
function base_url(string $path = ''): string
{
    return '/' . ltrim($path, '/');
}
