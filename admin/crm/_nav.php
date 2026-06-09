<?php
// /admin/crm/_nav.php
// Get current page
$current_page = basename($_SERVER['PHP_SELF']);
?>
<div class="list-group list-group-flush">
    <a class="list-group-item list-group-item-action <?= $current_page == 'index.php' ? 'active' : '' ?>" href="index.php">
        <i class="bi bi-speedometer2 me-2"></i>Dashboard
    </a>
    
    <div class="list-group-item small text-muted text-uppercase mt-3">SALES</div>
    
    <a class="list-group-item list-group-item-action <?= $current_page == 'leads.php' ? 'active' : '' ?>" href="leads.php">
        <i class="bi bi-people me-2"></i>Leads
        <span class="badge bg-primary float-end">
            <?php 
            $leadCount = $conn->query("SELECT COUNT(*) as c FROM crm_leads")->fetch_assoc()['c'] ?? 0;
            echo $leadCount;
            ?>
        </span>
    </a>
    
    <a class="list-group-item list-group-item-action <?= $current_page == 'pipeline.php' ? 'active' : '' ?>" href="pipeline.php">
        <i class="bi bi-funnel me-2"></i>Pipeline
    </a>
    
    <div class="list-group-item small text-muted text-uppercase mt-3">ACTIVITIES</div>
    
    <a class="list-group-item list-group-item-action <?= $current_page == 'tasks.php' ? 'active' : '' ?>" href="tasks.php">
        <i class="bi bi-check-square me-2"></i>Tasks
        <span class="badge bg-warning float-end">
            <?php 
            $taskCount = $conn->query("SELECT COUNT(*) as c FROM crm_tasks WHERE status='open'")->fetch_assoc()['c'] ?? 0;
            echo $taskCount;
            ?>
        </span>
    </a>
    
    <a class="list-group-item list-group-item-action <?= $current_page == 'activities.php' ? 'active' : '' ?>" href="activities.php">
        <i class="bi bi-activity me-2"></i>Activities
    </a>
    
    <a class="list-group-item list-group-item-action <?= $current_page == 'calendar.php' ? 'active' : '' ?>" href="calendar.php">
        <i class="bi bi-calendar me-2"></i>Calendar
    </a>
    
    <div class="list-group-item small text-muted text-uppercase mt-3">REPORTING</div>
    
    <a class="list-group-item list-group-item-action <?= $current_page == 'reports.php' ? 'active' : '' ?>" href="reports.php">
        <i class="bi bi-graph-up me-2"></i>Reports
    </a>
    
    
    <div class="list-group-item small text-muted text-uppercase mt-3">SYSTEM</div>
    
    <a class="list-group-item list-group-item-action <?= $current_page == 'notifications.php' ? 'active' : '' ?>" href="notifications.php">
        <i class="bi bi-bell me-2"></i>Notifications
        <?php if ($unreadCount > 0): ?>
            <span class="badge bg-danger float-end"><?= min($unreadCount, 9) ?></span>
        <?php endif; ?>
    </a>
    
    <a class="list-group-item list-group-item-action <?= $current_page == 'settings.php' ? 'active' : '' ?>" href="settings.php">
        <i class="bi bi-gear me-2"></i>Settings
    </a>
</div>