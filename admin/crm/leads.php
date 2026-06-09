<?php
// /admin/crm/leads.php
require_once __DIR__ . "/_auth.php";
require_once __DIR__ . "/_db.php";
require_once __DIR__ . "/_helpers.php";

$page_title = "Leads Management";
require_once __DIR__ . "/_layout_top.php";

$success = '';
$error   = '';

// ----------------------------------------------------
// Logged in CRM user
// ----------------------------------------------------
$currentUserId = (int)($_SESSION['crm_user_id'] ?? $_SESSION['user_id'] ?? 0);
$isAdminUser   = function_exists('crm_is_admin') ? crm_is_admin($conn) : (function_exists('is_admin') ? is_admin() : false);

// ----------------------------------------------------
// ✅ Show "msg" from address bar AS A POPUP + alert
// Example: leads.php?msg=Access+denied%3A+lead+not+assigned+to+you.
// ----------------------------------------------------
$getMsg = trim((string)($_GET['msg'] ?? ''));
if ($getMsg !== '') {
    $error = $getMsg;
}

// ----------------------------------
// ✅ In-page DELETE handler (POST)
// ----------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete_lead') {
    $leadId = (int)($_POST['lead_id'] ?? 0);

    if ($leadId <= 0) {
        $error = "Invalid lead selected.";
    } else {
        // Verify lead exists and ownership
        $chk = $conn->prepare("SELECT id, name, agent_id FROM crm_leads WHERE id=? LIMIT 1");
        if (!$chk) {
            $error = "Database error: cannot validate lead.";
        } else {
            $chk->bind_param("i", $leadId);
            $chk->execute();
            $row = $chk->get_result()->fetch_assoc();
            $chk->close();

            if (!$row) {
                $error = "Lead not found.";
            } else {
                $leadName = (string)$row['name'];
                $leadAgentId = isset($row['agent_id']) ? (int)$row['agent_id'] : 0;

                // ✅ ownership check for agents
                if (!$isAdminUser && $leadAgentId !== $currentUserId) {
                    $error = "Access denied: lead not assigned to you.";
                } else {
                    try {
                        // delete child records first if they exist
                        $stmt = $conn->prepare("DELETE FROM crm_activities WHERE lead_id=?");
                        if ($stmt) {
                            $stmt->bind_param("i", $leadId);
                            $stmt->execute();
                            $stmt->close();
                        }

                        $stmt = $conn->prepare("DELETE FROM crm_tasks WHERE lead_id=?");
                        if ($stmt) {
                            $stmt->bind_param("i", $leadId);
                            $stmt->execute();
                            $stmt->close();
                        }

                        // Uncomment if you later have crm_notes
                        // $stmt = $conn->prepare("DELETE FROM crm_notes WHERE lead_id=?");
                        // if ($stmt) {
                        //     $stmt->bind_param("i", $leadId);
                        //     $stmt->execute();
                        //     $stmt->close();
                        // }
                    } catch (Throwable $e) {
                        // ignore and continue to lead delete
                    }

                    if ($isAdminUser) {
                        $del = $conn->prepare("DELETE FROM crm_leads WHERE id=? LIMIT 1");
                        if (!$del) {
                            $error = "Database error: cannot delete lead.";
                        } else {
                            $del->bind_param("i", $leadId);
                            $del->execute();
                            $affected = $del->affected_rows;
                            $del->close();

                            if ($affected > 0) {
                                $success = "Lead deleted successfully: {$leadName} (#{$leadId}).";
                            } else {
                                $error = "Delete failed.";
                            }
                        }
                    } else {
                        // ✅ agent can only delete assigned leads
                        $del = $conn->prepare("DELETE FROM crm_leads WHERE id=? AND agent_id=? LIMIT 1");
                        if (!$del) {
                            $error = "Database error: cannot delete lead.";
                        } else {
                            $del->bind_param("ii", $leadId, $currentUserId);
                            $del->execute();
                            $affected = $del->affected_rows;
                            $del->close();

                            if ($affected > 0) {
                                $success = "Lead deleted successfully: {$leadName} (#{$leadId}).";
                            } else {
                                $error = "Access denied: lead not assigned to you.";
                            }
                        }
                    }
                }
            }
        }
    }
}

// ----------------------------------
// Filters
// ----------------------------------
$search   = $_GET['search'] ?? '';
$status   = $_GET['status'] ?? '';
$stage_id = $_GET['stage_id'] ?? '';
$source   = $_GET['source'] ?? '';
$page     = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 15;

if ($page < 1) $page = 1;

// Build query
$conditions = [];
$params = [];
$types = '';

// ✅ agents only see their own leads
if (!$isAdminUser) {
    $conditions[] = "l.agent_id = ?";
    $params[] = $currentUserId;
    $types .= 'i';
}

if ($search) {
    $conditions[] = "(l.name LIKE ? OR l.email LIKE ? OR l.phone LIKE ?)";
    $like = "%$search%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $types .= 'sss';
}

if ($status) {
    $conditions[] = "l.status = ?";
    $params[] = $status;
    $types .= 's';
}

if ($stage_id !== '' && is_numeric($stage_id)) {
    $conditions[] = "l.stage_id = ?";
    $params[] = (int)$stage_id;
    $types .= 'i';
}

if ($source) {
    $conditions[] = "l.source = ?";
    $params[] = $source;
    $types .= 's';
}

$where = $conditions ? "WHERE " . implode(" AND ", $conditions) : "";

// Get total count
$count_sql = "SELECT COUNT(*) as total FROM crm_leads l $where";
$count_stmt = $conn->prepare($count_sql);
if ($params) $count_stmt->bind_param($types, ...$params);
$count_stmt->execute();
$total_records = (int)($count_stmt->get_result()->fetch_assoc()['total'] ?? 0);
$count_stmt->close();

$total_pages = max(1, (int)ceil($total_records / $per_page));
if ($page > $total_pages) $page = $total_pages;

$offset = ($page - 1) * $per_page;

// Get leads
$sql = "
    SELECT l.*, s.name as stage_name
    FROM crm_leads l
    LEFT JOIN crm_stages s ON l.stage_id = s.id
    $where
    ORDER BY l.created_at DESC
    LIMIT ? OFFSET ?
";

$params2 = $params;
$types2  = $types . 'ii';
$params2[] = $per_page;
$params2[] = $offset;

$stmt = $conn->prepare($sql);
$stmt->bind_param($types2, ...$params2);
$stmt->execute();
$leads = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get distinct statuses, sources, and stages for filters
if ($isAdminUser) {
    $statuses = $conn->query("SELECT DISTINCT status FROM crm_leads WHERE status IS NOT NULL ORDER BY status")->fetch_all(MYSQLI_ASSOC);
    $sources  = $conn->query("SELECT DISTINCT source FROM crm_leads WHERE source IS NOT NULL AND source != '' ORDER BY source")->fetch_all(MYSQLI_ASSOC);
} else {
    $st1 = $conn->prepare("SELECT DISTINCT status FROM crm_leads WHERE agent_id=? AND status IS NOT NULL ORDER BY status");
    $st1->bind_param("i", $currentUserId);
    $st1->execute();
    $statuses = $st1->get_result()->fetch_all(MYSQLI_ASSOC);
    $st1->close();

    $st2 = $conn->prepare("SELECT DISTINCT source FROM crm_leads WHERE agent_id=? AND source IS NOT NULL AND source != '' ORDER BY source");
    $st2->bind_param("i", $currentUserId);
    $st2->execute();
    $sources = $st2->get_result()->fetch_all(MYSQLI_ASSOC);
    $st2->close();
}

$stages = $conn->query("SELECT id, name FROM crm_stages WHERE is_active = 1 ORDER BY sort_order")->fetch_all(MYSQLI_ASSOC);

// Get stats
if ($isAdminUser) {
    $stats = $conn->query("
        SELECT
            COUNT(*) as total,
            SUM(CASE WHEN status != 'Closed' THEN 1 ELSE 0 END) as active,
            SUM(CASE WHEN status = 'Closed' THEN 1 ELSE 0 END) as closed
        FROM crm_leads
    ")->fetch_assoc();
} else {
    $statsStmt = $conn->prepare("
        SELECT
            COUNT(*) as total,
            SUM(CASE WHEN status != 'Closed' THEN 1 ELSE 0 END) as active,
            SUM(CASE WHEN status = 'Closed' THEN 1 ELSE 0 END) as closed
        FROM crm_leads
        WHERE agent_id = ?
    ");
    $statsStmt->bind_param("i", $currentUserId);
    $statsStmt->execute();
    $stats = $statsStmt->get_result()->fetch_assoc();
    $statsStmt->close();
}
?>

<!-- ✅ Toast container (Popup) -->
<div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1100;">
    <div id="pageToast" class="toast align-items-center text-bg-danger border-0" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body" id="pageToastBody">Access denied.</div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    </div>
</div>

<div class="container-fluid px-3 px-md-4 py-4">
    <!-- HEADER -->
    <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between mb-4">
        <div>
            <h1 class="h2 fw-bold mb-2">
                <i class="bi bi-people me-2"></i>Leads Management
            </h1>
            <p class="text-muted mb-0">
                <?= number_format((int)$stats['total']) ?> total leads •
                <?= number_format((int)$stats['active']) ?> active •
                <?= number_format((int)$stats['closed']) ?> closed
            </p>
        </div>

        <div class="d-flex gap-2 mt-3 mt-md-0">
            <a href="lead_new.php" class="btn btn-primary d-flex align-items-center">
                <i class="bi bi-plus-circle me-2"></i> Add Lead
            </a>
            <button class="btn btn-outline-primary" type="button" data-bs-toggle="modal" data-bs-target="#exportModal">
                <i class="bi bi-download me-2"></i> Export
            </button>
        </div>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success d-flex align-items-center">
            <i class="bi bi-check-circle me-2"></i><?= h($success) ?>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger d-flex align-items-center">
            <i class="bi bi-exclamation-triangle me-2"></i><?= h($error) ?>
        </div>
    <?php endif; ?>

    <!-- FILTERS -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <input type="text" class="form-control" name="search" placeholder="Search leads..."
                           value="<?= h($search) ?>" aria-label="Search">
                </div>

                <div class="col-md-2">
                    <select class="form-select" name="status">
                        <option value="">All Statuses</option>
                        <?php foreach ($statuses as $s): ?>
                            <option value="<?= h($s['status']) ?>" <?= $status == $s['status'] ? 'selected' : '' ?>>
                                <?= h($s['status']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-2">
                    <select class="form-select" name="stage_id">
                        <option value="">All Stages</option>
                        <?php foreach ($stages as $stage): ?>
                            <option value="<?= (int)$stage['id'] ?>" <?= ((string)$stage_id === (string)$stage['id']) ? 'selected' : '' ?>>
                                <?= h($stage['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-2">
                    <select class="form-select" name="source">
                        <option value="">All Sources</option>
                        <?php foreach ($sources as $src): ?>
                            <option value="<?= h($src['source']) ?>" <?= $source == $src['source'] ? 'selected' : '' ?>>
                                <?= h($src['source']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-filter me-2"></i> Filter
                    </button>
                    <a href="leads.php" class="btn btn-outline-secondary">
                        <i class="bi bi-x-circle me-2"></i> Clear
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- LEADS TABLE -->
    <div class="card">
        <div class="card-body p-0">
            <?php if (empty($leads)): ?>
                <div class="text-center py-5">
                    <i class="bi bi-people display-1 text-muted opacity-25"></i>
                    <h4 class="mt-3">No leads found</h4>
                    <p class="text-muted">Try adjusting your filters or add a new lead.</p>
                    <a href="lead_new.php" class="btn btn-primary">
                        <i class="bi bi-plus-circle me-2"></i> Add First Lead
                    </a>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                        <tr>
                            <th>Name</th>
                            <th>Contact</th>
                            <th>Status</th>
                            <th>Stage</th>
                            <th>Source</th>
                            <th>Created</th>
                            <th class="text-end">Actions</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($leads as $lead): ?>
                            <?php
                            $leadAssignedToCurrent = $isAdminUser || ((int)($lead['agent_id'] ?? 0) === $currentUserId);
                            ?>
                            <tr>
                                <td>
                                    <div class="fw-semibold"><?= h($lead['name']) ?></div>
                                    <div class="small text-muted">ID: #<?= (int)$lead['id'] ?></div>
                                </td>
                                <td>
                                    <div class="small"><?= h($lead['email']) ?></div>
                                    <?php if (!empty($lead['phone'])): ?>
                                        <div class="small text-muted"><?= h($lead['phone']) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-<?=
                                        ($lead['status'] == 'New') ? 'info' :
                                        (($lead['status'] == 'Contacted') ? 'warning' :
                                            (($lead['status'] == 'Interested') ? 'success' : 'secondary'))
                                    ?>">
                                        <?= h($lead['status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if (!empty($lead['stage_name'])): ?>
                                        <span class="badge bg-light text-dark">
                                            <?= h($lead['stage_name']) ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= h($lead['source'] ?: '—') ?></td>
                                <td>
                                    <?php if (!empty($lead['created_at'])): ?>
                                        <div class="small"><?= date('M j, Y', strtotime($lead['created_at'])) ?></div>
                                        <div class="small text-muted"><?= date('g:i A', strtotime($lead['created_at'])) ?></div>
                                    <?php else: ?>
                                        <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm">
                                        <a href="lead_view.php?id=<?= (int)$lead['id'] ?>" class="btn btn-outline-primary">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="lead_edit.php?id=<?= (int)$lead['id'] ?>" class="btn btn-outline-secondary">
                                            <i class="bi bi-pencil"></i>
                                        </a>

                                        <?php if ($leadAssignedToCurrent): ?>
                                            <button
                                                type="button"
                                                class="btn btn-outline-danger"
                                                data-bs-toggle="modal"
                                                data-bs-target="#deleteLeadModal"
                                                data-lead-id="<?= (int)$lead['id'] ?>"
                                                data-lead-name="<?= h($lead['name']) ?>"
                                            >
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        <?php else: ?>
                                            <button
                                                type="button"
                                                class="btn btn-outline-danger disabled"
                                                title="You can only delete leads assigned to you."
                                                disabled
                                            >
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- PAGINATION -->
                <?php if ($total_pages > 1): ?>
                    <nav class="p-3 border-top">
                        <ul class="pagination justify-content-center mb-0">
                            <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                                <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>">Previous</a>
                            </li>

                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <?php if ($i == 1 || $i == $total_pages || ($i >= $page - 2 && $i <= $page + 2)): ?>
                                    <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                        <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"><?= $i ?></a>
                                    </li>
                                <?php elseif ($i == $page - 3 || $i == $page + 3): ?>
                                    <li class="page-item disabled"><span class="page-link">...</span></li>
                                <?php endif; ?>
                            <?php endfor; ?>

                            <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>">
                                <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>">Next</a>
                            </li>
                        </ul>
                    </nav>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- ✅ Delete Lead Modal -->
<div class="modal fade" id="deleteLeadModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="post" class="modal-content">
            <input type="hidden" name="action" value="delete_lead">
            <input type="hidden" name="lead_id" id="del_lead_id" value="">

            <div class="modal-header">
                <h5 class="modal-title text-danger">
                    <i class="bi bi-trash me-2"></i>Delete Lead
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <p class="mb-2">You are about to delete:</p>
                <div class="p-2 border rounded bg-light">
                    <strong id="del_lead_name">Lead</strong>
                </div>
                <div class="alert alert-warning mt-3 mb-0">
                    This will also remove tasks/activities linked to this lead (if applicable). This action cannot be undone.
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-danger">Yes, Delete</button>
            </div>
        </form>
    </div>
</div>

<!-- Export Modal -->
<div class="modal fade" id="exportModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" action="export_leads.php" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Export Leads</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Format</label>
                    <select class="form-select" name="format">
                        <option value="csv">CSV</option>
                        <option value="excel">Excel</option>
                        <option value="pdf">PDF</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Date Range</label>
                    <select class="form-select" name="range">
                        <option value="all">All Leads</option>
                        <option value="month">This Month</option>
                        <option value="week">This Week</option>
                        <option value="today">Today</option>
                    </select>
                </div>

                <input type="hidden" name="search" value="<?= h($search) ?>">
                <input type="hidden" name="status" value="<?= h($status) ?>">
                <input type="hidden" name="stage_id" value="<?= h((string)$stage_id) ?>">
                <input type="hidden" name="source" value="<?= h($source) ?>">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Export</button>
            </div>
        </form>
    </div>
</div>

<script>
// Fill delete modal
document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('deleteLeadModal');
    if (modal) {
        modal.addEventListener('show.bs.modal', function (event) {
            const btn = event.relatedTarget;
            if (!btn) return;

            const id = btn.getAttribute('data-lead-id') || '';
            const name = btn.getAttribute('data-lead-name') || 'Lead';

            document.getElementById('del_lead_id').value = id;
            document.getElementById('del_lead_name').textContent = name;
        });
    }

    // ✅ Show toast popup if msg exists in URL
    const urlMsg = <?= json_encode($getMsg, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
    if (urlMsg && typeof bootstrap !== 'undefined') {
        const toastEl = document.getElementById('pageToast');
        const toastBody = document.getElementById('pageToastBody');
        if (toastEl && toastBody) {
            toastBody.textContent = urlMsg;
            const toast = new bootstrap.Toast(toastEl, { delay: 6000 });
            toast.show();
        }
    }
});
</script>

<?php require_once __DIR__ . "/_layout_bottom.php"; ?>