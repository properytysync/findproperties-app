<?php
require_once __DIR__ . "/_auth.php";
require_once __DIR__ . "/_db.php";
require_once __DIR__ . "/_helpers.php";

$page_title = "Lead";
require_once __DIR__ . "/_layout_top.php";

// ✅ Logged in user (admin or agent)
$USER_ID   = current_user_id();
$IS_ADMIN  = is_admin();
$IS_AGENT  = is_agent();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) redirect("leads.php");

// ✅ Lead + assigned agent
$stmt = $conn->prepare("
  SELECT l.*, s.name AS stage_name, a.name AS agent_name, a.agent_id AS agent_id, a.email AS agent_email
  FROM crm_leads l
  LEFT JOIN crm_stages s ON s.id = l.stage_id
  LEFT JOIN agents a ON a.agent_id = l.agent_id
  WHERE l.id=?
  LIMIT 1
");
$stmt->bind_param("i", $id);
$stmt->execute();
$lead = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$lead) redirect("leads.php");

// ✅ Agents can only open leads assigned to them (optional but recommended)
// If you want agents to view ALL leads, remove this block.
if ($IS_AGENT && (int)$lead['agent_id'] !== $USER_ID) {
  redirect("leads.php?msg=" . urlencode("Access denied: lead not assigned to you."));
}

// stages for quick change
$stages = [];
$res = $conn->query("SELECT id, name FROM crm_stages WHERE is_active=1 ORDER BY sort_order ASC, id ASC");
while ($r = $res->fetch_assoc()) $stages[] = $r;

// agents list (admin only)
$agents = [];
if ($IS_ADMIN) {
  $res = $conn->query("SELECT agent_id, name, email FROM agents ORDER BY name ASC");
  while ($r = $res->fetch_assoc()) $agents[] = $r;
}

$error = "";

/**
 * Add note
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_note'])) {
  $note = trim($_POST['note'] ?? '');
  if ($note === '') {
    $error = "Note cannot be empty.";
  } else {
    $n = $conn->prepare("INSERT INTO crm_lead_notes (lead_id, admin_id, note) VALUES (?, ?, ?)");
    $n->bind_param("iis", $id, $USER_ID, $note);
    $n->execute();
    $n->close();

    $a = $conn->prepare("INSERT INTO crm_activities (lead_id, admin_id, activity_type, title, details) VALUES (?, ?, 'note', 'Note added', ?)");
    $a->bind_param("iis", $id, $USER_ID, $note);
    $a->execute();
    $a->close();

    flash_set('success', 'Note added.');
    redirect("lead_view.php?id=" . $id);
  }
}

/**
 * Change stage / status
 * ✅ Admin can also reassign agent here
 * ✅ Agent cannot reassign agent_id (field ignored)
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_stage'])) {
  $stage_id = (int)($_POST['stage_id'] ?? 0);
  $status = trim($_POST['status'] ?? 'New');

  // Admin may reassign
  $new_agent_id = null;
  if ($IS_ADMIN) {
    $new_agent_id = ($_POST['agent_id'] ?? '') !== '' ? (int)$_POST['agent_id'] : null;
  }

  if ($stage_id > 0) {

    if ($IS_ADMIN) {
      $agentParam = $new_agent_id ?? 0;
      $u = $conn->prepare("UPDATE crm_leads SET stage_id=?, status=?, agent_id=NULLIF(?,0), updated_at=NOW() WHERE id=?");
      $u->bind_param("isii", $stage_id, $status, $agentParam, $id);
      $u->execute();
      $u->close();

      $details = "Stage={$stage_id}; status={$status}; reassigned_agent_id=" . ($new_agent_id ?? 'NULL');
      $a = $conn->prepare("INSERT INTO crm_activities (lead_id, admin_id, activity_type, title, details) VALUES (?, ?, 'status_change', 'Stage/Status/Agent updated', ?)");
      $a->bind_param("iis", $id, $USER_ID, $details);
      $a->execute();
      $a->close();

    } else {
      // Agent: only stage/status
      $u = $conn->prepare("UPDATE crm_leads SET stage_id=?, status=?, updated_at=NOW() WHERE id=?");
      $u->bind_param("isi", $stage_id, $status, $id);
      $u->execute();
      $u->close();

      $details = "Stage={$stage_id}; status={$status}";
      $a = $conn->prepare("INSERT INTO crm_activities (lead_id, admin_id, activity_type, title, details) VALUES (?, ?, 'status_change', 'Stage/Status updated', ?)");
      $a->bind_param("iis", $id, $USER_ID, $details);
      $a->execute();
      $a->close();
    }

    flash_set('success', 'Lead updated.');
    redirect("lead_view.php?id=" . $id);
  }
}

/**
 * Create task for this lead
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_task'])) {
  $title = trim($_POST['title'] ?? '');
  $due_at = dt_input_to_sql($_POST['due_at'] ?? '');

  if ($title === '' || !$due_at) {
    $error = "Task title and due date are required.";
  } else {
    // Assigned admin_id = current user (admin/agent)
    $t = $conn->prepare("INSERT INTO crm_tasks (lead_id, assigned_admin_id, title, due_at, status, created_at, updated_at)
                         VALUES (?, ?, ?, ?, 'open', NOW(), NOW())");
    $t->bind_param("iiss", $id, $USER_ID, $title, $due_at);
    $t->execute();
    $t->close();

    $a = $conn->prepare("INSERT INTO crm_activities (lead_id, admin_id, activity_type, title, details) VALUES (?, ?, 'other', 'Task created', ?)");
    $a->bind_param("iis", $id, $USER_ID, $title);
    $a->execute();
    $a->close();

    flash_set('success', 'Task created.');
    redirect("lead_view.php?id=" . $id);
  }
}

$flash = flash_get('success');

// timeline
$acts = $conn->prepare("
  SELECT activity_type, title, details, activity_at
  FROM crm_activities
  WHERE lead_id=?
  ORDER BY activity_at DESC
  LIMIT 200
");
$acts->bind_param("i", $id);
$acts->execute();
$actsRes = $acts->get_result();

// lead notes
$notesStmt = $conn->prepare("
  SELECT note, created_at
  FROM crm_lead_notes
  WHERE lead_id=?
  ORDER BY created_at DESC
  LIMIT 200
");
$notesStmt->bind_param("i", $id);
$notesStmt->execute();
$notesRes = $notesStmt->get_result();

// lead tasks
$tasksStmt = $conn->prepare("
  SELECT id, title, due_at, status
  FROM crm_tasks
  WHERE lead_id=?
  ORDER BY due_at ASC, id DESC
  LIMIT 200
");
$tasksStmt->bind_param("i", $id);
$tasksStmt->execute();
$tasksRes = $tasksStmt->get_result();
?>

<div class="d-flex align-items-center justify-content-between mb-3">
  <div>
    <h4 class="mb-0"><?php echo h($lead['name']); ?></h4>
    <div class="text-muted small">
      <?php echo h($lead['email'] ?? '-'); ?> • <?php echo h($lead['phone'] ?? '-'); ?>
      • Source: <?php echo h($lead['source'] ?? '-'); ?>
    </div>

   
  </div>

  <div class="d-flex gap-2">
    <a class="btn btn-outline-secondary btn-sm" href="lead_edit.php?id=<?php echo (int)$lead['id']; ?>">Edit</a>
    <a class="btn btn-outline-secondary btn-sm" href="leads.php">Back</a>
  </div>
</div>

<?php if ($flash): ?><div class="alert alert-success"><?php echo h($flash); ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger"><?php echo h($error); ?></div><?php endif; ?>

<div class="row g-3">
  <div class="col-12 col-lg-4">
    <div class="card mb-3">
      <div class="card-header"><strong>Lead Info</strong></div>
      <div class="card-body">
        <div class="small text-muted">Created</div>
        <div><?php echo h($lead['created_at'] ?? '—'); ?></div>

        <div class="small text-muted mt-2">Updated</div>
        <div><?php echo h($lead['updated_at'] ?? '—'); ?></div>

        <div class="small text-muted mt-2">Assigned Agent</div>
        <div class="fw-semibold">
          <?php echo !empty($lead['agent_name']) ? h($lead['agent_name']) : '—'; ?>
        </div>
      </div>
    </div>

    <div class="card mb-3">
      <div class="card-header"><strong>Quick Update</strong></div>
      <div class="card-body">
        <form method="post" class="row g-2">
          <input type="hidden" name="change_stage" value="1">

          <div class="col-12">
            <label class="form-label">Stage</label>
            <select class="form-select" name="stage_id" required>
              <?php foreach ($stages as $s): ?>
                <option value="<?php echo (int)$s['id']; ?>" <?php echo ((int)$lead['stage_id']===(int)$s['id'])?'selected':''; ?>>
                  <?php echo h($s['name']); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="col-12">
            <label class="form-label">Status</label>
            <input class="form-control" name="status" value="<?php echo h($lead['status'] ?? 'New'); ?>" placeholder="New / Contacted / Interested / Closed">
          </div>

          <?php if ($IS_ADMIN): ?>
            <div class="col-12">
              <label class="form-label">Reassign Agent </label>
              <select class="form-select" name="agent_id">
                <option value="">-- None --</option>
                <?php foreach ($agents as $ag): ?>
                  <option value="<?php echo (int)$ag['agent_id']; ?>" <?php echo ((int)$lead['agent_id']===(int)$ag['agent_id'])?'selected':''; ?>>
                    <?php echo h($ag['name']); ?> (<?php echo h($ag['email']); ?>)
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
          <?php endif; ?>

          <div class="col-12">
            <button class="btn btn-primary w-100">Save</button>
          </div>
        </form>
      </div>
    </div>

    <div class="card">
      <div class="card-header"><strong>Create Task</strong></div>
      <div class="card-body">
        <form method="post" class="row g-2">
          <input type="hidden" name="add_task" value="1">
          <div class="col-12">
            <label class="form-label">Task title</label>
            <input class="form-control" name="title" placeholder="Call lead, schedule viewing..." required>
          </div>
          <div class="col-12">
            <label class="form-label">Due</label>
            <input class="form-control" type="datetime-local" name="due_at" required>
          </div>
          <div class="col-12">
            <button class="btn btn-outline-primary w-100">Add Task</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <div class="col-12 col-lg-8">
    <div class="card mb-3">
      <div class="card-header"><strong>Add Note</strong></div>
      <div class="card-body">
        <form method="post">
          <input type="hidden" name="add_note" value="1">
          <textarea class="form-control" name="note" rows="3" placeholder="Write note..." required></textarea>
          <button class="btn btn-primary mt-2">Save Note</button>
        </form>
      </div>
    </div>

    <div class="card mb-3">
      <div class="card-header"><strong>Tasks for this Lead</strong></div>
      <div class="card-body">
        <?php if ($tasksRes->num_rows === 0): ?>
          <div class="text-muted">No tasks yet.</div>
        <?php else: ?>
          <?php while ($t = $tasksRes->fetch_assoc()): ?>
            <div class="border rounded p-2 mb-2">
              <div class="d-flex justify-content-between">
                <div class="fw-semibold"><?php echo h($t['title']); ?></div>
                <div class="text-muted small"><?php echo h($t['due_at']); ?></div>
              </div>
              <div class="small">Status: <span class="badge text-bg-light"><?php echo h($t['status']); ?></span></div>
              <div class="mt-2">
                <a class="btn btn-sm btn-outline-primary" href="task_edit.php?id=<?php echo (int)$t['id']; ?>">Edit Task</a>
              </div>
            </div>
          <?php endwhile; ?>
        <?php endif; ?>
      </div>
    </div>

    <div class="card mb-3">
      <div class="card-header"><strong>Notes</strong></div>
      <div class="card-body">
        <?php if ($notesRes->num_rows === 0): ?>
          <div class="text-muted">No notes yet.</div>
        <?php else: ?>
          <?php while ($n = $notesRes->fetch_assoc()): ?>
            <div class="border rounded p-2 mb-2">
              <div class="text-muted small"><?php echo h($n['created_at']); ?></div>
              <div><?php echo nl2br(h($n['note'])); ?></div>
            </div>
          <?php endwhile; ?>
        <?php endif; ?>
      </div>
    </div>

    <div class="card">
      <div class="card-header"><strong>Timeline</strong></div>
      <div class="card-body">
        <?php
          $hadAny = false;
          while ($a = $actsRes->fetch_assoc()):
            $hadAny = true;
        ?>
          <div class="border rounded p-2 mb-2 bg-white">
            <div class="d-flex justify-content-between">
              <div class="fw-semibold">
                <?php echo h($a['title'] ?? '-'); ?>
                <span class="badge text-bg-light"><?php echo h($a['activity_type']); ?></span>
              </div>
              <div class="text-muted small"><?php echo h($a['activity_at']); ?></div>
            </div>
            <?php if (!empty($a['details'])): ?>
              <div class="text-muted"><?php echo nl2br(h($a['details'])); ?></div>
            <?php endif; ?>
          </div>
        <?php endwhile; ?>

        <?php if (!$hadAny): ?>
          <div class="text-muted">No activity yet.</div>
        <?php endif; ?>
      </div>
    </div>

  </div>
</div>

<?php
$acts->close();
$notesStmt->close();
$tasksStmt->close();
require_once __DIR__ . "/_layout_bottom.php";
?>