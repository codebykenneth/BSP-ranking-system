<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$pending = get_pending_attendance($pdo);

$pageTitle   = 'Attendance Approvals';
$currentPage = 'approvals';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>
<main class="main-content">
    <div class="content-header">
        <h1>Attendance Approvals</h1>
        <p>Scouts who checked themselves in or sent an excuse show up here until you confirm what actually happened.</p>
    </div>

    <?php if (isset($_GET['approved'])): ?>
        <div class="alert alert-success">Attendance approved.</div>
    <?php endif; ?>

    <section class="panel">
        <?php if (empty($pending)): ?>
            <p class="empty-state">No pending approvals right now.</p>
        <?php else: ?>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Scout</th>
                        <th>Activity</th>
                        <th>Submitted</th>
                        <th>Reported Status</th>
                        <th>Reason</th>
                        <th>Decision</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pending as $p): ?>
                        <tr>
                            <td>
                                <div class="name-cell">
                                    <?= scout_avatar(['name' => $p['scout_name'], 'photo' => $p['photo']], 'sm') ?>
                                    <span><?= e($p['scout_name']) ?></span>
                                </div>
                            </td>
                            <td>
                                <?= e($p['event_title']) ?>
                                <div style="font-size:12px;color:var(--ink-soft);">
                                    <?= e(date('M j, Y', strtotime($p['event_date']))) ?>
                                    <?php if (!empty($p['call_time'])): ?>
                                        &middot; Call: <?= e(date('g:i A', strtotime($p['call_time']))) ?>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td><?= e(date('M j, g:i A', strtotime($p['submitted_at']))) ?></td>
                            <td><span class="status-pill status-<?= strtolower($p['status']) ?>"><?= e($p['status']) ?></span></td>
                            <td><?= $p['excuse_reason'] ? e($p['excuse_reason']) : '&mdash;' ?></td>
                            <td>
                                <form method="POST" action="<?= base_url('pages/attendance_approve_action.php') ?>" class="approval-form">
                                    <input type="hidden" name="attendance_id" value="<?= (int) $p['id'] ?>">
                                    <select name="status">
                                        <?php foreach (['Present', 'Late', 'Absent', 'Excused'] as $st): ?>
                                            <option value="<?= $st ?>" <?= $p['status'] === $st ? 'selected' : '' ?>><?= $st ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="submit" class="btn btn-small btn-primary">Approve</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </section>
</main>
<style>
.approval-form { display: flex; gap: 8px; align-items: center; }
.approval-form select { padding: 6px 8px; border-radius: 6px; border: 1px solid var(--border); }
</style>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
