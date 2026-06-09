<?php
// /admin/crm/task_edit.php
require_once __DIR__ . "/_auth.php";
require_once __DIR__ . "/_db.php";
require_once __DIR__ . "/_helpers.php";

$page_title = "Edit Task";
require_once __DIR__ . "/_layout_top.php";
require_once __DIR__ . "/_nav.php";

$id = isset($_GET["id"]) ? (int)$_GET["id"] : 0;
if ($id <= 0) redirect("tasks.php?msg=" . urlencode("Invalid task id."));

$stmt = $conn->prepare("
    SELECT t.*, l.name AS lead_name, a.name AS assigned_agent_name
    FROM crm_tasks t
    LEFT JOIN crm_leads l ON l.id = t.lead_id
    LEFT JOIN agents a ON a.agent_id = t.assigned_agent_id
    WHERE t.id=? LIMIT 1
");
$stmt->bind_param("i", $id);
$stmt->execute();
$task = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$task) redirect("tasks.php?msg=" . urlencode("Task not found."));

// Agent list (for admin only selection UI)
$agents = [];
$res = $conn->query("SELECT agent_id, name, email FROM agents ORDER BY name ASC");
while ($r = $res->fetch_assoc()) $agents[] = $r;

$errors = [];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = trim($_POST["action"] ?? "update");

    if ($action === "delete") {
        $stmt = $conn->prepare("DELETE FROM crm_tasks WHERE id=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();

        flash_set("success", "Task deleted.");
        redirect(!empty($task["lead_id"]) ? "lead_view.php?id=".(int)$task["lead_id"] : "tasks.php");
    }

    $title = trim($_POST["title"] ?? "");
    $description = trim($_POST["description"] ?? "");
    $due_at = trim($_POST["due_at"] ?? "");
    $status = trim($_POST["status"] ?? "open");

    // ✅ ONLY ADMIN can change assigned agent
    if ($CRM_ROLE === 'admin') {
        $assigned_agent_id = (int)($_POST["assigned_agent_id"] ?? 0);
    } else {
        $assigned_agent_id = (int)($task["assigned_agent_id"] ?? 0); // keep existing
    }

    if ($title === "") $errors[] = "Task title is required.";
    if ($due_at === "") $errors[] = "Due date/time is required.";
    if (!in_array($status, ["open","done","canceled"], true)) $status = "open";

    $due_at_sql = dt_input_to_sql($due_at);
    if ($due_at_sql === null) $errors[] = "Invalid due date/time format.";

    // validate agent (admin only)
    if ($CRM_ROLE === 'admin' && $assigned_agent_id > 0) {
        $chk = $conn->prepare("SELECT agent_id FROM agents WHERE agent_id=? LIMIT 1");
        $chk->bind_param("i", $assigned_agent_id);
        $chk->execute();
        $ok = $chk->get_result()->fetch_assoc();
        $chk->close();
        if (!$ok) $errors[] = "Selected agent does not exist.";
    }

    if (!$errors) {
        $stmt = $conn->prepare("
            UPDATE crm_tasks
            SET assigned_agent_id=NULLIF(?,0),
                assigned_admin_id=NULL,
                title=?,
                description=?,
                due_at=?,
                status=?,
                updated_at=NOW()
            WHERE id=?
        ");
        $stmt->bind_param("issssi", $assigned_agent_id, $title, $description, $due_at_sql, $status, $id);
        $stmt->execute();
        $stmt->close();

        flash_set("success", "Task updated.");
        redirect(!empty($task["lead_id"]) ? "lead_view.php?id=".(int)$task["lead_id"] : "tasks.php");
    }

    // Keep form values
    $task["title"] = $title;
    $task["description"] = $description;
    $task["due_at"] = $due_at_sql ?: $task["due_at"];
    $task["status"] = $status;
    $task["assigned_agent_id"] = $assigned_agent_id;
}

$due_input = sql_to_dt_input($task["due_at"]);
?>

<div class="container-fluid px-3 px-md-4">
    <div class="d-flex align-items-center justify-content-between mt-3 mb-2">
        <h4 class="mb-0">Edit Task</h4>
        <a class="btn btn-outline-secondary btn-sm" href="<?php echo !empty($task['lead_id']) ? 'lead_view.php?id='.(int)$task['lead_id'] : 'tasks.php'; ?>">Back</a>
    </div>

    <?php if (!empty($task["lead_name"])): ?>
        <div class="alert alert-info">
            Lead: <strong><?php echo h($task["lead_name"]); ?></strong>
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
                <input type="hidden" name="action" value="update">

                <div class="mb-3">
                    <label class="form-label">Task Title *</label>
                    <input type="text" name="title" class="form-control" value="<?php echo h($task["title"]); ?>" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-control" rows="4"><?php echo h($task["description"] ?? ""); ?></textarea>
                </div>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Due Date & Time *</label>
                        <input type="datetime-local" name="due_at" class="form-control" value="<?php echo h($due_input); ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="open" <?php echo $task["status"]==="open"?"selected":""; ?>>Open</option>
                            <option value="done" <?php echo $task["status"]==="done"?"selected":""; ?>>Done</option>
                            <option value="canceled" <?php echo $task["status"]==="canceled"?"selected":""; ?>>Canceled</option>
                        </select>
                    </div>
                </div>

                <!-- ✅ only admin can edit assigned agent -->
                <?php if ($CRM_ROLE === 'admin'): ?>
                    <div class="mt-3">
                        <label class="form-label">Assign to Agent</label>
                        <select name="assigned_agent_id" class="form-select">
                            <option value="0" <?php echo empty($task["assigned_agent_id"]) ? "selected" : ""; ?>>— Unassigned —</option>
                            <?php foreach ($agents as $ag): ?>
                                <option value="<?php echo (int)$ag["agent_id"]; ?>"
                                    <?php echo ((int)($task["assigned_agent_id"] ?? 0) === (int)$ag["agent_id"]) ? "selected" : ""; ?>>
                                    <?php echo h($ag["name"]); ?>
                                    <?php if (!empty($ag["email"])): ?> (<?php echo h($ag["email"]); ?>)<?php endif; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text">Only admins can change assignment.</div>
                    </div>
                <?php else: ?>
                    <div class="mt-3 alert alert-light border">
                        Assigned Agent:
                        <strong><?php echo h($task["assigned_agent_name"] ?: "Unassigned"); ?></strong>
                    </div>
                <?php endif; ?>

                <div class="mt-4 d-flex gap-2 flex-wrap">
                    <button class="btn btn-primary" type="submit">Save Changes</button>

                    <button class="btn btn-danger" type="submit" name="action" value="delete"
                            onclick="return confirm('Delete this task permanently?');">
                        Delete
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . "/_layout_bottom.php"; ?>
