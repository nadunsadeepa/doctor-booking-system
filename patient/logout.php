<?php
/**
 * patient/logout.php
 * -----------------------------------------------------------
 * Destroys the patient session and returns to the login page.
 * -----------------------------------------------------------
 */
require_once __DIR__ . '/../config/app_config.php';

unset($_SESSION['patient_id'], $_SESSION['patient_name']);

session_destroy();
header('Location: login.php');
exit();
