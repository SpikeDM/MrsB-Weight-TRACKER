<?php
// ============================================================
// MrsB Tracker — Admin Session Guard
// Include at the top of every admin page (after config)
// ============================================================

if (session_status() === PHP_SESSION_NONE) {
    session_name(ADMIN_SESSION_NAME);
    session_start();
}

if (empty($_SESSION['admin_logged_in'])) {
    header('Location: /admin/login.php');
    exit;
}
