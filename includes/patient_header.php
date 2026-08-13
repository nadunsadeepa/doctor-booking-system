<?php
/**
 * includes/patient_header.php
 * -----------------------------------------------------------
 * Module 13 - Patient Dashboard
 * Shared top nav for every patient page. Expects:
 *   $pageTitle   - <title> text
 *   $currentPage - which nav link to highlight
 * Pair with includes/patient_footer.php at the end of the page.
 * -----------------------------------------------------------
 */
$pageTitle   = $pageTitle ?? 'Patient Portal';
$currentPage = $currentPage ?? '';

$unreadStmt = $pdo->prepare(
    "SELECT COUNT(*) FROM notifications WHERE patient_id = :id AND status = 'Unread'"
);
$unreadStmt->execute(['id' => $_SESSION['patient_id']]);
$unreadCount = (int)$unreadStmt->fetchColumn();

function patient_nav_active($page, $current)
{
    return $page === $current ? 'active' : '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> | Doctor Booking System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/admin.css" rel="stylesheet">
    <style>
        .patient-nav {
            background: linear-gradient(160deg, #144C49 0%, #1F6F6B 100%);
            padding: 0 20px;
        }
        .patient-nav .navbar-brand { color: #fff; font-weight: 700; }
        .patient-nav a.nav-link {
            color: rgba(255,255,255,0.85);
            font-size: 0.92rem;
            padding: 14px 14px !important;
            position: relative;
        }
        .patient-nav a.nav-link:hover, .patient-nav a.nav-link.active { color: #fff; font-weight: 600; }
        .patient-nav .badge-dot {
            background: #E4573D; color: #fff; border-radius: 20px;
            font-size: 0.68rem; padding: 1px 6px; margin-left: 4px; vertical-align: top;
        }
    </style>
</head>
<body class="admin-body">

<nav class="navbar navbar-expand-lg patient-nav">
    <div class="container-fluid">
        <a class="navbar-brand" href="dashboard.php">🏥 DoctorBooking</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#patientNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="patientNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link <?= patient_nav_active('dashboard', $currentPage) ?>" href="dashboard.php">Dashboard</a></li>
                <li class="nav-item"><a class="nav-link <?= patient_nav_active('categories', $currentPage) ?>" href="categories.php">Book Appointment</a></li>
                <li class="nav-item"><a class="nav-link <?= patient_nav_active('history', $currentPage) ?>" href="history.php">History</a></li>
                <li class="nav-item"><a class="nav-link <?= patient_nav_active('reports', $currentPage) ?>" href="reports.php">Reports</a></li>
                <li class="nav-item">
                    <a class="nav-link <?= patient_nav_active('notifications', $currentPage) ?>" href="notifications.php">
                        Notifications
                        <?php if ($unreadCount > 0): ?><span class="badge-dot"><?= $unreadCount ?></span><?php endif; ?>
                    </a>
                </li>
                <li class="nav-item"><a class="nav-link <?= patient_nav_active('profile', $currentPage) ?>" href="profile.php">Profile</a></li>
                <li class="nav-item"><a class="nav-link" href="logout.php">Logout</a></li>
            </ul>
        </div>
    </div>
</nav>

<div class="admin-content" style="max-width:900px;margin:0 auto;">
