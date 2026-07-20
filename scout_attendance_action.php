<?php
require_once __DIR__ . '/includes/auth.php';
require_scout_login();
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . base_url('scout_attendance.php'));
    exit;
}

$scoutId = (int) $_SESSION['scout_id'];
$eventId = (int) ($_POST['event_id'] ?? 0);
$action  = $_POST['action'] ?? '';

$stmt = $pdo->prepare("SELECT * FROM events WHERE id = ?");
$stmt->execute([$eventId]);
$event = $stmt->fetch();

if (!$event) {
    $_SESSION['attendance_error'] = 'That activity could not be found.';
    header('Location: ' . base_url('scout_attendance.php'));
    exit;
}

// A scout can only submit once per event — after that they wait for admin approval.
$existingStmt = $pdo->prepare("SELECT id FROM attendance WHERE event_id = ? AND scout_id = ?");
$existingStmt->execute([$eventId, $scoutId]);
if ($existingStmt->fetch()) {
    $_SESSION['attendance_error'] = 'You already submitted attendance for this activity.';
    header('Location: ' . base_url('scout_attendance.php'));
    exit;
}

function bsp_submit_attendance(PDO $pdo, int $eventId, int $scoutId, string $status, ?string $reason): void
{
    $stmt = $pdo->prepare(
        "INSERT INTO attendance (event_id, scout_id, status, submission_status, excuse_reason, submitted_at, submitted_by_scout)
         VALUES (?, ?, ?, 'pending', ?, NOW(), TRUE)
         ON CONFLICT (event_id, scout_id) DO UPDATE SET
            status = EXCLUDED.status,
            submission_status = 'pending',
            excuse_reason = EXCLUDED.excuse_reason,
            submitted_at = NOW(),
            submitted_by_scout = TRUE"
    );
    $stmt->execute([$eventId, $scoutId, $status, $reason]);
}

if ($action === 'checkin') {
    $isLate = false;
    if (!empty($event['call_time'])) {
        $callDateTime = new DateTime($event['event_date'] . ' ' . $event['call_time']);
        $now = new DateTime();
        if ($now > $callDateTime) {
            $isLate = true;
        }
    }

    if ($isLate) {
        // Don't record anything yet — send them back to type a reason first.
        header('Location: ' . base_url('scout_attendance.php?late=' . $eventId));
        exit;
    }

    bsp_submit_attendance($pdo, $eventId, $scoutId, 'Present', null);
    $_SESSION['attendance_success'] = "Checked in! Waiting for your Troop Leader's confirmation.";
    header('Location: ' . base_url('scout_attendance.php'));
    exit;
}

if ($action === 'late_confirm') {
    $reason = trim($_POST['reason'] ?? '');
    if ($reason === '') {
        $_SESSION['attendance_error'] = 'Please enter a reason for being late.';
        header('Location: ' . base_url('scout_attendance.php?late=' . $eventId));
        exit;
    }
    bsp_submit_attendance($pdo, $eventId, $scoutId, 'Late', $reason);
    $_SESSION['attendance_success'] = 'Late attendance submitted for approval.';
    header('Location: ' . base_url('scout_attendance.php'));
    exit;
}

if ($action === 'excuse') {
    $reason = trim($_POST['reason'] ?? '');
    if ($reason === '') {
        $_SESSION['attendance_error'] = 'Please enter a reason.';
        header('Location: ' . base_url('scout_attendance.php'));
        exit;
    }
    bsp_submit_attendance($pdo, $eventId, $scoutId, 'Excused', $reason);
    $_SESSION['attendance_success'] = 'Excuse submitted for approval.';
    header('Location: ' . base_url('scout_attendance.php'));
    exit;
}

header('Location: ' . base_url('scout_attendance.php'));
exit;
