<?php
/**
 * config/app_config.php
 * -----------------------------------------------------------
 * App-wide settings. Include this BEFORE db_config.php at the
 * very top of every page (it starts the session).
 * -----------------------------------------------------------
 */

// Start session once, on every page
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

date_default_timezone_set('Asia/Colombo');

// Change this if your project folder name is different
define('BASE_URL', 'http://localhost/doctor/');

// Error display (turn OFF display_errors on a live server)
ini_set('display_errors', 1);
error_reporting(E_ALL);
