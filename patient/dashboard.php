<?php
/**
 * patient/dashboard.php
 * -----------------------------------------------------------
 * Module 13 - Patient Dashboard (overview page)
 * Appointments/History/Reports/Profile/Notifications each got
 * their own page this module; this is the landing overview.
 * -----------------------------------------------------------
 */
require_once __DIR__ . '/../config/app_config.php';
require_once __DIR__ . '/../config/db_config.php';
require_once __DIR__ . '/../includes/functions.php';

require_patient_login();

$patientId = $_SESSION['patient_id'];

$upcomingStmt = $pdo->prepare(
    "SELECT a.*, d.doctor_name, d.hospital_name
     FROM appointments a
     JOIN doctors d ON d.id = a.doctor_id
     WHERE a.patient_id = :id AND a.appointment_date >= CURDATE()
       AND a.status IN ('Pending','Confirmed')
     ORDER BY a.appointment_date ASC, a.queue_number ASC"
);
$upcomingStmt->execute(['id' => $patientId]);
$upcoming = $upcomingStmt->fetchAll();

$countsStmt = $pdo->prepare(
    "SELECT
        SUM(CASE WHEN status = 'Completed' THEN 1 ELSE 0 END) AS completed,
        SUM(CASE WHEN status = 'Cancelled' THEN 1 ELSE 0 END) AS cancelled
     FROM appointments WHERE patient_id = :id"
);
$countsStmt->execute(['id' => $patientId]);
$counts = $countsStmt->fetch();

$reportsCountStmt = $pdo->prepare("SELECT COUNT(*) FROM medical_reports WHERE patient_id = :id");
$reportsCountStmt->execute(['id' => $patientId]);
$reportsCount = (int)$reportsCountStmt->fetchColumn();

$flash = get_flash();

$pageTitle   = 'Dashboard';
$currentPage = 'dashboard';
require_once __DIR__ . '/../includes/patient_header.php';
?>
<head>
    <link href="../assets/css/patient.css" rel="stylesheet">
</head>
<!-- ====== DARK MODE TOGGLE ====== -->
<button class="dark-toggle" id="darkModeToggle" aria-label="Toggle dark mode">
    <i class="fas fa-moon" id="toggleIcon"></i>
</button>

<?php if ($flash): ?>
    <div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : 'danger' ?> mt-3">
        <?= htmlspecialchars($flash['message']) ?>
    </div>
<?php endif; ?>

<div class="pt-4 pb-2">
    <h2 class="mb-1">Hi, <?= htmlspecialchars($_SESSION['patient_name']) ?> 👋</h2>
    <p class="text-muted mb-0">Here's what's coming up.</p>
</div>

<div class="row g-3 my-1">
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div class="stat-number"><?= count($upcoming) ?></div>
            <div class="stat-label">Upcoming</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div class="stat-number"><?= (int)($counts['completed'] ?? 0) ?></div>
            <div class="stat-label">Completed Visits</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div class="stat-number"><?= (int)($counts['cancelled'] ?? 0) ?></div>
            <div class="stat-label">Cancelled</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div class="stat-number"><?= $reportsCount ?></div>
            <div class="stat-label">Reports Uploaded</div>
        </div>
    </div>
</div>

<div class="admin-panel my-3 text-center">
    <h5 class="mb-2">Need to see a doctor?</h5>
    <a href="categories.php" class="btn btn-clinic" style="width:auto;padding-inline:28px;">Book an Appointment</a>
</div>

<div class="admin-panel">
    <div class="d-flex justify-content-between align-items-center mb-2">
        <h5 class="mb-0">Upcoming Appointments</h5>
        <a href="history.php" class="small">View full history →</a>
    </div>
    <?php if ($upcoming): ?>
        <table class="admin-table">
            <thead><tr><th>Doctor</th><th>Hospital</th><th>Date</th><th>Queue #</th><th>Time</th><th>Status</th><th></th></tr></thead>
            <tbody>
                <?php foreach ($upcoming as $a): ?>
                    <tr>
                        <td>Dr. <?= htmlspecialchars($a['doctor_name']) ?></td>
                        <td><?= htmlspecialchars($a['hospital_name']) ?></td>
                        <td><?= htmlspecialchars(date('d M Y', strtotime($a['appointment_date']))) ?></td>
                        <td>#<?= (int)$a['queue_number'] ?></td>
                        <td><?= htmlspecialchars(date('h:i A', strtotime($a['appointment_time']))) ?></td>
                        <td><span class="badge-success-soft"><?= htmlspecialchars($a['status']) ?></span></td>
                        <td>
                            <a href="queue_status.php?id=<?= $a['id'] ?>" class="btn btn-sm btn-outline-secondary">Track</a>
                            <a href="appointment_slip.php?id=<?= $a['id'] ?>" target="_blank" class="btn btn-sm btn-outline-secondary">Slip</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p class="text-muted mb-0">No upcoming appointments yet.</p>
    <?php endif; ?>
</div>

<script>
    // Dark mode toggle
    (function() {
        const toggleBtn = document.getElementById('darkModeToggle');
        const icon = document.getElementById('toggleIcon');
        const body = document.body;

        const savedMode = localStorage.getItem('darkMode');
        if (savedMode === 'enabled') {
            body.classList.add('dark-mode');
            icon.classList.remove('fa-moon');
            icon.classList.add('fa-sun');
        }

        toggleBtn.addEventListener('click', function() {
            body.classList.toggle('dark-mode');
            const isDark = body.classList.contains('dark-mode');
            if (isDark) {
                icon.classList.remove('fa-moon');
                icon.classList.add('fa-sun');
                localStorage.setItem('darkMode', 'enabled');
            } else {
                icon.classList.remove('fa-sun');
                icon.classList.add('fa-moon');
                localStorage.setItem('darkMode', 'disabled');
            }
        });
    })();
</script>

<?php require_once __DIR__ . '/../includes/patient_footer.php'; ?>