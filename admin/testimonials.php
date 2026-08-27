<?php
/**
 * Panda Realty - Testimonials Manager
 * Designed & Developed by TekTrend
 */

$admin_page_title = "Testimonials Manager";
require_once __DIR__ . '/includes/admin-header.php';
require_capability('manage_cms');

$conn = get_db_connection();
$msg = '';
$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = clean_input($_POST['csrf_token'] ?? '');
    $action = clean_input($_POST['testimonial_action'] ?? '');

    if (!verify_csrf_token($csrf)) {
        $err = 'Security session expired. Refresh the page and try again.';
    } elseif ($action === 'save_testimonial') {
        $id = (int)($_POST['testimonial_id'] ?? 0);
        $client_name = clean_input($_POST['client_name'] ?? '');
        $client_role = clean_input($_POST['client_role'] ?? '');
        $client_location = clean_input($_POST['client_location'] ?? '');
        $quote_text = clean_input($_POST['quote_text'] ?? '');
        $avatar_url = clean_input($_POST['avatar_url'] ?? '');
        $rating = max(1, min(5, (int)($_POST['rating'] ?? 5)));
        $display_order = (int)($_POST['display_order'] ?? 0);
        $is_featured = isset($_POST['is_featured']) ? 1 : 0;
        $is_active = isset($_POST['is_active']) ? 1 : 0;

        if ($client_name === '' || $quote_text === '') {
            $err = 'Client name and testimonial quote are required.';
        } else {
            $client_name = db_escape($client_name);
            $client_role = db_escape($client_role);
            $client_location = db_escape($client_location);
            $quote_text = db_escape($quote_text);
            $avatar_url = db_escape($avatar_url);

            if ($id > 0) {
                $sql = "UPDATE testimonials SET
                    client_name = '$client_name',
                    client_role = '$client_role',
                    client_location = '$client_location',
                    quote_text = '$quote_text',
                    rating = $rating,
                    avatar_url = '$avatar_url',
                    is_featured = $is_featured,
                    is_active = $is_active,
                    display_order = $display_order
                    WHERE id = $id";
                $msg = mysqli_query($conn, $sql) ? 'Testimonial updated successfully.' : 'Unable to update testimonial.';
            } else {
                $sql = "INSERT INTO testimonials
                    (client_name, client_role, client_location, quote_text, rating, avatar_url, is_featured, is_active, display_order)
                    VALUES
                    ('$client_name', '$client_role', '$client_location', '$quote_text', $rating, '$avatar_url', $is_featured, $is_active, $display_order)";
                $msg = mysqli_query($conn, $sql) ? 'Testimonial created successfully.' : 'Unable to create testimonial.';
            }
        }
    } elseif ($action === 'delete_testimonial') {
        $id = (int)($_POST['testimonial_id'] ?? 0);
        if ($id > 0 && mysqli_query($conn, "DELETE FROM testimonials WHERE id = $id")) {
            $msg = 'Testimonial deleted.';
        } else {
            $err = 'Unable to delete testimonial.';
        }
    }
}

$edit_id = (int)($_GET['edit'] ?? 0);
$edit_item = null;
if ($edit_id > 0) {
    $edit_res = mysqli_query($conn, "SELECT * FROM testimonials WHERE id = $edit_id LIMIT 1");
    $edit_item = $edit_res ? mysqli_fetch_assoc($edit_res) : null;
}

$testimonials = [];
$res = mysqli_query($conn, "SELECT * FROM testimonials ORDER BY display_order ASC, id DESC");
if ($res) {
    while ($row = mysqli_fetch_assoc($res)) {
        $testimonials[] = $row;
    }
}
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; gap: 15px; flex-wrap: wrap;">
    <div>
        <h3 style="font-size: 20px; font-weight: 700; color: var(--admin-text);">Public Testimonials</h3>
        <p style="color: var(--admin-muted); font-size: 13px;">Manage homepage trust signals and client success stories for the public site.</p>
    </div>
    <div style="display: flex; gap: 10px; flex-wrap: wrap;">
        <a href="cms-editor.php" class="btn-icon" style="width: auto; padding: 10px 16px; color: var(--admin-text);">
            <i class="fas fa-edit"></i> CMS Editor
        </a>
        <a href="videos-manager.php" class="btn-icon" style="width: auto; padding: 10px 16px; color: var(--admin-text);">
            <i class="fas fa-video"></i> Video Manager
        </a>
        <a href="property-tips.php" class="btn-icon" style="width: auto; padding: 10px 16px; color: var(--admin-text);">
            <i class="fas fa-lightbulb"></i> Property Tips
        </a>
    </div>
</div>

<?php if ($msg): ?><div class="alert-box alert-success"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($msg) ?></div><?php endif; ?>
<?php if ($err): ?><div class="alert-box alert-danger"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($err) ?></div><?php endif; ?>

<div class="admin-card" style="margin-bottom: 24px;">
    <div class="admin-card-header">
        <h3><i class="fas fa-quote-left"></i> <?= $edit_item ? 'Edit Testimonial' : 'Add Testimonial' ?></h3>
    </div>

    <form action="testimonials.php<?= $edit_item ? '?edit=' . (int)$edit_item['id'] : '' ?>" method="POST">
        <input type="hidden" name="csrf_token" value="<?= get_csrf_token() ?>">
        <input type="hidden" name="testimonial_action" value="save_testimonial">
        <input type="hidden" name="testimonial_id" value="<?= (int)($edit_item['id'] ?? 0) ?>">

        <div class="admin-form-grid">
            <div class="admin-form-group">
                <label>Client Name *</label>
                <input type="text" name="client_name" value="<?= htmlspecialchars($edit_item['client_name'] ?? '') ?>" required>
            </div>
            <div class="admin-form-group">
                <label>Client Role</label>
                <input type="text" name="client_role" value="<?= htmlspecialchars($edit_item['client_role'] ?? '') ?>" placeholder="Investor, Buyer, CEO">
            </div>
            <div class="admin-form-group">
                <label>Client Location</label>
                <input type="text" name="client_location" value="<?= htmlspecialchars($edit_item['client_location'] ?? '') ?>" placeholder="Eldoret, Nairobi, Diaspora">
            </div>
            <div class="admin-form-group">
                <label>Avatar URL</label>
                <input type="text" name="avatar_url" value="<?= htmlspecialchars($edit_item['avatar_url'] ?? '') ?>" placeholder="https://...">
            </div>
            <div class="admin-form-group">
                <label>Rating</label>
                <input type="number" min="1" max="5" name="rating" value="<?= (int)($edit_item['rating'] ?? 5) ?>">
            </div>
            <div class="admin-form-group">
                <label>Display Order</label>
                <input type="number" name="display_order" value="<?= (int)($edit_item['display_order'] ?? 0) ?>">
            </div>
        </div>

        <div class="admin-form-group">
            <label>Quote *</label>
            <textarea name="quote_text" rows="4" required><?= htmlspecialchars($edit_item['quote_text'] ?? '') ?></textarea>
        </div>

        <div style="display: flex; gap: 24px; flex-wrap: wrap; margin-bottom: 18px;">
            <label style="display: flex; gap: 8px; align-items: center;">
                <input type="checkbox" name="is_featured" value="1" <?= !isset($edit_item['is_featured']) || (int)$edit_item['is_featured'] === 1 ? 'checked' : '' ?>>
                <span>Featured</span>
            </label>
            <label style="display: flex; gap: 8px; align-items: center;">
                <input type="checkbox" name="is_active" value="1" <?= !isset($edit_item['is_active']) || (int)$edit_item['is_active'] === 1 ? 'checked' : '' ?>>
                <span>Visible On Public Site</span>
            </label>
        </div>

        <button type="submit" class="btn" style="background: var(--admin-accent); color: #fff; border: none; padding: 12px 18px; border-radius: 6px; font-weight: 700;">
            <i class="fas fa-save"></i> Save Testimonial
        </button>
    </form>
</div>

<div class="admin-card">
    <div class="admin-card-header">
        <h3><i class="fas fa-list"></i> Testimonial Library</h3>
    </div>

    <div class="admin-table-wrap">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Client</th>
                    <th>Quote</th>
                    <th>Rating</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($testimonials)): ?>
                    <tr><td colspan="5" style="text-align: center; padding: 30px; color: var(--admin-muted);">No testimonials added yet.</td></tr>
                <?php else: ?>
                    <?php foreach ($testimonials as $item): ?>
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars($item['client_name']) ?></strong><br>
                                <span style="font-size: 11px; color: var(--admin-muted);"><?= htmlspecialchars(trim(($item['client_role'] ?: 'Client') . ($item['client_location'] ? ' • ' . $item['client_location'] : ''))) ?></span>
                            </td>
                            <td><?= htmlspecialchars(substr($item['quote_text'], 0, 100) . (strlen($item['quote_text']) > 100 ? '...' : '')) ?></td>
                            <td><?= str_repeat('★', (int)$item['rating']) ?></td>
                            <td>
                                <span class="status-pill <?= (int)$item['is_active'] === 1 ? 'success' : 'warning' ?>"><?= (int)$item['is_active'] === 1 ? 'Live' : 'Hidden' ?></span>
                            </td>
                            <td>
                                <div class="action-btn-group">
                                    <a href="testimonials.php?edit=<?= (int)$item['id'] ?>" class="btn-icon" title="Edit"><i class="fas fa-pencil-alt"></i></a>
                                    <form action="testimonials.php" method="POST" style="display: inline;">
                                        <input type="hidden" name="csrf_token" value="<?= get_csrf_token() ?>">
                                        <input type="hidden" name="testimonial_action" value="delete_testimonial">
                                        <input type="hidden" name="testimonial_id" value="<?= (int)$item['id'] ?>">
                                        <button type="submit" class="btn-icon delete" title="Delete" onclick="return confirm('Delete this testimonial?')" style="border: none; background: transparent;">
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
