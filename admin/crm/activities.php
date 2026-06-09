<?php
// /admin/crm/activities.php
require_once __DIR__ . "/_auth.php";
require_once __DIR__ . "/_db.php";
require_once __DIR__ . "/_helpers.php";

$page_title = "Activities";
require_once __DIR__ . "/_layout_top.php";

// Flash + msg
$flash = flash_get("success");
$msg = trim($_GET["msg"] ?? "");

// Filter parameters
$search    = $_GET['search'] ?? '';
$type      = $_GET['type'] ?? '';
$lead_id   = $_GET['lead_id'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to   = $_GET['date_to'] ?? '';

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;
if ($page < 1) $page = 1;

// ------------------------------
// ✅ Helper: map stage/agent IDs to names inside activity details
// ------------------------------
function activity_details_pretty(string $details, array $stageMap, array $agentMap): string
{
    $raw = trim($details);
    if ($raw === '') return '';

    // Expected format examples:
    // "Stage=2; status=New; reassigned_agent_id=3"
    // allow spaces/case variations
    $stageId = null;
    $status = null;
    $agentId = null;

    if (preg_match('/stage\s*=\s*(\d+)/i', $raw, $m)) {
        $stageId = (int)$m[1];
    }
    if (preg_match('/status\s*=\s*([A-Za-z_ -]+)/i', $raw, $m)) {
        $status = trim($m[1]);
        // stop at semicolon if someone typed extra
        $status = preg_replace('/;.*$/', '', $status);
        $status = trim($status);
    }
    if (preg_match('/reassigned_agent_id\s*=\s*(\d+)/i', $raw, $m)) {
        $agentId = (int)$m[1];
    }

    // If it doesn't match, return original
    if ($stageId === null && $status === null && $agentId === null) {
        return $raw;
    }

    $parts = [];

    if ($stageId !== null) {
        $stageName = $stageMap[$stageId] ?? ("Stage #".$stageId);
        $parts[] = "Stage: " . $stageName;
    }
    if ($status !== null && $status !== '') {
        $parts[] = "Status: " . $status;
    }
    if ($agentId !== null) {
        $agentName = $agentMap[$agentId] ?? ("Agent #".$agentId);
        $parts[] = "Reassigned to: " . $agentName;
    }

    return implode(" • ", $parts);
}

// Build query
$conditions = [];
$params = [];
$types = '';

if ($search) {
    $conditions[] = "(a.title LIKE ? OR a.details LIKE ? OR l.name LIKE ?)";
    $like = "%$search%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $types .= 'sss';
}

if ($type) {
    $conditions[] = "a.activity_type = ?";
    $params[] = $type;
    $types .= 's';
}

if ($lead_id) {
    $conditions[] = "a.lead_id = ?";
    $params[] = (int)$lead_id;
    $types .= 'i';
}

if ($date_from) {
    $conditions[] = "DATE(a.activity_at) >= ?";
    $params[] = $date_from;
    $types .= 's';
}

if ($date_to) {
    $conditions[] = "DATE(a.activity_at) <= ?";
    $params[] = $date_to;
    $types .= 's';
}

$where = $conditions ? "WHERE " . implode(" AND ", $conditions) : "";

// ------------------------------------
// COUNT (pagination base)
// ------------------------------------
$count_sql = "
    SELECT COUNT(*) as total 
    FROM crm_activities a
    LEFT JOIN crm_leads l ON l.id = a.lead_id
    $where
";

$count_stmt = $conn->prepare($count_sql);
if ($params) $count_stmt->bind_param($types, ...$params);
$count_stmt->execute();
$total_records = (int)($count_stmt->get_result()->fetch_assoc()['total'] ?? 0);
$count_stmt->close();

$total_pages = max(1, (int)ceil($total_records / $per_page));
if ($page > $total_pages) $page = $total_pages;

$offset = ($page - 1) * $per_page;

// ------------------------------------
// DATA QUERY (paged)
// ------------------------------------
$sql = "
    SELECT 
        a.*,
        l.name as lead_name,
        l.email as lead_email
    FROM crm_activities a
    LEFT JOIN crm_leads l ON l.id = a.lead_id
    $where
    ORDER BY a.activity_at DESC
    LIMIT ? OFFSET ?
";

$params2 = $params;
$types2  = $types . "ii";
$params2[] = $per_page;
$params2[] = $offset;

$stmt = $conn->prepare($sql);
$stmt->bind_param($types2, ...$params2);
$stmt->execute();
$activities = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get activity types for filter
$types_list = $conn->query("
    SELECT DISTINCT activity_type 
    FROM crm_activities 
    WHERE activity_type IS NOT NULL AND activity_type != ''
    ORDER BY activity_type
")->fetch_all(MYSQLI_ASSOC);

// Get leads for filter + modal
$leads_list = $conn->query("
    SELECT id, name 
    FROM crm_leads 
    ORDER BY name
")->fetch_all(MYSQLI_ASSOC);

// ✅ Stage map (id => name)
$stage_rows = $conn->query("
    SELECT id, name
    FROM crm_stages
    WHERE is_active = 1
    ORDER BY sort_order ASC, id ASC
")->fetch_all(MYSQLI_ASSOC);

$stageMap = [];
foreach ($stage_rows as $sr) {
    $stageMap[(int)$sr['id']] = (string)$sr['name'];
}

// ✅ Agent map (agent_id => name)
$agent_rows = $conn->query("
    SELECT agent_id, name
    FROM agents
    WHERE is_active = 1
    ORDER BY name ASC
")->fetch_all(MYSQLI_ASSOC);

$agentMap = [];
foreach ($agent_rows as $ar) {
    $agentMap[(int)$ar['agent_id']] = (string)$ar['name'];
}

// helper: page url builder
function page_url(array $overrides = []): string {
    $q = array_merge($_GET, $overrides);
    unset($q['msg']); // keep clean
    return "?" . http_build_query($q);
}
?>

<div class="container-fluid px-3 px-md-4 py-4">
    <!-- HEADER -->
    <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between mb-4">
        <div>
            <h1 class="h2 fw-bold mb-2">
                <i class="bi bi-activity me-2"></i>Activities
            </h1>
            <p class="text-muted mb-0">
                Track all your interactions with leads •
                <strong><?= number_format($total_records) ?></strong> records
            </p>
        </div>

        <div class="d-flex gap-2 mt-3 mt-md-0">
            <button class="btn btn-primary" type="button" data-bs-toggle="modal" data-bs-target="#addActivityModal">
                <i class="bi bi-plus-circle me-2"></i> Add Activity
            </button>
        </div>
    </div>

    <?php if ($flash): ?>
        <div class="alert alert-success d-flex align-items-center">
            <i class="bi bi-check-circle me-2"></i><?= h($flash) ?>
        </div>
    <?php endif; ?>

    <?php if ($msg): ?>
        <div class="alert alert-danger d-flex align-items-center">
            <i class="bi bi-exclamation-triangle me-2"></i><?= h($msg) ?>
        </div>
    <?php endif; ?>

    <!-- FILTERS -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <input type="text" class="form-control" name="search" placeholder="Search activities..."
                           value="<?= h($search) ?>">
                </div>

                <div class="col-md-2">
                    <select class="form-select" name="type">
                        <option value="">All Types</option>
                        <?php foreach ($types_list as $t): ?>
                            <option value="<?= h($t['activity_type']) ?>" <?= $type == $t['activity_type'] ? 'selected' : '' ?>>
                                <?= ucfirst(h($t['activity_type'])) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-2">
                    <select class="form-select" name="lead_id">
                        <option value="">All Leads</option>
                        <?php foreach ($leads_list as $ld): ?>
                            <option value="<?= (int)$ld['id'] ?>" <?= ((string)$lead_id === (string)$ld['id']) ? 'selected' : '' ?>>
                                <?= h($ld['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-2">
                    <input type="date" class="form-control" name="date_from" value="<?= h($date_from) ?>">
                </div>

                <div class="col-md-2">
                    <input type="date" class="form-control" name="date_to" value="<?= h($date_to) ?>">
                </div>

                <div class="col-md-1 d-grid">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-filter"></i>
                    </button>
                </div>

                <div class="col-12">
                    <a class="btn btn-sm btn-outline-secondary" href="activities.php">
                        <i class="bi bi-x-circle me-1"></i>Clear Filters
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- ACTIVITIES LIST -->
    <div class="card">
        <div class="card-body p-0">
            <?php if (empty($activities)): ?>
                <div class="text-center py-5">
                    <i class="bi bi-activity display-1 text-muted opacity-25"></i>
                    <h4 class="mt-3">No activities found</h4>
                    <p class="text-muted">Try adjusting filters or add a new activity.</p>
                    <button class="btn btn-primary" type="button" data-bs-toggle="modal" data-bs-target="#addActivityModal">
                        <i class="bi bi-plus-circle me-2"></i> Add Activity
                    </button>
                </div>
            <?php else: ?>
                <div class="list-group list-group-flush">
                    <?php foreach ($activities as $activity):

                        // icons + classes (no PHP 8 match to avoid errors on old PHP)
                        $atype = (string)($activity['activity_type'] ?? '');
                        $icon = '📝';
                        $type_class = 'bg-secondary';

                        if ($atype === 'call') { $icon = '📞'; $type_class = 'bg-primary'; }
                        elseif ($atype === 'email') { $icon = '📧'; $type_class = 'bg-success'; }
                        elseif ($atype === 'meeting') { $icon = '👥'; $type_class = 'bg-warning'; }
                        elseif ($atype === 'whatsapp') { $icon = '💬'; $type_class = 'bg-info'; }
                        elseif ($atype === 'viewing') { $icon = '👁️'; $type_class = 'bg-dark'; }
                        elseif ($atype === 'note') { $icon = '📝'; $type_class = 'bg-secondary'; }

                        // ✅ Pretty details mapping Stage ID -> Stage Name, Agent ID -> Agent Name
                        $details_raw = (string)($activity['details'] ?? '');
                        $details_pretty = $details_raw !== '' ? activity_details_pretty($details_raw, $stageMap, $agentMap) : '';

                    ?>
                        <div class="list-group-item list-group-item-action">
                            <div class="d-flex gap-3">
                                <div class="flex-shrink-0">
                                    <div class="rounded-circle <?= $type_class ?> text-white d-flex align-items-center justify-content-center"
                                         style="width: 40px; height: 40px; font-size: 1.2rem;">
                                        <?= $icon ?>
                                    </div>
                                </div>

                                <div class="flex-grow-1">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <h6 class="mb-1"><?= h($activity['title']) ?></h6>

                                            <?php if (!empty($activity['lead_name'])): ?>
                                                <div class="small">
                                                    <i class="bi bi-person me-1"></i>
                                                    <a href="lead_view.php?id=<?= (int)$activity['lead_id'] ?>" class="text-decoration-none">
                                                        <?= h($activity['lead_name']) ?>
                                                    </a>
                                                    <?php if (!empty($activity['lead_email'])): ?>
                                                        <span class="text-muted ms-2"><?= h($activity['lead_email']) ?></span>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endif; ?>

                                            <?php if ($details_pretty !== ''): ?>
                                                <p class="mb-1 small"><?= h($details_pretty) ?></p>
                                            <?php endif; ?>
                                        </div>

                                        <div class="text-end">
                                            <div class="small text-muted">
                                                <?= date('M j, Y g:i A', strtotime($activity['activity_at'])) ?>
                                            </div>
                                            <span class="badge <?= $type_class ?>">
                                                <?= ucfirst(h($atype ?: 'other')) ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- ✅ PAGINATION (fixed + clamped) -->
                <?php if ($total_pages > 1): ?>
                    <nav class="p-3 border-top">
                        <ul class="pagination justify-content-center mb-0 flex-wrap">

                            <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                                <a class="page-link" href="<?= h(page_url(['page' => max(1, $page - 1)])) ?>">Previous</a>
                            </li>

                            <?php
                            $start = max(1, $page - 2);
                            $end   = min($total_pages, $page + 2);

                            if ($start > 1) {
                                echo '<li class="page-item"><a class="page-link" href="'.h(page_url(['page'=>1])).'">1</a></li>';
                                if ($start > 2) echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                            }

                            for ($i = $start; $i <= $end; $i++):
                            ?>
                                <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                    <a class="page-link" href="<?= h(page_url(['page' => $i])) ?>"><?= $i ?></a>
                                </li>
                            <?php endfor; ?>

                            <?php
                            if ($end < $total_pages) {
                                if ($end < $total_pages - 1) echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                echo '<li class="page-item"><a class="page-link" href="'.h(page_url(['page'=>$total_pages])).'">'.$total_pages.'</a></li>';
                            }
                            ?>

                            <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>">
                                <a class="page-link" href="<?= h(page_url(['page' => min($total_pages, $page + 1)])) ?>">Next</a>
                            </li>

                        </ul>
                    </nav>
                <?php endif; ?>

            <?php endif; ?>
        </div>
    </div>
</div>

<!-- ✅ Add Activity Modal (FIXED SUBMIT) -->
<div class="modal fade" id="addActivityModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">

            <form method="POST" action="activity_save.php" id="addActivityForm">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Activity</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <div class="row g-3">

                        <div class="col-md-6">
                            <label class="form-label">Lead</label>
                            <select class="form-select" name="lead_id" required>
                                <option value="">Select Lead</option>
                                <?php foreach ($leads_list as $ld): ?>
                                    <option value="<?= (int)$ld['id'] ?>"><?= h($ld['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Activity Type</label>
                            <select class="form-select" name="activity_type" required>
                                <option value="call">Call</option>
                                <option value="email">Email</option>
                                <option value="meeting">Meeting</option>
                                <option value="whatsapp">WhatsApp</option>
                                <option value="viewing">Property Viewing</option>
                                <option value="note">Note</option>
                            </select>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Title</label>
                            <input type="text" class="form-control" name="title" required>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Details</label>
                            <textarea class="form-control" name="details" rows="4"></textarea>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Date & Time</label>
                            <input type="datetime-local" class="form-control" name="activity_at"
                                   value="<?= date('Y-m-d\TH:i') ?>" required>
                        </div>

                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <!-- ✅ THIS NOW SUBMITS -->
                    <button type="submit" class="btn btn-primary">Save Activity</button>
                </div>
            </form>

        </div>
    </div>
</div>

<?php require_once __DIR__ . "/_layout_bottom.php"; ?>
