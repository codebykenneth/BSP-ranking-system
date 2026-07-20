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
}

header('Location: ' . base_url('pages/attendance_approvals.php?approved=1'));
exit;
