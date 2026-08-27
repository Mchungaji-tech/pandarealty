<?php
/**
 * Panda Realty - Executive Real Estate CRM & Deal Pipeline with Property Video
 * Designed & Developed by TekTrend
 */

require_once __DIR__ . '/../config/settings.php';
require_admin();
require_capability('manage_sales_pipeline');

$conn = get_db_connection();
$msg = '';
$err = '';
$today = date('Y-m-d');

// Handle Stage Quick Update
if (isset($_POST['update_deal_stage'])) {
    $csrf = clean_input($_POST['csrf_token'] ?? '');
    if (verify_csrf_token($csrf)) {
        $deal_id = (int)($_POST['deal_id'] ?? 0);
        $new_stage = clean_input($_POST['stage'] ?? 'new_lead');
        $notes = clean_input($_POST['notes'] ?? '');
        $stage_safe = db_escape($new_stage);
        $notes_safe = db_escape($notes);

        mysqli_query($conn, "UPDATE crm_deals SET stage = '$stage_safe', notes = IF('$notes_safe' != '', CONCAT(COALESCE(notes, ''), '\n[', NOW(), '] ', '$notes_safe'), notes), updated_at = NOW() WHERE id = $deal_id");
        log_security_action('CRM_DEAL_STAGE_UPDATED', "Updated CRM deal #$deal_id to stage '$new_stage'");
        $msg = "Deal stage updated successfully!";
    }
}

// Handle Add Lead / Deal
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_lead'])) {
    $csrf = clean_input($_POST['csrf_token'] ?? '');
    if (!verify_csrf_token($csrf)) {
        $err = "Security session expired. Please try again.";
    } else {
        $c_name = clean_input($_POST['client_name'] ?? '');
        $c_phone = clean_input($_POST['client_phone'] ?? '');
        $c_email = clean_input($_POST['client_email'] ?? '');
        $prop_id = (int)($_POST['property_id'] ?? 0);
        $stage = clean_input($_POST['stage'] ?? 'new_lead');
        $deal_val = (float)($_POST['deal_value'] ?? 0);
        $agent = clean_input($_POST['assigned_agent'] ?? 'Perpetuah Chepchirchir');
        $source = clean_input($_POST['source'] ?? 'Direct WhatsApp');
        $notes = clean_input($_POST['notes'] ?? '');
        $follow_up = clean_input($_POST['next_follow_up'] ?? '');

        if (empty($c_name) || empty($c_phone)) {
            $err = "Please provide Client Name and Phone number.";
        } else {
            $prop_title = '';
            if ($prop_id > 0) {
                $pr = mysqli_query($conn, "SELECT title FROM properties WHERE id = $prop_id LIMIT 1");
                if ($pr && $prow = mysqli_fetch_assoc($pr)) {
                    $prop_title = db_escape($prow['title']);
                }
            }
            $prop_sql = $prop_id > 0 ? $prop_id : "NULL";
            $f_sql = !empty($follow_up) ? "'$follow_up'" : "NULL";

            $sql = "INSERT INTO crm_deals (client_name, client_email, client_phone, property_id, property_name, stage, deal_value, currency, assigned_agent, source, notes, next_follow_up)
                    VALUES ('$c_name', '$c_email', '$c_phone', $prop_sql, '$prop_title', '$stage', $deal_val, 'KES', '$agent', '$source', '$notes', $f_sql)";
            
            if (mysqli_query($conn, $sql)) {
                $new_id = mysqli_insert_id($conn);
                log_security_action('CRM_LEAD_CREATED', "Added CRM deal for $c_name (ID #$new_id)");
                $msg = "New client opportunity added for $c_name!";
            } else {
                $err = "Database Error: " . mysqli_error($conn);
            }
        }
    }
}

// Stage definitions
$stages_def = [
    'new_lead' => ['label' => 'New Inquiries', 'color' => '#3b82f6', 'icon' => 'fa-inbox'],
    'contacted' => ['label' => 'Contacted / Qualified', 'color' => '#8b5cf6', 'icon' => 'fa-phone-volume'],
    'site_visit' => ['label' => 'Site Visit Scheduled', 'color' => '#f59e0b', 'icon' => 'fa-car'],
    'negotiation' => ['label' => 'In Negotiation', 'color' => '#ec4899', 'icon' => 'fa-handshake'],
    'won' => ['label' => 'Closed / Won (Sold)', 'color' => '#10b981', 'icon' => 'fa-trophy'],
    'lost' => ['label' => 'Lost / Inactive', 'color' => '#64748b', 'icon' => 'fa-times-circle']
];

// Fetch Deals with attached Property Details & Video
$res_deals = mysqli_query($conn, "SELECT d.*, p.title as prop_title, p.location as prop_location, p.price_kes as prop_price, p.images as prop_images, p.video_urls as prop_videos 
                                   FROM crm_deals d 
                                   LEFT JOIN properties p ON d.property_id = p.id 
                                   ORDER BY d.id DESC");
$all_deals = [];
if ($res_deals) {
    while ($row = mysqli_fetch_assoc($res_deals)) {
        $row['parsed_images'] = get_property_images($row['prop_images'] ?? '');
        $row['parsed_videos'] = get_property_videos($row['prop_videos'] ?? '');
        $all_deals[] = $row;
    }
}

$grouped = [];
$total_pipeline_val = 0;
$won_count = 0;
$won_value = 0;

foreach ($stages_def as $k => $st) {
    $grouped[$k] = [];
}

foreach ($all_deals as $d) {
    $stg = $d['stage'] ?? 'new_lead';
    if (!isset($grouped[$stg])) $grouped[$stg] = [];
    $grouped[$stg][] = $d;

    if ($stg !== 'lost') {
        $total_pipeline_val += (float)$d['deal_value'];
    }
    if ($stg === 'won') {
        $won_count++;
        $won_value += (float)$d['deal_value'];
    }
}

$admin_page_title = "CRM & Deal Pipeline";
require_once __DIR__ . '/includes/admin-header.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; flex-wrap: wrap; gap: 15px;">
    <div>
        <h3 style="font-size: 24px; font-weight: 700; color: var(--admin-text); margin-bottom: 4px;">
            <i class="fas fa-funnel-dollar" style="color: var(--admin-accent);"></i> Real Estate CRM & Deal Pipeline
        </h3>
        <p style="font-size: 13px; color: var(--admin-muted);">
            Manage Eldoret land buyers, studio apartment inquiries, and review attached property videos.
        </p>
    </div>

    <button type="button" class="btn btn-primary" onclick="document.getElementById('addLeadModal').style.display='flex'" style="padding: 12px 22px; font-weight: 700; border-radius: 8px; cursor: pointer; display: flex; align-items: center; gap: 8px;">
        <i class="fas fa-plus-circle"></i> Add Opportunity
    </button>
</div>

<?php if (!empty($msg)): ?>
    <div class="alert-box alert-success"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($msg) ?></div>
<?php endif; ?>

<?php if (!empty($err)): ?>
    <div class="alert-box alert-danger"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($err) ?></div>
<?php endif; ?>

<!-- CRM Metric Strip -->
<div class="stat-grid" style="margin-bottom: 30px;">
    <div class="stat-card">
        <div class="stat-icon" style="background: rgba(195, 154, 77, 0.12); color: var(--admin-accent);"><i class="fas fa-users"></i></div>
        <div class="stat-details">
            <h4><?= count($all_deals) ?></h4>
            <p>Total Pipeline Deals</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon info"><i class="fas fa-chart-line"></i></div>
        <div class="stat-details">
            <h4>KES <?= number_format($total_pipeline_val) ?></h4>
            <p>Active Pipeline Value</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon success"><i class="fas fa-trophy"></i></div>
        <div class="stat-details">
            <h4>KES <?= number_format($won_value) ?></h4>
            <p>Won Revenue (<?= $won_count ?> Closed)</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon warning"><i class="fas fa-calendar-check"></i></div>
        <div class="stat-details">
            <h4><?= count($grouped['site_visit']) ?></h4>
            <p>Upcoming Site Tours</p>
        </div>
    </div>
</div>

<!-- CRM Kanban Pipeline Board -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(290px, 1fr)); gap: 18px; margin-bottom: 30px; align-items: start;">
    <?php foreach ($stages_def as $stage_key => $st_info): ?>
        <div style="background: #f8fafc; border: 1px solid var(--admin-border); border-radius: 10px; overflow: hidden; box-shadow: 0 2px 4px rgba(0,0,0,0.02);">
            
            <!-- Stage Header -->
            <div style="padding: 14px 16px; background: #ffffff; border-bottom: 1px solid var(--admin-border); border-top: 3.5px solid <?= $st_info['color'] ?>; display: flex; justify-content: space-between; align-items: center;">
                <span style="font-weight: 700; font-size: 13px; color: var(--admin-text); display: flex; align-items: center; gap: 8px;">
                    <i class="fas <?= $st_info['icon'] ?>" style="color: <?= $st_info['color'] ?>;"></i>
                    <?= htmlspecialchars($st_info['label']) ?>
                </span>
                <span style="background: #f1f5f9; font-size: 11px; font-weight: 700; padding: 2px 8px; border-radius: 12px; color: var(--admin-muted);">
                    <?= count($grouped[$stage_key]) ?>
                </span>
            </div>

            <!-- Stage Cards Stack -->
            <div style="padding: 12px; display: flex; flex-direction: column; gap: 12px; min-height: 140px;">
                <?php if (empty($grouped[$stage_key])): ?>
                    <div style="text-align: center; color: var(--admin-muted); font-size: 12px; padding: 25px;">No active deals</div>
                <?php else: ?>
                    <?php foreach ($grouped[$stage_key] as $deal): ?>
                        <div style="background: #ffffff; border: 1px solid var(--admin-border); border-radius: 8px; padding: 14px; box-shadow: 0 1px 4px rgba(0,0,0,0.04); transition: transform 0.15s;">
                            
                            <!-- Client & Value Header -->
                            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 8px;">
                                <div>
                                    <strong style="font-size: 14px; color: var(--admin-text); display: block;"><?= htmlspecialchars($deal['client_name']) ?></strong>
                                    <span style="font-size: 11px; color: var(--admin-muted);"><?= htmlspecialchars($deal['client_phone']) ?></span>
                                </div>
                                <span style="font-weight: 800; font-size: 13px; color: var(--admin-accent);">
                                    KES <?= number_format($deal['deal_value']) ?>
                                </span>
                            </div>

                            <!-- Associated Property Badge -->
                            <?php if (!empty($deal['property_name']) || !empty($deal['prop_title'])): ?>
                                <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 6px 10px; margin-bottom: 8px; font-size: 12px; color: #334155;">
                                    <i class="fas fa-building" style="color: var(--admin-accent); font-size: 11px;"></i>
                                    <strong><?= htmlspecialchars($deal['prop_title'] ?: $deal['property_name']) ?></strong>
                                </div>
                            <?php endif; ?>

                            <!-- Property Video Attachment Indicator -->
                            <?php if (!empty($deal['parsed_videos'])): ?>
                                <div style="margin-bottom: 8px;">
                                    <a href="#deal-video-<?= $deal['id'] ?>" onclick="openDealVideoModal('<?= htmlspecialchars($deal['parsed_videos'][0]) ?>', '<?= htmlspecialchars(addslashes($deal['client_name'])) ?>', '<?= htmlspecialchars(addslashes($deal['prop_title'] ?: $deal['property_name'])) ?>')" style="display: inline-flex; align-items: center; gap: 6px; font-size: 11px; font-weight: 700; color: #ef4444; background: rgba(239, 68, 68, 0.08); padding: 4px 10px; border-radius: 4px; text-decoration: none;">
                                        <i class="fab fa-youtube"></i> Watch Property Tour Video
                                    </a>
                                </div>
                            <?php endif; ?>

                            <!-- Action Quick Buttons -->
                            <div style="display: flex; gap: 6px; align-items: center; margin-top: 10px; padding-top: 10px; border-top: 1px solid #f1f5f9; flex-wrap: wrap;">
                                <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $deal['client_phone']) ?>?text=Hello%20<?= urlencode($deal['client_name']) ?>,%20this%20is%20Perpetuah%20from%20Panda%20Realty%20following%20up%20on%20your%20property%20deal." target="_blank" class="btn" style="background: #25D366; color: white; padding: 5px 10px; font-size: 11px; border-radius: 4px; display: inline-flex; align-items: center; gap: 4px; font-weight: 600;">
                                    <i class="fab fa-whatsapp"></i> Chat
                                </a>
                                <a href="tel:<?= htmlspecialchars($deal['client_phone']) ?>" class="btn" style="background: #f1f5f9; color: #1e293b; padding: 5px 10px; font-size: 11px; border-radius: 4px; display: inline-flex; align-items: center; gap: 4px;">
                                    <i class="fas fa-phone-alt"></i> Call
                                </a>

                                <!-- Stage Changer Dropdown -->
                                <form action="crm.php" method="POST" style="display: inline-block; margin-left: auto;">
                                    <input type="hidden" name="csrf_token" value="<?= get_csrf_token() ?>">
                                    <input type="hidden" name="update_deal_stage" value="1">
                                    <input type="hidden" name="deal_id" value="<?= $deal['id'] ?>">
                                    <select name="stage" onchange="this.form.submit()" style="font-size: 11px; padding: 4px 6px; border-radius: 4px; border: 1px solid #cbd5e1; background: #ffffff; cursor: pointer; font-weight: 600;">
                                        <?php foreach ($stages_def as $sk => $sv): ?>
                                            <option value="<?= $sk ?>" <?= $deal['stage'] === $sk ? 'selected' : '' ?>><?= $sv['label'] ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<!-- Attached Property Video Modal -->
<div id="dealVideoModal" class="modal">
    <div class="modal-content modal-lg" style="background: #0f172a; color: #fff; padding: 0; overflow: hidden;">
        <button type="button" class="modal-close" style="background: rgba(255,255,255,0.15); color: #fff;" onclick="closeModal('dealVideoModal')">
            <i class="fas fa-times"></i>
        </button>

        <div style="padding: 20px 25px; border-bottom: 1px solid rgba(255,255,255,0.1);">
            <h4 id="dealVideoTitle" style="font-size: 18px; margin: 0; color: var(--admin-accent);">Attached Property Video</h4>
            <p id="dealVideoClient" style="font-size: 12px; color: #94a3b8; margin: 3px 0 0;">Reviewing property tour for client</p>
        </div>

        <div style="position: relative; padding-bottom: 56.25%; height: 0; background: #000;">
            <iframe id="dealVideoFrame" src="" style="position: absolute; inset: 0; width: 100%; height: 100%; border: none;" allowfullscreen></iframe>
        </div>
    </div>
</div>

<!-- Add Lead Modal -->
<div id="addLeadModal" class="modal">
    <div class="modal-content">
        <button type="button" class="modal-close" onclick="closeModal('addLeadModal')"><i class="fas fa-times"></i></button>
        <h3 class="font-serif" style="font-size: 22px; margin-bottom: 20px; color: var(--admin-text);">Add New Client / Opportunity</h3>

        <form action="crm.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?= get_csrf_token() ?>">
            <input type="hidden" name="add_lead" value="1">

            <div class="admin-form-grid">
                <div class="admin-form-group">
                    <label>Client Full Name *</label>
                    <input type="text" name="client_name" placeholder="e.g. Dr. Evans Kipchumba" required>
                </div>

                <div class="admin-form-group">
                    <label>Phone Number (WhatsApp) *</label>
                    <input type="tel" name="client_phone" placeholder="0708 289 852" required>
                </div>

                <div class="admin-form-group">
                    <label>Email Address</label>
                    <input type="email" name="client_email" placeholder="client@example.com">
                </div>

                <div class="admin-form-group">
                    <label>Deal / Budget Value (KES)</label>
                    <input type="number" step="1000" name="deal_value" placeholder="2200000">
                </div>
            </div>

            <div class="admin-form-grid">
                <div class="admin-form-group">
                    <label>Associated Property</label>
                    <select name="property_id">
                        <option value="">General Inquiry / Land Tour</option>
                        <?php
                        $p_res = mysqli_query($conn, "SELECT id, title, location, price_kes FROM properties WHERE status != 'sold'");
                        if ($p_res) {
                            while ($p = mysqli_fetch_assoc($p_res)) {
                                echo '<option value="' . $p['id'] . '">' . htmlspecialchars($p['title']) . ' (' . htmlspecialchars($p['location']) . ')</option>';
                            }
                        }
                        ?>
                    </select>
                </div>

                <div class="admin-form-group">
                    <label>Initial Pipeline Stage</label>
                    <select name="stage">
                        <?php foreach ($stages_def as $sk => $sv): ?>
                            <option value="<?= $sk ?>" <?= $sk === 'new_lead' ? 'selected' : '' ?>><?= $sv['label'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="admin-form-group">
                <label>Assigned Realtor</label>
                <input type="text" name="assigned_agent" value="Perpetuah Chepchirchir">
            </div>

            <div class="admin-form-group">
                <label>Lead Notes &amp; Installment Terms</label>
                <textarea name="notes" rows="3" placeholder="e.g. Inquiring about 50x100 plot in Annex or studio apartment in Pioneer. Prefers 12-month installment plan."></textarea>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%; padding: 14px; border-radius: 8px; font-weight: 700; cursor: pointer;">
                <i class="fas fa-plus-circle"></i> Save CRM Opportunity
            </button>
        </form>
    </div>
</div>

<script>
function openDealVideoModal(videoUrl, clientName, propTitle) {
    document.getElementById('dealVideoFrame').src = videoUrl;
    document.getElementById('dealVideoTitle').innerText = propTitle || 'Property Video Tour';
    document.getElementById('dealVideoClient').innerText = 'Opportunity for: ' + clientName;
    openModal('dealVideoModal');
}
</script>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>

