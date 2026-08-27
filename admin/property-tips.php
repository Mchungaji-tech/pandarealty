<?php
/**
 * Panda Realty - Property Tips Manager
 * Designed & Developed by TekTrend
 */

$admin_page_title = "Property Tips Manager";
require_once __DIR__ . '/includes/admin-header.php';
require_capability('manage_cms');

$conn = get_db_connection();
$msg = '';
$err = '';

$properties = [];
$property_res = mysqli_query($conn, "SELECT id, title FROM properties ORDER BY title ASC");
if ($property_res) {
    while ($row = mysqli_fetch_assoc($property_res)) {
        $properties[] = $row;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = clean_input($_POST['csrf_token'] ?? '');
    $action = clean_input($_POST['tip_action'] ?? '');

    if (!verify_csrf_token($csrf)) {
        $err = 'Security session expired. Refresh the page and try again.';
    } elseif ($action === 'save_tip') {
        $id = (int)($_POST['tip_id'] ?? 0);
        $title = clean_input($_POST['title'] ?? '');
        $message = clean_input($_POST['message'] ?? '');
        $icon_class = clean_input($_POST['icon_class'] ?? 'fas fa-lightbulb');
        $cta_label = clean_input($_POST['cta_label'] ?? '');
        $cta_url = clean_input($_POST['cta_url'] ?? '');
        $linked_property_id = (int)($_POST['linked_property_id'] ?? 0);
        $display_order = (int)($_POST['display_order'] ?? 0);
        $is_active = isset($_POST['is_active']) ? 1 : 0;

        if ($title === '' || $message === '') {
            $err = 'Tip title and message are required.';
        } else {
            $title = db_escape($title);
            $message = db_escape($message);
            $icon_class = db_escape($icon_class);
            $cta_label = db_escape($cta_label);
            $cta_url = db_escape($cta_url);
            $linked_property_sql = $linked_property_id > 0 ? $linked_property_id : 'NULL';

            if ($id > 0) {
                $sql = "UPDATE property_tips SET
                    title = '$title',
                    message = '$message',
                    icon_class = '$icon_class',
                    cta_label = '$cta_label',
                    cta_url = '$cta_url',
                    linked_property_id = $linked_property_sql,
                    is_active = $is_active,
                    display_order = $display_order
                    WHERE id = $id";
                $msg = mysqli_query($conn, $sql) ? 'Property tip updated successfully.' : 'Unable to update property tip.';
            } else {
                $sql = "INSERT INTO property_tips
                    (title, message, icon_class, cta_label, cta_url, linked_property_id, is_active, display_order)
                    VALUES
                    ('$title', '$message', '$icon_class', '$cta_label', '$cta_url', $linked_property_sql, $is_active, $display_order)";
                $msg = mysqli_query($conn, $sql) ? 'Property tip created successfully.' : 'Unable to create property tip.';
            }
        }
    } elseif ($action === 'delete_tip') {
        $id = (int)($_POST['tip_id'] ?? 0);
        if ($id > 0 && mysqli_query($conn, "DELETE FROM property_tips WHERE id = $id")) {
            $msg = 'Property tip deleted.';
        } else {
            $err = 'Unable to delete property tip.';
        }
    }
}

$edit_id = (int)($_GET['edit'] ?? 0);
$edit_item = null;
if ($edit_id > 0) {
    $edit_res = mysqli_query($conn, "SELECT * FROM property_tips WHERE id = $edit_id LIMIT 1");
    $edit_item = $edit_res ? mysqli_fetch_assoc($edit_res) : null;
}

$tips = [];
$res = mysqli_query($conn, "SELECT pt.*, p.title AS property_title FROM property_tips pt LEFT JOIN properties p ON pt.linked_property_id = p.id ORDER BY pt.display_order ASC, pt.id DESC");
if ($res) {
    while ($row = mysqli_fetch_assoc($res)) {
        $tips[] = $row;
    }
}
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; gap: 15px; flex-wrap: wrap;">
    <div>
        <h3 style="font-size: 20px; font-weight: 700; color: var(--admin-text);">Property Tips & Live Notifications</h3>
        <p style="color: var(--admin-muted); font-size: 13px;">Manage the public property tips toast notifications and link them to featured listings.</p>
    </div>
    <div style="display: flex; gap: 10px; flex-wrap: wrap;">
        <a href="cms-editor.php" class="btn-icon" style="width: auto; padding: 10px 16px; color: var(--admin-text);">
            <i class="fas fa-edit"></i> CMS Editor
        </a>
        <a href="videos-manager.php" class="btn-icon" style="width: auto; padding: 10px 16px; color: var(--admin-text);">
            <i class="fas fa-video"></i> Video Manager
        </a>
    </div>
</div>

<?php if ($msg): ?><div class="alert-box alert-success"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($msg) ?></div><?php endif; ?>
<?php if ($err): ?><div class="alert-box alert-danger"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($err) ?></div><?php endif; ?>

<div class="admin-card" style="margin-bottom: 24px;">
    <div class="admin-card-header">
        <h3><i class="fas fa-lightbulb"></i> <?= $edit_item ? 'Edit Property Tip' : 'Add Property Tip' ?></h3>
    </div>

    <form action="property-tips.php<?= $edit_item ? '?edit=' . (int)$edit_item['id'] : '' ?>" method="POST">
        <input type="hidden" name="csrf_token" value="<?= get_csrf_token() ?>">
        <input type="hidden" name="tip_action" value="save_tip">
        <input type="hidden" name="tip_id" value="<?= (int)($edit_item['id'] ?? 0) ?>">

        <div class="admin-form-grid">
            <div class="admin-form-group">
                <label>Tip Title *</label>
                <input type="text" name="title" value="<?= htmlspecialchars($edit_item['title'] ?? '') ?>" required>
            </div>
            <div class="admin-form-group">
                <label>Icon Class</label>
                <input type="text" name="icon_class" value="<?= htmlspecialchars($edit_item['icon_class'] ?? 'fas fa-lightbulb') ?>" placeholder="fas fa-lightbulb">
            </div>
            <div class="admin-form-group">
                <label>CTA Label</label>
                <input type="text" name="cta_label" value="<?= htmlspecialchars($edit_item['cta_label'] ?? '') ?>" placeholder="View Property">
            </div>
            <div class="admin-form-group">
                <label>CTA URL</label>
                <input type="text" name="cta_url" value="<?= htmlspecialchars($edit_item['cta_url'] ?? '') ?>" placeholder="/properties or full URL">
            </div>
            <div class="admin-form-group">
                <label>Linked Property</label>
                <select name="linked_property_id">
                    <option value="0">Not Linked To A Property</option>
                    <?php foreach ($properties as $property): ?>
                        <option value="<?= (int)$property['id'] ?>" <?= (int)($edit_item['linked_property_id'] ?? 0) === (int)$property['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($property['title']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="admin-form-group">
                <label>Display Order</label>
                <input type="number" name="display_order" value="<?= (int)($edit_item['display_order'] ?? 0) ?>">
            </div>
        </div>

        <div class="admin-form-group">
            <label>Tip Message *</label>
            <textarea name="message" rows="3" required><?= htmlspecialchars($edit_item['message'] ?? '') ?></textarea>
        </div>

        <label style="display: flex; gap: 8px; align-items: center; margin-bottom: 18px;">
            <input type="checkbox" name="is_active" value="1" <?= !isset($edit_item['is_active']) || (int)$edit_item['is_active'] === 1 ? 'checked' : '' ?>>
            <span>Visible On Public Site</span>
        </label>

        <button type="submit" class="btn" style="background: var(--admin-accent); color: #fff; border: none; padding: 12px 18px; border-radius: 6px; font-weight: 700;">
            <i class="fas fa-save"></i> Save Property Tip
        </button>
    </form>
</div>

<div class="admin-card">
    <div class="admin-card-header">
        <h3><i class="fas fa-stream"></i> Property Tip Library</h3>
    </div>

    <div class="admin-table-wrap">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Tip</th>
                    <th>Linked Property</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($tips)): ?>
                    <tr><td colspan="4" style="text-align: center; padding: 30px; color: var(--admin-muted);">No property tips added yet.</td></tr>
                <?php else: ?>
                    <?php foreach ($tips as $tip): ?>
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars($tip['title']) ?></strong><br>
                                <span style="font-size: 11px; color: var(--admin-muted);"><?= htmlspecialchars(substr($tip['message'], 0, 100) . (strlen($tip['message']) > 100 ? '...' : '')) ?></span>
                            </td>
                            <td><?= htmlspecialchars($tip['property_title'] ?: 'General Tip') ?></td>
                            <td><span class="status-pill <?= (int)$tip['is_active'] === 1 ? 'success' : 'warning' ?>"><?= (int)$tip['is_active'] === 1 ? 'Live' : 'Hidden' ?></span></td>
                            <td>
                                <div class="action-btn-group">
                                    <a href="property-tips.php?edit=<?= (int)$tip['id'] ?>" class="btn-icon" title="Edit"><i class="fas fa-pencil-alt"></i></a>
                                    <form action="property-tips.php" method="POST" style="display: inline;">
                                        <input type="hidden" name="csrf_token" value="<?= get_csrf_token() ?>">
                                        <input type="hidden" name="tip_action" value="delete_tip">
                                        <input type="hidden" name="tip_id" value="<?= (int)$tip['id'] ?>">
                                        <button type="submit" class="btn-icon delete" title="Delete" onclick="return confirm('Delete this property tip?')" style="border: none; background: transparent;">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
