<?php
// /admin/agents_list.php
require_once __DIR__ . "/config.php";
require_once __DIR__ . "/_auth.php";

require_login();

$msg = trim($_GET['msg'] ?? '');
$error = '';
$success = '';

// --------------------------
// Handle POST actions
// --------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    // --------------------------
    // Change Password
    // --------------------------
    if ($_POST['action'] === 'change_password') {

        $agent_id = (int)($_POST['agent_id'] ?? 0);
        $new_pass = (string)($_POST['new_password'] ?? '');
        $confirm  = (string)($_POST['confirm_password'] ?? '');
        $current  = (string)($_POST['current_password'] ?? '');

        if ($agent_id <= 0) {
            $error = "Invalid agent selected.";
        } elseif (!can_edit_agent($agent_id)) {
            $error = "You do not have permission to change this password.";
        } elseif (strlen($new_pass) < 6) {
            $error = "New password must be at least 6 characters.";
        } elseif ($new_pass !== $confirm) {
            $error = "New password and confirmation do not match.";
        } else {
            // Fetch target agent
            $stmt = $con->prepare("SELECT agent_id, password_hash FROM agents WHERE agent_id=? LIMIT 1");
            if (!$stmt) {
                $error = "Database error: cannot prepare statement.";
            } else {
                $stmt->bind_param("i", $agent_id);
                $stmt->execute();
                $agentRow = $stmt->get_result()->fetch_assoc();
                $stmt->close();

                if (!$agentRow) {
                    $error = "Agent record not found.";
                } else {
                    $storedHashOrPlain = (string)($agentRow['password_hash'] ?? '');
                    $isSelf = is_agent() && current_user_id() === $agent_id;

                    if ($isSelf) {
                        if ($current === '') {
                            $error = "Enter your current password.";
                        } else {
                            $ok = false;
                            if ($storedHashOrPlain !== '' && (str_starts_with($storedHashOrPlain, '$2y$') || str_starts_with($storedHashOrPlain, '$argon2'))) {
                                $ok = password_verify($current, $storedHashOrPlain);
                            } else {
                                $ok = ($storedHashOrPlain !== '' && hash_equals($storedHashOrPlain, $current));
                            }

                            if (!$ok) {
                                $error = "Current password is incorrect.";
                            }
                        }
                    }

                    if ($error === '') {
                        $newHash = password_hash($new_pass, PASSWORD_BCRYPT);

                        $up = $con->prepare("UPDATE agents SET password_hash=? WHERE agent_id=? LIMIT 1");
                        if (!$up) {
                            $error = "Database error: cannot update password.";
                        } else {
                            $up->bind_param("si", $newHash, $agent_id);
                            $up->execute();
                            $up->close();

                            $success = is_admin()
                                ? "Password updated successfully for agent #{$agent_id}."
                                : "Your password has been updated successfully.";
                        }
                    }
                }
            }
        }
    }

    // --------------------------
    // Delete Agent (Admin only)
    // --------------------------
    if ($_POST['action'] === 'delete_agent') {

        // Admin-only
        if (!is_admin()) {
            $error = "Admin access required.";
        } else {
            $agent_id = (int)($_POST['agent_id'] ?? 0);

            if ($agent_id <= 0) {
                $error = "Invalid agent selected.";
            } elseif ($agent_id === current_user_id()) {
                // safety: don't allow deleting self if your admin_id equals an agent_id by coincidence
                $error = "You cannot delete your own account.";
            } else {

                // Optional: ensure agent exists first (and get name for message)
                $chk = $con->prepare("SELECT name FROM agents WHERE agent_id=? LIMIT 1");
                if (!$chk) {
                    $error = "Database error: cannot validate agent.";
                } else {
                    $chk->bind_param("i", $agent_id);
                    $chk->execute();
                    $row = $chk->get_result()->fetch_assoc();
                    $chk->close();

                    if (!$row) {
                        $error = "Agent not found.";
                    } else {
                        $agentName = (string)$row['name'];

                        $del = $con->prepare("DELETE FROM agents WHERE agent_id=? LIMIT 1");
                        if (!$del) {
                            $error = "Database error: cannot delete agent.";
                        } else {
                            $del->bind_param("i", $agent_id);
                            $del->execute();
                            $affected = $del->affected_rows;
                            $del->close();

                            if ($affected > 0) {
                                $success = "Agent deleted successfully: {$agentName} (#{$agent_id}).";
                            } else {
                                $error = "Delete failed. Agent may not exist.";
                            }
                        }
                    }
                }
            }
        }
    }
}

// --------------------------
// Fetch all agents
// --------------------------
$agents = [];
$res = mysqli_query($con, "SELECT agent_id, name, email, contact_info, is_active, last_login_at, picture FROM agents ORDER BY agent_id DESC");
if ($res) {
    while ($row = mysqli_fetch_assoc($res)) {
        $agents[] = $row;
    }
}
$total_agents = count($agents);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Agents</title>

  <link rel="stylesheet" type="text/css" href="assets/css/vendors/font-awesome.css">
  <link rel="stylesheet" type="text/css" href="assets/css/vendors/bootstrap.css">
  <link rel="stylesheet" type="text/css" href="assets/css/style.css">
  <link rel="stylesheet" type="text/css" href="assets/css/responsive.css">
  <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.15.4/css/all.css">

  <style>
    .agent-card{border:1px solid #eee;border-radius:12px;padding:14px;margin-bottom:12px;background:#fff}
    .agent-avatar{width:42px;height:42px;border-radius:50%;object-fit:cover;border:1px solid #ddd}
    .badge-pill{padding:4px 10px;border-radius:999px;font-size:12px}
    .badge-active{background:#d1fae5;color:#065f46}
    .badge-inactive{background:#fee2e2;color:#991b1b}
  </style>
</head>

<body>
<div class="page-wrapper compact-wrapper" id="pageWrapper">
  <div class="page-body-wrapper">

    <div class="sidebar-wrapper" data-layout="fill-svg">
      <div>
        <?php include __DIR__ . "/menu.php"; ?>
      </div>
    </div>

    <div class="page-body">
      <div class="container-fluid">
        <div class="page-title">
          <div class="row">
            <div class="col-sm-6 p-0">
              <h3>Agents</h3>
              <div class="text-muted">
                <?= is_admin() ? "Admin view (full access)" : "Agent view (limited)" ?><br>
                Total: <?= (int)$total_agents ?> agents
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="container-fluid">
        <?php if ($msg): ?>
          <div class="alert alert-info"><?= h($msg) ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
          <div class="alert alert-success"><?= h($success) ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
          <div class="alert alert-danger"><?= h($error) ?></div>
        <?php endif; ?>

        <?php if (is_admin()): ?>
          <div class="mb-3">
            <a href="add_agent.php" class="btn btn-primary">
              <i class="fa fa-plus"></i> Add New Agent
            </a>
          </div>
        <?php endif; ?>

        <?php if (empty($agents)): ?>
          <div class="alert alert-warning">No agents found.</div>
        <?php else: ?>
          <?php foreach ($agents as $a): ?>
            <?php
              $aid = (int)$a['agent_id'];
              $active = (int)$a['is_active'] === 1;
              $canEdit = can_edit_agent($aid);
              $isSelf = is_agent() && current_user_id() === $aid;

              $pic = trim((string)($a['picture'] ?? ''));
              $picFs = $pic !== '' ? __DIR__ . '/' . ltrim($pic, '/') : '';
              $hasPic = ($pic !== '' && $picFs !== '' && file_exists($picFs));
            ?>
            <div class="agent-card">
              <div class="d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-3">
                  <?php if ($hasPic): ?>
                    <img class="agent-avatar" src="<?= h($pic) ?>" alt="">
                  <?php else: ?>
                    <div class="agent-avatar d-flex align-items-center justify-content-center bg-light">
                      <i class="fa fa-user text-muted"></i>
                    </div>
                  <?php endif; ?>

                  <div>
                    <div class="fw-bold"><?= h($a['name']) ?> <span class="text-muted">(#<?= $aid ?>)</span></div>
                    <div class="small text-muted">
                      <?= h($a['email']) ?> • <?= h($a['contact_info']) ?>
                    </div>
                    <div class="small">
                      <span class="badge-pill <?= $active ? 'badge-active' : 'badge-inactive' ?>">
                        <?= $active ? 'Active' : 'Disabled' ?>
                      </span>
                      <?php if (!empty($a['last_login_at'])): ?>
                        <span class="text-muted ms-2">Last login: <?= h($a['last_login_at']) ?></span>
                      <?php endif; ?>
                    </div>
                  </div>
                </div>

                <div class="d-flex gap-2">
                  <?php if ($canEdit): ?>
                    <a href="edit_agent.php?id=<?= $aid ?>" class="btn btn-primary btn-sm">Edit</a>

                    <button
                      type="button"
                      class="btn btn-outline-primary btn-sm"
                      data-bs-toggle="modal"
                      data-bs-target="#pwdModal"
                      data-agent-id="<?= $aid ?>"
                      data-agent-name="<?= h($a['name']) ?>"
                      data-require-current="<?= $isSelf ? '1' : '0' ?>"
                    >
                      Password
                    </button>
                  <?php endif; ?>

                  <?php if (is_admin()): ?>
                    <button
                      type="button"
                      class="btn btn-outline-danger btn-sm"
                      data-bs-toggle="modal"
                      data-bs-target="#deleteModal"
                      data-agent-id="<?= $aid ?>"
                      data-agent-name="<?= h($a['name']) ?>"
                    >
                      Delete
                    </button>
                  <?php endif; ?>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>

    </div>
  </div>
</div>

<!-- Change Password Modal -->
<div class="modal fade" id="pwdModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form method="post" class="modal-content">
      <input type="hidden" name="action" value="change_password">
      <input type="hidden" name="agent_id" id="pwd_agent_id" value="">

      <div class="modal-header">
        <h5 class="modal-title">Change Password</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body">
        <div class="mb-2 text-muted">
          For: <strong id="pwd_agent_name">Agent</strong>
        </div>

        <div class="mb-3" id="currentPasswordWrap" style="display:none;">
          <label class="form-label">Current Password</label>
          <input type="password" class="form-control" name="current_password" id="current_password" autocomplete="current-password">
          <div class="form-text">Required when changing your own password.</div>
        </div>

        <div class="mb-3">
          <label class="form-label">New Password</label>
          <input type="password" class="form-control" name="new_password" required minlength="6" autocomplete="new-password">
          <div class="form-text">Minimum 6 characters.</div>
        </div>

        <div class="mb-2">
          <label class="form-label">Confirm New Password</label>
          <input type="password" class="form-control" name="confirm_password" required minlength="6" autocomplete="new-password">
        </div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-primary">Update Password</button>
      </div>
    </form>
  </div>
</div>

<!-- Delete Agent Modal (Admin only) -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form method="post" class="modal-content">
      <input type="hidden" name="action" value="delete_agent">
      <input type="hidden" name="agent_id" id="del_agent_id" value="">

      <div class="modal-header">
        <h5 class="modal-title text-danger">Delete Agent</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body">
        <p class="mb-2">
          You are about to delete: <strong id="del_agent_name">Agent</strong>
        </p>
        <div class="alert alert-warning mb-0">
          This action cannot be undone.
        </div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-danger">Yes, Delete</button>
      </div>
    </form>
  </div>
</div>

<script src="assets/js/jquery.min.js"></script>
<script src="assets/js/bootstrap/bootstrap.bundle.min.js"></script>
<script src="assets/js/script.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {

  // Password modal
  const pwdModal = document.getElementById('pwdModal');
  if (pwdModal) {
    pwdModal.addEventListener('show.bs.modal', function (event) {
      const btn = event.relatedTarget;
      if (!btn) return;

      const agentId = btn.getAttribute('data-agent-id') || '';
      const agentName = btn.getAttribute('data-agent-name') || 'Agent';
      const requireCurrent = btn.getAttribute('data-require-current') === '1';

      document.getElementById('pwd_agent_id').value = agentId;
      document.getElementById('pwd_agent_name').textContent = agentName;

      const wrap = document.getElementById('currentPasswordWrap');
      const currentInput = document.getElementById('current_password');

      if (requireCurrent) {
        wrap.style.display = '';
        currentInput.setAttribute('required', 'required');
        currentInput.value = '';
      } else {
        wrap.style.display = 'none';
        currentInput.removeAttribute('required');
        currentInput.value = '';
      }
    });
  }

  // Delete modal
  const deleteModal = document.getElementById('deleteModal');
  if (deleteModal) {
    deleteModal.addEventListener('show.bs.modal', function (event) {
      const btn = event.relatedTarget;
      if (!btn) return;

      const agentId = btn.getAttribute('data-agent-id') || '';
      const agentName = btn.getAttribute('data-agent-name') || 'Agent';

      document.getElementById('del_agent_id').value = agentId;
      document.getElementById('del_agent_name').textContent = agentName;
    });
  }

});
</script>

</body>
</html>
