<?php
require_once __DIR__ . "/_auth.php";
require_once __DIR__ . "/_db.php";
require_once __DIR__ . "/_helpers.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_all_read'])) {
    $stmt = $conn->prepare("UPDATE crm_notifications SET is_read=1 WHERE admin_id=?");
    $stmt->bind_param("i", $CRM_ADMIN_ID);
    $stmt->execute();
    $stmt->close();

    flash_set('success', 'All notifications marked as read.');
    redirect("notifications.php");
}

if (isset($_GET['read']) && ctype_digit($_GET['read'])) {
    $nid = (int)$_GET['read'];
    $stmt = $conn->prepare("UPDATE crm_notifications SET is_read=1 WHERE id=? AND admin_id=?");
    $stmt->bind_param("ii", $nid, $CRM_ADMIN_ID);
    $stmt->execute();
    $stmt->close();
    redirect("notifications.php");
}

$flash = flash_get('success');

$stmt = $conn->prepare("
    SELECT id, type, title, body, link, is_read, created_at
    FROM crm_notifications
    WHERE admin_id=?
    ORDER BY created_at DESC
    LIMIT 200
");
$stmt->bind_param("i", $CRM_ADMIN_ID);
$stmt->execute();
$list = $stmt->get_result();
$stmt->close();

include __DIR__ . "/_layout_top.php";
?>

<div class="d-flex align-items-center justify-content-between mb-3">
  <h4 class="mb-0">Notifications</h4>
  <form method="post" class="m-0">
    <button class="btn btn-sm btn-outline-secondary" name="mark_all_read" value="1">Mark all as read</button>
  </form>
</div>

<?php if ($flash): ?>
  <div class="alert alert-success"><?= h($flash) ?></div>
<?php endif; ?>

<div class="card">
  <div class="list-group list-group-flush">
    <?php while ($n = $list->fetch_assoc()): ?>
      <?php
        $isRead = (int)$n['is_read'] === 1;
        $rowClass = $isRead ? '' : 'fw-semibold';
        $badge = $isRead ? '<span class="badge bg-secondary">Read</span>' : '<span class="badge bg-danger">New</span>';
        $link = trim((string)($n['link'] ?? ''));
        $openLink = $link !== '' ? $link : '';
      ?>
      <div class="list-group-item">
        <div class="d-flex justify-content-between align-items-start">
          <div class="me-3">
            <div class="<?= $rowClass ?>">
              <?= h($n['title']) ?> <?= $badge ?>
            </div>
            <?php if (!empty($n['body'])): ?>
              <div class="text-muted small mt-1"><?= nl2br(h($n['body'])) ?></div>
            <?php endif; ?>
            <div class="text-muted small mt-1"><?= h($n['created_at']) ?> • <?= h($n['type']) ?></div>
          </div>
          <div class="text-end d-flex flex-column gap-2">
            <?php if ($openLink !== ''): ?>
              <a class="btn btn-sm btn-primary" href="<?= h($openLink) ?>">Open</a>
            <?php endif; ?>
            <?php if (!$isRead): ?>
              <a class="btn btn-sm btn-outline-secondary" href="notifications.php?read=<?= (int)$n['id'] ?>">Mark read</a>
            <?php endif; ?>
          </div>
        </div>
      </div>
    <?php endwhile; ?>

    <?php if ($list->num_rows === 0): ?>
      <div class="list-group-item text-muted p-4 text-center">No notifications yet.</div>
    <?php endif; ?>
  </div>
</div>

<?php include __DIR__ . "/_layout_bottom.php"; ?>
