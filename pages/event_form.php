<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$errors = [];
$mode = 'single';

// Single-event field defaults
$title = '';
$eventDate = date('Y-m-d');
$callTime = '';

// Recurring field defaults
$recTitle = '';
$recStart = date('Y-m-d');
$recEnd   = date('Y-m-d', strtotime('+4 weeks'));
$recCallTime = '';
$recDays = ['1', '2', '3', '4', '5']; // Mon-Fri by default (0=Sun..6=Sat)

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mode = $_POST['mode'] ?? 'single';

    if ($mode === 'recurring') {
        $recTitle    = trim($_POST['rec_title'] ?? '');
        $recStart    = $_POST['rec_start'] ?? date('Y-m-d');
        $recEnd      = $_POST['rec_end'] ?? date('Y-m-d');
        $recCallTime = trim($_POST['rec_call_time'] ?? '');
        $recDays     = $_POST['rec_days'] ?? [];

        if ($recTitle === '') $errors[] = 'Event name is required.';
        if (empty($recStart) || empty($recEnd)) $errors[] = 'Start and end dates are required.';
        if (empty($recDays)) $errors[] = 'Pick at least one day of the week.';
        if (!empty($recStart) && !empty($recEnd) && strtotime($recEnd) < strtotime($recStart)) {
            $errors[] = 'End date must be on or after the start date.';
        }
        // Cap the range so a typo (e.g. wrong year) can't silently create hundreds of events.
        if (!empty($recStart) && !empty($recEnd) && (strtotime($recEnd) - strtotime($recStart)) > (366 * 86400)) {
            $errors[] = 'That range is longer than a year &mdash; double check your dates.';
        }

        if (empty($errors)) {
            $created = 0;
            $checkStmt = $pdo->prepare("SELECT id FROM events WHERE title = ? AND event_date = ?");
            $insertStmt = $pdo->prepare("INSERT INTO events (title, event_date, checkin_code, call_time) VALUES (?, ?, ?, ?)");

            $current = new DateTime($recStart);
            $end     = new DateTime($recEnd);
            $end->modify('+1 day'); // make end date inclusive

            while ($current < $end) {
                $dow = $current->format('w'); // 0=Sun..6=Sat
                if (in_array($dow, $recDays, true)) {
                    $dateStr = $current->format('Y-m-d');
                    $checkStmt->execute([$recTitle, $dateStr]);
                    if (!$checkStmt->fetch()) {
                        $checkinCode = strtoupper(substr(bin2hex(random_bytes(4)), 0, 6));
                        $insertStmt->execute([$recTitle, $dateStr, $checkinCode, $recCallTime !== '' ? $recCallTime : null]);
                        $created++;
                    }
                }
                $current->modify('+1 day');
            }

            header('Location: ' . base_url('pages/events.php?saved=1&count=' . $created));
            exit;
        }
    } else {
        $title     = trim($_POST['title'] ?? '');
        $eventDate = $_POST['event_date'] ?? date('Y-m-d');
        $callTime  = trim($_POST['call_time'] ?? '');

        if ($title === '') $errors[] = 'Event name is required.';
        if (empty($eventDate)) $errors[] = 'Date is required.';

        if (empty($errors)) {
            $checkinCode = strtoupper(substr(bin2hex(random_bytes(4)), 0, 6));
            $stmt = $pdo->prepare("INSERT INTO events (title, event_date, checkin_code, call_time) VALUES (?, ?, ?, ?)");
            $stmt->execute([$title, $eventDate, $checkinCode, $callTime !== '' ? $callTime : null]);
            header('Location: ' . base_url('pages/events.php?saved=1'));
            exit;
        }
    }
}

$pageTitle   = 'Add Event';
$currentPage = 'events';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';

$dayLabels = ['0' => 'Sun', '1' => 'Mon', '2' => 'Tue', '3' => 'Wed', '4' => 'Thu', '5' => 'Fri', '6' => 'Sat'];
?>
<main class="main-content">
    <div class="content-header">
        <h1>Add New Event</h1>
        <p>Create a single meeting, or set up a recurring schedule (e.g. every weekday) in one go.</p>
    </div>

    <section class="panel panel-narrow">
        <?php if (!empty($errors)): ?>
            <div class="alert alert-error">
                <ul>
                    <?php foreach ($errors as $err): ?><li><?= $err ?></li><?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <div class="event-mode-tabs">
            <button type="button" class="mode-tab <?= $mode === 'single' ? 'active' : '' ?>" data-mode="single">Single Event</button>
            <button type="button" class="mode-tab <?= $mode === 'recurring' ? 'active' : '' ?>" data-mode="recurring">Recurring Schedule</button>
        </div>

        <!-- SINGLE EVENT -->
        <form method="POST" class="stacked-form mode-panel" id="panel-single" style="<?= $mode === 'recurring' ? 'display:none;' : '' ?>">
            <input type="hidden" name="mode" value="single">

            <label for="title">Event Name</label>
            <input type="text" id="title" name="title" value="<?= e($title) ?>" placeholder="e.g. Weekly Troop Meeting">

            <label for="event_date">Date</label>
            <input type="date" id="event_date" name="event_date" value="<?= e($eventDate) ?>">

            <label for="call_time">Call Time <span style="font-weight:400;color:var(--ink-soft);">(optional &mdash; time scouts are expected to be present by, used for scout self check-in)</span></label>
            <input type="time" id="call_time" name="call_time" value="<?= e($callTime) ?>">

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Add Event</button>
                <a href="<?= base_url('pages/events.php') ?>" class="btn btn-ghost">Cancel</a>
            </div>
        </form>

        <!-- RECURRING SCHEDULE -->
        <form method="POST" class="stacked-form mode-panel" id="panel-recurring" style="<?= $mode === 'recurring' ? '' : 'display:none;' ?>">
            <input type="hidden" name="mode" value="recurring">

            <label for="rec_title">Event Name</label>
            <input type="text" id="rec_title" name="rec_title" value="<?= e($recTitle) ?>" placeholder="e.g. Practice">

            <div class="form-row">
                <div>
                    <label for="rec_start">Start Date</label>
                    <input type="date" id="rec_start" name="rec_start" value="<?= e($recStart) ?>">
                </div>
                <div>
                    <label for="rec_end">End Date</label>
                    <input type="date" id="rec_end" name="rec_end" value="<?= e($recEnd) ?>">
                </div>
            </div>

            <label for="rec_call_time">Call Time <span style="font-weight:400;color:var(--ink-soft);">(optional, applies to every date created)</span></label>
            <input type="time" id="rec_call_time" name="rec_call_time" value="<?= e($recCallTime) ?>">

            <label>Repeat On</label>
            <div class="day-picker">
                <?php foreach ($dayLabels as $val => $label): ?>
                    <label class="day-chip">
                        <input type="checkbox" name="rec_days[]" value="<?= $val ?>" <?= in_array($val, $recDays, true) ? 'checked' : '' ?>>
                        <span><?= $label ?></span>
                    </label>
                <?php endforeach; ?>
            </div>
            <p style="font-size:12px;color:var(--ink-soft);margin-top:-6px;">Only the checked days get events &mdash; e.g. leave Sat/Sun unchecked and nothing is created (or attendable) on weekends.</p>

            <label>Preview</label>
            <div id="rec-preview" class="rec-preview">
                <p class="empty-state" style="padding:14px;">Pick dates and days above to see what will be created.</p>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary" id="rec-submit">Create Events</button>
                <a href="<?= base_url('pages/events.php') ?>" class="btn btn-ghost">Cancel</a>
            </div>
        </form>
    </section>
</main>

<style>
.event-mode-tabs { display: flex; gap: 8px; margin-bottom: 22px; border-bottom: 1px solid var(--border); }
.mode-tab {
    background: none; border: none; cursor: pointer; padding: 10px 4px 12px;
    font-size: 14px; font-weight: 600; color: var(--ink-soft);
    border-bottom: 2px solid transparent; margin-right: 18px;
}
.mode-tab.active { color: var(--forest); border-bottom-color: var(--forest); }
.form-row { display: flex; gap: 16px; }
.form-row > div { flex: 1; }
.day-picker { display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 10px; }
.day-chip {
    display: flex; align-items: center; gap: 6px; padding: 8px 12px;
    border: 1px solid var(--border); border-radius: 999px; cursor: pointer; font-size: 13px;
}
.day-chip input { margin: 0; }
.rec-preview {
    max-height: 220px; overflow-y: auto; border: 1px solid var(--border);
    border-radius: 8px; margin-bottom: 16px; background: var(--khaki-light);
}
.rec-preview ul { list-style: none; margin: 0; padding: 0; }
.rec-preview li {
    padding: 8px 14px; font-size: 13px; border-bottom: 1px solid var(--border);
    display: flex; justify-content: space-between;
}
.rec-preview li:last-child { border-bottom: none; }
.rec-preview .rec-count {
    padding: 8px 14px; font-size: 12px; font-weight: 700; color: var(--forest);
    background: var(--white); border-bottom: 1px solid var(--border);
}
</style>

<script>
(function () {
    var tabs = document.querySelectorAll('.mode-tab');
    var panels = { single: document.getElementById('panel-single'), recurring: document.getElementById('panel-recurring') };

    tabs.forEach(function (tab) {
        tab.addEventListener('click', function () {
            tabs.forEach(function (t) { t.classList.remove('active'); });
            tab.classList.add('active');
            var mode = tab.getAttribute('data-mode');
            panels.single.style.display = mode === 'single' ? '' : 'none';
            panels.recurring.style.display = mode === 'recurring' ? '' : 'none';
        });
    });

    // Live preview for the recurring form
    var startInput = document.getElementById('rec_start');
    var endInput = document.getElementById('rec_end');
    var dayChecks = document.querySelectorAll('input[name="rec_days[]"]');
    var previewBox = document.getElementById('rec-preview');
    var dayNames = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];

    function renderPreview() {
        var start = startInput.value, end = endInput.value;
        var checked = Array.prototype.filter.call(dayChecks, function (c) { return c.checked; })
            .map(function (c) { return parseInt(c.value, 10); });

        if (!start || !end || checked.length === 0) {
            previewBox.innerHTML = '<p class="empty-state" style="padding:14px;">Pick dates and days above to see what will be created.</p>';
            return;
        }

        var startDate = new Date(start + 'T00:00:00');
        var endDate = new Date(end + 'T00:00:00');
        if (endDate < startDate) {
            previewBox.innerHTML = '<p class="empty-state" style="padding:14px;">End date is before the start date.</p>';
            return;
        }

        var dates = [];
        var cursor = new Date(startDate.getTime());
        var safety = 0;
        while (cursor <= endDate && safety < 400) {
            if (checked.indexOf(cursor.getDay()) !== -1) {
                dates.push(new Date(cursor.getTime()));
            }
            cursor.setDate(cursor.getDate() + 1);
            safety++;
        }

        if (dates.length === 0) {
            previewBox.innerHTML = '<p class="empty-state" style="padding:14px;">No matching dates in that range.</p>';
            return;
        }

        var html = '<div class="rec-count">' + dates.length + ' event' + (dates.length === 1 ? '' : 's') + ' will be created</div><ul>';
        dates.forEach(function (d) {
            var label = dayNames[d.getDay()] + ', ' + d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
            html += '<li><span>' + label + '</span></li>';
        });
        html += '</ul>';
        previewBox.innerHTML = html;
    }

    [startInput, endInput].forEach(function (el) { el.addEventListener('change', renderPreview); });
    dayChecks.forEach(function (el) { el.addEventListener('change', renderPreview); });
    renderPreview();
})();
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
