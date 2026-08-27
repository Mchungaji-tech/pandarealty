<?php
/**
 * Panda Realty - Staff & User Account Management (Super Admin)
 * Designed & Developed by TekTrend
 */

require_once __DIR__ . '/../config/settings.php';
require_admin();
require_capability('manage_users');

$conn = get_db_connection();
$msg = '';
$err = '';
$manageable_roles = get_manageable_roles();

// Check current superadmin count
$res_sa_cnt = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM users WHERE role = 'superadmin'");
$superadmin_count = ($res_sa_cnt && $row = mysqli_fetch_assoc($res_sa_cnt)) ? (int)$row['cnt'] : 0;

// Handle Add User
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_user'])) {
    $name = clean_input($_POST['name'] ?? '');
    $email = clean_input($_POST['email'] ?? '');
    $phone = clean_input($_POST['phone'] ?? '');
    $role = clean_input($_POST['role'] ?? 'admin');
    $pass = $_POST['password'] ?? '';
    $csrf = clean_input($_POST['csrf_token'] ?? '');

    if (!verify_csrf_token($csrf)) {
        $err = "Security session expired. Please refresh.";
    } elseif (empty($name) || empty($email) || empty($pass)) {
        $err = "Please complete all required fields.";
    } elseif (!in_array($role, $manageable_roles, true)) {
        $err = "You do not have permission to create this role.";
    } elseif ($role === 'superadmin' && $superadmin_count >= 2) {
        $err = "Quota Reached: Panda Realty allows a maximum of 2 Super Administrators. You can create additional Admins as needed.";
    } else {
        $email_safe = db_escape($email);
        $check = mysqli_query($conn, "SELECT id FROM users WHERE email = '$email_safe'");
        if ($check && mysqli_num_rows($check) > 0) {
            $err = "A user with this email address already exists.";
        } else {
            $hash = password_hash($pass, PASSWORD_BCRYPT);
            $secret = generate_totp_secret();
            $name_safe = db_escape($name);
            $phone_safe = db_escape($phone);
            $hash_safe = db_escape($hash);
            $role_safe = db_escape($role);
            $secret_safe = db_escape($secret);
            $ins = "INSERT INTO users (name, email, phone, password, role, two_factor_secret, two_factor_enabled) 
                    VALUES ('$name_safe', '$email_safe', '$phone_safe', '$hash_safe', '$role_safe', '$secret_safe', 0)";
            if (mysqli_query($conn, $ins)) {
                log_security_action('USER_CREATED', "Super Admin created $role account: $email");
                $msg = "New $role account created successfully for $name!";
                if ($role === 'superadmin') $superadmin_count++;
            } else {
                $err = "Database Error: " . mysqli_error($conn);
            }
        }
    }
}

// Handle Delete (Cannot delete initial seeded superadmins or self)
if (isset($_GET['delete'])) {
    $del_id = (int)$_GET['delete'];
    if ($del_id === (int)$_SESSION['user_id']) {
        $err = "You cannot delete your own active super admin session.";
    } else {
        mysqli_query($conn, "DELETE FROM users WHERE id = $del_id AND id NOT IN (1, 2)");
        log_security_action('USER_DELETED', "User ID #$del_id was removed");
        $msg = "User account removed.";
        
        $res_sa_cnt = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM users WHERE role = 'superadmin'");
        $superadmin_count = ($res_sa_cnt && $row = mysqli_fetch_assoc($res_sa_cnt)) ? (int)$row['cnt'] : 0;
    }
}

// Fetch all users
$res = mysqli_query($conn, "SELECT * FROM users ORDER BY role ASC, id ASC");
$users = [];
if ($res) {
    while ($row = mysqli_fetch_assoc($res)) {
        $users[] = $row;
    }
}

$admin_page_title = "Staff & User Account Management";
require_once __DIR__ . '/includes/admin-header.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; flex-wrap: wrap; gap: 15px;">
    <div>
        <h3 style="font-size: 20px; font-weight: 700; color: var(--admin-text);">People, Roles & Access Control</h3>
        <p style="color: var(--admin-muted); font-size: 13px;">Manage staff, admins, executives, developers, and registered client accounts.</p>
    </div>

    <div style="display: flex; gap: 10px; align-items: center;">
        <span style="font-size: 12px; background: rgba(197, 160, 89, 0.15); color: var(--admin-accent); padding: 8px 14px; border-radius: 20px; border: 1px solid var(--admin-accent); font-weight: 600;">
            <i class="fas fa-crown"></i> Super Admins: <strong><?= $superadmin_count ?>/2</strong>
        </span>

        <button type="button" class="btn" onclick="openModal('addUserModal')" style="background: var(--admin-accent); color: #000; font-weight: 700; padding: 12px 20px; border-radius: 6px; font-size: 13px; border: none; cursor: pointer;">
            <i class="fas fa-user-plus"></i> Add New Staff / Admin
        </button>
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
                    <th>User Name & Contact</th>
                    <th>Email Address</th>
                    <th>System Role</th>
                    <th>2FA Status</th>
                    <th>Presence</th>
                    <th>Joined</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                    <tr>
                        <td>
                            <strong><?= htmlspecialchars($u['name']) ?></strong><br>
                            <span style="font-size: 11px; color: var(--admin-muted);"><?= htmlspecialchars($u['phone'] ?? 'No phone') ?></span>
                        </td>
                        <td><?= htmlspecialchars($u['email']) ?></td>
                        <td>
                            <span class="sidebar-role-badge <?= get_role_badge_class($u['role']) ?>">
                                <?= htmlspecialchars(get_role_label($u['role'])) ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($u['two_factor_enabled']): ?>
                                <span class="status-pill success"><i class="fas fa-shield-alt"></i> 2FA ACTIVE</span>
                            <?php else: ?>
                                <span class="status-pill warning">DISABLED</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($u['is_online']): ?>
                                <span class="status-pill success"><span class="pulse-dot" style="display: inline-block; margin-right: 4px;"></span> ONLINE</span>
                            <?php else: ?>
                                <span class="status-pill" style="background: rgba(255,255,255,0.05); color: var(--admin-muted);">OFFLINE</span>
                            <?php endif; ?>
                        </td>
                        <td><?= date('M d, Y', strtotime($u['created_at'])) ?></td>
                        <td>
                            <?php if (!in_array((int)$u['id'], [1, 2]) && (int)$u['id'] !== (int)$_SESSION['user_id']): ?>
                                <a href="users.php?delete=<?= $u['id'] ?>" class="btn-icon delete" title="Delete User" onclick="return confirm('Remove user account?')"><i class="fas fa-trash-alt"></i></a>
                            <?php else: ?>
                                <span style="font-size: 11px; color: var(--admin-muted);"><i class="fas fa-lock"></i> Protected</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add User Modal -->
<div id="addUserModal" class="modal">
    <div class="modal-content">
        <button type="button" class="modal-close" onclick="closeModal('addUserModal')"><i class="fas fa-times"></i></button>
        <h3 class="font-serif" style="font-size: 22px; margin-bottom: 20px; color: var(--admin-text);">Create Staff or Admin Account</h3>

        <form action="users.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?= get_csrf_token() ?>">
            <input type="hidden" name="add_user" value="1">

            <div class="admin-form-group">
                <label>Full Name *</label>
                <input type="text" name="name" placeholder="e.g. Kelvin Kiprono" required>
            </div>

            <div class="admin-form-group">
                <label>Email Address *</label>
                <input type="email" name="email" placeholder="kelvin@pandarealty.co.ke" required>
            </div>

            <div class="admin-form-group">
                <label>Phone Number (WhatsApp)</label>
                <input type="tel" name="phone" placeholder="0708 289 852">
            </div>

            <div class="admin-form-group">
                <label>Role Privilege *</label>
                <select name="role" required>
                    <?php foreach ($manageable_roles as $role_option): ?>
                        <?php if ($role_option === 'superadmin' && $superadmin_count >= 2): ?>
                            <option value="superadmin" disabled>Super Admin (Max 2/2 Reached)</option>
                        <?php else: ?>
                            <option value="<?= htmlspecialchars($role_option) ?>" <?= $role_option === 'staff' ? 'selected' : '' ?>><?= htmlspecialchars(get_role_label($role_option)) ?></option>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </select>
                <span style="font-size: 11px; color: var(--admin-muted); margin-top: 4px; display: block;">
                    CEO can manage business users, while developer and super admin roles remain restricted.
                </span>
            </div>

            <div class="admin-form-group">
                <label>Initial Password (Min. 8 chars) *</label>
                <input type="password" name="password" placeholder="••••••••" required>
            </div>

            <button type="submit" class="btn" style="width: 100%; background: var(--admin-accent); color: #000; font-weight: 700; padding: 14px; border-radius: 6px; border: none; cursor: pointer;">
                <i class="fas fa-user-check"></i> Create Account
            </button>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
