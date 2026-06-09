<?php
// /admin/crm/calendar.php
require_once __DIR__ . "/_auth.php";
require_once __DIR__ . "/_db.php";
require_once __DIR__ . "/_helpers.php";

$page_title = "Calendar";
require_once __DIR__ . "/_layout_top.php";

// Get current month/year
$month = isset($_GET['month']) ? (int)$_GET['month'] : date('n');
$year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');

// Validate month/year
if ($month < 1) { $month = 12; $year--; }
if ($month > 12) { $month = 1; $year++; }

// Get first day of month
$first_day = mktime(0, 0, 0, $month, 1, $year);
$days_in_month = date('t', $first_day);
$first_day_of_week = date('w', $first_day); // 0 = Sunday, 1 = Monday, etc.

// Adjust to start week on Monday
if ($first_day_of_week == 0) $first_day_of_week = 7;
$first_day_of_week--; // Now 0 = Monday, 6 = Sunday

// Get tasks for this month
$start_date = date('Y-m-01', $first_day);
$end_date = date('Y-m-t', $first_day);

$sql = "
    SELECT 
        t.*,
        l.name as lead_name,
        DATE(t.due_at) as due_date
    FROM crm_tasks t
    LEFT JOIN crm_leads l ON l.id = t.lead_id
    WHERE t.status = 'open' 
        AND DATE(t.due_at) BETWEEN ? AND ?
    ORDER BY t.due_at ASC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$tasks_result = $stmt->get_result();

$tasks_by_day = [];
while ($task = $tasks_result->fetch_assoc()) {
    $day = date('j', strtotime($task['due_date']));
    if (!isset($tasks_by_day[$day])) {
        $tasks_by_day[$day] = [];
    }
    $tasks_by_day[$day][] = $task;
}
$stmt->close();

// Get activities for this month
$sql = "
    SELECT 
        a.*,
        l.name as lead_name,
        DATE(a.activity_at) as activity_date
    FROM crm_activities a
    LEFT JOIN crm_leads l ON l.id = a.lead_id
    WHERE DATE(a.activity_at) BETWEEN ? AND ?
    ORDER BY a.activity_at DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$activities_result = $stmt->get_result();

$activities_by_day = [];
while ($activity = $activities_result->fetch_assoc()) {
    $day = date('j', strtotime($activity['activity_date']));
    if (!isset($activities_by_day[$day])) {
        $activities_by_day[$day] = [];
    }
    $activities_by_day[$day][] = $activity;
}
$stmt->close();

// Get month name
$month_name = date('F', $first_day);
$prev_month = $month - 1;
$prev_year = $year;
$next_month = $month + 1;
$next_year = $year;

if ($prev_month < 1) { $prev_month = 12; $prev_year--; }
if ($next_month > 12) { $next_month = 1; $next_year++; }
?>

<div class="container-fluid px-3 px-md-4 py-4">
    <!-- HEADER -->
    <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between mb-4">
        <div>
            <h1 class="h2 fw-bold mb-2">
                <i class="bi bi-calendar me-2"></i>Calendar
            </h1>
            <p class="text-muted mb-0">View your schedule and activities</p>
        </div>
        
        <div class="d-flex gap-2 mt-3 mt-md-0">
            <a href="?month=<?= date('n') ?>&year=<?= date('Y') ?>" class="btn btn-outline-primary">
                Today
            </a>
            <div class="btn-group">
                <a href="?month=<?= $prev_month ?>&year=<?= $prev_year ?>" class="btn btn-outline-secondary">
                    <i class="bi bi-chevron-left"></i>
                </a>
                <a href="?month=<?= $next_month ?>&year=<?= $next_year ?>" class="btn btn-outline-secondary">
                    <i class="bi bi-chevron-right"></i>
                </a>
            </div>
        </div>
    </div>

    <!-- CALENDAR -->
    <div class="card">
        <div class="card-header bg-white">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold"><?= $month_name ?> <?= $year ?></h5>
                <div>
                    <a href="task_new.php" class="btn btn-sm btn-primary">
                        <i class="bi bi-plus-circle me-2"></i> Add Task
                    </a>
                    <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#addEventModal">
                        <i class="bi bi-plus-circle me-2"></i> Add Event
                    </button>
                </div>
            </div>
        </div>
        <div class="card-body p-0">
            <!-- Calendar Grid -->
            <div class="table-responsive">
                <table class="table table-bordered mb-0" style="min-height: 500px;">
                    <thead>
                        <tr class="table-light">
                            <th class="text-center" style="width: 14.28%;">Monday</th>
                            <th class="text-center" style="width: 14.28%;">Tuesday</th>
                            <th class="text-center" style="width: 14.28%;">Wednesday</th>
                            <th class="text-center" style="width: 14.28%;">Thursday</th>
                            <th class="text-center" style="width: 14.28%;">Friday</th>
                            <th class="text-center" style="width: 14.28%;">Saturday</th>
                            <th class="text-center" style="width: 14.28%;">Sunday</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $day = 1;
                        $current_date = date('Y-m-d');
                        
                        for ($row = 0; $row < 6; $row++): // Max 6 rows for calendar
                            if ($day > $days_in_month) break;
                            ?>
                            <tr style="height: 120px;">
                                <?php for ($col = 0; $col < 7; $col++): ?>
                                    <td class="p-2 align-top" style="vertical-align: top;">
                                        <?php
                                        $cell_day = null;
                                        $cell_date = null;
                                        
                                        if (($row == 0 && $col >= $first_day_of_week) || ($row > 0 && $day <= $days_in_month)) {
                                            if ($row == 0 && $col < $first_day_of_week) {
                                                // Empty cell before first day
                                                echo '<div class="text-muted text-center small p-1 bg-light"></div>';
                                            } else {
                                                $cell_day = $day;
                                                $cell_date = date('Y-m-d', mktime(0, 0, 0, $month, $day, $year));
                                                $is_today = ($cell_date == $current_date);
                                                $is_weekend = ($col == 5 || $col == 6); // Saturday or Sunday
                                                
                                                // Day header
                                                echo '<div class="d-flex justify-content-between align-items-center mb-1 ' . 
                                                     ($is_today ? 'bg-primary text-white rounded p-1' : ($is_weekend ? 'bg-light' : '')) . '">';
                                                echo '<div class="fw-bold">' . $day . '</div>';
                                                if ($is_today) {
                                                    echo '<span class="badge bg-white text-primary">Today</span>';
                                                }
                                                echo '</div>';
                                                
                                                // Tasks for this day
                                                if (isset($tasks_by_day[$day])) {
                                                    echo '<div class="mb-2">';
                                                    foreach ($tasks_by_day[$day] as $task) {
                                                        $is_overdue = strtotime($task['due_at']) < time();
                                                        echo '<div class="small mb-1">';
                                                        echo '<a href="task_edit.php?id=' . $task['id'] . '" class="text-decoration-none ' . 
                                                             ($is_overdue ? 'text-danger fw-bold' : 'text-dark') . '">';
                                                        echo '<i class="bi bi-check-square me-1"></i>';
                                                        echo substr(h($task['title']), 0, 20);
                                                        if (strlen($task['title']) > 20) echo '...';
                                                        echo '</a>';
                                                        echo '</div>';
                                                    }
                                                    echo '</div>';
                                                }
                                                
                                                // Activities for this day
                                                if (isset($activities_by_day[$day])) {
                                                    foreach ($activities_by_day[$day] as $activity) {
                                                        $icon = match($activity['activity_type']) {
                                                            'call' => '📞',
                                                            'email' => '📧',
                                                            'meeting' => '👥',
                                                            default => '📝'
                                                        };
                                                        echo '<div class="small text-muted mb-1">';
                                                        echo '<span title="' . h($activity['activity_type']) . '">' . $icon . '</span> ';
                                                        echo '<span class="ms-1">' . date('g:i', strtotime($activity['activity_at'])) . '</span>';
                                                        echo '</div>';
                                                    }
                                                }
                                                
                                                $day++;
                                            }
                                        } else {
                                            // Empty cell after last day
                                            echo '<div class="text-muted text-center small p-1 bg-light"></div>';
                                        }
                                        ?>
                                    </td>
                                <?php endfor; ?>
                            </tr>
                        <?php endfor; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- UPCOMING EVENTS -->
    <div class="row mt-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0 fw-bold">
                        <i class="bi bi-clock me-2"></i>Upcoming Tasks
                    </h6>
                </div>
                <div class="card-body">
                    <?php
                    $upcoming_tasks = $conn->query("
                        SELECT t.*, l.name as lead_name
                        FROM crm_tasks t
                        LEFT JOIN crm_leads l ON l.id = t.lead_id
                        WHERE t.status = 'open' AND t.due_at >= NOW()
                        ORDER BY t.due_at ASC
                        LIMIT 5
                    ")->fetch_all(MYSQLI_ASSOC);
                    
                    if (empty($upcoming_tasks)): ?>
                        <div class="text-center py-3 text-muted">
                            No upcoming tasks
                        </div>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($upcoming_tasks as $task): ?>
                                <a href="task_edit.php?id=<?= $task['id'] ?>" class="list-group-item list-group-item-action">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <div class="fw-medium"><?= h($task['title']) ?></div>
                                            <?php if ($task['lead_name']): ?>
                                                <div class="small text-muted">
                                                    <i class="bi bi-person me-1"></i><?= h($task['lead_name']) ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="text-end">
                                            <div class="small text-muted">
                                                <?= date('M j', strtotime($task['due_at'])) ?>
                                            </div>
                                            <div class="small">
                                                <?= date('g:i A', strtotime($task['due_at'])) ?>
                                            </div>
                                        </div>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0 fw-bold">
                        <i class="bi bi-calendar-event me-2"></i>Recent Activities
                    </h6>
                </div>
                <div class="card-body">
                    <?php
                    $recent_activities = $conn->query("
                        SELECT a.*, l.name as lead_name
                        FROM crm_activities a
                        LEFT JOIN crm_leads l ON l.id = a.lead_id
                        ORDER BY a.activity_at DESC
                        LIMIT 5
                    ")->fetch_all(MYSQLI_ASSOC);
                    
                    if (empty($recent_activities)): ?>
                        <div class="text-center py-3 text-muted">
                            No recent activities
                        </div>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($recent_activities as $activity): 
                                $icon = match($activity['activity_type']) {
                                    'call' => '📞',
                                    'email' => '📧',
                                    'meeting' => '👥',
                                    default => '📝'
                                };
                            ?>
                                <div class="list-group-item">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <span class="me-2"><?= $icon ?></span>
                                            <span class="fw-medium"><?= h($activity['title']) ?></span>
                                            <?php if ($activity['lead_name']): ?>
                                                <div class="small text-muted">
                                                    <i class="bi bi-person me-1"></i><?= h($activity['lead_name']) ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="text-end">
                                            <div class="small text-muted">
                                                <?= date('M j', strtotime($activity['activity_at'])) ?>
                                            </div>
                                            <div class="small">
                                                <?= date('g:i A', strtotime($activity['activity_at'])) ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Event Modal -->
<div class="modal fade" id="addEventModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Calendar Event</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="eventForm">
                    <div class="mb-3">
                        <label class="form-label">Event Title</label>
                        <input type="text" class="form-control" name="title" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Date</label>
                        <input type="date" class="form-control" name="date" 
                               value="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Time</label>
                        <input type="time" class="form-control" name="time" value="09:00">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" rows="3"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="saveEvent()">Save Event</button>
            </div>
        </div>
    </div>
</div>

<script>
function saveEvent() {
    const form = document.getElementById('eventForm');
    const formData = new FormData(form);
    
    // Here you would typically send the data to the server
    alert('Event saved! (This is a demo - implement actual save functionality)');
    $('#addEventModal').modal('hide');
}
</script>

<?php require_once __DIR__ . "/_layout_bottom.php"; ?>