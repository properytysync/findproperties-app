<?php
// /admin/crm/_layout_top.php

require_once __DIR__ . "/_db.php";
require_once __DIR__ . "/_helpers.php";

/**
 * Auto-generate in-app notifications for overdue tasks (failsafe)
 */
function crm_auto_due_notifications(mysqli $conn, int $adminId): void {
    if ($adminId <= 0) return;

    $checkColumn = $conn->query("SHOW COLUMNS FROM crm_tasks LIKE 'assigned_admin_id'");
    if ($checkColumn && $checkColumn->num_rows > 0) {
        $sql = "
            SELECT t.id, t.title, t.due_at, t.lead_id
            FROM crm_tasks t
            WHERE t.assigned_admin_id=?
              AND t.status='open'
              AND t.due_at <= NOW()
            ORDER BY t.due_at ASC
            LIMIT 25
        ";
        $stmt = $conn->prepare($sql);
        if (!$stmt) return;
        $stmt->bind_param("i", $adminId);
    } else {
        $sql = "
            SELECT t.id, t.title, t.due_at, t.lead_id
            FROM crm_tasks t
            WHERE t.status='open'
              AND t.due_at <= NOW()
            ORDER BY t.due_at ASC
            LIMIT 25
        ";
        $stmt = $conn->prepare($sql);
        if (!$stmt) return;
    }

    $stmt->execute();
    $rs = $stmt->get_result();

    while ($t = $rs->fetch_assoc()) {
        $taskId = (int)$t['id'];
        $title  = (string)$t['title'];
        $dueAt  = (string)$t['due_at'];

        $nTitle = "Task due: " . $title;
        $nBody  = "Due: {$dueAt}";
        $link   = "task_edit.php?id=" . $taskId;

        $chk = $conn->prepare("SELECT id FROM crm_notifications WHERE admin_id=? AND type='task_due' AND link=? LIMIT 1");
        if (!$chk) continue;

        $chk->bind_param("is", $adminId, $link);
        $chk->execute();
        $exists = $chk->get_result()->fetch_assoc();
        $chk->close();

        if (!$exists) {
            $ins = $conn->prepare("
                INSERT INTO crm_notifications (admin_id, type, title, body, link, is_read)
                VALUES (?, 'task_due', ?, ?, ?, 0)
            ");
            if ($ins) {
                $ins->bind_param("isss", $adminId, $nTitle, $nBody, $link);
                $ins->execute();
                $ins->close();
            }
        }
    }

    $stmt->close();
}

// These variables must already be set by /admin/crm/_auth.php
$CRM_ADMIN_ID   = isset($CRM_ADMIN_ID) ? (int)$CRM_ADMIN_ID : 0;
$CRM_ADMIN_USER = isset($CRM_ADMIN_USER) ? (string)$CRM_ADMIN_USER : "";

// Run failsafe notifier (no output)
try { crm_auto_due_notifications($conn, (int)$CRM_ADMIN_ID); } catch (Throwable $e) { /* ignore */ }

// unread count for bell
$unreadCount = 0;
try {
    if ($CRM_ADMIN_ID > 0) {
        $stmt = $conn->prepare("SELECT COUNT(*) AS c FROM crm_notifications WHERE admin_id=? AND is_read=0");
        if ($stmt) {
            $stmt->bind_param("i", $CRM_ADMIN_ID);
            $stmt->execute();
            $unreadCount = (int)($stmt->get_result()->fetch_assoc()['c'] ?? 0);
            $stmt->close();
        }
    }
} catch (Throwable $e) {
    $unreadCount = 0;
}

// initials
$adminInitials = '';
if (!empty($CRM_ADMIN_USER)) {
    $nameParts = explode(' ', $CRM_ADMIN_USER);
    $initials = '';
    foreach ($nameParts as $part) {
        $part = trim($part);
        if ($part !== '') $initials .= strtoupper(substr($part, 0, 1));
    }
    $adminInitials = substr($initials, 0, 2);
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= isset($page_title) ? h($page_title) . " - Propertysync CRM" : "Propertysync CRM" ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
  <style>
    :root {
        --salesforce-blue: #00a1e0;
        --salesforce-dark: #032d60;
        --salesforce-light: #f3f2f2;
    }
    body {
        background-color: #f8fafc;
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
    }
    .navbar-brand {
        color: var(--salesforce-dark) !important;
        font-weight: 700;
        font-size: 1.25rem;
    }
    .sidebar {
        background: white;
        border-right: 1px solid #e5e5e5;
        min-height: calc(100vh - 56px);
    }
    .sidebar .list-group-item {
        border: none;
        border-left: 3px solid transparent;
        border-radius: 0;
        padding: 0.75rem 1.25rem;
        color: #4a5568;
        font-weight: 500;
        transition: all 0.2s;
    }
    .sidebar .list-group-item:hover,
    .sidebar .list-group-item.active {
        background: #f0f9ff;
        border-left-color: var(--salesforce-blue);
        color: var(--salesforce-dark);
    }
    .avatar {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        background: var(--salesforce-blue);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        font-size: 0.875rem;
    }
    .notification-badge {
        position: absolute;
        top: -5px;
        right: -5px;
        width: 18px;
        height: 18px;
        border-radius: 50%;
        background: #ea001e;
        color: white;
        font-size: 0.7rem;
        display: flex;
        align-items: center;
        justify-content: center;
    }
  </style>
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom shadow-sm">
  <div class="container-fluid px-3">
    <button class="btn btn-outline-secondary d-lg-none me-2" type="button"
            data-bs-toggle="offcanvas" data-bs-target="#crmSidebar">
      <i class="bi bi-list"></i>
    </button>

    <a class="navbar-brand d-flex align-items-center" href="index.php">
      <i class="bi bi-cloud-fill me-2" style="color: var(--salesforce-blue);"></i>
      <span>Propertysync CRM</span>
    </a>

    <div class="d-flex align-items-center ms-auto">
      <div class="d-none d-md-block me-3">
        <div class="input-group input-group-sm">
          <input type="text" class="form-control" placeholder="Search leads, tasks...">
          <button class="btn btn-outline-secondary" type="button">
            <i class="bi bi-search"></i>
          </button>
        </div>
      </div>

      <div class="position-relative me-3">
        <a class="btn btn-outline-secondary btn-sm position-relative" href="notifications.php" title="Notifications">
          <i class="bi bi-bell"></i>
          <?php if ($unreadCount > 0): ?>
            <span class="notification-badge"><?= min($unreadCount, 9) ?></span>
          <?php endif; ?>
        </a>
      </div>

      <div class="dropdown">
        <button class="btn btn-outline-secondary btn-sm dropdown-toggle d-flex align-items-center" type="button" data-bs-toggle="dropdown">
          <div class="avatar me-2"><?= h($adminInitials) ?></div>
          <span class="d-none d-md-inline"><?= h($CRM_ADMIN_USER ?: 'Admin') ?></span>
        </button>
        <ul class="dropdown-menu dropdown-menu-end">
          <li><a class="dropdown-item" href="settings.php"><i class="bi bi-gear me-2"></i>Settings</a></li>
          <li><hr class="dropdown-divider"></li>
          <li><a class="dropdown-item" href="../dashboard.php"><i class="bi bi-grid me-2"></i>Admin Panel</a></li>
          <li><a class="dropdown-item text-danger" href="../logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
        </ul>
      </div>
    </div>
  </div>
</nav>

<div class="container-fluid">
  <div class="row">

    <aside class="col-lg-2 d-none d-lg-block sidebar p-0">
      <?php include __DIR__ . "/_nav.php"; ?>

      <div class="p-3 border-top">
        <div class="small text-muted mb-2">QUICK STATS</div>
        <?php
        $quickLeads = (int)($conn->query("SELECT COUNT(*) as c FROM crm_leads")->fetch_assoc()['c'] ?? 0);
        $quickTasks = (int)($conn->query("SELECT COUNT(*) as c FROM crm_tasks WHERE status='open'")->fetch_assoc()['c'] ?? 0);
        ?>
        <div class="d-flex justify-content-between mb-2">
          <span class="small">Leads</span>
          <span class="fw-bold"><?= $quickLeads ?></span>
        </div>
        <div class="d-flex justify-content-between mb-2">
          <span class="small">Open Tasks</span>
          <span class="fw-bold"><?= $quickTasks ?></span>
        </div>
        <div class="d-flex justify-content-between">
          <span class="small">Today</span>
          <span class="fw-bold"><?= date('M j') ?></span>
        </div>
      </div>
    </aside>

    <div class="offcanvas offcanvas-start d-lg-none" tabindex="-1" id="crmSidebar">
      <div class="offcanvas-header border-bottom">
        <h5 class="offcanvas-title">
          <i class="bi bi-cloud-fill me-2" style="color: var(--salesforce-blue);"></i>
          CRM Menu
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
      </div>
      <div class="offcanvas-body p-0">
        <?php include __DIR__ . "/_nav.php"; ?>
      </div>
    </div>

    <main class="col-12 col-lg-10 p-3 p-lg-4">
