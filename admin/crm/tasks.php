<?php
// /admin/crm/tasks.php
require_once __DIR__ . "/_auth.php";
require_once __DIR__ . "/_db.php";
require_once __DIR__ . "/_helpers.php";

$page_title = "Tasks Management";
require_once __DIR__ . "/_layout_top.php";

$success = '';
$error = '';

// --------------------------
// ✅ In-page DELETE handler
// --------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete_task') {
    $taskId = (int)($_POST['task_id'] ?? 0);

    if ($taskId <= 0) {
        $error = "Invalid task selected.";
    } else {
        $chk = $conn->prepare("SELECT id, title FROM crm_tasks WHERE id=? LIMIT 1");
        if (!$chk) {
            $error = "Database error: cannot validate task.";
        } else {
            $chk->bind_param("i", $taskId);
            $chk->execute();
            $row = $chk->get_result()->fetch_assoc();
            $chk->close();

            if (!$row) {
                $error = "Task not found.";
            } else {
                $title = (string)$row['title'];

                $del = $conn->prepare("DELETE FROM crm_tasks WHERE id=? LIMIT 1");
                if (!$del) {
                    $error = "Database error: cannot delete task.";
                } else {
                    $del->bind_param("i", $taskId);
                    $del->execute();
                    $affected = $del->affected_rows;
                    $del->close();

                    if ($affected > 0) {
                        $success = "Task deleted successfully: {$title} (#{$taskId}).";
                    } else {
                        $error = "Delete failed.";
                    }
                }
            }
        }
    }
}

// Check if priority column exists
$hasPriority = false;
$colCheck = $conn->query("SHOW COLUMNS FROM crm_tasks LIKE 'priority'");
if ($colCheck && $colCheck->num_rows > 0) $hasPriority = true;

// Filter parameters
$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? 'open';
$priority = $_GET['priority'] ?? '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 15;
if ($page < 1) $page = 1;

// Build query
$conditions = [];
$params = [];
$types = '';

if ($search) {
    $conditions[] = "(t.title LIKE ? OR t.description LIKE ? OR l.name LIKE ? OR a.name LIKE ?)";
    $like = "%$search%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $types .= 'ssss';
}

if ($status !== '') {
    $conditions[] = "t.status = ?";
    $params[] = $status;
    $types .= 's';
}

if ($hasPriority && $priority !== '') {
    $conditions[] = "t.priority = ?";
    $params[] = $priority;
    $types .= 's';
}

$where = $conditions ? "WHERE " . implode(" AND ", $conditions) : "";

// Count
$count_sql = "
    SELECT COUNT(*) as total 
    FROM crm_tasks t
    LEFT JOIN crm_leads l ON l.id = t.lead_id
    LEFT JOIN agents a ON a.agent_id = t.assigned_agent_id
    $where
";
$count_stmt = $conn->prepare($count_sql);
if ($params) $count_stmt->bind_param($types, ...$params);
$count_stmt->execute();
$total_records = (int)($count_stmt->get_result()->fetch_assoc()['total'] ?? 0);
$count_stmt->close();

$total_pages = max(1, (int)ceil($total_records / $per_page));
if ($page > $total_pages) $page = $total_pages;

$offset = ($page - 1) * $per_page;

// Tasks
$selectPriority = $hasPriority ? ", t.priority" : "";
$sql = "
    SELECT 
        t.id, t.lead_id, t.assigned_agent_id, t.title, t.description, t.due_at, t.status, t.created_at, t.updated_at
        $selectPriority,
        l.name as lead_name,
        a.name as agent_name,
        a.agent_id as agent_id
    FROM crm_tasks t
    LEFT JOIN crm_leads l ON l.id = t.lead_id
    LEFT JOIN agents a ON a.agent_id = t.assigned_agent_id
    $where
    ORDER BY 
        CASE 
            WHEN t.status = 'open' AND t.due_at < NOW() THEN 0
            WHEN t.status = 'open' THEN 1
            ELSE 2
        END,
        t.due_at ASC,
        t.created_at DESC
    LIMIT ? OFFSET ?
";

$params2 = $params;
$types2 = $types . 'ii';
$params2[] = $per_page;
$params2[] = $offset;

$stmt = $conn->prepare($sql);
$stmt->bind_param($types2, ...$params2);
$stmt->execute();
$tasks = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Stats
$stats = $conn->query("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'open' THEN 1 ELSE 0 END) as open,
        SUM(CASE WHEN status = 'done' THEN 1 ELSE 0 END) as done,
        SUM(CASE WHEN status = 'canceled' THEN 1 ELSE 0 END) as canceled,
        SUM(CASE WHEN status = 'open' AND due_at < NOW() THEN 1 ELSE 0 END) as overdue
    FROM crm_tasks
")->fetch_assoc();
?>

<div class="container-fluid px-3 px-md-4 py-4">

    <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between mb-4">
        <div>
            <h1 class="h2 fw-bold mb-2">
                <i class="bi bi-check-square me-2"></i>Tasks Management
            </h1>
            <p class="text-muted mb-0">
                <?= number_format((int)$stats['total']) ?> total tasks • 
                <span class="text-warning"><?= number_format((int)$stats['open']) ?> open</span> • 
                <span class="text-danger"><?= number_format((int)$stats['overdue']) ?> overdue</span>
            </p>
        </div>

        <div class="d-flex gap-2 mt-3 mt-md-0">
            <a href="task_new.php" class="btn btn-primary d-flex align-items-center">
                <i class="bi bi-plus-circle me-2"></i> Add Task
            </a>
            <a href="calendar.php" class="btn btn-outline-primary d-flex align-items-center">
                <i class="bi bi-calendar me-2"></i> Calendar View
            </a>
        </div>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success d-flex align-items-center">
            <i class="bi bi-check-circle me-2"></i><?= h($success) ?>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger d-flex align-items-center">
            <i class="bi bi-exclamation-triangle me-2"></i><?= h($error) ?>
        </div>
    <?php endif; ?>

    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <input type="text" class="form-control" name="search" placeholder="Search tasks / lead / agent..."
                           value="<?= h($search) ?>">
                </div>

                <div class="col-md-2">
                    <select class="form-select" name="status">
                        <option value="">All Statuses</option>
                        <option value="open" <?= $status === 'open' ? 'selected' : '' ?>>Open</option>
                        <option value="done" <?= $status === 'done' ? 'selected' : '' ?>>Done</option>
                        <option value="canceled" <?= $status === 'canceled' ? 'selected' : '' ?>>Canceled</option>
                    </select>
                </div>

                <?php if ($hasPriority): ?>
                    <div class="col-md-2">
                        <select class="form-select" name="priority">
                            <option value="">All Priorities</option>
                            <option value="high" <?= $priority === 'high' ? 'selected' : '' ?>>High</option>
                            <option value="medium" <?= $priority === 'medium' ? 'selected' : '' ?>>Medium</option>
                            <option value="low" <?= $priority === 'low' ? 'selected' : '' ?>>Low</option>
                        </select>
                    </div>
                <?php endif; ?>

                <div class="col-md-4 d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-filter me-2"></i> Filter
                    </button>
                    <a href="tasks.php" class="btn btn-outline-secondary">
                        <i class="bi bi-x-circle me-2"></i> Clear
                    </a>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <?php if (empty($tasks)): ?>
                <div class="text-center py-5">
                    <i class="bi bi-check-square display-1 text-muted opacity-25"></i>
                    <h4 class="mt-3">No tasks found</h4>
                    <p class="text-muted">Try adjusting your filters or add a new task.</p>
                    <a href="task_new.php" class="btn btn-primary">
                        <i class="bi bi-plus-circle me-2"></i> Add First Task
                    </a>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Task</th>
                                <th>Due Date</th>
                                <th>Lead</th>
                                <th>Assigned Agent</th>
                                <th>Status</th>
                                <?php if ($hasPriority): ?><th>Priority</th><?php endif; ?>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($tasks as $task):
                                $isOverdue = ($task['status'] === 'open' && strtotime($task['due_at']) < time());
                                $dueDate = new DateTime($task['due_at']);
                                $now = new DateTime();
                                $diff = $now->diff($dueDate);
                                $taskPriority = $hasPriority ? ($task['priority'] ?? null) : null;
                            ?>
                                <tr class="<?= $isOverdue ? 'table-warning' : '' ?>">
                                    <td>
                                        <div class="fw-semibold"><?= h($task['title']) ?></div>
                                        <?php if (!empty($task['description'])): ?>
                                            <div class="small text-muted"><?= h(mb_strimwidth($task['description'], 0, 50, '...')) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="<?= $isOverdue ? 'text-danger fw-bold' : '' ?>">
                                            <?= date('M j, Y', strtotime($task['due_at'])) ?>
                                        </div>
                                        <div class="small text-muted">
                                            <?= date('g:i A', strtotime($task['due_at'])) ?>
                                            <?php if ($isOverdue): ?>
                                                <span class="badge bg-danger ms-1">Overdue</span>
                                            <?php elseif ($task['status'] === 'open'): ?>
                                                <span class="badge bg-<?= ($diff->days <= 1 ? 'warning' : 'info') ?> ms-1">
                                                    <?= ($diff->days <= 0 ? 'Today' : ($diff->days . ' days')) ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if (!empty($task['lead_name'])): ?>
                                            <a href="lead_view.php?id=<?= (int)$task['lead_id'] ?>" class="text-decoration-none">
                                                <i class="bi bi-person me-1"></i><?= h($task['lead_name']) ?>
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted">—</span>
                                        <?php endif; ?>
                                    </td>

                                    <td>
                                        <?php if (!empty($task['agent_name'])): ?>
                                            <span class="badge bg-light text-dark border">
                                                <i class="bi bi-person-badge me-1"></i><?= h($task['agent_name']) ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted">—</span>
                                        <?php endif; ?>
                                    </td>

                                    <td>
                                        <span class="badge bg-<?=
                                            $task['status'] === 'open' ? 'warning' :
                                            ($task['status'] === 'done' ? 'success' : 'secondary')
                                        ?>">
                                            <?= ucfirst($task['status']) ?>
                                        </span>
                                    </td>

                                    <?php if ($hasPriority): ?>
                                        <td>
                                            <?php if (!empty($taskPriority)): ?>
                                                <span class="badge bg-<?=
                                                    $taskPriority === 'high' ? 'danger' :
                                                    ($taskPriority === 'medium' ? 'warning' : 'info')
                                                ?>">
                                                    <?= ucfirst($taskPriority) ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="text-muted">—</span>
                                            <?php endif; ?>
                                        </td>
                                    <?php endif; ?>

                                    <td class="text-end">
                                        <div class="btn-group btn-group-sm">
                                            <a href="task_edit.php?id=<?= (int)$task['id'] ?>" class="btn btn-outline-primary">
                                                <i class="bi bi-pencil"></i>
                                            </a>

                                            <button type="button" class="btn btn-outline-success"
                                                    onclick="completeTask(<?= (int)$task['id'] ?>)">
                                                <i class="bi bi-check"></i>
                                            </button>

                                            <button
                                                type="button"
                                                class="btn btn-outline-danger"
                                                data-bs-toggle="modal"
                                                data-bs-target="#deleteTaskModal"
                                                data-task-id="<?= (int)$task['id'] ?>"
                                                data-task-title="<?= h($task['title']) ?>"
                                            >
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <?php if ($total_pages > 1): ?>
                    <nav class="p-3 border-top">
                        <ul class="pagination justify-content-center mb-0">
                            <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                                <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>">Previous</a>
                            </li>

                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <?php if ($i == 1 || $i == $total_pages || ($i >= $page - 2 && $i <= $page + 2)): ?>
                                    <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                        <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"><?= $i ?></a>
                                    </li>
                                <?php elseif ($i == $page - 3 || $i == $page + 3): ?>
                                    <li class="page-item disabled"><span class="page-link">...</span></li>
                                <?php endif; ?>
                            <?php endfor; ?>

                            <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>">
                                <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>">Next</a>
                            </li>
                        </ul>
                    </nav>
                <?php endif; ?>

            <?php endif; ?>
        </div>
    </div>
</div>

<!-- ✅ Delete Task Modal -->
<div class="modal fade" id="deleteTaskModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form method="post" class="modal-content">
      <input type="hidden" name="action" value="delete_task">
      <input type="hidden" name="task_id" id="del_task_id" value="">

      <div class="modal-header">
        <h5 class="modal-title text-danger">
          <i class="bi bi-trash me-2"></i>Delete Task
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">
        <p class="mb-2">You are about to delete:</p>
        <div class="p-2 border rounded bg-light">
          <strong id="del_task_title">Task</strong>
        </div>
        <div class="alert alert-warning mt-3 mb-0">
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

<script>
function completeTask(taskId) {
    if (confirm('Mark this task as completed?')) {
        window.location.href = 'task_complete.php?id=' + taskId;
    }
}

document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('deleteTaskModal');
    if (!modal) return;

    modal.addEventListener('show.bs.modal', function (event) {
        const btn = event.relatedTarget;
        if (!btn) return;

        const id = btn.getAttribute('data-task-id') || '';
        const title = btn.getAttribute('data-task-title') || 'Task';

        document.getElementById('del_task_id').value = id;
        document.getElementById('del_task_title').textContent = title;
    });
});
</script>

<?php require_once __DIR__ . "/_layout_bottom.php"; ?>
