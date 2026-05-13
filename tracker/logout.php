<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

session_start();
session_unset();
session_destroy();

header('Location: ' . SITE_URL . '/tracker/login.php');
exit;
