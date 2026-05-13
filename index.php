<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';

// Root redirect — send admin to dashboard, everyone else to login
session_name(ADMIN_SESSION_NAME);
session_start();

if (!empty($_SESSION['admin_logged_in'])) {
    redirect(SITE_URL . '/admin/');
} else {
    redirect(SITE_URL . '/admin/login.php');
}
