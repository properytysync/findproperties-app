<?php
session_start();
require("config.php");
require_once __DIR__ . "/_auth.php";

require_login();

/**
 * ✅ ADMIN DETECTION
 * Adjust if your auth uses a different role variable.
 */
$isAdmin = false;
if (function_exists('is_admin')) {
    $isAdmin = (bool)is_admin();
} elseif (!empty($_SESSION['role'])) {
    $role = strtolower((string)$_SESSION['role']);
    $isAdmin = in_array($role, ['admin', 'superadmin'], true);
} elseif (!empty($_SESSION['auser'])) {
    // Many templates store admin login in auser
    $isAdmin = true;
}

/**
 * ✅ Flash messages
 */
$success = $_GET['success'] ?? '';
$error   = $_GET['error'] ?? '';

/**
 * ✅ Handle POST actions (Update/Delete) on same page
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Block agents from modifying
    if (!$isAdmin && in_array($action, ['update_tenant', 'delete_tenant'], true)) {
        header("Location: display_tenants.php?error=" . urlencode("Access denied: only admins can edit or delete tenants."));
        exit();
    }

    // ✅ UPDATE TENANT
    if ($action === 'update_tenant') {
        $tenant_id = (int)($_POST['tenant_id'] ?? 0);

        $pid          = ($_POST['pid'] ?? '');
        $name         = trim($_POST['name'] ?? '');
        $contact_info = trim($_POST['contact_info'] ?? '');
        $tenant_type  = trim($_POST['tenant_type'] ?? '');
        $lease_start  = trim($_POST['lease_start'] ?? '');
        $lease_end    = trim($_POST['lease_end'] ?? '');
        $purchase_date= trim($_POST['purchase_date'] ?? '');
        $amount_paid  = trim($_POST['amount_paid'] ?? '');

        // Validate required fields
        if ($tenant_id <= 0) {
            header("Location: display_tenants.php?error=" . urlencode("Invalid tenant selected."));
            exit();
        }

        if ($name === '' || $contact_info === '' || $tenant_type === '') {
            header("Location: display_tenants.php?error=" . urlencode("Please fill required fields (Name, Contact, Tenant Type)."));
            exit();
        }

        // enum validation
        if (!in_array($tenant_type, ['renter','buyer'], true)) {
            header("Location: display_tenants.php?error=" . urlencode("Invalid tenant type."));
            exit();
        }

        // Normalize fields
        $pid = ($pid === '' ? null : (int)$pid);
        $lease_start   = ($lease_start === '' ? null : $lease_start);
        $lease_end     = ($lease_end === '' ? null : $lease_end);
        $purchase_date = ($purchase_date === '' ? null : $purchase_date);

        // amount_paid default
        if ($amount_paid === '' || !is_numeric($amount_paid)) {
            $amount_paid = 0.00;
        } else {
            $amount_paid = (float)$amount_paid;
        }

        $sql = "UPDATE tenants
                SET pid=?, name=?, contact_info=?, tenant_type=?, lease_start=?, lease_end=?, purchase_date=?, amount_paid=?
                WHERE tenant_id=? LIMIT 1";

        $stmt = $con->prepare($sql);
        if (!$stmt) {
            header("Location: display_tenants.php?error=" . urlencode("Database error: unable to prepare update."));
            exit();
        }

        // Types: i s s s s s s d i
        $stmt->bind_param(
            "issssssdi",
            $pid,
            $name,
            $contact_info,
            $tenant_type,
            $lease_start,
            $lease_end,
            $purchase_date,
            $amount_paid,
            $tenant_id
        );

        if ($stmt->execute()) {
            header("Location: display_tenants.php?success=" . urlencode("Tenant updated successfully."));
            exit();
        } else {
            header("Location: display_tenants.php?error=" . urlencode("Update failed: " . $stmt->error));
            exit();
        }
    }

    // ✅ DELETE TENANT
    if ($action === 'delete_tenant') {
        $tenant_id = (int)($_POST['tenant_id'] ?? 0);

        if ($tenant_id <= 0) {
            header("Location: display_tenants.php?error=" . urlencode("Invalid tenant selected."));
            exit();
        }

        $stmt = $con->prepare("DELETE FROM tenants WHERE tenant_id=? LIMIT 1");
        if (!$stmt) {
            header("Location: display_tenants.php?error=" . urlencode("Database error: unable to prepare delete."));
            exit();
        }
        $stmt->bind_param("i", $tenant_id);

        if ($stmt->execute()) {
            header("Location: display_tenants.php?success=" . urlencode("Tenant deleted successfully."));
            exit();
        } else {
            header("Location: display_tenants.php?error=" . urlencode("Delete failed: " . $stmt->error));
            exit();
        }
    }
}

/**
 * ✅ Pagination
 */
$recordsPerPage = 10;
$currentPage = isset($_GET["page"]) ? (int)$_GET["page"] : 1;
if ($currentPage < 1) $currentPage = 1;

$startFrom = ($currentPage - 1) * $recordsPerPage;

$totalRecordsQuery = "SELECT COUNT(*) AS total FROM tenants";
$totalResult = $con->query($totalRecordsQuery);
$rowCount = $totalResult ? $totalResult->fetch_assoc() : ['total' => 0];
$totalRecords = (int)($rowCount['total'] ?? 0);
$totalPages = ($totalRecords > 0) ? (int)ceil($totalRecords / $recordsPerPage) : 1;

if ($currentPage > $totalPages && $totalPages > 0) {
    $currentPage = $totalPages;
    $startFrom = ($currentPage - 1) * $recordsPerPage;
}

/**
 * ✅ Fetch Tenants
 */
$stmt = $con->prepare("SELECT * FROM tenants ORDER BY tenant_id DESC LIMIT ?, ?");
$stmt->bind_param("ii", $startFrom, $recordsPerPage);
$stmt->execute();
$result = $stmt->get_result();

/**
 * ✅ Lease stats (safe even when lease dates are NULL)
 */
$activeLeases = (int)$con->query("
    SELECT COUNT(*) AS count 
    FROM tenants 
    WHERE lease_start IS NOT NULL 
      AND lease_end IS NOT NULL 
      AND CURDATE() BETWEEN lease_start AND lease_end
")->fetch_assoc()['count'];

$expiringSoon = (int)$con->query("
    SELECT COUNT(*) AS count 
    FROM tenants 
    WHERE lease_end IS NOT NULL
      AND lease_end BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
")->fetch_assoc()['count'];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tenant Management</title>

    <link rel="icon" href="assets/images/favicon.png" type="image/x-icon">
    <link rel="shortcut icon" href="assets/images/favicon.png" type="image/x-icon">

    <link rel="stylesheet" type="text/css" href="assets/css/vendors/bootstrap.css">
    <link rel="stylesheet" type="text/css" href="assets/css/style.css">
    <link rel="stylesheet" type="text/css" href="assets/css/responsive.css">

    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.15.4/css/all.css">

    <style>
        .page-body { background: #f8fafc; min-height: calc(100vh - 130px); }
        .container-fluid { padding: 20px 30px; }
        .page-title { margin-bottom: 16px; }
        .page-title h3 { color: #111827; font-weight: 800; font-size: 1.8rem; margin-bottom: 6px; }
        .page-title p { margin: 0; color:#6b7280; font-weight: 600; }

        .stats-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 16px;
            margin-bottom: 18px;
        }
        .stat-card {
            background: #fff;
            border-radius: 14px;
            padding: 16px;
            box-shadow: 0 6px 22px rgba(0,0,0,0.06);
            border-left: 5px solid #4361ee;
            transition: transform .2s ease, box-shadow .2s ease;
        }
        .stat-card:hover { transform: translateY(-3px); box-shadow: 0 10px 30px rgba(0,0,0,0.10); }
        .stat-card.primary { border-left-color: #4361ee; }
        .stat-card.success { border-left-color: #2ecc71; }
        .stat-card.warning { border-left-color: #f39c12; }
        .stat-card.danger { border-left-color: #e74c3c; }

        .stat-icon {
            width: 46px; height: 46px;
            border-radius: 12px;
            display:flex; align-items:center; justify-content:center;
            margin-bottom: 10px;
            font-size: 20px;
        }
        .stat-card.primary .stat-icon { background: rgba(67,97,238,.10); color:#4361ee; }
        .stat-card.success .stat-icon { background: rgba(46,204,113,.10); color:#2ecc71; }
        .stat-card.warning .stat-icon { background: rgba(243,156,18,.12); color:#f39c12; }
        .stat-card.danger  .stat-icon { background: rgba(231,76,60,.12); color:#e74c3c; }

        .stat-number { font-size: 26px; font-weight: 900; color:#111827; }
        .stat-label { color:#6b7280; font-size: 12px; text-transform: uppercase; letter-spacing: .6px; margin-top: 6px; font-weight:800; }

        .main-content-card {
            background:#fff;
            border-radius: 14px;
            box-shadow: 0 8px 28px rgba(0,0,0,0.08);
            overflow:hidden;
            margin-bottom: 24px;
        }
        .card-header {
            padding: 16px 18px;
            border-bottom: 1px solid #eef2f7;
            display:flex;
            justify-content: space-between;
            align-items:center;
            gap: 12px;
            flex-wrap: wrap;
        }
        .card-title {
            font-size: 1.1rem;
            font-weight: 900;
            color:#111827;
            display:flex;
            align-items:center;
            gap: 10px;
            margin: 0;
        }
        .card-title i { color:#4361ee; }
        .card-actions {
            display:flex;
            gap: 10px;
            align-items:center;
            flex: 1;
            justify-content: flex-end;
            min-width: 260px;
        }
        .search-box { position:relative; width: min(360px, 100%); }
        .search-box input {
            border-radius: 10px;
            border: 1px solid #e5e7eb;
            padding: 11px 14px 11px 42px;
            font-size: 14px;
            width: 100%;
        }
        .search-box i {
            position:absolute;
            left:14px;
            top:50%;
            transform: translateY(-50%);
            color:#9ca3af;
        }
        .btn-add {
            background:#4361ee;
            color:#fff;
            border:none;
            padding: 11px 16px;
            border-radius: 10px;
            font-weight: 800;
            cursor:pointer;
            display:flex;
            align-items:center;
            gap: 8px;
            white-space: nowrap;
        }

        .alert-area { padding: 0 30px; margin-bottom: 10px; }
        .alert { border-radius: 12px; padding: 12px 14px; font-weight: 700; }

        .table-container { overflow-x: auto; }
        .tenant-table { width:100%; border-collapse: separate; border-spacing: 0; }
        .tenant-table thead th {
            background:#f8fafc;
            color:#6b7280;
            font-weight: 900;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: .6px;
            padding: 14px 16px;
            border-bottom: 2px solid #eef2f7;
            white-space: nowrap;
        }
        .tenant-table td {
            padding: 14px 16px;
            border-bottom: 1px solid #f1f5f9;
            color:#374151;
            vertical-align: middle;
        }
        .tenant-table tbody tr:hover { background:#f8fafc; }

        .tenant-type {
            padding: 6px 12px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: .6px;
            display:inline-block;
        }
        .tenant-type.renter { background:#e0f2fe; color:#075985; border:1px solid #7dd3fc; }
        .tenant-type.buyer  { background:#dcfce7; color:#166534; border:1px solid #86efac; }

        .lease-status {
            padding: 6px 10px;
            border-radius: 10px;
            font-size: 11px;
            font-weight: 900;
            display:inline-block;
        }
        .status-active { background:#d1fae5; color:#065f46; }
        .status-expiring { background:#fef3c7; color:#92400e; }
        .status-expired { background:#fee2e2; color:#991b1b; }

        .action-buttons { display:flex; gap: 8px; flex-wrap: wrap; }
        .btn-action {
            width: 36px; height: 36px;
            border-radius: 10px;
            border:none;
            display:inline-flex;
            align-items:center;
            justify-content:center;
            cursor:pointer;
            font-size: 14px;
        }
        .btn-view { background:#e0f2fe; color:#0369a1; }
        .btn-edit { background:#fff7ed; color:#c2410c; }
        .btn-delete { background:#fee2e2; color:#b91c1c; }

        .pid-cell { font-weight: 900; color:#4361ee; }
        .name-cell { font-weight: 900; color:#111827; }
        .date-cell { font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono"; font-size: 12.5px; }

        .pagination-container { padding: 16px 18px; border-top:1px solid #eef2f7; }
        .pagination { justify-content:center; margin:0; }
        .page-link {
            border:1px solid #e5e7eb;
            border-radius: 10px !important;
            padding: 8px 12px;
            color:#374151;
            font-weight: 800;
        }
        .page-link:hover { background:#4361ee; color:#fff; border-color:#4361ee; }
        .page-item.active .page-link { background:#4361ee; color:#fff; border-color:#4361ee; }
        .page-info { text-align:center; margin-top: 8px; color:#6b7280; font-size: 13px; font-weight:700; }

        /* ✅ MOBILE: table becomes stacked cards (no dragging) */
        @media (max-width: 768px) {
            .container-fluid { padding: 14px; }
            .card-actions { width:100%; justify-content: space-between; }
            .search-box { width:100%; }
            .btn-add { width:100%; justify-content:center; }

            .tenant-table thead { display:none; }
            .tenant-table, .tenant-table tbody, .tenant-table tr, .tenant-table td { display:block; width:100%; }
            .tenant-table tr {
                background:#fff;
                margin: 12px 0;
                border: 1px solid #eef2f7;
                border-radius: 14px;
                overflow:hidden;
                box-shadow: 0 6px 18px rgba(0,0,0,0.06);
            }
            .tenant-table td {
                border-bottom: 1px solid #f1f5f9;
                display:flex;
                justify-content: space-between;
                gap: 12px;
                padding: 12px 14px;
            }
            .tenant-table td::before {
                content: attr(data-label);
                font-weight: 900;
                color:#6b7280;
                text-transform: uppercase;
                letter-spacing: .5px;
                font-size: 11px;
                min-width: 120px;
            }
            .tenant-table td:last-child { border-bottom: none; }
            .action-buttons { justify-content: flex-end; }
        }
    </style>
</head>

<body>
<div class="page-wrapper compact-wrapper" id="pageWrapper">

    <div class="page-body-wrapper">
        <div class="sidebar-wrapper" data-layout="fill-svg">
            <div>
                <?php include('menu.php'); ?>
            </div>
        </div>

        <div class="page-body">
            <div class="container-fluid">
                <div class="page-title">
                    <h3>Tenant Management</h3>
                    <p>Manage tenant records and lease agreements</p>
                </div>

                <?php if ($success || $error): ?>
                    <div class="alert-area">
                        <?php if ($success): ?>
                            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                        <?php endif; ?>
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <div class="stats-cards">
                    <div class="stat-card primary">
                        <div class="stat-icon"><i class="fas fa-users"></i></div>
                        <div class="stat-number"><?php echo number_format($totalRecords); ?></div>
                        <div class="stat-label">Total Tenants</div>
                    </div>

                    <div class="stat-card success">
                        <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
                        <div class="stat-number"><?php echo number_format($activeLeases); ?></div>
                        <div class="stat-label">Active Leases</div>
                    </div>

                    <div class="stat-card warning">
                        <div class="stat-icon"><i class="fas fa-exclamation-triangle"></i></div>
                        <div class="stat-number"><?php echo number_format($expiringSoon); ?></div>
                        <div class="stat-label">Expiring Soon</div>
                    </div>

                    <div class="stat-card danger">
                        <div class="stat-icon"><i class="fas fa-clock"></i></div>
                        <div class="stat-number"><?php echo number_format(max(0, $totalRecords - $activeLeases)); ?></div>
                        <div class="stat-label">Inactive</div>
                    </div>
                </div>

                <div class="main-content-card">
                    <div class="card-header">
                        <h5 class="card-title"><i class="fas fa-list-alt"></i> Tenant List</h5>
                        <div class="card-actions">
                            <div class="search-box">
                                <i class="fas fa-search"></i>
                                <input type="text" id="searchInput" placeholder="Search tenants...">
                            </div>

                            <?php if ($isAdmin): ?>
                                <a class="btn-add" href="add_tenant.php"><i class="fas fa-plus"></i> Add Tenant</a>
                            <?php else: ?>
                                <button class="btn-add" type="button" id="btnAddTenantAgent"><i class="fas fa-plus"></i> Add Tenant</button>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="table-container">
                        <table class="tenant-table" id="tenantTable">
                            <thead>
                                <tr>
                                    <th>Tenant ID</th>
                                    <th>Property ID</th>
                                    <th>Name</th>
                                    <th>Contact</th>
                                    <th>Type</th>
                                    <th>Lease Start</th>
                                    <th>Lease End</th>
                                    <th>Purchase Date</th>
                                    <th>Amount Paid</th>
                                    <th>Lease Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>

                            <tbody>
                            <?php if ($result && $result->num_rows > 0): ?>
                                <?php while($r = $result->fetch_assoc()):
                                    $tenant_id = (int)$r['tenant_id'];
                                    $pid = $r['pid'];
                                    $name = $r['name'];
                                    $contact = $r['contact_info'];
                                    $type = $r['tenant_type']; // renter/buyer
                                    $lease_start = $r['lease_start'];
                                    $lease_end   = $r['lease_end'];
                                    $purchase_date = $r['purchase_date'];
                                    $amount_paid = (float)$r['amount_paid'];

                                    $typeClass = strtolower($type); // renter or buyer

                                    $daysLeft = null;
                                    $statusText = 'N/A';
                                    $statusClass = 'status-expired';

                                    if (!empty($lease_start) && !empty($lease_end)) {
                                        $leaseEndTs = strtotime($lease_end);
                                        $nowTs = time();
                                        $daysLeft = (int)floor(($leaseEndTs - $nowTs) / 86400);

                                        if ($daysLeft > 30) {
                                            $statusClass = 'status-active';
                                            $statusText = 'Active';
                                        } elseif ($daysLeft > 0) {
                                            $statusClass = 'status-expiring';
                                            $statusText = 'Expiring Soon';
                                        } else {
                                            $statusClass = 'status-expired';
                                            $statusText = 'Expired';
                                        }
                                    }
                                ?>
                                <tr
                                    data-tenant-id="<?php echo $tenant_id; ?>"
                                    data-tenantid="<?php echo $tenant_id; ?>"
                                    data-pid="<?php echo htmlspecialchars((string)$pid); ?>"
                                    data-name="<?php echo htmlspecialchars($name); ?>"
                                    data-contact="<?php echo htmlspecialchars($contact); ?>"
                                    data-type="<?php echo htmlspecialchars($type); ?>"
                                    data-lease-start="<?php echo htmlspecialchars((string)$lease_start); ?>"
                                    data-lease-end="<?php echo htmlspecialchars((string)$lease_end); ?>"
                                    data-purchase-date="<?php echo htmlspecialchars((string)$purchase_date); ?>"
                                    data-amount-paid="<?php echo htmlspecialchars((string)$amount_paid); ?>"
                                >
                                    <td class="pid-cell" data-label="Tenant ID">#<?php echo $tenant_id; ?></td>
                                    <td data-label="Property ID"><?php echo $pid ? '#' . htmlspecialchars((string)$pid) : '-'; ?></td>
                                    <td class="name-cell" data-label="Name"><?php echo htmlspecialchars($name); ?></td>
                                    <td data-label="Contact"><?php echo htmlspecialchars($contact); ?></td>
                                    <td data-label="Type">
                                        <span class="tenant-type <?php echo $typeClass; ?>"><?php echo htmlspecialchars($type); ?></span>
                                    </td>
                                    <td class="date-cell" data-label="Lease Start"><?php echo $lease_start ? date('M d, Y', strtotime($lease_start)) : '-'; ?></td>
                                    <td class="date-cell" data-label="Lease End">
                                        <?php echo $lease_end ? date('M d, Y', strtotime($lease_end)) : '-'; ?>
                                        <?php if ($daysLeft !== null && $daysLeft > 0 && $daysLeft <= 30): ?>
                                            <div class="small" style="color:#b45309;font-weight:800;margin-top:4px;">
                                                (<?php echo $daysLeft; ?> days left)
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="date-cell" data-label="Purchase Date"><?php echo $purchase_date ? date('M d, Y', strtotime($purchase_date)) : '-'; ?></td>
                                    <td data-label="Amount Paid"><strong>₦<?php echo number_format($amount_paid, 2); ?></strong></td>
                                    <td data-label="Lease Status">
                                        <span class="lease-status <?php echo $statusClass; ?>"><?php echo $statusText; ?></span>
                                    </td>
                                    <td data-label="Actions">
                                        <div class="action-buttons">
                                            <button type="button" class="btn-action btn-view" title="View"><i class="fas fa-eye"></i></button>
                                            <button type="button" class="btn-action btn-edit" title="Edit"><i class="fas fa-edit"></i></button>
                                            <button type="button" class="btn-action btn-delete" title="Delete"><i class="fas fa-trash"></i></button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="11" style="padding: 30px; text-align:center;">No tenants found.</td></tr>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <?php if ($totalPages > 1): ?>
                    <div class="pagination-container">
                        <nav>
                            <ul class="pagination">
                                <?php if ($currentPage > 1): ?>
                                <li class="page-item"><a class="page-link" href="?page=<?php echo $currentPage - 1; ?>"><i class="fas fa-chevron-left"></i></a></li>
                                <?php endif; ?>

                                <?php
                                $startPage = max(1, $currentPage - 2);
                                $endPage = min($totalPages, $currentPage + 2);

                                if ($startPage > 1) {
                                    echo '<li class="page-item"><a class="page-link" href="?page=1">1</a></li>';
                                    if ($startPage > 2) echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                }

                                for ($i=$startPage; $i<=$endPage; $i++) {
                                    $active = ($i == $currentPage) ? 'active' : '';
                                    echo '<li class="page-item '.$active.'"><a class="page-link" href="?page='.$i.'">'.$i.'</a></li>';
                                }

                                if ($endPage < $totalPages) {
                                    if ($endPage < $totalPages - 1) echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                    echo '<li class="page-item"><a class="page-link" href="?page='.$totalPages.'">'.$totalPages.'</a></li>';
                                }
                                ?>

                                <?php if ($currentPage < $totalPages): ?>
                                <li class="page-item"><a class="page-link" href="?page=<?php echo $currentPage + 1; ?>"><i class="fas fa-chevron-right"></i></a></li>
                                <?php endif; ?>
                            </ul>

                            <div class="page-info">
                                Showing <?php echo min($startFrom + 1, $totalRecords); ?> - <?php echo min($startFrom + $recordsPerPage, $totalRecords); ?>
                                of <?php echo number_format($totalRecords); ?> tenants
                            </div>
                        </nav>
                    </div>
                    <?php endif; ?>
                </div>

            </div>
        </div>
    </div>
</div>

<!-- ✅ MODALS -->

<!-- View Modal -->
<div class="modal fade" id="viewTenantModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content" style="border-radius:16px;">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fas fa-eye text-primary me-2"></i> Tenant Details</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="row g-3">
          <div class="col-md-4"><strong>Tenant ID:</strong><div id="v_tenant_id" class="text-muted"></div></div>
          <div class="col-md-4"><strong>Property ID:</strong><div id="v_pid" class="text-muted"></div></div>
          <div class="col-md-4"><strong>Type:</strong><div id="v_type" class="text-muted"></div></div>

          <div class="col-md-6"><strong>Name:</strong><div id="v_name" class="text-muted"></div></div>
          <div class="col-md-6"><strong>Contact:</strong><div id="v_contact" class="text-muted"></div></div>

          <div class="col-md-4"><strong>Lease Start:</strong><div id="v_lease_start" class="text-muted"></div></div>
          <div class="col-md-4"><strong>Lease End:</strong><div id="v_lease_end" class="text-muted"></div></div>
          <div class="col-md-4"><strong>Purchase Date:</strong><div id="v_purchase" class="text-muted"></div></div>

          <div class="col-md-4"><strong>Amount Paid:</strong><div id="v_amount" class="text-muted"></div></div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editTenantModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content" style="border-radius:16px;">
      <form method="POST">
        <input type="hidden" name="action" value="update_tenant">
        <input type="hidden" name="tenant_id" id="e_tenant_id">

        <div class="modal-header">
          <h5 class="modal-title"><i class="fas fa-edit text-warning me-2"></i> Edit Tenant</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body">
          <div class="row g-3">
            <div class="col-md-4">
              <label class="form-label">Property ID (optional)</label>
              <input type="number" class="form-control" name="pid" id="e_pid">
            </div>

            <div class="col-md-8">
              <label class="form-label">Name *</label>
              <input type="text" class="form-control" name="name" id="e_name" required>
            </div>

            <div class="col-md-8">
              <label class="form-label">Contact Info *</label>
              <input type="text" class="form-control" name="contact_info" id="e_contact" required>
            </div>

            <div class="col-md-4">
              <label class="form-label">Tenant Type *</label>
              <select class="form-control" name="tenant_type" id="e_type" required>
                <option value="">Select type</option>
                <option value="renter">Renter</option>
                <option value="buyer">Buyer</option>
              </select>
            </div>

            <div class="col-md-4">
              <label class="form-label">Lease Start (optional)</label>
              <input type="date" class="form-control" name="lease_start" id="e_lease_start">
            </div>

            <div class="col-md-4">
              <label class="form-label">Lease End (optional)</label>
              <input type="date" class="form-control" name="lease_end" id="e_lease_end">
            </div>

            <div class="col-md-4">
              <label class="form-label">Purchase Date (optional)</label>
              <input type="date" class="form-control" name="purchase_date" id="e_purchase_date">
            </div>

            <div class="col-md-4">
              <label class="form-label">Amount Paid</label>
              <input type="number" step="0.01" class="form-control" name="amount_paid" id="e_amount_paid">
            </div>
          </div>
          <div class="mt-3 small text-muted">Only Admin can save edits.</div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-warning"><i class="fas fa-save me-1"></i> Save</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Delete Modal -->
<div class="modal fade" id="deleteTenantModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content" style="border-radius:16px;">
      <form method="POST">
        <input type="hidden" name="action" value="delete_tenant">
        <input type="hidden" name="tenant_id" id="d_tenant_id">

        <div class="modal-header">
          <h5 class="modal-title"><i class="fas fa-trash text-danger me-2"></i> Delete Tenant</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body">
          <p class="mb-1">Are you sure you want to delete:</p>
          <div style="font-weight:900;color:#111827;" id="d_name"></div>
          <div class="small text-muted mt-2">This action cannot be undone.</div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-danger"><i class="fas fa-trash me-1"></i> Delete</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Access Denied Modal -->
<div class="modal fade" id="accessDeniedModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content" style="border-radius:16px;">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fas fa-lock text-danger me-2"></i> Permission Required</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div style="font-weight:900;color:#111827;margin-bottom:6px;">Access denied</div>
        <div class="text-muted">Only Admin can edit or delete tenant records.</div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Okay</button>
      </div>
    </div>
  </div>
</div>

<script src="assets/js/jquery.min.js"></script>
<script src="assets/js/bootstrap/bootstrap.bundle.min.js"></script>

<script>
  const IS_ADMIN = <?php echo $isAdmin ? 'true' : 'false'; ?>;

  function showModal(id) {
    const modal = new bootstrap.Modal(document.getElementById(id));
    modal.show();
  }

  // Search
  document.getElementById('searchInput').addEventListener('input', function(e) {
    const term = e.target.value.toLowerCase();
    document.querySelectorAll('#tenantTable tbody tr').forEach(tr => {
      const text = tr.textContent.toLowerCase();
      tr.style.display = text.includes(term) ? '' : 'none';
    });
  });

  // Agent Add Tenant
  const addBtnAgent = document.getElementById('btnAddTenantAgent');
  if (addBtnAgent) addBtnAgent.addEventListener('click', () => showModal('accessDeniedModal'));

  // Buttons
  document.querySelectorAll('#tenantTable tbody tr').forEach(tr => {
    const tenantId = tr.dataset.tenantId;
    const pid = tr.dataset.pid;
    const name = tr.dataset.name;
    const contact = tr.dataset.contact;
    const type = tr.dataset.type;
    const leaseStart = tr.dataset.leaseStart;
    const leaseEnd = tr.dataset.leaseEnd;
    const purchaseDate = tr.dataset.purchaseDate;
    const amountPaid = tr.dataset.amountPaid;

    tr.querySelector('.btn-view')?.addEventListener('click', () => {
      document.getElementById('v_tenant_id').textContent = tenantId ? '#' + tenantId : '-';
      document.getElementById('v_pid').textContent = pid ? '#' + pid : '-';
      document.getElementById('v_name').textContent = name || '-';
      document.getElementById('v_contact').textContent = contact || '-';
      document.getElementById('v_type').textContent = type || '-';
      document.getElementById('v_lease_start').textContent = leaseStart || '-';
      document.getElementById('v_lease_end').textContent = leaseEnd || '-';
      document.getElementById('v_purchase').textContent = purchaseDate || '-';
      document.getElementById('v_amount').textContent = amountPaid ? ('₦' + Number(amountPaid).toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2})) : '₦0.00';
      showModal('viewTenantModal');
    });

    tr.querySelector('.btn-edit')?.addEventListener('click', () => {
      if (!IS_ADMIN) return showModal('accessDeniedModal');

      document.getElementById('e_tenant_id').value = tenantId || '';
      document.getElementById('e_pid').value = pid || '';
      document.getElementById('e_name').value = name || '';
      document.getElementById('e_contact').value = contact || '';
      document.getElementById('e_type').value = type || '';
      document.getElementById('e_lease_start').value = leaseStart || '';
      document.getElementById('e_lease_end').value = leaseEnd || '';
      document.getElementById('e_purchase_date').value = purchaseDate || '';
      document.getElementById('e_amount_paid').value = amountPaid || '0.00';

      showModal('editTenantModal');
    });

    tr.querySelector('.btn-delete')?.addEventListener('click', () => {
      if (!IS_ADMIN) return showModal('accessDeniedModal');

      document.getElementById('d_tenant_id').value = tenantId || '';
      document.getElementById('d_name').textContent = (name ? name : 'Selected Tenant') + (pid ? ' (Property #' + pid + ')' : '');
      showModal('deleteTenantModal');
    });
  });
</script>
</body>
</html>
