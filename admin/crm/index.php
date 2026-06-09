<?php
// /admin/crm/index.php - Modern CRM Dashboard
require_once __DIR__ . "/_auth.php";
require_once __DIR__ . "/_db.php";
require_once __DIR__ . "/_helpers.php";

/**
 * ✅ PHP Time Ago Helper
 * Fixes: Fatal error: Call to undefined function time_ago()
 *
 * Accepts MySQL datetime string, timestamp, or DateTime-compatible value.
 */
if (!function_exists('time_ago')) {
    function time_ago($datetime): string
    {
        if (!$datetime) return '—';

        // Normalize input into DateTime
        try {
            if (is_numeric($datetime)) {
                // Unix timestamp
                $dt = (new DateTime())->setTimestamp((int)$datetime);
            } else {
                $dt = new DateTime($datetime);
            }
        } catch (Exception $e) {
            return '—';
        }

        $now = new DateTime();

        // If it's in the future (timezone issues), show "Just now"
        if ($dt > $now) {
            return 'Just now';
        }

        $diffSeconds = $now->getTimestamp() - $dt->getTimestamp();

        if ($diffSeconds < 60) {
            return 'Just now';
        }

        $minutes = floor($diffSeconds / 60);
        if ($minutes < 60) {
            return $minutes . ' min' . ($minutes > 1 ? 's' : '') . ' ago';
        }

        $hours = floor($diffSeconds / 3600);
        if ($hours < 24) {
            return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
        }

        $days = floor($diffSeconds / 86400);
        if ($days < 7) {
            return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
        }

        // Older than 7 days: show date
        $thisYear = $now->format('Y');
        $dtYear = $dt->format('Y');

        return ($dtYear === $thisYear)
            ? $dt->format('M j')
            : $dt->format('M j, Y');
    }
}

$page_title = "CRM Dashboard";
require_once __DIR__ . "/_layout_top.php";

$msg = flash_get("success") ?: trim($_GET["msg"] ?? "");

// ====================
// DASHBOARD METRICS
// ====================

// 1. Overall Statistics
$stats = [
    'total_leads' => 0,
    'open_leads' => 0,
    'converted_leads' => 0,
    'total_tasks' => 0,
    'overdue_tasks' => 0,
    'win_rate' => 0
];

// Total Leads
$sql = "SELECT COUNT(*) as cnt FROM crm_leads";
$res = $conn->query($sql);
$stats['total_leads'] = (int)($res->fetch_assoc()['cnt'] ?? 0);

// Open Leads (not in 'Closed' status)
$sql = "SELECT COUNT(*) as cnt FROM crm_leads WHERE status != 'Closed'";
$res = $conn->query($sql);
$stats['open_leads'] = (int)($res->fetch_assoc()['cnt'] ?? 0);

// Converted Leads (Closed)
$sql = "SELECT COUNT(*) as cnt FROM crm_leads WHERE status = 'Closed'";
$res = $conn->query($sql);
$stats['converted_leads'] = (int)($res->fetch_assoc()['cnt'] ?? 0);

// Total Open Tasks
$sql = "SELECT COUNT(*) as cnt FROM crm_tasks WHERE status='open'";
$res = $conn->query($sql);
$stats['total_tasks'] = (int)($res->fetch_assoc()['cnt'] ?? 0);

// Overdue Tasks
$sql = "SELECT COUNT(*) as cnt FROM crm_tasks WHERE status='open' AND due_at < NOW()";
$res = $conn->query($sql);
$stats['overdue_tasks'] = (int)($res->fetch_assoc()['cnt'] ?? 0);

// Win Rate
if ($stats['total_leads'] > 0) {
    $stats['win_rate'] = round(($stats['converted_leads'] / $stats['total_leads']) * 100, 1);
}

// ====================
// LEADS BY STAGE (using stage_id from crm_leads)
// ====================
$stageStats = [];
$sql = "
    SELECT s.id, s.name, COUNT(l.id) AS total
    FROM crm_stages s
    LEFT JOIN crm_leads l ON l.stage_id = s.id
    WHERE s.is_active = 1
    GROUP BY s.id, s.name
    ORDER BY s.sort_order ASC, s.id ASC
";
$res = $conn->query($sql);
while ($r = $res->fetch_assoc()) {
    $stageStats[] = $r;
}

// ====================
// LEAD SOURCE BREAKDOWN
// ====================
$leadSources = [];
$sql = "
    SELECT 
        COALESCE(source, 'Unknown') as source_name,
        COUNT(*) as lead_count,
        ROUND(COUNT(*) * 100.0 / (SELECT COUNT(*) FROM crm_leads), 1) as percentage
    FROM crm_leads 
    GROUP BY source
    ORDER BY lead_count DESC
    LIMIT 6
";
$res = $conn->query($sql);
while ($r = $res->fetch_assoc()) {
    $leadSources[] = $r;
}

// ====================
// MONTHLY TREND (Last 6 months)
// ====================
$monthlyTrend = [];
$sql = "
    SELECT 
        DATE_FORMAT(created_at, '%Y-%m') as month,
        COUNT(*) as leads,
        SUM(CASE WHEN status = 'Closed' THEN 1 ELSE 0 END) as wins
    FROM crm_leads 
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
    ORDER BY month ASC
    LIMIT 6
";
$res = $conn->query($sql);
while ($r = $res->fetch_assoc()) {
    $monthlyTrend[] = $r;
}

// ====================
// RECENT ACTIVITIES
// ====================
$recentActivities = [];
$sql = "
    SELECT 
        'activity' as type,
        a.activity_type,
        a.title as description,
        a.activity_at as created_at,
        l.name as lead_name,
        CONCAT('lead_view.php?id=', a.lead_id) as link
    FROM crm_activities a
    LEFT JOIN crm_leads l ON l.id = a.lead_id
    ORDER BY a.activity_at DESC
    LIMIT 8
";
$res = $conn->query($sql);
while ($r = $res->fetch_assoc()) {
    $recentActivities[] = $r;
}

// ====================
// RECENT LEADS
// ====================
$recentLeads = [];
$sql = "
    SELECT 
        id,
        name,
        email,
        phone,
        status,
        source,
        created_at,
        (SELECT name FROM crm_stages WHERE id = l.stage_id) as stage_name
    FROM crm_leads l
    ORDER BY created_at DESC
    LIMIT 5
";
$res = $conn->query($sql);
while ($r = $res->fetch_assoc()) {
    $recentLeads[] = $r;
}

// ====================
// UPCOMING TASKS (Next 7 days)
// ====================
$upcomingTasks = [];
$sql = "
    SELECT
        t.id,
        t.title,
        t.due_at,
        t.status,
        l.name as lead_name,
        l.id as lead_id
    FROM crm_tasks t
    LEFT JOIN crm_leads l ON l.id = t.lead_id
    WHERE t.status='open' 
        AND t.due_at BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 7 DAY)
    ORDER BY t.due_at ASC
    LIMIT 8
";
$res = $conn->query($sql);
while ($r = $res->fetch_assoc()) {
    $upcomingTasks[] = $r;
}

// ====================
// TASK STATS
// ====================
$sql = "SELECT COUNT(*) as total FROM crm_tasks";
$res = $conn->query($sql);
$totalTasks = (int)($res->fetch_assoc()['total'] ?? 0);

$sql = "SELECT COUNT(*) as completed FROM crm_tasks WHERE status='done'";
$res = $conn->query($sql);
$completedTasks = (int)($res->fetch_assoc()['completed'] ?? 0);

$completionRate = $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100, 1) : 0;
?>

<!-- STYLES -->
<style>
    :root {
        --primary: #00a1e0;
        --primary-dark: #032d60;
        --success: #2e844a;
        --warning: #fe9339;
        --danger: #ea001e;
        --info: #0176d3;
        --purple: #9050e9;
    }
    
    .metric-card {
        background: white;
        border-radius: 10px;
        border: 1px solid #e5e5e5;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        transition: all 0.3s ease;
        /* ✅ FIX: remove forced full-height so cards shrink to content */
        height: auto;
        overflow: hidden;
    }
    
    .metric-card:hover {
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }
    
    .metric-value {
        font-size: 1.8rem;
        font-weight: 700;
        color: var(--primary-dark);
        line-height: 1;
    }
    
    .metric-label {
        font-size: 0.875rem;
        color: #666;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .metric-icon {
        width: 48px;
        height: 48px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
    }
    
    .stage-card {
        background: white;
        border-radius: 8px;
        padding: 1rem;
        border-left: 4px solid var(--primary);
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        transition: all 0.2s;
    }
    
    .stage-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    }
    
    .stage-name {
        font-weight: 600;
        color: #333;
        margin-bottom: 0.25rem;
    }
    
    .stage-count {
        font-size: 1.5rem;
        font-weight: 700;
        color: var(--primary);
    }
    
    .progress-bar {
        height: 6px;
        border-radius: 3px;
        background: #e9ecef;
        overflow: hidden;
        margin: 0.5rem 0;
    }
    
    .progress-fill {
        height: 100%;
        background: linear-gradient(90deg, var(--primary), #66c4ff);
        border-radius: 3px;
    }
    
    .activity-item {
        display: flex;
        align-items: flex-start;
        gap: 12px;
        padding: 12px 0;
        border-bottom: 1px solid #f0f0f0;
    }
    
    .activity-item:last-child {
        border-bottom: none;
    }
    
    .activity-icon {
        width: 32px;
        height: 32px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }
    
    .icon-call { background: #e3f2fd; color: #1976d2; }
    .icon-email { background: #e8f5e9; color: #388e3c; }
    .icon-meeting { background: #f3e5f5; color: #7b1fa2; }
    .icon-note { background: #fff3e0; color: #f57c00; }
    
    .badge-status {
        font-size: 0.75rem;
        padding: 3px 8px;
        border-radius: 12px;
        font-weight: 500;
    }
    
    .status-new { background: #e3f2fd; color: #1976d2; }
    .status-contacted { background: #fff3e0; color: #f57c00; }
    .status-interested { background: #e8f5e9; color: #388e3c; }
    .status-closed { background: #f5f5f5; color: #666; }
    
    .quick-action {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 1.5rem;
        border: 2px dashed #ddd;
        border-radius: 10px;
        text-align: center;
        color: #666;
        transition: all 0.3s;
        height: 100%;
        text-decoration: none;
    }
    
    .quick-action:hover {
        border-color: var(--primary);
        background: #f8fdff;
        color: var(--primary-dark);
    }
    
    .quick-action i {
        font-size: 2rem;
        margin-bottom: 0.5rem;
        color: var(--primary);
    }
    
    .section-title {
        font-size: 1.1rem;
        font-weight: 600;
        color: var(--primary-dark);
        margin-bottom: 1rem;
        padding-bottom: 0.5rem;
        border-bottom: 2px solid #f0f0f0;
    }
    
    @media (max-width: 768px) {
        .metric-value {
            font-size: 1.5rem;
        }
        .section-title {
            font-size: 1rem;
        }
    }
</style>

<div class="container-fluid px-3 px-md-4 py-4">
    <!-- HEADER -->
    <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between mb-4">
        <div>
            <h1 class="h2 fw-bold mb-2" style="color: var(--primary-dark);">
                <i class="bi bi-speedometer2 me-2"></i>CRM Dashboard
            </h1>
            <p class="text-muted mb-0">
                <i class="bi bi-calendar-check me-1"></i> 
                <?= date('F j, Y'); ?> • 
                Welcome, <strong><?= h($CRM_ADMIN_USER) ?></strong>
            </p>
        </div>
        
        <div class="d-flex gap-2 mt-3 mt-md-0">
            <a class="btn btn-primary d-flex align-items-center" href="lead_new.php">
                <i class="bi bi-plus-circle me-2"></i> Add Lead
            </a>
            <a class="btn btn-outline-primary d-flex align-items-center" href="task_new.php">
                <i class="bi bi-plus-square me-2"></i> Add Task
            </a>
        </div>
    </div>

    <?php if ($msg): ?>
        <div class="alert alert-success alert-dismissible fade show d-flex align-items-center mb-4" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i>
            <?= h($msg) ?>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- KPI METRICS -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="metric-card p-3">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="metric-value"><?= number_format($stats['total_leads']) ?></div>
                        <div class="metric-label">Total Leads</div>
                    </div>
                    <div class="metric-icon bg-primary bg-opacity-10 text-primary">
                        <i class="bi bi-people"></i>
                    </div>
                </div>
                <div class="mt-2 small text-muted">
                    <?= $stats['open_leads'] ?> active
                </div>
            </div>
        </div>
        
        <div class="col-6 col-md-3">
            <div class="metric-card p-3">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="metric-value"><?= $stats['win_rate'] ?>%</div>
                        <div class="metric-label">Conversion Rate</div>
                    </div>
                    <div class="metric-icon bg-success bg-opacity-10 text-success">
                        <i class="bi bi-trophy"></i>
                    </div>
                </div>
                <div class="mt-2 small text-muted">
                    <?= $stats['converted_leads'] ?> won
                </div>
            </div>
        </div>
        
        <div class="col-6 col-md-3">
            <div class="metric-card p-3">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="metric-value"><?= $stats['total_tasks'] ?></div>
                        <div class="metric-label">Active Tasks</div>
                    </div>
                    <div class="metric-icon bg-warning bg-opacity-10 text-warning">
                        <i class="bi bi-check-square"></i>
                    </div>
                </div>
                <div class="mt-2 small text-muted">
                    <?= $stats['overdue_tasks'] ?> overdue
                </div>
            </div>
        </div>
        
        <div class="col-6 col-md-3">
            <div class="metric-card p-3">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="metric-value"><?= $completionRate ?>%</div>
                        <div class="metric-label">Task Completion</div>
                    </div>
                    <div class="metric-icon bg-info bg-opacity-10 text-info">
                        <i class="bi bi-check-circle"></i>
                    </div>
                </div>
                <div class="mt-2 small text-muted">
                    <?= $completedTasks ?>/<?= $totalTasks ?> tasks
                </div>
            </div>
        </div>
    </div>

    <!-- MAIN CONTENT -->
    <div class="row g-4">
        <!-- LEFT COLUMN -->
        <div class="col-lg-8">
            <!-- SALES PIPELINE -->
            <div class="metric-card mb-4">
                <div class="p-3 border-bottom">
                    <h6 class="mb-0 fw-bold">
                        <i class="bi bi-funnel me-2"></i>Sales Pipeline
                    </h6>
                </div>
                <div class="p-3">
                    <?php if (empty($stageStats)): ?>
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle me-2"></i> No pipeline stages configured.
                        </div>
                    <?php else: ?>
                        <div class="row">
                            <?php 
                            $totalLeadsInPipeline = array_sum(array_column($stageStats, 'total'));
                            foreach ($stageStats as $stage): 
                                $percentage = $totalLeadsInPipeline > 0 ? round(($stage['total'] / $totalLeadsInPipeline) * 100) : 0;
                            ?>
                            <div class="col-md-4 col-6 mb-3">
                                <a href="leads.php?stage_id=<?= $stage['id'] ?>" class="text-decoration-none">
                                    <div class="stage-card">
                                        <div class="stage-name"><?= h($stage['name']) ?></div>
                                        <div class="stage-count"><?= (int)$stage['total'] ?></div>
                                        <div class="progress-bar">
                                            <div class="progress-fill" style="width: <?= $percentage ?>%"></div>
                                        </div>
                                        <div class="small text-muted"><?= $percentage ?>% of pipeline</div>
                                    </div>
                                </a>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- CHARTS ROW -->
            <div class="row g-4 mb-4">
                <!-- LEAD SOURCES -->
                <div class="col-md-6">
                    <div class="metric-card h-100">
                        <div class="p-3 border-bottom">
                            <h6 class="mb-0 fw-bold">
                                <i class="bi bi-pie-chart me-2"></i>Lead Sources
                            </h6>
                        </div>
                        <div class="p-3">
                            <?php if (empty($leadSources)): ?>
                                <div class="text-center py-4 text-muted">
                                    <i class="bi bi-pie-chart" style="font-size: 2rem; opacity: 0.3;"></i>
                                    <div class="mt-2">No lead source data</div>
                                </div>
                            <?php else: ?>
                                <div class="mb-3">
                                    <?php foreach ($leadSources as $source): ?>
                                        <div class="mb-2">
                                            <div class="d-flex justify-content-between mb-1">
                                                <span class="small"><?= h($source['source_name']) ?></span>
                                                <span class="small fw-bold"><?= $source['lead_count'] ?> (<?= $source['percentage'] ?>%)</span>
                                            </div>
                                            <div class="progress" style="height: 5px;">
                                                <div class="progress-bar" role="progressbar" 
                                                     style="width: <?= $source['percentage'] ?>%"></div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- MONTHLY TREND -->
                <div class="col-md-6">
                    <div class="metric-card h-100">
                        <div class="p-3 border-bottom">
                            <h6 class="mb-0 fw-bold">
                                <i class="bi bi-graph-up me-2"></i>Monthly Trend
                            </h6>
                        </div>
                        <div class="p-3">
                            <?php if (empty($monthlyTrend)): ?>
                                <div class="text-center py-4 text-muted">
                                    <i class="bi bi-graph-up" style="font-size: 2rem; opacity: 0.3;"></i>
                                    <div class="mt-2">No trend data available</div>
                                </div>
                            <?php else: ?>
                                <div class="d-flex align-items-end justify-content-between mb-3" style="height: 120px;">
                                    <?php 
                                    $maxLeads = max(array_column($monthlyTrend, 'leads'));
                                    foreach ($monthlyTrend as $trend):
                                        $height = $maxLeads > 0 ? ($trend['leads'] / $maxLeads) * 100 : 0;
                                        $monthName = date('M', strtotime($trend['month'] . '-01'));
                                    ?>
                                    <div class="text-center" style="width: 16%;">
                                        <div class="bg-primary bg-opacity-25 rounded-top" 
                                             style="height: <?= $height ?>%; margin: 0 2px;"></div>
                                        <div class="small text-muted mt-1"><?= $monthName ?></div>
                                        <div class="fw-bold"><?= $trend['leads'] ?></div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                                <div class="row text-center">
                                    <div class="col-6">
                                        <div class="small text-muted">Total Leads</div>
                                        <div class="fw-bold">
                                            <?= array_sum(array_column($monthlyTrend, 'leads')) ?>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="small text-muted">Closed</div>
                                        <div class="fw-bold text-success">
                                            <?= array_sum(array_column($monthlyTrend, 'wins')) ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- RECENT LEADS -->
            <div class="metric-card">
                <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-bold">
                        <i class="bi bi-people me-2"></i>Recent Leads
                    </h6>
                    <a href="leads.php" class="btn btn-sm btn-outline-primary">View All</a>
                </div>
                <div class="p-0">
                    <?php if (empty($recentLeads)): ?>
                        <div class="text-center py-5 text-muted">
                            <i class="bi bi-people" style="font-size: 2rem; opacity: 0.3;"></i>
                            <div class="mt-2">No leads available</div>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Name</th>
                                        <th>Contact</th>
                                        <th>Status</th>
                                        <th>Source</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentLeads as $lead): ?>
                                        <tr onclick="window.location='lead_view.php?id=<?= $lead['id'] ?>'" style="cursor: pointer;">
                                            <td class="fw-semibold"><?= h($lead['name']) ?></td>
                                            <td>
                                                <div class="small"><?= h($lead['email']) ?></div>
                                                <?php if ($lead['phone']): ?>
                                                    <div class="small text-muted"><?= h($lead['phone']) ?></div>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge-status status-<?= strtolower($lead['status']) ?>">
                                                    <?= h($lead['status']) ?>
                                                </span>
                                            </td>
                                            <td><?= h($lead['source'] ?: '—') ?></td>
                                            <td class="text-end">
                                                <i class="bi bi-chevron-right text-muted"></i>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- RIGHT COLUMN -->
        <div class="col-lg-4">
            <!-- UPCOMING TASKS -->
            <div class="metric-card mb-4">
                <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-bold">
                        <i class="bi bi-calendar-check me-2"></i>Upcoming Tasks
                    </h6>
                    <a href="tasks.php" class="btn btn-sm btn-outline-primary">View All</a>
                </div>
                <div class="p-3">
                    <?php if (empty($upcomingTasks)): ?>
                        <div class="alert alert-success">
                            <i class="bi bi-check-circle me-2"></i> No upcoming tasks
                        </div>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($upcomingTasks as $task): 
                                $dueDate = new DateTime($task['due_at']);
                                $now = new DateTime();
                                $diff = $now->diff($dueDate);
                                $days = $diff->days;
                                $isToday = $days == 0;
                            ?>
                            <a href="task_edit.php?id=<?= $task['id'] ?>" 
                               class="list-group-item list-group-item-action border-0 px-0 py-2">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="me-2">
                                        <div class="fw-medium mb-1"><?= h($task['title']) ?></div>
                                        <?php if ($task['lead_name']): ?>
                                            <div class="small text-muted">
                                                <i class="bi bi-person me-1"></i><?= h($task['lead_name']) ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="text-end">
                                        <div class="small <?= $isToday ? 'text-warning fw-bold' : 'text-muted' ?>">
                                            <?php if ($isToday): ?>
                                                Today
                                            <?php elseif ($days == 1): ?>
                                                Tomorrow
                                            <?php else: ?>
                                                <?= $days ?>d
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </a>
                            <?php endforeach; ?>
                        </div>
                        <div class="mt-3">
                            <a href="task_new.php" class="btn btn-primary w-100">
                                <i class="bi bi-plus-circle me-2"></i>Add Task
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- RECENT ACTIVITIES -->
            <div class="metric-card mb-4">
                <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-bold">
                        <i class="bi bi-activity me-2"></i>Recent Activity
                    </h6>
                </div>
                <div class="p-3">
                    <?php if (empty($recentActivities)): ?>
                        <div class="text-center py-3 text-muted">
                            <i class="bi bi-activity" style="font-size: 2rem; opacity: 0.3;"></i>
                            <div class="mt-2">No recent activity</div>
                        </div>
                    <?php else: ?>
                        <div style="max-height: 300px; overflow-y: auto;">
                            <?php foreach ($recentActivities as $activity): 
                                $timeAgo = time_ago($activity['created_at']);
                                $iconClass = 'icon-' . $activity['activity_type'];
                                $icon = match($activity['activity_type']) {
                                    'call' => '📞',
                                    'email' => '📧',
                                    'meeting' => '👥',
                                    default => '📝'
                                };
                            ?>
                            <div class="activity-item">
                                <div class="activity-icon <?= $iconClass ?>">
                                    <?= $icon ?>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="mb-1"><?= h($activity['description']) ?></div>
                                    <?php if ($activity['lead_name']): ?>
                                        <div class="small text-muted mb-1">
                                            <i class="bi bi-person me-1"></i><?= h($activity['lead_name']) ?>
                                        </div>
                                    <?php endif; ?>
                                    <div class="small text-muted">
                                        <i class="bi bi-clock me-1"></i><?= h($timeAgo) ?>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="mt-3 text-center">
                            <a href="activities.php" class="btn btn-sm btn-outline-primary">View All</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- QUICK ACTIONS -->
            <div class="metric-card">
                <div class="p-3 border-bottom">
                    <h6 class="mb-0 fw-bold">
                        <i class="bi bi-lightning me-2"></i>Quick Actions
                    </h6>
                </div>
                <div class="p-3">
                    <div class="row g-2">
                        <div class="col-6">
                            <a href="lead_new.php" class="quick-action">
                                <i class="bi bi-person-plus"></i>
                                <div class="fw-medium mt-1">Add Lead</div>
                            </a>
                        </div>
                        <div class="col-6">
                            <a href="task_new.php" class="quick-action">
                                <i class="bi bi-check-square"></i>
                                <div class="fw-medium mt-1">Add Task</div>
                            </a>
                        </div>
                        <div class="col-6">
                            <a href="leads.php" class="quick-action">
                                <i class="bi bi-table"></i>
                                <div class="fw-medium mt-1">View Leads</div>
                            </a>
                        </div>
                        <div class="col-6">
                            <a href="reports.php" class="quick-action">
                                <i class="bi bi-graph-up"></i>
                                <div class="fw-medium mt-1">Reports</div>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Time Ago Helper (JS - optional; not used by PHP rendering)
function time_ago(datetime) {
    const time = new Date(datetime).getTime();
    const now = new Date().getTime();
    const diff = now - time;
    
    if (diff < 60000) {
        return 'Just now';
    } else if (diff < 3600000) {
        const mins = Math.floor(diff / 60000);
        return mins + ' min' + (mins > 1 ? 's' : '') + ' ago';
    } else if (diff < 86400000) {
        const hours = Math.floor(diff / 3600000);
        return hours + ' hour' + (hours > 1 ? 's' : '') + ' ago';
    } else if (diff < 604800000) {
        const days = Math.floor(diff / 86400000);
        return days + ' day' + (days > 1 ? 's' : '') + ' ago';
    } else {
        return new Date(datetime).toLocaleDateString('en-US', { 
            month: 'short', 
            day: 'numeric',
            year: new Date().getFullYear() !== new Date(datetime).getFullYear() ? 'numeric' : undefined
        });
    }
}

// Update time ago on page load
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.time-ago').forEach(el => {
        const datetime = el.getAttribute('data-time');
        if (datetime) {
            el.textContent = time_ago(datetime);
        }
    });
    
    // Auto refresh every minute
    setInterval(() => {
        document.querySelectorAll('.time-ago').forEach(el => {
            const datetime = el.getAttribute('data-time');
            if (datetime) {
                el.textContent = time_ago(datetime);
            }
        });
    }, 60000);
});
</script>

<?php require_once __DIR__ . "/_layout_bottom.php"; ?>
