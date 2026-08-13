<?php
/**
 * includes/admin_header.php
 * -----------------------------------------------------------
 * Included at the top of every admin page (after require_admin_login()).
 * Expects these variables to already be set by the calling page:
 *   $pageTitle   - shown in <title> and the topbar heading
 *   $currentPage - which sidebar link to highlight (e.g. 'dashboard')
 * Pair with includes/admin_footer.php at the end of the page.
 * -----------------------------------------------------------
 */
$pageTitle = $pageTitle ?? 'Admin';
$initial   = strtoupper(substr($_SESSION['admin_name'] ?? 'A', 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> | Doctor Booking System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/admin.css" rel="stylesheet">
</head>
<body class="admin-body">
<div class="admin-wrapper">

    <?php require __DIR__ . '/admin_sidebar.php'; ?>

    <div class="admin-main">
        <div class="admin-topbar">
            <h1><?= htmlspecialchars($pageTitle) ?></h1>
            <div class="admin-user">
                <div class="admin-avatar"><?= htmlspecialchars($initial) ?></div>
                <span><?= htmlspecialchars($_SESSION['admin_name'] ?? '') ?></span>
                <a href="../admin/logout.php" class="btn btn-sm btn-outline-danger ms-2">Logout</a>
            </div>
        </div>

        <div class="admin-content">
