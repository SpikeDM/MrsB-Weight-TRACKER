<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

session_start();
session_destroy();

header('Location: ' . SITE_URL . '/tracker/login.php?t=' . urlencode($_GET['t'] ?? ''));
exit;
