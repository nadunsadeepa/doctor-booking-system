<?php
/**
 * includes/admin_sidebar.php
 * -----------------------------------------------------------
 * Included by includes/admin_header.php.
 * $currentPage should be set (e.g. 'dashboard') by the calling
 * page before including the header, so the right link
 * highlights as active.
 * -----------------------------------------------------------
 */
$currentPage = $currentPage ?? '';

function nav_active($page, $current)
{
    return $page === $current ? 'active' : '';
}
?>
<aside class="admin-sidebar">
    <div class="brand">🏥 DoctorBooking</div>

    <a href="../admin/dashboard.php" class="<?= nav_active('dashboard', $currentPage) ?>">Dashboard</a>
    <a href="../admin/doctors.php" class="<?= nav_active('doctors', $currentPage) ?>">Doctors</a>
    <a href="../admin/categories.php" class="<?= nav_active('categories', $currentPage) ?>">Disease Categories</a>
    <a href="../admin/appointments.php" class="<?= nav_active('appointments', $currentPage) ?>">Appointments</a>
    <a href="../admin/sms_logs.php" class="<?= nav_active('sms_logs', $currentPage) ?>">SMS Logs</a>
    <a href="../admin/reports.php" class="<?= nav_active('reports', $currentPage) ?>">Reports</a>
    <a href="../admin/settings.php" class="<?= nav_active('settings', $currentPage) ?>">Settings</a>
</aside>
