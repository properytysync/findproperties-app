<?php
require_once __DIR__ . "/_auth.php";
require_once __DIR__ . "/_db.php";
require_once __DIR__ . "/_helpers.php";

$page_title = "Convert Enquiry";
require_once __DIR__ . "/_layout_top.php";
require_once __DIR__ . "/_nav.php";

$CRM_ADMIN_ID = (int)($_SESSION['aid'] ?? 0);

// stages
$stages = [];
$res = $conn->query("SELECT id, name FROM crm_stages WHERE is_active=1 ORDER BY sort_order ASC, id ASC");
while ($r = $res->fetch_assoc()) $stages[] = $r;

$error = '';
$success = flash_get('success');

// show last 100 enquiries
$enq = $conn->query("
  SELECT cid, name, email, phone, subject, message, payment_status, payment_reference
  FROM contact
  ORDER BY cid DESC
  LIMIT 100
");

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['convert'])) {
  $cid = (int)($_POST['cid'] ?? 0);
  $stage_id = (int)($_POST['stage_id'] ?? 0);

  if ($cid <= 0 || $stage_id <= 0) {
    $error = "Choose an enquiry and a stage.";
  } else {
    // load contact
    $stmt = $conn->prepare("SELECT cid, name, email, phone, subject, message FROM contact WHERE cid=? LIMIT 1");
    $stmt->bind_param("i", $cid);
    $stmt->execute();
    $c = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$c) {
      $error = "Enquiry not found.";
    } else {
      $name  = trim($c['name'] ?? '');
      $email = trim($c['email'] ?? '');
      $phone = trim($c['phone'] ?? '');

      if ($name === '' || $email === '') {
        $error = "This enquiry is missing name or email, so it cannot be converted safely.";
      } else {
        // Prevent duplicates (best effort without source_ref):
        // If a lead already exists with same email OR phone and source='contact', open it.
        $dupSql = "SELECT id FROM crm_leads WHERE source='contact' AND (email=? OR (phone IS NOT NULL AND phone=?)) LIMIT 1";
        $chk = $conn->prepare($dupSql);
        $chk->bind_param("ss", $email, $phone);
        $chk->execute();
        $exists = $chk->get_result()->fetch_assoc();
        $chk->close();

        if ($exists) {
          flash_set('success', 'This enquiry looks already converted (same email/phone). Opening lead...');
          redirect("lead_view.php?id=" . (int)$exists['id']);
        }

        $source = "contact";
        $status = "New";     // matches your enum style better than open/closed
        $agent_id = null;    // assign later from lead_edit.php

        $ins = $conn->prepare("
          INSERT INTO crm_leads (name, email, phone, source, agent_id, stage_id, status, created_at, updated_at)
          VALUES (?, ?, NULLIF(?,''), ?, ?, ?, ?, NOW(), NOW())
        ");

        // agent_id is nullable; bind as int with fallback 0 then NULLIF in query isn't possible for int.
        // We'll use a variable and pass NULL via bind_param by using 'i' with null will set 0, so we handle it:
        // easiest: insert NULL directly when no agent.
        $ins->close();

        // Insert with NULL agent_id properly:
        $ins = $conn->prepare("
          INSERT INTO crm_leads (name, email, phone, source, agent_id, stage_id, status, created_at, updated_at)
          VALUES (?, ?, NULLIF(?,''), ?, NULL, ?, ?, NOW(), NOW())
        ");
        $ins->bind_param("sssis", $name, $email, $phone, $source, $stage_id, $status);
        $ins->execute();
        $lead_id = $conn->insert_id;
        $ins->close();

        // activity log (crm_activities uses admin_id + activity_at auto/current_timestamp)
        $subject = trim($c['subject'] ?? '');
        $message = trim($c['message'] ?? '');
        $details = "Converted from contact enquiry #{$cid}\nSubject: {$subject}\nMessage: {$message}";

        $a = $conn->prepare("
          INSERT INTO crm_activities (lead_id, admin_id, activity_type, title, details)
          VALUES (?, ?, 'note', 'Converted from enquiry', ?)
        ");
        $a->bind_param("iis", $lead_id, $CRM_ADMIN_ID, $details);
        $a->execute();
        $a->close();

        flash_set('success', 'Enquiry converted to lead.');
        redirect("lead_view.php?id=" . $lead_id);
      }
    }
  }
}
?>

<div class="d-flex align-items-center justify-content-between mb-3">
  <h4 class="mb-0">Convert Enquiry (Contact) → Lead</h4>
  <a class="btn btn-outline-secondary btn-sm" href="leads.php">Back</a>
</div>

<?php if ($success): ?><div class="alert alert-success"><?php echo h($success); ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger"><?php echo h($error); ?></div><?php endif; ?>

<form method="post" class="card p-3 mb-3">
  <div class="row g-2">
    <div class="col-12 col-md-4">
      <label class="form-label">Stage to put lead in *</label>
      <select class="form-select" name="stage_id" required>
        <option value="">Select stage</option>
        <?php foreach ($stages as $s): ?>
          <option value="<?php echo (int)$s['id']; ?>"><?php echo h($s['name']); ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-12 col-md-8">
      <div class="alert alert-info mb-0">
        Choose an enquiry below and click Convert.
      </div>
    </div>
  </div>

  <div class="table-responsive mt-3">
    <table class="table table-striped mb-0">
      <thead>
        <tr>
          <th></th>
          <th>Name</th>
          <th>Phone</th>
          <th>Email</th>
          <th>Subject</th>
          <th>Payment</th>
        </tr>
      </thead>
      <tbody>
        <?php while ($c = $enq->fetch_assoc()): ?>
          <tr>
            <td><input type="radio" name="cid" value="<?php echo (int)$c['cid']; ?>" required></td>
            <td><?php echo h($c['name']); ?></td>
            <td><?php echo h($c['phone']); ?></td>
            <td><?php echo h($c['email']); ?></td>
            <td><?php echo h($c['subject']); ?></td>
            <td><?php echo h($c['payment_status'] ?? '-'); ?></td>
          </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>

  <button class="btn btn-primary mt-3" name="convert" value="1">Convert Selected Enquiry</button>
</form>

<?php require_once __DIR__ . "/_layout_bottom.php"; ?>
