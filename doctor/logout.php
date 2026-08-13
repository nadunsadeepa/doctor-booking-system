<?php
/**
 * doctor/logout.php
 * -----------------------------------------------------------
 * Destroys the doctor session and returns to the login page.
 * -----------------------------------------------------------
 */
require_once __DIR__ . '/../config/app_config.php';

unset($_SESSION['doctor_id'], $_SESSION['doctor_name']);

session_destroy();
header('Location: login.php');
exit();
