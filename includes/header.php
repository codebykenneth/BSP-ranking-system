<?php
/**
 * Expects (optionally) $pageTitle to be set before include.
 */
$pageTitle = $pageTitle ?? 'BSP Ranking System';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($pageTitle) ?> · BSP Ranking System</title>
<?php
$cssPath = __DIR__ . '/../assets/css/style.css';
$cssVersion = file_exists($cssPath) ? filemtime($cssPath) : time();
?>
<link rel="stylesheet" href="<?= base_url('assets/css/style.css') ?>?v=<?= $cssVersion ?>">
</head>
<body>
<div class="app-shell">
