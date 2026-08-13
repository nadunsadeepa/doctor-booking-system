<?php
/**
 * admin/dashboard.php
 * -----------------------------------------------------------
 * Module 05 - Admin Dashboard
 * Shows live counts + recent activity using tables that exist
 * so far (doctors, patients, login_logs). Doctor/category/
 * appointment management links in the sidebar are wired up
 * but their pages are built in later modules (06, 07, 09...).
 * -----------------------------------------------------------
 */
require_once __DIR__ . '/../config/app_config.php';
require_once __DIR__ . '/../config/db_config.php';
require_once __DIR__ . '/../includes/functions.php';

require_admin_login();

// ---- Stats ----
$totalDoctors  = $pdo->query("SELECT COUNT(*) FROM doctors")->fetchColumn();
$totalPatients = $pdo->query("SELECT COUNT(*) FROM patients")->fetchColumn();

$loginsToday = $pdo->query(
    "SELECT COUNT(*) FROM login_logs
     WHERE status = 'success' AND DATE(created_at) = CURDATE()"
)->fetchColumn();

$failedLoginsToday = $pdo->query(
    "SELECT COUNT(*) FROM login_logs
     WHERE status = 'failed' AND DATE(created_at) = CURDATE()"
)->fetchColumn();

// ---- Recently registered patients ----
$recentPatients = $pdo->query(
    "SELECT full_name, email, created_at
     FROM patients ORDER BY created_at DESC LIMIT 5"
)->fetchAll();

// ---- Recent login activity (any role) ----
$recentLogins = $pdo->query(
    "SELECT user_role, email, status, created_at
     FROM login_logs ORDER BY created_at DESC LIMIT 8"
)->fetchAll();

$pageTitle   = 'Dashboard';
$currentPage = 'dashboard';
$flash       = get_flash();

require_once __DIR__ . '/../includes/admin_header.php';
?>

<?php if ($flash): ?>
    <div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : 'danger' ?>">
        <?= htmlspecialchars($flash['message']) ?>
    </div>
<?php endif; ?>

<!-- Stat cards -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div class="stat-number"><?= (int)$totalDoctors ?></div>
            <div class="stat-label">Total Doctors</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div class="stat-number"><?= (int)$totalPatients ?></div>
            <div class="stat-label">Total Patients</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div class="stat-number"><?= (int)$loginsToday ?></div>
            <div class="stat-label">Successful Logins Today</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div class="stat-number"><?= (int)$failedLoginsToday ?></div>
            <div class="stat-label">Failed Logins Today</div>
        </div>
    </div>
</div>

<div class="row g-3">
    <!-- Recent patients -->
    <div class="col-lg-6">
        <div class="admin-panel">
            <h5>Recently Registered Patients</h5>
            <?php if ($recentPatients): ?>
                <table class="admin-table">
                    <thead>
                        <tr><th>Name</th><th>Email</th><th>Registered</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentPatients as $p): ?>
                            <tr>
                                <td><?= htmlspecialchars($p['full_name']) ?></td>
                                <td><?= htmlspecialchars($p['email']) ?></td>
                                <td><?= htmlspecialchars(date('d M Y', strtotime($p['created_at']))) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="text-muted mb-0">No patients have registered yet.</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Recent login activity -->
    <div class="col-lg-6">
        <div class="admin-panel">
            <h5>Recent Login Activity</h5>
            <?php if ($recentLogins): ?>
                <table class="admin-table">
                    <thead>
                        <tr><th>Role</th><th>Email</th><th>Status</th><th>Time</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentLogins as $l): ?>
                            <tr>
                                <td class="text-capitalize"><?= htmlspecialchars($l['user_role']) ?></td>
                                <td><?= htmlspecialchars($l['email']) ?></td>
                                <td>
                                    <?php if ($l['status'] === 'success'): ?>
                                        <span class="badge-success-soft">Success</span>
                                    <?php else: ?>
                                        <span class="badge-danger-soft">Failed</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars(date('d M, H:i', strtotime($l['created_at']))) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="text-muted mb-0">No login activity yet.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
