<?php
require_once __DIR__ . "/_auth.php";
require_once __DIR__ . "/_db.php";
require_once __DIR__ . "/_helpers.php";

$page_title = "New Lead";
require_once __DIR__ . "/_layout_top.php";

$USER_ID  = current_user_id();
$IS_ADMIN = is_admin();
$IS_AGENT = is_agent();

$errors = [];

$name = "";
$email = "";
$phone = "";
$status = "New";
$source = "";
$agent_id = null;
$stage_id = null;

// stages
$stages = [];
$res = $conn->query("SELECT id, name FROM crm_stages WHERE is_active=1 ORDER BY sort_order ASC, id ASC");
while ($r = $res->fetch_assoc()) $stages[] = $r;

// agents (admin only needs list)
$agents = [];
if ($IS_ADMIN) {
  $res = $conn->query("SELECT agent_id, name, email FROM agents ORDER BY name ASC");
  while ($r = $res->fetch_assoc()) $agents[] = $r;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name   = trim($_POST["name"] ?? "");
    $email  = trim($_POST["email"] ?? "");
    $phone  = trim($_POST["phone"] ?? "");
    $status = trim($_POST["status"] ?? "New");
    $source = trim($_POST["source"] ?? "");
    $stage_id = ($_POST["stage_id"] ?? "") !== "" ? (int)$_POST["stage_id"] : null;

    // ✅ assignment rules
    if ($IS_AGENT) {
        // Force agent assignment to self
        $agent_id = $USER_ID;
    } else {
        // Admin may assign or leave none
        $agent_id = ($_POST["agent_id"] ?? "") !== "" ? (int)$_POST["agent_id"] : null;
    }

    if ($name === "")  $errors[] = "Name is required.";
    if ($email === "") $errors[] = "Email is required.";

    // status safety (your enum includes more but keep core)
    $allowed = ["New","Contacted","Interested","Closed"];
    if (!in_array($status, $allowed, true)) $status = "New";

    if (!$errors) {
        $agentParam = $agent_id ?? 0;
        $stageParam = $stage_id ?? 0;

        $stmt = $conn->prepare("
            INSERT INTO crm_leads (name, email, phone, status, source, agent_id, stage_id, created_at, updated_at)
            VALUES (?, ?, NULLIF(?,''), ?, NULLIF(?,''), NULLIF(?,0), NULLIF(?,0), NOW(), NOW())
        ");
        $stmt->bind_param("sssssii", $name, $email, $phone, $status, $source, $agentParam, $stageParam);
        $stmt->execute();
        $newId = (int)$conn->insert_id;
        $stmt->close();

        // activity
        $details = "Lead created: {$name}";
        $a = $conn->prepare("INSERT INTO crm_activities (lead_id, admin_id, activity_type, title, details) VALUES (?, ?, 'other', 'Lead created', ?)");
        $a->bind_param("iis", $newId, $USER_ID, $details);
        $a->execute();
        $a->close();

        flash_set("success", "Lead created.");
        redirect("lead_view.php?id=" . $newId);
    }
}
?>

<div class="container-fluid px-3 px-md-4">
    <div class="d-flex align-items-center justify-content-between mt-3 mb-2">
        <h4 class="mb-0">Create Lead</h4>
        <a class="btn btn-outline-secondary btn-sm" href="leads.php">Back</a>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($errors as $er): ?><li><?php echo h($er); ?></li><?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <form method="post">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Name *</label>
                        <input class="form-control" type="text" name="name" value="<?php echo h($name); ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Email *</label>
                        <input class="form-control" type="email" name="email" value="<?php echo h($email); ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Phone</label>
                        <input class="form-control" type="text" name="phone" value="<?php echo h($phone); ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Source</label>
                        <input class="form-control" type="text" name="source" value="<?php echo h($source); ?>" placeholder="WhatsApp / Facebook Ads / Website...">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Stage</label>
                        <select class="form-select" name="stage_id">
                            <option value="">-- Select Stage --</option>
                            <?php foreach ($stages as $s): ?>
                                <option value="<?php echo (int)$s["id"]; ?>" <?php echo ((int)$stage_id===(int)$s["id"])?"selected":""; ?>>
                                    <?php echo h($s["name"]); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <?php if ($IS_ADMIN): ?>
                      <div class="col-md-6">
                          <label class="form-label">Assign to Agent (Admin only)</label>
                          <select class="form-select" name="agent_id">
                              <option value="">-- None --</option>
                              <?php foreach ($agents as $a): ?>
                                  <option value="<?php echo (int)$a["agent_id"]; ?>" <?php echo ((int)$agent_id===(int)$a["agent_id"])?"selected":""; ?>>
                                      <?php echo h($a["name"]); ?> (<?php echo h($a["email"]); ?>)
                                  </option>
                              <?php endforeach; ?>
                          </select>
                          <div class="form-text">Agents auto-assign to themselves.</div>
                      </div>
                    <?php else: ?>
                      <div class="col-md-6">
                        <label class="form-label">Assigned Agent</label>
                        <div class="form-control bg-light">You (auto-assigned)</div>
                      </div>
                    <?php endif; ?>

                    <div class="col-md-6">
                        <label class="form-label">Lead Status</label>
                        <select class="form-select" name="status">
                            <option value="New" <?php echo $status==="New"?"selected":""; ?>>New</option>
                            <option value="Contacted" <?php echo $status==="Contacted"?"selected":""; ?>>Contacted</option>
                            <option value="Interested" <?php echo $status==="Interested"?"selected":""; ?>>Interested</option>
                            <option value="Closed" <?php echo $status==="Closed"?"selected":""; ?>>Closed</option>
                        </select>
                    </div>
                </div>

                <div class="mt-4">
                    <button class="btn btn-primary" type="submit">Create Lead</button>
                    <a class="btn btn-outline-secondary" href="leads.php">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . "/_layout_bottom.php"; ?>
