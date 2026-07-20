<?php
$currentPage = $currentPage ?? '';
$pendingApprovalsCount = isset($pdo) ? get_pending_attendance_count($pdo) : 0;
$navItems = [
    'dashboard'   => ['label' => 'Dashboard',   'icon' => '&#9733;', 'href' => 'pages/dashboard.php'],
    'scouts'      => ['label' => 'Scouts',      'icon' => '&#9878;', 'href' => 'pages/scouts.php'],
    'activities'  => ['label' => 'Activities',  'icon' => '&#127942;', 'href' => 'pages/activities.php'],
    'events'      => ['label' => 'Events',      'icon' => '&#128197;', 'href' => 'pages/events.php'],
    'approvals'   => ['label' => 'Approvals',   'icon' => '&#128276;', 'href' => 'pages/attendance_approvals.php', 'badge' => $pendingApprovalsCount],
    'announcements' => ['label' => 'Announcements', 'icon' => '&#128226;', 'href' => 'pages/announcements.php'],
    'leaderboard' => ['label' => 'Leaderboard', 'icon' => '&#128202;', 'href' => 'pages/leaderboard.php'],
    'reports'     => ['label' => 'Reports',     'icon' => '&#128196;', 'href' => 'pages/reports.php'],
];
?>
<aside class="sidebar">
    <div class="sidebar-brand">
        <span class="brand-mark">BSP</span>
        <div>
            <strong>Ranking System</strong>
            <small>Troop Management</small>
        </div>
    </div>

    <nav class="sidebar-nav">
        <?php foreach ($navItems as $key => $item): ?>
            <a href="<?= base_url($item['href']) ?>"
               class="nav-link <?= $currentPage === $key ? 'active' : '' ?>">
                <span class="nav-icon"><?= $item['icon'] ?></span>
                <span><?= e($item['label']) ?></span>
                <?php if (!empty($item['badge'])): ?>
                    <span class="nav-badge"><?= (int) $item['badge'] ?></span>
                <?php endif; ?>
            </a>
        <?php endforeach; ?>
    </nav>

    <div class="sidebar-footer">
        <div class="admin-chip">
            <span class="admin-avatar"><?= e(strtoupper(substr($_SESSION['admin_username'] ?? 'A', 0, 1))) ?></span>
            <span><?= e($_SESSION['admin_username'] ?? 'Admin') ?></span>
        </div>
        <a href="<?= base_url('logout.php') ?>" class="logout-link">Log out</a>
    </div>
</aside>
