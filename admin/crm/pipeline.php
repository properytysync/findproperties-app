<?php
require_once __DIR__ . "/_auth.php";
require_once __DIR__ . "/_db.php";
require_once __DIR__ . "/_helpers.php";
require_once __DIR__ . "/_settings.php";

/**
 * Tables used:
 * crm_stages(id, name, sort_order, is_active)
 * crm_settings(key, value, updated_at)
 */

$msg = trim($_GET["msg"] ?? "");
$errors = [];

/**
 * SAVE WHATSAPP SETTINGS
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_whatsapp'])) {
    crm_setting_set('wa_enabled', isset($_POST['wa_enabled']) ? '1' : '0');
    crm_setting_set('wa_phone_id', trim($_POST['wa_phone_id'] ?? ''));
    crm_setting_set('wa_token', trim($_POST['wa_token'] ?? ''));
    crm_setting_set('wa_from_label', trim($_POST['wa_from_label'] ?? 'PropertySync CRM'));

    flash_set('success', 'WhatsApp settings saved.');
    redirect("settings.php");
}

/**
 * STAGE ACTIONS
 */
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["action"])) {
    $action = trim($_POST["action"] ?? "");

    if ($action === "add_stage") {
        $name = trim($_POST["name"] ?? "");
        $sort_order = (int)($_POST["sort_order"] ?? 1);
        $is_active = isset($_POST["is_active"]) ? 1 : 0;

        if ($name === "") $errors[] = "Stage name is required.";

        if (!$errors) {
            $stmt = $conn->prepare("INSERT INTO crm_stages (name, sort_order, is_active) VALUES (?, ?, ?)");
            $stmt->bind_param("sii", $name, $sort_order, $is_active);
            $stmt->execute();
            $stmt->close();

            redirect("settings.php?msg=" . urlencode("Stage added."));
        }
    }

    if ($action === "update_stage") {
        $id = (int)($_POST["id"] ?? 0);
        $name = trim($_POST["name"] ?? "");
        $sort_order = (int)($_POST["sort_order"] ?? 1);
        $is_active = isset($_POST["is_active"]) ? 1 : 0;

        if ($id <= 0) $errors[] = "Invalid stage id.";
        if ($name === "") $errors[] = "Stage name is required.";

        if (!$errors) {
            $stmt = $conn->prepare("UPDATE crm_stages SET name=?, sort_order=?, is_active=? WHERE id=?");
            $stmt->bind_param("siii", $name, $sort_order, $is_active, $id);
            $stmt->execute();
            $stmt->close();

            redirect("settings.php?msg=" . urlencode("Stage updated."));
        }
    }

    if ($action === "delete_stage") {
        $id = (int)($_POST["id"] ?? 0);
        if ($id <= 0) $errors[] = "Invalid stage id.";

        if (!$errors) {
            // block delete if leads use it
            $stmt = $conn->prepare("SELECT COUNT(*) AS c FROM crm_leads WHERE stage_id=?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $count = (int)($stmt->get_result()->fetch_assoc()["c"] ?? 0);
            $stmt->close();

            if ($count > 0) {
                redirect("settings.php?msg=" . urlencode("Cannot delete stage: it is assigned to {$count} lead(s). Deactivate instead."));
            }

            $stmt = $conn->prepare("DELETE FROM crm_stages WHERE id=?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();

            redirect("settings.php?msg=" . urlencode("Stage deleted."));
        }
    }
}

// flash msg
$flash = flash_get('success');

// Load stages
$stages = [];
$res = $conn->query("SELECT id, name, sort_order, is_active FROM crm_stages ORDER BY sort_order ASC, id ASC");
while ($r = $res->fetch_assoc()) $stages[] = $r;

// Load WhatsApp settings
$wa_enabled    = (string)crm_setting_get('wa_enabled', '0');
$wa_phone_id   = (string)crm_setting_get('wa_phone_id', '');
$wa_token      = (string)crm_setting_get('wa_token', '');
$wa_from_label = (string)crm_setting_get('wa_from_label', 'PropertySync CRM');

$page_title = "CRM Settings";
require_once __DIR__ . "/_layout_top.php";
require_once __DIR__ . "/_nav.php";
?>

<div class="container-fluid px-3 px-md-4">
    <div class="d-flex align-items-center justify-content-between mt-3 mb-2">
        <h4 class="mb-0">CRM Settings</h4>
        <a class="btn btn-outline-secondary btn-sm" href="index.php">Back to Dashboard</a>
    </div>

    <?php if ($flash): ?>
        <div class="alert alert-success"><?php echo h($flash); ?></div>
    <?php endif; ?>

    <?php if ($msg): ?>
        <div class="alert alert-success"><?php echo h($msg); ?></div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($errors as $er): ?>
                    <li><?php echo h($er); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="row g-3">

 

        <!-- Stages -->
        <div class="col-lg-5">
            <div class="card">
                <div class="card-header"><strong>Add Pipeline Stage</strong></div>
                <div class="card-body">
                    <form method="post">
                        <input type="hidden" name="action" value="add_stage">

                        <div class="mb-3">
                            <label class="form-label">Stage Name *</label>
                            <input type="text" name="name" class="form-control" placeholder="e.g. Contacted" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Sort Order</label>
                            <input type="number" name="sort_order" class="form-control" value="1" min="1">
                        </div>

                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" name="is_active" id="activeNew" checked>
                            <label class="form-check-label" for="activeNew">Active</label>
                        </div>

                        <button class="btn btn-primary" type="submit">Add Stage</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card">
                <div class="card-header"><strong>Pipeline Stages</strong></div>
                <div class="card-body">

                    <?php if (empty($stages)): ?>
                        <div class="alert alert-warning mb-0">
                            No stages found. Add stages like: New, Contacted, Qualified, Viewing, Negotiation, Won, Lost.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-bordered align-middle">
                                <thead>
                                <tr>
                                    <th style="width:70px;">ID</th>
                                    <th>Name</th>
                                    <th style="width:120px;">Sort</th>
                                    <th style="width:110px;">Active</th>
                                    <th style="width:220px;">Actions</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($stages as $s): ?>
                                    <tr>
                                        <td><?php echo (int)$s["id"]; ?></td>
                                        <td>
                                            <form method="post" class="d-flex gap-2 align-items-center">
                                                <input type="hidden" name="action" value="update_stage">
                                                <input type="hidden" name="id" value="<?php echo (int)$s["id"]; ?>">
                                                <input type="text" name="name" class="form-control"
                                                       value="<?php echo h($s["name"]); ?>" required>
                                        </td>
                                        <td>
                                                <input type="number" name="sort_order" class="form-control"
                                                       value="<?php echo (int)$s["sort_order"]; ?>" min="1">
                                        </td>
                                        <td class="text-center">
                                                <input class="form-check-input" type="checkbox" name="is_active"
                                                    <?php echo ((int)$s["is_active"] === 1) ? "checked" : ""; ?>>
                                        </td>
                                        <td>
                                                <button class="btn btn-sm btn-primary" type="submit">Save</button>
                                            </form>

                                            <form method="post" class="d-inline">
                                                <input type="hidden" name="action" value="delete_stage">
                                                <input type="hidden" name="id" value="<?php echo (int)$s["id"]; ?>">
                                                <button class="btn btn-sm btn-danger"
                                                        onclick="return confirm('Delete stage? If leads are using it, deletion will be blocked. Deactivate instead.');">
                                                    Delete
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>

                </div>
            </div>
        </div>

    </div>
</div>

<?php require_once __DIR__ . "/_layout_bottom.php"; ?>
