<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

session_name(ADMIN_SESSION_NAME);
session_start();
session_destroy();

redirect(SITE_URL . '/admin/login.php');
