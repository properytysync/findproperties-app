<?php
// /admin/crm/task_new.php
require_once __DIR__ . "/_auth.php";
require_once __DIR__ . "/_db.php";
require_once __DIR__ . "/_helpers.php";

$page_title = "New Task";
require_once __DIR__ . "/_layout_top.php";
require_once __DIR__ . "/_nav.php";

$lead_id = isset($_GET["lead_id"]) ? (int)$_GET["lead_id"] : 0;

$title = "";
$description = "";
$due_at = "";
$status = "open";

$errors = [];

// Agent list (for admin only selection UI)
$agents = [];
$res = $conn->query("SELECT agent_id, name, email FROM agents ORDER BY name ASC");
while ($r = $res->fetch_assoc()) $agents[] = $r;

// Optional lead info
$lead = null;
if ($lead_id > 0) {
    $stmt = $conn->prepare("SELECT id, name, phone, email FROM crm_leads WHERE id=? LIMIT 1");
    $stmt->bind_param("i", $lead_id);
    $stmt->execute();
    $lead = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$lead) $lead_id = 0;
}

// default assignment:
// - agent => themselves
// - admin => unassigned by default
$assigned_agent_id = ($CRM_ROLE === 'agent') ? (int)$CRM_USER_ID : 0;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $lead_id = (int)($_POST["lead_id"] ?? 0);
    $title = trim($_POST["title"] ?? "");
    $description = trim($_POST["description"] ?? "");
    $due_at = trim($_POST["due_at"] ?? "");
    $status = trim($_POST["status"] ?? "open");

    // enforce assignment rule
    if ($CRM_ROLE === 'admin') {
        $assigned_agent_id = (int)($_POST["assigned_agent_id"] ?? 0);
    } else {
        $assigned_agent_id = (int)$CRM_USER_ID; // force agent -> self
    }

    if ($title === "") $errors[] = "Task title is required.";
    if ($due_at === "") $errors[] = "Due date/time is required.";
    if (!in_array($status, ["open","done","canceled"], true)) $status = "open";

    $due_at_sql = dt_input_to_sql($due_at);
    if ($due_at_sql === null) $errors[] = "Invalid due date/time format.";

    // validate agent id (if any)
    if ($assigned_agent_id > 0) {
        $chk = $conn->prepare("SELECT agent_id FROM agents WHERE agent_id=? LIMIT 1");
        $chk->bind_param("i", $assigned_agent_id);
        $chk->execute();
        $ok = $chk->get_result()->fetch_assoc();
        $chk->close();
        if (!$ok) $errors[] = "Assigned agent does not exist.";
    }

    if (!$errors) {
        // NOTE: your schema uses assigned_agent_id
        $stmt = $conn->prepare("
            INSERT INTO crm_tasks (lead_id, assigned_agent_id, assigned_admin_id, title, description, due_at, status, created_at, updated_at)
            VALUES (NULLIF(?,0), NULLIF(?,0), NULL, ?, ?, ?, ?, NOW(), NOW())
        ");

        $stmt->bind_param(
            "iissss",
            $lead_id,
            $assigned_agent_id,
            $title,
            $description,
            $due_at_sql,
            $status
        );

        $stmt->execute();
        $stmt->close();

        flash_set("success", "Task created.");
        redirect($lead_id > 0 ? "lead_view.php?id=".(int)$lead_id : "tasks.php");
    }
}
?>

<div class="container-fluid px-3 px-md-4">
    <div class="d-flex align-items-center justify-content-between mt-3 mb-2">
        <h4 class="mb-0">Create Task</h4>
        <a class="btn btn-outline-secondary btn-sm" href="<?php echo $lead_id>0 ? 'lead_view.php?id='.(int)$lead_id : 'tasks.php'; ?>">Back</a>
    </div>

    <?php if ($lead): ?>
        <div class="alert alert-info">
            Lead: <strong><?php echo h($lead["name"]); ?></strong>
            <?php if (!empty($lead["phone"])): ?> — <?php echo h($lead["phone"]); ?><?php endif; ?>
            <?php if (!empty($lead["email"])): ?> — <?php echo h($lead["email"]); ?><?php endif; ?>
        </div>
    <?php endif; ?>

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
                <input type="hidden" name="lead_id" value="<?php echo (int)$lead_id; ?>">

                <div class="mb-3">
                    <label class="form-label">Task Title *</label>
                    <input type="text" name="title" class="form-control" value="<?php echo h($title); ?>" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-control" rows="4"><?php echo h($description); ?></textarea>
                </div>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Due Date & Time *</label>
                        <input type="datetime-local" name="due_at" class="form-control" value="<?php echo h($due_at); ?>" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="open" <?php echo $status==="open"?"selected":""; ?>>Open</option>
                            <option value="done" <?php echo $status==="done"?"selected":""; ?>>Done</option>
                            <option value="canceled" <?php echo $status==="canceled"?"selected":""; ?>>Canceled</option>
                        </select>
                    </div>
                </div>

                <!-- ✅ Assignment rule UI -->
                <?php if ($CRM_ROLE === 'admin'): ?>
                    <div class="mt-3">
                        <label class="form-label">Assign to Agent</label>
                        <select name="assigned_agent_id" class="form-select">
                            <option value="0" <?php echo $assigned_agent_id===0 ? "selected" : ""; ?>>— Unassigned —</option>
                            <?php foreach ($agents as $ag): ?>
                                <option value="<?php echo (int)$ag["agent_id"]; ?>"
                                    <?php echo ((int)$assigned_agent_id === (int)$ag["agent_id"]) ? "selected" : ""; ?>>
                                    <?php echo h($ag["name"]); ?>
                                    <?php if (!empty($ag["email"])): ?> (<?php echo h($ag["email"]); ?>)<?php endif; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php else: ?>
                    <input type="hidden" name="assigned_agent_id" value="<?php echo (int)$CRM_USER_ID; ?>">
                    <div class="mt-3 alert alert-light border">
                        Assigned Agent: <strong>You</strong>
                    </div>
                <?php endif; ?>

                <div class="mt-4">
                    <button class="btn btn-primary" type="submit">Create Task</button>
                    <a class="btn btn-outline-secondary" href="<?php echo $lead_id>0 ? 'lead_view.php?id='.(int)$lead_id : 'tasks.php'; ?>">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . "/_layout_bottom.php"; ?>
