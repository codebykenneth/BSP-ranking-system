<?php
/**
 * Shared helper functions used across the system.
 */

// Official BSP rank progression -> numeric level used in the score formula.
const RANK_LEVELS = [
    'Explorer'            => 1,
    'Pathfinder'         => 2,
    'Outdorsman '          => 3,
    'Venturer'               => 4,
    'Sea Manship'         => 5,
    'Air Manship'         => 6,
    'Eagle Scout'         => 7,
];

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Renders a scout's avatar: their uploaded photo if present,
 * otherwise a circle with their initials.
 * $size should be a CSS class suffix: 'sm', 'md', or 'lg'.
 */
function scout_avatar(array $scout, string $size = 'md'): string
{
    $name = $scout['name'] ?? '';
    $initials = '';
    foreach (preg_split('/\s+/', trim($name)) as $part) {
        if ($part !== '') {
            $initials .= strtoupper(substr($part, 0, 1));
        }
        if (strlen($initials) >= 2) break;
    }
    $initials = $initials !== '' ? $initials : '?';

    if (!empty($scout['photo'])) {
        $src = e(base_url($scout['photo']));
        return '<img src="' . $src . '" alt="' . e($name) . '" class="avatar avatar-' . e($size) . '">';
    }

    return '<span class="avatar avatar-' . e($size) . ' avatar-initials">' . e($initials) . '</span>';
}

/**
 * Total activity points earned by a single scout.
 */
function get_total_points(PDO $pdo, int $scoutId): int
{
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(points),0) AS total FROM activities WHERE scout_id = ?");
    $stmt->execute([$scoutId]);
    return (int) $stmt->fetchColumn();
}

/**
 * Computed attendance % for one scout from real event records.
 * Present = full credit, Late = half credit, Absent = none,
 * Excused events don't count against them (excluded from the denominator).
 * Falls back to the scout's manually-set `attendance` field if they have
 * no attendance records yet (e.g. newly added, or before the attendance
 * system existed).
 */
function get_attendance_percentage(PDO $pdo, int $scoutId, float $fallback = 0): float
{
    try {
        $stmt = $pdo->prepare(
            "SELECT
                SUM(CASE WHEN status IN ('Present','Late','Absent') THEN 1 ELSE 0 END) AS countable,
                SUM(CASE WHEN status = 'Present' THEN 1 WHEN status = 'Late' THEN 0.5 ELSE 0 END) AS weight
             FROM attendance WHERE scout_id = ?"
        );
        $stmt->execute([$scoutId]);
        $row = $stmt->fetch();
    } catch (PDOException $e) {
        // attendance table doesn't exist yet (migration not run) — use the manual fallback.
        return round($fallback, 2);
    }

    if (empty($row) || (int) $row['countable'] === 0) {
        return round($fallback, 2);
    }

    return round(((float) $row['weight'] / (int) $row['countable']) * 100, 2);
}

/**
 * Ranking formula:
 * Total Score = (Activity Points x 0.5) + (Rank Level x 0.3) + (Attendance x 0.2)
 */
function calculate_score(float $activityPoints, int $rankLevel, float $attendance): float
{
    return round(($activityPoints * 0.5) + ($rankLevel * 0.3) + ($attendance * 0.2), 2);
}

/**
 * Returns every scout with total points + computed attendance % + score,
 * sorted from highest to lowest score.
 */
function get_ranked_scouts(PDO $pdo): array
{
    $sql = "SELECT s.id, s.name, s.troop, s.rank_name, s.rank_level, s.attendance AS manual_attendance, s.photo,
                   COALESCE(ap.total_points, 0) AS activity_points,
                   at.countable AS attendance_countable,
                   at.weight AS attendance_weight
            FROM scouts s
            LEFT JOIN (SELECT scout_id, SUM(points) AS total_points FROM activities GROUP BY scout_id) ap
                   ON ap.scout_id = s.id";

    try {
        $pdo->query("SELECT 1 FROM attendance LIMIT 1");
        $sql .= " LEFT JOIN (SELECT scout_id,
                                SUM(CASE WHEN status IN ('Present','Late','Absent') THEN 1 ELSE 0 END) AS countable,
                                SUM(CASE WHEN status = 'Present' THEN 1 WHEN status = 'Late' THEN 0.5 ELSE 0 END) AS weight
                              FROM attendance GROUP BY scout_id) at
                         ON at.scout_id = s.id";
        $hasAttendanceTable = true;
    } catch (PDOException $e) {
        $hasAttendanceTable = false;
    }

    $rows = $pdo->query($sql)->fetchAll();

    foreach ($rows as &$row) {
        $manual = (float) $row['manual_attendance'];
        if ($hasAttendanceTable && !empty($row['attendance_countable'])) {
            $attendancePct = round(((float) $row['attendance_weight'] / (int) $row['attendance_countable']) * 100, 2);
        } else {
            $attendancePct = round($manual, 2);
        }
        $row['attendance'] = $attendancePct;
        $row['score'] = calculate_score((float) $row['activity_points'], (int) $row['rank_level'], $attendancePct);
    }
    unset($row);

    usort($rows, fn($a, $b) => $b['score'] <=> $a['score']);

    return $rows;
}

/**
 * Scouts whose computed attendance % falls below the given threshold.
 * Useful for a "needs attention" dashboard widget.
 */
function get_low_attendance_scouts(PDO $pdo, float $threshold = 75): array
{
    $ranked = get_ranked_scouts($pdo);
    return array_values(array_filter($ranked, fn($s) => $s['attendance'] < $threshold));
}

/**
 * Full per-event attendance history for one scout, most recent first.
 */
function get_scout_attendance_history(PDO $pdo, int $scoutId): array
{
    $stmt = $pdo->prepare(
        "SELECT e.title, e.event_date, att.status
         FROM attendance att
         JOIN events e ON e.id = att.event_id
         WHERE att.scout_id = ?
         ORDER BY e.event_date DESC"
    );
    $stmt->execute([$scoutId]);
    return $stmt->fetchAll();
}

/**
 * Most recent admin announcements, for the scout portal.
 * Returns [] instead of erroring if the announcements table
 * hasn't been created yet (migration not run).
 */
function get_recent_announcements(PDO $pdo, int $limit = 5): array
{
    try {
        $stmt = $pdo->prepare("SELECT * FROM announcements ORDER BY created_at DESC LIMIT ?");
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Count of scout self-submitted attendance rows still awaiting admin approval.
 */
function get_pending_attendance_count(PDO $pdo): int
{
    try {
        return (int) $pdo->query("SELECT COUNT(*) FROM attendance WHERE submission_status = 'pending'")->fetchColumn();
    } catch (PDOException $e) {
        return 0;
    }
}

/**
 * Every pending (scout self-submitted, unapproved) attendance row, with the
 * scout and event info joined in, oldest submission first.
 */
function get_pending_attendance(PDO $pdo): array
{
    try {
        $stmt = $pdo->query(
            "SELECT att.id, att.status, att.excuse_reason, att.submitted_at,
                    e.id AS event_id, e.title AS event_title, e.event_date, e.call_time,
                    s.id AS scout_id, s.name AS scout_name, s.photo
             FROM attendance att
             JOIN events e ON e.id = att.event_id
             JOIN scouts s ON s.id = att.scout_id
             WHERE att.submission_status = 'pending'
             ORDER BY att.submitted_at ASC"
        );
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Events happening today, soonest call time first (events with no call
 * time set are listed last).
 */
function get_todays_events(PDO $pdo): array
{
    $stmt = $pdo->query("SELECT * FROM events WHERE event_date = CURRENT_DATE ORDER BY call_time ASC NULLS LAST");
    return $stmt->fetchAll();
}

/**
 * One scout's attendance row for a specific event, or null if they
 * haven't submitted / been marked yet.
 */
function get_scout_attendance_for_event(PDO $pdo, int $eventId, int $scoutId): ?array
{
    $stmt = $pdo->prepare("SELECT * FROM attendance WHERE event_id = ? AND scout_id = ?");
    $stmt->execute([$eventId, $scoutId]);
    $row = $stmt->fetch();
    return $row ?: null;
}

/**
 * Keep the activities table in sync with a single attendance record's
 * current status. Present/Late earns points automatically; anything else
 * (Absent/Excused) removes any previously-awarded entry. Safe to call
 * repeatedly — it always deletes the old auto-entry first, so overriding
 * a status (e.g. admin corrects Present -> Absent) never double-counts.
 */
function sync_attendance_points(
    PDO $pdo,
    int $attendanceId,
    string $status,
    int $scoutId,
    string $eventTitle,
    string $eventDate,
    int $pointsPerAttendance = 5
): void {
    $del = $pdo->prepare("DELETE FROM activities WHERE attendance_id = ?");
    $del->execute([$attendanceId]);

    if (in_array($status, ['Present', 'Late'], true)) {
        $ins = $pdo->prepare(
            "INSERT INTO activities (scout_id, activity_name, points, activity_date, attendance_id)
             VALUES (?, ?, ?, ?, ?)"
        );
        $ins->execute([$scoutId, $eventTitle, $pointsPerAttendance, $eventDate, $attendanceId]);
    }
}

/**
 * Point values for attendance status — used for the scout's points-based view.
 */
const ATTENDANCE_POINTS = [
    'Present' => 10,
    'Late'    => 8,
    'Excused' => 0,
    'Absent'  => 0,
];

/**
 * A scout's activity log with individual point values (for the "how did I earn this" view).
 */
function get_scout_activities(PDO $pdo, int $scoutId): array
{
    $stmt = $pdo->prepare("SELECT * FROM activities WHERE scout_id = ? ORDER BY created_at DESC");
    $stmt->execute([$scoutId]);
    return $stmt->fetchAll();
}

/**
 * Attendance history with the new point value attached to each record, plus a running total.
 */
function get_scout_attendance_with_points(PDO $pdo, int $scoutId): array
{
    $history = get_scout_attendance_history($pdo, $scoutId);
    $total = 0;
    foreach ($history as &$row) {
        $pts = ATTENDANCE_POINTS[$row['status']] ?? 0;
        $row['points'] = $pts;
        $total += $pts;
    }
    unset($row);
    return ['records' => $history, 'total' => $total];
}

/**
 * Combined progress score out of 500: activity points + attendance points + rank bonus.
 */
function calculate_progress_score(float $activityPoints, float $attendancePoints, int $rankLevel): float
{
    $score = $activityPoints + $attendancePoints + ($rankLevel * 10);
    return min(500, round($score, 2));
}

/**
 * Full troop leaderboard: every scout's name + combined points, highest first.
 */
function get_troop_leaderboard(PDO $pdo): array
{
    $scouts = $pdo->query("SELECT id, name, rank_level FROM scouts")->fetchAll();

    $leaderboard = [];
    foreach ($scouts as $s) {
        $activityPoints = get_total_points($pdo, (int) $s['id']);
        $attRows = get_scout_attendance_history($pdo, (int) $s['id']);
        $attendancePoints = 0;
        foreach ($attRows as $r) {
            $attendancePoints += ATTENDANCE_POINTS[$r['status']] ?? 0;
        }
        $leaderboard[] = [
            'id' => (int) $s['id'],
            'name' => $s['name'],
            'points' => calculate_progress_score($activityPoints, $attendancePoints, (int) $s['rank_level']),
        ];
    }

    usort($leaderboard, fn($a, $b) => $b['points'] <=> $a['points']);
    return $leaderboard;
}
