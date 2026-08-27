<?php
/**
 * Panda Realty - Inquiries & Lead Management
 * Designed & Developed by TekTrend
 */

require_once __DIR__ . '/../config/settings.php';
require_admin();
require_capability('manage_inquiries');

$conn = get_db_connection();
$msg = '';
$err = '';
$team_members = fetch_business_team_members();

// Handle Status Change
if (isset($_GET['status']) && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $status = clean_input($_GET['status']);
    mysqli_query($conn, "UPDATE inquiries SET status = '$status' WHERE id = $id");
    $msg = "Inquiry status updated to '$status'.";
}

// Handle Delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    mysqli_query($conn, "DELETE FROM inquiries WHERE id = $id");
    $msg = "Inquiry deleted.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_inquiry'])) {
    $csrf = clean_input($_POST['csrf_token'] ?? '');
    $id = (int)($_POST['inquiry_id'] ?? 0);
    $status = clean_input($_POST['status'] ?? 'new');
    $client_stage = clean_input($_POST['client_stage'] ?? 'new');
    $preferred_contact = clean_input($_POST['preferred_contact'] ?? 'whatsapp');
    $assigned_to = (int)($_POST['assigned_to'] ?? 0);
    $follow_up_date = clean_input($_POST['follow_up_date'] ?? '');

    if (!verify_csrf_token($csrf)) {
        $err = "Security session expired. Please refresh and try again.";
    } elseif ($id > 0) {
        $status = in_array($status, ['new', 'in_progress', 'resolved', 'archived'], true) ? $status : 'new';
        $client_stage = in_array($client_stage, ['new', 'qualified', 'consultation', 'site_visit', 'negotiation', 'won', 'lost'], true) ? $client_stage : 'new';
        $preferred_contact = in_array($preferred_contact, ['phone', 'whatsapp', 'email'], true) ? $preferred_contact : 'whatsapp';
        $assigned_sql = $assigned_to > 0 ? $assigned_to : "NULL";
        $follow_up_sql = $follow_up_date !== '' ? "'" . db_escape($follow_up_date) . "'" : "NULL";
        $sql = "UPDATE inquiries
                SET status = '$status',
                    client_stage = '$client_stage',
                    preferred_contact = '$preferred_contact',
                    assigned_to = $assigned_sql,
                    follow_up_date = $follow_up_sql
                WHERE id = $id";
        if (mysqli_query($conn, $sql)) {
            $msg = "Inquiry workflow updated.";
        } else {
            $err = "Unable to update inquiry workflow.";
        }
    }
}

// Fetch Inquiries
$res = mysqli_query($conn, "SELECT i.*, p.title as property_title, u.name AS assigned_name
    FROM inquiries i
    LEFT JOIN properties p ON i.property_id = p.id
    LEFT JOIN users u ON i.assigned_to = u.id
    ORDER BY i.id DESC");
$inquiries = [];
if ($res) {
    while ($row = mysqli_fetch_assoc($res)) {
        $inquiries[] = $row;
    }
}

$admin_page_title = "Leads & Contact Inquiries";
require_once __DIR__ . '/includes/admin-header.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
    <div>
        <h3 style="font-size: 20px; font-weight: 700; color: #fff;">Inbound Leads & Contact Form Submissions</h3>
        <p style="color: var(--admin-muted); font-size: 13px;">Manage client messages, studio apartment queries, and consultation requests for Perpetuah.</p>
    </div>
</div>

<?php if (!empty($msg)): ?>
    <div class="alert-box alert-success"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($msg) ?></div>
<?php endif; ?>
<?php if (!empty($err)): ?>
    <div class="alert-box alert-danger"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($err) ?></div>
<?php endif; ?>

<div class="admin-card">
    <div class="admin-table-wrap">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Client Name</th>
                    <th>Contact Info</th>
                    <th>Subject / Type</th>
                    <th>Message Details</th>
                    <th>Workflow</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($inquiries)): ?>
                    <tr><td colspan="8" style="text-align: center; padding: 30px; color: var(--admin-muted);">No inquiries found.</td></tr>
                <?php else: ?>
                    <?php foreach ($inquiries as $inq): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($inq['name']) ?></strong></td>
                            <td>
                                <div><i class="fas fa-phone-alt" style="color: var(--admin-accent); font-size: 11px;"></i> <?= htmlspecialchars($inq['phone']) ?></div>
                                <div style="font-size: 11px; color: var(--admin-muted);"><?= htmlspecialchars($inq['email']) ?></div>
                            </td>
                            <td>
                                <strong><?= htmlspecialchars($inq['subject']) ?></strong>
                                <span class="status-pill info" style="display: block; width: fit-content; margin-top: 4px;"><?= htmlspecialchars($inq['inquiry_type']) ?></span>
                            </td>
                            <td style="max-width: 250px;">
                                <span style="font-size: 12px; color: #cbd5e1;"><?= nl2br(htmlspecialchars($inq['message'])) ?></span>
                                <?php if (!empty($inq['budget_range'])): ?>
                                    <div style="font-size: 11px; color: var(--admin-accent); margin-top: 6px;">Budget: <?= htmlspecialchars($inq['budget_range']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td style="min-width: 230px;">
                                <div style="font-size: 11px; color: var(--admin-muted); margin-bottom: 10px;">
                                    Stage: <strong style="color: #fff;"><?= htmlspecialchars(str_replace('_', ' ', $inq['client_stage'] ?? 'new')) ?></strong><br>
                                    Owner: <?= htmlspecialchars($inq['assigned_name'] ?: 'Unassigned') ?><br>
                                    Follow-up: <?= !empty($inq['follow_up_date']) ? date('M d, Y', strtotime($inq['follow_up_date'])) : 'Not set' ?><br>
                                    Preferred: <?= htmlspecialchars(str_replace('_', ' ', $inq['preferred_contact'] ?? 'whatsapp')) ?>
                                </div>
                                <form action="inquiries.php" method="POST">
                                    <input type="hidden" name="csrf_token" value="<?= get_csrf_token() ?>">
                                    <input type="hidden" name="update_inquiry" value="1">
                                    <input type="hidden" name="inquiry_id" value="<?= (int)$inq['id'] ?>">
                                    <div style="display: grid; gap: 8px;">
                                        <select name="client_stage">
                                            <option value="new" <?= ($inq['client_stage'] ?? 'new') === 'new' ? 'selected' : '' ?>>New</option>
                                            <option value="qualified" <?= ($inq['client_stage'] ?? '') === 'qualified' ? 'selected' : '' ?>>Qualified</option>
                                            <option value="consultation" <?= ($inq['client_stage'] ?? '') === 'consultation' ? 'selected' : '' ?>>Consultation</option>
                                            <option value="site_visit" <?= ($inq['client_stage'] ?? '') === 'site_visit' ? 'selected' : '' ?>>Site Visit</option>
                                            <option value="negotiation" <?= ($inq['client_stage'] ?? '') === 'negotiation' ? 'selected' : '' ?>>Negotiation</option>
                                            <option value="won" <?= ($inq['client_stage'] ?? '') === 'won' ? 'selected' : '' ?>>Won</option>
                                            <option value="lost" <?= ($inq['client_stage'] ?? '') === 'lost' ? 'selected' : '' ?>>Lost</option>
                                        </select>
                                        <select name="preferred_contact">
                                            <option value="whatsapp" <?= ($inq['preferred_contact'] ?? 'whatsapp') === 'whatsapp' ? 'selected' : '' ?>>WhatsApp</option>
                                            <option value="phone" <?= ($inq['preferred_contact'] ?? '') === 'phone' ? 'selected' : '' ?>>Phone</option>
                                            <option value="email" <?= ($inq['preferred_contact'] ?? '') === 'email' ? 'selected' : '' ?>>Email</option>
                                        </select>
                                        <select name="assigned_to">
                                            <option value="0">Unassigned</option>
                                            <?php foreach ($team_members as $member): ?>
                                                <option value="<?= (int)$member['id'] ?>" <?= (int)($inq['assigned_to'] ?? 0) === (int)$member['id'] ? 'selected' : '' ?>><?= htmlspecialchars($member['name']) ?> (<?= htmlspecialchars(get_role_label($member['role'])) ?>)</option>
                                            <?php endforeach; ?>
                                        </select>
                                        <input type="date" name="follow_up_date" value="<?= htmlspecialchars($inq['follow_up_date'] ?? '') ?>">
                                        <select name="status">
                                            <option value="new" <?= $inq['status'] === 'new' ? 'selected' : '' ?>>New</option>
                                            <option value="in_progress" <?= $inq['status'] === 'in_progress' ? 'selected' : '' ?>>In Progress</option>
                                            <option value="resolved" <?= $inq['status'] === 'resolved' ? 'selected' : '' ?>>Resolved</option>
                                            <option value="archived" <?= $inq['status'] === 'archived' ? 'selected' : '' ?>>Archived</option>
                                        </select>
                                        <button type="submit" class="btn-icon" style="width: auto; padding: 8px 12px; color: var(--admin-text); justify-content: center;">
                                            <i class="fas fa-save"></i> Save Workflow
                                        </button>
                                    </div>
                                </form>
                            </td>
                            <td>
                                <span class="status-pill <?= $inq['status'] === 'resolved' ? 'success' : ($inq['status'] === 'in_progress' ? 'warning' : 'danger') ?>">
                                    <?= strtoupper($inq['status']) ?>
                                </span>
                            </td>
                            <td><?= date('M d, Y', strtotime($inq['created_at'])) ?></td>
                            <td>
                                <div class="action-btn-group">
                                    <?php if (user_can('manage_sales_pipeline')): ?>
                                        <a href="sales-pipeline.php?from_inquiry=<?= $inq['id'] ?>" class="btn-icon" title="Move To Sales Pipeline"><i class="fas fa-funnel-dollar"></i></a>
                                    <?php endif; ?>
                                    <a href="<?= htmlspecialchars(build_whatsapp_link($inq['phone'], 'Hello ' . $inq['name'] . ', this is Perpetuah from Panda Realty following up on your request.')) ?>" target="_blank" class="btn-icon" title="Reply via WhatsApp" style="color: #25D366;"><i class="fab fa-whatsapp"></i></a>
                                    <a href="inquiries.php?status=in_progress&id=<?= $inq['id'] ?>" class="btn-icon" title="Mark In Progress"><i class="fas fa-spinner"></i></a>
                                    <a href="inquiries.php?status=resolved&id=<?= $inq['id'] ?>" class="btn-icon" title="Mark Resolved"><i class="fas fa-check"></i></a>
                                    <a href="inquiries.php?delete=<?= $inq['id'] ?>" class="btn-icon delete" title="Delete" onclick="return confirm('Delete this inquiry?')"><i class="fas fa-trash-alt"></i></a>
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
