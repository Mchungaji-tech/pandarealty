<?php
/**
 * Panda Realty - Personal Tasks & Reminders Board
 * Designed & Developed by TekTrend
 */

require_once __DIR__ . '/../config/settings.php';
require_admin();
require_capability('manage_tasks');

$conn = get_db_connection();
$uid = (int)($_SESSION['user_id'] ?? 0);
$msg = '';

// Handle Add Task
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_task'])) {
    $title = clean_input($_POST['title'] ?? '');
    $desc = clean_input($_POST['description'] ?? '');
    $due = clean_input($_POST['due_date'] ?? '');
    $prio = clean_input($_POST['priority'] ?? 'medium');

    $due_sql = !empty($due) ? "'$due'" : "NULL";
    if (!empty($title)) {
        mysqli_query($conn, "INSERT INTO tasks (user_id, title, description, due_date, priority, status) VALUES ($uid, '$title', '$desc', $due_sql, '$prio', 'pending')");
        $msg = "New task added!";
    }
}

// Handle Delete Task
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    mysqli_query($conn, "DELETE FROM tasks WHERE id = $id AND user_id = $uid");
    $msg = "Task removed.";
}

// Handle Toggle
if (isset($_GET['toggle']) && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $st = clean_input($_GET['toggle']);
    mysqli_query($conn, "UPDATE tasks SET status = '$st' WHERE id = $id AND user_id = $uid");
    $msg = "Task status updated.";
}

// Fetch Tasks
$res = mysqli_query($conn, "SELECT * FROM tasks WHERE user_id = $uid ORDER BY status ASC, due_date ASC, id DESC");
$tasks = [];
if ($res) {
    while ($row = mysqli_fetch_assoc($res)) {
        $tasks[] = $row;
    }
}

$admin_page_title = "Tasks & Reminders";
require_once __DIR__ . '/includes/admin-header.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; flex-wrap: wrap; gap: 15px;">
    <div>
        <h3 style="font-size: 20px; font-weight: 700; color: #fff;">My Personal Tasks & Follow-up Reminders</h3>
        <p style="color: var(--admin-muted); font-size: 13px;">Keep track of title deed pickups, client site inspections, and escrow settlements.</p>
    </div>

    <button type="button" class="btn" onclick="openModal('addTaskModal')" style="background: var(--admin-accent); color: #000; font-weight: 700; padding: 12px 20px; border-radius: 6px; font-size: 13px; border: none; cursor: pointer;">
        <i class="fas fa-plus"></i> Add New Task
    </button>
</div>

<?php if (!empty($msg)): ?>
    <div class="alert-box alert-success"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($msg) ?></div>
<?php endif; ?>

<div class="admin-card">
    <?php if (empty($tasks)): ?>
        <p style="color: var(--admin-muted); padding: 30px; text-align: center;">No tasks listed. Click "Add New Task" to create one!</p>
    <?php else: ?>
        <div style="display: flex; flex-direction: column; gap: 10px;">
            <?php foreach ($tasks as $t): ?>
                <div class="task-item <?= $t['status'] === 'completed' ? 'completed' : '' ?>" style="padding: 15px; background: #ffffff; border-radius: 6px; border: 1px solid var(--admin-border);">
                    <div class="task-left">
                        <a href="tasks.php?toggle=<?= $t['status'] === 'completed' ? 'pending' : 'completed' ?>&id=<?= $t['id'] ?>" class="btn-icon" style="width: 28px; height: 28px;">
                            <i class="<?= $t['status'] === 'completed' ? 'fas fa-check-square' : 'far fa-square' ?>" style="color: var(--admin-accent);"></i>
                        </a>
                        <div>
                            <span style="font-size: 14px; font-weight: 600; color: var(--admin-text);"><?= htmlspecialchars($t['title']) ?></span>
                            <?php if (!empty($t['description'])): ?>
                                <p style="font-size: 12px; color: var(--admin-muted); margin-top: 3px;"><?= htmlspecialchars($t['description']) ?></p>
                            <?php endif; ?>
                            <span style="font-size: 11px; color: var(--admin-muted); margin-top: 4px; display: inline-block;">
                                <i class="fas fa-clock"></i> Due: <?= $t['due_date'] ? date('M d, Y', strtotime($t['due_date'])) : 'No deadline' ?>
                            </span>
                        </div>
                    </div>

                    <div style="display: flex; align-items: center; gap: 15px;">
                        <span class="status-pill <?= $t['priority'] === 'urgent' ? 'danger' : ($t['priority'] === 'high' ? 'warning' : 'info') ?>">
                            <?= strtoupper($t['priority']) ?>
                        </span>
                        <a href="tasks.php?delete=<?= $t['id'] ?>" class="btn-icon delete" title="Delete Task" onclick="return confirm('Delete task?')"><i class="fas fa-trash-alt"></i></a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Add Task Modal -->
<div id="addTaskModal" class="modal">
    <div class="modal-content">
        <button type="button" class="modal-close" onclick="closeModal('addTaskModal')"><i class="fas fa-times"></i></button>
        <h3 class="font-serif" style="font-size: 22px; margin-bottom: 20px; color: var(--admin-text);">Create New Task</h3>

        <form action="tasks.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?= get_csrf_token() ?>">
            <input type="hidden" name="add_task" value="1">

            <div class="admin-form-group">
                <label>Task Title *</label>
                <input type="text" name="title" placeholder="e.g. Title deed registration for Annex Plot #18" required>
            </div>

            <div class="admin-form-group">
                <label>Details / Notes</label>
                <textarea name="description" placeholder="Additional details..."></textarea>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <div class="admin-form-group">
                    <label>Due Date</label>
                    <input type="date" name="due_date" min="<?= date('Y-m-d') ?>">
                </div>

                <div class="admin-form-group">
                    <label>Priority</label>
                    <select name="priority">
                        <option value="low">Low</option>
                        <option value="medium" selected>Medium</option>
                        <option value="high">High</option>
                        <option value="urgent">Urgent</option>
                    </select>
                </div>
            </div>

            <button type="submit" class="btn" style="width: 100%; background: var(--admin-accent); color: #000; font-weight: 700; padding: 14px; border-radius: 6px; border: none; cursor: pointer;">
                <i class="fas fa-plus"></i> Save Task
            </button>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
