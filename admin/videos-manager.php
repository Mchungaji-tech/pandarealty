<?php
/**
 * Panda Realty - Video Library Manager
 * Designed & Developed by TekTrend
 */

$admin_page_title = "Video Library Manager";
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
    $action = clean_input($_POST['video_action'] ?? '');

    if (!verify_csrf_token($csrf)) {
        $err = 'Security session expired. Refresh the page and try again.';
    } elseif ($action === 'save_video') {
        $id = (int)($_POST['video_id'] ?? 0);
        $title = clean_input($_POST['title'] ?? '');
        $source_url = clean_input($_POST['source_url'] ?? '');
        $summary = clean_input($_POST['summary'] ?? '');
        $display_order = (int)($_POST['display_order'] ?? 0);
        $linked_property_id = (int)($_POST['linked_property_id'] ?? 0);
        $is_featured = isset($_POST['is_featured']) ? 1 : 0;
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        $platform = 'other';
        $embed_url = normalize_embed_video_url($source_url, $platform);

        if ($title === '' || $embed_url === '') {
            $err = 'Title and a supported YouTube, Facebook, Instagram, or TikTok URL are required.';
        } else {
            $title = db_escape($title);
            $platform = db_escape($platform);
            $embed_url = db_escape($embed_url);
            $source_url = db_escape($source_url);
            $summary = db_escape($summary);
            $linked_property_sql = $linked_property_id > 0 ? $linked_property_id : 'NULL';

            if ($id > 0) {
                $sql = "UPDATE media_videos SET
                    title = '$title',
                    platform = '$platform',
                    embed_url = '$embed_url',
                    source_url = '$source_url',
                    summary = '$summary',
                    linked_property_id = $linked_property_sql,
                    is_featured = $is_featured,
                    is_active = $is_active,
                    display_order = $display_order
                    WHERE id = $id";
                $msg = mysqli_query($conn, $sql) ? 'Video updated successfully.' : 'Unable to update video.';
            } else {
                $sql = "INSERT INTO media_videos
                    (title, platform, embed_url, source_url, summary, linked_property_id, is_featured, is_active, display_order)
                    VALUES
                    ('$title', '$platform', '$embed_url', '$source_url', '$summary', $linked_property_sql, $is_featured, $is_active, $display_order)";
                $msg = mysqli_query($conn, $sql) ? 'Video created successfully.' : 'Unable to create video.';
            }
        }
    } elseif ($action === 'delete_video') {
        $id = (int)($_POST['video_id'] ?? 0);
        if ($id > 0 && mysqli_query($conn, "DELETE FROM media_videos WHERE id = $id")) {
            $msg = 'Video deleted.';
        } else {
            $err = 'Unable to delete video.';
        }
    }
}

$edit_id = (int)($_GET['edit'] ?? 0);
$edit_item = null;
if ($edit_id > 0) {
    $edit_res = mysqli_query($conn, "SELECT * FROM media_videos WHERE id = $edit_id LIMIT 1");
    $edit_item = $edit_res ? mysqli_fetch_assoc($edit_res) : null;
}

$videos = [];
$res = mysqli_query($conn, "SELECT mv.*, p.title AS property_title FROM media_videos mv LEFT JOIN properties p ON mv.linked_property_id = p.id ORDER BY mv.display_order ASC, mv.id DESC");
if ($res) {
    while ($row = mysqli_fetch_assoc($res)) {
        $videos[] = $row;
    }
}
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; gap: 15px; flex-wrap: wrap;">
    <div>
        <h3 style="font-size: 20px; font-weight: 700; color: var(--admin-text);">Video Library Manager</h3>
        <p style="color: var(--admin-muted); font-size: 13px;">Manage embedded media for the public video page and featured homepage reels.</p>
    </div>
    <div style="display: flex; gap: 10px; flex-wrap: wrap;">
        <a href="../videos.php" target="_blank" class="btn-icon" style="width: auto; padding: 10px 16px; color: var(--admin-text);">
            <i class="fas fa-external-link-alt"></i> Open Public Video Page
        </a>
        <a href="testimonials.php" class="btn-icon" style="width: auto; padding: 10px 16px; color: var(--admin-text);">
            <i class="fas fa-quote-left"></i> Testimonials
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
        <h3><i class="fas fa-video"></i> <?= $edit_item ? 'Edit Video' : 'Add Video' ?></h3>
    </div>

    <form action="videos-manager.php<?= $edit_item ? '?edit=' . (int)$edit_item['id'] : '' ?>" method="POST">
        <input type="hidden" name="csrf_token" value="<?= get_csrf_token() ?>">
        <input type="hidden" name="video_action" value="save_video">
        <input type="hidden" name="video_id" value="<?= (int)($edit_item['id'] ?? 0) ?>">

        <div class="admin-form-grid">
            <div class="admin-form-group">
                <label>Video Title *</label>
                <input type="text" name="title" value="<?= htmlspecialchars($edit_item['title'] ?? '') ?>" required>
            </div>
            <div class="admin-form-group">
                <label>Source URL *</label>
                <input type="text" name="source_url" value="<?= htmlspecialchars($edit_item['source_url'] ?? '') ?>" placeholder="https://www.youtube.com/watch?v=..." required>
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
            <label>Summary</label>
            <textarea name="summary" rows="3"><?= htmlspecialchars($edit_item['summary'] ?? '') ?></textarea>
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
            <i class="fas fa-save"></i> Save Video
        </button>
    </form>
</div>

<div class="admin-card">
    <div class="admin-card-header">
        <h3><i class="fas fa-photo-video"></i> Video Library Records</h3>
    </div>

    <div class="admin-table-wrap">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Platform</th>
                    <th>Linked Property</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($videos)): ?>
                    <tr><td colspan="5" style="text-align: center; padding: 30px; color: var(--admin-muted);">No videos added yet.</td></tr>
                <?php else: ?>
                    <?php foreach ($videos as $video): ?>
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars($video['title']) ?></strong><br>
                                <span style="font-size: 11px; color: var(--admin-muted);"><?= htmlspecialchars($video['summary'] ? substr($video['summary'], 0, 90) . (strlen($video['summary']) > 90 ? '...' : '') : 'No summary added.') ?></span>
                            </td>
                            <td><span class="status-pill info"><?= htmlspecialchars(get_video_platform_label($video['platform'])) ?></span></td>
                            <td><?= htmlspecialchars($video['property_title'] ?: 'General Media') ?></td>
                            <td><span class="status-pill <?= (int)$video['is_active'] === 1 ? 'success' : 'warning' ?>"><?= (int)$video['is_active'] === 1 ? 'Live' : 'Hidden' ?></span></td>
                            <td>
                                <div class="action-btn-group">
                                    <a href="videos-manager.php?edit=<?= (int)$video['id'] ?>" class="btn-icon" title="Edit"><i class="fas fa-pencil-alt"></i></a>
                                    <a href="<?= htmlspecialchars($video['source_url']) ?>" target="_blank" rel="noopener" class="btn-icon" title="Open Source"><i class="fas fa-external-link-alt"></i></a>
                                    <form action="videos-manager.php" method="POST" style="display: inline;">
                                        <input type="hidden" name="csrf_token" value="<?= get_csrf_token() ?>">
                                        <input type="hidden" name="video_action" value="delete_video">
                                        <input type="hidden" name="video_id" value="<?= (int)$video['id'] ?>">
                                        <button type="submit" class="btn-icon delete" title="Delete" onclick="return confirm('Delete this video?')" style="border: none; background: transparent;">
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
