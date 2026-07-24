<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . base_url('pages/attendance_approvals.php'));
    exit;
}

$attendanceId = (int) ($_POST['attendance_id'] ?? 0);
$status = $_POST['status'] ?? '';

if ($attendanceId && in_array($status, ['Present', 'Late', 'Absent', 'Excused'], true)) {
    $stmt = $pdo->prepare("UPDATE attendance SET status = ?, submission_status = 'confirmed' WHERE id = ?");
    $stmt->execute([$status, $attendanceId]);

    $info = $pdo->prepare(
        "SELECT a.scout_id, e.title AS event_title, e.event_date
         FROM attendance a JOIN events e ON e.id = a.event_id
         WHERE a.id = ?"
    );
    $info->execute([$attendanceId]);
    $row = $info->fetch();
    if ($row) {
        sync_attendance_points($pdo, $attendanceId, $status, (int) $row['scout_id'], $row['event_title'], $row['event_date']);
    }
}

header('Location: ' . base_url('pages/attendance_approvals.php?approved=1'));
exit;
