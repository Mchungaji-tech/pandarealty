<?php
/**
 * Panda Realty - Admin Properties Management Table
 * Designed & Developed by TekTrend
 */

require_once __DIR__ . '/../config/settings.php';
require_admin();
require_capability('manage_properties');

$conn = get_db_connection();
$msg = '';

// Handle Delete Action
if (isset($_GET['delete'])) {
    $del_id = (int)$_GET['delete'];
    $del_query = "DELETE FROM properties WHERE id = $del_id";
    if (mysqli_query($conn, $del_query)) {
        log_security_action('PROPERTY_DELETED', "Property ID #$del_id was deleted", 'warning');
        $msg = "Property deleted successfully.";
    }
}

// Handle Quick Status Toggle
if (isset($_GET['toggle_status']) && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $new_status = clean_input($_GET['toggle_status']);
    $upd = "UPDATE properties SET status = '$new_status' WHERE id = $id";
    mysqli_query($conn, $upd);
    log_security_action('PROPERTY_STATUS_UPDATED', "Property #$id status changed to $new_status");
    $msg = "Property status updated.";
}

// Fetch Properties
$filter_type = clean_input($_GET['type'] ?? '');
$where = "1=1";
if (!empty($filter_type)) {
    $t_safe = db_escape($filter_type);
    $where .= " AND type = '$t_safe'";
}

$res = mysqli_query($conn, "SELECT * FROM properties WHERE $where ORDER BY id DESC");
$properties = [];
if ($res) {
    while ($row = mysqli_fetch_assoc($res)) {
        $properties[] = $row;
    }
}

$admin_page_title = "Manage Properties & Land Inventory";
require_once __DIR__ . '/includes/admin-header.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; flex-wrap: wrap; gap: 15px;">
    <div>
        <h3 style="font-size: 20px; font-weight: 700; color: #fff;">Real Estate Inventory</h3>
        <p style="color: var(--admin-muted); font-size: 13px;">Manage titled land, studio apartments, residential houses, and building developments.</p>
    </div>

    <a href="property-add.php" class="btn" style="background: var(--admin-accent); color: #000; font-weight: 700; padding: 12px 20px; border-radius: 6px; font-size: 13px;">
        <i class="fas fa-plus"></i> Add New Property Listing
    </a>
</div>

<?php if (!empty($msg)): ?>
    <div class="alert-box alert-success">
        <i class="fas fa-check-circle"></i>
        <span><?= htmlspecialchars($msg) ?></span>
    </div>
<?php endif; ?>

<!-- Filter Tabs -->
<div style="display: flex; gap: 10px; margin-bottom: 20px; flex-wrap: wrap;">
    <a href="properties.php" class="btn-icon" style="width: auto; padding: 8px 16px; font-size: 12px; font-weight: 600; <?= empty($filter_type) ? 'background: var(--admin-accent); color: #000;' : '' ?>">All Listings</a>
    <a href="properties.php?type=studio" class="btn-icon" style="width: auto; padding: 8px 16px; font-size: 12px; font-weight: 600; <?= $filter_type === 'studio' ? 'background: var(--admin-accent); color: #000;' : '' ?>">Studio Apartments</a>
    <a href="properties.php?type=land" class="btn-icon" style="width: auto; padding: 8px 16px; font-size: 12px; font-weight: 600; <?= $filter_type === 'land' ? 'background: var(--admin-accent); color: #000;' : '' ?>">Land & Plots</a>
    <a href="properties.php?type=house" class="btn-icon" style="width: auto; padding: 8px 16px; font-size: 12px; font-weight: 600; <?= $filter_type === 'house' ? 'background: var(--admin-accent); color: #000;' : '' ?>">Residential Houses</a>
    <a href="properties.php?type=villa" class="btn-icon" style="width: auto; padding: 8px 16px; font-size: 12px; font-weight: 600; <?= $filter_type === 'villa' ? 'background: var(--admin-accent); color: #000;' : '' ?>">Luxury Villas</a>
</div>

<div class="admin-card">
    <div class="admin-table-wrap">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Photo</th>
                    <th>Property Title & Location</th>
                    <th>Type & Category</th>
                    <th>Price (KES / USD)</th>
                    <th>Status & Progress</th>
                    <th>Views</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($properties)): ?>
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 30px; color: var(--admin-muted);">No property listings found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($properties as $p): 
                        $imgs = get_property_images($p['images']);
                        $videos = get_property_videos($p['video_urls'] ?? '');
                        $thumb = $imgs[0] ?? '';
                    ?>
                        <tr>
                            <td style="width: 70px;">
                                <img src="<?= htmlspecialchars($thumb) ?>" alt="Thumb" style="width: 60px; height: 45px; object-fit: cover; border-radius: 4px; border: 1px solid var(--admin-border);">
                            </td>
                            <td>
                                <strong><a href="../property-details.php?id=<?= $p['id'] ?>" target="_blank" style="color: #fff;"><?= htmlspecialchars($p['title']) ?></a></strong><br>
                                <span style="font-size: 11px; color: var(--admin-muted);"><i class="fas fa-map-marker-alt" style="color: var(--admin-accent);"></i> <?= htmlspecialchars($p['location']) ?></span>
                                <?php if (!empty($videos)): ?>
                                    <span style="font-size: 11px; color: var(--admin-info); display: block; margin-top: 3px;"><i class="fas fa-video"></i> <?= count($videos) ?> linked video<?= count($videos) === 1 ? '' : 's' ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="status-pill info"><?= strtoupper(htmlspecialchars($p['type'])) ?></span>
                                <span style="font-size: 11px; color: var(--admin-muted); display: block; margin-top: 3px;"><?= strtoupper(htmlspecialchars($p['category'])) ?></span>
                            </td>
                            <td>
                                <strong>KSh <?= number_format($p['price_kes']) ?></strong><br>
                                <span style="font-size: 11px; color: var(--admin-muted);">$<?= number_format($p['price_usd']) ?></span>
                            </td>
                            <td>
                                <?php if ($p['status'] === 'sold'): ?>
                                    <span class="status-pill danger">SOLD</span>
                                <?php elseif ($p['status'] === 'under_construction'): ?>
                                    <span class="status-pill warning"><?= (int)$p['construction_progress'] ?>% Built</span>
                                    <span style="font-size: 10px; color: var(--admin-muted); display: block;"><?= htmlspecialchars($p['construction_stage']) ?></span>
                                <?php else: ?>
                                    <span class="status-pill success">AVAILABLE</span>
                                <?php endif; ?>
                            </td>
                            <td><?= number_format($p['views_count']) ?></td>
                            <td>
                                <div class="action-btn-group">
                                    <a href="property-edit.php?id=<?= $p['id'] ?>" class="btn-icon" title="Edit Listing"><i class="fas fa-pencil-alt"></i></a>
                                    <a href="../property-details.php?id=<?= $p['id'] ?>" target="_blank" class="btn-icon" title="View Public Page"><i class="fas fa-external-link-alt"></i></a>
                                    <a href="properties.php?delete=<?= $p['id'] ?>" class="btn-icon delete" title="Delete Listing" onclick="return confirm('Are you sure you want to permanently delete this property?')"><i class="fas fa-trash-alt"></i></a>
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
