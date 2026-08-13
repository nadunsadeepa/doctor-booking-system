<?php
/**
 * admin/logout.php
 * -----------------------------------------------------------
 * Destroys the admin session and returns to the login page.
 * -----------------------------------------------------------
 */
require_once __DIR__ . '/../config/app_config.php';

// Clear only admin-related session data (safe if other roles
// ever shared a session in a more advanced setup).
unset($_SESSION['admin_id'], $_SESSION['admin_username'], $_SESSION['admin_name']);

session_destroy();
header('Location: login.php');
exit();
