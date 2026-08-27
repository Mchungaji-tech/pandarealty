<?php
/**
 * Panda Realty - Logout & Session Destruction
 * Designed & Developed by TekTrend
 */
require_once __DIR__ . '/config/functions.php';

$conn = get_db_connection();

if (is_logged_in()) {
    $uid = (int)$_SESSION['user_id'];
    @mysqli_query($conn, "UPDATE users SET is_online = 0 WHERE id = $uid");
    log_security_action('USER_LOGOUT', "User signed out", 'success', $uid);
}

if (!empty($_SESSION['superadmin_id'])) {
    $id = (int)$_SESSION['superadmin_id'];
    @mysqli_query($conn, "UPDATE super_admins SET is_online = 0 WHERE id = $id");
}
if (!empty($_SESSION['ceo_id'])) {
    $id = (int)$_SESSION['ceo_id'];
    @mysqli_query($conn, "UPDATE ceo_users SET is_online = 0 WHERE id = $id");
}
if (!empty($_SESSION['staff_id'])) {
    $id = (int)$_SESSION['staff_id'];
    @mysqli_query($conn, "UPDATE staff_members SET is_online = 0 WHERE id = $id");
}

destroy_active_session();
redirect_to('', ['msg' => 'logged_out']);
