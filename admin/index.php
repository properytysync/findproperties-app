<?php
// /admin/index.php
session_start();
require("config.php");
require_once __DIR__ . "/_auth.php";

error_reporting(E_ALL);
ini_set("display_errors", "1");

$msg   = trim($_GET['msg'] ?? '');
$error = '';

// ✅ Redirect logged-in user to correct home
if (is_logged_in()) {
    if (is_admin()) {
        header("Location: dashboard.php");
        exit();
    }
    if (is_agent()) {
        header("Location: agents_list.php");
        exit();
    }
    header("Location: logout.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $login = trim($_POST['login'] ?? '');
    $pass  = (string)($_POST['password'] ?? '');

    if ($login === '' || $pass === '') {
        $error = "Enter login and password";
    } else {

        /**
         * 1) ADMIN LOGIN (admin table: aid, auser, aemail, apass)
         */
        $adminRow = null;
        $st = $con->prepare("SELECT * FROM admin WHERE auser=? OR aemail=? LIMIT 1");
        if ($st) {
            $st->bind_param("ss", $login, $login);
            $st->execute();
            $adminRow = $st->get_result()->fetch_assoc();
            $st->close();
        }

        if ($adminRow) {
            $aid   = (int)$adminRow['aid'];
            $auser = (string)$adminRow['auser'];
            $hashOrPlain = (string)($adminRow['apass'] ?? '');

            $ok = false;

            if ($hashOrPlain !== '' && (str_starts_with($hashOrPlain, '$2y$') || str_starts_with($hashOrPlain, '$argon2'))) {
                $ok = password_verify($pass, $hashOrPlain);
            } else {
                $ok = ($hashOrPlain !== '' && hash_equals($hashOrPlain, $pass));
            }

            if ($ok) {
                // ✅ clear agent session leftovers
                unset($_SESSION['agent_id'], $_SESSION['agent_name']);

                $_SESSION['user_role'] = 'admin';
                $_SESSION['user_id']   = $aid;

                // ✅ legacy keys (IMPORTANT for CRM + older admin pages)
                $_SESSION['auser']     = $auser;
                $_SESSION['admin_id']  = $aid;
                $_SESSION['aid']       = $aid; // ✅ CRM legacy key

                header("Location: dashboard.php");
                exit();
            }
        }

        /**
         * 2) AGENT LOGIN (agents table: email, password_hash, is_active)
         */
        $agentRow = null;
        $st = $con->prepare("SELECT * FROM agents WHERE email=? LIMIT 1");
        if ($st) {
            $st->bind_param("s", $login);
            $st->execute();
            $agentRow = $st->get_result()->fetch_assoc();
            $st->close();
        }

        if ($agentRow) {
            $agent_id = (int)$agentRow['agent_id'];
            $name     = (string)$agentRow['name'];
            $hash     = (string)($agentRow['password_hash'] ?? '');
            $active   = (int)($agentRow['is_active'] ?? 1);

            if ($active !== 1) {
                $error = "Account is disabled. Contact admin.";
            } else {
                if ($hash !== '' && password_verify($pass, $hash)) {

                    // update last login
                    $up = $con->prepare("UPDATE agents SET last_login_at=NOW() WHERE agent_id=?");
                    if ($up) {
                        $up->bind_param("i", $agent_id);
                        $up->execute();
                        $up->close();
                    }

                    // ✅ clear admin session leftovers
                    unset($_SESSION['auser'], $_SESSION['admin_id'], $_SESSION['aid']);

                    $_SESSION['user_role']  = 'agent';
                    $_SESSION['user_id']    = $agent_id;

                    // legacy
                    $_SESSION['agent_id']   = $agent_id;
                    $_SESSION['agent_name'] = $name;

                    header("Location: agents_list.php?msg=" . urlencode("Welcome, {$name}"));
                    exit();
                } else {
                    $error = "Invalid login details";
                }
            }
        } else {
            if ($error === '') $error = "Invalid login details";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin / Agent Login</title>
  <link rel="stylesheet" href="assets/css/vendors/bootstrap.css">
  <style>
    body{background:#f4f7f6}
    .card{max-width:460px;margin:70px auto;border-radius:12px;box-shadow:0 10px 30px rgba(0,0,0,.08)}
  </style>
</head>
<body>
  <div class="card p-4">
    <h4 class="mb-2">Admin / Agent Login</h4>
    <div class="text-muted mb-3">Admin uses auser/aemail. Agents use email.</div>

    <?php if($msg): ?><div class="alert alert-info"><?= h($msg) ?></div><?php endif; ?>
    <?php if($error): ?><div class="alert alert-danger"><?= h($error) ?></div><?php endif; ?>

    <form method="post" autocomplete="off">
      <div class="mb-3">
        <label class="form-label">Email / Username</label>
        <input class="form-control" name="login" required>
      </div>

      <div class="mb-3">
        <label class="form-label">Password</label>
        <input class="form-control" type="password" name="password" required>
      </div>

      <button class="btn btn-primary w-100">Login</button>
    </form>
  </div>
</body>
</html>
