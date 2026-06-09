<?php
require_once __DIR__ . "/_auth.php";
require_once __DIR__ . "/_db.php";
require_once __DIR__ . "/_helpers.php";

$page_title = "Edit Lead";
require_once __DIR__ . "/_layout_top.php";

$USER_ID  = current_user_id();
$IS_ADMIN = is_admin();
$IS_AGENT = is_agent();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) redirect("leads.php");

$stmt = $conn->prepare("
  SELECT l.*, a.name AS agent_name
  FROM crm_leads l
  LEFT JOIN agents a ON a.agent_id = l.agent_id
  WHERE l.id=?
  LIMIT 1
");
$stmt->bind_param("i", $id);
$stmt->execute();
$lead = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$lead) redirect("leads.php");

// ✅ Optional: agent can only edit leads assigned to them
if ($IS_AGENT && (int)$lead['agent_id'] !== $USER_ID) {
  redirect("leads.php?msg=" . urlencode("Access denied: lead not assigned to you."));
}

// stages
$stages = [];
$res = $conn->query("SELECT id, name FROM crm_stages WHERE is_active=1 ORDER BY sort_order ASC, id ASC");
while ($r = $res->fetch_assoc()) $stages[] = $r;

// agents list (admin only)
$agents = [];
if ($IS_ADMIN) {
  $ar = $conn->query("SELECT agent_id, name, email FROM agents ORDER BY name ASC");
  while ($r = $ar->fetch_assoc()) $agents[] = $r;
}

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $name    = trim($_POST['name'] ?? '');
  $email   = trim($_POST['email'] ?? '');
  $phone   = trim($_POST['phone'] ?? '');
  $source  = trim($_POST['source'] ?? '');
  $stage_id = (int)($_POST['stage_id'] ?? 0);
  $status  = trim($_POST['status'] ?? 'New');

  // ✅ assignment rules
  $agent_id = null;
  if ($IS_ADMIN) {
    $agent_id = ($_POST['agent_id'] ?? '') !== '' ? (int)$_POST['agent_id'] : null;
  } else {
    // agent cannot reassign
    $agent_id = (int)$lead['agent_id'];
  }

  if ($name === '' || $email === '' || $stage_id <= 0) {
    $error = "Name, Email and Stage are required.";
  } else {

    $agentParam = $agent_id ?? 0;

    $u = $conn->prepare("
      UPDATE crm_leads
      SET name=?,
          email=?,
          phone=NULLIF(?, ''),
          source=NULLIF(?, ''),
          stage_id=?,
          status=?,
          agent_id = " . ($IS_ADMIN ? "NULLIF(?,0)" : "agent_id") . ",
          updated_at=NOW()
      WHERE id=?
    ");

    if ($IS_ADMIN) {
      $u->bind_param("ssssiisi", $name, $email, $phone, $source, $stage_id, $status, $agentParam, $id);
    } else {
      $u->bind_param("ssssisi", $name, $email, $phone, $source, $stage_id, $status, $id);
    }

    $u->execute();
    $u->close();

    $details = "Lead updated: {$name}";
    if ($IS_ADMIN) $details .= " (agent reassigned=" . ($agent_id ?? 'NULL') . ")";
    $a = $conn->prepare("INSERT INTO crm_activities (lead_id, admin_id, activity_type, title, details) VALUES (?, ?, 'other', 'Lead updated', ?)");
    $a->bind_param("iis", $id, $USER_ID, $details);
    $a->execute();
    $a->close();

    flash_set('success', 'Lead updated.');
    redirect("lead_view.php?id=" . $id);
  }

  // keep values for re-render
  $lead['name'] = $name;
  $lead['email'] = $email;
  $lead['phone'] = $phone;
  $lead['source'] = $source;
  $lead['stage_id'] = $stage_id;
  $lead['status'] = $status;

  if ($IS_ADMIN) $lead['agent_id'] = $agent_id;
}
?>

<div class="d-flex align-items-center justify-content-between mb-3">
  <h4 class="mb-0">Edit Lead</h4>
  <a class="btn btn-outline-secondary btn-sm" href="lead_view.php?id=<?php echo (int)$id; ?>">Back</a>
</div>

<?php if ($error): ?>
  <div class="alert alert-danger"><?php echo h($error); ?></div>
<?php endif; ?>

<form method="post" class="card p-3">
  <div class="row g-2">

    <div class="col-12 col-md-6">
      <label class="form-label">Name *</label>
      <input class="form-control" name="name" value="<?php echo h($lead['name']); ?>" required>
    </div>

    <div class="col-12 col-md-3">
      <label class="form-label">Phone</label>
      <input class="form-control" name="phone" value="<?php echo h($lead['phone'] ?? ''); ?>">
    </div>

    <div class="col-12 col-md-3">
      <label class="form-label">Email *</label>
      <input class="form-control" name="email" value="<?php echo h($lead['email']); ?>" required>
    </div>

    <div class="col-12 col-md-4">
      <label class="form-label">Stage *</label>
      <select class="form-select" name="stage_id" required>
        <?php foreach ($stages as $s): ?>
          <option value="<?php echo (int)$s['id']; ?>" <?php echo ((int)$lead['stage_id']===(int)$s['id'])?'selected':''; ?>>
            <?php echo h($s['name']); ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="col-12 col-md-4">
      <label class="form-label">Status</label>
      <input class="form-control" name="status" value="<?php echo h($lead['status'] ?? 'New'); ?>" placeholder="New / Contacted / Interested / Closed">
    </div>

    <div class="col-12 col-md-4">
      <label class="form-label">Assigned Agent</label>

      <?php if ($IS_ADMIN): ?>
        <select class="form-select" name="agent_id">
          <option value="">-- None --</option>
          <?php foreach ($agents as $a): ?>
            <option value="<?php echo (int)$a['agent_id']; ?>" <?php echo ((int)$lead['agent_id']===(int)$a['agent_id'])?'selected':''; ?>>
              <?php echo h($a['name']); ?> (<?php echo h($a['email']); ?>)
            </option>
          <?php endforeach; ?>
        </select>
      <?php else: ?>
        <div class="form-control bg-light">
          <?php echo !empty($lead['agent_name']) ? h($lead['agent_name']) : '—'; ?>
          <span class="text-muted small">(agents cannot reassign)</span>
        </div>
      <?php endif; ?>
    </div>

    <div class="col-12 col-md-6">
      <label class="form-label">Source</label>
      <input class="form-control" name="source" value="<?php echo h($lead['source'] ?? ''); ?>" placeholder="website / WhatsApp / Facebook ads...">
    </div>

    <div class="col-12 mt-2">
      <button class="btn btn-primary">Save Changes</button>
    </div>
  </div>
</form>

<?php require_once __DIR__ . "/_layout_bottom.php"; ?>
