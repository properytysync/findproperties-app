<?php
// /admin/crm/reports.php
require_once __DIR__ . "/_auth.php";
require_once __DIR__ . "/_db.php";
require_once __DIR__ . "/_helpers.php";

$page_title = "Reports & Analytics";
require_once __DIR__ . "/_layout_top.php";

/**
 * Safe helper: check if table column exists (no guessing)
 */
if (!function_exists("crm_column_exists")) {
    function crm_column_exists(mysqli $conn, string $table, string $column): bool {
        $table = trim($table);
        $column = trim($column);
        if ($table === "" || $column === "") return false;

        $sql = "SHOW COLUMNS FROM `$table` LIKE ?";
        $stmt = $conn->prepare($sql);
        if (!$stmt) return false;
        $stmt->bind_param("s", $column);
        $stmt->execute();
        $res = $stmt->get_result();
        $ok = ($res && $res->num_rows > 0);
        $stmt->close();
        return $ok;
    }
}

/**
 * Helpers
 */
function dt_range_normalize(string $date_from, string $date_to): array {
    $df = DateTime::createFromFormat('Y-m-d', $date_from) ?: new DateTime(date('Y-m-d'));
    $dt = DateTime::createFromFormat('Y-m-d', $date_to) ?: new DateTime(date('Y-m-d'));

    if ($df > $dt) {
        [$df, $dt] = [$dt, $df];
    }
    return [$df->format('Y-m-d'), $dt->format('Y-m-d')];
}

function quarter_start_end(DateTime $now): array {
    $year = (int)$now->format('Y');
    $month = (int)$now->format('n');
    $q = (int)ceil($month / 3);
    $startMonth = (($q - 1) * 3) + 1;
    $endMonth = $q * 3;

    $start = sprintf('%04d-%02d-01', $year, $startMonth);
    $endDay = cal_days_in_month(CAL_GREGORIAN, $endMonth, $year);
    $end = sprintf('%04d-%02d-%02d', $year, $endMonth, $endDay);

    return [$start, $end];
}

function seconds_to_human(?int $seconds): string {
    if ($seconds === null || $seconds <= 0) return "—";
    $minutes = (int)floor($seconds / 60);
    $hours = (int)floor($minutes / 60);
    $days = (int)floor($hours / 24);

    if ($days > 0) {
        $remH = $hours % 24;
        return $days . "d " . $remH . "h";
    }
    if ($hours > 0) {
        $remM = $minutes % 60;
        return $hours . "h " . $remM . "m";
    }
    if ($minutes > 0) return $minutes . "m";
    return $seconds . "s";
}

/**
 * Date range
 */
$range = $_GET['range'] ?? 'month'; // day, week, month, quarter, year, custom
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

$today = (new DateTime())->format('Y-m-d');

switch ($range) {
    case 'day':
        $date_from = $date_from ?: $today;
        $date_to   = $date_to   ?: $today;
        break;

    case 'week':
        $date_to = $date_to ?: $today;
        $tmp = new DateTime($date_to);
        $tmp->modify('-7 days');
        $date_from = $date_from ?: $tmp->format('Y-m-d');
        break;

    case 'quarter':
        [$qStart, $qEnd] = quarter_start_end(new DateTime());
        $date_from = $date_from ?: $qStart;
        $date_to   = $date_to   ?: $qEnd;
        break;

    case 'year':
        $year = (new DateTime())->format('Y');
        $date_from = $date_from ?: "{$year}-01-01";
        $date_to   = $date_to   ?: "{$year}-12-31";
        break;

    case 'custom':
        $date_from = $date_from ?: $today;
        $date_to   = $date_to   ?: $today;
        break;

    default: // month
        $date_from = $date_from ?: (new DateTime())->format('Y-m-01');
        $date_to   = $date_to   ?: (new DateTime())->format('Y-m-t');
        break;
}

// Normalize range
[$date_from, $date_to] = dt_range_normalize($date_from, $date_to);
$dt_from = $date_from . " 00:00:00";
$dt_to   = $date_to   . " 23:59:59";

/**
 * LEAD STATISTICS (range)
 */
$lead_stats = [
    'total' => 0, 'new' => 0, 'contacted' => 0, 'interested' => 0, 'closed' => 0
];

$sql = "
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'New' THEN 1 ELSE 0 END) as new_count,
        SUM(CASE WHEN status = 'Contacted' THEN 1 ELSE 0 END) as contacted_count,
        SUM(CASE WHEN status = 'Interested' THEN 1 ELSE 0 END) as interested_count,
        SUM(CASE WHEN status = 'Closed' THEN 1 ELSE 0 END) as closed_count
    FROM crm_leads
    WHERE created_at BETWEEN ? AND ?
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $dt_from, $dt_to);
$stmt->execute();
$r = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($r) {
    $lead_stats['total'] = (int)$r['total'];
    $lead_stats['new'] = (int)$r['new_count'];
    $lead_stats['contacted'] = (int)$r['contacted_count'];
    $lead_stats['interested'] = (int)$r['interested_count'];
    $lead_stats['closed'] = (int)$r['closed_count'];
}

/**
 * LEAD GROWTH (last 6 months up to dt_to)
 */
$growth_data = [];
$sql = "
    SELECT 
        DATE_FORMAT(created_at, '%Y-%m') as month,
        COUNT(*) as leads,
        SUM(CASE WHEN status = 'Closed' THEN 1 ELSE 0 END) as won
    FROM crm_leads
    WHERE created_at >= DATE_SUB(?, INTERVAL 6 MONTH)
      AND created_at <= ?
    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
    ORDER BY month ASC
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $dt_to, $dt_to);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) $growth_data[] = $row;
$stmt->close();

/**
 * LEAD SOURCES (top 10)
 */
$source_data = [];
$total_in_range = 0;

$sqlTotal = "SELECT COUNT(*) AS c FROM crm_leads WHERE created_at BETWEEN ? AND ?";
$stmt = $conn->prepare($sqlTotal);
$stmt->bind_param("ss", $dt_from, $dt_to);
$stmt->execute();
$total_in_range = (int)($stmt->get_result()->fetch_assoc()['c'] ?? 0);
$stmt->close();

if ($total_in_range > 0) {
    $sql = "
        SELECT 
            COALESCE(source, 'Unknown') as source_name,
            COUNT(*) as count
        FROM crm_leads
        WHERE created_at BETWEEN ? AND ?
        GROUP BY source
        ORDER BY count DESC
        LIMIT 10
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $dt_from, $dt_to);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $count = (int)$row['count'];
        $row['percentage'] = round(($count * 100.0) / $total_in_range, 1);
        $source_data[] = $row;
    }
    $stmt->close();
}

/**
 * ACTIVITY STATS (by type)
 */
$activity_stats = [];
$sql = "
    SELECT activity_type, COUNT(*) as count
    FROM crm_activities
    WHERE activity_at BETWEEN ? AND ?
    GROUP BY activity_type
    ORDER BY count DESC
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $dt_from, $dt_to);
$stmt->execute();
$activity_stats = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

/**
 * TASK STATS (range)
 */
$task_stats = [
    'total' => 0, 'open' => 0, 'done' => 0, 'canceled' => 0, 'overdue' => 0
];

$sql = "
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'open' THEN 1 ELSE 0 END) as open_count,
        SUM(CASE WHEN status = 'done' THEN 1 ELSE 0 END) as done_count,
        SUM(CASE WHEN status = 'canceled' THEN 1 ELSE 0 END) as canceled_count,
        SUM(CASE WHEN status = 'open' AND due_at < NOW() THEN 1 ELSE 0 END) as overdue_count
    FROM crm_tasks
    WHERE created_at BETWEEN ? AND ?
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $dt_from, $dt_to);
$stmt->execute();
$r = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($r) {
    $task_stats['total'] = (int)$r['total'];
    $task_stats['open'] = (int)$r['open_count'];
    $task_stats['done'] = (int)$r['done_count'];
    $task_stats['canceled'] = (int)$r['canceled_count'];
    $task_stats['overdue'] = (int)$r['overdue_count'];
}

/**
 * CONVERSION RATE
 */
$conversion_rate = $lead_stats['total'] > 0
    ? round(($lead_stats['closed'] / $lead_stats['total']) * 100, 1)
    : 0;

/**
 * ✅ AVERAGE RESPONSE TIME (DYNAMIC)
 * Definition (facts based on your existing tables):
 * - For each lead created within range
 * - Find the first activity logged for that lead (MIN(activity_at))
 * - Response time = first_activity_at - lead.created_at
 *
 * NOTE: This uses only crm_leads.created_at and crm_activities.activity_at.
 * It does NOT guess agent/admin responsibility because that column was not shown.
 */
$response = [
    'avg_seconds' => null,
    'min_seconds' => null,
    'max_seconds' => null,
    'responded' => 0,
    'unresponded' => 0,
    'total' => 0,
    'responded_pct' => 0,
];

$sql = "
    SELECT
        COUNT(*) AS total_leads,
        SUM(CASE WHEN fa.first_activity_at IS NOT NULL THEN 1 ELSE 0 END) AS responded,
        SUM(CASE WHEN fa.first_activity_at IS NULL THEN 1 ELSE 0 END) AS unresponded,
        AVG(CASE 
                WHEN fa.first_activity_at IS NOT NULL 
                 AND fa.first_activity_at >= l.created_at
                THEN TIMESTAMPDIFF(SECOND, l.created_at, fa.first_activity_at)
            END
        ) AS avg_seconds,
        MIN(CASE 
                WHEN fa.first_activity_at IS NOT NULL 
                 AND fa.first_activity_at >= l.created_at
                THEN TIMESTAMPDIFF(SECOND, l.created_at, fa.first_activity_at)
            END
        ) AS min_seconds,
        MAX(CASE 
                WHEN fa.first_activity_at IS NOT NULL 
                 AND fa.first_activity_at >= l.created_at
                THEN TIMESTAMPDIFF(SECOND, l.created_at, fa.first_activity_at)
            END
        ) AS max_seconds
    FROM crm_leads l
    LEFT JOIN (
        SELECT lead_id, MIN(activity_at) AS first_activity_at
        FROM crm_activities
        GROUP BY lead_id
    ) fa ON fa.lead_id = l.id
    WHERE l.created_at BETWEEN ? AND ?
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $dt_from, $dt_to);
$stmt->execute();
$rr = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($rr) {
    $response['total'] = (int)($rr['total_leads'] ?? 0);
    $response['responded'] = (int)($rr['responded'] ?? 0);
    $response['unresponded'] = (int)($rr['unresponded'] ?? 0);
    $response['avg_seconds'] = isset($rr['avg_seconds']) ? (int)round((float)$rr['avg_seconds']) : null;
    $response['min_seconds'] = isset($rr['min_seconds']) ? (int)$rr['min_seconds'] : null;
    $response['max_seconds'] = isset($rr['max_seconds']) ? (int)$rr['max_seconds'] : null;
    $response['responded_pct'] = $response['total'] > 0 ? round(($response['responded'] / $response['total']) * 100, 1) : 0;
}

/**
 * ✅ AGENTS LIST + PERFORMANCE (tasks in range)
 */
$agents = [];
$sql = "
    SELECT 
        a.agent_id,
        a.name,
        a.email,
        a.contact_info,
        a.instagram_username,
        a.facebook_username,
        a.picture,

        COUNT(t.id) AS tasks_total,
        SUM(CASE WHEN t.status='open' THEN 1 ELSE 0 END) AS tasks_open,
        SUM(CASE WHEN t.status='done' THEN 1 ELSE 0 END) AS tasks_done,
        SUM(CASE WHEN t.status='canceled' THEN 1 ELSE 0 END) AS tasks_canceled,
        SUM(CASE WHEN t.status='open' AND t.due_at < NOW() THEN 1 ELSE 0 END) AS tasks_overdue
    FROM agents a
    LEFT JOIN crm_tasks t 
        ON t.assigned_agent_id = a.agent_id
       AND t.created_at BETWEEN ? AND ?
    GROUP BY 
        a.agent_id, a.name, a.email, a.contact_info, a.instagram_username, a.facebook_username, a.picture
    ORDER BY a.name ASC
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $dt_from, $dt_to);
$stmt->execute();
$agents = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

?>

<div class="container-fluid px-3 px-md-4 py-4">

    <!-- HEADER -->
    <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between mb-4">
        <div>
            <h1 class="h2 fw-bold mb-2">
                <i class="bi bi-graph-up me-2"></i>Reports & Analytics
            </h1>
            <p class="text-muted mb-0">
                <?= date('F j, Y', strtotime($date_from)) ?> - <?= date('F j, Y', strtotime($date_to)) ?>
            </p>
        </div>

        <div class="d-flex gap-2 mt-3 mt-md-0">
            <button class="btn btn-primary" onclick="window.print()">
                <i class="bi bi-printer me-2"></i> Print Report
            </button>
            <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#exportReportModal">
                <i class="bi bi-download me-2"></i> Export
            </button>
        </div>
    </div>

    <!-- FILTERS -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <select class="form-select" name="range" onchange="this.form.submit()">
                        <option value="day" <?= $range == 'day' ? 'selected' : '' ?>>Today</option>
                        <option value="week" <?= $range == 'week' ? 'selected' : '' ?>>This Week</option>
                        <option value="month" <?= $range == 'month' ? 'selected' : '' ?>>This Month</option>
                        <option value="quarter" <?= $range == 'quarter' ? 'selected' : '' ?>>This Quarter</option>
                        <option value="year" <?= $range == 'year' ? 'selected' : '' ?>>This Year</option>
                        <option value="custom" <?= $range == 'custom' ? 'selected' : '' ?>>Custom Range</option>
                    </select>
                </div>

                <div class="col-md-4">
                    <div class="input-group">
                        <span class="input-group-text">From</span>
                        <input type="date" class="form-control" name="date_from" value="<?= h($date_from) ?>">
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="input-group">
                        <span class="input-group-text">To</span>
                        <input type="date" class="form-control" name="date_to" value="<?= h($date_to) ?>">
                    </div>
                </div>

                <div class="col-md-1">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-filter"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- KEY METRICS (clear + dynamic) -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card bg-primary bg-opacity-10 border-primary h-100">
                <div class="card-body text-center">
                    <div class="display-6 fw-bold"><?= number_format($lead_stats['total']) ?></div>
                    <div class="text-muted">Leads Created</div>
                    <div class="small text-muted mt-1">In selected date range</div>
                </div>
            </div>
        </div>

        <div class="col-6 col-md-3">
            <div class="card bg-success bg-opacity-10 border-success h-100">
                <div class="card-body text-center">
                    <div class="display-6 fw-bold"><?= $conversion_rate ?>%</div>
                    <div class="text-muted">Lead Conversion</div>
                    <div class="small text-muted mt-1">Closed ÷ Total</div>
                </div>
            </div>
        </div>

        <div class="col-6 col-md-3">
            <div class="card bg-warning bg-opacity-10 border-warning h-100">
                <div class="card-body text-center">
                    <div class="display-6 fw-bold"><?= number_format((int)$task_stats['open']) ?></div>
                    <div class="text-muted">Open Tasks</div>
                    <div class="small text-muted mt-1">Created in range</div>
                </div>
            </div>
        </div>

        <div class="col-6 col-md-3">
            <div class="card bg-info bg-opacity-10 border-info h-100">
                <div class="card-body text-center">
                    <div class="display-6 fw-bold"><?= h(seconds_to_human($response['avg_seconds'])) ?></div>
                    <div class="text-muted">Avg Response Time</div>
                    <div class="small text-muted mt-1">
                        Responded: <?= (int)$response['responded'] ?> (<?= $response['responded_pct'] ?>%)
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Response details -->
    <div class="card mb-4">
        <div class="card-header d-flex align-items-center justify-content-between">
            <h6 class="mb-0 fw-bold"><i class="bi bi-clock-history me-2"></i>Response Time Details</h6>
            <span class="text-muted small">Based on first activity logged per lead</span>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <div class="p-3 border rounded bg-light">
                        <div class="small text-muted">Responded Leads</div>
                        <div class="h4 mb-0 fw-bold"><?= (int)$response['responded'] ?></div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="p-3 border rounded bg-light">
                        <div class="small text-muted">Unresponded Leads</div>
                        <div class="h4 mb-0 fw-bold"><?= (int)$response['unresponded'] ?></div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="p-3 border rounded bg-light">
                        <div class="small text-muted">Min / Max Response</div>
                        <div class="fw-bold">
                            <?= h(seconds_to_human($response['min_seconds'])) ?>
                            <span class="text-muted">/</span>
                            <?= h(seconds_to_human($response['max_seconds'])) ?>
                        </div>
                    </div>
                </div>
            </div>

            
        </div>
    </div>

    <!-- AGENTS PERFORMANCE -->
    <div class="card mb-4">
        <div class="card-header d-flex align-items-center justify-content-between">
            <h6 class="mb-0 fw-bold"><i class="bi bi-people me-2"></i>Agents Performance</h6>
            <span class="text-muted small">Task performance in selected date range</span>
        </div>
        <div class="card-body">
            <?php if (empty($agents)): ?>
                <div class="text-center py-4 text-muted">No agents found.</div>
            <?php else: ?>
                <?php
                    $totalTasksAllAgents = 0;
                    foreach ($agents as $a) $totalTasksAllAgents += (int)($a['tasks_total'] ?? 0);
                ?>
                <div class="table-responsive">
                    <table class="table table-bordered align-middle">
                        <thead class="table-light">
                            <tr>
                                <th style="width:60px;">#</th>
                                <th>Agent</th>
                                <th>Contact</th>
                                <th class="text-center">Tasks</th>
                                <th class="text-center">Open</th>
                                <th class="text-center">Done</th>
                                <th class="text-center">Canceled</th>
                                <th class="text-center">Overdue</th>
                                <th class="text-center">Completion</th>
                                <th class="text-center">Overdue Rate</th>
                                <th class="text-center">Share</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $i=1; foreach ($agents as $a):
                                $total = (int)($a['tasks_total'] ?? 0);
                                $open  = (int)($a['tasks_open'] ?? 0);
                                $done  = (int)($a['tasks_done'] ?? 0);
                                $canc  = (int)($a['tasks_canceled'] ?? 0);
                                $over  = (int)($a['tasks_overdue'] ?? 0);

                                $completion = $total > 0 ? round(($done / $total) * 100, 1) : 0;
                                $overRate   = $total > 0 ? round(($over / $total) * 100, 1) : 0;
                                $share      = $totalTasksAllAgents > 0 ? round(($total / $totalTasksAllAgents) * 100, 1) : 0;

                                $agentName = $a['name'] ?: 'Unnamed';
                            ?>
                                <tr>
                                    <td class="text-muted"><?= $i++ ?></td>
                                    <td class="fw-bold"><?= h($agentName) ?></td>
                                    <td class="small">
                                        <?php if (!empty($a['contact_info'])): ?>
                                            <div><i class="bi bi-telephone me-1"></i><?= h($a['contact_info']) ?></div>
                                        <?php endif; ?>
                                        <?php if (!empty($a['email'])): ?>
                                            <div><i class="bi bi-envelope me-1"></i><?= h($a['email']) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center fw-bold"><?= $total ?></td>
                                    <td class="text-center"><?= $open ?></td>
                                    <td class="text-center text-success fw-bold"><?= $done ?></td>
                                    <td class="text-center"><?= $canc ?></td>
                                    <td class="text-center">
                                        <span class="<?= $over > 0 ? 'text-danger fw-bold' : 'text-muted' ?>"><?= $over ?></span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-<?= $completion >= 70 ? 'success' : ($completion >= 40 ? 'warning' : 'secondary') ?>">
                                            <?= $completion ?>%
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-<?= $overRate > 20 ? 'danger' : ($overRate > 0 ? 'warning' : 'success') ?>">
                                            <?= $overRate ?>%
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <span class="text-muted"><?= $share ?>%</span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="text-muted small mt-2">
                    Note: “Overdue” means open tasks where due date is before now.
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- CHARTS AND DATA -->
    <div class="row g-4">
        <!-- LEAD GROWTH -->
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0 fw-bold">Lead Growth (Last 6 Months)</h6>
                </div>
                <div class="card-body">
                    <?php if (empty($growth_data)): ?>
                        <div class="text-center py-5 text-muted">No growth data available</div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                <tr>
                                    <th>Month</th>
                                    <th>Leads</th>
                                    <th>Won</th>
                                    <th>Conversion</th>
                                    <th>Growth</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php
                                $prev_leads = 0;
                                foreach ($growth_data as $data):
                                    $leads = (int)$data['leads'];
                                    $won = (int)$data['won'];
                                    $conversion = $leads > 0 ? round(($won / $leads) * 100, 1) : 0;
                                    $growth = $prev_leads > 0 ? round((($leads - $prev_leads) / $prev_leads) * 100, 1) : 0;
                                    ?>
                                    <tr>
                                        <td><?= date('F Y', strtotime($data['month'] . '-01')) ?></td>
                                        <td class="fw-bold"><?= $leads ?></td>
                                        <td class="text-success fw-bold"><?= $won ?></td>
                                        <td>
                                            <span class="badge bg-<?= $conversion >= 20 ? 'success' : ($conversion >= 10 ? 'warning' : 'danger') ?>">
                                                <?= $conversion ?>%
                                            </span>
                                        </td>
                                        <td>
                                            <span class="<?= $growth >= 0 ? 'text-success' : 'text-danger' ?>">
                                                <i class="bi bi-arrow-<?= $growth >= 0 ? 'up' : 'down' ?>"></i>
                                                <?= abs($growth) ?>%
                                            </span>
                                        </td>
                                    </tr>
                                    <?php
                                    $prev_leads = $leads;
                                endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- LEAD SOURCES -->
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0 fw-bold">Top Lead Sources</h6>
                </div>
                <div class="card-body">
                    <?php if (empty($source_data)): ?>
                        <div class="text-center py-4 text-muted">No source data available</div>
                    <?php else: ?>
                        <div class="mb-3">
                            <?php foreach ($source_data as $source): ?>
                                <div class="mb-2">
                                    <div class="d-flex justify-content-between mb-1">
                                        <span class="small"><?= h($source['source_name']) ?></span>
                                        <span class="small fw-bold"><?= (int)$source['count'] ?> (<?= $source['percentage'] ?>%)</span>
                                    </div>
                                    <div class="progress" style="height: 8px;">
                                        <div class="progress-bar" role="progressbar" style="width: <?= (float)$source['percentage'] ?>%"></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- ACTIVITY DISTRIBUTION -->
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0 fw-bold">Activity Distribution</h6>
                </div>
                <div class="card-body">
                    <?php if (empty($activity_stats)): ?>
                        <div class="text-center py-4 text-muted">No activity data available</div>
                    <?php else: ?>
                        <div class="row">
                            <?php foreach ($activity_stats as $activity): ?>
                                <div class="col-6 mb-3">
                                    <div class="d-flex align-items-center">
                                        <div class="rounded-circle bg-primary bg-opacity-25 text-primary d-flex align-items-center justify-content-center me-3"
                                             style="width: 40px; height: 40px;">
                                            <?= match($activity['activity_type']) {
                                                'call' => '📞',
                                                'email' => '📧',
                                                'meeting' => '👥',
                                                'whatsapp' => '💬',
                                                'viewing' => '👁️',
                                                default => '📝'
                                            } ?>
                                        </div>
                                        <div>
                                            <div class="fw-bold"><?= ucfirst(h($activity['activity_type'])) ?></div>
                                            <div class="text-muted small"><?= (int)$activity['count'] ?> activities</div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- PERFORMANCE METRICS -->
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0 fw-bold">Performance Metrics</h6>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-6 mb-4">
                            <div class="display-6 fw-bold text-primary"><?= (int)($lead_stats['new'] ?? 0) ?></div>
                            <div class="small text-muted">New Leads</div>
                        </div>
                        <div class="col-6 mb-4">
                            <div class="display-6 fw-bold text-warning"><?= (int)($lead_stats['contacted'] ?? 0) ?></div>
                            <div class="small text-muted">Contacted</div>
                        </div>
                        <div class="col-6">
                            <div class="display-6 fw-bold text-info"><?= (int)($lead_stats['interested'] ?? 0) ?></div>
                            <div class="small text-muted">Interested</div>
                        </div>
                        <div class="col-6">
                            <div class="display-6 fw-bold text-success"><?= (int)($lead_stats['closed'] ?? 0) ?></div>
                            <div class="small text-muted">Closed/Won</div>
                        </div>
                    </div>

                    <hr>

                    <div class="row text-center">
                        <div class="col-4">
                            <div class="fw-bold"><?= (int)($task_stats['open'] ?? 0) ?></div>
                            <div class="small text-muted">Open Tasks</div>
                        </div>
                        <div class="col-4">
                            <div class="fw-bold"><?= (int)($task_stats['overdue'] ?? 0) ?></div>
                            <div class="small text-muted">Overdue</div>
                        </div>
                        <div class="col-4">
                            <div class="fw-bold"><?= (int)($task_stats['done'] ?? 0) ?></div>
                            <div class="small text-muted">Completed</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div><!-- /row -->
</div><!-- /container -->

<!-- Export Report Modal (unchanged) -->
<div class="modal fade" id="exportReportModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Export Report</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="exportReportForm" method="POST" action="export_report.php">
                    <input type="hidden" name="date_from" value="<?= h($date_from) ?>">
                    <input type="hidden" name="date_to" value="<?= h($date_to) ?>">

                    <div class="mb-3">
                        <label class="form-label">Format</label>
                        <select class="form-select" name="format">
                            <option value="pdf">PDF Document</option>
                            <option value="excel">Excel Spreadsheet</option>
                            <option value="csv">CSV File</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Include</label>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="include_metrics" checked>
                            <label class="form-check-label">Key Metrics</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="include_growth" checked>
                            <label class="form-check-label">Growth Charts</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="include_sources" checked>
                            <label class="form-check-label">Lead Sources</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="include_activities" checked>
                            <label class="form-check-label">Activity Data</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="include_agents" checked>
                            <label class="form-check-label">Agents Performance</label>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="document.getElementById('exportReportForm').submit();">
                    Export Report
                </button>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . "/_layout_bottom.php"; ?>
