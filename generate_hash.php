<?php
/**
 * One-off utility: generates a password_hash() value you can paste into
 * the `users` table via phpMyAdmin to set/change an admin password.
 *
 * Usage: open http://localhost/bsp-ranking-system/generate_hash.php?password=yourNewPassword
 * Delete this file once you're done using it — it should not stay on a live server.
 */
$password = $_GET['password'] ?? null;
?>
<!DOCTYPE html>
<html><head><title>Password Hash Generator</title>
<style>body{font-family:sans-serif;max-width:600px;margin:60px auto;padding:0 20px;}
code{background:#eee;padding:8px;display:block;word-break:break-all;border-radius:6px;}</style>
</head><body>
<h2>Password Hash Generator</h2>
<form method="GET">
    <input type="text" name="password" placeholder="Enter a new password" style="padding:8px;width:70%;" value="<?= htmlspecialchars($password ?? '') ?>">
    <button type="submit" style="padding:8px 14px;">Generate</button>
</form>
<?php if ($password): ?>
    <p>Hash for <strong><?= htmlspecialchars($password) ?></strong>:</p>
    <code><?= password_hash($password, PASSWORD_DEFAULT) ?></code>
    <p>Copy this into the <code>password</code> column of the <code>users</code> table for the relevant admin row.</p>
<?php endif; ?>
<p style="color:#a00;margin-top:30px;">⚠️ Delete this file after use.</p>
</body></html>
