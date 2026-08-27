<?php
/**
 * Panda Realty - Site Visits & Calendar Scheduler
 * Designed & Developed by TekTrend
 */

require_once __DIR__ . '/../config/settings.php';
require_admin();
require_capability('manage_bookings');

$conn = get_db_connection();
$msg = '';
$err = '';
$team_members = fetch_business_team_members();

// Handle Status Change
if (isset($_GET['status']) && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $st = clean_input($_GET['status']);
    mysqli_query($conn, "UPDATE site_visits SET status = '$st' WHERE id = $id");
    log_security_action('VISIT_STATUS_UPDATED', "Visit #$id marked as $st");
    $msg = "Site visit status updated to '$st'.";
}

// Handle Add New Visit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_visit'])) {
    $csrf = clean_input($_POST['csrf_token'] ?? '');
    $c_name = clean_input($_POST['client_name'] ?? '');
    $c_email = clean_input($_POST['client_email'] ?? '');
    $c_phone = clean_input($_POST['client_phone'] ?? '');
    $v_date = clean_input($_POST['visit_date'] ?? '');
    $v_time = clean_input($_POST['visit_time'] ?? '10:00:00');
    $prop_id = (int)($_POST['property_id'] ?? 0);
    $booking_type = clean_input($_POST['booking_type'] ?? 'site_visit');
    $consultation_mode = clean_input($_POST['consultation_mode'] ?? 'in_person');
    $preferred_contact = clean_input($_POST['preferred_contact'] ?? 'whatsapp');
    $assigned_to = (int)($_POST['assigned_to'] ?? 0);
    $follow_up_date = clean_input($_POST['follow_up_date'] ?? '');
    $whatsapp_opt_in = isset($_POST['whatsapp_opt_in']) ? 1 : 0;
    $notes = clean_input($_POST['notes'] ?? '');

    if (!verify_csrf_token($csrf)) {
        $err = "Security session expired. Please refresh and try again.";
    } else {
        $booking_type = in_array($booking_type, ['site_visit', 'consultation'], true) ? $booking_type : 'site_visit';
        $consultation_mode = in_array($consultation_mode, ['phone', 'whatsapp', 'zoom', 'in_person'], true) ? $consultation_mode : 'in_person';
        $preferred_contact = in_array($preferred_contact, ['phone', 'whatsapp', 'email'], true) ? $preferred_contact : 'whatsapp';
        $prop_sql = $prop_id > 0 ? $prop_id : "NULL";
        $assigned_sql = $assigned_to > 0 ? $assigned_to : "NULL";
        $follow_up_sql = $follow_up_date !== '' ? "'" . db_escape($follow_up_date) . "'" : "NULL";
        $ins = "INSERT INTO site_visits (property_id, client_name, client_email, client_phone, visit_date, visit_time, booking_type, consultation_mode, preferred_contact, whatsapp_opt_in, status, notes, follow_up_date, assigned_to) 
                VALUES ($prop_sql, '$c_name', '$c_email', '$c_phone', '$v_date', '$v_time', '$booking_type', '$consultation_mode', '$preferred_contact', $whatsapp_opt_in, 'confirmed', '$notes', $follow_up_sql, $assigned_sql)";
        if (mysqli_query($conn, $ins)) {
            log_security_action('NEW_TOUR_SCHEDULED', "Admin scheduled $booking_type for $c_name on $v_date");
            $msg = "New booking saved successfully.";
        } else {
            $err = "Unable to save the booking workflow.";
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_visit'])) {
    $csrf = clean_input($_POST['csrf_token'] ?? '');
    $visit_id = (int)($_POST['visit_id'] ?? 0);
    $status = clean_input($_POST['status'] ?? 'pending');
    $booking_type = clean_input($_POST['booking_type'] ?? 'site_visit');
    $consultation_mode = clean_input($_POST['consultation_mode'] ?? 'in_person');
    $preferred_contact = clean_input($_POST['preferred_contact'] ?? 'whatsapp');
    $assigned_to = (int)($_POST['assigned_to'] ?? 0);
    $follow_up_date = clean_input($_POST['follow_up_date'] ?? '');

    if (!verify_csrf_token($csrf)) {
        $err = "Security session expired. Please refresh and try again.";
    } elseif ($visit_id > 0) {
        $status = in_array($status, ['pending', 'confirmed', 'completed', 'cancelled'], true) ? $status : 'pending';
        $booking_type = in_array($booking_type, ['site_visit', 'consultation'], true) ? $booking_type : 'site_visit';
        $consultation_mode = in_array($consultation_mode, ['phone', 'whatsapp', 'zoom', 'in_person'], true) ? $consultation_mode : 'in_person';
        $preferred_contact = in_array($preferred_contact, ['phone', 'whatsapp', 'email'], true) ? $preferred_contact : 'whatsapp';
        $assigned_sql = $assigned_to > 0 ? $assigned_to : "NULL";
        $follow_up_sql = $follow_up_date !== '' ? "'" . db_escape($follow_up_date) . "'" : "NULL";
        $sql = "UPDATE site_visits
                SET status = '$status',
                    booking_type = '$booking_type',
                    consultation_mode = '$consultation_mode',
                    preferred_contact = '$preferred_contact',
                    assigned_to = $assigned_sql,
                    follow_up_date = $follow_up_sql
                WHERE id = $visit_id";
        if (mysqli_query($conn, $sql)) {
            log_security_action('BOOKING_WORKFLOW_UPDATED', "Booking #$visit_id workflow updated to $status");
            $msg = "Booking workflow updated.";
        } else {
            $err = "Unable to update booking workflow.";
        }
    }
}

// Fetch all visits
$res = mysqli_query($conn, "SELECT sv.*, p.title as property_title, p.location as property_location, u.name AS assigned_name
    FROM site_visits sv
    LEFT JOIN properties p ON sv.property_id = p.id
    LEFT JOIN users u ON sv.assigned_to = u.id
    ORDER BY sv.visit_date ASC, sv.visit_time ASC");
$visits = [];
if ($res) {
    while ($row = mysqli_fetch_assoc($res)) {
        $visits[] = $row;
    }
}

$admin_page_title = "Site Tours & Calendar Scheduler";
require_once __DIR__ . '/includes/admin-header.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; flex-wrap: wrap; gap: 15px;">
    <div>
        <h3 style="font-size: 20px; font-weight: 700; color: #fff;">Site Visits & Inspection Calendar</h3>
        <p style="color: var(--admin-muted); font-size: 13px;">Manage client site tours, Elgon View mansion inspections, and Annex plot visits.</p>
    </div>

    <button type="button" class="btn" onclick="openModal('addVisitModal')" style="background: var(--admin-accent); color: #000; font-weight: 700; padding: 12px 20px; border-radius: 6px; font-size: 13px; border: none; cursor: pointer;">
        <i class="fas fa-calendar-plus"></i> Schedule New Site Visit
    </button>
</div>

<?php if (!empty($msg)): ?>
    <div class="alert-box alert-success"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($msg) ?></div>
<?php endif; ?>
<?php if (!empty($err)): ?>
    <div class="alert-box alert-danger"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($err) ?></div>
<?php endif; ?>

<!-- Interactive Visual Schedule Grid -->
<div class="admin-card">
    <div class="admin-card-header">
        <h3><i class="fas fa-calendar-alt"></i> Scheduled Appointments</h3>
    </div>

    <div class="admin-table-wrap">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Date & Time</th>
                    <th>Client Name</th>
                    <th>Property / Destination</th>
                    <th>Channel</th>
                    <th>Workflow</th>
                    <th>Status</th>
                    <th>Notes</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($visits)): ?>
                    <tr><td colspan="8" style="text-align: center; padding: 30px; color: var(--admin-muted);">No site tours booked yet.</td></tr>
                <?php else: ?>
                    <?php foreach ($visits as $v): ?>
                        <tr>
                            <td>
                                <strong><?= date('M d, Y', strtotime($v['visit_date'])) ?></strong><br>
                                <span style="font-size: 11px; color: var(--admin-accent); font-weight: 600;"><i class="fas fa-clock"></i> <?= date('h:i A', strtotime($v['visit_time'])) ?></span>
                            </td>
                            <td><strong><?= htmlspecialchars($v['client_name']) ?></strong></td>
                            <td>
                                <strong><?= htmlspecialchars($v['property_title'] ?: 'General Land Tour') ?></strong><br>
                                <span style="font-size: 11px; color: var(--admin-muted);"><?= htmlspecialchars($v['property_location'] ?: 'Eldoret Region') ?></span>
                            </td>
                            <td>
                                <div>
                                    <a href="<?= htmlspecialchars(build_whatsapp_link($v['client_phone'], 'Hello ' . $v['client_name'] . ', this is Panda Realty following up on your booking request.')) ?>" target="_blank" style="color: #25D366; font-weight: 600;">
                                        <i class="fab fa-whatsapp"></i> <?= htmlspecialchars($v['client_phone']) ?>
                                    </a>
                                </div>
                                <div style="font-size: 11px; color: var(--admin-muted); margin-top: 4px;"><?= htmlspecialchars($v['client_email']) ?></div>
                                <div style="font-size: 11px; color: var(--admin-accent); text-transform: uppercase; margin-top: 4px;"><?= htmlspecialchars(str_replace('_', ' ', $v['preferred_contact'] ?? 'whatsapp')) ?></div>
                            </td>
                            <td style="min-width: 230px;">
                                <div style="font-size: 11px; color: var(--admin-muted); margin-bottom: 10px;">
                                    <strong style="color: #fff; text-transform: capitalize;"><?= htmlspecialchars(str_replace('_', ' ', $v['booking_type'] ?? 'site_visit')) ?></strong>
                                    <?php if (($v['booking_type'] ?? '') === 'consultation'): ?>
                                        <span> via <?= htmlspecialchars(str_replace('_', ' ', $v['consultation_mode'] ?? 'phone')) ?></span>
                                    <?php endif; ?>
                                    <br>
                                    Owner: <?= htmlspecialchars($v['assigned_name'] ?: 'Unassigned') ?><br>
                                    Follow-up: <?= !empty($v['follow_up_date']) ? date('M d, Y', strtotime($v['follow_up_date'])) : 'Not set' ?>
                                </div>
                                <form action="bookings.php" method="POST">
                                    <input type="hidden" name="csrf_token" value="<?= get_csrf_token() ?>">
                                    <input type="hidden" name="update_visit" value="1">
                                    <input type="hidden" name="visit_id" value="<?= (int)$v['id'] ?>">
                                    <div style="display: grid; gap: 8px;">
                                        <select name="booking_type">
                                            <option value="site_visit" <?= ($v['booking_type'] ?? 'site_visit') === 'site_visit' ? 'selected' : '' ?>>Site Visit</option>
                                            <option value="consultation" <?= ($v['booking_type'] ?? '') === 'consultation' ? 'selected' : '' ?>>Consultation</option>
                                        </select>
                                        <select name="consultation_mode">
                                            <option value="in_person" <?= ($v['consultation_mode'] ?? 'in_person') === 'in_person' ? 'selected' : '' ?>>In Person</option>
                                            <option value="phone" <?= ($v['consultation_mode'] ?? '') === 'phone' ? 'selected' : '' ?>>Phone</option>
                                            <option value="whatsapp" <?= ($v['consultation_mode'] ?? '') === 'whatsapp' ? 'selected' : '' ?>>WhatsApp</option>
                                            <option value="zoom" <?= ($v['consultation_mode'] ?? '') === 'zoom' ? 'selected' : '' ?>>Zoom</option>
                                        </select>
                                        <select name="preferred_contact">
                                            <option value="whatsapp" <?= ($v['preferred_contact'] ?? 'whatsapp') === 'whatsapp' ? 'selected' : '' ?>>WhatsApp</option>
                                            <option value="phone" <?= ($v['preferred_contact'] ?? '') === 'phone' ? 'selected' : '' ?>>Phone</option>
                                            <option value="email" <?= ($v['preferred_contact'] ?? '') === 'email' ? 'selected' : '' ?>>Email</option>
                                        </select>
                                        <select name="assigned_to">
                                            <option value="0">Unassigned</option>
                                            <?php foreach ($team_members as $member): ?>
                                                <option value="<?= (int)$member['id'] ?>" <?= (int)($v['assigned_to'] ?? 0) === (int)$member['id'] ? 'selected' : '' ?>><?= htmlspecialchars($member['name']) ?> (<?= htmlspecialchars(get_role_label($member['role'])) ?>)</option>
                                            <?php endforeach; ?>
                                        </select>
                                        <input type="date" name="follow_up_date" value="<?= htmlspecialchars($v['follow_up_date'] ?? '') ?>">
                                        <select name="status">
                                            <option value="pending" <?= $v['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                                            <option value="confirmed" <?= $v['status'] === 'confirmed' ? 'selected' : '' ?>>Confirmed</option>
                                            <option value="completed" <?= $v['status'] === 'completed' ? 'selected' : '' ?>>Completed</option>
                                            <option value="cancelled" <?= $v['status'] === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                        </select>
                                        <button type="submit" class="btn-icon" style="width: auto; padding: 8px 12px; color: var(--admin-text); justify-content: center;">
                                            <i class="fas fa-save"></i> Save Workflow
                                        </button>
                                    </div>
                                </form>
                            </td>
                            <td>
                                <span class="status-pill <?= $v['status'] === 'confirmed' ? 'success' : ($v['status'] === 'completed' ? 'info' : 'warning') ?>">
                                    <?= strtoupper($v['status']) ?>
                                </span>
                            </td>
                            <td style="font-size: 12px; color: var(--admin-muted); max-width: 200px;">
                                <?= htmlspecialchars($v['notes'] ?? 'Standard site pickup') ?>
                            </td>
                            <td>
                                <div class="action-btn-group">
                                    <a href="bookings.php?status=confirmed&id=<?= $v['id'] ?>" class="btn-icon" title="Confirm Tour"><i class="fas fa-check"></i></a>
                                    <a href="bookings.php?status=completed&id=<?= $v['id'] ?>" class="btn-icon" title="Mark Completed"><i class="fas fa-flag-checkered"></i></a>
                                    <a href="bookings.php?status=cancelled&id=<?= $v['id'] ?>" class="btn-icon delete" title="Cancel Tour"><i class="fas fa-times"></i></a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add Visit Modal -->
<div id="addVisitModal" class="modal">
    <div class="modal-content">
        <button type="button" class="modal-close" onclick="closeModal('addVisitModal')"><i class="fas fa-times"></i></button>
        <h3 class="font-serif" style="font-size: 22px; margin-bottom: 20px; color: var(--admin-text);">Schedule New Site Visit</h3>

        <form action="bookings.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?= get_csrf_token() ?>">
            <input type="hidden" name="add_visit" value="1">

            <div class="admin-form-group">
                <label>Client Full Name *</label>
                <input type="text" name="client_name" placeholder="e.g. Eng. David Mwangi" required>
            </div>

            <div class="admin-form-group">
                <label>Client Phone *</label>
                <input type="tel" name="client_phone" placeholder="0708 289 852" required>
            </div>

            <div class="admin-form-group">
                <label>Client Email</label>
                <input type="email" name="client_email" placeholder="client@example.com">
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <div class="admin-form-group">
                    <label>Visit Date *</label>
                    <input type="date" name="visit_date" min="<?= date('Y-m-d') ?>" required>
                </div>
                <div class="admin-form-group">
                    <label>Time *</label>
                    <input type="time" name="visit_time" value="10:00" required>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <div class="admin-form-group">
                    <label>Booking Type</label>
                    <select name="booking_type">
                        <option value="site_visit">Site Visit</option>
                        <option value="consultation">Consultation</option>
                    </select>
                </div>
                <div class="admin-form-group">
                    <label>Consultation Mode</label>
                    <select name="consultation_mode">
                        <option value="in_person">In Person</option>
                        <option value="phone">Phone</option>
                        <option value="whatsapp">WhatsApp</option>
                        <option value="zoom">Zoom</option>
                    </select>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <div class="admin-form-group">
                    <label>Preferred Contact</label>
                    <select name="preferred_contact">
                        <option value="whatsapp">WhatsApp</option>
                        <option value="phone">Phone</option>
                        <option value="email">Email</option>
                    </select>
                </div>
                <div class="admin-form-group">
                    <label>Assign Owner</label>
                    <select name="assigned_to">
                        <option value="0">Unassigned</option>
                        <?php foreach ($team_members as $member): ?>
                            <option value="<?= (int)$member['id'] ?>"><?= htmlspecialchars($member['name']) ?> (<?= htmlspecialchars(get_role_label($member['role'])) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="admin-form-group">
                <label>Property Destination</label>
                <select name="property_id">
                    <option value="">General Eldoret Land Tour</option>
                    <?php
                    $p_list = mysqli_query($conn, "SELECT id, title, location FROM properties");
                    if ($p_list) {
                        while ($pl = mysqli_fetch_assoc($p_list)) {
                            echo '<option value="' . $pl['id'] . '">' . htmlspecialchars($pl['title']) . ' (' . htmlspecialchars($pl['location']) . ')</option>';
                        }
                    }
                    ?>
                </select>
            </div>

            <div class="admin-form-group">
                <label>Notes & Pick-Up Location</label>
                <textarea name="notes" placeholder="Pick-up at Eldoret CBD office at 10:00 AM..."></textarea>
            </div>

            <div class="admin-form-group">
                <label>Follow-Up Date</label>
                <input type="date" name="follow_up_date">
            </div>

            <label style="display: flex; gap: 10px; align-items: center; margin-bottom: 18px; font-size: 13px; color: var(--admin-muted);">
                <input type="checkbox" name="whatsapp_opt_in" value="1" checked>
                <span>Client is open to WhatsApp confirmations.</span>
            </label>

            <button type="submit" class="btn" style="width: 100%; background: var(--admin-accent); color: #000; font-weight: 700; padding: 14px; border-radius: 6px; border: none; cursor: pointer;">
                <i class="fas fa-calendar-check"></i> Save Scheduled Tour
            </button>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
